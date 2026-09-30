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
        assertTrue(empty($b['emergency_bar']['enabled']), "Basic tier disables emergency bar");
        assertTrue(empty($b['duty_hours']['enabled']), "Basic tier disables duty hours");
        assertTrue(empty($b['cost_calculator']['enabled']), "Basic tier disables cost calculator");
        assertTrue(empty($b['faq']['enabled']), "Basic tier disables faq");
        assertTrue(!empty($b['navigation_hub']['enabled']), "Basic tier keeps navigation hub");
        assertTrue(!empty($b['hero']['enabled']), "Basic tier keeps hero");
        assertTrue(!empty($b['booking']['enabled']), "Basic tier keeps booking");
        assertTrue(!empty($b['sticky_mobile_bar']['enabled']), "Basic tier keeps sticky mobile bar");
    } elseif ($tier === 'standard') {
        assertTrue(!empty($b['stats_strip']['enabled']), "Standard tier enables stats strip");
        assertTrue(!empty($b['articles']['enabled']), "Standard tier enables articles");
        assertTrue(!empty($b['loyalty_club']['enabled']), "Standard tier enables loyalty club");
        assertTrue(!empty($b['reviews']['enabled']), "Standard tier enables reviews");
        assertTrue(!empty($b['duty_hours']['enabled']), "Standard tier enables duty hours");
        assertTrue(!empty($b['cost_calculator']['enabled']), "Standard tier enables cost calculator for org/doctor");
        assertTrue(!empty($b['faq']['enabled']), "Standard tier enables faq");
        assertTrue(empty($b['bento_facilities']['enabled']), "Standard tier leaves bento disabled");
    } elseif ($tier === 'premium') {
        assertTrue(!empty($b['bento_facilities']['enabled']), "Premium tier enables bento facilities");
        assertTrue(!empty($b['telehealth_launcher']['enabled']), "Premium tier enables telehealth launcher");
        assertTrue(!empty($b['autoship_showcase']['enabled']), "Premium tier enables autoship showcase");
        assertTrue(!empty($b['emergency_bar']['enabled']), "Premium tier enables emergency bar for org/doctor");
        assertTrue(!empty($b['duty_hours']['enabled']), "Premium tier enables duty hours");
        assertTrue(!empty($b['cost_calculator']['enabled']), "Premium tier enables cost calculator");
        assertTrue(!empty($b['faq']['enabled']), "Premium tier enables faq");
    } elseif ($tier === 'pharmacy') {
        assertTrue(!empty($b['rx_prescription_box']['enabled']), "Pharmacy tier enables rx prescription box");
        assertTrue(!empty($b['autoship_showcase']['enabled']), "Pharmacy tier enables autoship showcase");
        assertTrue(!empty($b['duty_hours']['enabled']), "Pharmacy tier enables duty hours");
        assertTrue(!empty($b['faq']['enabled']), "Pharmacy tier enables faq");
        assertTrue(empty($b['cost_calculator']['enabled']), "Pharmacy tier disables clinical calculator");
    } elseif ($tier === 'enterprise') {
        assertTrue(!empty($b['bento_facilities']['enabled']), "Enterprise tier enables bento facilities");
        assertTrue(!empty($b['doctors_roster']['enabled']), "Enterprise tier enables doctors roster");
        assertTrue(!empty($b['telehealth_launcher']['enabled']), "Enterprise tier enables telehealth");
        assertTrue(!empty($b['rx_prescription_box']['enabled']), "Enterprise tier enables rx box");
        assertTrue(!empty($b['autoship_showcase']['enabled']), "Enterprise tier enables autoship");
        assertTrue(!empty($b['articles']['enabled']), "Enterprise tier enables articles");
        assertTrue(!empty($b['loyalty_club']['enabled']), "Enterprise tier enables loyalty club");
        assertTrue(!empty($b['reviews']['enabled']), "Enterprise tier enables reviews");
        assertTrue(!empty($b['emergency_bar']['enabled']), "Enterprise tier enables emergency bar for org/doctor");
        assertTrue(!empty($b['duty_hours']['enabled']), "Enterprise tier enables duty hours");
        assertTrue(!empty($b['cost_calculator']['enabled']), "Enterprise tier enables cost calculator");
        assertTrue(!empty($b['faq']['enabled']), "Enterprise tier enables faq");
        assertTrue(!empty($b['navigation_hub']['enabled']), "Enterprise tier enables navigation hub");
    }
}

// 2. Test applyTierPreset
$baseLayout = $service->buildDefaultLayout('organization', ['name' => 'کلینیک نمونه'], 'enterprise');
$presetBasic = $service->applyTierPreset('organization', 'basic', $baseLayout);
assertTrue(empty($presetBasic['blocks']['bento_facilities']['enabled']), "applyTierPreset basic disables bento");
assertTrue(empty($presetBasic['blocks']['articles']['enabled']), "applyTierPreset basic disables articles");
assertTrue(empty($presetBasic['blocks']['cost_calculator']['enabled']), "applyTierPreset basic disables cost_calculator");
assertTrue(empty($presetBasic['blocks']['emergency_bar']['enabled']), "applyTierPreset basic disables emergency_bar");

$presetEnterprise = $service->applyTierPreset('organization', 'enterprise', $presetBasic);
assertTrue(!empty($presetEnterprise['blocks']['bento_facilities']['enabled']), "applyTierPreset enterprise re-enables bento");
assertTrue(!empty($presetEnterprise['blocks']['articles']['enabled']), "applyTierPreset enterprise re-enables articles");
assertTrue(!empty($presetEnterprise['blocks']['cost_calculator']['enabled']), "applyTierPreset enterprise re-enables cost_calculator");
assertTrue(!empty($presetEnterprise['blocks']['emergency_bar']['enabled']), "applyTierPreset enterprise re-enables emergency_bar");

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

// 5. Test getTenantFaqs
$faqsClinic = $service->getTenantFaqs('organization');
assertTrue(count($faqsClinic) >= 5, "getTenantFaqs returns at least 5 clinic FAQs");
assertTrue(!empty($faqsClinic[0]['q']) && !empty($faqsClinic[0]['a']), "FAQ items have question and answer");

$faqsPharmacy = $service->getTenantFaqs('pharmacist');
assertTrue(count($faqsPharmacy) >= 4, "getTenantFaqs returns pharmacy FAQs");

// 6. Test getCostCalculatorConfig
$calcConfig = $service->getCostCalculatorConfig('doctor');
assertTrue(!empty($calcConfig['pet_types']), "Cost calculator provides pet types");
assertTrue(!empty($calcConfig['services']), "Cost calculator provides clinical services");
assertTrue(count($calcConfig['services']) >= 4, "Cost calculator has at least 4 base services");

echo "=== All $assertCount Tests Passed Successfully! ===\n";
