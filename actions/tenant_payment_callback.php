<?php
/**
 * ASENA Enterprise - Tenant Payment Callback Engine
 * Handles post-payment gateway return from Shaparak / ZarinPal.
 * Updates order status, decrements tenant inventory, deposits into escrow, and redirects back to site.php.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/TenantSiteService.php';
require_once __DIR__ . '/../includes/gateway.php';
require_once __DIR__ . '/../includes/SmsService.php';

$authority = trim((string)($_GET['Authority'] ?? $_GET['authority'] ?? ''));
$status    = strtoupper(trim((string)($_GET['Status'] ?? $_GET['status'] ?? '')));
$orderId   = (int)($_GET['order_id'] ?? 0);
$slug      = trim((string)($_GET['slug'] ?? ''));

// Resilient parsing if tx parameter was passed
if (empty($authority) && !empty($_GET['tx'])) {
    $rawTx = trim($_GET['tx']);
    if (str_contains($rawTx, '?')) {
        $parts = explode('?', $rawTx, 2);
        $authority = $parts[0];
        if (empty($status) && str_contains($parts[1], 'Status=')) {
            parse_str($parts[1], $extraParams);
            if (!empty($extraParams['Status'])) {
                $status = strtoupper(trim($extraParams['Status']));
            }
        }
    } else {
        $authority = $rawTx;
    }
}

$tenantService = new TenantSiteService($pdo);
$site = $tenantService->getSiteBySlug($slug);
$redirectBase = (!empty($slug)) ? "../site.php?slug=" . urlencode($slug) : "../index.php";

if ($orderId <= 0) {
    header("Location: {$redirectBase}&error=" . urlencode("شناسه سفارش نامعتبر است."));
    exit;
}

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: {$redirectBase}&error=" . urlencode("سفارش مورد نظر یافت نشد."));
    exit;
}

$totalAmount = (int)$order['total_amount'];
$tenantType  = $order['source_tenant_type'] ?? ($site['tenant_type'] ?? 'seller');
$tenantId    = (int)($order['source_tenant_id'] ?? ($site['tenant_id'] ?? 1));

// If gateway reported failure or customer cancelled
if ($status !== 'OK' || empty($authority)) {
    try {
        $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND status = 'pending_payment'")
            ->execute([$orderId]);
    } catch (Throwable $e) {}

    unset($_SESSION['tenant_pending_order']);
    header("Location: {$redirectBase}&order_failed=1&order_id={$orderId}");
    exit;
}

// Server-to-Server Gateway Verification
$gateway  = new ZarinPalGateway();
$verified = $gateway->verifyPayment($totalAmount, $authority);

if (!$verified['success']) {
    unset($_SESSION['tenant_pending_order']);
    $errMsg = $verified['error'] ?? 'خطا در احراز تراکنش بانکی';
    header("Location: {$redirectBase}&order_failed=1&order_id={$orderId}&msg=" . urlencode($errMsg));
    exit;
}

$refId = $verified['ref_id'] ?? ('REF-' . time());

// Verified Payment: Commit Order Transition Atomically
try {
    $pdo->beginTransaction();

    // 1. Update Order Status
    $upOrder = $pdo->prepare("
        UPDATE orders 
        SET status = 'processing',
            gateway_ref_id = ?,
            tracking_code = ?
        WHERE id = ?
    ");
    $upOrder->execute([$refId, $refId, $orderId]);

    // 2. Fetch Order Items and Decrement Stock
    $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $pId    = (int)$item['product_id'];
        $src    = $item['item_source'] ?? 'product';
        $qty    = (int)$item['quantity'];

        $tenantService->decrementTenantStock($pId, $src, $qty, $tenantType, $tenantId);
    }

    // 3. Deposit into Marketplace Escrow Ledger
    try {
        App::escrow()->depositOrderToEscrow($orderId);
    } catch (Throwable $escEx) {
        error_log("[Tenant Callback Escrow Warning] " . $escEx->getMessage());
    }

    $pdo->commit();

    // 4. SMS Alerts to Buyer & Tenant Cockpit
    try {
        $sms = new SmsService();
        $buyerPhone = '';
        if (preg_match('/تماس:\s*(09[0-9]{9})/', $order['shipping_address'] ?? '', $m)) {
            $buyerPhone = $m[1];
        }

        if (!empty($buyerPhone)) {
            $siteName = $site['site_title'] ?? 'وب‌سایت ارائه‌دهنده';
            $sms->sendShippingUpdate($buyerPhone, $orderId);
        }

        // Notify Tenant Owner User
        $tenantUserId = $tenantService->resolveTenantUserId($tenantType, $tenantId);
        if ($tenantUserId > 0) {
            $uStmt = $pdo->prepare("SELECT phone, name FROM users WHERE id = ? LIMIT 1");
            $uStmt->execute([$tenantUserId]);
            $tenantUser = $uStmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($tenantUser['phone'])) {
                $sms->sendSellerNewOrderAlert($tenantUser['phone'], $orderId);
            }
        }
    } catch (Throwable $smsEx) {
        error_log("[Tenant Callback SMS Warning] " . $smsEx->getMessage());
    }

    // 5. In-App Notification in ASENA
    try {
        $tenantUserId = $tenantService->resolveTenantUserId($tenantType, $tenantId);
        if ($tenantUserId > 0) {
            $notifTitle = "سفارش جدید از وب‌سایت اختصاصی (#PC-{$orderId})";
            $notifMsg   = "سفارش جدیدی به مبلغ " . number_format($totalAmount) . " تومان در وب‌سایت اختصاصی شما ثبت و پرداخت گردید. جهت بررسی و ارسال به پنل مدیریت انبار مراجعه فرمایید.";
            
            $pdo->prepare("
                INSERT INTO user_notifications (user_id, title, message, link, is_read, created_at)
                VALUES (?, ?, ?, 'seller/index.php', 0, datetime('now'))
            ")->execute([$tenantUserId, $notifTitle, $notifMsg]);
        }
    } catch (Throwable $nEx) {}

    unset($_SESSION['tenant_pending_order']);

    // Redirect with celebratory receipt parameters back to tenant site
    header("Location: {$redirectBase}&order_success=1&order_id={$orderId}&ref_id=" . urlencode($refId));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[Tenant Payment Callback Error] " . $e->getMessage());
    header("Location: {$redirectBase}&order_failed=1&order_id={$orderId}&msg=" . urlencode($e->getMessage()));
    exit;
}
