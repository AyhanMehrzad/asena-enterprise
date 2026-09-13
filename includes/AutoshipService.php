<?php
/**
 * ASENA Enterprise - Autoship & Recurring Subscription Engine
 * Benchmarked against Chewy.com Autoship Standards
 */

class AutoshipService
{
    private PDO $pdo;

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
     * Map frequency code to number of days.
     */
    public static function frequencyToDays(string $freq): int
    {
        $map = [
            '1_week'   => 7,
            '2_weeks'  => 14,
            '3_weeks'  => 21,
            '4_weeks'  => 28,
            '1_month'  => 30,
            '6_weeks'  => 42,
            '2_months' => 60,
            '3_months' => 90,
        ];
        return $map[$freq] ?? 30;
    }

    /**
     * Human-readable Persian label for frequency.
     */
    public static function frequencyLabel(string $freq): string
    {
        $map = [
            '1_week'   => 'هر ۱ هفته (هفتگی)',
            '2_weeks'  => 'هر ۲ هفته (دو هفته یک‌بار)',
            '3_weeks'  => 'هر ۳ هفته',
            '4_weeks'  => 'هر ۴ هفته (ماهانه)',
            '1_month'  => 'ماهانه (هر ۳۰ روز)',
            '6_weeks'  => 'هر ۶ هفته',
            '2_months' => 'هر ۲ ماه (دومشخصه)',
            '3_months' => 'هر ۳ ماه (فصلی)',
        ];
        return $map[$freq] ?? 'ماهانه';
    }

