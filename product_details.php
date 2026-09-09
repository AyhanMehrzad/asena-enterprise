<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/App.php';
App::boot();

$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: shop.php');
    exit;
}

// Get product details (checking both petshop products and pharmacy_medicines)
$type = $_GET['type'] ?? '';
$product = null;
$item_source = 'product';

if ($type === 'pharmacy' && Feature::has('pharmacy_catalog')) {
    $stmt = $pdo->prepare("SELECT * FROM pharmacy_medicines WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) $item_source = 'pharmacy';
} elseif ($type === 'product' && Feature::has('petshop_catalog')) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) $item_source = 'product';
}

if (!$product && Feature::has('petshop_catalog')) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) $item_source = 'product';
}

if (!$product && Feature::has('pharmacy_catalog')) {
    $stmt = $pdo->prepare("SELECT * FROM pharmacy_medicines WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) $item_source = 'pharmacy';
}

if (!$product) {
    $redirect = Feature::has('petshop_catalog') ? 'shop.php' : 'pharmacy.php';
    header("Location: $redirect");
    exit;
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    csrf_verify();
    if (!isset($_SESSION['user_id'])) {
        $error = "برای ثبت نظر باید وارد حساب کاربری شوید.";
    } else {
        $rating = (int)($_POST['rating'] ?? 5);
        $comment = trim($_POST['comment'] ?? '');
        
        if ($rating >= 1 && $rating <= 5) {
            // Check verified purchase
            $vStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ?");
            $vStmt->execute([$_SESSION['user_id'], $product_id]);
            $is_verified = ($vStmt->fetchColumn() > 0) ? 1 : 0;

            // Check existing review
            $chkStmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND target_type = 'product' AND target_id = ?");
            $chkStmt->execute([$_SESSION['user_id'], $product_id]);
            $existing_id = $chkStmt->fetchColumn();

            if ($existing_id) {
                $upd = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, is_verified_buyer = ?, status = 'approved', created_at = NOW() WHERE id = ?");
                $upd->execute([$rating, $comment, $is_verified, $existing_id]);
            } else {
                $ins = $pdo->prepare("INSERT INTO reviews (user_id, target_type, target_id, rating, comment, is_verified_buyer, status, created_at) VALUES (?, 'product', ?, ?, ?, ?, 'approved', NOW())");
                $ins->execute([$_SESSION['user_id'], $product_id, $rating, $comment, $is_verified]);
                
                if ($is_verified) {
                    $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 5 WHERE id = ?")->execute([$_SESSION['user_id']]);
                }
            }

            // Recalculate Bayesian Rating
            recalculate_bayesian_rating($pdo, 'product', $product_id);
            $success = "نظر شما با موفقیت ثبت شد." . ($is_verified ? " (+۵ امتیاز وفاداری به کیف پول شما افزوده شد)" : "");
        }
    }
}

// Get reviews with verified buyer status
$stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.target_type = 'product' AND r.target_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bayesian Score & Count
$stats = recalculate_bayesian_rating($pdo, 'product', $product_id);
$avg_rating = $stats['rating'];
$review_count = $stats['review_count'];

// Fetch Related / Recommended Products (Smart Tag & Category matching + Always Random)
$target_animal = $product['target_animal'] ?? 'all';
$pharmacy_tag  = $product['pharmacy_tag'] ?? null;
$category      = $product['category'] ?? '';

$conditions = ["id != ?"];
$params = [$product_id];

$tag_or = [];
if (!empty($pharmacy_tag)) {
    $tag_or[] = "pharmacy_tag = ?";
    $params[] = $pharmacy_tag;
}
if (!empty($category)) {
    $tag_or[] = "category = ?";
    $params[] = $category;
}
if (!empty($target_animal) && $target_animal !== 'all') {
    $tag_or[] = "(target_animal = ? OR target_animal = 'all')";
    $params[] = $target_animal;
}

if (!empty($tag_or)) {
    $conditions[] = "(" . implode(" OR ", $tag_or) . ")";
}

$target_table = ($item_source === 'pharmacy') ? 'pharmacy_medicines' : 'products';
$where_clause = implode(" AND ", $conditions);
$rec_stmt = $pdo->prepare("SELECT * FROM {$target_table} WHERE {$where_clause} ORDER BY RAND() LIMIT 4");
$rec_stmt->execute($params);
$related_products = $rec_stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($related_products) < 4) {
    $exclude_ids = array_merge([$product_id], array_column($related_products, 'id'));
    $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
    $needed = 4 - count($related_products);
    $rec_fallback = $pdo->prepare("SELECT * FROM {$target_table} WHERE id NOT IN ($placeholders) ORDER BY RAND() LIMIT $needed");
    $rec_fallback->execute($exclude_ids);
    $fallback_items = $rec_fallback->fetchAll(PDO::FETCH_ASSOC);
    $related_products = array_merge($related_products, $fallback_items);
}

