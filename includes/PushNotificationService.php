<?php
/**
 * ASENA Enterprise - Unified Push & Event Notification Service
 * Integrates Web Push with Melipayamak SMS Fallback
 * Version: 1.0.0
 */

require_once __DIR__ . '/SmsService.php';

class PushNotificationService {
    private SmsService $sms;

    public function __construct(?SmsService $sms = null) {
        $this->sms = $sms ?? new SmsService();
    }

    /**
     * Send Vaccination Due Alert (Chewy.com Benchmark)
     */
    public function sendVaccinationAlert(string $phone, string $petName, string $vaccineName, string $dueDate): bool {
        $message = "آسنا: موعد تزریق واکسن {$vaccineName} برای {$petName} نزدیک است ({$dueDate}). جهت رزرو نوبت کلینیک به پنل کاربری مراجعه نمایید.";
        return (bool)$this->sms->send($phone, $message);
    }

    /**
     * Send Autoship Upcoming Shipment Notice (Chewy.com Benchmark)
     */
    public function sendAutoshipNotice(string $phone, int $subId, int $daysUntilShipment = 3): bool {
        $message = "آسنا: سفارش ادواری (#{$subId}) شما تا {$daysUntilShipment} روز دیگر ارسال خواهد شد. در صورت نیاز به تغییر تاریخ یا اقلام، به پنل مراجعه فرمایید.";
        return (bool)$this->sms->send($phone, $message);
    }

    /**
     * Send Order Shipping & Fulfillment Alert (Amazon / Digikala Benchmark)
     */
    public function sendOrderDispatchAlert(string $phone, int $orderId, string $carrier, string $trackingCode): bool {
        $message = "آسنا: مرسوله سفارش (#{$orderId}) به ناوگان {$carrier} تحویل شد. کد رهگیری: {$trackingCode}";
        return (bool)$this->sms->send($phone, $message);
    }
}
