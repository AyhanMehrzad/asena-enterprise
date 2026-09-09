<?php
/**
 * Dynamic XML Sitemap Generator for ASENA Platform
 * Automatically outputs valid sitemaps.org & Google Image XML for Google Search Console, Bing Webmaster & Yandex.
 */

$isCli = (php_sapi_name() === 'cli');
$forceRebuild = isset($_GET['rebuild']) || $isCli;

if (!$isCli && !headers_sent()) {
    header('Content-Type: application/xml; charset=utf-8');
    header('X-Robots-Tag: noindex, follow');
}

$staticSitemap = __DIR__ . '/sitemap.xml';
if (!$forceRebuild && file_exists($staticSitemap) && (time() - filemtime($staticSitemap) < 86400)) {
    readfile($staticSitemap);
    exit;
}

// Site base URL configuration
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'asena.company';
$siteUrl = (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false)
    ? "$proto://$host"
    : 'https://asena.company';

$today = date('Y-m-d');

// Resilient Database Connection
$pdo = null;
if (file_exists(__DIR__ . '/includes/Env.php')) {
    require_once __DIR__ . '/includes/Env.php';
    Env::load();
}

$dbHost = defined('DB_HOST') ? DB_HOST : (class_exists('Env') ? Env::get('DB_HOST', '127.0.0.1') : '127.0.0.1');
$dbUser = defined('DB_USER') ? DB_USER : (class_exists('Env') ? Env::get('DB_USER', 'root') : 'root');
$dbPass = defined('DB_PASS') ? DB_PASS : (class_exists('Env') ? Env::get('DB_PASS', '') : '');
$dbName = defined('DB_NAME') ? DB_NAME : (class_exists('Env') ? Env::get('DB_NAME', 'asena_premium') : 'asena_premium');

$tryDbs = array_unique([$dbName, 'asena_premium', 'petshop_db', 'asencomp_asena_db']);
foreach ($tryDbs as $db) {
    try {
        $pdo = new PDO("mysql:host={$dbHost};dbname={$db};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2
        ]);
        break;
    } catch (Throwable $e) {
        $pdo = null;
    }
}

$urls = [];

/**
 * Add an entry to the sitemap array
 */
function sitemapAdd(&$urls, $loc, $priority = '0.8', $changefreq = 'weekly', $lastmod = null, $images = []) {
    global $today;
    $urls[$loc] = [
        'loc' => $loc,
        'lastmod' => $lastmod ?: $today,
        'changefreq' => $changefreq,
        'priority' => $priority,
        'images' => $images
    ];
}

// 1. Core Platform Hubs (Root & Tier Portals)
sitemapAdd($urls, "{$siteUrl}/", '1.0', 'daily');
sitemapAdd($urls, "{$siteUrl}/standard/", '0.9', 'daily');
sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/", '0.9', 'daily');
sitemapAdd($urls, "{$siteUrl}/premium/", '0.85', 'weekly');
sitemapAdd($urls, "{$siteUrl}/pharmacy-premium/", '0.85', 'weekly');
sitemapAdd($urls, "{$siteUrl}/basic/", '0.75', 'weekly');
sitemapAdd($urls, "{$siteUrl}/pharmacy-basic/", '0.75', 'weekly');

// 2. Primary Public Service Hubs
sitemapAdd($urls, "{$siteUrl}/shop.php", '0.95', 'daily');
sitemapAdd($urls, "{$siteUrl}/booking.php", '0.95', 'daily');
sitemapAdd($urls, "{$siteUrl}/pharmacy.php", '0.95', 'daily');
sitemapAdd($urls, "{$siteUrl}/subscriptions.php", '0.85', 'weekly');
sitemapAdd($urls, "{$siteUrl}/charity.php", '0.85', 'weekly');
sitemapAdd($urls, "{$siteUrl}/knowledge_base.php", '0.9', 'daily');
sitemapAdd($urls, "{$siteUrl}/organizations.php", '0.85', 'daily');

// Tier-specific Service Hubs
sitemapAdd($urls, "{$siteUrl}/standard/shop.php", '0.9', 'daily');
sitemapAdd($urls, "{$siteUrl}/standard/booking.php", '0.9', 'daily');
sitemapAdd($urls, "{$siteUrl}/standard/subscriptions.php", '0.8', 'weekly');
sitemapAdd($urls, "{$siteUrl}/standard/charity.php", '0.8', 'weekly');
sitemapAdd($urls, "{$siteUrl}/standard/knowledge_base.php", '0.85', 'daily');
sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/shop.php", '0.9', 'daily');
sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/booking.php", '0.85', 'daily');

// 3. Animal Category & Medical Tag Filter Hubs
$animals = ['dog', 'cat', 'bird', 'smallpet', 'horse', 'cow'];
foreach ($animals as $a) {
    sitemapAdd($urls, "{$siteUrl}/shop.php?animal={$a}", '0.85', 'weekly');
    sitemapAdd($urls, "{$siteUrl}/pharmacy.php?animal={$a}", '0.85', 'weekly');
    sitemapAdd($urls, "{$siteUrl}/standard/shop.php?animal={$a}", '0.8', 'weekly');
    sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/shop.php?animal={$a}", '0.8', 'weekly');
}

