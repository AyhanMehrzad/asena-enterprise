<?php
/**
 * ASENA Asset Localizer & Optimizer
 * 
 * Downloads external images (Unsplash, Google User Content, CDNs),
 * stores them in dedicated local folders, and updates database references.
 */

$root = dirname(__DIR__);
$productsDir = $root . '/assets/images/products';
$doctorsDir = $root . '/assets/images/doctors';
$campaignsDir = $root . '/assets/images/campaigns';
$shippingDir = $root . '/assets/images/shipping';
$placeholdersDir = $root . '/assets/images/placeholders';
$iconsDir = $root . '/assets/icons';

@mkdir($productsDir, 0755, true);
@mkdir($doctorsDir, 0755, true);
@mkdir($campaignsDir, 0755, true);
@mkdir($shippingDir, 0755, true);
@mkdir($placeholdersDir, 0755, true);
@mkdir($iconsDir, 0755, true);

echo "==> ASENA Asset Localizer Starting...\n";

// Map of known remote URLs to local clean filenames
$urlMap = [
    // Google User Content products
    'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0' => 'assets/images/products/royal-canin-mini-adult-dog.jpg',
    'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o' => 'assets/images/products/gourmet-gold-cat-canned.jpg',
    'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR' => 'assets/images/products/zoolux-leather-collar-lg.jpg',
    'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj' => 'assets/images/products/petopia-cat-litter-10kg.jpg',
    
    // Unsplash Pharmacy & Pet Products
    'https://images.unsplash.com/photo-1563281577-a7be47e20db9?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/amprolium-soluble-powder.jpg',
    'https://images.unsplash.com/photo-1596797882870-8c33deeac224?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/mentofin-respiratory-solution.jpg',
    'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/newcastle-bronchitis-vaccine.jpg',
    'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/arthroflex-joint-care.jpg',
    'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/drontal-plus-dewormer.jpg',
    'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=500' => 'assets/images/products/drontal-plus-dewormer.jpg',
    'https://images.unsplash.com/photo-1537151608828-ea2b11777ee8?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/otoflox-otic-cleanser.jpg',
    'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/carprofen-analgesic-capsules.jpg',
    'https://images.unsplash.com/photo-1517849845537-4d257902454a?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/vet-inhaler-respiratory.jpg',
    'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/emergency-first-aid-kit.jpg',
    'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/organic-paw-protection-balm.jpg',
    'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/gimcat-multivitamin-paste.jpg',
    'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/feliway-calming-spray.jpg',
    'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/advocate-spot-on-cat.jpg',
    'https://images.unsplash.com/photo-1495360010541-f48722b34f7d?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/sterile-eye-drops-cat.jpg',
    'https://images.unsplash.com/photo-1500595046743-cd271d694d30?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/livestock-mineral-bolus.jpg',
    'https://images.unsplash.com/photo-1527153857715-3908f2ae5e81?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/horse-flex-joint-care.jpg',
    'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/bird-feather-vitamin-drops.jpg',
    'https://images.unsplash.com/photo-1546445317-29f4545e9d53?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/canary-multivitamin-complex.jpg',
    'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/poultry-probiotic-powder.jpg',
    'https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/equine-electrolyte-solution.jpg',
    'https://images.unsplash.com/photo-1568640347023-a616a30bc3bd?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/small-pet-calcium-drops.jpg',
    'https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/mastitis-cloxacillin-injector.jpg',
    'https://images.unsplash.com/photo-1598974357801-cbca100e6571?w=600&auto=format&fit=crop&q=80' => 'assets/images/products/rabbit-enrofloxacin-oral.jpg',
    
    // Postex Courier Logo
    'https://static.postex.ir/images/couriers/logos/small/IR_POST.png' => 'assets/images/shipping/ir_post.png',
];

// 1. Download each file
$downloaded = 0;
$skipped = 0;

foreach ($urlMap as $remoteUrl => $localRel) {
    $targetPath = $root . '/' . $localRel;
    if (file_exists($targetPath) && filesize($targetPath) > 500) {
        $skipped++;
        continue;
    }

    echo "Downloading: " . basename($localRel) . "... ";
    $ch = curl_init($remoteUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty($data) && strlen($data) > 300) {
        file_put_contents($targetPath, $data);
        echo "OK (" . round(strlen($data)/1024, 1) . " KB)\n";
        $downloaded++;
    } else {
        // Fallback: Copy a generic placeholder
        echo "FAILED (HTTP $httpCode). Using local fallback SVG/JPG.\n";
        copy($root . '/assets/images/placeholders/placeholder-product.svg', $targetPath);
    }
}

echo "Downloads complete: $downloaded downloaded, $skipped already existed.\n";

