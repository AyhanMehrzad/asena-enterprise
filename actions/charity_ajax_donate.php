<?php
/**
 * actions/charity_ajax_donate.php — Real-time in-page donation processor
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'متد درخواست باید POST باشد.']);
    exit;
}

csrf_verify();

$rawAmount = to_english_digits($_POST['amount'] ?? '0');
$amount = (int)preg_replace('/[^0-9]/', '', $rawAmount);
$campaign_id = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
$is_anonymous = isset($_POST['is_anonymous']) && ($_POST['is_anonymous'] === '1' || $_POST['is_anonymous'] === 'true' || $_POST['is_anonymous'] === 'on');
$donor_name = trim($_POST['donor_name'] ?? '');

if ($amount < 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'حداقل مبلغ حمایت ۱,۰۰۰ تومان می‌باشد.']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$is_anonymous && $user_id && empty($donor_name)) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch();
    if ($u && !empty($u['name'])) {
        $donor_name = $u['name'];
    }
}

if ($is_anonymous || empty($donor_name)) {
    $donor_name = $is_anonymous ? 'ناشناس' : 'نیکوکار ناشناس';
}

$ref_id = 'TRX-' . rand(10000000, 99999999);

try {
    // 1. Insert successful donation
    $stmt = $pdo->prepare("
        INSERT INTO donations (user_id, donor_name, campaign_id, amount, status, payment_reference, created_at) 
        VALUES (?, ?, ?, ?, 'successful', ?, NOW())
    ");
    $stmt->execute([$user_id, $donor_name, $campaign_id, $amount, $ref_id]);
    $newDonationId = $pdo->lastInsertId();

    // 2. Update campaign current_amount if assigned
    if ($campaign_id) {
        $stmt = $pdo->prepare("
            UPDATE campaigns 
            SET current_amount = (
                SELECT COALESCE(SUM(amount), 0) 
                FROM donations 
                WHERE campaign_id = ? AND status = 'successful'
            ) 
            WHERE id = ?
        ");
        $stmt->execute([$campaign_id, $campaign_id]);
    }

    // 3. Send SMS if phone is available
    if (!empty($user_id)) {
        try {
            require_once __DIR__ . '/../includes/SmsService.php';
            $user_stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
            $user_stmt->execute([$user_id]);
            $donor = $user_stmt->fetch(PDO::FETCH_ASSOC);
            if ($donor && !empty($donor['phone'])) {
                $sms = new SmsService();
                $sms->sendCharityThankYou($donor['phone'], $amount);
            }
        } catch (Exception $ex) {
            error_log('[Charity SMS Error] ' . $ex->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'پرداخت و حمایت شما با موفقیت ثبت شد. صمیمانه از همراهی شما سپاسگزاریم!',
        'ref_id' => $ref_id,
        'donation' => [
            'id' => (int)$newDonationId,
            'donor_name' => $donor_name,
            'amount' => $amount,
            'amount_formatted' => number_format($amount),
            'campaign_id' => $campaign_id
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'خطا در ثبت حمایت: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
