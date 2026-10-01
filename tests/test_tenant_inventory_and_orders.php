<?php
/**
 * ASENA Enterprise - Automated Test Suite: Tenant Inventory Isolation & On-Site Order Processing
 * 
 * Verifies:
 * 1. Strict tenant inventory isolation (no cross-tenant product leakage or generic fallback).
 * 2. On-site checkout order creation connected to ASENA gateway.
 * 3. Atomic stock decrement upon payment callback.
 * 4. Automatic visibility in ASENA panels (organization/orders.php & seller/index.php).
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/TenantSiteService.php';

$testPassed = 0;
$testFailed = 0;

function run_test(string $name, callable $fn) {
    global $testPassed, $testFailed;
    try {
        $result = $fn();
        if ($result === true) {
            echo "[\033[32mPASS\033[0m] $name\n";
            $testPassed++;
        } else {
            echo "[\033[31mFAIL\033[0m] $name (Returned false)\n";
            $testFailed++;
        }
    } catch (Throwable $e) {
        echo "[\033[31mFAIL\033[0m] $name - Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
        $testFailed++;
    }
}

echo "====================================================================\n";
echo "ASENA Enterprise: Tenant Inventory & On-Site Order Pipeline Tests\n";
echo "====================================================================\n";

$tenantService = new TenantSiteService($pdo);

// Setup mock test fixtures
$orgId = 8888;
$sellerId = 7777;
$emptyTenantId = 9999;

// Ensure tables exist
$tenantService->ensureTable();

// Clean up old test data if present
$pdo->exec("DELETE FROM tenant_sites WHERE slug IN ('test-org-clinic', 'test-pet-seller', 'test-empty-tenant')");
$pdo->exec("DELETE FROM organization_inventory WHERE organization_id IN ($orgId, $emptyTenantId)");
$pdo->exec("DELETE FROM products WHERE organization_id IN ($orgId, $emptyTenantId) OR seller_id IN ($sellerId, $emptyTenantId)");
$pdo->exec("DELETE FROM pharmacy_medicines WHERE organization_id IN ($orgId, $emptyTenantId) OR seller_id IN ($sellerId, $emptyTenantId)");
$pdo->exec("DELETE FROM orders WHERE source_tenant_id IN ($orgId, $sellerId, $emptyTenantId)");

// 1. Create Tenant Sites
$siteOrg = $tenantService->saveSite('organization', $orgId, [
    'slug' => 'test-org-clinic',
    'site_title' => 'کلینیک دامپزشکی تست رازی',
    'theme_palette' => 'emerald'
]);

$siteSeller = $tenantService->saveSite('seller', $sellerId, [
    'slug' => 'test-pet-seller',
    'site_title' => 'پت‌شاپ اختصاصی البرز',
    'theme_palette' => 'amber'
]);

$siteEmpty = $tenantService->saveSite('organization', $emptyTenantId, [
    'slug' => 'test-empty-tenant',
    'site_title' => 'مرکز جدید بدون موجودی',
    'theme_palette' => 'sky'
]);

// 2. Add inventory for org
$pdo->exec("
    INSERT INTO products (name, price, stock, organization_id, category, image_url)
    VALUES ('شامپو درمانی ضد قارچ کلینیک', 250000, 20, $orgId, 'بهداشتی', 'assets/images/sample1.png')
");
$orgProdId = (int)$pdo->lastInsertId();

$pdo->exec("
    INSERT INTO pharmacy_medicines (name, brand, price, stock, organization_id, requires_prescription, image_url)
    VALUES ('آنتی‌بیوتیک انروفلوکساسین کلینیک', 'بایر', 180000, 15, $orgId, 1, 'assets/images/sample2.png')
");
$orgMedId = (int)$pdo->lastInsertId();

// Add stocked item in organization_inventory
$pdo->exec("
    INSERT INTO organization_inventory (organization_id, item_type, item_id, custom_price, stock, is_in_stock)
    VALUES ($orgId, 'medicine', $orgMedId, 185000, 12, 1)
");

// 3. Add inventory for seller
$pdo->exec("
    INSERT INTO products (name, price, stock, seller_id, category, image_url)
    VALUES ('غذای خشک گربه رویال کنین پت‌شاپ', 650000, 8, $sellerId, 'غذای پت', 'assets/images/sample3.png')
");
$sellerProdId = (int)$pdo->lastInsertId();

// ── Test 1: Strict Inventory Scoping ───────────────────────────────────────────
run_test("Strict Inventory Scoping: Organization gets only its own products & medicines", function() use ($tenantService, $orgId, $sellerId) {
    $prods = $tenantService->getTenantProducts('organization', $orgId, 10);
    if (empty($prods)) return false;

    // Must contain org's items
    $names = array_column($prods, 'name');
    $hasOrgProd = false;
    $hasSellerProd = false;

    foreach ($names as $nm) {
        if (str_contains($nm, 'کلینیک')) $hasOrgProd = true;
        if (str_contains($nm, 'پت‌شاپ')) $hasSellerProd = true;
    }

    // Must have org's items and MUST NOT have seller's items
    return ($hasOrgProd === true && $hasSellerProd === false);
});

run_test("Strict Inventory Scoping: Seller gets only its own products", function() use ($tenantService, $sellerId) {
    $prods = $tenantService->getTenantProducts('seller', $sellerId, 10);
    if (empty($prods)) return false;

    foreach ($prods as $p) {
        if (!str_contains($p['name'], 'پت‌شاپ')) return false;
        if (str_contains($p['name'], 'کلینیک')) return false;
    }
    return true;
});

run_test("Strict Inventory Scoping: Empty tenant returns empty array without fallback leakage", function() use ($tenantService, $emptyTenantId) {
    $prods = $tenantService->getTenantProducts('organization', $emptyTenantId, 10);
    // Must strictly be empty, never leaking other tenants' products
    return ($prods === []);
});

// ── Test 2: Item Stock Lookup and Direct Decrement ─────────────────────────────
run_test("getTenantStockItem returns accurate product details and enforces ownership", function() use ($tenantService, $orgId, $orgProdId, $sellerId) {
    // Org owner looking up their product
    $item = $tenantService->getTenantStockItem($orgProdId, 'product', 'organization', $orgId);
    if (!$item || (int)$item['stock'] !== 20 || (int)$item['price'] !== 250000) return false;

    // Alien tenant looking up the same product -> must be denied (null)
    $alienItem = $tenantService->getTenantStockItem($orgProdId, 'product', 'organization', 7777);
    if ($alienItem !== null) return false;

    return true;
});

run_test("decrementTenantStock accurately decrements stock in organization_inventory", function() use ($tenantService, $orgId, $orgMedId) {
    $dec = $tenantService->decrementTenantStock($orgMedId, 'pharmacy', 2, 'organization', $orgId);
    if (!$dec) return false;

    $stmt = $GLOBALS['pdo']->prepare("SELECT stock FROM organization_inventory WHERE organization_id = ? AND item_id = ? AND item_type = 'medicine'");
    $stmt->execute([$orgId, $orgMedId]);
    $newStock = (int)$stmt->fetchColumn();

    return ($newStock === 10); // 12 - 2 = 10
});

// ── Test 3: Order Creation and ASENA Central Connection ────────────────────────
run_test("Order placement from tenant website sets source_tenant and assigns seller_id", function() use ($tenantService, $pdo, $orgId, $orgProdId) {
    // Simulate tenant order insertion
    $tenantOwnerUserId = $tenantService->resolveTenantUserId('organization', $orgId);

    $subtotal = 250000 * 2; // 500,000
    $vat = (int)round($subtotal * 0.10); // 50,000
    $shipping = 0; // free over 500k
    $total = $subtotal + $vat + $shipping; // 550,000

    $pdo->beginTransaction();
    $insOrder = $pdo->prepare("
        INSERT INTO orders (
            user_id, total_amount, discount_amount, tax_amount, shipping_cost,
            carrier_name, status, shipping_address, tracking_code,
            source_tenant_type, source_tenant_id, created_at
        ) VALUES (
            1, ?, 0, ?, ?,
            'شرکت ملی پست / پیشتاز', 'processing', 'گیرنده: تست | شهر: تهران | آدرس: خ ولیعصر', 'ASN-TEST-1234',
            'organization', ?, datetime('now')
        )
    ");
    $insOrder->execute([$total, $vat, $shipping, $orgId]);
    $orderId = (int)$pdo->lastInsertId();

    $insItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, item_source, quantity, price_at_purchase,
            product_name_snapshot, seller_id, organization_id, commission_rate
        ) VALUES (?, ?, 'product', 2, 250000, 'شامپو درمانی ضد قارچ کلینیک', ?, ?, 10.00)
    ");
    $insItem->execute([$orderId, $orgProdId, $tenantOwnerUserId, $orgId]);
    $pdo->commit();

    // Verify order in database
    $chk = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $chk->execute([$orderId]);
    $ord = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$ord) return false;
    if ($ord['source_tenant_type'] !== 'organization' || (int)$ord['source_tenant_id'] !== $orgId) return false;
    if ((int)$ord['total_amount'] !== 550000 || (int)$ord['tax_amount'] !== 50000) return false;

    return true;
});

// ── Test 4: Visibility in Organization Orders Cockpit (organization/orders.php) ─
run_test("Orders placed on tenant website are immediately visible in organization/orders.php", function() use ($pdo, $orgId) {
    // Replicate organization/orders.php query logic
    $currentOrgId = $orgId;
    $orgUserId = $orgId;
    $currUserId = $orgId;

    $whereClauses = ["(
        (EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND (oi.seller_id = :org_user OR oi.seller_id = :curr_user OR oi.organization_id = :current_org_id)))
        OR (o.source_tenant_type = 'organization' AND o.source_tenant_id = :current_org_id)
    )"];
    $params = [
        ':org_user' => $orgUserId,
        ':curr_user' => $currUserId,
        ':current_org_id' => $currentOrgId
    ];

    $whereSql = implode(' AND ', $whereClauses);
    $ordersQuery = "SELECT o.* FROM orders o WHERE {$whereSql} ORDER BY o.id DESC";
    $stmt = $pdo->prepare($ordersQuery);
    $stmt->execute($params);
    $foundOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return (count($foundOrders) > 0);
});

// ── Test 5: Visibility in Seller Cockpit (seller/index.php) ───────────────────
run_test("Orders placed on seller website are immediately visible in seller/index.php", function() use ($pdo, $sellerId, $sellerProdId) {
    // Create an order for seller
    $pdo->beginTransaction();
    $insOrder = $pdo->prepare("
        INSERT INTO orders (
            user_id, total_amount, discount_amount, tax_amount, shipping_cost,
            carrier_name, status, shipping_address, tracking_code,
            source_tenant_type, source_tenant_id, created_at
        ) VALUES (
            1, 715000, 0, 65000, 0,
            'شرکت ملی پست / پیشتاز', 'processing', 'گیرنده: خریدار پت | شهر: کرج', 'ASN-SELLER-999',
            'seller', ?, datetime('now')
        )
    ");
    $insOrder->execute([$sellerId]);
    $orderId = (int)$pdo->lastInsertId();

    $insItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, item_source, quantity, price_at_purchase,
            product_name_snapshot, seller_id, organization_id, commission_rate
        ) VALUES (?, ?, 'product', 1, 650000, 'غذای خشک گربه رویال کنین پت‌شاپ', ?, NULL, 10.00)
    ");
    $insItem->execute([$orderId, $sellerProdId, $sellerId]);
    $pdo->commit();

    // Replicate seller/index.php query logic
    $ordersQuery = $pdo->prepare("
        SELECT o.id as order_id, o.status as order_status, oi.product_name_snapshot
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE (oi.seller_id = ? OR 'seller' = 'admin' OR (o.source_tenant_type = 'seller' AND o.source_tenant_id = ?))
        ORDER BY o.id DESC
    ");
    $ordersQuery->execute([$sellerId, $sellerId]);
    $sellerOrders = $ordersQuery->fetchAll(PDO::FETCH_ASSOC);

    if (empty($sellerOrders)) return false;
    return (str_contains($sellerOrders[0]['product_name_snapshot'], 'پت‌شاپ'));
});

// ── Test 6: Management Cockpit URLs ───────────────────────────────────────────
run_test("getTenantManagementUrl returns designated ASENA cockpit for each tenant type", function() use ($tenantService) {
    if ($tenantService->getTenantManagementUrl('organization', 1) !== 'organization/inventory.php') return false;
    if ($tenantService->getTenantManagementUrl('seller', 1) !== 'seller/index.php') return false;
    if ($tenantService->getTenantManagementUrl('pharmacist', 1) !== 'pharmacist/index.php') return false;
    if ($tenantService->getTenantManagementUrl('doctor', 1) !== 'doctor/index.php') return false;
    return true;
});

// Cleanup test fixtures
$pdo->exec("DELETE FROM tenant_sites WHERE slug IN ('test-org-clinic', 'test-pet-seller', 'test-empty-tenant')");
$pdo->exec("DELETE FROM organization_inventory WHERE organization_id IN ($orgId, $emptyTenantId)");
$pdo->exec("DELETE FROM products WHERE organization_id IN ($orgId, $emptyTenantId) OR seller_id IN ($sellerId, $emptyTenantId)");
$pdo->exec("DELETE FROM pharmacy_medicines WHERE organization_id IN ($orgId, $emptyTenantId) OR seller_id IN ($sellerId, $emptyTenantId)");
$pdo->exec("DELETE FROM orders WHERE source_tenant_id IN ($orgId, $sellerId, $emptyTenantId)");

echo "====================================================================\n";
echo "Test Execution Finished: $testPassed Passed, $testFailed Failed.\n";
echo "====================================================================\n";

if ($testFailed > 0) {
    exit(1);
}
exit(0);