// 2. Update petshop_db.sql with local paths
$sqlFile = $root . '/petshop_db.sql';
if (file_exists($sqlFile)) {
    echo "Updating petshop_db.sql with local paths...\n";
    $sqlContent = file_get_contents($sqlFile);
    foreach ($urlMap as $remoteUrl => $localRel) {
        $sqlContent = str_replace($remoteUrl, $localRel, $sqlContent);
    }
    // Also replace placehold.co and via.placeholder
    $sqlContent = str_replace('https://placehold.co/1200x600?text=Campaign', 'assets/images/placeholders/placeholder-campaign.svg', $sqlContent);
    $sqlContent = str_replace('https://placehold.co/800x600?text=Campaign', 'assets/images/placeholders/placeholder-campaign.svg', $sqlContent);
    $sqlContent = str_replace('https://placehold.co/150?text=Doctor', 'assets/images/placeholders/placeholder-doctor.svg', $sqlContent);
    $sqlContent = str_replace('https://placehold.co/100?text=No+Image', 'assets/images/placeholders/placeholder-no-image.svg', $sqlContent);
    $sqlContent = str_replace('https://via.placeholder.com/150', 'assets/images/placeholders/placeholder-doctor.svg', $sqlContent);
    
    file_put_contents($sqlFile, $sqlContent);
    echo "petshop_db.sql updated.\n";
}

// 3. Update active local MySQL database tables
try {
    $pdo = new PDO("mysql:host=localhost;dbname=petshop_db;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Updating local MySQL database (petshop_db)...\n";
    foreach ($urlMap as $remoteUrl => $localRel) {
        $stmt = $pdo->prepare("UPDATE products SET image_url = :local WHERE image_url = :remote");
        $stmt->execute([':local' => $localRel, ':remote' => $remoteUrl]);
    }
    echo "petshop_db updated successfully.\n";
} catch (Exception $e) {
    echo "Notice: Could not directly update MySQL petshop_db: " . $e->getMessage() . "\n";
}

// 4. Also update PostexShippingService.php
$postexFile = $root . '/includes/PostexShippingService.php';
if (file_exists($postexFile)) {
    $c = file_get_contents($postexFile);
    $c = str_replace('https://static.postex.ir/images/couriers/logos/small/IR_POST.png', 'assets/images/shipping/ir_post.png', $c);
    file_put_contents($postexFile, $c);
    echo "PostexShippingService.php updated.\n";
}

// 5. Also replace placeholder URLs in charity.php, booking.php, etc.
$phpFiles = glob($root . '/*.php');
foreach ($phpFiles as $pf) {
    $c = file_get_contents($pf);
    $orig = $c;
    $c = str_replace('https://placehold.co/1200x600?text=Campaign', 'assets/images/placeholders/placeholder-campaign.svg', $c);
    $c = str_replace('https://placehold.co/800x600?text=Campaign', 'assets/images/placeholders/placeholder-campaign.svg', $c);
    $c = str_replace('https://placehold.co/150?text=Doctor', 'assets/images/placeholders/placeholder-doctor.svg', $c);
    $c = str_replace('https://placehold.co/100?text=No+Image', 'assets/images/placeholders/placeholder-no-image.svg', $c);
    $c = str_replace('https://via.placeholder.com/150', 'assets/images/placeholders/placeholder-doctor.svg', $c);
    $c = str_replace('https://static.postex.ir/images/couriers/logos/small/IR_POST.png', 'assets/images/shipping/ir_post.png', $c);
    if ($c !== $orig) {
        file_put_contents($pf, $c);
        echo "Updated placeholders in: " . basename($pf) . "\n";
    }
}

// 6. Sync assets to asena.company
$portalRoot = dirname($root) . '/asena.company';
if (is_dir($portalRoot)) {
    echo "Syncing assets to asena.company...\n";
    shell_exec("rsync -avq {$productsDir}/ {$portalRoot}/assets/images/products/");
    shell_exec("rsync -avq {$shippingDir}/ {$portalRoot}/assets/images/shipping/");
    shell_exec("rsync -avq {$placeholdersDir}/ {$portalRoot}/assets/images/placeholders/");
    shell_exec("rsync -avq {$iconsDir}/ {$portalRoot}/assets/icons/");
    
    // Also sync to each tier directory in asena.company
    $tierDirs = ['basic', 'standard', 'premium', 'pharmacy', 'pharmacy-basic', 'pharmacy-standard', 'pharmacy-premium'];
    foreach ($tierDirs as $td) {
        $tPath = "{$portalRoot}/{$td}";
        if (is_dir($tPath)) {
            @mkdir("{$tPath}/assets/icons", 0755, true);
            @mkdir("{$tPath}/assets/images/products", 0755, true);
            @mkdir("{$tPath}/assets/images/shipping", 0755, true);
            @mkdir("{$tPath}/assets/images/placeholders", 0755, true);
            shell_exec("rsync -avq {$productsDir}/ {$tPath}/assets/images/products/");
            shell_exec("rsync -avq {$shippingDir}/ {$tPath}/assets/images/shipping/");
            shell_exec("rsync -avq {$placeholdersDir}/ {$tPath}/assets/images/placeholders/");
            shell_exec("rsync -avq {$iconsDir}/ {$tPath}/assets/icons/");
        }
    }
    echo "All assets synchronized to asena.company tiers.\n";
}

echo "==> Asset localization complete!\n";
