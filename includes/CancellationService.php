<?php
/**
 * ASENA Enterprise - CancellationService
 *
 * Dedicated Business Rules & Legal Refund Engine:
 * 1. Physical Orders (Store & Pharmacy/Drugs):
 *    - Inquires Postex logistics tracking before cancellation.
 *    - If parcel has left the provider's city / handed over to courier, cancellation is strictly forbidden.
 * 2. Appointments (Veterinary Doctors & Groomers):
 *    - Strictly enforces the 24-hour window before scheduled time.
 *    - Cancellations under 24 hours are locked and non-refundable.
 * 3. Subscriptions (Autoship Cycles):
 *    - In the final fulfillment week of the current shipment, cancellation is blocked.
 *    - Upon receipt of the current parcel, user can cancel and prorated remaining weeks/months are refunded.
 * 4. Digital Consultations / Calculators (BMI, Diet, Interaction Analysis):
 *    - Strictly non-refundable per E-Commerce Law Art. 37.
 * 5. Golden Refund Rule:
 *    - 100% of refundable amount is credited directly to In-App Wallet (users.wallet_balance).
 *    - Zero external bank/Sheba transfers.
 *
 * Version: 1.0.0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/WalletService.php';
require_once __DIR__ . '/PostexShippingService.php';
require_once __DIR__ . '/SmsService.php';

class CancellationService
{
    private PDO $pdo;
    private WalletService $wallet;
    private PostexShippingService $postex;
    private SmsService $sms;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
        $this->wallet = new WalletService($this->pdo);
        $this->postex = new PostexShippingService($this->pdo);
        $this->sms = new SmsService();
    }

    /**
     * Check if an order is eligible for cancellation and return exact legal reason
     *
     * @param int $orderId
     * @param int $userId
     * @return array [
     *   'can_cancel' => bool,
     *   'reason' => string,
     *   'refund_amount' => int,
     *   'status' => string,
     *   'postex_checked' => bool,
     *   'left_origin_city' => bool
     * ]
     */
    public function checkOrderEligibility(int $orderId, int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return [
                'can_cancel' => false,
                'reason' => 'سفارش مورد نظر یافت نشد.',
                'refund_amount' => 0,
                'status' => 'not_found',
                'postex_checked' => false,
                'left_origin_city' => false
            ];
        }

        $status = $order['status'] ?? '';
        $paidAmount = (int)($order['total_amount'] ?? 0);

        // Already cancelled
        if ($status === 'cancelled') {
            return [
                'can_cancel' => false,
                'reason' => 'این سفارش پیش‌تر لغو گردیده است.',
                'refund_amount' => 0,
                'status' => $status,
                'postex_checked' => false,
                'left_origin_city' => false
            ];
        }

        // Already delivered
        if ($status === 'delivered') {
            return [
                'can_cancel' => false,
                'reason' => 'این سفارش تحویل داده شده است. طبق قوانین تجارت الکترونیک، امکان لغو سفارش وجود ندارد و در صورت مغایرت باید از فرآیند مرجوعی ۷ روزه استفاده فرمایید.',
                'refund_amount' => 0,
                'status' => $status,
                'postex_checked' => false,
                'left_origin_city' => true
            ];
        }

        // Check postal / courier status and Postex tracking
        $trackingCode = trim($order['post_tracking_code'] ?? ($order['tracking_code'] ?? ''));
        $leftOriginCity = false;
        $postexChecked = false;

        if (in_array($status, ['shipped', 'in_transit', 'out_for_delivery'])) {
            $leftOriginCity = true;
        }

        if (!empty($trackingCode)) {
            $postexChecked = true;
            try {
                $trackRes = $this->postex->trackCourierBarcode($trackingCode, 'IR_POST');
                if (!empty($trackRes['success']) && !empty($trackRes['events'])) {
                    foreach ($trackRes['events'] as $evt) {
                        $desc = $evt['description'] ?? '';
                        if (
                            str_contains($desc, 'خروج از مبدا') ||
                            str_contains($desc, 'ارسال به مقصد') ||
                            str_contains($desc, 'رهسپاری') ||
                            str_contains($desc, 'مرکز مبادله') ||
                            str_contains($desc, 'ورود به مقصد') ||
                            str_contains($desc, 'توزیع') ||
                            str_contains($desc, 'تحویل')
                        ) {
                            $leftOriginCity = true;
                            break;
                        }
                    }
                }
            } catch (Throwable $e) {
                // Postex network failure fallback to local order status
            }
        }

        if ($leftOriginCity) {
            return [
                'can_cancel' => false,
                'reason' => 'مرسوله به شرکت پست/پیک تحویل داده شده و از شهر مبدأ فروشنده خارج گردیده است. بر اساس قوانین لجستیک و انبارداری، سفارش‌های در حال انتقال قابل لغو نیستند.',
                'refund_amount' => 0,
                'status' => $status,
                'postex_checked' => $postexChecked,
                'left_origin_city' => true
            ];
        }

        // Status is pending_payment, processing, paid, confirmed, or picking
        if (in_array($status, ['pending_payment', 'processing', 'paid', 'confirmed', 'picking', 'packed'])) {
            return [
                'can_cancel' => true,
                'reason' => 'سفارش در مرحله پردازش انبار بوده و هنوز از شهر مبدأ ارسال نشده است.',
                'refund_amount' => $status === 'pending_payment' ? 0 : $paidAmount,
                'status' => $status,
                'postex_checked' => $postexChecked,
                'left_origin_city' => false
            ];
        }

        return [
            'can_cancel' => false,
            'reason' => 'وضعیت سفارش اجازه لغو را نمی‌دهد.',
            'refund_amount' => 0,
            'status' => $status,
            'postex_checked' => $postexChecked,
            'left_origin_city' => false
        ];
    }

    /**
     * Execute Order Cancellation with 100% In-App Wallet Refund
     */
    public function cancelOrder(int $orderId, int $userId): array
    {
        $check = $this->checkOrderEligibility($orderId, $userId);
        if (!$check['can_cancel']) {
            return [
                'success' => false,
                'message' => $check['reason']
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled' 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$orderId, $userId]);

            // Restore product inventory if table exists
            try {
                $itemsStmt = $this->pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $itemsStmt->execute([$orderId]);
                $restoreStmt = $this->pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
                    $restoreStmt->execute([$item['quantity'], $item['product_id']]);
                }
            } catch (Throwable $eProd) {}

            // Refund to In-App Wallet if paid
            $refundAmount = $check['refund_amount'];
            $walletResult = null;
            if ($refundAmount > 0) {
                $walletResult = $this->wallet->refund(
                    $userId,
                    $refundAmount,
                    'orders',
                    (string)$orderId,
                    "استرداد ۱۰۰٪ وجه سفارش لغو شده #PC-{$orderId} به کیف‌پول درون‌برنامه‌ای"
                );
            }

            // Deduct cancelled loyalty points
            try {
                $this->pdo->prepare("UPDATE users SET loyalty_points = GREATEST(0, loyalty_points - 50) WHERE id = ?")->execute([$userId]);
            } catch (Throwable $e) {}

            $this->pdo->commit();

            // Customer SMS Notification
            try {
                $uStmt = $this->pdo->prepare("SELECT phone FROM users WHERE id = ?");
                $uStmt->execute([$userId]);
                $phone = $uStmt->fetchColumn();
                if ($phone) {
                    $refundNotice = ($refundAmount > 0) 
                        ? " و مبلغ " . number_format($refundAmount) . " تومان به کیف‌پول شما در آسنا بازگشت داده شد."
                        : ".";
                    $this->sms->sendNotification($phone, "سفارش شماره #PC-{$orderId} شما در آسنا لغو گردید{$refundNotice}");
                }
            } catch (Throwable $e) {}

            return [
                'success' => true,
                'refund_amount' => $refundAmount,
                'message' => ($refundAmount > 0)
                    ? "سفارش شما با موفقیت لغو شد و مبلغ " . number_format($refundAmount) . " تومان بلافاصله به کیف‌پول درون‌برنامه‌ای شما واریز گردید."
                    : "سفارش پرداخت‌نشده با موفقیت لغو گردید."
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'خطای سیستمی در لغو سفارش: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if an appointment (Doctor or Groomer) is eligible for cancellation
     * Enforces the 24-hour advance cancellation rule.
     *
     * @param int $appointmentId
     * @param int $userId
     * @return array [
     *   'can_cancel' => bool,
     *   'reason' => string,
     *   'refund_amount' => int,
     *   'hours_remaining' => float,
     *   'status' => string
     * ]
     */
    public function checkAppointmentEligibility(int $appointmentId, int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE id = ? AND user_id = ?");
        $stmt->execute([$appointmentId, $userId]);
        $apt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$apt) {
            return [
                'can_cancel' => false,
                'reason' => 'نوبت مورد نظر یافت نشد.',
                'refund_amount' => 0,
                'hours_remaining' => 0,
                'status' => 'not_found'
            ];
        }

        $status = $apt['status'] ?? '';
        $fee = (int)($apt['fee'] ?? 0);

        if (in_array($status, ['cancelled', 'لغو شده'])) {
            return [
                'can_cancel' => false,
                'reason' => 'این نوبت پیش‌تر لغو گردیده است.',
                'refund_amount' => 0,
                'hours_remaining' => 0,
                'status' => $status
            ];
        }

        if (in_array($status, ['completed', 'انجام شده'])) {
            return [
                'can_cancel' => false,
                'reason' => 'این نوبت انجام شده و پرونده ویزیت آن ثبت گردیده است.',
                'refund_amount' => 0,
                'hours_remaining' => 0,
                'status' => $status
            ];
        }

        // Calculate appointment timestamp
        $aptDate = $apt['appointment_date'] ?? '';
        $aptTime = $apt['appointment_time'] ?? '00:00';
        $aptTimestamp = strtotime("{$aptDate} {$aptTime}");

        if ($aptTimestamp === false) {
            $aptTimestamp = strtotime($aptDate);
        }

        $currentTime = time();
        $secondsDiff = $aptTimestamp - $currentTime;
        $hoursRemaining = round($secondsDiff / 3600, 1);

        // Strict 24-Hour Rule (86,400 seconds)
        if ($secondsDiff < 86400) {
            $reason = ($secondsDiff <= 0)
                ? 'زمان این نوبت فرا رسیده یا منقضی گردیده است و امکان لغو وجود ندارد.'
                : 'کمتر از ۲۴ ساعت به زمان نوبت رزرو شده باقی مانده است (حدود ' . max(0, $hoursRemaining) . ' ساعت). طبق منشور حقوقی و نظام‌نامه کلینیک‌های پلتفرم آسنا، جهت جبران مسدودی تایم متخصص، امکان استرداد وجه و لغو نوبت زیر ۲۴ ساعت وجود ندارد.';

            return [
                'can_cancel' => false,
                'reason' => $reason,
                'refund_amount' => 0,
                'hours_remaining' => $hoursRemaining,
                'status' => $status
            ];
        }

        return [
            'can_cancel' => true,
            'reason' => 'بیشتر از ۲۴ ساعت تا زمان نوبت باقی مانده است و لغو با استرداد ۱۰۰٪ به کیف‌پول مجاز است.',
            'refund_amount' => $fee,
            'hours_remaining' => $hoursRemaining,
            'status' => $status
        ];
    }

    /**
     * Execute Appointment Cancellation with 100% In-App Wallet Refund
     */
    public function cancelAppointment(int $appointmentId, int $userId): array
    {
        $check = $this->checkAppointmentEligibility($appointmentId, $userId);
        if (!$check['can_cancel']) {
            return [
                'success' => false,
                'message' => $check['reason']
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE appointments 
                SET status = 'cancelled' 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$appointmentId, $userId]);

            $refundAmount = $check['refund_amount'];
            if ($refundAmount > 0) {
                $this->wallet->refund(
                    $userId,
                    $refundAmount,
                    'appointments',
                    (string)$appointmentId,
                    "استرداد ۱۰۰٪ هزینه رزرو نوبت #{$appointmentId} به کیف‌پول درون‌برنامه‌ای"
                );
            }

            $this->pdo->commit();

            // SMS Notification
            try {
                $uStmt = $this->pdo->prepare("SELECT phone FROM users WHERE id = ?");
                $uStmt->execute([$userId]);
                $phone = $uStmt->fetchColumn();
                if ($phone) {
                    $msg = "نوبت پزشکی/آرایشگری شما (شناسه #{$appointmentId}) لغو گردید و مبلغ " . number_format($refundAmount) . " تومان به کیف‌پول آسنا شما بازگشت داده شد.";
                    $this->sms->sendNotification($phone, $msg);
                }
            } catch (Throwable $e) {}

            return [
                'success' => true,
                'refund_amount' => $refundAmount,
                'message' => "نوبت با موفقیت لغو شد و مبلغ " . number_format($refundAmount) . " تومان بلافاصله به کیف‌پول درون‌برنامه‌ای شما واریز گردید."
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'خطای سیستمی در لغو نوبت: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if a subscription is eligible for cancellation and compute prorated refund
     *
     * Rule:
     * - If in the last week before the next autoship shipment date (within 7 days) AND the parcel is already locked/packing:
     *   Cancellation is blocked. User must receive this pack first.
     * - Outside the blackout week or after receipt:
     *   User can cancel anytime; remaining unused deliveries/cycles are prorated and refunded 100% to wallet.
     *
     * @param int $subscriptionId
     * @param int $userId
     * @return array [
     *   'can_cancel' => bool,
     *   'reason' => string,
     *   'refund_amount' => int,
     *   'is_blackout_week' => bool,
     *   'remaining_deliveries' => int,
     *   'status' => string
     * ]
     */
    public function checkSubscriptionEligibility(int $subscriptionId, int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_subscriptions WHERE id = ? AND user_id = ?");
        $stmt->execute([$subscriptionId, $userId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sub) {
            return [
                'can_cancel' => false,
                'reason' => 'اشتراک مورد نظر یافت نشد.',
                'refund_amount' => 0,
                'is_blackout_week' => false,
                'remaining_deliveries' => 0,
                'status' => 'not_found'
            ];
        }

        $status = $sub['status'] ?? '';
        if ($status === 'cancelled') {
            return [
                'can_cancel' => false,
                'reason' => 'این اشتراک قبلاً لغو شده است.',
                'refund_amount' => 0,
                'is_blackout_week' => false,
                'remaining_deliveries' => 0,
                'status' => $status
            ];
        }

        // Check next delivery date and blackout week (7 days)
        $nextDate = $sub['next_delivery_date'] ?? null;
        $isBlackoutWeek = false;

        if ($nextDate) {
            $nextTimestamp = strtotime($nextDate);
            $diffDays = ($nextTimestamp - time()) / 86400;

            // Check if there is an in-flight delivery currently 'shipped' or scheduled in <= 7 days
            $flightStmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM subscription_deliveries 
                WHERE subscription_id = ? AND status = 'shipped'
            ");
            $flightStmt->execute([$subscriptionId]);
            $inFlightCount = (int)$flightStmt->fetchColumn();

            if ($inFlightCount > 0) {
                return [
                    'can_cancel' => false,
                    'reason' => 'بسته این دوره تحویل شرکت پست گردیده و در مسیر ارسال به شماست. طبق قوانین اشتراک هوشمند (Autoship)، ابتدا باید بسته ارسالی را دریافت و تحویل بگیرید؛ سپس می‌توانید اشتراک را لغو کرده و مابقی دوره‌ها را در کیف‌پول استرداد نمایید.',
                    'refund_amount' => 0,
                    'is_blackout_week' => true,
                    'remaining_deliveries' => 0,
                    'status' => $status
                ];
            }

            if ($diffDays >= 0 && $diffDays <= 7) {
                // In final preparation week
                return [
                    'can_cancel' => false,
                    'reason' => 'اشتراک در هفته پایانی آماده‌سازی و ارسال بسته جاری قرار دارد (' . round($diffDays) . ' روز مانده به موعد ارسال). طبق قوانین اشتراک دوره‌ای، سفارش این دوره قطعی گردیده است. پس از دریافت بسته می‌توانید اشتراک را لغو و مابقی ماه‌ها/هفته‌ها را به کیف‌پول بازگردانید.',
                    'refund_amount' => 0,
                    'is_blackout_week' => true,
                    'remaining_deliveries' => 0,
                    'status' => $status
                ];
            }
        }

        // Calculate prorated remaining unused deliveries
        $pendingStmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM subscription_deliveries 
            WHERE subscription_id = ? AND status = 'pending'
        ");
        $pendingStmt->execute([$subscriptionId]);
        $remainingCount = (int)$pendingStmt->fetchColumn();

        $totalStmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM subscription_deliveries 
            WHERE subscription_id = ?
        ");
        $totalStmt->execute([$subscriptionId]);
        $totalCount = (int)$totalStmt->fetchColumn();

        $subAmount = (int)($sub['amount'] ?? 0);
        $refundAmount = 0;

        if ($totalCount > 0 && $remainingCount > 0) {
            $costPerDelivery = (int)round($subAmount / $totalCount);
            $refundAmount = $costPerDelivery * $remainingCount;
        } elseif ($remainingCount === 0 && $totalCount === 0) {
            // No granular deliveries record, fallback to monthly duration
            $duration = max(1, (int)($sub['duration_months'] ?? 1));
            $refundAmount = $subAmount;
        }

        return [
            'can_cancel' => true,
            'reason' => "لغو اشتراک امکان‌پذیر است. تعداد {$remainingCount} نوبت ارسال باقی‌مانده محاسبه و مبلغ آن به کیف‌پول بازگردانده می‌شود.",
            'refund_amount' => $refundAmount,
            'is_blackout_week' => false,
            'remaining_deliveries' => $remainingCount,
            'status' => $status
        ];
    }

    /**
     * Execute Subscription Cancellation with Prorated In-App Wallet Refund
     */
    public function cancelSubscription(int $subscriptionId, int $userId): array
    {
        $check = $this->checkSubscriptionEligibility($subscriptionId, $userId);
        if (!$check['can_cancel']) {
            return [
                'success' => false,
                'message' => $check['reason']
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE user_subscriptions 
                SET status = 'cancelled' 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$subscriptionId, $userId]);

            // Cancel all pending unfulfilled deliveries
            $this->pdo->prepare("
                UPDATE subscription_deliveries 
                SET status = 'cancelled' 
                WHERE subscription_id = ? AND status = 'pending'
            ")->execute([$subscriptionId]);

            // Prorated refund to In-App Wallet
            $refundAmount = $check['refund_amount'];
            if ($refundAmount > 0) {
                $this->wallet->refund(
                    $userId,
                    $refundAmount,
                    'subscriptions',
                    (string)$subscriptionId,
                    "استرداد مانده دوره‌های لغو اشتراک هوشمند #SUB-{$subscriptionId} به کیف‌پول"
                );
            }

            $this->pdo->commit();

            // SMS Notification
            try {
                $uStmt = $this->pdo->prepare("SELECT phone FROM users WHERE id = ?");
                $uStmt->execute([$userId]);
                $phone = $uStmt->fetchColumn();
                if ($phone) {
                    $msg = "اشتراک شما در آسنا لغو گردید و مبلغ " . number_format($refundAmount) . " تومان بابت دوره‌های باقی‌مانده به کیف‌پول شما واریز شد.";
                    $this->sms->sendNotification($phone, $msg);
                }
            } catch (Throwable $e) {}

            return [
                'success' => true,
                'refund_amount' => $refundAmount,
                'message' => ($refundAmount > 0)
                    ? "اشتراک با موفقیت لغو شد و مبلغ " . number_format($refundAmount) . " تومان بابت دوره‌های باقی‌مانده به کیف‌پول شما واریز گردید."
                    : "اشتراک شما با موفقیت لغو شد."
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'خطای سیستمی در لغو اشتراک: ' . $e->getMessage()
            ];
        }
    }
}
