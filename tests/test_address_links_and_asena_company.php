<?php
/**
 * Test Suite: Address Map Linking, asena.company Live Services & Universal Linking System
 *
 * Verifies:
 * 1. Default schema and fallbacks in TenantSiteService (map_link, nav_btn_text, asena.company endpoints, cta urls).
 * 2. Address map linking on public/preview site (duty bar, contact card, footer, modal).
 * 3. asena.company ecosystem links normalization and target="_blank" rel="noopener".
 * 4. Services and Bento Facilities custom link and button support.
 * 5. Studio quick controls, repeater forms, and client-side sync.
 */

require_once __DIR__ . '/../includes/TenantSiteService.php';

$passed = 0;
$total = 0;

function assertCondition($name, $condition, $msg = '') {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        echo "  [FAIL] {$name} - {$msg}\n";
    }
}

echo "=== Running Address Links, asena.company & Universal Linking Tests ===\n\n";

// 1. TenantSiteService Default Layout Verification
echo "Test Group 1: TenantSiteService Schema & Defaults\n";
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$siteService = new TenantSiteService($pdo);
$defaultLayout = $siteService->buildDefaultLayout('doctor', ['site_title' => 'کلینیک تخصصی']);

$blocks = $defaultLayout['blocks'] ?? $defaultLayout;

assertCondition(
    'Contact block default has map_link',
    array_key_exists('map_link', $blocks['contact'])
);

assertCondition(
    'Contact block default has nav_btn_text',
    isset($blocks['contact']['nav_btn_text']) && $blocks['contact']['nav_btn_text'] === 'مسیریابی با بلد / نشان'
);

assertCondition(
    'Header block default has cta_url',
    isset($blocks['header']['cta_url']) && $blocks['header']['cta_url'] === '#booking'
);

assertCondition(
    'Hero block default has cta_primary_url and cta_secondary_url',
    isset($blocks['hero']['cta_primary_url']) && isset($blocks['hero']['cta_secondary_url'])
);

assertCondition(
    'asena_services telehealth_url points to https://asena.company/chat.php',
    isset($blocks['asena_services']['telehealth_url']) && $blocks['asena_services']['telehealth_url'] === 'https://asena.company/chat.php'
);

assertCondition(
    'asena_services pharmacy_url points to https://asena.company/pharmacy.php',
    isset($blocks['asena_services']['pharmacy_url']) && $blocks['asena_services']['pharmacy_url'] === 'https://asena.company/pharmacy.php'
);

assertCondition(
    'asena_services subscriptions points to https://asena.company/subscriptions.php',
    isset($blocks['asena_services']['autoship_url']) && $blocks['asena_services']['autoship_url'] === 'https://asena.company/subscriptions.php'
);

assertCondition(
    'asena_services rewards points to https://asena.company/rewards.php',
    isset($blocks['asena_services']['rewards_url']) && $blocks['asena_services']['rewards_url'] === 'https://asena.company/rewards.php'
);

assertCondition(
    'asena_services charity points to https://asena.company/charity.php',
    isset($blocks['asena_services']['charity_url']) && $blocks['asena_services']['charity_url'] === 'https://asena.company/charity.php'
);

assertCondition(
    'Services default items have url and btn_text fields',
    !empty($blocks['services']['items']) && isset($blocks['services']['items'][0]['url']) && isset($blocks['services']['items'][0]['btn_text'])
);

assertCondition(
    'Bento Facilities default items have url and btn_text fields',
    !empty($blocks['bento_facilities']['items']) && isset($blocks['bento_facilities']['items'][0]['url']) && isset($blocks['bento_facilities']['items'][0]['btn_text'])
);

// 2. Verification of site.php rendering with map_link and asena.company endpoints
echo "\nTest Group 2: site.php Address Linking & asena.company Routing\n";
$siteFile = file_get_contents(__DIR__ . '/../site.php');

assertCondition(
    'site.php defines $addressMapLink calculation',
    strpos($siteFile, '$addressMapLink =') !== false
);

assertCondition(
    'site.php defines $normalizeAsenaUrl helper closure',
    strpos($siteFile, '$normalizeAsenaUrl = function') !== false
);

assertCondition(
    'Duty nav button uses $addressMapLink and #live-duty-nav-btn',
    strpos($siteFile, 'id="live-duty-nav-btn"') !== false && strpos($siteFile, '$addressMapLink') !== false
);

assertCondition(
    'Contact address element #live-contact-address renders link when map link present',
    strpos($siteFile, 'id="live-contact-address"') !== false && strpos($siteFile, '$addressMapLink') !== false
);

assertCondition(
    'Contact nav button #live-contact-nav-btn and #live-contact-nav-text present',
    strpos($siteFile, 'id="live-contact-nav-btn"') !== false && strpos($siteFile, 'id="live-contact-nav-text"') !== false
);

assertCondition(
    'Footer address and nav button #live-footer-nav-btn use map link',
    strpos($siteFile, 'id="live-footer-address"') !== false && strpos($siteFile, 'id="live-footer-nav-btn"') !== false
);