$pharmacyTags = ['دارو', 'مکمل', 'واکسن', 'ضد انگل', 'بهداشتی', 'تجهیزات'];
foreach ($pharmacyTags as $tag) {
    $encodedTag = urlencode($tag);
    sitemapAdd($urls, "{$siteUrl}/pharmacy.php?tag={$encodedTag}", '0.85', 'weekly');
    sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/shop.php?tag={$encodedTag}", '0.8', 'weekly');
}

// 4. Master Medical & Platform Knowledge Base Articles
$masterArticleSlugs = [
    'vaccination-schedule-dogs-cats' => 'جدول کامل واکسیناسیون سگ و گربه در ایران',
    'toxic-foods-and-plants-for-pets' => 'مسمومیت در حیوانات و مواد سمی خانگی',
    'pharmacy-prescription-guidelines' => 'راهنمای تایید نسخه و سفارش داروهای دامپزشکی',
    'charity-stray-pet-healthcare-guide' => 'پویش‌های خیریه آسنا و نجات حیوانات بی‌سرپرست',
    'admin-panel-guide' => 'راهنمای جامع کاربری پنل مدیریت آسنا',
    'doctor-panel-guide' => 'راهنمای جامع کاربری پنل پزشکان آسنا',
    'pet-hair-loss-causes-and-treatments' => 'علل ریزش موی سگ و گربه و راه‌های درمان',
    'pet-neutering-benefits-and-post-op' => 'ضرورت جراحی عقیم‌سازی و سن طلایی پت',
    'pet-deworming-schedule-and-medications' => 'جدول انگل‌تراپی و دوز قرص ضد انگل سگ و گربه',
    'parvovirus-symptoms-and-treatment-in-puppies' => 'علائم، خطرات و راه نجات توله‌ها از پاروویروس',
    'pet-diabetes-symptoms-and-insulin-guide' => 'بیماری دیابت در سگ و گربه و علائم هشدار',
    'cat-urinary-blockage-emergency-guide' => 'انسداد ادراری در گربه نر و وضعیت‌های اورژانسی',
    'pet-dental-care-and-scaling-guide' => 'بهداشت دندان، رفع بوی دهان و جرم‌گیری پت',
    'pet-fleas-ticks-and-mites-treatment' => 'شپش، کک، کنه و جرب در سگ و گربه'
];

foreach ($masterArticleSlugs as $slug => $artTitle) {
    sitemapAdd(
        $urls,
        "{$siteUrl}/knowledge_base.php?article={$slug}",
        '0.9',
        'weekly',
        '2026-09-02',
        [[
            'loc' => "{$siteUrl}/assets/images/og-asena.png",
            'title' => $artTitle
        ]]
    );
    sitemapAdd($urls, "{$siteUrl}/standard/knowledge_base.php?article={$slug}", '0.85', 'weekly', '2026-09-02');
}

