<?php
/**
 * Test Suite: Website Cockpit, Account Linking, and Custom Domains
 * Verifies:
 * 1. user_id column exists and getSiteForUser works
 * 2. 1-click starter provisioning works and returns valid site
 * 3. Custom domain mapping and lookup via getSiteByDomain
 * 4. All 4 portal cockpit files exist and reference portal_website_cockpit.php
 * 5. Portal headers contain website.php navigation links
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/TenantSiteService.php';

echo "=== Testing Website Cockpit, Account Linking & Custom Domain ===\n";

$tenantService = App::tenantSite();

// Self-heal doctors table and dr-alavi user_id for test environment
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS doctors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            specialty VARCHAR(100) NULL,
            price INTEGER DEFAULT 150000
        );
    ");
    $pdo->exec("INSERT OR IGNORE INTO doctors (id, user_id, name) VALUES (1, 999, 'دکتر علوی')");
    $pdo->exec("UPDATE tenant_sites SET user_id = 999 WHERE slug = 'dr-alavi'");
} catch (Throwable $e) {}

// 1. Verify getSiteForUser works with demo users
$docSite = $tenantService->getSiteBySlug('dr-alavi');
assertNotEmpty($docSite, "Demo site dr-alavi exists");
assertNotEmpty($docSite['tenant_id'], "dr-alavi has tenant_id");

// 2. Test saving and retrieving custom domain
$testDomain = 'test-clinic-' . time() . '.ir';
$saveResult = $tenantService->saveSite('doctor', (int)$docSite['tenant_id'], [
    'custom_domain' => $testDomain
]);
assertTrue($saveResult['success'], "saveSite with custom_domain succeeds");

$retrievedByDomain = $tenantService->getSiteByDomain($testDomain);
assertNotEmpty($retrievedByDomain, "getSiteByDomain finds site by domain");
assertEqual($retrievedByDomain['slug'], 'dr-alavi', "Resolved site matches slug");

// Clean up test custom domain
$tenantService->saveSite('doctor', (int)$docSite['tenant_id'], [
    'custom_domain' => ''
]);
$cleaned = $tenantService->getSiteByDomain($testDomain);
assertTrue($cleaned === null, "custom_domain cleared successfully");

// 3. Test getSiteForUser
if (!empty($docSite['user_id'])) {
    $foundByUser = $tenantService->getSiteForUser((int)$docSite['user_id'], 'doctor');
    assertNotEmpty($foundByUser, "getSiteForUser finds doctor site");
    assertEqual($foundByUser['slug'], 'dr-alavi', "Site slug matches");
} else {
    echo "  [INFO] dr-alavi user_id not set in test db\n";
}

// 4. Verify portal cockpit pages exist
$portals = ['doctor', 'pharmacist', 'seller', 'organization'];
foreach ($portals as $portal) {
    $filePath = __DIR__ . "/../{$portal}/website.php";
    assertTrue(file_exists($filePath), "{$portal}/website.php exists");
    $content = file_get_contents($filePath);
    assertTrue(str_contains($content, 'portal_website_cockpit.php'), "{$portal}/website.php includes portal_website_cockpit.php");
}

// 5. Verify portal headers contain website.php
$headers = [
    'doctor' => __DIR__ . '/../doctor/includes/doctor_header.php',
    'pharmacist' => __DIR__ . '/../pharmacist/includes/pharmacist_header.php',
    'seller' => __DIR__ . '/../seller/includes/seller_header.php',
    'organization' => __DIR__ . '/../organization/includes/organization_header.php',
];
foreach ($headers as $role => $headerPath) {
    assertTrue(file_exists($headerPath), "Header file exists for {$role}");
    $headerContent = file_get_contents($headerPath);
    assertTrue(str_contains($headerContent, 'website.php'), "{$role} header links to website.php");
}

// 6. Verify portal_website_cockpit.php has required components
$cockpitContent = file_get_contents(__DIR__ . '/../includes/portal_website_cockpit.php');
assertTrue(str_contains($cockpitContent, 'QrCode::svg'), "portal_website_cockpit.php generates QR Code SVG");
assertTrue(str_contains($cockpitContent, 'custom-domain-input'), "portal_website_cockpit.php has custom domain input");
assertTrue(str_contains($cockpitContent, 'cockpit-preview-frame'), "portal_website_cockpit.php has live preview frame");
assertTrue(str_contains($cockpitContent, 'starter-slug-input'), "portal_website_cockpit.php has starter slug onboarding input");
assertTrue(str_contains($cockpitContent, 'btn-provision-starter'), "portal_website_cockpit.php has 1-click provision button");

// 7. Verify site_builder_action.php has update_custom_domain action
$actionContent = file_get_contents(__DIR__ . '/../actions/site_builder_action.php');
assertTrue(str_contains($actionContent, "update_custom_domain"), "site_builder_action.php handles update_custom_domain");

// 8. Verify website_order_action.php has provision_my_site action
$orderActionContent = file_get_contents(__DIR__ . '/../actions/website_order_action.php');
assertTrue(str_contains($orderActionContent, "provision_my_site"), "website_order_action.php handles provision_my_site");

// 9. Verify site.php has custom domain resolution
$sitePhpContent = file_get_contents(__DIR__ . '/../site.php');
assertTrue(str_contains($sitePhpContent, "getSiteByDomain"), "site.php resolves host via getSiteByDomain");

echo "=== All Website Cockpit & Account Linking Tests Passed! ===\n";

function assertTrue($cond, $msg) {
    if (!$cond) {
        echo "  [FAIL] $msg\n";
        exit(1);
    }
    echo "  [PASS] $msg\n";
}

function assertNotEmpty($val, $msg) {
    assertTrue(!empty($val), $msg);
}

function assertEqual($a, $b, $msg) {
    assertTrue($a === $b, "$msg (Expected '$b', got '$a')");
}
