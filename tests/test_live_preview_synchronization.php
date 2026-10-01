<?php
/**
 * Test: Real-Time Zero-Refresh Live Preview Synchronization
 */

$studioContent = file_get_contents(__DIR__ . '/../includes/site_builder_studio.php');
$siteContent = file_get_contents(__DIR__ . '/../site.php');

$errors = [];
$passes = 0;

function assertCondition($cond, $msg) {
    global $errors, $passes;
    if ($cond) {
        echo "  [PASS] $msg\n";
        $passes++;
    } else {
        echo "  [FAIL] $msg\n";
        $errors[] = $msg;
    }
}

echo "=== Testing Real-Time Zero-Refresh Live Preview Synchronization ===\n";

// 1. Studio side verification
assertCondition(str_contains($studioContent, 'function sendLiveUpdate('), "Studio has sendLiveUpdate function");
assertCondition(str_contains($studioContent, "type: 'STUDIO_LIVE_UPDATE'"), "Studio sends STUDIO_LIVE_UPDATE message");
assertCondition(str_contains($studioContent, 'function updatePalettePreview('), "Studio has updatePalettePreview function");
assertCondition(str_contains($studioContent, 'function initLiveStudioBindings('), "Studio has initLiveStudioBindings function");
assertCondition(str_contains($studioContent, 'initLiveStudioBindings();'), "Studio initializes live bindings on load");
assertCondition(str_contains($studioContent, "sendLiveUpdate('reorder_blocks'"), "Studio reorders blocks live in preview");
assertCondition(str_contains($studioContent, "sendLiveUpdate('scroll_to_block'"), "Studio scrolls preview to active block on accordion toggle");
assertCondition(str_contains($studioContent, "sendLiveUpdate('site_logo'"), "Studio updates logo live after upload");
assertCondition(str_contains($studioContent, "sendLiveUpdate('hero_image'"), "Studio updates hero image live after upload");
assertCondition(str_contains($studioContent, "value=\"aurora\""), "Studio supports aurora prestige palette");
assertCondition(str_contains($studioContent, 'name="ambient_mode"'), "Studio has ambient lighting mode selector");
assertCondition(str_contains($studioContent, 'input-trust-anchor-toggle'), "Studio has symbiotic trust anchor toggle");
assertCondition(str_contains($studioContent, "section-before_after"), "Studio has Before/After comparison accordion");

// 2. Site side verification
assertCondition(str_contains($siteContent, 'window.applyLiveFieldUpdate = function'), "Site.php exposes applyLiveFieldUpdate function in preview mode");
assertCondition(str_contains($siteContent, "e.data.type === 'STUDIO_LIVE_UPDATE'"), "Site.php listens for STUDIO_LIVE_UPDATE postMessage");
assertCondition(str_contains($siteContent, "case 'site_title':"), "Site.php handles site_title live update");
assertCondition(str_contains($siteContent, "case 'theme_palette':"), "Site.php handles theme_palette live update");
assertCondition(str_contains($siteContent, "case 'block_toggle':"), "Site.php handles block_toggle live update");
assertCondition(str_contains($siteContent, "case 'calc_discount':"), "Site.php handles calc_discount live update");
assertCondition(str_contains($siteContent, "case 'scroll_to_block':"), "Site.php handles scroll_to_block live update");
assertCondition(str_contains($siteContent, "case 'storefront_limit':"), "Site.php handles storefront_limit live update");
assertCondition(str_contains($siteContent, "case 'reorder_blocks':"), "Site.php handles reorder_blocks live update");
assertCondition(str_contains($siteContent, "case 'ambient_mode':"), "Site.php handles ambient_mode live update");
assertCondition(str_contains($siteContent, "case 'trust_anchor_toggle':"), "Site.php handles trust_anchor_toggle live update");
assertCondition(str_contains($siteContent, "case 'before_after_label_before':"), "Site.php handles before/after labels live update");
assertCondition(str_contains($siteContent, "id=\"live-trust-anchor\""), "Site.php renders symbiotic trust anchor element");
assertCondition(str_contains($siteContent, "id=\"before-after\""), "Site.php renders Before/After comparison module");

// 3. Click to edit
assertCondition(str_contains($siteContent, "type: 'BLOCK_CLICKED'"), "Site.php emits BLOCK_CLICKED on block click");
assertCondition(str_contains($studioContent, "event.data.type === 'BLOCK_CLICKED'"), "Studio handles BLOCK_CLICKED event to open accordion");

if (empty($errors)) {
    echo "=== All {$passes} Tests Passed Successfully! ===\n";
    exit(0);
} else {
    echo "=== " . count($errors) . " Tests Failed! ===\n";
    exit(1);
}
