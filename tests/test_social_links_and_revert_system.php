<?php
/**
 * Test Suite: Social Media Links, Digital Business Card Hub & Accidental Removal Revert System
 */

require_once __DIR__ . '/../includes/TenantSiteService.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$service = new TenantSiteService($pdo);

echo "=========================================================\n";
echo "TEST 1: TenantSiteService default layout & social links\n";
echo "=========================================================\n";

$defaultLayout = $service->buildDefaultLayout('doctor', ['site_title' => 'کلینیک دکتر رادمنش']);
assert(!empty($defaultLayout['social_links']), "Social links must be present in default layout root");
assert(!empty($defaultLayout['blocks']['social_links']), "Social links block must be present in default blocks");
assert(is_array($defaultLayout['social_links']), "Social links must be an array");

$platforms = array_column($defaultLayout['social_links'], 'platform');
echo "Found default platforms: " . implode(', ', $platforms) . "\n";
assert(in_array('instagram', $platforms), "Instagram must be in default social links");
assert(in_array('telegram', $platforms), "Telegram must be in default social links");
assert(in_array('whatsapp', $platforms), "WhatsApp must be in default social links");
assert(in_array('bale', $platforms), "Bale messenger must be in default social links");
assert(in_array('eitaa', $platforms), "Eitaa messenger must be in default social links");
echo "✓ TEST 1 PASSED\n\n";

echo "=========================================================\n";
echo "TEST 2: getDefaultSectionItems for 1-click restore\n";
echo "=========================================================\n";

$socialItems = $service->getDefaultSectionItems('doctor', 'social_links');
assert(count($socialItems) >= 4, "Must return at least 4 default social links");

$serviceItems = $service->getDefaultSectionItems('doctor', 'services');
assert(!empty($serviceItems), "Must return default service items");

$facilityItems = $service->getDefaultSectionItems('doctor', 'bento_facilities');
assert(!empty($facilityItems), "Must return default bento facilities");

$faqItems = $service->getDefaultSectionItems('doctor', 'faq');
assert(!empty($faqItems), "Must return default FAQs");

$navItems = $service->getDefaultSectionItems('doctor', 'navigation_hub');
assert(!empty($navItems), "Must return default nav apps");

$statItems = $service->getDefaultSectionItems('doctor', 'stats_strip');
assert(!empty($statItems), "Must return default stats");
echo "✓ TEST 2 PASSED: All sections have default restore items\n\n";

echo "=========================================================\n";
echo "TEST 3: site.php rendering & vCard with X-SOCIALPROFILE\n";
echo "=========================================================\n";

$sitePhp = file_get_contents(__DIR__ . '/../site.php');
assert(strpos($sitePhp, 'data-block-id="social_links"') !== false, "site.php must render data-block-id='social_links'");
assert(strpos($sitePhp, 'id="vcard-social-links-list"') !== false, "site.php must contain #vcard-social-links-list in vCard modal");
assert(strpos($sitePhp, 'id="stand-social-links-list"') !== false, "site.php must contain #stand-social-links-list in countertop stand");
assert(strpos($sitePhp, 'X-SOCIALPROFILE') !== false, "site.php vCard must export X-SOCIALPROFILE for phone contacts");
assert(strpos($sitePhp, 'function copySocialHandle') !== false, "site.php must include 1-tap copySocialHandle");
assert(strpos($sitePhp, 'openVCardModal()') !== false, "site.php must retain openVCardModal");
assert(strpos($sitePhp, 'downloadVCard()') !== false, "site.php must retain downloadVCard");
assert(strpos($sitePhp, 'id="vcard-modal"') !== false, "site.php must retain #vcard-modal");
echo "✓ TEST 3 PASSED: site.php contains rich digital visit card and RFC social profiles\n\n";

echo "=========================================================\n";
echo "TEST 4: site_builder_studio.php Undo/Redo & Revert Engine\n";
echo "=========================================================\n";

$studioPhp = file_get_contents(__DIR__ . '/../includes/site_builder_studio.php');
assert(strpos($studioPhp, 'id="btn-studio-undo"') !== false, "Studio top bar must have #btn-studio-undo");
assert(strpos($studioPhp, 'id="btn-studio-redo"') !== false, "Studio top bar must have #btn-studio-redo");
assert(strpos($studioPhp, 'id="section-social_links"') !== false, "Studio must have #section-social_links accordion");
assert(strpos($studioPhp, 'id="studio-repeater-list-social_links"') !== false, "Studio must have #studio-repeater-list-social_links");
assert(strpos($studioPhp, 'id="form-repeater-social_links"') !== false, "Studio modal must have #form-repeater-social_links");
assert(strpos($studioPhp, 'input-rep-social-platform') !== false, "Studio modal must have platform selector");
assert(strpos($studioPhp, 'socialPresets') !== false, "Studio script must define socialPresets");
assert(strpos($studioPhp, 'applySocialPreset') !== false, "Studio script must define applySocialPreset");
assert(strpos($studioPhp, 'const studioHistory =') !== false, "Studio script must define studioHistory engine");
assert(strpos($studioPhp, 'restoreSectionDefaults') !== false, "Studio must support restoreSectionDefaults");
assert(strpos($studioPhp, 'revertLastRemovedItem') !== false, "Studio must support revertLastRemovedItem");
assert(strpos($studioPhp, 'showInteractiveToast') !== false, "Studio must support interactive Undo toast");
assert(strpos($studioPhp, 'studioHistory.undo()') !== false, "Studio must wire undo to studioHistory.undo()");
assert(strpos($studioPhp, 'studioHistory.redo()') !== false, "Studio must wire redo to studioHistory.redo()");
echo "✓ TEST 4 PASSED: Studio contains full undo/redo history engine and interactive revert toast\n\n";

echo "=========================================================\n";
echo "TEST 5: actions/site_builder_action.php get_section_defaults\n";
echo "=========================================================\n";

$actionPhp = file_get_contents(__DIR__ . '/../actions/site_builder_action.php');
assert(strpos($actionPhp, "action === 'get_section_defaults'") !== false, "Action must support get_section_defaults endpoint");
echo "✓ TEST 5 PASSED: Backend endpoint for default restore exists\n\n";

echo "ALL TESTS PASSED SUCCESSFULLY! (100%)\n";
