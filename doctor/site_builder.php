<?php
/**
 * ASENA Enterprise - Doctor Visual Site Builder
 * Tailored for veterinarians to customize their personal showcase website.
 * Version: 1.0.0
 */

$current_page = 'site_builder.php';
require_once __DIR__ . '/includes/doctor_header.php';

$builderTenantType = 'doctor';
$builderTenantId = (int)$doctorProfile['id'];
$builderTenantInfo = [
    'name' => $doctorProfile['name'] ?? $currentUser['name'],
    'specialty' => $doctorProfile['specialty'] ?? 'دامپزشک متخصص',
    'phone' => $currentUser['phone'] ?? '',
    'avatar_url' => $doctorProfile['avatar_url'] ?? '',
    'banner_url' => 'assets/images/clinic-banner.jpg',
    'vet_council_number' => $currentUser['vet_council_number'] ?? ''
];

// Main Container
echo '<main class="lg:mr-64 p-0 min-h-screen bg-slate-100 flex flex-col transition-all duration-300">';
require_once dirname(__DIR__) . '/includes/site_builder_studio.php';
echo '</main>';
?>
</body>
</html>
