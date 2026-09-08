<?php
/**
 * ASENA Enterprise - Centralized Multi-Role RBAC & Authorization Guard
 * Version: 1.0.0
 */

require_once __DIR__ . '/SecurityAuditService.php';

class AuthGuard {
    private static ?array $cachedUser = null;

    /**
     * Get current authenticated user
     */
    public static function user(?PDO $pdo = null): ?array {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
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
                unset($u['password']);
                self::$cachedUser = $u;
                return self::$cachedUser;
            }
        } catch (Throwable $e) {}

        return null;
    }

    /**
     * Require authenticated session
     */
    public static function requireAuth(?string $returnUrl = null): array {
        $u = self::user();
        if (!$u) {
            $url = $returnUrl ?? ($_SERVER['REQUEST_URI'] ?? 'index.php');
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            if (preg_match('#/(organization|admin|pharmacist|doctor|seller)/#', $script)) {
                $baseApp = dirname(dirname($script));
            } else {
                $baseApp = dirname($script);
            }
            $baseApp = rtrim(str_replace('\\', '/', $baseApp), '/');
            $loginTarget = (!empty($baseApp) && $baseApp !== '.') ? ($baseApp . '/login.php') : 'login.php';
            header("Location: " . $loginTarget . "?return_url=" . urlencode($url));
            exit;
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

        $currentRole = $u['role'] ?? 'user';
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
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

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
}
