<?php
/**
 * ASENA Enterprise - Organization Portal Website Cockpit
 * Dedicated website management hub for veterinary clinics and animal hospitals.
 * Version: 2.0.0
 */

$currentFile = 'website.php';
require_once __DIR__ . '/includes/organization_header.php';

$cockpitTenantType = 'organization';
$cockpitTenantId = (int)($currentOrg['id'] ?? 0);
$cockpitUserId = (int)($_SESSION['user_id'] ?? 0);
$cockpitTitle = 'وب‌سایت اختصاصی کلینیک و مرکز درمانی';
$cockpitDefaultSlug = $currentOrg['slug'] ?? ('clinic-' . ($cockpitTenantId ?: $cockpitUserId));

require_once dirname(__DIR__) . '/includes/portal_website_cockpit.php';
?>
</body>
</html>
