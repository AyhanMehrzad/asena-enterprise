<?php
/**
 * Test Suite: Digital VCard & Dynamic Scannable QR System
 * Validates:
 * 1. Card 6 interactive trigger & modal bindings
 * 2. Real QR code SVG generation for website URL and vCard
 * 3. Server-side vCard direct download endpoint (?download_vcard=1) with UTF-8 BOM
 * 4. Server-side QR SVG download endpoint (?download_qr=...)
 * 5. Dual-mode tab switching & interactive controls
 * 6. Printable reception counter stand template
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/App.php';
require_once __DIR__ . '/../includes/QrCode.php';

$passed = 0;
$failed = 0;

function it(string $desc, bool $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$desc}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$desc}\n";
        $failed++;
    }
}

echo "=== Testing Digital VCard & Dynamic QR Code System ===\n";

// 1. QrCode generator test
$testUrl = 'http://localhost:8085/site.php?slug=dr-alavi';
$svgUrl = QrCode::svg($testUrl, 200, '#001a48', '#ffffff', 2);
it("QrCode::svg generates valid SVG markup for URL", str_contains($svgUrl, '<svg') && str_contains($svgUrl, '</svg>') && str_contains($svgUrl, 'width="200"'));

$testVcard = "BEGIN:VCARD\nVERSION:3.0\nFN;CHARSET=UTF-8:کلینیک علوی\nTEL:02188888888\nURL:{$testUrl}\nEND:VCARD";
$svgVcard = QrCode::svg($testVcard, 220, '#001a48', '#ffffff', 2);
it("QrCode::svg generates valid SVG markup for Persian vCard", str_contains($svgVcard, '<svg') && str_contains($svgVcard, '</svg>') && str_contains($svgVcard, 'width="220"'));

// 2. Fetch site.php output for existing site 'dr-alavi'
$html = file_get_contents('http://127.0.0.1:8085/site.php?slug=dr-alavi');

it("Site.php renders Card 6 with openVCardModal trigger", str_contains($html, 'onclick="openVCardModal()"') && str_contains($html, 'id="live-vcard-title"'));
it("Card 6 button is clickable and styled", str_contains($html, 'id="live-vcard-btn"') && str_contains($html, 'qr_code_2'));

it("Site.php renders #vcard-modal with backdrop blur", str_contains($html, 'id="vcard-modal"') && str_contains($html, 'backdrop-blur-md'));
it("Modal has dual QR tabs (vCard & website)", str_contains($html, 'id="tab-qr-vcard"') && str_contains($html, 'id="tab-qr-website"'));
it("Modal contains real SVG in qr-vcard-container", str_contains($html, 'id="qr-vcard-container"') && str_contains($html, '<svg'));
it("Modal contains real SVG in qr-website-container", str_contains($html, 'id="qr-website-container"') && str_contains($html, '<svg'));
it("Modal provides 1-tap download VCF button", str_contains($html, 'onclick="downloadVCard()"'));
it("Modal provides QR image download button", str_contains($html, 'onclick="downloadQrImage()"'));
it("Modal provides print reception stand button", str_contains($html, 'onclick="printVCardStand()"'));
it("Modal provides native mobile share button", str_contains($html, 'onclick="nativeShare()"'));

// 3. Test printable stand template
it("Site.php contains #vcard-printable-stand plaque", str_contains($html, 'id="vcard-printable-stand"') && str_contains($html, 'ASENA CLOUD NETWORK'));
it("CSS contains @media print styles for countertop stand", str_contains($html, '@media print') && str_contains($html, '#vcard-printable-stand'));

// 4. Test direct vCard download endpoint
$vcfContent = file_get_contents('http://127.0.0.1:8085/site.php?slug=dr-alavi&download_vcard=1');
it("download_vcard endpoint starts with UTF-8 BOM", str_starts_with($vcfContent, "\xEF\xBB\xBF"));
it("download_vcard contains valid vCard structure", str_contains($vcfContent, 'BEGIN:VCARD') && str_contains($vcfContent, 'VERSION:3.0') && str_contains($vcfContent, 'END:VCARD'));
it("download_vcard contains clinic name with CHARSET=UTF-8", str_contains($vcfContent, 'FN;CHARSET=UTF-8:'));

// 5. Test direct QR vector download endpoint
$qrVcardDownload = file_get_contents('http://127.0.0.1:8085/site.php?slug=dr-alavi&download_qr=vcard');
it("download_qr=vcard returns valid SVG XML", str_starts_with(trim($qrVcardDownload), '<svg') && str_ends_with(trim($qrVcardDownload), '</svg>'));

$qrWebDownload = file_get_contents('http://127.0.0.1:8085/site.php?slug=dr-alavi&download_qr=website');
it("download_qr=website returns valid SVG XML", str_starts_with(trim($qrWebDownload), '<svg') && str_ends_with(trim($qrWebDownload), '</svg>'));

echo "====================================================================\n";
echo "VCard & QR Test Execution Finished: {$passed} Passed, {$failed} Failed.\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
