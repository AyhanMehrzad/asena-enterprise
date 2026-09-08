<?php
/**
 * ASENA Enterprise - TrafficMonitoringService
 *
 * Universal Request/Interaction Logger, Cloudflare-Aware Anomaly & DDoS Detection,
 * Automated Time-Based IP Restriction, and User Request Journey Investigator.
 *
 * Version: 1.0.0
 */

require_once __DIR__ . '/SecurityAuditService.php';

class TrafficMonitoringService {
    private PDO $db;
    private SecurityAuditService $audit;

    // L7 DDoS Flood thresholds
    public const BURST_WINDOW_10S_THRESHOLD = 50;   // 50 requests in 10s = L7 flood
    public const SUSTAINED_WINDOW_60S_THRESHOLD = 150; // 150 requests in 60s = sustained attack

    // Malicious vulnerability scanner patterns
    private static array $scannerPatterns = [
        '/\.(env|git|svn|htaccess|htpasswd|bak|sql|tar|gz|zip)(?:\/|\?|$)/i' => 'پویش و اسکن فایل‌های حساس محیطی و پیکربندی',
        '/(?:wp-login|wp-admin|wp-content|wp-includes|wp-config)/i' => 'پویش اسکریپت‌ها و افزونه‌های وردپرس (CMS Scanner)',
        '/(?:eval-stdin|phpinfo|info\.php|test\.php|shell\.php|cmd\.php|alfa|c99|r57)/i' => 'تلاش برای تزریق وب‌شل و اجرای کد دلخواه (Webshell Probe)',
        '/(?:adminer|pma|phpmyadmin|myadmin|sqladmin)/i' => 'پویش ابزارهای مدیریت دیتابیس (DB Probe)',
        '/(?:actuator|web-inf|meta-inf|setup\.cgi|\.aws)/i' => 'پویش اندپوینت‌های فریم‌ورک‌ها و کلیدهای ابری (Cloud Probe)',
        '/(?:xmlrpc\.php|solr|autodiscover)/i' => 'سوءاستفاده از پروتکل‌های قدیمی و پورت‌های آسیب‌پذیر'
    ];

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->audit = new SecurityAuditService($db);
    }

    /**
     * Resolve authentic client IP address respecting Cloudflare and reverse proxy headers
     */
    public static function resolveClientIp(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',   // Cloudflare primary client IP
            'HTTP_TRUE_CLIENT_IP',     // Enterprise Cloudflare / Akamai
            'HTTP_X_REAL_IP',          // Nginx reverse proxy
            'HTTP_X_FORWARDED_FOR',    // Standard forward proxy chain
            'REMOTE_ADDR'              // Direct connection fallback
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $candidate = trim($ips[0]);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get Cloudflare Ray ID if request passes through Cloudflare
     */
    public static function getCloudflareRayId(): ?string {
        return !empty($_SERVER['HTTP_CF_RAY']) ? substr($_SERVER['HTTP_CF_RAY'], 0, 64) : null;
    }

    /**
     * Get Cloudflare two-letter country code
     */
    public static function getCloudflareCountry(): ?string {
        return !empty($_SERVER['HTTP_CF_IPCOUNTRY']) ? substr($_SERVER['HTTP_CF_IPCOUNTRY'], 0, 10) : null;
    }

    /**
     * Categorize action type based on requested URI
     */
    public static function categorizeAction(string $uri, string $method): string {
        $clean = strtolower($uri);

        if (str_contains($clean, 'login') || str_contains($clean, 'register') || str_contains($clean, 'logout') || str_contains($clean, 'reset_password')) {
            return 'auth';
        }
        if (str_contains($clean, '/admin/')) {
            return 'admin_action';
        }
        if (str_contains($clean, '/organization/')) {
            return 'organization_action';
        }
        if (str_contains($clean, 'cart.php') || str_contains($clean, 'add_to_cart')) {
            return 'cart';
        }
        if (str_contains($clean, 'checkout') || str_contains($clean, 'payment') || str_contains($clean, 'gateway')) {
            return 'checkout';
        }
        if (str_contains($clean, '/api/') || str_contains($clean, 'actions/')) {
            return 'api';
        }

        return 'browse';
    }

    /**
     * Sanitize and redact sensitive fields from parameters (passwords, tokens, credit cards)
     */
    public static function sanitizePayload(array $data): string {
        if (empty($data)) {
            return '';
        }

        $redactKeys = [
            'password', 'confirm_password', 'pass', 'token', 'csrf_token', 
            'secret', 'card_number', 'cvv', 'cvv2', 'pin', 'auth_token', 'api_key'
        ];

        $sanitized = [];
        foreach ($data as $k => $v) {
            $keyLower = strtolower((string)$k);
            $shouldRedact = false;
            foreach ($redactKeys as $rk) {
                if (str_contains($keyLower, $rk)) {
                    $shouldRedact = true;
                    break;
                }
            }

            if ($shouldRedact) {
                $sanitized[$k] = '[محرمانه - ردکت شده]';
            } elseif (is_array($v)) {
                $sanitized[$k] = '[Array: ' . count($v) . ' items]';
            } else {
                $sanitized[$k] = substr((string)$v, 0, 150);
            }
        }

        return json_encode($sanitized, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Inspect URI against scanner probe patterns
     */
    public static function checkScannerProbe(string $uri): ?string {
        foreach (self::$scannerPatterns as $pattern => $description) {
            if (preg_match($pattern, $uri)) {
                return $description;
            }
        }
        return null;
    }

    /**
     * Log an interaction into system_request_logs
     */
    public function logRequest(array $meta): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO system_request_logs 
                (ip_address, user_id, user_name, session_id, action_type, request_method, request_uri, 
                 payload_summary, cf_ray, cf_country, user_agent, response_code, is_suspicious, suspicion_reason, created_at)
                VALUES 
                (:ip, :uid, :uname, :sess, :action, :method, :uri, :payload, :ray, :country, :ua, :code, :susp, :reason, NOW())
            ");

            $stmt->execute([
                'ip' => $meta['ip_address'],
                'uid' => $meta['user_id'] ?? null,
                'uname' => $meta['user_name'] ?? null,
                'sess' => $meta['session_id'] ?? null,
                'action' => $meta['action_type'] ?? 'page_view',
                'method' => $meta['request_method'] ?? 'GET',
                'uri' => substr($meta['request_uri'] ?? '', 0, 500),
                'payload' => $meta['payload_summary'] ?? null,
                'ray' => $meta['cf_ray'] ?? null,
                'country' => $meta['cf_country'] ?? null,
                'ua' => substr($meta['user_agent'] ?? '', 0, 255),
                'code' => $meta['response_code'] ?? 200,
                'susp' => !empty($meta['is_suspicious']) ? 1 : 0,
                'reason' => $meta['suspicion_reason'] ?? null
            ]);

            return (int)$this->db->lastInsertId();
        } catch (Throwable $e) {
            error_log("TrafficMonitoringService log error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if IP is performing an L7 DDoS flood (Burst / Sustained flood)
     */
    public function checkDdosFlood(string $ip): array {
        try {
            // 1. Check 10-second burst window
            $stmt10 = $this->db->prepare("
                SELECT COUNT(*) FROM system_request_logs 
                WHERE ip_address = :ip AND created_at >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
            ");
            $stmt10->execute(['ip' => $ip]);
            $count10s = (int)$stmt10->fetchColumn();

            if ($count10s >= self::BURST_WINDOW_10S_THRESHOLD) {
                return [
                    'is_flood' => true,
                    'type' => 'burst_flood',
                    'count' => $count10s,
                    'window' => 10,
                    'threshold' => self::BURST_WINDOW_10S_THRESHOLD
                ];
            }

            // 2. Check 60-second sustained window
            $stmt60 = $this->db->prepare("
                SELECT COUNT(*) FROM system_request_logs 
                WHERE ip_address = :ip AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
            ");
            $stmt60->execute(['ip' => $ip]);
            $count60s = (int)$stmt60->fetchColumn();

            if ($count60s >= self::SUSTAINED_WINDOW_60S_THRESHOLD) {
                return [
                    'is_flood' => true,
                    'type' => 'sustained_flood',
                    'count' => $count60s,
                    'window' => 60,
                    'threshold' => self::SUSTAINED_WINDOW_60S_THRESHOLD
                ];
            }

            return ['is_flood' => false, 'count' => $count10s];
        } catch (Throwable $e) {
            return ['is_flood' => false, 'count' => 0];
        }
    }

    /**
     * Automatically restrict an IP for a specific duration with threat classification
     */
    public function autoRestrictIp(string $ip, int $durationMinutes, string $threatType, string $reason, ?string $cfRay = null): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO security_banned_ips (ip_address, reason, threat_type, cf_ray, banned_until, created_at)
                VALUES (:ip, :reason, :threat, :ray, DATE_ADD(NOW(), INTERVAL :duration MINUTE), NOW())
                ON DUPLICATE KEY UPDATE 
                    reason = VALUES(reason),
                    threat_type = VALUES(threat_type),
                    cf_ray = VALUES(cf_ray),
                    banned_until = DATE_ADD(NOW(), INTERVAL :duration_up MINUTE)
            ");
            $ok = $stmt->execute([
                'ip' => $ip,
                'reason' => $reason,
                'threat' => $threatType,
                'ray' => $cfRay,
                'duration' => $durationMinutes,
                'duration_up' => $durationMinutes
            ]);

            if ($ok) {
                $this->audit->logEvent(
                    'ip_banned', 
                    'critical', 
                    null, 
                    "محدودسازی خودکار IP: {$ip} به مدت {$durationMinutes} دقیقه | نوع تهدید: {$threatType} | علت: {$reason}"
                );
            }
            return $ok;
        } catch (Throwable $e) {
            error_log("autoRestrictIp error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get remaining ban seconds for an IP
     */
    public function getBanRemainingSeconds(string $ip): int {
        try {
            $stmt = $this->db->prepare("
                SELECT TIMESTAMPDIFF(SECOND, NOW(), banned_until) as sec 
                FROM security_banned_ips 
                WHERE ip_address = :ip AND banned_until > NOW() 
                LIMIT 1
            ");
            $stmt->execute(['ip' => $ip]);
            $sec = (int)$stmt->fetchColumn();
            return max(0, $sec);
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Get complete request history for a specific IP (Investigation Tool)
     */
    public function getRequestHistoryByIp(string $ip, int $limit = 100): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM system_request_logs 
                WHERE ip_address = :ip 
                ORDER BY id DESC 
                LIMIT " . (int)$limit
            );
            $stmt->execute(['ip' => $ip]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get complete request history for a specific User ID (Investigation Tool)
     */
    public function getRequestHistoryByUser(int $userId, int $limit = 100): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM system_request_logs 
                WHERE user_id = :uid 
                ORDER BY id DESC 
                LIMIT " . (int)$limit
            );
            $stmt->execute(['uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get recent interaction logs with optional filtering
     */
    public function getRecentInteractions(int $limit = 100, ?string $filter = null): array {
        try {
            $sql = "SELECT * FROM system_request_logs";
            $params = [];

            if ($filter === 'suspicious') {
                $sql .= " WHERE is_suspicious = 1";
            } elseif (!empty($filter) && $filter !== 'all') {
                $sql .= " WHERE action_type = :act";
                $params['act'] = $filter;
            }

            $sql .= " ORDER BY id DESC LIMIT " . (int)$limit;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get aggregate traffic and threat metrics
     */
    public function getTrafficMetrics(): array {
        try {
            $req24h = (int)$this->db->query("
                SELECT COUNT(*) FROM system_request_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")->fetchColumn();

            $uniqueIps24h = (int)$this->db->query("
                SELECT COUNT(DISTINCT ip_address) FROM system_request_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")->fetchColumn();

            $suspicious24h = (int)$this->db->query("
                SELECT COUNT(*) FROM system_request_logs 
                WHERE is_suspicious = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")->fetchColumn();

            $activeRestrictions = (int)$this->db->query("
                SELECT COUNT(*) FROM security_banned_ips 
                WHERE banned_until > NOW()
            ")->fetchColumn();

            return [
                'requests_24h' => $req24h,
                'unique_ips_24h' => $uniqueIps24h,
                'suspicious_24h' => $suspicious24h,
                'active_restrictions' => $activeRestrictions
            ];
        } catch (Throwable $e) {
            return [
                'requests_24h' => 0,
                'unique_ips_24h' => 0,
                'suspicious_24h' => 0,
                'active_restrictions' => 0
            ];
        }
    }

    /**
     * Master Inspection & Logging Hook - executed on every HTTP request
     */
    public static function inspectAndLog(PDO $db): bool {
        // Skip CLI execution from blocking or logging
        if (php_sapi_name() === 'cli') {
            return true;
        }

        $service = new self($db);
        $clientIp = self::resolveClientIp();
        $cfRay = self::getCloudflareRayId();
        $cfCountry = self::getCloudflareCountry();
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $sessionId = session_id() ?: null;
        $userId = $_SESSION['user_id'] ?? null;
        $userName = $_SESSION['user_name'] ?? ($_SESSION['name'] ?? null);

        // 1. Check if IP is already restricted
        $remainingSec = $service->getBanRemainingSeconds($clientIp);
        if ($remainingSec > 0) {
            self::renderRestrictionShield($clientIp, $remainingSec, $cfRay, 'دسترسی IP شما به دلیل نقض قوانین یا ارسال درخواست‌های سیل‌آسا مسدود گردیده است.');
            return false;
        }

        // 2. Check for malicious vulnerability scanner probes
        $probeReason = self::checkScannerProbe($requestUri);
        $isSuspicious = !empty($probeReason);
        $actionType = $isSuspicious ? 'suspicious_probe' : self::categorizeAction($requestUri, $requestMethod);

        // Prepare sanitized payload summary
        $payloadData = array_merge($_GET, $_POST);
        $sanitizedPayload = self::sanitizePayload($payloadData);

        // 3. Log request to system_request_logs
        $service->logRequest([
            'ip_address' => $clientIp,
            'user_id' => $userId,
            'user_name' => $userName,
            'session_id' => $sessionId,
            'action_type' => $actionType,
            'request_method' => $requestMethod,
            'request_uri' => $requestUri,
            'payload_summary' => $sanitizedPayload,
            'cf_ray' => $cfRay,
            'cf_country' => $cfCountry,
            'user_agent' => $userAgent,
            'response_code' => 200,
            'is_suspicious' => $isSuspicious,
            'suspicion_reason' => $probeReason
        ]);

        // If caught as a malicious probe, immediately restrict IP for 60 minutes
        if ($isSuspicious) {
            $service->autoRestrictIp($clientIp, 60, 'scanner_probe', $probeReason, $cfRay);
            self::renderRestrictionShield($clientIp, 3600, $cfRay, $probeReason);
            return false;
        }

        // 4. Check for L7 DDoS flood (Burst / Sustained flood)
        $floodCheck = $service->checkDdosFlood($clientIp);
        if ($floodCheck['is_flood']) {
            $banDuration = ($floodCheck['type'] === 'sustained_flood') ? 30 : 15;
            $reason = "شناسایی ترافیک سیل‌آسای لایه ۷ (DDoS Flood: {$floodCheck['count']} درخواست در {$floodCheck['window']} ثانیه)";
            $service->autoRestrictIp($clientIp, $banDuration, 'ddos_flood', $reason, $cfRay);
            self::renderRestrictionShield($clientIp, $banDuration * 60, $cfRay, $reason);
            return false;
        }

        return true;
    }

    /**
     * Render Persian Cloudflare-style defense shield screen
     */
    public static function renderRestrictionShield(string $ip, int $remainingSeconds, ?string $cfRay, string $reason): void {
        if (!headers_sent()) {
            http_response_code(429); // Too Many Requests / Rate Limited
            header('Content-Type: text/html; charset=utf-8');
            header('Retry-After: ' . $remainingSeconds);
        }

        $minutes = ceil($remainingSeconds / 60);
        $rayDisplay = htmlspecialchars($cfRay ?: 'ASENA-' . strtoupper(substr(md5($ip . date('YmdH')), 0, 16)));
        $safeReason = htmlspecialchars($reason);

        echo <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8">
    <title>محدودیت</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #0b0f19; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .shield-card { background: rgba(17, 24, 39, 0.85); border: 1px solid #1f2937; border-radius: 24px; padding: 40px; max-width: 520px; width: 100%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.7); backdrop-filter: blur(20px); }
        .shield-icon { width: 72px; height: 72px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; border-radius: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 36px; margin-bottom: 24px; border: 1px solid rgba(245, 158, 11, 0.3); }
        h1 { font-size: 22px; font-weight: 800; margin: 0 0 10px; color: #ffffff; }
        .reason-box { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 12px 16px; border-radius: 14px; font-size: 13px; line-height: 1.6; margin: 20px 0; text-align: right; }
        .meta-strip { background: #0b0f19; border: 1px solid #1f2937; border-radius: 14px; padding: 14px; margin-bottom: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px; text-align: right; }
        .meta-label { color: #94a3b8; display: block; font-size: 11px; margin-bottom: 2px; }
        .meta-val { color: #ffffff; font-family: monospace; font-weight: bold; }
        .countdown { font-size: 28px; font-weight: 900; color: #38bdf8; margin: 10px 0; font-family: monospace; }
        .notice { font-size: 11px; color: #64748b; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="shield-card">
        <div class="shield-icon">🛡️</div>
        <h1>محدودیت موقت دسترسی (DDoS Shield)</h1>
        <p style="color: #94a3b8; font-size: 13px; margin: 0;">سیستم امنیتی آسنا ترافیک غیرعادی یا درخواست‌های مکرر از سوی شما ثبت کرده است.</p>

        <div class="reason-box">
            <strong>علت محدودیت:</strong> {$safeReason}
        </div>

        <div class="countdown">
            {$minutes} <span style="font-size: 14px; font-weight: normal; color: #94a3b8;">دقیقه تا رفع محدودیت</span>
        </div>

        <div class="meta-strip">
            <div>
                <span class="meta-label">آدرس IP شما:</span>
                <span class="meta-val">{$ip}</span>
            </div>
            <div>
                <span class="meta-label">Cloudflare Ray ID:</span>
                <span class="meta-val">{$rayDisplay}</span>
            </div>
        </div>

        <p class="notice">
            پس از پایان زمان مشخص‌شده، دسترسی شما به صورت خودکار برقرار خواهد شد. در صورت تکرار رفتارهای ناهنجار، مدت زمان مسدودی به صورت تصاعدی افزایش خواهد یافت.
        </p>
    </div>
</body>
</html>
HTML;
        exit;
    }
}
