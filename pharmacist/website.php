<?php
/**
 * ASENA Enterprise - Pharmacist Portal Website Cockpit
 * Dedicated website management hub for veterinary pharmacies.
 * Version: 2.0.0
 */

$currentPage = 'website';
require_once __DIR__ . '/includes/pharmacist_header.php';

$cockpitTenantType = 'pharmacist';
$cockpitTenantId = (int)($currentUser['id'] ?? 0);
$cockpitUserId = (int)($_SESSION['user_id'] ?? 0);
$cockpitTitle = 'وب‌سایت اختصاصی داروخانه دامپزشکی';
$cockpitDefaultSlug = 'pharmacy-' . $cockpitUserId;

require_once dirname(__DIR__) . '/includes/portal_website_cockpit.php';
?>
</body>
</html>
