<?php
/**
 * ASENA Enterprise - Torob & Emalls Iranian Product Crawl Engine
 * Standardized API feed for Torob (ترب) and Emalls (ایمالز) price comparison engines.
 * Specification compliant with Torob API v1/v2 requirements.
 */

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('X-Robots-Tag: noindex, follow');
}

// Resilient DB Connection
if (file_exists(__DIR__ . '/../includes/Env.php')) {
    require_once __DIR__ . '/../includes/Env.php';
    Env::load();
}

$dbHost = defined('DB_HOST') ? DB_HOST : (class_exists('Env') ? Env::get('DB_HOST', '127.0.0.1') : '127.0.0.1');
$dbUser = defined('DB_USER') ? DB_USER : (class_exists('Env') ? Env::get('DB_USER', 'root') : 'root');
$dbPass = defined('DB_PASS') ? DB_PASS : (class_exists('Env') ? Env::get('DB_PASS', '') : '');
$dbName = defined('DB_NAME') ? DB_NAME : (class_exists('Env') ? Env::get('DB_NAME', 'asena_premium') : 'asena_premium');

$pdo = null;
$tryDbs = array_unique([$dbName, 'asena_premium', 'petshop_db', 'asencomp_asena_db']);
foreach ($tryDbs as $db) {
    try {
        $pdo = new PDO("mysql:host={$dbHost};dbname={$db};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 3
        ]);
        break;
    } catch (Throwable $e) {
        $pdo = null;
    }
}

$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'asena.company';
$siteUrl = (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false)
    ? "$proto://$host"
    : 'https://asena.company';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(500, max(10, (int)($_GET['limit'] ?? 200)));
$offset = ($page - 1) * $limit;

$items = [];

$animalFaMap = [
    'dog' => 'سگ',
    'cat' => 'گربه',
    'bird' => 'پرندگان',
    'smallpet' => 'حیوانات کوچک و جوندگان',
    'horse' => 'اسب',
    'cow' => 'دام بزرگ',
    'all' => 'عمومی'
];

if ($pdo) {
    // 1. Fetch Petshop Products
    try {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 500");
        if ($stmt) {
            while ($p = $stmt->fetch()) {
                $pId = (int)$p['id'];
                $price = !empty($p['discount_price']) && $p['discount_price'] > 0 ? (int)$p['discount_price'] : (int)$p['price'];
                $oldPrice = !empty($p['discount_price']) && $p['discount_price'] > 0 ? (int)$p['price'] : null;
                $stock = (int)($p['stock'] ?? 0);
                
                $img = !empty($p['image_url'])
                    ? ((strpos($p['image_url'], 'http') === 0) ? $p['image_url'] : "{$siteUrl}/" . ltrim($p['image_url'], '/'))
                    : "{$siteUrl}/assets/images/pharma-default.svg";

                $animLabel = $animalFaMap[$p['target_animal'] ?? 'all'] ?? 'عمومی';

                $items[] = [
                    'page_unique_key' => 'prod_' . $pId,
                    'product_id' => $pId,
                    'title' => $p['name'],
                    'subtitle' => $p['brand'] ?? '',
                    'page_url' => "{$siteUrl}/product_details.php?id={$pId}",
                    'price' => $price,
                    'old_price' => $oldPrice,
                    'availability' => $stock > 0 ? 'instock' : 'outofstock',
                    'stock_count' => $stock,
                    'image_link' => $img,
                    'image_links' => [$img],
                    'category_name' => 'پت شاپ > ' . ($p['category'] ?? 'ملزومات پت') . ' > ' . $animLabel,
                    'guarantee' => 'ضمانت اصالت و سلامت فیزیکی کالا',
                    'spec' => [
                        'برند' => $p['brand'] ?? 'آسنا',
                        'گونه حیوان' => $animLabel,
                        'دسته بندی' => $p['category'] ?? 'لوازم پت',
                        'تحویل دوره‌ای' => !empty($p['is_autoship']) ? 'دارد (Autoship)' : 'ندارد'
                    ]
                ];
            }
        }
    } catch (Throwable $e) {}

    // 2. Fetch Pharmacy Medicines
    try {
        $stmt = $pdo->query("SELECT * FROM pharmacy_medicines ORDER BY id DESC LIMIT 500");
        if ($stmt) {
            while ($m = $stmt->fetch()) {
                $mId = (int)$m['id'];
                $price = !empty($m['discount_price']) && $m['discount_price'] > 0 ? (int)$m['discount_price'] : (int)$m['price'];
                $oldPrice = !empty($m['discount_price']) && $m['discount_price'] > 0 ? (int)$m['price'] : null;
                $stock = (int)($m['stock'] ?? 0);
                
                $img = !empty($m['image_url'])
                    ? ((strpos($m['image_url'], 'http') === 0) ? $m['image_url'] : "{$siteUrl}/" . ltrim($m['image_url'], '/'))
                    : "{$siteUrl}/assets/images/pharma-default.svg";

                $animLabel = $animalFaMap[$m['target_animal'] ?? 'all'] ?? 'عمومی';

                $items[] = [
                    'page_unique_key' => 'pharma_' . $mId,
                    'product_id' => $mId,
                    'title' => $m['name'],
                    'subtitle' => $m['brand'] ?? 'داروخانه دامپزشکی آسنا',
                    'page_url' => "{$siteUrl}/product_details.php?id={$mId}&type=pharmacy",
                    'price' => $price,
                    'old_price' => $oldPrice,
                    'availability' => $stock > 0 ? 'instock' : 'outofstock',
                    'stock_count' => $stock,
                    'image_link' => $img,
                    'image_links' => [$img],
                    'category_name' => 'داروخانه دامپزشکی > ' . ($m['pharmacy_tag'] ?? 'دارو و مکمل'),
                    'guarantee' => 'ضمانت اصالت با نظارت دکتر داروساز',
                    'spec' => [
                        'برند' => $m['brand'] ?? 'آسنا',
                        'گونه هدف' => $animLabel,
                        'نوع دارو' => $m['pharmacy_tag'] ?? 'داروی دامپزشکی',
                        'نیاز به نسخه' => !empty($m['requires_prescription']) ? 'بله' : 'خیر'
                    ]
                ];
            }
        }
    } catch (Throwable $e) {}
}

$response = [
    'count' => count($items),
    'max_pages' => 1,
    'products' => $items
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
