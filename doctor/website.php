<?php
/**
 * ASENA Enterprise - Doctor Portal Website Cockpit
 * Dedicated website management hub for veterinarians.
 * Version: 2.0.0
 */

$currentPage = 'website';
require_once __DIR__ . '/includes/doctor_header.php';

$cockpitTenantType = 'doctor';
$cockpitTenantId = (int)($doctorProfile['id'] ?? 0);
$cockpitUserId = (int)($_SESSION['user_id'] ?? 0);
$cockpitTitle = 'وب‌سایت اختصاصی پزشک دامپزشک';
$slugBase = !empty($doctorProfile['name']) ? $doctorProfile['name'] : 'vet';
$cockpitDefaultSlug = 'dr-' . preg_replace('/[^a-z0-9\-]/i', '', str_replace(' ', '-', strtolower($slugBase)));
if (empty(trim($cockpitDefaultSlug, 'dr-'))) {
    $cockpitDefaultSlug = 'dr-' . $cockpitUserId;
}

require_once dirname(__DIR__) . '/includes/portal_website_cockpit.php';
?>
</body>
</html>
