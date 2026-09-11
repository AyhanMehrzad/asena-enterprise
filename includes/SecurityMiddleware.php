<?php
/**
 * ASENA Enterprise - Security Middleware & Zero-Trust Layer
 * Version: 1.0.0
 */

class SecurityMiddleware {

    /**
     * Emit robust enterprise HTTP security headers
     */
    public static function applyHeaders(): void {
        if (headers_sent()) {
            return;
        }

        // 1. Content Security Policy (CSP)
        // Accommodates Tailwind CDN, Google Fonts, Material Symbols, and inline app scripts
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'"
        ];
        header("Content-Security-Policy: " . implode('; ', $csp));

        // 2. Strict-Transport-Security (HSTS - 1 Year)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }

        // 3. X-Content-Type-Options (MIME sniffing prevention)
        header("X-Content-Type-Options: nosniff");

        // 4. X-Frame-Options (Clickjacking defense)
        header("X-Frame-Options: SAMEORIGIN");

        // 5. Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // 6. Permissions Policy (Disables unused intrusive hardware features)
        header("Permissions-Policy: camera=(), microphone=(), geolocation=(self)");

        // 7. X-XSS-Protection (Legacy browser protection)
        header("X-XSS-Protection: 1; mode=block");
    }

    /**
     * Start session with strict enterprise security flags
     */
    public static function startSecureSession(): void {
        if (php_sapi_name() === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        if ($isHttps) {
            ini_set('session.cookie_secure', '1');
        }

        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        @session_start();
    }

    /**
     * Session Hijacking & Fixation Defense
     */
    public static function secureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            self::startSecureSession();
        }

        // Session fingerprint verification
        $currentFingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        if (isset($_SESSION['_security_fingerprint'])) {
            if ($_SESSION['_security_fingerprint'] !== $currentFingerprint) {
                // Potential session hijacking detected: invalidate session
                session_unset();
                session_destroy();
                self::startSecureSession();
            }
        } else {
            $_SESSION['_security_fingerprint'] = $currentFingerprint;
        }

        // Periodic session ID regeneration (every 30 minutes)
        if (!isset($_SESSION['_last_regeneration'])) {
            $_SESSION['_last_regeneration'] = time();
        } elseif (time() - $_SESSION['_last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = time();
        }
    }

    /**
     * Invalidate all active PHP session files for a specific user, optionally keeping one session ID active.
     * Essential for revocation upon password change, account takeover response, or user lockout.
     */
    public static function invalidateOtherUserSessions(int $userId, ?string $keepSessionId = null): int {
        if ($userId <= 0) {
            return 0;
        }

        $revokedCount = 0;
        $savePath = session_save_path() ?: sys_get_temp_dir();
        if (empty($savePath) || !is_dir($savePath)) {
            return 0;
        }

        $keepFile = $keepSessionId ? ('sess_' . $keepSessionId) : (session_id() ? ('sess_' . session_id()) : '');
        $pattern = rtrim($savePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_*';
        $files = glob($pattern);

        if (is_array($files)) {
            $userPatterns = [
                'user_id|i:' . $userId . ';',
                '"user_id";i:' . $userId . ';',
                'user_id|s:' . strlen((string)$userId) . ':"' . $userId . '";',
                '"user_id";s:' . strlen((string)$userId) . ':"' . $userId . '";'
            ];

            foreach ($files as $file) {
                if (!is_file($file) || ($keepFile && basename($file) === $keepFile)) {
                    continue;
                }

                $content = @file_get_contents($file);
                if ($content === false) {
                    continue;
                }

                foreach ($userPatterns as $p) {
                    if (strpos($content, $p) !== false) {
                        if (@unlink($file)) {
                            $revokedCount++;
                        }
                        break;
                    }
                }
            }
        }

        return $revokedCount;
    }

    /**
     * Deep MIME-Type Validation for File Uploads (Prescriptions, documents)
     * Prevents malicious executable uploads disguised as images/PDFs
     */
    public static function validateUploadedFile(array $file, array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], int $maxSizeBytes = 5242880): array {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'فایل آپلود شده معتبر نیست یا ارسال نشده است.'];
        }

        if ($file['size'] > $maxSizeBytes) {
            return ['valid' => false, 'error' => 'حجم فایل بیش از حد مجاز است (حداکثر ۵ مگابایت).'];
        }

        // Verify genuine binary MIME type using PHP Fileinfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimes, true)) {
            return ['valid' => false, 'error' => "فرمت فایل ({$mimeType}) غیرمجاز است. تنها فایل‌های JPG، PNG و PDF پذیرفته می‌شوند."];
        }

        // Anti-Polyglot & embedded script detection in file content
        $contentSample = file_get_contents($file['tmp_name'], false, null, 0, 4096);
        if ($contentSample !== false && preg_match('/<\?(?:php|=)|<script\b/i', $contentSample)) {
            return ['valid' => false, 'error' => 'محتوای فایل حاوی کدهای غیرمجاز یا اسکریپت اجرایی می‌باشد.'];
        }

        // Sanitize and create safe storage filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = bin2hex(random_bytes(16)) . '_' . time() . '.' . strtolower($ext);

        return [
            'valid' => true,
            'mime' => $mimeType,
            'safe_filename' => $safeName
        ];
    }

    /**
     * Generate or retrieve session CSRF token
     */
    public static function generateCsrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            self::startSecureSession();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate submitted CSRF token using constant-time comparison
     */
    public static function validateCsrfToken(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            self::startSecureSession();
        }
        $expected = $_SESSION['csrf_token'] ?? '';
        if (empty($expected) || empty($token) || !hash_equals($expected, trim($token))) {
            return false;
        }
        return true;
    }
}
