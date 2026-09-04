<?php
/**
 * Dynamic XML Sitemap Generator for ASENA Platform
 * Automatically outputs valid sitemaps.org XML for Google Search Console & Bing Webmaster.
 */

// Resolve base site URL and connect to DB cleanly without sessions
require_once __DIR__ . '/config.php';
$site_url = 'https://asena.company';

$products = [];
try {
    $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
    $dbName = defined('DB_NAME') ? DB_NAME : 'asencomp_asena_db';
    $dbUser = defined('DB_USER') ? DB_USER : 'asencomp_admin';
    $dbPass = defined('DB_PASS') ? DB_PASS : '';
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    $stmt = $pdo->query("SELECT id, name, category, image_url, created_at FROM products WHERE stock > 0 ORDER BY id DESC LIMIT 1000");
    if ($stmt) {
        $products = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Graceful fallback
}

// Set proper XML headers
if (!headers_sent()) {
    header('Content-Type: application/xml; charset=utf-8');
    header('X-Robots-Tag: noindex, follow');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
                            http://www.google.com/schemas/sitemap-image/1.1
                            http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd">

    <!-- 1. Main Portal Landing Page (Highest Priority) -->
    <url>
        <loc><?= htmlspecialchars($site_url . '/') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- 2. Core Module Hubs -->
    <url>
        <loc><?= htmlspecialchars($site_url . '/standard/') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url . '/pharmacy-standard/') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url . '/premium/') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url . '/pharmacy-premium/') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- 3. Core Services & Commerce Pages -->
    <url>
        <loc><?= htmlspecialchars($site_url . '/standard/shop.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url . '/standard/booking.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url . '/standard/subscriptions.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url . '/standard/charity.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url . '/pharmacy-standard/shop.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($site_url . '/pharmacy-standard/booking.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- 4. Knowledge Base Hub & High-Ranking Medical/Service Articles -->
    <url>
        <loc><?= htmlspecialchars($site_url . '/standard/knowledge_base.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <?php
    $kb_slugs = [
        'vaccination-schedule-dogs-cats' => '0.85',
        'pet-poisoning-emergency-guide' => '0.85',
        'human-vs-veterinary-medications' => '0.85',
        'best-dry-food-selection-guide' => '0.85',
        'how-autoship-works-guide' => '0.85',
        'how-to-book-vet-appointment' => '0.8',
        'pharmacy-prescription-verification-guide' => '0.8',
        'charity-stray-pet-healthcare-guide' => '0.8'
    ];
    foreach ($kb_slugs as $slug => $prio):
    ?>
    <url>
        <loc><?= htmlspecialchars($site_url . '/standard/knowledge_base.php?article=' . $slug) ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority><?= $prio ?></priority>
    </url>
    <?php endforeach; ?>

    <!-- 4. High-Intent Pet Categories -->
    <?php
    $pet_categories = [
        'animal=dog'      => ['title' => 'غذای و لوازم سگ', 'priority' => '0.8'],
        'animal=cat'      => ['title' => 'غذای و لوازم گربه', 'priority' => '0.8'],
        'animal=bird'     => ['title' => 'لوازم و دان پرندگان', 'priority' => '0.7'],
        'animal=smallpet' => ['title' => 'ملزومات جوندگان و حیوانات کوچک', 'priority' => '0.7'],
        'animal=horse'    => ['title' => 'مکمل و ملزومات اسب', 'priority' => '0.7'],
        'animal=cow'      => ['title' => 'دارو و مکمل دام', 'priority' => '0.7']
    ];
    foreach ($pet_categories as $query => $info):
    ?>
    <url>
        <loc><?= htmlspecialchars($site_url . '/standard/shop.php?' . $query) ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority><?= $info['priority'] ?></priority>
    </url>
    <?php endforeach; ?>

    <!-- 5. Dynamic Products from Database -->
    <?php
    foreach ($products as $product):
        $prod_url = $site_url . '/standard/product_details.php?id=' . (int)$product['id'];
        $prod_date = !empty($product['created_at']) ? date('Y-m-d', strtotime($product['created_at'])) : date('Y-m-d');
        $prod_img = !empty($product['image_url']) ? (strpos($product['image_url'], 'http') === 0 ? $product['image_url'] : $site_url . '/standard/' . ltrim($product['image_url'], '/')) : '';
    ?>
    <url>
        <loc><?= htmlspecialchars($prod_url) ?></loc>
        <lastmod><?= $prod_date ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        <?php if (!empty($prod_img)): ?>
        <image:image>
            <image:loc><?= htmlspecialchars($prod_img) ?></image:loc>
            <image:title><?= htmlspecialchars($product['name'] ?? 'محصول پت شاپ آسنا') ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
    <?php endforeach; ?>
</urlset>