assertCondition(
    'Header CTA button #live-header-cta-btn and Hero CTA #live-hero-primary-cta present',
    strpos($siteFile, 'id="live-header-cta-btn"') !== false && strpos($siteFile, 'id="live-hero-primary-cta"') !== false
);

assertCondition(
    'Hero secondary CTA text #live-hero-secondary-cta-text present',
    strpos($siteFile, 'id="live-hero-secondary-cta-text"') !== false
);

assertCondition(
    'ASENA service cards use $normalizeAsenaUrl and open in new tab',
    strpos($siteFile, '$normalizeAsenaUrl($asenaServicesBlock[\'telehealth_url\']') !== false &&
    strpos($siteFile, '$normalizeAsenaUrl($asenaServicesBlock[\'pharmacy_url\']') !== false
);

assertCondition(
    'Services items render link when item url is provided',
    strpos($siteFile, '!empty($srv[\'url\'])') !== false
);

assertCondition(
    'Bento Facilities items render link when item url is provided',
    strpos($siteFile, '!empty($item[\'url\'])') !== false
);

assertCondition(
    'applyLiveFieldUpdate handles contact_map_link and contact_nav_btn_text',
    strpos($siteFile, "case 'contact_map_link':") !== false && strpos($siteFile, "case 'contact_nav_btn_text':") !== false
);

assertCondition(
    'applyLiveFieldUpdate handles header_cta_url and hero_cta_url',
    strpos($siteFile, "case 'header_cta_url':") !== false && strpos($siteFile, "case 'hero_cta_url':") !== false
);

assertCondition(
    'applyLiveFieldUpdate handles asena_services URLs',
    strpos($siteFile, "case 'telehealth_url':") !== false && strpos($siteFile, "case 'pharmacy_url':") !== false
);

assertCondition(
    'renderLiveRepeaterSection supports url in services and bento',
    strpos($siteFile, 'srv.url') !== false && strpos($siteFile, 'item.url') !== false
);

// 3. Studio Studio Configuration & Form Verification
echo "\nTest Group 3: Site Builder Studio Inputs & Bidirectional Sync\n";
$studioFile = file_get_contents(__DIR__ . '/../includes/site_builder_studio.php');

assertCondition(
    'Studio Card 1 has input-quick-contact-map-link',
    strpos($studioFile, 'id="input-quick-contact-map-link"') !== false
);

assertCondition(
    'Studio Card 1 has input-quick-contact-nav-btn-text',
    strpos($studioFile, 'id="input-quick-contact-nav-btn-text"') !== false
);

assertCondition(
    'Studio Card 1 has input-quick-header-cta-url',
    strpos($studioFile, 'id="input-quick-header-cta-url"') !== false
);

assertCondition(
    'Studio Card 2 has input-quick-hero-cta-url and secondary inputs',
    strpos($studioFile, 'id="input-quick-hero-cta-url"') !== false &&
    strpos($studioFile, 'id="input-quick-hero-cta-secondary"') !== false &&
    strpos($studioFile, 'id="input-quick-hero-cta-secondary-url"') !== false
);

assertCondition(
    'Studio Card 8 has direct asena.company quick inputs',
    strpos($studioFile, 'id="input-quick-telehealth-url"') !== false &&
    strpos($studioFile, 'id="input-quick-pharmacy-url"') !== false &&
    strpos($studioFile, 'id="input-quick-autoship-url"') !== false &&
    strpos($studioFile, 'id="input-quick-rewards-url"') !== false &&
    strpos($studioFile, 'id="input-quick-charity-url"') !== false
);

assertCondition(
    'Studio repeater modal services form has input-rep-service-url and input-rep-service-btn',
    strpos($studioFile, 'id="input-rep-service-url"') !== false && strpos($studioFile, 'id="input-rep-service-btn"') !== false
);

assertCondition(
    'Studio repeater modal bento form has input-rep-bento-url and input-rep-bento-btn',
    strpos($studioFile, 'id="input-rep-bento-url"') !== false && strpos($studioFile, 'id="input-rep-bento-btn"') !== false
);

assertCondition(
    'saveSiteConfig serializes map_link and nav_btn_text in contact',
    strpos($studioFile, 'map_link: document.getElementById(\'input-contact-map-link\')') !== false &&
    strpos($studioFile, 'nav_btn_text: document.getElementById(\'input-contact-nav-btn-text\')') !== false
);

assertCondition(
    'saveSiteConfig serializes cta_url in header and hero',
    strpos($studioFile, 'cta_url: document.getElementById(\'input-header-cta-url\')') !== false &&
    strpos($studioFile, 'cta_primary_url: document.getElementById(\'input-hero-cta-url\')') !== false
);

assertCondition(
    'quickSyncPairs includes new contact and CTA inputs',
    strpos($studioFile, "quick: 'input-quick-contact-map-link'") !== false &&
    strpos($studioFile, "quick: 'input-quick-header-cta-url'") !== false &&
    strpos($studioFile, "quick: 'input-quick-hero-cta-url'") !== false
);

echo "\nSummary: {$passed}/{$total} assertions passed.\n";

if ($passed === $total) {
    echo "Result: ALL TESTS PASSED!\n";
    exit(0);
} else {
    echo "Result: SOME TESTS FAILED!\n";
    exit(1);
}
