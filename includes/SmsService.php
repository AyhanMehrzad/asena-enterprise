<?php
/**
 * SmsService.php — Robust Meli Payamak SMS Gateway for ASENA Platform
 *
 * Implements:
 * 1. Strict Phone Number Normalizer (Persian/Arabic digit conversion & Iranian 09... format)
 * 2. Strict Indexed Array Pattern Logic for BaseServiceNumber ({0}, {1}, ...)
 * 3. Comprehensive Diagnostic Logging (Outbound payload with masked password, raw response, gateway status codes)
 * 4. Dual REST & SOAP gateway delivery with graceful fallbacks
 */

class SmsService {
    private $apiKey;
    private $username;
    private $password;
    private $from;
    private bool $isMock = false;

    private $lastError = '';
    private $lastLog = [];

    // Pattern Body IDs from Melipayamak panel (Can be overridden via .env or DB settings)
    const BODY_ID_OTP               = '518597'; // کد تایید ورود/ثبت نام/فراموشی رمز (تایید شده)
    const BODY_ID_BOOKING           = '528861'; // تایید رزرو نوبت به کاربر
    const BODY_ID_RESCHEDULE        = '528862'; // تغییر زمان نوبت
    const BODY_ID_SHIPPING          = '528863'; // ارسال سفارش به خریدار
    const BODY_ID_SUBSCRIPTION      = '528864'; // فعال‌سازی بسته اشتراک
    const BODY_ID_CHARITY           = '528865'; // قدردانی خیریه
    const BODY_ID_ADMIN_ORDER       = '528866'; // اطلاع‌رسانی سفارش جدید به مدیر
    const BODY_ID_DOCTOR_BOOKING    = '528867'; // اطلاع‌رسانی نوبت جدید به پزشک
    const BODY_ID_DOCTOR_TELEHEALTH = '528867'; // اطلاع‌رسانی پیام جدید تله‌هلث به پزشک
    const BODY_ID_USER_CHAT         = '518597'; // اطلاع‌رسانی پیام جدید به کاربر/بیمار

    public function __construct() {
        self::loadEnv();
        require_once __DIR__ . '/functions.php';

        global $pdo;
        if (!($pdo instanceof PDO)) {
            $dbFile = __DIR__ . '/db.php';
            if (file_exists($dbFile)) {
                require_once $dbFile;
            }
        }

        $defaultApiKey   = 'efaec6c8-2daf-4473-9080-df7ac67eea89';
        $defaultUsername = '9146676978';
        $defaultPassword = 'efaec6c8-2daf-4473-9080-df7ac67eea89';
        $defaultFrom     = '2170002198';

        $dbApiKey   = ($pdo instanceof PDO) ? get_setting($pdo, 'melipayamak_api_key', '') : '';
        $dbUsername = ($pdo instanceof PDO) ? get_setting($pdo, 'melipayamak_username', '') : '';
        $dbPassword = ($pdo instanceof PDO) ? get_setting($pdo, 'melipayamak_password', '') : '';
        $dbFrom     = ($pdo instanceof PDO) ? get_setting($pdo, 'melipayamak_from', '') : '';
        $dbSandbox  = ($pdo instanceof PDO) ? get_setting($pdo, 'melipayamak_sandbox', null) : null;

        // Auto-heal dummy/stale seed values in database (such as on cPanel hosting)
        if ($pdo instanceof PDO) {
            $isDummyKey  = empty($dbApiKey) || str_contains($dbApiKey, 'SANDBOX') || str_contains($dbApiKey, 'd3cbc1e6');
            $isDummyUser = empty($dbUsername) || $dbUsername === 'asena_enterprise' || $dbUsername === 'your_username';
            if ($isDummyKey || $isDummyUser) {
                set_setting($pdo, 'melipayamak_api_key', $defaultApiKey);
                set_setting($pdo, 'melipayamak_username', $defaultUsername);
                set_setting($pdo, 'melipayamak_password', $defaultPassword);
                set_setting($pdo, 'melipayamak_from', $defaultFrom);
                set_setting($pdo, 'melipayamak_sandbox', '0');

                $dbApiKey   = $defaultApiKey;
                $dbUsername = $defaultUsername;
                $dbPassword = $defaultPassword;
                $dbFrom     = $defaultFrom;
                $dbSandbox  = '0';
            }
        }

        $this->apiKey   = (!empty($dbApiKey) && !str_contains($dbApiKey, 'SANDBOX')) ? trim((string)$dbApiKey) : (getenv('MELIPAYAMAK_API_KEY') ?: ($_ENV['MELIPAYAMAK_API_KEY'] ?? $defaultApiKey));
        $rawUsername    = (!empty($dbUsername) && $dbUsername !== 'asena_enterprise' && $dbUsername !== 'your_username') ? trim((string)$dbUsername) : (getenv('MELIPAYAMAK_USERNAME') ?: ($_ENV['MELIPAYAMAK_USERNAME'] ?? $defaultUsername));
        $this->username = $rawUsername; // Preserves both mobile numbers and alphanumeric usernames!
        $this->password = !empty($dbPassword) ? trim((string)$dbPassword) : (getenv('MELIPAYAMAK_PASSWORD') ?: ($_ENV['MELIPAYAMAK_PASSWORD'] ?? $defaultPassword));
        $this->from     = !empty($dbFrom) ? trim((string)$dbFrom) : (getenv('MELIPAYAMAK_FROM') ?: ($_ENV['MELIPAYAMAK_FROM'] ?? $defaultFrom));

        // Safe Sandbox Detection:
        $isExplicitSandbox = ($dbSandbox === '1');
        $hasRealApiKey = !empty($this->apiKey) && !str_contains($this->apiKey, 'SANDBOX') && strlen($this->apiKey) >= 16;
        $hasRealUserPass = !empty($this->username) && !empty($this->password) && $this->username !== 'your_username' && $this->password !== 'your_password';

        // Run live if explicit live credentials exist; otherwise run in safe mock sandbox
        $this->isMock = ($isExplicitSandbox || (!$hasRealApiKey && !$hasRealUserPass));
    }

