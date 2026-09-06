<?php
/**
 * ASENA Enterprise - Order Lifecycle & Fulfillment State Machine
 * Benchmarked against Amazon.com Fulfillment Pipeline & Carrier Tracking
 */

class OrderLifecycleService
{
    private PDO $pdo;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID            = 'paid';
    public const STATUS_CONFIRMED       = 'confirmed';        // انبارداری تأیید کرد
    public const STATUS_PICKING         = 'picking';          // در حال جمع‌آوری اقلام
    public const STATUS_PACKED          = 'packed';           // بسته‌بندی و بارکدگذاری
    public const STATUS_HANDED_OVER     = 'handed_over';      // تحویل به پست / تیپاکس
    public const STATUS_OUT_DELIVERY    = 'out_for_delivery'; // در مسیر تحویل (پیک)
    public const STATUS_DELIVERED       = 'delivered';        // تحویل نهایی به مشتری
    public const STATUS_CANCELLED       = 'cancelled';        // لغو شده
    public const STATUS_RETURNED        = 'returned';         // مرجوعی

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            $this->pdo = $pdo;
        }
    }

    /**
     * Persian title and badge style for each state.
     */
    public static function getStatusMeta(string $status): array
    {
        $meta = [
            self::STATUS_PENDING_PAYMENT => ['title' => 'در انتظار پرداخت', 'badge' => 'bg-amber-100 text-amber-800', 'step' => 1],
            self::STATUS_PAID            => ['title' => 'پرداخت موفق', 'badge' => 'bg-blue-100 text-blue-800', 'step' => 2],
            'processing'                 => ['title' => 'در حال پردازش', 'badge' => 'bg-blue-100 text-blue-800', 'step' => 2],
            self::STATUS_CONFIRMED       => ['title' => 'تأیید انبارداری', 'badge' => 'bg-indigo-100 text-indigo-800', 'step' => 3],
            self::STATUS_PICKING         => ['title' => 'در حال جمع‌آوری اقلام', 'badge' => 'bg-purple-100 text-purple-800', 'step' => 4],
            self::STATUS_PACKED          => ['title' => 'بسته‌بندی و الصاق بارکد', 'badge' => 'bg-teal-100 text-teal-800', 'step' => 5],
            self::STATUS_HANDED_OVER     => ['title' => 'تحویل به پست/تیپاکس', 'badge' => 'bg-cyan-100 text-cyan-800', 'step' => 6],
            'shipped'                    => ['title' => 'ارسال شده', 'badge' => 'bg-cyan-100 text-cyan-800', 'step' => 6],
            self::STATUS_OUT_DELIVERY    => ['title' => 'پیک در مسیر تحویل', 'badge' => 'bg-sky-100 text-sky-800', 'step' => 7],
            self::STATUS_DELIVERED       => ['title' => 'تحویل نهایی به مشتری', 'badge' => 'bg-emerald-100 text-emerald-800', 'step' => 8],
            self::STATUS_CANCELLED       => ['title' => 'لغو شده', 'badge' => 'bg-red-100 text-red-800', 'step' => 0],
            self::STATUS_RETURNED        => ['title' => 'مرجوع شده', 'badge' => 'bg-gray-100 text-gray-800', 'step' => 0],
        ];
        return $meta[$status] ?? ['title' => $status, 'badge' => 'bg-gray-100 text-gray-800', 'step' => 0];
    }

    /**
     * Map allowed next transition states (DAG fulfillment machine).
     */
    public static function getAllowedNextStatuses(string $current): array
    {
        $transitions = [
            self::STATUS_PENDING_PAYMENT => [self::STATUS_PAID, self::STATUS_CANCELLED],
            self::STATUS_PAID            => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            'processing'                 => [self::STATUS_CONFIRMED, self::STATUS_PICKING, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED       => [self::STATUS_PICKING, self::STATUS_CANCELLED],
            self::STATUS_PICKING         => [self::STATUS_PACKED, self::STATUS_CANCELLED],
            self::STATUS_PACKED          => [self::STATUS_HANDED_OVER, 'shipped', self::STATUS_OUT_DELIVERY],
            self::STATUS_HANDED_OVER     => [self::STATUS_OUT_DELIVERY, self::STATUS_DELIVERED],
            'shipped'                    => [self::STATUS_OUT_DELIVERY, self::STATUS_DELIVERED],
            self::STATUS_OUT_DELIVERY    => [self::STATUS_DELIVERED, self::STATUS_RETURNED],
            self::STATUS_DELIVERED       => [self::STATUS_RETURNED],
            self::STATUS_CANCELLED       => [],
            self::STATUS_RETURNED        => [],
        ];
        return $transitions[$current] ?? [self::STATUS_CONFIRMED, self::STATUS_PACKED, self::STATUS_HANDED_OVER, self::STATUS_DELIVERED];
    }

    /**
     * Advance order to new status with audit trail and optional customer SMS.
     */
    public function transition(
        int $orderId,
        string $newStatus,
        string $actorType = 'admin',
        ?int $actorId = null,
        ?string $carrierName = null,
        ?string $trackingCode = null,
        ?string $notes = null
    ): array {
        $stmt = $this->pdo->prepare("SELECT status, user_id FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return ['success' => false, 'message' => 'سفارش یافت نشد.'];
        }

        $oldStatus = $order['status'];

        $this->pdo->beginTransaction();
        try {
            // Update order record
            $upSql = "UPDATE orders SET status = ?";
            $params = [$newStatus];

            if (!empty($carrierName)) {
                $upSql .= ", carrier_name = ?";
                $params[] = $carrierName;
            }
            if (!empty($trackingCode)) {
                $upSql .= ", tracking_code = ?, post_tracking_code = ?";
                $params[] = $trackingCode;
                $params[] = $trackingCode;
            }
            $upSql .= " WHERE id = ?";
            $params[] = $orderId;

            $this->pdo->prepare($upSql)->execute($params);

            // Generate direct tracking URL based on carrier
            $trackingUrl = null;
            if (!empty($trackingCode)) {
                if (stripos($carrierName, 'tipax') !== false || stripos($carrierName, 'تیپاکس') !== false) {
                    $trackingUrl = "https://tipaxco.com/tracking?id=" . urlencode($trackingCode);
                } elseif (stripos($carrierName, 'postex') !== false || stripos($carrierName, 'پستکس') !== false) {
                    $trackingUrl = "https://postex.ir/tracking?tracking_code=" . urlencode($trackingCode);
                } elseif (stripos($carrierName, 'chapar') !== false || stripos($carrierName, 'چاپار') !== false) {
                    $trackingUrl = "https://chaparnet.com/track/?tracking_number=" . urlencode($trackingCode);
                } elseif (stripos($carrierName, 'post') !== false || stripos($carrierName, 'پیشتاز') !== false) {
                    $trackingUrl = "https://tracking.post.ir/?id=" . urlencode($trackingCode);
                } else {
                    $trackingUrl = "https://postex.ir/tracking?tracking_code=" . urlencode($trackingCode);
                }
            }


            // Insert into order_status_logs
            $logStmt = $this->pdo->prepare("
                INSERT INTO order_status_logs 
                    (order_id, from_status, to_status, actor_type, actor_id, carrier_name, tracking_code, tracking_url, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $orderId, $oldStatus, $newStatus, $actorType, $actorId, $carrierName, $trackingCode, $trackingUrl, $notes
            ]);

            // Also keep legacy order_logs in sync
            try {
                $this->pdo->prepare("INSERT INTO order_logs (order_id, old_status, new_status) VALUES (?, ?, ?)")
                    ->execute([$orderId, $oldStatus, $newStatus]);
            } catch (Exception $e) {}

            $this->pdo->commit();

            // Send SMS notification if relevant
            $this->dispatchStatusSms($orderId, (int)$order['user_id'], $newStatus, $carrierName, $trackingCode);

            return [
                'success'    => true,
                'message'    => 'وضعیت سفارش با موفقیت به «' . self::getStatusMeta($newStatus)['title'] . '» تغییر یافت.',
                'new_status' => $newStatus,
                'meta'       => self::getStatusMeta($newStatus)
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Order transition error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در تغییر وضعیت: ' . $e->getMessage()];
        }
    }

    /**
     * Dispatch SMS on key fulfillment milestones.
     */
    private function dispatchStatusSms(int $orderId, int $userId, string $status, ?string $carrier, ?string $trackingCode): void
    {
        try {
            require_once __DIR__ . '/SmsService.php';
            $sms = new SmsService();

            $uStmt = $this->pdo->prepare("SELECT phone, name FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user || empty($user['phone'])) return;

            $phone = $user['phone'];
            $name = $user['name'] ?: 'مشتری گرامی';

            if ($status === self::STATUS_HANDED_OVER || $status === 'shipped') {
                $cName = $carrier ?: 'پست/تیپاکس';
                $code = $trackingCode ?: 'ثبت در سامانه';
                $text = "آسنا: {$name} عزیز، سفارش شما (#{$orderId}) تحویل {$cName} گردید. کد رهگیری مرسوله: {$code}";
                $sms->send($phone, $text);
            } elseif ($status === self::STATUS_OUT_DELIVERY) {
                $text = "آسنا: {$name} عزیز، مرسوله سفارش (#{$orderId}) به پیک تحویل داده شد و در مسیر تحویل به شماست.";
                $sms->send($phone, $text);
            } elseif ($status === self::STATUS_DELIVERED) {
                $text = "آسنا: سفارش (#{$orderId}) با موفقیت تحویل داده شد. از خرید شما سپاسگزاریم! مشتاق خواندن نظر شما در سایت هستیم.";
                $sms->send($phone, $text);
            }
        } catch (Exception $e) {
            error_log("Fulfillment SMS dispatch error: " . $e->getMessage());
        }
    }

    /**
     * Retrieve full timeline events for an order.
     */
    public function getOrderTimeline(int $orderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, u.name as actor_name
            FROM order_status_logs l
            LEFT JOIN users u ON l.actor_id = u.id
            WHERE l.order_id = ?
            ORDER BY l.created_at ASC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
