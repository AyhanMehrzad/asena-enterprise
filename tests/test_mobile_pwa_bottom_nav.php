<?php
/**
 * ASENA Enterprise - Test Mobile PWA Bottom Navigation Bar
 */

echo "=== Testing Mobile PWA Bottom Navigation Bar ===\n";

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

// Render footer in isolated subprocess
$cmd = "/opt/lampp/bin/php -r '\$_SESSION = []; ob_start(); include \"" . __DIR__ . "/../includes/footer.php\"; echo ob_get_clean();'";
$html = shell_exec($cmd);

// 1. Verify nav element and ID exist
testAssert(strpos($html, '<nav class="mobile-bottom-nav" id="mobileBottomNavBar"') !== false, "mobileBottomNavBar nav element exists");

// 2. Verify offline resilience strip exists
testAssert(strpos($html, 'id="offline-status-strip"') !== false, "offline-status-strip element exists");

// 3. Verify all 5 primary bottom navigation tabs exist
testAssert(strpos($html, 'href="/" class="bottom-nav-link') !== false, "Tab 1: Home exists");
testAssert(strpos($html, 'onclick="openMobileCategoriesSheet(); return false;"') !== false, "Tab 2: Categories sheet trigger exists");
testAssert(strpos($html, 'href="/cart" class="bottom-nav-link') !== false, "Tab 3: Cart exists");
testAssert(strpos($html, 'href="/booking" class="bottom-nav-link') !== false, "Tab 4: Booking exists");
testAssert(strpos($html, '<span>آسنای من</span>') !== false, "Tab 5: My Asena profile exists");

// 4. Verify Categories bottom sheet dialog exists
testAssert(strpos($html, 'id="mobileCategoriesSheet"') !== false, "mobileCategoriesSheet bottom sheet exists");
testAssert(strpos($html, 'id="mobileCategoriesBackdrop"') !== false, "mobileCategoriesBackdrop exists");

// 5. Verify no script tag corruption
$scriptPos = strpos($html, 'actions/autoship_worker.php');
$scriptClosePos = strpos($html, '</script>', $scriptPos);
$navPos = strpos($html, '<nav class="mobile-bottom-nav"');
testAssert($scriptClosePos !== false && $scriptClosePos < $navPos, "Autoship script is properly closed before nav starts");

echo "=== All $passCount Mobile Bottom Nav Tests Passed Successfully! ===\n";