    public function isMock(): bool {
        return $this->isMock;
    }

    public function getActiveGateway(): string {
        if ($this->isMock) return 'mock';
        if (!empty($this->apiKey) && !str_contains($this->apiKey, 'SANDBOX')) return 'console';
        return 'classic';
    }

    /**
     * Resolve effective credential secret (ApiKey if set, or panel password)
     */
    public function getAuthSecret(): string {
        if (!empty($this->apiKey) && !str_contains($this->apiKey, 'SANDBOX') && strlen($this->apiKey) >= 16) {
            return $this->apiKey;
        }
        return $this->password;
    }

    /**
     * Ensure environment variables from root .env are loaded into getenv() and $_ENV
     */
    public static function loadEnv() {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;

        $envPaths = [
            __DIR__ . '/../../.env',
            __DIR__ . '/../.env',
            __DIR__ . '/.env',
            dirname(__DIR__, 2) . '/.env'
        ];

        foreach ($envPaths as $path) {
            if (file_exists($path) && is_readable($path)) {
                $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0) continue;
                    if (strpos($line, '=') !== false) {
                        list($key, $val) = explode('=', $line, 2);
                        $key = trim($key);
                        $val = trim($val, " \t\n\r\0\x0B\"'");
                        putenv("$key=$val");
                        $_ENV[$key] = $val;
                    }
                }
                break;
            }
        }
    }

    /**
     * Get effective body ID for a pattern type (reads DB settings first, then .env, then constant)
     */
    public static function getBodyId($type) {
        self::loadEnv();
        require_once __DIR__ . '/functions.php';

        global $pdo;
        if (!($pdo instanceof PDO)) {
            $dbFile = __DIR__ . '/db.php';
            if (file_exists($dbFile)) {
                require_once $dbFile;
            }
        }

        // 1. Priority 1: Dynamic settings configured in Admin Panel
        if ($pdo instanceof PDO) {
            try {
                $dbVal = get_setting($pdo, "melipayamak_body_id_{$type}", '');
                if (!empty($dbVal) && is_numeric($dbVal)) {
                    return trim((string)$dbVal);
                }
            } catch (Throwable $e) {}
        }

        $envMap = [
            'otp'               => 'MELIPAYAMAK_BODY_ID_OTP',
            'booking'           => 'MELIPAYAMAK_BODY_ID_BOOKING',
            'shipping'          => 'MELIPAYAMAK_BODY_ID_SHIPPING',
            'subscription'      => 'MELIPAYAMAK_BODY_ID_SUBSCRIPTION',
            'charity'           => 'MELIPAYAMAK_BODY_ID_CHARITY',
            'reschedule'        => 'MELIPAYAMAK_BODY_ID_RESCHEDULE',
            'admin_order'       => 'MELIPAYAMAK_BODY_ID_ADMIN_ORDER',
            'doctor_booking'    => 'MELIPAYAMAK_BODY_ID_DOCTOR_BOOKING',
            'doctor_telehealth' => 'MELIPAYAMAK_BODY_ID_DOCTOR_TELEHEALTH',
            'user_chat'         => 'MELIPAYAMAK_BODY_ID_USER_CHAT',
        ];

        $constMap = [
            'otp'               => self::BODY_ID_OTP,
            'booking'           => self::BODY_ID_BOOKING,
            'shipping'          => self::BODY_ID_SHIPPING,
            'subscription'      => self::BODY_ID_SUBSCRIPTION,
            'charity'           => self::BODY_ID_CHARITY,
            'reschedule'        => self::BODY_ID_RESCHEDULE,
            'admin_order'       => self::BODY_ID_ADMIN_ORDER,
            'doctor_booking'    => self::BODY_ID_DOCTOR_BOOKING,
            'doctor_telehealth' => self::BODY_ID_DOCTOR_TELEHEALTH,
            'user_chat'         => self::BODY_ID_USER_CHAT,
        ];

        // 2. Priority 2: Root .env configuration
        if (isset($envMap[$type])) {
            $envVal = getenv($envMap[$type]) ?: ($_ENV[$envMap[$type]] ?? null);
            if (!empty($envVal)) return trim((string)$envVal);
        }

        // 3. Priority 3: Fallback class constant
        return $constMap[$type] ?? '12345';
    }

    /**
     * 1. Phone Number Normalizer:
     * - Convert Persian/Arabic digits (۰-۹ / ٠-٩) to standard ASCII (0-9)
     * - Strip all non-numeric characters (spaces, hyphens, parentheses)
     * - Normalize format to standard 11 digits: convert "+98" or "98" to "0". Ensure numbers start with "09"
     */
    public static function normalizePhone($phone) {
        if (empty($phone)) return '';
        $phone = (string)$phone;

        // Convert Persian digits
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        // Convert Arabic digits
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $ascii   = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $phone = str_replace($persian, $ascii, $phone);
        $phone = str_replace($arabic, $ascii, $phone);

        // Strip non-digit characters except leading +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        $phone = trim($phone);

        // Normalize international prefixes
        if (strpos($phone, '+98') === 0) {
            $phone = '0' . substr($phone, 3);
        } elseif (strpos($phone, '0098') === 0) {
            $phone = '0' . substr($phone, 4);
        } elseif (strpos($phone, '98') === 0 && strlen($phone) === 12) {
            $phone = '0' . substr($phone, 2);
        } elseif (preg_match('/^9\d{9}$/', $phone)) {
            $phone = '0' . $phone;
        }

        // Final cleanup of non-digits
        $phone = preg_replace('/\D/', '', $phone);

        // Verify standard Iranian mobile format (11 digits, starts with 09)
        if (preg_match('/^09\d{9}$/', $phone)) {
            return $phone;
        }

        return $phone;
    }

    /**
     * Sanitize OTP and verification input:
     * Strips whitespace and converts Persian/Arabic digits to ASCII
     */
    public static function sanitizeCode($code) {
        if (empty($code)) return '';
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $ascii   = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $code = str_replace($persian, $ascii, (string)$code);
        $code = str_replace($arabic, $ascii, $code);
        return preg_replace('/\D/', '', trim($code));
    }

    /**
     * Send OTP / Verification code
     * Pattern expects {0} = verification code
     */
    public function sendOtp($phone, $code) {
        $phone = self::normalizePhone($phone);
        $code  = self::sanitizeCode($code);

        if (empty($phone) || strlen($phone) !== 11) {
            $this->lastError = 'شماره موبایل وارد شده نامعتبر است.';
            return false;
        }
        if (empty($code)) {
            $this->lastError = 'کد تایید نامعتبر است.';
            return false;
        }

        $bodyId = self::getBodyId('otp');
        // Indexed array of values strictly matching {0}
        $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$code], 'OTP');
        if (!$sent) {
            $text = "کد تایید شما در سامانه آسنا: $code\nasena.company";
            return $this->sendDirectSms($phone, $text, 'OTP_FALLBACK');
        }
        return true;
    }

    /**
     * Send Booking Confirmation to User
     * Pattern: {0} = Date, {1} = Time
     */
    public function sendBookingConfirmation($phone, $date, $time) {
        $phone = self::normalizePhone($phone);
        $bodyId = self::getBodyId('booking');
        $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$date, (string)$time], 'BOOKING_USER');
        if (!$sent) {
            $text = "کاربر گرامی، نوبت ویزیت شما در آسنا برای تاریخ $date ساعت $time با موفقیت تایید شد.\nasena.company";
            return $this->sendDirectSms($phone, $text, 'BOOKING_FALLBACK');
        }
        return true;
    }

    /**
     * Send Appointment Reschedule Alert
     * Pattern: {0} = Doctor, {1} = Pet, {2} = Date, {3} = Time
     */
    public function sendAppointmentReschedule($phone, $doctorName, $petName, $newDate, $newTime, $reason = '') {
        $phone = self::normalizePhone($phone);
        $bodyId = self::getBodyId('reschedule');
        $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$doctorName, (string)$petName, (string)$newDate, (string)$newTime], 'RESCHEDULE');
        if (!$sent) {
            $text = "کاربر گرامی آسنا، زمان نوبت ویزیت پت شما ($petName) با دکتر $doctorName به علت «" . ($reason ?: 'موارد فورس‌ماژور و هماهنگی مجدد مطب') . "» به تاریخ $newDate ساعت $newTime تغییر یافت.\nasena.company";
            return $this->sendDirectSms($phone, $text, 'RESCHEDULE_FALLBACK');
        }
        return true;
    }

    /**
     * Send Shipping / Order status update to buyer
     * Pattern: {0} = Order ID
     */
    public function sendShippingUpdate($phone, $orderId) {
        $phone = self::normalizePhone($phone);
        $bodyId = self::getBodyId('shipping');
        $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$orderId], 'SHIPPING');
        if (!$sent) {
            $text = "سفارش شما به شماره $orderId در آسنا پردازش و تحویل واحد ارسال شد.\nasena.company";
            return $this->sendDirectSms($phone, $text, 'SHIPPING_FALLBACK');
        }
        return true;
    }

    /**
     * Send Subscription Confirmation to user
     * Pattern: {0} = Plan Name
     */
    public function sendSubscriptionSent($phone, $planName = 'ماهانه') {
        $phone = self::normalizePhone($phone);
        $bodyId = self::getBodyId('subscription');
        $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$planName], 'SUBSCRIPTION');
        if (!$sent) {
            $text = "اشتراک $planName شما در سامانه آسنا با موفقیت فعال گردید.\nasena.company";
            return $this->sendDirectSms($phone, $text, 'SUBSCRIPTION_FALLBACK');
        }
        return true;
    }

    /**
     * Send Charity Donation Thank You
     * Pattern: {0} = Formatted Amount
     */
    public function sendCharityThankYou($phone, $amount) {
        $phone = self::normalizePhone($phone);
        $formattedAmount = number_format((float)$amount);
        $bodyId = self::getBodyId('charity');
        $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$formattedAmount], 'CHARITY');
        if (!$sent) {
            $text = "کاربر گرامی، از حمایت ارزشمند شما به مبلغ $formattedAmount تومان به پویش خیریه حیوانات آسنا سپاسگزاریم.\nasena.company";
            return $this->sendDirectSms($phone, $text, 'CHARITY_FALLBACK');
        }
        return true;
    }

    /**
     * Send New Order Notification to Admin(s)
     * Pattern: {0} = Order ID, {1} = Total Amount
     */
    public function sendAdminNewOrderAlert($phones, $orderId, $totalAmount) {
        if (empty($phones)) return false;

        $phoneList = is_array($phones) ? $phones : preg_split('/[,\s;]+/', (string)$phones);
        $formattedAmount = number_format((float)$totalAmount);
        $bodyId = self::getBodyId('admin_order');

        $atLeastOneSent = false;
        foreach ($phoneList as $p) {
            $p = self::normalizePhone($p);
            if (empty($p) || strlen($p) !== 11) continue;

            $sent = $this->sendPatternRequest($p, $bodyId, [(string)$orderId, (string)$formattedAmount], 'ADMIN_ORDER');
            if (!$sent) {
                $text = "مدیر گرامی، سفارش جدید به شماره $orderId با مبلغ $formattedAmount تومان در سامانه آسنا ثبت شد.\nasena.company";
                $this->sendDirectSms($p, $text, 'ADMIN_ORDER_FALLBACK');
            }
            $atLeastOneSent = true;
        }

        return $atLeastOneSent;
    }

    /**
     * Send New Appointment Notification to Doctor
     * Pattern: {0} = Doctor Name, {1} = Pet Name, {2} = Date, {3} = Time
     */
    public function sendDoctorNewAppointmentAlert($phone, $doctorName, $petName, $date, $time) {
        $phone = self::normalizePhone($phone);
        if (empty($phone) || strlen($phone) !== 11) return false;

        $doctorName = $doctorName ?: 'همکار گرامی';
        $petName    = $petName ?: 'پت بیمار';
        $bodyId     = self::getBodyId('doctor_booking');

        $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$doctorName, (string)$petName, (string)$date, (string)$time], 'DOCTOR_BOOKING');
        if (!$sent) {
            $text = "دکتر $doctorName گرامی، نوبت جدید برای پت ($petName) در تاریخ $date ساعت $time در آسنا ثبت شد.\nasena.company";
            return $this->sendDirectSms($phone, $text, 'DOCTOR_BOOKING_FALLBACK');
        }

        return true;
    }

    /**
     * Send Telehealth Message Notification to Doctor
     * Pattern: {0} = Doctor Name, {1} = Patient Name
     */
    public function sendDoctorTelehealthAlert($phone, $doctorName, $patientName, $ticketId = 0) {
        $phone = self::normalizePhone($phone);
        if (empty($phone) || strlen($phone) !== 11) return false;

        $doctorName = !empty($doctorName) ? $doctorName : 'پزشک گرامی';
        $patientName = !empty($patientName) ? $patientName : 'مراجع';
        $bodyId = self::getBodyId('doctor_telehealth');

        if (!empty($bodyId) && $bodyId !== '12345') {
            $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$doctorName, (string)$patientName], 'DOCTOR_TELEHEALTH');
            if ($sent) return true;
        }

        // Direct fallback
        $url = 'https://asena.company/doctor/telehealth.php' . ($ticketId ? "?ticket_id=" . (int)$ticketId : "");
        $text = "دکتر {$doctorName} گرامی،\nپیام جدیدی از بیمار {$patientName} در سامانه تله‌هلث آسنا ثبت شد.\nورود و پاسخگویی:\n{$url}";
        return $this->sendDirectSms($phone, $text, 'DOCTOR_TELEHEALTH_FALLBACK');
    }

    /**
     * Send New Chat / Consultation Message Notification to User / Patient
     * Pattern: {0} = User Name, {1} = Sender Name
     */
    public function sendUserChatMessageAlert($phone, $userName, $senderName, $chatUrl = '') {
        $phone = self::normalizePhone($phone);
        if (empty($phone) || strlen($phone) !== 11) return false;

        $userName = !empty($userName) ? $userName : 'کاربر گرامی';
        $senderName = !empty($senderName) ? $senderName : 'پشتیبانی آسنا';
        $url = !empty($chatUrl) ? $chatUrl : 'https://asena.company/chat.php';
        $bodyId = self::getBodyId('user_chat');

        if (!empty($bodyId) && $bodyId !== '12345') {
            $sent = $this->sendPatternRequest($phone, $bodyId, [(string)$userName, (string)$senderName], 'USER_CHAT');
            if ($sent) return true;
        }

        // Direct fallback
        $text = "{$userName} گرامی،\nپیام جدیدی از طرف «{$senderName}» در سامانه آسنا دریافت شد.\nمشاهده و پاسخ:\n{$url}";
        return $this->sendDirectSms($phone, $text, 'USER_CHAT_FALLBACK');
    }

    /**
     * Get effective username for Melipayamak web services (strips leading 0 for mobile numbers)
     */
    public function getEffectiveUsername(): string {
        $u = trim((string)$this->username);
        if (preg_match('/^0(9\d{9})$/', $u, $m)) {
            return $m[1]; // e.g. 9146676978
        }
        return $u;
    }

    /**
     * Modern Console REST API for OTP and Patterns (console.melipayamak.com)
     */
    public function sendViaConsoleApi($phone, $bodyId, array $args, $isOtp = false) {
        if (empty($this->apiKey) || str_contains($this->apiKey, 'SANDBOX')) {
            return false;
        }

        $endpoint = $isOtp 
            ? "https://console.melipayamak.com/api/send/otp/{$this->apiKey}" 
            : "https://console.melipayamak.com/api/send/shared/{$this->apiKey}";

        $payload = [
            'to'     => $phone,
            'bodyId' => (int)$bodyId,
            'args'   => array_values(array_map('strval', $args))
        ];

        $startTime = microtime(true);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);
        $durationMs = round((microtime(true) - $startTime) * 1000);

        $this->logDiagnostic(($isOtp ? 'CONSOLE_OTP' : 'CONSOLE_SHARED'), $endpoint, ['Content-Type: application/json'], $payload, $httpCode, $response, $curlError, $durationMs);

        $result = json_decode((string)$response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($result['status']) && ($result['status'] === 200 || $result['status'] === 'success' || (isset($result['recId']) && $result['recId'] > 0))) {
                return true;
            }
            if (isset($result['recId']) && is_numeric($result['recId']) && (float)$result['recId'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Modern Console REST API for Direct Simple SMS (console.melipayamak.com)
     */
    public function sendDirectViaConsoleApi($phone, $text) {
        if (empty($this->apiKey) || str_contains($this->apiKey, 'SANDBOX')) {
            return false;
        }

        $endpoint = "https://console.melipayamak.com/api/send/simple/{$this->apiKey}";
        $payload = [
            'to'   => $phone,
            'from' => $this->from ?: '50004001',
            'text' => (string)$text
        ];

        $startTime = microtime(true);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);
        $durationMs = round((microtime(true) - $startTime) * 1000);

        $this->logDiagnostic('CONSOLE_SIMPLE', $endpoint, ['Content-Type: application/json'], $payload, $httpCode, $response, $curlError, $durationMs);

        $result = json_decode((string)$response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($result['status']) && ($result['status'] === 200 || $result['status'] === 'success' || (isset($result['recId']) && $result['recId'] > 0))) {
                return true;
            }
            if (isset($result['recId']) && is_numeric($result['recId']) && (float)$result['recId'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send pattern request with indexed variables
     */
    public function sendPatternRequest($phone, $bodyId, array $textVariables, $actionTag = 'PATTERN') {
        $phone = self::normalizePhone($phone);
        if (empty($phone)) {
            $this->lastError = 'شماره موبایل نامعتبر است.';
            return false;
        }

        $intBodyId = (int)$bodyId;
        if ($intBodyId <= 0) {
            $this->lastError = 'شناسه قالب (BodyId) نامعتبر است.';
            return false;
        }

        // Safe Mock Delivery in Sandbox Mode
        if ($this->isMock) {
            $this->logMockDelivery($actionTag, $phone, (string)$intBodyId, implode(' ; ', $textVariables));
            $this->lastError = 'اطلاعات وب‌سرویس ملی‌پیامک (کلید API یا نام کاربری و رمز عبور) تنظیم نشده است. لطفاً از پنل مدیریت > تنظیمات پیامک اقدام نمایید.';
            return false;
        }

        $indexedArgs = array_values(array_map('strval', $textVariables));
        $effectiveUser = $this->getEffectiveUsername();

        // 1. Primary Engine: Melipayamak Classic REST API (BaseServiceNumber)
        $url = "https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber";
        $headers = ['Content-Type: application/json; charset=utf-8'];
        $payload = [
            'username' => $effectiveUser,
            'password' => $this->getAuthSecret(),
            'text'     => implode(';', $indexedArgs),
            'to'       => $phone,
            'bodyId'   => $intBodyId
        ];

        $startTime = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);
        $durationMs = round((microtime(true) - $startTime) * 1000);

        // Full diagnostic logging
        $this->logDiagnostic($actionTag . '_REST', $url, $headers, $payload, $httpCode, $response, $curlError, $durationMs);

        $result = json_decode((string)$response, true);
        $retStatus = isset($result['RetStatus']) ? (int)$result['RetStatus'] : null;
        $val = isset($result['Value']) ? $result['Value'] : null;

        // If successful, return true
        if ($httpCode >= 200 && $httpCode < 300 && ($retStatus === 1 || (is_numeric($val) && (float)$val > 1000))) {
            return true;
        }

        // SOAP fallback if REST encounters issues and SOAP extension is loaded
        if (class_exists('SoapClient')) {
            try {
                $soapStart = microtime(true);
                $soapClient = new \SoapClient("http://api.payamak-panel.com/post/send.asmx?wsdl", [
                    'exceptions'         => true,
                    'connection_timeout' => 5,
                    'trace'              => 1
                ]);

                $soapData = [
                    'username' => $effectiveUser,
                    'password' => $this->getAuthSecret(),
                    'to'       => $phone,
                    'bodyId'   => $intBodyId,
                    'text'     => implode(';', $indexedArgs)
                ];

                $soapRes = $soapClient->SendByBaseNumber2($soapData);
                $rawSoapVal = $soapRes->SendByBaseNumber2Result ?? '';
                $soapDuration = round((microtime(true) - $soapStart) * 1000);

                $this->logDiagnostic(
                    $actionTag . '_SOAP',
                    'http://api.payamak-panel.com/post/send.asmx?wsdl',
                    ['SOAPAction: SendByBaseNumber2'],
                    $soapData,
                    200,
                    json_encode(['SoapResult' => $rawSoapVal]),
                    '',
                    $soapDuration
                );

                if (is_numeric($rawSoapVal) && (float)$rawSoapVal > 1000) {
                    return true;
                }
            } catch (\Exception $e) {
                $this->logDiagnostic(
                    $actionTag . '_SOAP_ERR',
                    'http://api.payamak-panel.com/post/send.asmx?wsdl',
                    ['SOAPAction: SendByBaseNumber'],
                    [],
                    500,
                    '',
                    $e->getMessage(),
                    0
                );
            }
        }

        return false;
    }

    /**
     * General send alias pointing to sendDirectSms
     */
    public function send($phone, $text) {
        return $this->sendDirectSms($phone, $text);
    }

    /**
     * Send Direct / Simple SMS (Used for general alerts and pattern fallback)
     */
    public function sendDirectSms($phone, $text, $actionTag = 'DIRECT') {
        $phone = self::normalizePhone($phone);
        if (empty($phone) || strlen($phone) !== 11) {
            $this->lastError = "شماره گیرنده نامعتبر است: $phone";
            return false;
        }

        // Safe Mock Delivery in Sandbox Mode
        if ($this->isMock) {
            $this->logMockDelivery($actionTag, $phone, null, (string)$text);
            $this->lastError = 'اطلاعات وب‌سرویس ملی‌پیامک (کلید API یا نام کاربری و رمز عبور) تنظیم نشده است. لطفاً از پنل مدیریت > تنظیمات پیامک اقدام نمایید.';
            return false;
        }

        // 1. Primary Modern Engine: Melipayamak Console REST API
        if (!empty($this->apiKey) && !str_contains($this->apiKey, 'SANDBOX') && strlen($this->apiKey) >= 16) {
            if ($this->sendDirectViaConsoleApi($phone, $text)) {
                return true;
            }
        }

        // 2. Secondary Engine: Melipayamak Classic REST API (SendSMS)
        $url = "https://rest.payamak-panel.com/api/SendSMS/SendSMS";
        $headers = ['Content-Type: application/json; charset=utf-8'];
        $payload = [
            'username' => $this->getEffectiveUsername(),
            'password' => $this->getAuthSecret(),
            'from'     => $this->from,
            'to'       => $phone,
            'text'     => (string)$text,
            'isflash'  => false
        ];

        $startTime = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);
        $durationMs = round((microtime(true) - $startTime) * 1000);

        // Full diagnostic logging
        $this->logDiagnostic($actionTag . '_DIRECT', $url, $headers, $payload, $httpCode, $response, $curlError, $durationMs);

        $result = json_decode((string)$response, true);
        $retStatus = isset($result['RetStatus']) ? (int)$result['RetStatus'] : null;
        $val = isset($result['Value']) ? $result['Value'] : null;

        if ($httpCode >= 200 && $httpCode < 300 && ($retStatus === 1 || (is_numeric($val) && (float)$val > 1000))) {
            return true;
        }

        return false;
    }

    /**
     * Record simulated SMS delivery in sandbox mode without hitting external network
     */
    private function logMockDelivery(string $actionTag, string $phone, ?string $bodyId, string $message): void {
        try {
            $pdo = $GLOBALS['pdo'] ?? null;
            if ($pdo) {
                $stmt = $pdo->prepare("
                    INSERT INTO sms_delivery_logs (phone, action_tag, body_id, message, is_mock, status, gateway_response, created_at)
                    VALUES (:phone, :tag, :body, :msg, 1, 'mock_delivered', '200 OK (Simulated Sandbox)', NOW())
                ");
                $stmt->execute([
                    'phone' => $phone,
                    'tag' => $actionTag,
                    'body' => $bodyId,
                    'msg' => substr($message, 0, 500)
                ]);
            }
        } catch (Throwable $e) {}
    }

    /**
     * 4. Diagnostic Logging:
     * - Logs raw payload sent to Melipayamak (with password masked)
     * - Logs full raw response (HTTP status, body, and returned Value or RetStatus)
     * - Interprets Melipayamak status codes to human-readable Persian explanations
     */
    private function logDiagnostic($action, $url, array $headers, array $payload, $httpCode, $rawResponse, $curlError, $durationMs) {
        $timestamp = date('Y-m-d H:i:s');

        // Mask sensitive credentials
        $maskedPayload = $payload;
        if (!empty($maskedPayload['password'])) {
            $p = (string)$maskedPayload['password'];
            $maskedPayload['password'] = strlen($p) > 8 ? substr($p, 0, 4) . '****' . substr($p, -4) : '****';
        }

        // Decode gateway response
        $decoded = json_decode((string)$rawResponse, true);
        $val = $decoded['Value'] ?? ($decoded['SoapResult'] ?? null);
        $retStatus = $decoded['RetStatus'] ?? null;
        $strRetStatus = $decoded['StrRetStatus'] ?? '';

        // Interpret gateway codes
        $interpretation = 'نامشخص';
        if ($retStatus === 1 || ($val !== null && is_numeric($val) && (float)$val > 1000)) {
            $interpretation = 'ارسال موفق به مخابرات (کد رهگیری: ' . ($val ?: $retStatus) . ')';
        } elseif ($val === '-108' || $retStatus === -108) {
            $interpretation = 'خطای -108: مسدود شدن موقت IP سرور در ملی‌پیامک به دلیل تلاش‌های ناموفق مکرر قبلی. این محدودیت به زودی (۱۰ الی ۱۵ دقیقه) به صورت خودکار رفع می‌شود یا می‌توانید IP سرور خود را در پنل ملی‌پیامک > تنظیمات > IP های مجاز ثبت کنید.';
        } elseif ($val === '-110' || $retStatus === -110) {
            $interpretation = 'خطای -110: الزام استفاده از ApiKey یا رمز عبور نامعتبر';
        } elseif ($val === '-111' || $retStatus === -111) {
            $interpretation = 'خطای -111: عدم دسترسی یا نام کاربری نامعتبر (باید به فرمت 09... باشد)';
        } elseif ($val === '-109' || $retStatus === -109) {
            $interpretation = 'خطای -109: الزام تنظیم IP مجاز در پنل ملی پیامک';
        } elseif ($val === '0' || $retStatus === 0) {
            $interpretation = 'خطای 0: نام کاربری یا رمز عبور اشتباه است';
        } elseif ($retStatus === 35 || $strRetStatus === 'InvalidData') {
            $interpretation = 'خطای 35: شماره در لیست سیاه مخابراتی (بلک‌لیست تبلیغات) قرار دارد؛ باید از وب‌سرویس خدماتی/الگو استفاده شود';
        } elseif (!empty($strRetStatus)) {
            $interpretation = "وضعیت درگاه: $strRetStatus (کد $retStatus / $val)";
        } elseif (!empty($curlError)) {
            $interpretation = "خطای ارتباطی cURL: $curlError";
        }

        $this->lastError = $interpretation;
        $this->lastLog = [
            'time'           => $timestamp,
            'action'         => $action,
            'url'            => $url,
            'http_code'      => $httpCode,
            'duration_ms'    => $durationMs,
            'curl_error'     => $curlError,
            'payload'        => $maskedPayload,
            'raw_response'   => (string)$rawResponse,
            'ret_status'     => $retStatus,
            'value'          => $val,
            'interpretation' => $interpretation
        ];

        // Format single-line JSON log entry for dedicated log file
        $logEntry = json_encode([
            'time'           => $timestamp,
            'action'         => $action,
            'url'            => $url,
            'http_code'      => $httpCode,
            'duration_ms'    => $durationMs,
            'curl_error'     => $curlError ?: null,
            'payload'        => $maskedPayload,
            'response'       => $decoded ?: $rawResponse,
            'interpretation' => $interpretation
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        // Write to project logs directory (safe under open_basedir)
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/sms.log';
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Also write summary to PHP error log for monitoring
        error_log("SmsService [$action] To: {$payload['to']} | HTTP: $httpCode | Gateway: $val / $retStatus | $interpretation");
    }

    /**
     * Get last diagnostic log
     */
    public function getLastLog() {
        return $this->lastLog;
    }

    /**
     * Get last error description
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Query account credit from Melipayamak
     */
    public function getCredit() {
        if (class_exists('SoapClient')) {
            try {
                $client = new \SoapClient("http://api.payamak-panel.com/post/send.asmx?wsdl", [
                    'encoding' => 'UTF-8',
                    'exceptions' => true,
                    'connection_timeout' => 5
                ]);
                $res = $client->GetCredit([
                    'username' => $this->getEffectiveUsername(),
                    'password' => $this->getAuthSecret()
                ]);
                return isset($res->GetCreditResult) ? (float)$res->GetCreditResult : null;
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get remaining paid SMS credits for a seller/doctor/clinic
     */
    public static function getUserSmsCredits(PDO $pdo, int $userId): int {
        $stmt = $pdo->prepare("SELECT sms_credits FROM seller_wallets WHERE seller_id = ?");
        $stmt->execute([$userId]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? (int)$val : 0;
    }

    /**
     * Deduct SMS credit from seller/doctor/clinic wallet upon sending
     */
    public static function deductUserSmsCredits(PDO $pdo, int $userId, string $recipient, string $message, int $credits = 1): bool {
        $stmt = $pdo->prepare("UPDATE seller_wallets SET sms_credits = GREATEST(0, sms_credits - ?) WHERE seller_id = ? AND sms_credits >= ?");
        $stmt->execute([$credits, $userId, $credits]);
        if ($stmt->rowCount() > 0) {
            $log = $pdo->prepare("INSERT INTO sms_usage_logs (user_id, recipient, message, credits_deducted) VALUES (?, ?, ?, ?)");
            $log->execute([$userId, $recipient, $message, $credits]);
            return true;
        }
        return false;
    }
}
