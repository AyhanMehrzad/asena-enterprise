<?php
/**
 * actions/notification_action.php — Unified Notification & Live Social Proof API
 * Endpoints for Live Feed, Bell Center, PWA Subscription & Read Status
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/PushNotificationService.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$service = new PushNotificationService($pdo);

switch ($action) {
    case 'fetch_live_feed':
        // Return live social proof stream of customer purchases and appointments
        $feed = $service->getLiveSocialProofFeed(10);
        echo json_encode([
            'success' => true,
            'feed' => $feed
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'fetch_user_notifications':
        // Return user notifications & unread badge count
        $isPwa = !empty($_REQUEST['is_pwa']) && $_REQUEST['is_pwa'] === '1';
        $sinceId = (int)($_REQUEST['since_id'] ?? 0);
        $notifications = $service->getUserNotifications($userId, 30, $isPwa, $sinceId);
        $unreadCount = $service->getUnreadCount($userId, $isPwa);

        $latestId = 0;
        foreach ($notifications as $n) {
            if ((int)$n['id'] > $latestId) {
                $latestId = (int)$n['id'];
            }
        }

        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'latest_id' => $latestId,
            'logged_in' => !empty($userId)
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'mark_read':
        $notifId = (int)($_POST['id'] ?? 0);
        if ($notifId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }
        $ok = $service->markAsRead($notifId, $userId);
        echo json_encode(['success' => $ok]);
        exit;

    case 'mark_all_read':
        $ok = $service->markAllAsRead($userId);
        echo json_encode(['success' => $ok]);
        exit;

    case 'subscribe_pwa':
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $endpoint = $data['endpoint'] ?? '';
        $p256dh = $data['keys']['p256dh'] ?? ($data['p256dh'] ?? null);
        $auth = $data['keys']['auth'] ?? ($data['auth'] ?? null);
        $deviceType = $data['device_type'] ?? 'pwa';

        if (empty($endpoint)) {
            echo json_encode(['success' => false, 'error' => 'Missing endpoint']);
            exit;
        }

        $ok = $service->registerPwaSubscription($userId, $endpoint, $p256dh, $auth, $deviceType);
        echo json_encode(['success' => $ok]);
        exit;

    case 'pwa_installed':
        if ($userId) {
            $service->issuePwaWelcomeNotification($userId);
        }
        echo json_encode([
            'success' => true,
            'voucher' => 'PWA-WELCOME',
            'discount' => '10%'
        ], JSON_UNESCAPED_UNICODE);
        exit;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;
}
