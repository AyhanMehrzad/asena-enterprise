<?php
/**
 * Dynamic XML Sitemap Generator for ASENA Platform
 * Automatically outputs valid sitemaps.org XML for Google Search Console, Bing Webmaster & Yandex.
 */

if (!headers_sent()) {
    header('Content-Type: application/xml; charset=utf-8');
    header('X-Robots-Tag: noindex, follow');
}

$staticSitemap = __DIR__ . '/sitemap.xml';
if (file_exists($staticSitemap) && (time() - filemtime($staticSitemap) < 86400)) {
    readfile($staticSitemap);
    exit;
}

// Fallback dynamic regeneration
$siteUrl = 'https://asena.company';
$today = date('Y-m-d');

$pdo = null;
$tryDbs = ['petshop_db', 'asena_premium', 'asencomp_asena_db'];
foreach ($tryDbs as $db) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname={$db};charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        break;
    } catch (Exception $e) {}
}

$urls = [];
function sitemapAdd(&$urls, $loc, $priority = '0.8', $changefreq = 'weekly', $lastmod = null) {
    global $today;
    $urls[$loc] = [
        'loc' => $loc,
        'lastmod' => $lastmod ?: $today,
        'changefreq' => $changefreq,
        'priority' => $priority
    ];
}

// Core hubs
sitemapAdd($urls, "{$siteUrl}/", '1.0', 'daily');
sitemapAdd($urls, "{$siteUrl}/standard/", '0.9', 'daily');
sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/", '0.9', 'daily');
sitemapAdd($urls, "{$siteUrl}/premium/", '0.85', 'weekly');
sitemapAdd($urls, "{$siteUrl}/pharmacy-premium/", '0.85', 'weekly');
sitemapAdd($urls, "{$siteUrl}/basic/", '0.75', 'weekly');
sitemapAdd($urls, "{$siteUrl}/pharmacy-basic/", '0.75', 'weekly');

// Services
sitemapAdd($urls, "{$siteUrl}/standard/shop.php", '0.95', 'daily');
sitemapAdd($urls, "{$siteUrl}/standard/booking.php", '0.95', 'daily');
sitemapAdd($urls, "{$siteUrl}/standard/subscriptions.php", '0.85', 'weekly');
sitemapAdd($urls, "{$siteUrl}/standard/charity.php", '0.8', 'weekly');
sitemapAdd($urls, "{$siteUrl}/standard/knowledge_base.php", '0.9', 'daily');
sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/shop.php", '0.95', 'daily');
sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/booking.php", '0.85', 'daily');

// Categories
foreach (['dog', 'cat', 'bird', 'smallpet', 'horse', 'cow'] as $a) {
    sitemapAdd($urls, "{$siteUrl}/standard/shop.php?animal={$a}", '0.8', 'weekly');
    sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/shop.php?animal={$a}", '0.8', 'weekly');
}
sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/shop.php?tag=" . urlencode('دارو'), '0.85', 'weekly');
sitemapAdd($urls, "{$siteUrl}/pharmacy-standard/shop.php?tag=" . urlencode('مکمل'), '0.85', 'weekly');

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, created_at FROM products WHERE stock > 0 ORDER BY id DESC LIMIT 500");
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                sitemapAdd($urls, "{$siteUrl}/standard/product_details.php?id={$row['id']}", '0.8', 'weekly', substr($row['created_at'] ?? $today, 0, 10));
            }
        }
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->query("SELECT id, slug, created_at FROM organizations WHERE status = 'approved'");
        if ($stmt) {
            sitemapAdd($urls, "{$siteUrl}/organizations.php", '0.85', 'daily');
            while ($row = $stmt->fetch()) {
                $slug = !empty($row['slug']) ? $row['slug'] : $row['id'];
                sitemapAdd($urls, "{$siteUrl}/organization_profile.php?slug={$slug}", '0.85', 'weekly');
            }
        }
    } catch (Exception $e) {}
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<?php foreach ($urls as $u): ?>
    <url>
        <loc><?= htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= $u['lastmod'] ?></lastmod>
        <changefreq><?= $u['changefreq'] ?></changefreq>
        <priority><?= $u['priority'] ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
