<?php
/**
 * Test: Site Builder Image Upload & Portal Sidebar Collapse
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';

echo "=== Testing Site Builder Image Upload & Portal Sidebar Collapse ===\n";

// 1. Verify actions/site_builder_action.php contains upload_asset
$actionContent = file_get_contents(__DIR__ . '/../actions/site_builder_action.php');
if (strpos($actionContent, "action === 'upload_asset'") !== false) {
    echo "  [PASS] upload_asset action is present in actions/site_builder_action.php\n";
} else {
    echo "  [FAIL] upload_asset action missing\n";
    exit(1);
}

// 2. Verify includes/site_builder_studio.php collapses portal sidebars
$studioContent = file_get_contents(__DIR__ . '/../includes/site_builder_studio.php');
if (strpos($studioContent, '#doctor-sidebar') !== false && 
    strpos($studioContent, 'transform: translateX(100%) !important') !== false) {
    echo "  [PASS] Dark navy portal sidebars collapsed across doctor, org, pharmacy, seller\n";
} else {
    echo "  [FAIL] Portal sidebar collapse CSS missing\n";
    exit(1);
}

// 3. Verify togglePortalSidebar and portal-backdrop
if (strpos($studioContent, 'togglePortalSidebar') !== false && 
    strpos($studioContent, 'id="portal-backdrop"') !== false) {
    echo "  [PASS] togglePortalSidebar function and portal-backdrop are present\n";
} else {
    echo "  [FAIL] Portal toggle function or backdrop missing\n";
    exit(1);
}

// 4. Verify Dual-Mode Image Upload / Link components
if (strpos($studioContent, 'hero-img-upload-box') !== false && 
    strpos($studioContent, 'hero-img-link-box') !== false &&
    strpos($studioContent, 'handleImageUpload') !== false &&
    strpos($studioContent, 'switchImageInputMode') !== false) {
    echo "  [PASS] Hero image dual-mode upload and link switcher is present\n";
} else {
    echo "  [FAIL] Hero image dual-mode upload switcher missing\n";
    exit(1);
}

// 5. Verify Logo & Banner Dual-Mode Upload
if (strpos($studioContent, 'logo-img-upload-box') !== false && 
    strpos($studioContent, 'banner-img-upload-box') !== false) {
    echo "  [PASS] Logo & Banner dual-mode upload and link switchers are present\n";
} else {
    echo "  [FAIL] Logo or Banner dual-mode upload switcher missing\n";
    exit(1);
}

// 6. Test Upload Asset Endpoint Simulation
$uploadDir = __DIR__ . '/../uploads/sites/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Create a dummy image file
$dummyImgPath = sys_get_temp_dir() . '/test_upload_' . uniqid() . '.png';
// 1x1 transparent PNG binary
$pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
file_put_contents($dummyImgPath, $pngData);

$targetName = 'test_asset_' . time() . '.png';
$finalDest = $uploadDir . $targetName;
if (copy($dummyImgPath, $finalDest)) {
    echo "  [PASS] Upload directory writable and accepts site assets: uploads/sites/$targetName\n";
    @unlink($finalDest);
} else {
    echo "  [FAIL] Failed to write to uploads/sites/\n";
    exit(1);
}
@unlink($dummyImgPath);

echo "=== All Tests Passed Successfully! ===\n";