// 5. Dynamic Content From Database (Products, Medicines, Doctors, Organizations, DB Blogs)
if ($pdo) {
    // A. Dynamic Database Blog Posts
    try {
        $stmt = $pdo->query("SELECT slug, title, updated_at, created_at FROM blog_posts WHERE status = 'published' ORDER BY id DESC LIMIT 200");
        if ($stmt) {
            while ($post = $stmt->fetch()) {
                $pSlug = $post['slug'];
                $pDate = substr($post['updated_at'] ?? $post['created_at'] ?? $today, 0, 10);
                sitemapAdd(
                    $urls,
                    "{$siteUrl}/knowledge_base.php?article={$pSlug}",
                    '0.9',
                    'weekly',
                    $pDate,
                    [[
                        'loc' => "{$siteUrl}/assets/images/og-asena.png",
                        'title' => $post['title']
                    ]]
                );
            }
        }
    } catch (Throwable $e) {}

    // B. Doctors
    try {
        $stmt = $pdo->query("SELECT id, name, specialty, avatar_url, updated_at FROM doctors ORDER BY id DESC LIMIT 200");
        if ($stmt) {
            while ($doc = $stmt->fetch()) {
                $docId = (int)$doc['id'];
                $docDate = substr($doc['updated_at'] ?? $today, 0, 10);
                $docImages = [];
                if (!empty($doc['avatar_url'])) {
                    $avatarLoc = (strpos($doc['avatar_url'], 'http') === 0) ? $doc['avatar_url'] : "{$siteUrl}/" . ltrim($doc['avatar_url'], '/');
                    $docImages[] = [
                        'loc' => $avatarLoc,
                        'title' => 'دکتر ' . ($doc['name'] ?? '')
                    ];
                }
                sitemapAdd($urls, "{$siteUrl}/doctor_profile.php?id={$docId}", '0.85', 'weekly', $docDate, $docImages);
            }
        }
    } catch (Throwable $e) {}

    // C. Organizations / Clinics
    try {
        $stmt = $pdo->query("SELECT id, name, slug, logo_url, updated_at, created_at FROM organizations WHERE status = 'approved' ORDER BY id DESC LIMIT 200");
        if ($stmt) {
            while ($org = $stmt->fetch()) {
                $slug = !empty($org['slug']) ? $org['slug'] : $org['id'];
                $orgDate = substr($org['updated_at'] ?? $org['created_at'] ?? $today, 0, 10);
                $orgImages = [];
                if (!empty($org['logo_url'])) {
                    $logoLoc = (strpos($org['logo_url'], 'http') === 0) ? $org['logo_url'] : "{$siteUrl}/" . ltrim($org['logo_url'], '/');
                    $orgImages[] = [
                        'loc' => $logoLoc,
                        'title' => $org['name'] ?? 'کلینیک دامپزشکی'
                    ];
                }
                sitemapAdd($urls, "{$siteUrl}/organization_profile.php?slug={$slug}", '0.85', 'weekly', $orgDate, $orgImages);
            }
        }
    } catch (Throwable $e) {}

    // D. Petshop Products (with image tag)
    try {
        $stmt = $pdo->query("SELECT id, name, image_url, updated_at, created_at FROM products WHERE stock > 0 ORDER BY id DESC LIMIT 1000");
        if ($stmt) {
            while ($prod = $stmt->fetch()) {
                $pId = (int)$prod['id'];
                $pDate = substr($prod['updated_at'] ?? $prod['created_at'] ?? $today, 0, 10);
                $pImages = [];
                if (!empty($prod['image_url'])) {
                    $imgLoc = (strpos($prod['image_url'], 'http') === 0) ? $prod['image_url'] : "{$siteUrl}/" . ltrim($prod['image_url'], '/');
                    $pImages[] = [
                        'loc' => $imgLoc,
                        'title' => $prod['name'] ?? 'محصول پت‌شاپ آسنا'
                    ];
                }
                sitemapAdd($urls, "{$siteUrl}/product_details.php?id={$pId}", '0.8', 'weekly', $pDate, $pImages);
                sitemapAdd($urls, "{$siteUrl}/standard/product_details.php?id={$pId}", '0.75', 'weekly', $pDate, $pImages);
            }
        }
    } catch (Throwable $e) {}

    // E. Pharmacy Medicines (with image tag)
    try {
        $stmt = $pdo->query("SELECT id, name, image_url, updated_at, created_at FROM pharmacy_medicines WHERE stock > 0 ORDER BY id DESC LIMIT 1000");
        if ($stmt) {
            while ($med = $stmt->fetch()) {
                $mId = (int)$med['id'];
                $mDate = substr($med['updated_at'] ?? $med['created_at'] ?? $today, 0, 10);
                $mImages = [];
                if (!empty($med['image_url'])) {
                    $imgLoc = (strpos($med['image_url'], 'http') === 0) ? $med['image_url'] : "{$siteUrl}/" . ltrim($med['image_url'], '/');
                    $mImages[] = [
                        'loc' => $imgLoc,
                        'title' => $med['name'] ?? 'داروی دامپزشکی آسنا'
                    ];
                }
                sitemapAdd($urls, "{$siteUrl}/product_details.php?id={$mId}&type=pharmacy", '0.8', 'weekly', $mDate, $mImages);
                sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/product_details.php?id={$mId}&type=pharmacy", '0.75', 'weekly', $mDate, $mImages);
            }
        }
    } catch (Throwable $e) {}
}

// Generate XML Output
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
$xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' . "\n";
$xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
$xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
$xml .= '                            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' . "\n";
$xml .= '                            http://www.google.com/schemas/sitemap-image/1.1' . "\n";
$xml .= '                            http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd">' . "\n";

foreach ($urls as $u) {
    $xml .= "    <url>\n";
    $xml .= "        <loc>" . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "        <lastmod>" . $u['lastmod'] . "</lastmod>\n";
    $xml .= "        <changefreq>" . $u['changefreq'] . "</changefreq>\n";
    $xml .= "        <priority>" . $u['priority'] . "</priority>\n";
    if (!empty($u['images'])) {
        foreach ($u['images'] as $img) {
            $xml .= "        <image:image>\n";
            $xml .= "            <image:loc>" . htmlspecialchars($img['loc'], ENT_XML1, 'UTF-8') . "</image:loc>\n";
            if (!empty($img['title'])) {
                $xml .= "            <image:title>" . htmlspecialchars($img['title'], ENT_XML1, 'UTF-8') . "</image:title>\n";
            }
            $xml .= "        </image:image>\n";
        }
    }
    $xml .= "    </url>\n";
}
$xml .= "</urlset>\n";

// Save to static file for high-speed delivery
@file_put_contents($staticSitemap, $xml);

echo $xml;
