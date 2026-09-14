<?php
/**
 * Automated Test Suite for ASENA Live Interaction & PWA Notification System
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/PushNotificationService.php';

function runAssertion(string $testName, bool $condition, string $detail = '') {
    if ($condition) {
        echo " [PASS] {$testName}\n";
    } else {
        echo " [FAIL] {$testName} - {$detail}\n";
        exit(1);
    }
}

echo "========================================================\n";
echo " Running ASENA Notification & PWA System Test Suite\n";
echo "========================================================\n";

// Test 1: Verify DB tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
runAssertion("Table 'user_notifications' exists", in_array('user_notifications', $tables));
runAssertion("Table 'pwa_subscriptions' exists", in_array('pwa_subscriptions', $tables));
runAssertion("Table 'live_social_proof_events' exists", in_array('live_social_proof_events', $tables));

// Test 2: PushNotificationService instance
$service = new PushNotificationService($pdo);
runAssertion("PushNotificationService instantiates successfully", $service instanceof PushNotificationService);

// Test 3: Create specific notification
$testUserId = 1;
$notifId = $service->createNotification(
    $testUserId,
    'purchase_offer',
    'تست خودکار پیشنهاد خرید',
    'این یک پیام تست برای بررسی سیستم اعلان‌های آسنا است.',
    'shop.php',
    'local_fire_department',
    'all'
);
runAssertion("createNotification returns valid ID", $notifId > 0, "ID: {$notifId}");

// Test 4: Retrieve user notifications
$notifs = $service->getUserNotifications($testUserId, 10);
runAssertion("getUserNotifications returns non-empty array", is_array($notifs) && count($notifs) > 0);

// Test 5: Verify unread count and mark as read
$unread = $service->getUnreadCount($testUserId);
runAssertion("getUnreadCount returns > 0", $unread > 0, "Unread: {$unread}");

$markOk = $service->markAsRead($notifId, $testUserId);
runAssertion("markAsRead succeeds", $markOk);

// Test 6: Broadcast campaign
$broadcastRes = $service->broadcastCampaign(
    'تست کمپین شگفت‌انگیز PWA',
    'تخفیف ویژه برای کاربران اپلیکیشن',
    'purchase_offer',
    'pwa_only',
    'shop.php'
);
runAssertion("broadcastCampaign succeeds for pwa_only", !empty($broadcastRes['success']));

// Test 7: Live Social Proof Feed
$feed = $service->getLiveSocialProofFeed(5);
runAssertion("getLiveSocialProofFeed returns array", is_array($feed) && count($feed) > 0);
runAssertion("Feed item contains user_name, city, and item_title", !empty($feed[0]['user_name']) && !empty($feed[0]['city']) && !empty($feed[0]['item_title']));

// Test 8: PWA subscription registration
$fakeEndpoint = 'https://fcm.googleapis.com/fcm/send/test-endpoint-' . uniqid();
$subOk = $service->registerPwaSubscription($testUserId, $fakeEndpoint, 'fake-p256dh', 'fake-auth', 'android_pwa');
runAssertion("registerPwaSubscription succeeds", $subOk);

// Test 9: PWA Welcome notification
$welcomeOk = $service->issuePwaWelcomeNotification($testUserId);
runAssertion("issuePwaWelcomeNotification succeeds", $welcomeOk);

echo "========================================================\n";
echo " ALL 9 TESTS PASSED SUCCESSFULLY!\n";
echo "========================================================\n";
