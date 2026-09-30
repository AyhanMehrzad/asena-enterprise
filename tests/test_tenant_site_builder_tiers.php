<?php
/**
 * Test Suite: ASENA Tenant Showcase & Site Builder 5-Tier Verification
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/TenantSiteService.php';

echo "=== ASENA Tenant Site Builder 5-Tier Test ===\n";

$service = App::tenantSite();
$assertCount = 0;

function assertTrue($cond, $msg) {
    global $assertCount;
    if ($cond) {
        echo "  [PASS] $msg\n";
        $assertCount++;
    } else {
        echo "  [FAIL] $msg\n";
        exit(1);
    }
}

// 1. Test buildDefaultLayout for 5 Tiers
$tiers = ['basic', 'standard', 'premium', 'pharmacy', 'enterprise'];

foreach ($tiers as $tier) {
    $layout = $service->buildDefaultLayout('organization', ['name' => "کلینیک آزمایشی $tier"], $tier);
    assertTrue(!empty($layout['blocks']), "Layout built for tier: $tier");

    $b = $layout['blocks'];
    if ($tier === 'basic') {
        assertTrue(empty($b['stats_strip']['enabled']), "Basic tier disables stats strip");
        assertTrue(empty($b['bento_facilities']['enabled']), "Basic tier disables bento facilities");
        assertTrue(empty($b['articles']['enabled']), "Basic tier disables articles");
        assertTrue(empty($b['loyalty_club']['enabled']), "Basic tier disables loyalty club");
        assertTrue(empty($b['reviews']['enabled']), "Basic tier disables reviews");
        assertTrue(!empty($b['hero']['enabled']), "Basic tier keeps hero");
        assertTrue(!empty($b['booking']['enabled']), "Basic tier keeps booking");
        assertTrue(!empty($b['sticky_mobile_bar']['enabled']), "Basic tier keeps sticky mobile bar");
    } elseif ($tier === 'standard') {
        assertTrue(!empty($b['stats_strip']['enabled']), "Standard tier enables stats strip");
        assertTrue(!empty($b['articles']['enabled']), "Standard tier enables articles");
        assertTrue(!empty($b['loyalty_club']['enabled']), "Standard tier enables loyalty club");
        assertTrue(!empty($b['reviews']['enabled']), "Standard tier enables reviews");
        assertTrue(empty($b['bento_facilities']['enabled']), "Standard tier leaves bento disabled");
    } elseif ($tier === 'premium') {
        assertTrue(!empty($b['bento_facilities']['enabled']), "Premium tier enables bento facilities");
        assertTrue(!empty($b['telehealth_launcher']['enabled']), "Premium tier enables telehealth launcher");
        assertTrue(!empty($b['autoship_showcase']['enabled']), "Premium tier enables autoship showcase");
    } elseif ($tier === 'pharmacy') {
        assertTrue(!empty($b['rx_prescription_box']['enabled']), "Pharmacy tier enables rx prescription box");
        assertTrue(!empty($b['autoship_showcase']['enabled']), "Pharmacy tier enables autoship showcase");
    } elseif ($tier === 'enterprise') {
        assertTrue(!empty($b['bento_facilities']['enabled']), "Enterprise tier enables bento facilities");
        assertTrue(!empty($b['doctors_roster']['enabled']), "Enterprise tier enables doctors roster");
        assertTrue(!empty($b['telehealth_launcher']['enabled']), "Enterprise tier enables telehealth");
        assertTrue(!empty($b['rx_prescription_box']['enabled']), "Enterprise tier enables rx box");
        assertTrue(!empty($b['autoship_showcase']['enabled']), "Enterprise tier enables autoship");
        assertTrue(!empty($b['articles']['enabled']), "Enterprise tier enables articles");
        assertTrue(!empty($b['loyalty_club']['enabled']), "Enterprise tier enables loyalty club");
        assertTrue(!empty($b['reviews']['enabled']), "Enterprise tier enables reviews");
    }
}

// 2. Test applyTierPreset
$baseLayout = $service->buildDefaultLayout('organization', ['name' => 'کلینیک نمونه'], 'enterprise');
$presetBasic = $service->applyTierPreset('organization', 'basic', $baseLayout);
assertTrue(empty($presetBasic['blocks']['bento_facilities']['enabled']), "applyTierPreset basic disables bento");
assertTrue(empty($presetBasic['blocks']['articles']['enabled']), "applyTierPreset basic disables articles");

$presetEnterprise = $service->applyTierPreset('organization', 'enterprise', $presetBasic);
assertTrue(!empty($presetEnterprise['blocks']['bento_facilities']['enabled']), "applyTierPreset enterprise re-enables bento");
assertTrue(!empty($presetEnterprise['blocks']['articles']['enabled']), "applyTierPreset enterprise re-enables articles");

// 3. Test getTenantArticles and getTenantReviews
$articles = $service->getTenantArticles(3);
assertTrue(count($articles) === 3, "getTenantArticles returns 3 clinical articles");
assertTrue(!empty($articles[0]['title']), "Articles contain valid title: " . $articles[0]['title']);

$reviews = $service->getTenantReviews('organization', 1, 3);
assertTrue(count($reviews) === 3, "getTenantReviews returns 3 verified reviews");
assertTrue(!empty($reviews[0]['pet_info']), "Reviews contain pet details: " . $reviews[0]['pet_info']);

// 4. Test Doctors Roster
$docs = $service->getOrganizationDoctors(1);
assertTrue(is_array($docs), "getOrganizationDoctors returns array");

echo "=== All $assertCount Tests Passed Successfully! ===\n";