// Animal names map
$animal_fa_map = [
    'dog' => 'سگ',
    'cat' => 'گربه',
    'horse' => 'اسب',
    'cow' => 'گاو و دام',
    'chick' => 'جوجه و طیور',
    'all' => 'همه حیوانات'
];

$animal_display = $animal_fa_map[$product['target_animal'] ?? 'all'] ?? 'عمومی';

$is_autoship = !empty($product['is_autoship']);
$autoship_discount = $product['autoship_discount'] ?? 10;
$base_price = $product['discount_price'] ?? $product['price'];
$autoship_price = round($base_price * (100 - $autoship_discount) / 100);

// Dynamic On-Page SEO, OpenGraph & GEO for Product Details
$page_title = htmlspecialchars($product['name']) . ' | خرید اینترنتی با تحویل فوری - آسنا';
$clean_desc = mb_substr(strip_tags($product['description'] ?? $product['name']), 0, 150, 'UTF-8');
$page_description = "خرید آنلاین {$product['name']} با ضمانت اصالت کالا، مشاوره تخصصی و ارسال فوری به سراسر کشور در سامانه خدمات دامپزشکی و پت‌شاپ آسنا.";
$og_image = !empty($product['image_url']) ? $product['image_url'] : 'assets/images/pharma-default.svg';
$og_type = 'product';
$product_price_irr = ($product['discount_price'] ?: $product['price']) * 10;

$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'asena.company';
$canonical_url = "$proto://$host/product_details.php?id={$product_id}" . ($type === 'pharmacy' ? '&type=pharmacy' : '');

require_once 'includes/header.php';
?>

