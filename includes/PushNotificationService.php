<?php
/**
 * ASENA Enterprise - Unified Push & Event Notification Service
 * Integrates In-App Live Activity, Web Push, PWA Notifications, and Melipayamak SMS Fallback
 * Version: 2.0.0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/SmsService.php';

class PushNotificationService {
    private ?PDO $pdo;
    private SmsService $sms;

    public function __construct(?PDO $pdo = null, ?SmsService $sms = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            $this->pdo = $pdo ?? null;
        }
        $this->sms = $sms ?? new SmsService($this->pdo);
    }

    /**
     * Create a specific or broadcast notification
     */
    public function createNotification(?int $userId, string $type, string $title, string $message, ?string $linkUrl = null, ?string $icon = 'notifications', string $targetAudience = 'all', ?string $imageUrl = null): int {
        if (!$this->pdo) return 0;

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_notifications (user_id, type, title, message, link_url, icon, image_url, target_audience)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $type, $title, $message, $linkUrl, $icon, $imageUrl, $targetAudience]);
            return (int)$this->pdo->lastInsertId();
        } catch (Throwable $e) {
            error_log("[NotificationService] createNotification error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Broadcast a marketing, purchase offer, or system notification from Admin
     */
    public function broadcastCampaign(string $title, string $message, string $type = 'purchase_offer', string $targetAudience = 'all', ?string $linkUrl = null, ?string $imageUrl = null, bool $sendSms = false, ?int $targetUserId = null): array {
        if (!$this->pdo) return ['success' => false, 'sent_count' => 0, 'error' => 'Database connection unavailable'];

        $sentCount = 0;
        $smsSentCount = 0;

        try {
            if ($targetAudience === 'user' && $targetUserId) {
                // Send to single user
                $notifId = $this->createNotification($targetUserId, $type, $title, $message, $linkUrl, $this->resolveIcon($type), 'user', $imageUrl);
                if ($notifId) $sentCount = 1;

                if ($sendSms) {
                    $uStmt = $this->pdo->prepare("SELECT phone FROM users WHERE id = ?");
                    $uStmt->execute([$targetUserId]);
                    $phone = $uStmt->fetchColumn();
                    if ($phone) {
                        $smsBody = "آسنا: {$title}\n{$message}\n{$linkUrl}";
                        if ($this->sms->send($phone, $smsBody)) $smsSentCount++;
                    }
                }
            } elseif ($targetAudience === 'buyers') {
                // Target users with previous orders
                $uStmt = $this->pdo->query("SELECT DISTINCT u.id, u.phone FROM users u JOIN orders o ON u.id = o.user_id WHERE u.role = 'user'");
                $buyers = $uStmt->fetchAll(PDO::FETCH_ASSOC);

                $insStmt = $this->pdo->prepare("
                    INSERT INTO user_notifications (user_id, type, title, message, link_url, icon, image_url, target_audience)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'buyers')
                ");

                foreach ($buyers as $b) {
                    $insStmt->execute([$b['id'], $type, $title, $message, $linkUrl, $this->resolveIcon($type), $imageUrl]);
                    $sentCount++;
                    if ($sendSms && !empty($b['phone'])) {
                        $smsBody = "آسنا: {$title}\n{$message}\n{$linkUrl}";
                        if ($this->sms->send($b['phone'], $smsBody)) $smsSentCount++;
                    }
                }
            } else {
                // 'all' or 'pwa_only' -> Store as global broadcast row
                $notifId = $this->createNotification(null, $type, $title, $message, $linkUrl, $this->resolveIcon($type), $targetAudience, $imageUrl);
                if ($notifId) $sentCount = 1;

                // If SMS requested for broadcast to normal users
                if ($sendSms) {
                    $uStmt = $this->pdo->query("SELECT phone FROM users WHERE role = 'user' AND phone IS NOT NULL AND phone != '' LIMIT 100");
                    while ($phone = $uStmt->fetchColumn()) {
                        $smsBody = "آسنا: {$title}\n{$message}\n{$linkUrl}";
                        if ($this->sms->send($phone, $smsBody)) $smsSentCount++;
                    }
                }
            }

            return [
                'success' => true,
                'sent_count' => $sentCount,
                'sms_sent_count' => $smsSentCount,
                'audience' => $targetAudience
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'sent_count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch user notifications (User specific + Target Broadcasts)
     */
    public function getUserNotifications(?int $userId, int $limit = 25, bool $isPwa = false, int $sinceId = 0): array {
        if (!$this->pdo) return [];

        try {
            $audienceFilter = $isPwa ? "('all', 'pwa_only')" : "('all')";
            $sinceFilter = $sinceId > 0 ? "AND id > " . (int)$sinceId : "";

            if ($userId) {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM user_notifications 
                    WHERE (user_id = ? OR (user_id IS NULL AND target_audience IN {$audienceFilter}))
                    {$sinceFilter}
                    ORDER BY created_at DESC 
                    LIMIT ?
                ");
                $stmt->bindValue(1, $userId, PDO::PARAM_INT);
                $stmt->bindValue(2, $limit, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                // Guest / Anonymous visitor
                $stmt = $this->pdo->prepare("
                    SELECT * FROM user_notifications 
                    WHERE user_id IS NULL AND target_audience IN {$audienceFilter}
                    {$sinceFilter}
                    ORDER BY created_at DESC 
                    LIMIT ?
                ");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['category'] = self::resolveCategory($r['type'] ?? '');
                $r['time_ago'] = $this->timeAgoString($r['created_at'] ?? 'now');
                if (empty($r['icon'])) {
                    $r['icon'] = $this->resolveIcon($r['type'] ?? '');
                }
            }
            unset($r);

            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(?int $userId, bool $isPwa = false): int {
        if (!$this->pdo) return 0;
        try {
            $audienceFilter = $isPwa ? "('all', 'pwa_only')" : "('all')";
            if ($userId) {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) FROM user_notifications 
                    WHERE is_read = 0 AND (user_id = ? OR (user_id IS NULL AND target_audience IN {$audienceFilter}))
                ");
                $stmt->execute([$userId]);
                return (int)$stmt->fetchColumn();
            } else {
                $stmt = $this->pdo->query("
                    SELECT COUNT(*) FROM user_notifications 
                    WHERE is_read = 0 AND user_id IS NULL AND target_audience IN {$audienceFilter}
                ");
                return (int)$stmt->fetchColumn();
            }
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(int $notificationId, ?int $userId = null): bool {
        if (!$this->pdo) return false;
        try {
            $stmt = $this->pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ?");
            return $stmt->execute([$notificationId]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(?int $userId = null): bool {
        if (!$this->pdo) return false;
        try {
            if ($userId) {
                $stmt = $this->pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ? OR user_id IS NULL");
                return $stmt->execute([$userId]);
            } else {
                return (bool)$this->pdo->exec("UPDATE user_notifications SET is_read = 1 WHERE user_id IS NULL");
            }
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get real-time live interaction & social proof feed (Purchases, Appointments)
     */
    public function getLiveSocialProofFeed(int $limit = 8): array {
        if (!$this->pdo) return [];

        $feed = [];

        try {
            // 1. Fetch real recent successful purchases from database
            $orderStmt = $this->pdo->query("
                SELECT o.id, o.created_at, u.name as user_name,
                       (SELECT p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id LIMIT 1) as item_title,
                       (SELECT p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id LIMIT 1) as item_image
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.status != 'cancelled'
                ORDER BY o.created_at DESC
                LIMIT 5
            ");
            $realOrders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($realOrders as $ro) {
                if (!empty($ro['item_title'])) {
                    $feed[] = [
                        'user_name' => $this->maskUserName($ro['user_name'] ?? 'کاربر آسنا'),
                        'city' => 'تهران',
                        'event_type' => 'purchase',
                        'item_title' => $ro['item_title'],
                        'item_link' => 'shop.php',
                        'item_image' => $ro['item_image'] ?: 'assets/images/cat-hero.jpg',
                        'time_ago' => $this->timeAgoString($ro['created_at']),
                        'icon' => 'shopping_bag'
                    ];
                }
            }

            // 2. Fetch curated social proof events to ensure rich, vibrant stream
            $curatedStmt = $this->pdo->query("
                SELECT user_name, city, event_type, item_title, item_link, item_image, minutes_ago
                FROM live_social_proof_events
                WHERE is_active = 1
                ORDER BY id ASC
                LIMIT 10
            ");
            $curated = $curatedStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($curated as $c) {
                $feed[] = [
                    'user_name' => $c['user_name'],
                    'city' => $c['city'],
                    'event_type' => $c['event_type'],
                    'item_title' => $c['item_title'],
                    'item_link' => $c['item_link'] ?: 'shop.php',
                    'item_image' => $c['item_image'] ?: 'assets/images/cat-hero.jpg',
                    'time_ago' => $c['minutes_ago'] . ' دقیقه پیش',
                    'icon' => ($c['event_type'] === 'booking') ? 'calendar_month' : (($c['event_type'] === 'prescription') ? 'medication' : 'shopping_bag')
                ];
            }

            // Slice to desired limit
            return array_slice($feed, 0, $limit);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Register or update PWA Push Subscription
     */
    public function registerPwaSubscription(?int $userId, string $endpoint, ?string $p256dh, ?string $auth, string $deviceType = 'unknown'): bool {
        if (!$this->pdo || empty($endpoint)) return false;

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO pwa_subscriptions (user_id, endpoint, p256dh, auth, device_type, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth = VALUES(auth), is_active = 1, updated_at = NOW()
            ");
            return $stmt->execute([$userId, $endpoint, $p256dh, $auth, $deviceType]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Issue PWA install welcome reward notification
     */
    public function issuePwaWelcomeNotification(int $userId): bool {
        return (bool)$this->createNotification(
            $userId,
            'pwa_welcome',
            '🎉 نصب موفقیت‌آمیز اپلیکیشن آسنا!',
            'به خانواده آسنا خوش آمدید! کد تخفیف ۱۰٪ خرید اول شما: PWA-WELCOME (معتبر برای کلیه سفارشات پت‌شاپ و داروخانه).',
            'shop.php?coupon=PWA-WELCOME',
            'card_giftcard',
            'user'
        );
    }

    /**
     * Helper: Mask user name for privacy (Social proof standard)
     */
    private function maskUserName(?string $name): string {
        if (empty($name)) return 'کاربر آسنا';
        $parts = explode(' ', trim($name));
        if (count($parts) >= 2) {
            return $parts[0] . ' ' . mb_substr($parts[1], 0, 1, 'UTF-8') . '.';
        }
        return $name;
    }

    /**
     * Helper: Persian Time Ago string
     */
    private function timeAgoString(string $datetime): string {
        $timestamp = strtotime($datetime);
        $diff = max(1, time() - $timestamp);
        if ($diff < 60) return 'هم‌اکنون';
        if ($diff < 3600) return round($diff / 60) . ' دقیقه پیش';
        if ($diff < 86400) return round($diff / 3600) . ' ساعت پیش';
        if ($diff < 604800) return round($diff / 86400) . ' روز پیش';
        return date('Y/m/d', $timestamp);
    }

    /**
     * Helper: Resolve Material Symbol Icon from Notification Type
     */
    public function resolveIcon(string $type): string {
        return match ($type) {
            'purchase_offer' => 'local_fire_department',
            'order_status'   => 'local_shipping',
            'pwa_welcome'    => 'celebration',
            'pet_health'     => 'health_and_safety',
            'appointment'    => 'calendar_month',
            'prescription'   => 'medication',
            'chat_message'   => 'forum',
            'loyalty_reward' => 'stars',
            'financial'      => 'payments',
            default          => 'notifications'
        };
    }

    /**
     * Helper: Resolve high-level category for drawer tabs
     */
    public static function resolveCategory(string $type): string {
        return match ($type) {
            'order_status'                    => 'orders',
            'appointment', 'pet_health'       => 'health',
            'prescription'                    => 'prescriptions',
            'chat_message'                    => 'messages',
            'loyalty_reward', 'purchase_offer', 'pwa_welcome' => 'rewards',
            default                           => 'all'
        };
    }

    // -------------------------------------------------------------
    // Real-World Domain Event Dispatchers
    // -------------------------------------------------------------

    public function notifyOrderPlaced(int $userId, int $orderId, int $totalAmount): int {
        return $this->createNotification(
            $userId,
            'order_status',
            "سفارش #{$orderId} با موفقیت ثبت شد",
            "سفارش شما به ارزش " . number_format($totalAmount) . " تومان با موفقیت پرداخت شد و در صف آماده‌سازی قرار گرفت.",
            "profile.php#orders",
            'shopping_bag',
            'user'
        );
    }

    public function notifyOrderStatusChanged(int $userId, int $orderId, string $status, ?string $carrier = null, ?string $trackingCode = null): int {
        $meta = [
            'confirmed' => [
                'title' => "سفارش #{$orderId} تأیید انبارداری شد",
                'msg'   => "اقلام سفارش شما توسط انبار تأیید گردیده و وارد فرآیند جمع‌آوری و بسته‌بندی شد.",
                'icon'  => 'inventory_2'
            ],
            'picking' => [
                'title' => "جمع‌آوری اقلام سفارش #{$orderId}",
                'msg'   => "کالاهای سفارش شما در حال آماده‌سازی و کنترل فیزیکی نهایی است.",
                'icon'  => 'inventory'
            ],
            'packed' => [
                'title' => "سفارش #{$orderId} بسته‌بندی شد",
                'msg'   => "بسته‌بندی مرسوله تکمیل شده و بارکد ارسال بر روی بسته الصاق گردید.",
                'icon'  => 'package_2'
            ],
            'handed_over' => [
                'title' => "سفارش #{$orderId} تحویل ناوگان ارسال شد",
                'msg'   => "مرسوله شما تحویل " . ($carrier ?: 'ناوگان پستی') . " گردید." . (!empty($trackingCode) ? " کد رهگیری: {$trackingCode}" : ""),
                'icon'  => 'local_shipping'
            ],
            'shipped' => [
                'title' => "سفارش #{$orderId} ارسال گردید",
                'msg'   => "مرسوله شما با موفقیت ارسال شد." . (!empty($trackingCode) ? " کد رهگیری " . ($carrier ?: 'پست') . ": {$trackingCode}" : ""),
                'icon'  => 'local_shipping'
            ],
            'out_for_delivery' => [
                'title' => "مرسوله در مسیر تحویل",
                'msg'   => "سفارش #{$orderId} توسط مأمور توزیع در مسیر تحویل به نشانی شما می‌باشد.",
                'icon'  => 'near_me'
            ],
            'delivered' => [
                'title' => "سفارش #{$orderId} تحویل داده شد",
                'msg'   => "مرسوله با موفقیت به شما تحویل شد. با ثبت نظر در مورد کالاها، ۵ امتیاز وفاداری هدیه بگیرید!",
                'icon'  => 'task_alt'
            ],
            'cancelled' => [
                'title' => "سفارش #{$orderId} لغو گردید",
                'msg'   => "سفارش شما لغو شد. در صورت کسر وجه از حساب، وجه پرداختی طی ۴۸ ساعت کاری عودت خواهد شد.",
                'icon'  => 'cancel'
            ]
        ];

        $info = $meta[$status] ?? [
            'title' => "به‌روزرسانی وضعیت سفارش #{$orderId}",
            'msg'   => "وضعیت سفارش شما به روزرسانی گردید.",
            'icon'  => 'local_shipping'
        ];

        return $this->createNotification(
            $userId,
            'order_status',
            $info['title'],
            $info['msg'],
            "profile.php#orders",
            $info['icon'],
            'user'
        );
    }

    public function notifyAppointmentBooked(int $userId, int $appointmentId, string $doctorName, string $date, string $time): int {
        return $this->createNotification(
            $userId,
            'appointment',
            "رزرو موفقیت‌آمیز نوبت ویزیت",
            "نوبت شما نزد {$doctorName} برای تاریخ {$date} ساعت {$time} با موفقیت ثبت شد. لطفاً در ساعت مقرر حضور به هم رسانید.",
            "profile.php#appointments",
            'calendar_month',
            'user'
        );
    }

    public function notifyAppointmentRescheduled(int $userId, int $appointmentId, string $doctorName, string $newDate, string $newTime, ?string $reason = null): int {
        $reasonText = !empty($reason) ? " (علت: {$reason})" : "";
        return $this->createNotification(
            $userId,
            'appointment',
            "تغییر زمان نوبت ویزیت با پزشک",
            "زمان نوبت شما نزد {$doctorName} به تاریخ {$newDate} ساعت {$newTime} تغییر یافت.{$reasonText}",
            "profile.php#appointments",
            'edit_calendar',
            'user'
        );
    }

    public function notifyAppointmentCompleted(int $userId, int $appointmentId, string $doctorName): int {
        return $this->createNotification(
            $userId,
            'pet_health',
            "ثبت پرونده ویزیت و معاینه بالینی",
            "ویزیت شما نزد {$doctorName} به پایان رسید. شرح معاینات و پرونده بالینی حیوان در سامانه ثبت گردید.",
            "profile.php#appointments",
            'medical_services',
            'user'
        );
    }

    public function notifyPrescriptionStatus(int $userId, int $rxId, string $status, ?string $pharmacyName = null, ?string $notes = null): int {
        $pharmacy = $pharmacyName ?: 'داروخانه مرجع آسنا';
        $meta = [
            'ready_for_pickup' => [
                'title' => "داروهای نسخه #{$rxId} آماده تحویل است",
                'msg'   => "اقلام دارویی نسخه شما توسط {$pharmacy} آماده‌سازی شده و آماده تحویل حضوری یا ارسال با پیک می‌باشد." . (!empty($notes) ? " (یادداشت داروساز: {$notes})" : "")
            ],
            'preparing' => [
                'title' => "نسخه در حال آماده‌سازی در داروخانه",
                'msg'   => "مسئول فنی {$pharmacy} در حال بررسی و آماده‌سازی اقلام نسخه #{$rxId} است."
            ],
            'dispensed' => [
                'title' => "نسخه #{$rxId} با موفقیت تحویل گردید",
                'msg'   => "داروهای نسخه به بیمار تحویل داده شد. آرزوی بهبودی کامل برای پت دلبندتان داریم."
            ]
        ];

        $info = $meta[$status] ?? [
            'title' => "به‌روزرسانی نسخه الکترونیک #{$rxId}",
            'msg'   => "وضعیت نسخه دارویی شما تغییر یافت."
        ];

        return $this->createNotification(
            $userId,
            'prescription',
            $info['title'],
            $info['msg'],
            "profile.php#prescriptions",
            'medication',
            'user'
        );
    }

    public function notifyChatMessageReceived(int $userId, string $senderName, string $senderRole, string $snippet, ?string $chatUrl = null): int {
        $cleanSnippet = mb_substr(strip_tags($snippet), 0, 95, 'UTF-8');
        if (mb_strlen($snippet, 'UTF-8') > 95) $cleanSnippet .= '...';

        return $this->createNotification(
            $userId,
            'chat_message',
            "پیام جدید از طرف {$senderName}",
            $cleanSnippet,
            $chatUrl ?: "chat.php",
            'forum',
            'user'
        );
    }

    public function notifyLoyaltyPointsEarned(int $userId, int $points, string $reason): int {
        return $this->createNotification(
            $userId,
            'loyalty_reward',
            "🎉 دریافت +{$points} امتیاز باشگاه مشتریان!",
            "به دلیل «{$reason}»، تعداد {$points} امتیاز وفاداری به کیف پول شما اضافه شد.",
            "rewards.php",
            'stars',
            'user'
        );
    }

    public function notifyPayoutIssued(int $userId, int $amount, string $sheba, string $batchRef): int {
        return $this->createNotification(
            $userId,
            'financial',
            "حواله تسویه پایا صادر گردید",
            "مبلغ " . number_format($amount) . " تومان جهت تسویه به حساب شبای {$sheba} واریز شد (شماره پیگیری پایا: {$batchRef}).",
            "profile.php?view=seller",
            'payments',
            'user'
        );
    }

    // -------------------------------------------------------------
    // Backward Compatibility Alerts
    // -------------------------------------------------------------

    public function sendVaccinationAlert(string $phone, string $petName, string $vaccineName, string $dueDate): bool {
        $message = "آسنا: موعد تزریق واکسن {$vaccineName} برای {$petName} نزدیک است ({$dueDate}). جهت رزرو نوبت کلینیک به پنل کاربری مراجعه نمایید.";
        return (bool)$this->sms->send($phone, $message);
    }

    public function sendAutoshipNotice(string $phone, int $subId, int $daysUntilShipment = 3): bool {
        $message = "آسنا: سفارش ادواری (#{$subId}) شما تا {$daysUntilShipment} روز دیگر ارسال خواهد شد. در صورت نیاز به تغییر تاریخ یا اقلام، به پنل مراجعه فرمایید.";
        return (bool)$this->sms->send($phone, $message);
    }

    public function sendOrderDispatchAlert(string $phone, int $orderId, string $carrier, string $trackingCode): bool {
        $message = "آسنا: مرسوله سفارش (#{$orderId}) به ناوگان {$carrier} تحویل شد. کد رهگیری: {$trackingCode}";
        return (bool)$this->sms->send($phone, $message);
    }
}
