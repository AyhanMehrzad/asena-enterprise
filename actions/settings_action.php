<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if ($action === 'update_account') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $hashed_password, $user_id]);

                // 1. Regenerate session ID and synchronize session password hash to prevent revocation
                session_regenerate_id(true);
                $_SESSION['password_hash'] = hash('sha256', $hashed_password);

                // 2. Refresh remember-me cookie if active
                if (!empty($_COOKIE['asena_remember'])) {
                    require_once __DIR__ . '/../includes/AuthGuard.php';
                    $uStmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
                    $uStmt->execute([$user_id]);
                    $userPhone = $uStmt->fetchColumn() ?: '';
                    AuthGuard::setRememberCookie($user_id, $userPhone, $hashed_password, 30);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $user_id]);
            }
            if (!empty($name)) {
                $_SESSION['user_name'] = $name;
                $_SESSION['name'] = $name;
            }
            $_SESSION['settings_success'] = "اطلاعات حساب با موفقیت بروزرسانی شد.";
            $_SESSION['profile_success'] = "اطلاعات حساب با موفقیت بروزرسانی شد.";
            header("Location: ../profile.php#personal-info");
            exit;
        } catch (PDOException $e) {
            $_SESSION['settings_error'] = "خطا در بروزرسانی اطلاعات حساب.";
            $_SESSION['profile_error'] = "خطا در بروزرسانی اطلاعات حساب.";
            header("Location: ../profile.php#personal-info");
            exit;
        }
    } elseif ($action === 'update_address') {
        $city = trim($_POST['city'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        // Ensure lat/lng are null if empty
        if ($latitude === '') $latitude = null;
        if ($longitude === '') $longitude = null;

        require_once __DIR__ . '/../includes/MapService.php';
        $postalVal = MapService::validatePostalCode($postal_code);

        if (!$postalVal['valid']) {
            $_SESSION['settings_error'] = $postalVal['error'];
            $_SESSION['profile_error'] = $postalVal['error'];
            header("Location: ../profile.php#addresses");
            exit;
        } else {
            $postal_code = $postalVal['code'];
            try {
                $stmt = $pdo->prepare("UPDATE users SET city = ?, postal_code = ?, address = ?, latitude = ?, longitude = ? WHERE id = ?");
                $stmt->execute([$city, $postal_code, $address, $latitude, $longitude, $user_id]);
                $_SESSION['settings_success'] = "نشانی با موفقیت بروزرسانی شد.";
                $_SESSION['profile_success'] = "نشانی با موفقیت بروزرسانی شد.";
                header("Location: ../profile.php#addresses");
                exit;
            } catch (PDOException $e) {
                $_SESSION['settings_error'] = "خطا در بروزرسانی نشانی.";
                $_SESSION['profile_error'] = "خطا در بروزرسانی نشانی.";
                header("Location: ../profile.php#addresses");
                exit;
            }
        }
    }
}

header("Location: ../profile.php#personal-info");
exit;
?>
