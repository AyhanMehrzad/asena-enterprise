<?php
/**
 * ASENA Enterprise - Organization Visual Site Builder
 * Tailored for clinics and veterinary hospitals to customize their official showcase website.
 * Version: 1.0.0
 */

$current_page = 'site_builder.php';
require_once __DIR__ . '/includes/organization_header.php';

$builderTenantType = 'organization';
$builderTenantId = (int)$currentOrg['id'];
$builderTenantInfo = [
    'name' => $currentOrg['name'] ?? 'کلینیک دامپزشکی',
    'tagline' => $currentOrg['tagline'] ?? 'مرکز خدمات تخصصی و جراحی حیوانات خانگی',
    'phone' => $currentOrg['phone'] ?? '',
    'emergency_phone' => $currentOrg['emergency_phone'] ?? '',
    'address' => $currentOrg['address'] ?? '',
    'operating_hours' => $currentOrg['operating_hours'] ?? '',
    'logo_url' => $currentOrg['logo_url'] ?? '',
    'banner_url' => $currentOrg['banner_url'] ?? 'assets/images/clinic-banner.jpg',
    'license_number' => $currentOrg['license_number'] ?? ''
];
require_once dirname(__DIR__) . '/includes/site_builder_studio.php';
?>
</body>
</html>
