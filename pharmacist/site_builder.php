<?php
/**
 * ASENA Enterprise - Pharmacist Visual Site Builder
 * Tailored for veterinary pharmacies to customize their digital storefront & Rx portal.
 * Version: 1.0.0
 */

$current_page = 'site_builder.php';
require_once __DIR__ . '/includes/pharmacist_header.php';

$builderTenantType = 'pharmacist';
$builderTenantId = (int)$currentUser['id'];
$builderTenantInfo = [
    'name' => 'داروخانه دامپزشکی ' . ($pharmacistName),
    'tagline' => 'تأمین تخصصی داروها، مکمل‌ها و واکسن‌های دام، طیور و پت با رعایت زنجیره سرد',
    'phone' => $currentUser['phone'] ?? '',
    'address' => $linkedOrg['address'] ?? 'تهران، مرکز پلتفرم آسنا',
    'operating_hours' => 'شنبه تا پنجشنبه: ۸:۳۰ صبح الی ۲۱:۳۰ شب',
    'logo_url' => 'assets/images/logo.png',
    'banner_url' => 'assets/images/pharmacy-banner.jpg',
    'license_number' => $currentUser['vet_council_number'] ?? 'سازمان دامپزشکی کشور'
];
require_once dirname(__DIR__) . '/includes/site_builder_studio.php';
?>
</body>
</html>