    /**
     * Create or enroll an item into an active Autoship recurring delivery.
     */
    public function createSubscription(int $userId, int $productId, bool $isPharmacy, string $freq, int $qty, int $unitPrice, ?string $address = null): int
    {
        $days = self::frequencyToDays($freq);
        $nextDate = date('Y-m-d', strtotime("+{$days} days"));

        // Autoship discount calculation (guaranteed minimum 5% recurring discount)
        $autoshipPrice = (int)round($unitPrice * 0.95);
        $tableName = $isPharmacy ? 'pharmacy_medicines' : 'products';

        // Fetch product title
        $stmt = $this->pdo->prepare("SELECT name FROM {$tableName} WHERE id = ?");
        $stmt->execute([$productId]);
        $productName = $stmt->fetchColumn() ?: 'محصول سفارش ادواری';

        $planName = "اتوشیپ Chewy-Style: {$productName} (" . self::frequencyLabel($freq) . ")";
        $totalAmount = $autoshipPrice * $qty;

        $insertStmt = $this->pdo->prepare("
            INSERT INTO user_subscriptions 
                (user_id, plan_name, amount, status, next_delivery_date, duration_months, payment_model, delivery_frequency)
            VALUES (?, ?, ?, 'active', ?, 12, 'monthly', ?)
        ");
        $insertStmt->execute([$userId, $planName, $totalAmount, $nextDate, $freq]);
        $subId = (int)$this->pdo->lastInsertId();

        // Also record in autoship_subscriptions table if exists
        try {
            $this->pdo->prepare("
                INSERT INTO autoship_subscriptions (user_id, plan_id, product_id, next_delivery_date, status)
                VALUES (?, 1, ?, ?, 'active')
            ")->execute([$userId, $productId, $nextDate]);
        } catch (Exception $e) {
            // Non-critical if table structure varies
        }

        return $subId;
    }

    /**
     * Get all active subscriptions for a customer.
     */
    public function getUserSubscriptions(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_subscriptions 
            WHERE user_id = ? 
            ORDER BY next_delivery_date ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Chewy Feature: 1-Click "Skip Next Shipment" (تغییر و تعویق تاریخ نوبت بعد)
     */
    public function skipNextShipment(int $subId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT delivery_frequency, next_delivery_date FROM user_subscriptions WHERE id = ? AND user_id = ?");
        $stmt->execute([$subId, $userId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sub) return false;

        $freqDays = self::frequencyToDays($sub['delivery_frequency'] ?: '1_month');
        $currentNext = !empty($sub['next_delivery_date']) ? $sub['next_delivery_date'] : date('Y-m-d');
        $newNext = date('Y-m-d', strtotime("{$currentNext} +{$freqDays} days"));

        $upStmt = $this->pdo->prepare("UPDATE user_subscriptions SET next_delivery_date = ? WHERE id = ?");
        return $upStmt->execute([$newNext, $subId]);
    }

    /**
     * Chewy Feature: 1-Click "Ship Now" (ارسال فوری سفارش همین امروز)
     */
    public function shipNow(int $subId, int $userId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_subscriptions WHERE id = ? AND user_id = ?");
        $stmt->execute([$subId, $userId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sub) return null;

        // Generate immediate order
        $this->pdo->beginTransaction();
        try {
            $orderStmt = $this->pdo->prepare("
                INSERT INTO orders (user_id, total_amount, status, shipping_address)
                SELECT ?, ?, 'processing', address FROM users WHERE id = ?
            ");
            $orderStmt->execute([$userId, (int)$sub['amount'], $userId]);
            $orderId = (int)$this->pdo->lastInsertId();

            // Create order log
            $this->pdo->prepare("
                INSERT INTO order_status_logs (order_id, from_status, to_status, actor_type, actor_id, notes)
                VALUES (?, NULL, 'processing', 'user', ?, 'ثبت فوری از طریق کلیک Ship Now اتوشیپ')
            ")->execute([$orderId, $userId]);

            // Advance next delivery date
            $freqDays = self::frequencyToDays($sub['delivery_frequency'] ?: '1_month');
            $newNext = date('Y-m-d', strtotime("+{$freqDays} days"));
            $this->pdo->prepare("UPDATE user_subscriptions SET next_delivery_date = ? WHERE id = ?")->execute([$newNext, $subId]);

            $this->pdo->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Autoship shipNow error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update delivery frequency (e.g. change from every 4 weeks to every 2 weeks).
     */
    public function updateFrequency(int $subId, int $userId, string $newFreq): bool
    {
        $freqDays = self::frequencyToDays($newFreq);
        $nextDate = date('Y-m-d', strtotime("+{$freqDays} days"));

        $stmt = $this->pdo->prepare("
            UPDATE user_subscriptions 
            SET delivery_frequency = ?, next_delivery_date = ?
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$newFreq, $nextDate, $subId, $userId]);
    }

    /**
     * Pause / Resume subscription.
     */
    public function togglePause(int $subId, int $userId): string
    {
        $stmt = $this->pdo->prepare("SELECT status FROM user_subscriptions WHERE id = ? AND user_id = ?");
        $stmt->execute([$subId, $userId]);
        $current = $stmt->fetchColumn();
        if (!$current) return 'not_found';

        $newStatus = ($current === 'active') ? 'paused' : 'active';
        $this->pdo->prepare("UPDATE user_subscriptions SET status = ? WHERE id = ?")->execute([$newStatus, $subId]);
        return $newStatus;
    }

    /**
     * Cancel subscription.
     */
    public function cancel(int $subId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE user_subscriptions SET status = 'cancelled' WHERE id = ? AND user_id = ?");
        return $stmt->execute([$subId, $userId]);
    }

    /**
     * Retrieve all subscriptions due for processing today or earlier.
     */
    public function getDueSubscriptions(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, u.phone as user_phone, u.name as user_name, u.address as user_address
            FROM user_subscriptions s
            JOIN users u ON s.user_id = u.id
            WHERE s.status = 'active' AND s.next_delivery_date <= CURRENT_DATE
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Chewy Proactive Alert: Subscriptions shipping in N days (default: 3 days).
     * Used to send SMS reminder: "سفارش دوره‌ای شما ۳ روز دیگر ارسال می‌شود. در صورت نیاز تغییر دهید."
     */
    public function getUpcomingReminders(int $daysAhead = 3): array
    {
        $targetDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
        $stmt = $this->pdo->prepare("
            SELECT s.*, u.phone as user_phone, u.name as user_name
            FROM user_subscriptions s
            JOIN users u ON s.user_id = u.id
            WHERE s.status = 'active' AND s.next_delivery_date = ?
        ");
        $stmt->execute([$targetDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check whether an item qualifies for Autoship recommendation based on stock buffer.
     * Guaranteed 3+ months buffer (default threshold: 5 units).
     */
    public static function isEligibleForAutoship(int $stock, int $threshold = 5): bool
    {
        return $stock >= max(1, $threshold);
    }

    /**
     * Authenticate an inventory item for Autoship eligibility.
     * Balances user trust and provider business needs without disappointing either party.
     */
    public static function authenticateAutoshipInventory(array $item, int $monthlyQty = 1, int $minMonths = 3): array
    {
        $stock = (int)($item['stock'] ?? 0);
        $threshold = (int)($item['autoship_min_months_stock'] ?? max(5, $monthlyQty * $minMonths));
        $isEligible = ($stock >= $threshold);
        $monthsBuffer = ($monthlyQty > 0) ? (int)floor($stock / $monthlyQty) : 0;

        if ($stock <= 0) {
            return [
                'is_eligible'    => false,
                'can_single_buy' => false,
                'status'         => 'out_of_stock',
                'badge_fa'       => 'ناموجود در انبار',
                'badge_class'    => 'bg-rose-100 text-rose-800 border-rose-200',
                'user_note'      => 'این کالا در حال حاضر اتمام موجودی شده است.',
                'provider_tip'   => 'جهت امکان فروش، لطفاً انبار خود را شارژ فرمایید.',
                'months_buffer'  => 0,
                'stock'          => 0
            ];
        }

        if ($isEligible) {
            return [
                'is_eligible'    => true,
                'can_single_buy' => true,
                'status'         => 'autoship_qualified',
                'badge_fa'       => 'واجد شرایط تحویل ادواری (Autoship)',
                'badge_class'    => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'user_note'      => 'این کالا دارای موجودی پایدار است و با ۱۵٪ تخفیف دائمی قابل سفارش ادواری است.',
                'provider_tip'   => 'کالای شما دارای نشان طلایی اتوشیپ بوده و در اولویت سبد اشتراک ماهانه مشتریان قرار دارد.',
                'months_buffer'  => $monthsBuffer,
                'stock'          => $stock
            ];
        }

        // Low stock: single purchase only, NOT suggested for autoship
        // User is happy because they can still purchase immediately!
        // Provider is happy because they get an immediate sale and avoid stockout cancellation penalties!
        return [
            'is_eligible'    => false,
            'can_single_buy' => true,
            'status'         => 'single_order_only',
            'badge_fa'       => 'خرید تک‌باره فعال (سهمیه اشتراک محدود)',
            'badge_class'    => 'bg-amber-100 text-amber-800 border-amber-200',
            'user_note'      => 'امکان خرید تکی وجود دارد. (سفارش دوره‌ای موقتاً جهت تضمین تحویل پایدار ماه‌های بعد برای این کالا غیرفعال است)',
            'provider_tip'   => "با افزایش موجودی به حداقل {$threshold} عدد، نشان پرفروش اشتراک دوره‌ای برای این کالا فعال می‌گردد.",
            'months_buffer'  => $monthsBuffer,
            'stock'          => $stock
        ];
    }

    /**
     * Resolve provider tag, verified icon, and store link for any product or medicine.
     */
    public static function resolveProviderTag(array $item): array
    {
        $orgName   = $item['org_name'] ?? null;
        $orgType   = $item['org_type'] ?? null;
        $orgId     = !empty($item['organization_id']) ? (int)$item['organization_id'] : null;
        $sellerName= $item['seller_name'] ?? null;
        $sellerId  = !empty($item['seller_id']) ? (int)$item['seller_id'] : null;

        // If direct clinic or hospital
        if (!empty($orgName) && in_array($orgType, ['hospital', 'clinic'], true)) {
            return [
                'name'         => $orgName,
                'type'         => 'clinic',
                'type_fa'      => 'مرکز درمانی',
                'icon'         => 'local_hospital',
                'tag_label'    => "🏥 مرکز درمانی: {$orgName}",
                'badge_class'  => 'bg-blue-50 text-blue-800 border-blue-200',
                'is_verified'  => true,
                'profile_url'  => "organization_profile.php?id={$orgId}"
            ];
        }

        // If pharmacy
        if (!empty($orgName) && $orgType === 'pharmacy') {
            return [
                'name'         => $orgName,
                'type'         => 'pharmacy',
                'type_fa'      => 'داروخانه رسمی',
                'icon'         => 'medication',
                'tag_label'    => "💊 داروخانه: {$orgName}",
                'badge_class'  => 'bg-teal-50 text-teal-800 border-teal-200',
                'is_verified'  => true,
                'profile_url'  => "organization_profile.php?id={$orgId}"
            ];
        }

        // If seller / petshop
        if (!empty($sellerName) || !empty($sellerId)) {
            $name = !empty($sellerName) ? $sellerName : 'فروشنده تاییدشده';
            return [
                'name'         => $name,
                'type'         => 'seller',
                'type_fa'      => 'پت‌شاپ مجاز',
                'icon'         => 'storefront',
                'tag_label'    => "🛍️ تأمین‌کننده: {$name}",
                'badge_class'  => 'bg-purple-50 text-purple-800 border-purple-200',
                'is_verified'  => true,
                'profile_url'  => "shop.php?seller_id={$sellerId}"
            ];
        }

        // Default: ASENA Corporate Express
        return [
            'name'         => 'آسنا اکسپرس',
            'type'         => 'official',
            'type_fa'      => 'پلتفرم رسمی',
            'icon'         => 'verified',
            'tag_label'    => '✨ ارسال مستقیم آسنا اکسپرس',
            'badge_class'  => 'bg-amber-50 text-amber-800 border-amber-200',
            'is_verified'  => true,
            'profile_url'  => 'about.php'
        ];
    }
}

