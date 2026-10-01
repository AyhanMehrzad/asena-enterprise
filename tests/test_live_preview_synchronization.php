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
assertCondition(str_contains($studioContent, "applyColorSwatch("), "Studio provides quick brand harmonized color swatches");
assertCondition(str_contains($studioContent, "resetBrandColorsToDefault("), "Studio has reset brand colors to default function");
assertCondition(str_contains($studioContent, "id=\"input-primary-color\""), "Studio has primary brand color picker");
assertCondition(str_contains($studioContent, "id=\"input-secondary-color\""), "Studio has secondary accent color picker");
assertCondition(str_contains($studioContent, "id: 'input-calc-badge'"), "Studio binds input-calc-badge to live update");
assertCondition(str_contains($studioContent, "id: 'input-calc-subtitle'"), "Studio binds input-calc-subtitle to live update");

// 2. Site side verification
assertCondition(str_contains($siteContent, 'window.applyLiveFieldUpdate = function'), "Site.php exposes applyLiveFieldUpdate function in preview mode");
assertCondition(str_contains($siteContent, "e.data.type === 'STUDIO_LIVE_UPDATE'"), "Site.php listens for STUDIO_LIVE_UPDATE postMessage");
assertCondition(str_contains($siteContent, "case 'site_title':"), "Site.php handles site_title live update");
assertCondition(str_contains($siteContent, "case 'theme_palette':"), "Site.php handles theme_palette live update");
assertCondition(str_contains($siteContent, "case 'custom_colors':"), "Site.php handles custom_colors live update with contrast protection");
assertCondition(str_contains($siteContent, "case 'block_toggle':"), "Site.php handles block_toggle live update");
assertCondition(str_contains($siteContent, "case 'calc_badge':"), "Site.php handles calc_badge live update");
assertCondition(str_contains($siteContent, "case 'calc_heading':"), "Site.php handles calc_heading live update");
assertCondition(str_contains($siteContent, "case 'calc_subtitle':"), "Site.php handles calc_subtitle live update");
assertCondition(str_contains($siteContent, "case 'calc_discount':"), "Site.php handles calc_discount live update");
assertCondition(str_contains($siteContent, "case 'scroll_to_block':"), "Site.php handles scroll_to_block live update");
assertCondition(str_contains($siteContent, "case 'storefront_limit':"), "Site.php handles storefront_limit live update");
assertCondition(str_contains($siteContent, "case 'reorder_blocks':"), "Site.php handles reorder_blocks live update");
assertCondition(str_contains($siteContent, "case 'ambient_mode':"), "Site.php handles ambient_mode live update");
assertCondition(str_contains($siteContent, "case 'trust_anchor_toggle':"), "Site.php handles trust_anchor_toggle live update");
assertCondition(str_contains($siteContent, "case 'before_after_label_before':"), "Site.php handles before/after labels live update");
assertCondition(str_contains($siteContent, "id=\"live-trust-anchor\""), "Site.php renders symbiotic trust anchor element");
assertCondition(str_contains($siteContent, "id=\"before-after\""), "Site.php renders Before/After comparison module");
assertCondition(str_contains($siteContent, "id=\"live-calc-badge\""), "Site.php renders live-calc-badge element");
assertCondition(str_contains($siteContent, "id=\"live-calc-subtitle\""), "Site.php renders live-calc-subtitle element");

// 3. Click to edit & WYSIWYG Direct Preview Editing
assertCondition(str_contains($siteContent, "type: 'BLOCK_CLICKED'"), "Site.php emits BLOCK_CLICKED on block click");
assertCondition(str_contains($studioContent, "event.data.type === 'BLOCK_CLICKED'"), "Studio handles BLOCK_CLICKED event to open accordion");
assertCondition(str_contains($siteContent, 'data-studio-editable'), "Site.php marks live elements with data-studio-editable");
assertCondition(str_contains($siteContent, 'initPreviewDirectEditing'), "Site.php initializes direct WYSIWYG click-to-edit");
assertCondition(str_contains($siteContent, "type: 'FIELD_UPDATED_FROM_PREVIEW'"), "Site.php dispatches FIELD_UPDATED_FROM_PREVIEW on inline input");
assertCondition(str_contains($siteContent, 'studio-block-floating-bar'), "Site.php injects floating quick-action toolbar on hoverable blocks");

// 4. Studio Simplified Cockpit & Quick-Editor UX
assertCondition(str_contains($studioContent, 'id="tab-btn-quick"'), "Studio has dedicated Quick Edit tab button");
assertCondition(str_contains($studioContent, 'id="tab-panel-quick"'), "Studio renders simplified quick cockpit panel");
assertCondition(str_contains($studioContent, 'id="studio-quick-search"'), "Studio has smart instant search bar");
assertCondition(str_contains($studioContent, 'handleStudioQuickSearch('), "Studio has handleStudioQuickSearch function");
assertCondition(str_contains($studioContent, 'id="spotlight-quick-modal"'), "Studio has Spotlight Quick-Editor Modal");
assertCondition(str_contains($studioContent, 'openSpotlightModal('), "Studio has openSpotlightModal function");
assertCondition(str_contains($studioContent, 'applyClinicalCopyTemplate('), "Studio provides 1-click clinical copy generation");
assertCondition(str_contains($studioContent, "event.data.type === 'FIELD_UPDATED_FROM_PREVIEW'"), "Studio synchronizes inline preview edits back to sidebar");
assertCondition(str_contains($studioContent, "event.data.type === 'OPEN_QUICK_EDIT_MODAL'"), "Studio handles OPEN_QUICK_EDIT_MODAL message from preview toolbar");
assertCondition(str_contains($studioContent, "event.data.type === 'TOGGLE_BLOCK_FROM_PREVIEW'"), "Studio handles TOGGLE_BLOCK_FROM_PREVIEW message from preview toolbar");
assertCondition(str_contains($studioContent, "event.data.type === 'MOVE_BLOCK_FROM_PREVIEW'"), "Studio handles MOVE_BLOCK_FROM_PREVIEW message from preview toolbar");
assertCondition(str_contains($studioContent, 'quickSyncPairs'), "Studio provides two-way synchronization between quick inputs and main inputs");

if (empty($errors)) {
    echo "=== All {$passes} Tests Passed Successfully! ===\n";
    exit(0);
} else {
    echo "=== " . count($errors) . " Tests Failed! ===\n";
    exit(1);
}
