<?php
/**
 * ASENA Enterprise - GlobalExceptionBoundary
 * 
 * Central fail-safe error and exception boundary for the entire application.
 * Ensures that unexpected fatal errors, unhandled PDOExceptions, and bad requests:
 * 1. Cleanly rollback any active database transactions (preventing lock holding)
 * 2. Instantly close lingering session file locks (preventing user/server stalls)
 * 3. Trip the Circuit Breaker for repeat bad actors
 * 4. Log detailed diagnostics to security audit logs and disk
 * 5. Return sanitized, elegant UI/JSON responses with an incident reference ID
 * 
 * Version: 1.0.0
 */

require_once __DIR__ . '/CircuitBreakerMiddleware.php';

class GlobalExceptionBoundary
{
    private static ?PDO $pdo = null;
    private static bool $initialized = false;

    public static function init(?PDO $pdo = null): void
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;
        self::$pdo = $pdo;

        // Catch uncaught exceptions
        set_exception_handler([self::class, 'handleException']);

        // Convert standard PHP warnings/notices into ErrorExceptions for strict containment
        set_error_handler([self::class, 'handleError'], E_ALL & ~E_NOTICE & ~E_DEPRECATED);

        // Catch fatal fatal runtime shutdowns (out of memory, parse fatal, etc.)
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Top-level Exception Handler
     */
    public static function handleException(Throwable $e): void
    {
        $incidentId = 'ASENA-ERR-' . strtoupper(bin2hex(random_bytes(4)));

        // 1. Rollback any pending database transactions to release row locks
        try {
            if (self::$pdo && self::$pdo->inTransaction()) {
                self::$pdo->rollBack();
            }
        } catch (Throwable $dbEx) {}

        // 2. Release session file lock immediately
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        // 3. Increment failure count on circuit breaker
        CircuitBreakerMiddleware::recordFailure(null, 500, get_class($e));

        // 4. Log full diagnostic details securely
        self::logDiagnostic($incidentId, $e);

        // 5. Render sanitized user response
        self::renderErrorResponse($incidentId, $e);
    }

    /**
     * Convert PHP runtime errors to ErrorException
     */
    public static function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }
        throw new ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Handle fatal shutdown events
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $incidentId = 'ASENA-FATAL-' . strtoupper(bin2hex(random_bytes(4)));

            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
            }

            CircuitBreakerMiddleware::recordFailure(null, 500, 'PHP_FATAL');
            self::renderErrorResponse($incidentId, null, $error['message']);
        }
    }

    private static function logDiagnostic(string $incidentId, ?Throwable $e, ?string $rawMessage = null): void
    {
        $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . DIRECTORY_SEPARATOR . 'exceptions.log';
        $entry = sprintf(
            "[%s] [%s] %s in %s:%d | IP: %s | URI: %s\nStack:\n%s\n---\n",
            date('Y-m-d H:i:s'),
            $incidentId,
            $e ? $e->getMessage() : ($rawMessage ?: 'Fatal error'),
            $e ? $e->getFile() : 'unknown',
            $e ? $e->getLine() : 0,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['REQUEST_URI'] ?? '',
            $e ? $e->getTraceAsString() : ''
        );

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    private static function renderErrorResponse(string $incidentId, ?Throwable $e, ?string $rawMessage = null): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: ' . (self::isJsonRequest() ? 'application/json; charset=utf-8' : 'text/html; charset=utf-8'));
        }

        if (self::isJsonRequest()) {
            echo json_encode([
                'success'      => false,
                'error'        => 'خطایی در پردازش رخ داده است. لطفاً مجدداً تلاش نمایید.',
                'incident_ref' => $incidentId,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Beautiful, resilient HTML fallback view
        echo <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آسنا | خطای موقت در پردازش</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
        body { background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; text-align: center; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 1.25rem; max-width: 520px; width: 100%; padding: 2.5rem 2rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h1 { font-size: 1.4rem; font-weight: 800; color: #f87171; margin-bottom: 0.75rem; }
        p { font-size: 0.95rem; line-height: 1.6; color: #94a3b8; margin-bottom: 1.5rem; }
        .ref-box { background: #0f172a; border: 1px dashed #475569; padding: 0.75rem 1rem; border-radius: 0.75rem; font-family: monospace; font-size: 0.85rem; color: #38bdf8; margin-bottom: 1.5rem; }
        .btn { display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 700; font-size: 0.9rem; transition: background 0.2s; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⚙️</div>
        <h1>خطای غیرمنتظره در پردازش اطلاعات</h1>
        <p>سیستم محافظت یکپارچه آسنا از بروز اختلال در پایگاه‌داده جلوگیری نمود. درخواست شما به صورت ایمن مهار شد و پایداری سایر بخش‌های سامانه برقرار است.</p>
        <div class="ref-box">شناسه پیگیری پشتیبانی: {$incidentId}</div>
        <a href="index.php" class="btn">بازگشت به صفحه اصلی</a>
    </div>
</body>
</html>
HTML;
        exit;
    }

    private static function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return (str_contains($accept, 'application/json') || strtolower($xReq) === 'xmlhttprequest');
    }
}
