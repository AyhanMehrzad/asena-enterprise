<?php
/**
 * ASENA Enterprise - Seller / Pet Shop Visual Site Builder
 * Tailored for pet shops and suppliers to customize their branded online pet shop.
 * Version: 1.0.0
 */

$current_page = 'site_builder.php';
require_once __DIR__ . '/includes/seller_header.php';

$builderTenantType = 'seller';
$builderTenantId = (int)$sellerId;
$builderTenantInfo = [
    'name' => 'پت‌شاپ اختصاصی ' . ($sellerName),
    'tagline' => 'فروشگاه تخصصی انواع غذای سگ، گربه، پرندگان و ملزومات نگهداری حیوانات خانگی',
    'phone' => $currentUser['phone'] ?? '',
    'address' => 'تهران، انبار رسمی آسنا',
    'operating_hours' => 'پاسخگویی و ارسال سفارشات: شنبه تا چهارشنبه ۹ الی ۱۸',
    'logo_url' => 'assets/images/logo.png',
    'banner_url' => 'assets/images/petshop-banner.jpg'
];
require_once dirname(__DIR__) . '/includes/site_builder_studio.php';
?>
</body>
</html>