<main class="max-w-container-max mx-auto overflow-hidden py-8 lg:py-12 px-margin-desktop min-h-[70vh]">
    
    <!-- Breadcrumb -->
    <div class="text-label-sm text-on-surface-variant mb-8 flex items-center gap-2">
        <a href="index.php" class="hover:underline">خانه</a> > 
        <a href="shop.php" class="hover:underline">فروشگاه</a> > 
        <?php if(!empty($product['target_animal'])): ?>
            <a href="shop.php?animal=<?php echo $product['target_animal']; ?>" class="hover:underline"><?php echo $animal_display; ?></a> >
        <?php endif; ?>
        <span class="text-on-surface font-medium"><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <!-- Product Main Card -->
    <div class="bg-white rounded-[3rem] p-6 lg:p-12 shadow-xl border border-outline-variant/20 flex flex-col lg:flex-row gap-12 mb-16">
        
        <!-- Product Image -->
        <div class="lg:w-5/12 flex flex-col gap-4">
            <div class="aspect-square bg-surface-container-lowest rounded-3xl overflow-hidden relative border border-outline-variant/30 shadow-inner group">
                <?php if($product['discount_price']): ?>
                    <div class="absolute top-6 left-6 bg-secondary-container text-white text-xs px-3.5 py-1 rounded-full z-10 font-bold shadow-md">تخفیف ویژه</div>
                <?php endif; ?>
                
                <?php if($is_autoship): ?>
                    <div class="absolute top-6 right-6 bg-primary-container text-white text-xs px-3.5 py-1 rounded-full z-10 font-bold shadow-md flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">autorenew</span>
                        پشتیبانی از Autoship
                    </div>
                <?php endif; ?>

                <img loading="lazy" src="<?php echo htmlspecialchars($product['image_url']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            
            <!-- Quick Features Banner -->
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="bg-surface-container-low p-3 rounded-2xl border border-outline-variant/20">
                    <span class="material-symbols-outlined text-primary text-xl mb-1">verified_user</span>
                    <p class="text-[11px] font-bold text-on-surface">ضمانت اصالت دارو</p>
                </div>
                <div class="bg-surface-container-low p-3 rounded-2xl border border-outline-variant/20">
                    <span class="material-symbols-outlined text-secondary-container text-xl mb-1">local_shipping</span>
                    <p class="text-[11px] font-bold text-on-surface">ارسال به سراسر کشور</p>
                </div>
                <div class="bg-surface-container-low p-3 rounded-2xl border border-outline-variant/20">
                    <span class="material-symbols-outlined text-status-active text-xl mb-1">support_agent</span>
                    <p class="text-[11px] font-bold text-on-surface">مشاوره دارویی</p>
                </div>
            </div>
        </div>

        <!-- Product Info & Purchase Form -->
        <div class="lg:w-7/12 flex flex-col justify-between">
            <div>
                <!-- Category & Species Tags -->
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="text-xs font-bold text-primary bg-primary/10 px-3 py-1 rounded-full"><?php echo htmlspecialchars($product['category']); ?></span>
                    <span class="text-xs font-bold text-secondary bg-secondary-container/15 px-3 py-1 rounded-full">گونه: <?php echo $animal_display; ?></span>
                    <?php if(!empty($product['brand'])): ?>
                    <span class="text-xs font-bold text-on-surface-variant bg-surface-container-high px-3 py-1 rounded-full">برند: <?php echo htmlspecialchars($product['brand']); ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="text-2xl lg:text-3xl font-bold text-on-surface mb-4 leading-snug"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <!-- Ratings Summary -->
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-outline-variant/30">
                    <div class="flex items-center text-status-warning font-bold">
                        <span class="material-symbols-outlined text-[22px] star-rating-filled">star</span>
                        <span class="mr-1 mt-0.5 text-base"><?php echo $avg_rating; ?></span>
                    </div>
                    <span class="text-on-surface-variant text-xs">(<?php echo count($reviews); ?> دیدگاه ثبت شده کاربران)</span>
                    <span class="text-outline-variant">•</span>
                    <span class="text-xs <?php echo $product['stock'] > 0 ? 'text-status-active font-bold' : 'text-error font-bold'; ?>">
                        <?php echo $product['stock'] > 0 ? 'موجود در انبار (' . $product['stock'] . ' عدد)' : 'ناموجود'; ?>
                    </span>
                </div>

                <!-- Description -->
                <div class="mb-8 text-sm lg:text-base text-on-surface-variant leading-relaxed bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/20">
                    <?php echo nl2br(htmlspecialchars(!empty(trim($product['description'] ?? '')) ? $product['description'] : 'توضیحات و مشخصات فنی این کالا توسط دامپزشکان و کارشناسان آسنا تایید شده است. برای کسب اطلاعات بیشتر می‌توانید با بخش مشاوره تماس حاصل فرمایید.')); ?>
                </div>

                <!-- Autoship Option Selector Box (Page 6) -->
                <?php if($is_autoship): ?>
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 p-5 rounded-2xl border-2 border-secondary-container/30 mb-8">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary-container text-2xl animate-spin" style="animation-duration: 10s;">autorenew</span>
                            <div>
                                <span class="text-sm font-bold text-primary block">خرید اشتراکی با تحویل خودکار (Autoship)</span>
                                <span class="text-xs text-on-surface-variant">تخفیف مداوم <?php echo $autoship_discount; ?>٪ و تحویل سروقت در بازه دلخواه</span>
                            </div>
                        </div>
                        <span class="bg-secondary-container text-white text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                            <?php echo number_format($autoship_price); ?> تومان
                        </span>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-3 text-xs pt-2 border-t border-secondary-container/20">
                        <label class="flex items-center gap-1.5 cursor-pointer font-bold text-primary">
                            <input type="radio" name="purchase_type" value="one_time" checked class="text-primary focus:ring-primary">
                            خرید معمولی یک‌باره
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer font-bold text-secondary-container">
                            <input type="radio" name="purchase_type" value="autoship" class="text-secondary-container focus:ring-secondary-container">
                            ارسال دوره‌ای خودکار (با <?php echo $autoship_discount; ?>٪ تخفیف)
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Price and Action Buttons -->
            <div class="mt-4 pt-6 border-t border-outline-variant/30 flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex flex-col">
                    <?php if($product['discount_price']): ?>
                        <span class="text-sm text-on-surface-variant line-through mb-1"><?php echo number_format($product['price']); ?> تومان</span>
                        <span class="text-3xl font-bold text-primary"><?php echo number_format($product['discount_price']); ?> <span class="text-sm font-normal">تومان</span></span>
                    <?php else: ?>
                        <span class="text-3xl font-bold text-primary"><?php echo number_format($product['price']); ?> <span class="text-sm font-normal">تومان</span></span>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>)" class="flex-1 sm:flex-initial bg-primary text-white px-10 py-4 rounded-2xl font-bold flex items-center justify-center gap-3 hover:bg-primary-container transition-all shadow-xl shadow-primary/20 hover:-translate-y-0.5 active:scale-95">
                        <span class="material-symbols-outlined">shopping_cart</span>
                        افزودن به سبد خرید
                    </button>
                    
                    <?php if($is_autoship): ?>
                    <a href="subscriptions.php" class="bg-secondary-container text-white px-5 py-4 rounded-2xl font-bold hover:bg-[#ea580c] transition-all shadow-md flex items-center justify-center" title="پلن‌های ارسال خودکار">
                        <span class="material-symbols-outlined">autorenew</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Amazon.com Benchmark: Frequently Bought Together
    require_once __DIR__ . '/includes/RecommendationService.php';
    $recService = new RecommendationService($pdo);
    $fbtItems = $recService->getFrequentlyBoughtTogether($product_id);
    $bundle = null;
    if (!empty($fbtItems)) {
        $complement = $fbtItems[0];
        $bundleCalc = RecommendationService::calculateBundle($product, [$complement], 5.0);
        $bundle = [
            'complement_product' => $complement,
            'regular_total'      => $bundleCalc['regular_total'],
            'discounted_total'   => $bundleCalc['bundle_total'],
            'savings'            => $bundleCalc['savings'],
        ];
    }

    // Alibaba.com Benchmark: Wholesale Pricing Tiers
    $wholesaleTiers = App::wholesale()->getPriceTiers($product_id);
    ?>

    <!-- Amazon.com Benchmark: خرید مکرر با هم (Frequently Bought Together) -->
    <?php if ($bundle): ?>
    <section class="glass-card p-6 md:p-8 mb-12 border-2 border-primary/15 relative overflow-hidden">
        <div class="flex items-center gap-3 mb-6">
            <span class="material-symbols-outlined text-secondary-container text-2xl">shopping_basket</span>
            <div>
                <h2 class="text-lg md:text-xl font-bold text-primary">خرید مکرر با هم (پک اقتصادی پیشنهادی)</h2>
                <p class="text-xs text-on-surface-variant">مشتریانی که این کالا را خریده‌اند، این اقلام را نیز به همراه آن تهیه کرده‌اند</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
            <!-- Items Visual Display -->
            <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 flex-1">
                <!-- Item 1: Current Product -->
                <div class="flex items-center gap-3 bg-white p-3 rounded-2xl border border-outline-variant/30 shadow-sm max-w-[260px]">
                    <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'assets/images/logo.png'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-16 h-16 object-cover rounded-xl">
                    <div>
                        <div class="text-xs font-bold text-slate-800 line-clamp-1"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="text-xs font-bold text-primary mt-1 toman-price"><?php echo number_format($product['price']); ?> تومان</div>
                    </div>
                </div>

                <span class="material-symbols-outlined text-secondary-container font-extrabold text-2xl">+</span>

                <!-- Item 2: Recommended Complement -->
                <div class="flex items-center gap-3 bg-white p-3 rounded-2xl border border-outline-variant/30 shadow-sm max-w-[260px]">
                    <img src="<?php echo htmlspecialchars($bundle['complement_product']['image_url'] ?? 'assets/images/logo.png'); ?>" alt="<?php echo htmlspecialchars($bundle['complement_product']['name']); ?>" class="w-16 h-16 object-cover rounded-xl">
                    <div>
                        <div class="text-xs font-bold text-slate-800 line-clamp-1"><?php echo htmlspecialchars($bundle['complement_product']['name']); ?></div>
                        <div class="text-xs font-bold text-primary mt-1 toman-price"><?php echo number_format($bundle['complement_product']['price']); ?> تومان</div>
                    </div>
                </div>
            </div>

            <!-- Price & Add Bundle CTA -->
            <div class="flex flex-col items-center lg:items-end gap-2 text-center lg:text-right border-t lg:border-t-0 lg:border-r border-outline-variant/30 pt-4 lg:pt-0 lg:pr-8">
                <div class="text-xs text-on-surface-variant">قیمت مجموع دو کالا:</div>
                <div class="flex items-center gap-2">
                    <span class="line-through text-xs text-slate-400 toman-price"><?php echo number_format($bundle['regular_total']); ?></span>
                    <span class="text-xl font-extrabold text-emerald-600 toman-price"><?php echo number_format($bundle['discounted_total']); ?> تومان</span>
                </div>
                <span class="bg-emerald-50 text-emerald-700 text-xs font-bold px-2.5 py-1 rounded-lg border border-emerald-200">
                    ۵٪ تخفیف خرید بسته ای (سود شما: <?php echo number_format($bundle['savings']); ?> تومان)
                </span>
                <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>); setTimeout(() => addToCart(this, <?php echo $bundle['complement_product']['id']; ?>), 300);" class="mt-3 px-6 py-3 bg-secondary-container text-white text-xs font-bold rounded-xl hover:bg-[#ea580c] transition-all shadow-md flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    افزودن هر دو قلم به سبد خرید
                </button>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Alibaba.com Benchmark: جدول قیمت عمده و استعلام کلینیک‌ها (Wholesale Tier & RFQ) -->
    <section class="glass-card p-6 md:p-8 mb-12 border border-outline-variant/30">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-2xl">warehouse</span>
                <div>
                    <h2 class="text-lg md:text-xl font-bold text-primary">خرید عمده، تخفیف پلکانی و استعلام کلینیک‌ها</h2>
                    <p class="text-xs text-on-surface-variant">ویژه داروخانه‌ها، کلینیک‌های دامپزشکی و پرورش‌دهندگان دارای پروانه معتبر</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('rfqModal').classList.remove('hidden')" class="px-5 py-2.5 bg-primary/10 hover:bg-primary hover:text-white text-primary text-xs font-bold rounded-xl transition-all flex items-center gap-2 border border-primary/20">
                <span class="material-symbols-outlined text-sm">request_quote</span>
                درخواست استعلام قیمت اختصاصی (RFQ)
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="wholesale-tier-card">
                <div class="text-xs text-slate-500 font-bold mb-1">خرید تکی و خرد</div>
                <div class="text-sm font-extrabold text-slate-800">۱ تا ۹ عدد</div>
                <div class="text-base font-extrabold text-primary mt-2 toman-price"><?php echo number_format($product['price']); ?> تومان</div>
                <div class="text-[11px] text-slate-400 mt-1">قیمت رسمی مصرف‌کننده</div>
            </div>
            <div class="wholesale-tier-card">
                <div class="text-xs text-emerald-600 font-bold mb-1">بسته کلینیکی (۱۰٪ تخفیف)</div>
                <div class="text-sm font-extrabold text-slate-800">۱۰ تا ۴۹ عدد</div>
                <div class="text-base font-extrabold text-emerald-600 mt-2 toman-price"><?php echo number_format(round($product['price'] * 0.9)); ?> تومان</div>
                <div class="text-[11px] text-emerald-700 mt-1">تحویل اکسپرس با بارنامه</div>
            </div>
            <div class="wholesale-tier-card active">
                <div class="text-xs text-secondary-container font-bold mb-1">پالت عمده همکاران (۲۰٪ تخفیف)</div>
                <div class="text-sm font-extrabold text-slate-800">۵۰ عدد به بالا</div>
                <div class="text-base font-extrabold text-secondary-container mt-2 toman-price"><?php echo number_format(round($product['price'] * 0.8)); ?> تومان</div>
                <div class="text-[11px] text-orange-700 mt-1">امکان تسویه اعتباری و فاکتور رسمی</div>
            </div>
        </div>
    </section>

    <!-- RFQ Tender Modal for Clinics -->
    <div id="rfqModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 md:p-8 shadow-2xl relative text-right border border-outline-variant/30">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-outline-variant/20">
                <h3 class="text-lg font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-container">request_quote</span>
                    ثبت استعلام عمده قیمت (RFQ)
                </h3>
                <button type="button" onclick="document.getElementById('rfqModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form action="actions/rfq_action.php" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">نام کلینیک / داروخانه / مجموعه</label>
                    <input type="text" name="company_name" required placeholder="مثال: بیمارستان دامپزشکی پایتخت" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-primary outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">تعداد مورد نیاز</label>
                        <input type="number" name="quantity" min="10" value="50" required class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">شماره نظام دامپزشکی</label>
                        <input type="text" name="vet_license" placeholder="مثال: IR-98432" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-primary outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">توضیحات و مشخصات سفارش</label>
                    <textarea name="notes" rows="3" placeholder="تاریخ تحویل مدنظر، شرایط نگهداری، زنجیره سرد و ..." class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-primary outline-none"></textarea>
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full py-3 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-hover transition-all shadow-md">
                        ارسال استعلام و صدور پیش‌فاکتور
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SMART RELATED PRODUCTS (Page 6 Recommendation System)                    -->
    <!-- ========================================================================= -->
    <?php if(!empty($related_products)): ?>
    <section class="mb-16">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-2xl">recommend</span>
                <div>
                    <h2 class="text-2xl font-bold text-primary">محصولات و داروهای مرتبط</h2>
                    <p class="text-xs text-on-surface-variant">پیشنهادات تخصصی برای <?php echo $animal_display; ?></p>
                </div>
            </div>
            <a href="shop.php?animal=<?php echo $product['target_animal'] ?? ''; ?>" class="text-xs font-bold text-primary hover:underline">مشاهده همه محصولات این دسته ></a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach($related_products as $rel): ?>
            <div class="bg-white rounded-3xl p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-outline-variant/15 flex flex-col justify-between group">
                <div class="aspect-square bg-surface-container-lowest rounded-2xl mb-4 overflow-hidden relative">
                    <img loading="lazy" src="<?php echo htmlspecialchars($rel['image_url']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="<?php echo htmlspecialchars($rel['name']); ?>">
                </div>
                <div>
                    <span class="text-[11px] text-on-surface-variant font-bold"><?php echo htmlspecialchars($rel['brand'] ?? 'آسنا'); ?></span>
                    <a href="product_details.php?id=<?php echo $rel['id']; ?>">
                        <h3 class="text-sm font-bold text-on-surface mb-3 line-clamp-2 hover:text-primary transition-colors cursor-pointer">
                            <?php echo htmlspecialchars($rel['name']); ?>
                        </h3>
                    </a>
                    <div class="flex items-center justify-between pt-2 border-t border-outline-variant/20">
                        <span class="text-sm font-bold text-primary"><?php echo number_format($rel['discount_price'] ?? $rel['price']); ?> ت</span>
                        <button type="button" onclick="addToCart(this, <?php echo $rel['id']; ?>)" class="bg-surface-container-low hover:bg-primary hover:text-white text-primary p-2 rounded-xl transition-colors">
                            <span class="material-symbols-outlined text-[18px]">add_shopping_cart</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Reviews Section -->
    <section class="max-w-4xl mx-auto bg-white rounded-3xl p-8 shadow-sm border border-outline-variant/20">
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-outline-variant/20">
            <h2 class="text-xl font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary-container">reviews</span>
                دیدگاه‌ها و تجربیات مصرف‌کنندگان
            </h2>
            <div class="flex flex-col items-end">
                <div class="flex items-center gap-2 text-status-warning font-bold text-sm">
                    <span class="material-symbols-outlined text-[20px] star-rating-filled">star</span>
                    <span><?php echo $avg_rating; ?> از ۵</span>
                </div>
                <span class="text-[11px] text-on-surface-variant">
                    <?php if($review_count > 0): ?>
                        (بر اساس <?php echo $review_count; ?> نظر خریداران)
                    <?php else: ?>
                        (امتیاز کارشناسی آسنا)
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <?php if(isset($success)): ?>
            <div class="bg-status-active/10 text-status-active p-4 rounded-xl mb-6 font-bold text-sm border border-status-active/20"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="bg-error/10 text-error p-4 rounded-xl mb-6 font-bold text-sm border border-error/20"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add Review Form -->
        <div class="bg-surface-container-low p-6 rounded-2xl border border-outline-variant/30 mb-8">
            <?php if(isset($_SESSION['user_id'])): ?>
            <h3 class="font-bold text-on-surface mb-4 text-sm">ثبت دیدگاه یا تجربه مصرف دارو</h3>
            <form action="product_details.php?id=<?php echo $product_id; ?>" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="submit_review">
                
                <div>
                    <label class="block text-xs font-bold mb-2">امتیاز شما به این محصول:</label>
                    <div class="flex flex-row-reverse justify-end gap-1 rating-stars">
                        <input type="radio" name="rating" value="5" id="star5" class="hidden" required><label for="star5" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                        <input type="radio" name="rating" value="4" id="star4" class="hidden"><label for="star4" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                        <input type="radio" name="rating" value="3" id="star3" class="hidden"><label for="star3" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                        <input type="radio" name="rating" value="2" id="star2" class="hidden"><label for="star2" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                        <input type="radio" name="rating" value="1" id="star1" class="hidden"><label for="star1" class="material-symbols-outlined cursor-pointer text-outline-variant hover:text-status-warning text-3xl transition-colors">star</label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold mb-2">متن نظر یا تجربه اثرگذاری:</label>
                    <textarea name="comment" rows="3" required class="w-full border border-outline-variant/50 rounded-xl p-3 focus:ring-2 focus:ring-primary outline-none text-sm resize-none bg-white" placeholder="تجربه خود از مصرف یا ویژگی‌های این محصول را بنویسید..."></textarea>
                </div>
                
                <button type="submit" class="bg-primary text-white px-8 py-3 rounded-xl font-bold hover:bg-primary-container transition-all text-xs">ثبت دیدگاه</button>
            </form>
            <style>
                .rating-stars label:hover,
                .rating-stars label:hover ~ label,
                .rating-stars input:checked ~ label {
                    color: #f59e0b;
                    font-variation-settings: 'FILL' 1;
                }
            </style>
            <?php else: ?>
                <div class="text-center py-6">
                    <p class="text-on-surface-variant font-bold mb-3 text-sm">برای ثبت نظر، ابتدا باید وارد حساب کاربری خود شوید.</p>
                    <a href="login.php" class="inline-block bg-primary text-white px-6 py-2.5 rounded-xl font-bold text-xs">ورود به حساب</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviews List -->
        <div class="space-y-4">
            <?php if(empty($reviews)): ?>
                <p class="text-center text-on-surface-variant py-8 border border-outline-variant/30 rounded-2xl border-dashed text-xs">هنوز دیدگاهی ثبت نشده است. اولین نفری باشید که نظر خود را به اشتراک می‌گذارد!</p>
            <?php else: ?>
                <?php foreach($reviews as $rev): ?>
                    <div class="bg-surface-container-low/50 p-5 rounded-2xl border border-outline-variant/20">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                    <?php echo mb_substr($rev['user_name'] ?? 'ک', 0, 1, 'UTF-8'); ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-on-surface text-xs"><?php echo htmlspecialchars($rev['user_name'] ?? 'کاربر آسنا'); ?></h4>
                                    <p class="text-[10px] text-on-surface-variant"><?php echo date('Y/m/d', strtotime($rev['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center text-status-warning">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <span class="material-symbols-outlined text-xs <?php echo $i <= $rev['rating'] ? 'star-rating-filled' : 'star-rating-empty'; ?>">star</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="text-xs text-on-surface-variant leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($rev['comment'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
function addToCart(btn, productId) {
    if(window.event) window.event.preventDefault();
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span> در حال افزودن...';
    btn.disabled = true;

    // Check purchase type (one_time or autoship)
    const selectedRadio = document.querySelector('input[name="purchase_type"]:checked');
    const purchaseType = selectedRadio ? selectedRadio.value : 'standard';
    
    let postBody = 'action=add&ajax=1&csrf_token=<?php echo csrf_token(); ?>&product_id=' + productId;
    if (purchaseType === 'autoship') {
        postBody += '&type=autoship&frequency=1_month';
    }
    
    fetch('actions/cart_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: postBody
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">check_circle</span> اضافه شد';
            btn.classList.add('bg-status-active');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('bg-status-active');
                btn.disabled = false;
            }, 2000);
        } else {
            alert('خطا در افزودن به سبد خرید');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
</script>

<!-- Schema.org JSON-LD Structured Data for Google Rich Snippets (Product, Offer, Rating) -->
<?php
$abs_image = strpos($product['image_url'] ?? '', 'http') === 0 
    ? $product['image_url'] 
    : "$proto://$host/" . ltrim($product['image_url'] ?: 'assets/images/pharma-default.svg', '/');
?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": <?php echo json_encode($product['name']); ?>,
  "image": [
    <?php echo json_encode($abs_image); ?>
  ],
  "description": <?php echo json_encode(strip_tags($product['description'] ?? $product['name'])); ?>,
  "sku": "ASENA-PROD-<?php echo $product['id']; ?>",
  "mpn": "ASENA-PROD-<?php echo $product['id']; ?>",
  "brand": {
    "@type": "Brand",
    "name": <?php echo json_encode($product['brand'] ?? 'داروخانه و پت‌شاپ آسنا'); ?>
  },
  "category": <?php echo json_encode($product['category'] ?? 'دامپزشکی'); ?>,
  "offers": {
    "@type": "Offer",
    "url": <?php echo json_encode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>,
    "priceCurrency": "IRR",
    "price": "<?php echo ($product['discount_price'] ?: $product['price']) * 10; ?>",
    "priceValidUntil": "<?php echo date('Y-12-31'); ?>",
    "itemCondition": "https://schema.org/NewCondition",
    "availability": "<?php echo ($product['stock'] > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'; ?>",
    "seller": {
      "@type": "Pharmacy",
      "name": "داروخانه آنلاین و تخصصی آسنا"
    },
    "hasMerchantReturnPolicy": {
      "@type": "MerchantReturnPolicy",
      "applicableCountry": "IR",
      "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",
      "merchantReturnDays": 7
    },
    "shippingDetails": {
      "@type": "OfferShippingDetails",
      "shippingRate": {
        "@type": "MonetaryAmount",
        "value": "0",
        "currency": "IRR"
      },
      "shippingDestination": {
        "@type": "DefinedRegion",
        "addressCountry": "IR"
      }
    }
  }<?php if(!empty($product['rating_cache']) && $product['rating_cache'] > 0): ?>,
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?php echo $product['rating_cache']; ?>",
    "reviewCount": "<?php echo max(1, (int)($product['review_count_cache'] ?? count($reviews))); ?>"
  }
  <?php endif; ?>
}
</script>

<?php include 'includes/footer.php'; ?>
