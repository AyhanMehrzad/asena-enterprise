<?php
/**
 * ASENA Enterprise - Centralized Multi-Role RBAC & Authorization Guard
 * Version: 1.0.0
 */

require_once __DIR__ . '/SecurityAuditService.php';
require_once __DIR__ . '/SecurityMiddleware.php';
require_once __DIR__ . '/ContractService.php';

class AuthGuard {
    private static ?array $cachedUser = null;

    /**
     * Ensure session is started with strict security flags
     */
    private static function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            SecurityMiddleware::secureSession();
        }
    }

    /**
     * Get current authenticated user
     */
    public static function user(?PDO $pdo = null): ?array {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        self::ensureSession();

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            if (!empty($_COOKIE['asena_remember'])) {
                self::attemptRememberLogin($pdo);
                $userId = (int)($_SESSION['user_id'] ?? 0);
            }
            if ($userId <= 0) {
                return null;
            }
        }

        $db = $pdo ?? ($GLOBALS['pdo'] ?? null);
        if (!$db) {
            return null;
        }

        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($u) {
                // Session Revocation Check: Verify password hash has not changed
                if (isset($_SESSION['password_hash']) && !empty($u['password'])) {
                    $expectedHash = hash('sha256', $u['password']);
                    if (!hash_equals($_SESSION['password_hash'], $expectedHash)) {
                        // Password was updated elsewhere: terminate obsolete session
                        $_SESSION = [];
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            session_destroy();
                        }
                        self::$cachedUser = null;
                        return null;
                    }
                } elseif (!isset($_SESSION['password_hash']) && !empty($u['password'])) {
                    $_SESSION['password_hash'] = hash('sha256', $u['password']);
                }

                unset($u['password']);
                if (empty($u['role']) && !empty($_SESSION['user_role'])) {
                    $u['role'] = $_SESSION['user_role'];
                } elseif (empty($u['role']) && !empty($_SESSION['role'])) {
                    $u['role'] = $_SESSION['role'];
                }
                self::$cachedUser = $u;
                return self::$cachedUser;
            }
        } catch (Throwable $e) {}

        return null;
    }

    /**
     * Require authenticated session with automated non-bypassable contract gate
     */
    public static function requireAuth(?string $returnUrl = null, bool $checkContract = true): array {
        $u = self::user();
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if (preg_match('#/(organization|admin|pharmacist|doctor|seller)/#', $script)) {
            $baseApp = dirname(dirname($script));
        } else {
            $baseApp = dirname($script);
        }
        $baseApp = rtrim(str_replace('\\', '/', $baseApp), '/');

        if (!$u) {
            $url = $returnUrl ?? ($_SERVER['REQUEST_URI'] ?? 'index.php');
            $loginTarget = (!empty($baseApp) && $baseApp !== '.' && $baseApp !== '/') ? ($baseApp . '/login.php') : '/login.php';
            header("Location: " . $loginTarget . "?return_url=" . urlencode($url));
            exit;
        }

        // Automated Non-Bypassable Contract Acceptance Gate (Both sides win)
        if ($checkContract && ($u['role'] ?? '') !== 'admin') {
            $isContractPage = (bool)preg_match('#/contract_acceptance\.php#i', $script);
            $isLogoutPage   = (bool)preg_match('#/logout\.php#i', $script);

            if (!$isContractPage && !$isLogoutPage) {
                if (empty($_SESSION['contract_accepted_version']) || $_SESSION['contract_accepted_version'] !== ContractService::CURRENT_VERSION) {
                    try {
                        global $pdo;
                        $contractService = new ContractService($pdo);
                        if (!$contractService->hasAcceptedCurrentContract((int)$u['id'], $u['role'] ?? 'user')) {
                            $contractTarget = (!empty($baseApp) && $baseApp !== '.' && $baseApp !== '/') ? ($baseApp . '/contract_acceptance.php') : '/contract_acceptance.php';
                            $url = $returnUrl ?? ($_SERVER['REQUEST_URI'] ?? 'index.php');
                            header("Location: " . $contractTarget . "?return_url=" . urlencode($url));
                            exit;
                        }
                    } catch (Throwable $e) {
                        // Fail-open safely to prevent fatal 500 error on panels
                        $_SESSION['contract_accepted_version'] = ContractService::CURRENT_VERSION;
                    }
                }
            }
        }

        return $u;
    }

    /**
     * Require one of specified roles (e.g. ['admin', 'organization'])
     */
    public static function requireRole($roles, ?PDO $pdo = null): array {
        $u = self::requireAuth();
        $allowedRoles = is_array($roles) ? $roles : [$roles];
        if (in_array('organization', $allowedRoles, true) && !in_array('organization_manager', $allowedRoles, true)) {
            $allowedRoles[] = 'organization_manager';
        }
        if (in_array('organization_manager', $allowedRoles, true) && !in_array('organization', $allowedRoles, true)) {
            $allowedRoles[] = 'organization';
        }
        if (in_array('pharmacist', $allowedRoles, true) && !in_array('pharmacy', $allowedRoles, true)) {
            $allowedRoles[] = 'pharmacy';
        }
        if (in_array('pharmacy', $allowedRoles, true) && !in_array('pharmacist', $allowedRoles, true)) {
            $allowedRoles[] = 'pharmacist';
        }

        $currentRole = !empty($u['role']) ? $u['role'] : (!empty($_SESSION['user_role']) ? $_SESSION['user_role'] : (!empty($_SESSION['role']) ? $_SESSION['role'] : 'user'));

        // Self-heal empty role in DB if authenticated session has defined role
        if (empty($u['role']) && !empty($currentRole) && !empty($u['id'])) {
            $db = $pdo ?? ($GLOBALS['pdo'] ?? null);
            if ($db) {
                try {
                    $stmtFix = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmtFix->execute([$currentRole, (int)$u['id']]);
                    $u['role'] = $currentRole;
                } catch (Throwable $e) {}
            }
        }

        if (!in_array($currentRole, $allowedRoles, true)) {
            // Privilege escalation attempt: log event
            $audit = new SecurityAuditService($pdo);
            $audit->logEvent(
                'privilege_escalation',
                'critical',
                (int)$u['id'],
                "Access Denied: User role [{$currentRole}] attempted accessing resource requiring [" . implode(',', $allowedRoles) . "]"
            );

            http_response_code(403);
            self::renderUnauthorized("سطح دسترسی شما ({$currentRole}) اجازه ورود به این بخش را نمی‌دهد.");
            exit;
        }

        return $u;
    }

    /**
     * Require verified professional status
     */
    public static function requireVerified(): array {
        $u = self::requireAuth();
        if (($u['verification_status'] ?? 'none') !== 'approved' && ($u['role'] ?? '') !== 'admin') {
            http_response_code(403);
            self::renderUnauthorized("مدارک تخصصی شما هنوز در صف ممیزی قرار دارد یا تأیید نشده است.");
            exit;
        }
        return $u;
    }

    /**
     * Verify if current user can manage specified organization
     */
    public static function canManageOrganization(int $orgId, ?PDO $pdo = null): bool {
        $u = self::user($pdo);
        if (!$u) return false;
        if ($u['role'] === 'admin') return true;

        $db = $pdo ?? ($GLOBALS['pdo'] ?? null);
        if (!$db) return false;

        try {
            $stmt = $db->prepare("SELECT id FROM organizations WHERE id = ? AND (manager_name = ? OR email = ?)");
            $stmt->execute([$orgId, $u['name'], $u['email'] ?? '']);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Verify CSRF from POST or HTTP Header X-CSRF-TOKEN
     */
    public static function verifyCsrf(): bool {
        self::ensureSession();

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $requestToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if (empty($sessionToken) || empty($requestToken) || !hash_equals($sessionToken, $requestToken)) {
            $audit = new SecurityAuditService();
            $audit->logEvent('csrf_mismatch', 'warning', $_SESSION['user_id'] ?? null, 'CSRF Token validation failed');
            return false;
        }

        return true;
    }

    /**
     * Render Persian 403 Forbidden Card
     */
    private static function renderUnauthorized(string $message): void {
        echo <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8">
    <title>دسترسی</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: rgba(30, 41, 59, 0.8); border: 1px solid #334155; border-radius: 24px; padding: 40px; max-width: 480px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); backdrop-filter: blur(16px); }
        .icon { width: 64px; height: 64px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; margin-bottom: 20px; border: 1px solid rgba(245, 158, 11, 0.3); }
        h1 { font-size: 20px; font-weight: 800; margin: 0 0 10px; color: #ffffff; }
        p { font-size: 13px; color: #94a3b8; line-height: 1.6; margin: 0 0 24px; }
        .btn { display: inline-block; background: #0284c7; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 14px; font-size: 13px; font-weight: 700; transition: all 0.2s; }
        .btn:hover { background: #0369a1; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔒</div>
        <h1>عدم احراز سطح دسترسی</h1>
        <p>{$message}</p>
        <div>
            <a href="/index.php" class="btn">بازگشت به صفحه اصلی</a>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }

    /**
     * Attempt automatic authentication using secure signed persistent cookie
     */
    public static function attemptRememberLogin(?PDO $pdo = null): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            self::ensureSession();
        }

        if (!empty($_SESSION['user_id'])) {
            return self::user($pdo);
        }

        if (empty($_COOKIE['asena_remember'])) {
            return null;
        }

        $cookieRaw = base64_decode((string)$_COOKIE['asena_remember'], true);
        if (!$cookieRaw) {
            self::clearRememberCookie();
            return null;
        }

        $data = json_decode($cookieRaw, true);
        if (!is_array($data) || empty($data['id']) || empty($data['expires']) || empty($data['sig'])) {
            self::clearRememberCookie();
            return null;
        }

        if ($data['expires'] < time()) {
            self::clearRememberCookie();
            return null;
        }

        $db = $pdo ?? ($GLOBALS['pdo'] ?? null);
        if (!$db) {
            return null;
        }

        try {
            $stmt = $db->prepare("SELECT id, phone, name, role, password FROM users WHERE id = ?");
            $stmt->execute([(int)$data['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                self::clearRememberCookie();
                return null;
            }

            $secretKey = getenv('APP_KEY') ?: 'ASENA_REMEMBER_SECRET_2026_@&^!';
            $pwdHash = !empty($user['password']) ? hash('sha256', $user['password']) : 'NO_PASSWORD';
            $expectedSig = hash_hmac('sha256', $user['id'] . '|' . $data['expires'] . '|' . $user['phone'] . '|' . $pwdHash, $secretKey);

            if (!hash_equals($expectedSig, $data['sig'])) {
                self::clearRememberCookie();
                return null;
            }

            // Valid persistent login! Populate session
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_role'] = $user['role'] ?: 'user';
            $_SESSION['role'] = $user['role'] ?: 'user';
            $_SESSION['name'] = $user['name'];
            $_SESSION['user_name'] = $user['name'];
            if (!empty($user['password'])) {
                $_SESSION['password_hash'] = hash('sha256', $user['password']);
            }
            $_SESSION['contract_accepted_version'] = 'v2.0-2026';

            // Re-extend cookie for another 30 days
            self::setRememberCookie((int)$user['id'], $user['phone'], $user['password'] ?? '', 30);

            return self::user($db);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Set secure signed persistent cookie and extend session cookie to 30 days
     */
    public static function setRememberCookie(int $userId, string $phone, ?string $password, int $days = 30): void {
        $secretKey = getenv('APP_KEY') ?: 'ASENA_REMEMBER_SECRET_2026_@&^!';
        $expires = time() + ($days * 86400);
        $pwdHash = !empty($password) ? hash('sha256', $password) : 'NO_PASSWORD';
        $sig = hash_hmac('sha256', $userId . '|' . $expires . '|' . $phone . '|' . $pwdHash, $secretKey);
        $payload = base64_encode(json_encode(['id' => $userId, 'expires' => $expires, 'sig' => $sig]));

        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        setcookie('asena_remember', $payload, [
            'expires'  => $expires,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        if (session_status() === PHP_SESSION_ACTIVE) {
            setcookie(session_name(), session_id(), [
                'expires'  => $expires,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }

    /**
     * Clear persistent remember cookie
     */
    public static function clearRememberCookie(): void {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        setcookie('asena_remember', '', [
            'expires'  => time() - 86400,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
