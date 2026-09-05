<?php
/**
 * ASENA Enterprise - Zero-Trust Web Application Firewall (WAF)
 * Intercepts SQL Injection, XSS, Directory Traversal, and Automated Attack Payloads.
 * Version: 1.0.0
 */

require_once __DIR__ . '/SecurityAuditService.php';

class WafMiddleware {

    private static array $sqliPatterns = [
        '/\bunion\s+(?:all\s+)?select\b/i',
        '/\binformation_schema\b/i',
        '/\bsleep\s*\(\s*\d+\s*\)/i',
        '/\bbenchmark\s*\(\s*\d+\s*,/i',
        '/\bload_file\s*\(/i',
        '/\binto\s+(?:dumpfile|outfile)\b/i',
        '/\bor\s+[\'"]?1[\'"]?\s*=\s*[\'"]?1/i',
        '/[\'"]\s*or\s*[\'"]?[a-z0-9]+[\'"]?\s*=\s*[\'"]?[a-z0-9]+/i',
        '/;\s*(?:drop|truncate|alter)\s+table\b/i'
    ];

    private static array $xssPatterns = [
        '/<script\b[^>]*>/i',
        '/<\/script>/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/\bon(?:error|load|click|mouseover|submit|focus|blur)\s*=\s*[\'"]/i',
        '/<iframe\b[^>]*>/i',
        '/<object\b[^>]*>/i',
        '/<embed\b[^>]*>/i'
    ];

    private static array $traversalPatterns = [
        '/(?:\.\.[\\\/])+/i',
        '/(?:%2e%2e[%2f%5c])+/i',
        '/\x00|\%00/'
    ];

    /**
     * Inspect all incoming request superglobals
     */
    public static function inspect(?PDO $pdo = null): bool {
        $audit = new SecurityAuditService($pdo);

        // 1. IP Ban check
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($audit->isIpBanned($clientIp)) {
            self::blockRequest('دسترسی شما به دلیل نقض مکرر قوانین امنیتی مسدود شده است.', 403);
            return false;
        }

        // 2. Inspect Request URI for Path Traversal or Null Bytes
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        foreach (self::$traversalPatterns as $p) {
            if (preg_match($p, $requestUri)) {
                $audit->logEvent('waf_blocked', 'critical', $_SESSION['user_id'] ?? null, "Path Traversal in URI: {$requestUri}");
                self::autoBanIfAggressive($audit, $clientIp);
                self::blockRequest('درخواست حاوی کاراکترهای پیمایش غیرمجاز مسیر مسدود شد.', 403);
                return false;
            }
        }

        // 3. Inspect GET Parameters
        if (!self::inspectArray($_GET, 'GET', $audit, $clientIp)) {
            return false;
        }

        // 4. Inspect POST Parameters (excluding raw passwords)
        if (!empty($_POST)) {
            $filteredPost = $_POST;
            unset($filteredPost['password'], $filteredPost['confirm_password']);
            if (!self::inspectArray($filteredPost, 'POST', $audit, $clientIp)) {
                return false;
            }
        }

        // 5. Inspect Cookies
        if (!empty($_COOKIE)) {
            $filteredCookies = $_COOKIE;
            unset($filteredCookies[session_name()]);
            if (!self::inspectArray($filteredCookies, 'COOKIE', $audit, $clientIp)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursive payload inspector
     */
    private static function inspectArray(array $items, string $source, SecurityAuditService $audit, string $clientIp): bool {
        foreach ($items as $key => $val) {
            if (is_array($val)) {
                if (!self::inspectArray($val, "{$source}[{$key}]", $audit, $clientIp)) {
                    return false;
                }
                continue;
            }

            if (!is_string($val) || empty($val)) {
                continue;
            }

            // Check SQL Injection
            foreach (self::$sqliPatterns as $pat) {
                if (preg_match($pat, $val)) {
                    $audit->logEvent('waf_blocked', 'critical', $_SESSION['user_id'] ?? null, "WAF SQLi Blocked in {$source} (Key: {$key})", ['payload' => substr($val, 0, 100)]);
                    self::autoBanIfAggressive($audit, $clientIp);
                    self::blockRequest('ترافیک مشکوک به تزریق SQL شناسایی و مسدود گردید.', 403);
                    return false;
                }
            }

            // Check XSS
            foreach (self::$xssPatterns as $pat) {
                if (preg_match($pat, $val)) {
                    $audit->logEvent('waf_blocked', 'critical', $_SESSION['user_id'] ?? null, "WAF XSS Blocked in {$source} (Key: {$key})", ['payload' => substr($val, 0, 100)]);
                    self::autoBanIfAggressive($audit, $clientIp);
                    self::blockRequest('ترافیک مشکوک به اسکریپت‌نویسی متقاطع (XSS) مسدود گردید.', 403);
                    return false;
                }
            }

            // Check Path Traversal
            foreach (self::$traversalPatterns as $pat) {
                if (preg_match($pat, $val)) {
                    $audit->logEvent('waf_blocked', 'critical', $_SESSION['user_id'] ?? null, "WAF Path Traversal in {$source} (Key: {$key})", ['payload' => substr($val, 0, 100)]);
                    self::autoBanIfAggressive($audit, $clientIp);
                    self::blockRequest('کاراکترهای نامعتبر مسیر شناسایی و مسدود گردید.', 403);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Automatically ban IP if 3 or more attacks occur in a 10-minute window
     */
    private static function autoBanIfAggressive(SecurityAuditService $audit, string $ip): void {
        try {
            $pdo = $GLOBALS['pdo'] ?? null;
            if ($pdo) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM security_audit_logs 
                    WHERE ip_address = ? AND event_type = 'waf_blocked' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                ");
                $stmt->execute([$ip]);
                $count = (int)$stmt->fetchColumn();

                if ($count >= 3) {
                    $audit->banIp($ip, 60, 'حمله مکرر و مشکوک به فایروال برنامه (WAF Auto-Ban)');
                }
            }
        } catch (Throwable $e) {}
    }

    /**
     * Terminate and render Persian security denial response
     */
    private static function blockRequest(string $reason, int $httpCode = 403): void {
        if (!headers_sent()) {
            http_response_code($httpCode);
            header('Content-Type: text/html; charset=utf-8');
        }

        echo <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8">
    <title>دسترسی مسدود شد | ASENA Shield</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: rgba(30, 41, 59, 0.8); border: 1px solid #334155; border-radius: 24px; padding: 40px; max-width: 480px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); backdrop-filter: blur(16px); }
        .icon { width: 64px; height: 64px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.3); }
        h1 { font-size: 20px; font-weight: 800; margin: 0 0 10px; color: #ffffff; }
        p { font-size: 13px; color: #94a3b8; line-height: 1.6; margin: 0 0 24px; }
        .code { background: #0f172a; padding: 8px 12px; border-radius: 10px; font-family: monospace; font-size: 11px; color: #f43f5e; margin-bottom: 24px; display: inline-block; direction: ltr; }
        .btn { display: inline-block; background: #0284c7; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 14px; font-size: 13px; font-weight: 700; transition: all 0.2s; }
        .btn:hover { background: #0369a1; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🛡️</div>
        <h1>درخواست غیرمجاز شناسایی شد</h1>
        <p>{$reason}</p>
        <div class="code">SECURITY_BLOCK_ID: 403_WAF_PROTECTED</div>
        <div>
            <a href="index.php" class="btn">بازگشت به صفحه اصلی</a>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }
}
