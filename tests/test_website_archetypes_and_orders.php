<?php
/**
 * ASENA Enterprise - Multi-Archetype Websites & Order Flow Test Suite
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== Testing Multi-Archetype Websites & Ordering Engine ===\n";

$passCount = 0;
function testAssert(bool $condition, string $msg): void {
    global $passCount;
    if ($condition) {
        echo "  [PASS] $msg\n";
        $passCount++;
    } else {
        echo "  [FAIL] $msg\n";
        exit(1);
    }
}

$tenantService = App::tenantSite();

// 1. Test Archetypes Definition
$archetypes = $tenantService->getWebsiteArchetypes();
testAssert(count($archetypes) === 4, "getWebsiteArchetypes returns exactly 4 archetypes");
testAssert(isset($archetypes['doctor']), "Doctor archetype is defined");
testAssert(isset($archetypes['pharmacist']), "Pharmacist archetype is defined");
testAssert(isset($archetypes['seller']), "Seller archetype is defined");
testAssert(isset($archetypes['organization']), "Organization archetype is defined");

testAssert(!empty($archetypes['doctor']['features']), "Doctor has feature list");
testAssert(!empty($archetypes['pharmacist']['features']), "Pharmacist has feature list");
testAssert(!empty($archetypes['seller']['features']), "Seller has feature list");
testAssert(!empty($archetypes['organization']['features']), "Organization has feature list");

// 2. Test Demo Sites Exist
$demoDoctor = $tenantService->getSiteBySlug('dr-alavi');
testAssert(!empty($demoDoctor), "Demo site dr-alavi exists");
testAssert($demoDoctor['tenant_type'] === 'doctor', "dr-alavi is doctor tenant_type");

$demoPharmacy = $tenantService->getSiteBySlug('sina-pharmacy');
testAssert(!empty($demoPharmacy), "Demo site sina-pharmacy exists");
testAssert($demoPharmacy['tenant_type'] === 'pharmacist', "sina-pharmacy is pharmacist tenant_type");

$demoSeller = $tenantService->getSiteBySlug('petland-store');
testAssert(!empty($demoSeller), "Demo site petland-store exists");
testAssert($demoSeller['tenant_type'] === 'seller', "petland-store is seller tenant_type");

$demoOrg = $tenantService->getSiteBySlug('razi-hospital');
testAssert(!empty($demoOrg), "Demo site razi-hospital exists");
testAssert($demoOrg['tenant_type'] === 'organization', "razi-hospital is organization tenant_type");

// Helper to run action in sub-process
function runActionPost(array $post): array {
    $payload = base64_encode(json_encode($post, JSON_UNESCAPED_UNICODE));
    $cmd = sprintf(
        "/opt/lampp/bin/php -r '\$_POST = json_decode(base64_decode(\"%s\"), true); ob_start(); include \"%s\"; echo ob_get_clean();'",
        $payload,
        __DIR__ . '/../actions/website_order_action.php'
    );
    $out = shell_exec($cmd);
    return json_decode($out, true) ?: [];
}

// 3. Test Subdomain Checking Endpoint
$checkJson = runActionPost(['action' => 'check_slug', 'slug' => 'brand-new-clinic']);
testAssert(!empty($checkJson['success']), "check_slug returns success: true");
testAssert(!empty($checkJson['available']), "brand-new-clinic is available");

// 4. Test Subdomain Reserved Protection
$reservedJson = runActionPost(['action' => 'check_slug', 'slug' => 'admin']);
testAssert(empty($reservedJson['available']), "Reserved slug 'admin' is blocked");

// 5. Test Website Order Submission
$orderJson = runActionPost([
    'action' => 'submit_order',
    'archetype' => 'pharmacist',
    'tier' => 'pharmacy',
    'desired_slug' => 'test-shafa-rx',
    'full_name' => 'دکتر کیانی',
    'phone' => '09129876543',
    'email' => 'kiani@example.com',
    'notes' => 'تست جامع ثبت سفارش داروخانه'
]);
testAssert(!empty($orderJson['success']), "submit_order succeeds");
testAssert(!empty($orderJson['order_id']), "submit_order returns valid order_id");

// Verify in DB
$stmt = $pdo->prepare("SELECT * FROM website_orders WHERE id = ?");
$stmt->execute([(int)$orderJson['order_id']]);
$dbOrder = $stmt->fetch(PDO::FETCH_ASSOC);
testAssert(!empty($dbOrder), "Order found in website_orders table");
testAssert($dbOrder['archetype'] === 'pharmacist', "Stored archetype is pharmacist");
testAssert($dbOrder['tier'] === 'pharmacy', "Stored tier is pharmacy");
testAssert($dbOrder['desired_slug'] === 'test-shafa-rx', "Stored slug is test-shafa-rx");

// Cleanup test record
$pdo->exec("DELETE FROM website_orders WHERE desired_slug = 'test-shafa-rx'");

// 6. Test websites.php Catalog HTML Output
ob_start();
include __DIR__ . '/../websites.php';
$catalogHtml = ob_get_clean();
testAssert(strpos($catalogHtml, 'پزشکان و متخصصین') !== false, "websites.php contains doctor archetype");
testAssert(strpos($catalogHtml, 'داروخانه‌های تخصصی') !== false, "websites.php contains pharmacy archetype");
testAssert(strpos($catalogHtml, 'پت‌شاپ‌ها و فروشگاه‌ها') !== false, "websites.php contains pet shop archetype");
testAssert(strpos($catalogHtml, 'بیمارستان‌ها و مراکز جامع') !== false, "websites.php contains hospital archetype");
testAssert(strpos($catalogHtml, 'websiteOrderModal') !== false, "websites.php contains order modal");
testAssert(strpos($catalogHtml, 'hero-subdomain-input') !== false, "websites.php contains hero subdomain checker");

echo "=== All $passCount Tests Passed Successfully! ===\n";
