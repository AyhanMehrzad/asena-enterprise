<?php
/**
 * ASENA Enterprise - Tenant Website Checkout & Order Creation Engine
 * Handles native orders placed directly on tenant-branded microsites (site.php).
 * Connects directly to ASENA central gateway, inventory decrement, and seller cockpit.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/TenantSiteService.php';
require_once __DIR__ . '/../includes/gateway.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $tenantService = new TenantSiteService($pdo);

    // Read input (support JSON payload or Form POST)
    $rawInput = file_get_contents('php://input');
    $data = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    if (empty($data)) {
        $data = $_POST;
    }

    // CSRF check if token provided in session
    if (!empty($_SESSION['csrf_token']) && !empty($data['csrf_token'])) {
        if (!hash_equals($_SESSION['csrf_token'], (string)$data['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'توکن امنیتی منقضی شده است. لطفاً صفحه را رفرش فرمایید.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $siteSlug = trim((string)($data['slug'] ?? ''));
    $siteId   = (int)($data['site_id'] ?? 0);

    $site = null;
    if (!empty($siteSlug)) {
        $site = $tenantService->getSiteBySlug($siteSlug);
    } elseif ($siteId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM tenant_sites WHERE id = ? LIMIT 1");
        $stmt->execute([$siteId]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$site) {
        echo json_encode(['success' => false, 'message' => 'اطلاعات وب‌سایت ارائه‌دهنده یافت نشد.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tenantType = $site['tenant_type'];
    $tenantId   = (int)$site['tenant_id'];
    $tenantOwnerUserId = $tenantService->resolveTenantUserId($tenantType, $tenantId);

    // Validate Customer Info
    $customerName    = trim((string)($data['customer_name'] ?? ''));
    $customerPhone   = trim((string)($data['customer_phone'] ?? ''));
    $customerProvince= trim((string)($data['province'] ?? 'تهران'));
    $customerCity    = trim((string)($data['city'] ?? 'تهران'));
    $customerAddress = trim((string)($data['address'] ?? ''));
    $customerPostal  = trim((string)($data['postal_code'] ?? ''));
    $orderNotes      = trim((string)($data['order_notes'] ?? ''));
    $shippingMethod  = trim((string)($data['shipping_method'] ?? 'pishtaz'));

    // Normalize phone number (Iranian mobile format)
    $cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
    if (str_starts_with($cleanPhone, '98')) {
        $cleanPhone = '0' . substr($cleanPhone, 2);
    } elseif (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '9')) {
        $cleanPhone = '0' . $cleanPhone;
    }

    if (empty($customerName)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً نام و نام خانوادگی خریدار را وارد نمایید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!preg_match('/^09[0-9]{9}$/', $cleanPhone)) {
        echo json_encode(['success' => false, 'message' => 'شماره تلفن همراه وارد شده نامعتبر است (مثال: 09121234567).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($customerAddress) || mb_strlen($customerAddress, 'UTF-8') < 5) {
        echo json_encode(['success' => false, 'message' => 'لطفاً آدرس پستی دقیق جهت ارسال مرسوله را وارد فرمایید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validate Order Items
    $items = $data['items'] ?? [];
    if (is_string($items)) {
        $items = json_decode($items, true) ?: [];
    }

    if (empty($items) || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'سبد خرید شما خالی است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $verifiedItems = [];
    $subtotal = 0;

    foreach ($items as $rawItem) {
        $itemId = (int)($rawItem['id'] ?? 0);
        $source = trim((string)($rawItem['source'] ?? ($rawItem['item_source'] ?? 'product')));
        $qty    = max(1, min(50, (int)($rawItem['qty'] ?? ($rawItem['quantity'] ?? 1))));

        if ($itemId <= 0) continue;

        // Verify strictly from tenant inventory
        $stockItem = $tenantService->getTenantStockItem($itemId, $source, $tenantType, $tenantId);

        if (!$stockItem) {
            echo json_encode([
                'success' => false, 
                'message' => "کالای انتخابی (کد: {$itemId}) در انبار اختصاصی این مرکز موجود نمی‌باشد."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $availStock = (int)($stockItem['stock'] ?? 0);
        if ($availStock < $qty) {
            echo json_encode([
                'success' => false, 
                'message' => "موجودی کالای «{$stockItem['name']}» کافی نیست (موجودی انبار: {$availStock} عدد)."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $unitPrice = (int)($stockItem['price'] ?? 0);
        $lineTotal = $unitPrice * $qty;
        $subtotal += $lineTotal;

        $verifiedItems[] = [
            'id' => $itemId,
            'source' => $source,
            'name' => $stockItem['name'],
            'unit_price' => $unitPrice,
            'quantity' => $qty,
            'line_total' => $lineTotal
        ];
    }

    if (empty($verifiedItems)) {
        echo json_encode(['success' => false, 'message' => 'هیچ کالای معتبری در سبد خرید یافت نشد.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Shipping calculations (free if subtotal >= 500,000 Toman)
    $shippingCost = ($subtotal >= 500000) ? 0 : (($shippingMethod === 'express') ? 65000 : 45000);
    $carrierName  = ($shippingMethod === 'express') ? 'پیک اکسپرس درون‌شهری' : 'شرکت ملی پست / پیشتاز';

    // 10% statutory VAT (per Iranian Tax Standard)
    $vatAmount = (int)round($subtotal * 0.10);
    $totalAmount = $subtotal + $vatAmount + $shippingCost;

    // Resolve or Auto-Create User Account in ASENA
    $buyerUserId = (int)($_SESSION['user_id'] ?? 0);
    if ($buyerUserId <= 0) {
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $uStmt->execute([$cleanPhone]);
        $existingId = $uStmt->fetchColumn();

        if ($existingId) {
            $buyerUserId = (int)$existingId;
        } else {
            // Register new client user
            $rndPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $insU = $pdo->prepare("
                INSERT INTO users (name, phone, role, password, city, province, address, postal_code, created_at)
                VALUES (?, ?, 'user', ?, ?, ?, ?, ?, datetime('now'))
            ");
            $insU->execute([$customerName, $cleanPhone, $rndPassword, $customerCity, $customerProvince, $customerAddress, $customerPostal]);
            $buyerUserId = (int)$pdo->lastInsertId();
        }
    }

    // Build Full Shipping Details
    $fullAddressDetails = sprintf(
        "گیرنده: %s | تماس: %s | استان: %s | شهر: %s | آدرس: %s%s%s [ارسال از طریق: %s]",
        $customerName,
        $cleanPhone,
        $customerProvince,
        $customerCity,
        $customerAddress,
        !empty($customerPostal) ? " | کدپستی: {$customerPostal}" : "",
        !empty($orderNotes) ? " | توضیحات: {$orderNotes}" : "",
        $carrierName
    );

    // Create Order Record
    $pdo->beginTransaction();

    $orderTrackingCode = 'ASN-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));

    $insOrder = $pdo->prepare("
        INSERT INTO orders (
            user_id, total_amount, discount_amount, tax_amount, shipping_cost,
            carrier_name, status, shipping_address, tracking_code,
            source_tenant_type, source_tenant_id, created_at
        ) VALUES (
            ?, ?, 0, ?, ?,
            ?, 'pending_payment', ?, ?,
            ?, ?, datetime('now')
        )
    ");
    $insOrder->execute([
        $buyerUserId,
        $totalAmount,
        $vatAmount,
        $shippingCost,
        $carrierName,
        $fullAddressDetails,
        $orderTrackingCode,
        $tenantType,
        $tenantId
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // Create Order Items
    $insItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, item_source, quantity, price_at_purchase,
            product_name_snapshot, seller_id, organization_id, commission_rate
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 10.00)
    ");

    $orgIdParam = ($tenantType === 'organization') ? $tenantId : null;

    foreach ($verifiedItems as $vIt) {
        $insItem->execute([
            $orderId,
            $vIt['id'],
            $vIt['source'],
            $vIt['quantity'],
            $vIt['unit_price'],
            $vIt['name'],
            $tenantOwnerUserId,
            $orgIdParam
        ]);
    }

    $pdo->commit();

    // Initiate Gateway Payment Request
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $appRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $subDir = str_replace($docRoot, '', $appRoot);
    $baseUrl = $scheme . '://' . $host . $subDir;

    $callbackUrl = $baseUrl . '/actions/tenant_payment_callback.php?' . http_build_query([
        'order_id' => $orderId,
        'slug'     => $site['slug']
    ]);

    $gatewayDesc = "سفارش #PC-{$orderId} از وب‌سایت اختصاصی «{$site['site_title']}»";
    $gateway = new ZarinPalGateway();
    $gateRes = $gateway->requestPayment($totalAmount, $gatewayDesc, $callbackUrl, [
        'mobile' => $cleanPhone,
        'email'  => ''
    ]);

    if (!$gateRes['success']) {
        echo json_encode([
            'success' => false,
            'message' => 'خطا در اتصال به درگاه پرداخت شاپرک: ' . ($gateRes['error'] ?? 'خطای ناشناخته')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Save pending authority for security check
    $_SESSION['tenant_pending_order'] = [
        'order_id'     => $orderId,
        'slug'         => $site['slug'],
        'authority'    => $gateRes['authority'],
        'total_amount' => $totalAmount,
        'items'        => $verifiedItems,
        'tenant_type'  => $tenantType,
        'tenant_id'    => $tenantId
    ];

    echo json_encode([
        'success'      => true,
        'message'      => 'سفارش ثبت گردید و در حال انتقال به درگاه پرداخت شاپرک است...',
        'order_id'     => $orderId,
        'redirect_url' => $gateRes['payment_url'],
        'total_amount' => $totalAmount
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[actions/tenant_order_action.php] " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'خطای سیستمی در پردازش سفارش: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
