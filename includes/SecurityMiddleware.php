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
     * Session Hijacking & Fixation Defense
     */
    public static function secureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            session_start();
        }

        // Session fingerprint verification
        $currentFingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        if (isset($_SESSION['_security_fingerprint'])) {
            if ($_SESSION['_security_fingerprint'] !== $currentFingerprint) {
                // Potential session hijacking detected: invalidate session
                session_unset();
                session_destroy();
                session_start();
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
}
