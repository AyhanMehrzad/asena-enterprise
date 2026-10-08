<?php
/**
 * ASENA Enterprise - Seller Portal Website Cockpit
 * Dedicated website management hub for pet shops and suppliers.
 * Version: 2.0.0
 */

$activeTab = 'website';
require_once __DIR__ . '/includes/seller_header.php';

$cockpitTenantType = 'seller';
$cockpitTenantId = (int)($sellerId ?? $currentUser['id'] ?? 0);
$cockpitUserId = (int)($_SESSION['user_id'] ?? 0);
$cockpitTitle = 'وب‌سایت اختصاصی پت‌شاپ و ملزومات حیوانات';
$cockpitDefaultSlug = 'shop-' . ($cockpitTenantId ?: $cockpitUserId);

require_once dirname(__DIR__) . '/includes/portal_website_cockpit.php';
?>
</body>
</html>
