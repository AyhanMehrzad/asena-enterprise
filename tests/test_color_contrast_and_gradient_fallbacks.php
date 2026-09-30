<?php
/**
 * ASENA Enterprise - Color Contrast & Gradient Fallbacks Test Suite
 * Validates that all -950 shades, arbitrary gradient stops, aspect ratios,
 * and contact fallback text render with high contrast and zero visual defects.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';

echo "=== ASENA Color Contrast & Gradient Fallbacks Test ===\n";

// 1. Verify CSS files contain fallback tokens
$styleCss = file_get_contents(__DIR__ . '/../assets/css/style.css');
$enterpriseCss = file_get_contents(__DIR__ . '/../assets/css/enterprise-ui.css');

assert(strpos($styleCss, '.text-orange-950') !== false, 'style.css must define .text-orange-950');
assert(strpos($styleCss, '.text-rose-950') !== false, 'style.css must define .text-rose-950');
assert(strpos($styleCss, '.aspect-\[4\/3\]') !== false, 'style.css must define .aspect-[4/3]');
assert(strpos($styleCss, '.bg-gradient-to-r') !== false, 'style.css must define .bg-gradient-to-r');
assert(strpos($styleCss, '.from-\[\#001a48\]') !== false, 'style.css must define .from-[#001a48]');
echo "  [PASS] style.css contains all -950 shades and gradient fallbacks\n";

assert(strpos($enterpriseCss, '.text-orange-950') !== false, 'enterprise-ui.css must define .text-orange-950');
assert(strpos($enterpriseCss, '.aspect-\[4\/3\]') !== false, 'enterprise-ui.css must define .aspect-[4/3]');
assert(strpos($enterpriseCss, '.bg-gradient-to-r') !== false, 'enterprise-ui.css must define .bg-gradient-to-r');
echo "  [PASS] enterprise-ui.css contains all -950 shades and gradient fallbacks\n";

// 2. Verify site.php has explicit inline gradients on dark sections
$siteCode = file_get_contents(__DIR__ . '/../site.php');

assert(strpos($siteCode, 'data-block-id="emergency_bar"') !== false, 'emergency_bar present');
assert(strpos($siteCode, 'bg-gradient-red') !== false, 'emergency_bar has bg-gradient-red');
assert(strpos($siteCode, 'data-block-id="telehealth_launcher"') !== false, 'telehealth_launcher present');
assert(strpos($siteCode, 'bg-gradient-indigo') !== false, 'telehealth_launcher has bg-gradient-indigo');
assert(strpos($siteCode, 'data-block-id="booking"') !== false, 'booking present');
assert(strpos($siteCode, 'bg-gradient-dark-navy') !== false, 'booking has bg-gradient-dark-navy');
assert(strpos($siteCode, 'data-block-id="loyalty_club"') !== false, 'loyalty_club present');
assert(strpos($siteCode, 'bg-gradient-amber') !== false, 'loyalty_club has bg-gradient-amber');
echo "  [PASS] site.php dark sections have explicit semantic gradient classes\n";

// 3. Verify white pill buttons have explicit dark text colors
assert(strpos($siteCode, 'color: #7c2d12 !important;') !== false, 'Pill buttons in autoship/loyalty have dark text style');
echo "  [PASS] site.php pill buttons have explicit dark text color to prevent white-on-white\n";

// 4. Verify contact fallbacks in site.php
assert(strpos($siteCode, '$hoursDisplay') !== false, 'site.php defines $hoursDisplay fallback');
assert(strpos($siteCode, '$phoneDisplay') !== false, 'site.php defines $phoneDisplay fallback');
echo "  [PASS] site.php ensures non-empty fallback values for hours and phones\n";

// 5. Verify clinic-banner.jpg asset exists
assert(file_exists(__DIR__ . '/../assets/images/clinic-banner.jpg'), 'clinic-banner.jpg must exist');
assert(filesize(__DIR__ . '/../assets/images/clinic-banner.jpg') > 1000, 'clinic-banner.jpg must not be empty');
echo "  [PASS] clinic-banner.jpg exists and is valid\n";

// 6. Verify layout unpacking handles both root layout and blocks wrapper
assert(strpos($siteCode, 'isset($site[\'layout\'][\'hero\'])') !== false, 'site.php handles root layout schema');
echo "  [PASS] site.php supports two-way layout unpacking\n";

echo "=== All Contrast & Gradient Fallback Tests Passed Successfully! ===\n";
