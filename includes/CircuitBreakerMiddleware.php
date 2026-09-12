<?php
/**
 * ASENA Enterprise - CircuitBreakerMiddleware
 * 
 * Protects server threads and database connection pools from starvation.
 * If any client/IP triggers 3 or more bad requests, WAF violations, or uncaught
 * exceptions within a 60-second window, the circuit breaker trips for 30 seconds.
 * 
 * Subsequent requests from that client are fast-dropped in < 0.5ms with HTTP 429
 * without querying the database, without locking session files, and without
 * consuming Apache MPM worker threads.
 * 
 * Version: 1.0.0
 */

class CircuitBreakerMiddleware
{
    private const WINDOW_SECONDS    = 60;
    private const TRIP_DURATION     = 30;
    private const FAILURE_THRESHOLD = 3;

    private static ?string $storageDir = null;

    private static function getStorageDir(): string
    {
        if (self::$storageDir === null) {
            self::$storageDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'asena_circuit_breaker';
            if (!is_dir(self::$storageDir)) {
                @mkdir(self::$storageDir, 0755, true);
            }
        }
        return self::$storageDir;
    }

    /**
     * Resolve the client IP address (Cloudflare & proxy aware)
     */
    public static function resolveClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $ips = explode(',', $_SERVER[$h]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '127.0.0.1';
    }

    private static function getFilePath(string $ip): string
    {
        return self::getStorageDir() . DIRECTORY_SEPARATOR . 'cb_' . md5($ip) . '.json';
    }

    /**
     * Inspect client state at the very beginning of the HTTP request.
     * If tripped, fast-drops the request immediately.
     */
    public static function inspect(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        $ip = self::resolveClientIp();
        $file = self::getFilePath($ip);

        if (!file_exists($file)) {
            return;
        }

        $data = @json_decode(@file_get_contents($file), true);
        if (!is_array($data)) {
            return;
        }

        $now = time();
        $trippedUntil = (int)($data['tripped_until'] ?? 0);

        if ($trippedUntil > $now) {
            $remaining = $trippedUntil - $now;
            self::fastDrop($ip, $remaining);
        }
    }

    /**
     * Record a bad request, unhandled exception, or WAF violation.
     */
    public static function recordFailure(?string $ip = null, int $statusCode = 400, ?string $reason = null): void
    {
        $ip = $ip ?: self::resolveClientIp();
        $file = self::getFilePath($ip);
        $now = time();

        $data = file_exists($file) ? @json_decode(@file_get_contents($file), true) : null;
        if (!is_array($data)) {
            $data = [
                'ip'            => $ip,
                'failures'      => [],
                'tripped_until' => 0,
            ];
        }

        // Clean failures older than sliding window
        $cutoff = $now - self::WINDOW_SECONDS;
        $data['failures'] = array_values(array_filter(
            $data['failures'] ?? [],
            fn($ts) => $ts >= $cutoff
        ));

        // Add current failure
        $data['failures'][] = $now;
        $data['last_reason'] = $reason ?: "HTTP_{$statusCode}";

        // If threshold reached, trip circuit
        if (count($data['failures']) >= self::FAILURE_THRESHOLD) {
            $data['tripped_until'] = $now + self::TRIP_DURATION;
        }

        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Clear failure count on valid, successful authenticated interactions
     */
    public static function recordSuccess(?string $ip = null): void
    {
        $ip = $ip ?: self::resolveClientIp();
        $file = self::getFilePath($ip);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Fast-drop response that takes < 0.5ms with zero database/session overhead
     */
    private static function fastDrop(string $ip, int $retryAfterSeconds): void
    {
        http_response_code(429);
        header('Retry-After: ' . $retryAfterSeconds);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Asena-Protection: CircuitBreaker-Tripped');

        // Close any session lock if started
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        // Return ultra-lightweight, beautiful shield page
        echo <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آسنا | محدودیت موقت به دلیل درخواست‌های نامعتبر</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
        body { background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; text-align: center; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 1.25rem; max-width: 480px; width: 100%; padding: 2.5rem 2rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h1 { font-size: 1.35rem; font-weight: 800; color: #f59e0b; margin-bottom: 0.75rem; }
        p { font-size: 0.95rem; line-height: 1.6; color: #94a3b8; margin-bottom: 1.5rem; }
        .badge { display: inline-block; background: #334155; color: #38bdf8; padding: 0.4rem 1rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 700; margin-bottom: 1rem; }
        .timer { font-size: 1.5rem; font-weight: 800; color: #38bdf8; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🛡️</div>
        <span class="badge">سیستم عایق‌سازی درخواست‌های ناامن (Circuit Breaker)</span>
        <h1>توقف موقت به دلیل خطاهای مکرر درخواست</h1>
        <p>به منظور حفظ پایداری مداوم سامانه و محافظت از منابع سرور، دسترسی شما برای مدت کوتاهی متوقف گردید.</p>
        <div class="timer">لطفاً <span id="sec">{$retryAfterSeconds}</span> ثانیه دیگر دوباره تلاش نمایید...</div>
    </div>
    <script>
        let s = {$retryAfterSeconds};
        const el = document.getElementById('sec');
        const itv = setInterval(() => {
            s--;
            if (el) el.innerText = s;
            if (s <= 0) {
                clearInterval(itv);
                window.location.reload();
            }
        }, 1000);
    </script>
</body>
</html>
HTML;
        exit;
    }
}
