<?php
/**
 * ASENA Enterprise - Universal Live Search Endpoint (Digikala Benchmark)
 * High-performance Omnibox search supporting:
 * - Bilingual cross-lingual queries (English <-> Persian)
 * - Synonym dictionary (e.g. "cat food" -> "غذای گربه", "vet" -> "دامپزشک")
 * - Smart Categorization paths (Digikala-style intent matching)
 * - Pet Shop Products, Pharmacy Medicines, Top Doctors, and Medical Centers/Organizations
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/Feature.php';

$rawQ = trim($_GET['q'] ?? '');
if (mb_strlen($rawQ, 'UTF-8') < 2) {
    echo json_encode([
        'status' => 'success',
        'query' => '',
        'total' => 0,
        'categories' => [],
        'results' => [
            'products' => [],
            'pharmacy' => [],
            'doctors' => [],
            'organizations' => []
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 1. Persian Text Normalization ─────────────────────────────────────────────
function normalizePersianText($text) {
    $text = mb_strtolower(trim($text), 'UTF-8');
    // Arabic to Persian character mapping
    $from = ['ي', 'ك', 'ة', 'ؤ', 'إ', 'أ', 'آ', 'ئ'];
    $to   = ['ی', 'ک', 'ه', 'و', 'ا', 'ا', 'ا', 'ی'];
    $text = str_replace($from, $to, $text);
    // Replace half-spaces (ZWNJ) and non-breaking spaces with standard space
    $text = preg_replace('/[\x{200c}\x{200b}\x{00a0}\s]+/u', ' ', $text);
    // Remove punctuation
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    return trim(preg_replace('/\s+/', ' ', $text));
}

$normalizedQuery = normalizePersianText($rawQ);
$lowerRaw = mb_strtolower($rawQ, 'UTF-8');

// ── 2. Bilingual & Semantic Thesaurus (English <-> Persian) ────────────────────
$thesaurus = [
    // Cat & Feline Nutrition
    'cat food' => ['غذای گربه', 'غذای خشک گربه', 'کنسرو گربه', 'پوچ گربه', 'گربه'],
    'catfood' => ['غذای گربه', 'غذای خشک گربه', 'کنسرو گربه', 'گربه'],
    'cat wet food' => ['کنسرو گربه', 'پوچ گربه', 'غذای تر گربه'],
    'cat dry food' => ['غذای خشک گربه', 'غذای گربه'],
    'kitten food' => ['غذای بچه گربه', 'کیتن', 'بچه گربه'],
    'cat litter' => ['خاک گربه', 'بستر گربه', 'خاک بنتونیت'],
    'litter' => ['خاک گربه', 'بستر گربه'],
    'cat' => ['گربه', 'پیشی', 'ملوس'],
    'kitten' => ['بچه گربه', 'کیتن'],

    // Dog & Canine Nutrition
    'dog food' => ['غذای سگ', 'غذای خشک سگ', 'کنسرو سگ', 'سگ'],
    'dogfood' => ['غذای سگ', 'غذای خشک سگ', 'کنسرو سگ', 'سگ'],
    'puppy food' => ['غذای توله سگ', 'پاپی'],
    'dog' => ['سگ', 'هاپو'],
    'puppy' => ['توله سگ', 'پاپی'],

    // Global Brands & Common Products
    'royal canin' => ['رویال کنین', 'رویال', 'royal canin'],
    'royalcanin' => ['رویال کنین', 'royal canin'],
    'royal' => ['رویال کنین', 'رویال'],
    'josera' => ['جوسرا', 'josera'],
    'reflex' => ['رفلکس', 'reflex'],
    'shayer' => ['شایر', 'shayer'],
    'nutripet' => ['نوتری پت', 'نوتری'],
    'gourmet' => ['گورمت', 'گورمت گلد'],
    'dr clauders' => ['دکتر کلودرز', 'کلودرز'],
    'gimcat' => ['جیم کت', 'gimcat'],
    'beaphar' => ['بیافار', 'beaphar'],
    'trixie' => ['تریکسی', 'trixie'],
    'collar' => ['قلاده', 'قلاده چرمی', 'لید'],
    'shampoo' => ['شامپو', 'شامپو ضد ریزش'],
    'toy' => ['اسباب‌بازی', 'توپ دندانی', 'درخت گربه'],
    'bed' => ['تشک خواب', 'جای خواب'],
    'scratch' => ['درخت گربه', 'اسکرچر'],
    'malt' => ['خمیر مالت', 'مالت'],
    'vitamin' => ['مولتی ویتامین', 'ویتامین', 'مکمل'],
    'supplement' => ['مکمل دارویی', 'مکمل غذایی', 'تقویتی'],

    // Veterinary & Medical Services
    'vet' => ['دامپزشک', 'پزشک', 'دکتر', 'کلینیک', 'بیمارستان'],
    'doctor' => ['دامپزشک', 'پزشک', 'دکتر', 'متخصص'],
    'physician' => ['پزشک', 'دامپزشک', 'دکتر'],
    'clinic' => ['کلینیک', 'بیمارستان', 'درمانگاه'],
    'hospital' => ['بیمارستان', 'کلینیک', 'اورژانس'],
    'emergency' => ['اورژانس', 'شبانه‌روزی', '۲۴ ساعته', 'icu'],
    'dentist' => ['دندانپزشکی', 'جرم‌گیری', 'دندان'],
    'dental' => ['دندانپزشکی', 'جرم‌گیری اولتراسونیک'],
    'orthopedic' => ['ارتوپدی', 'جراحی', 'شکستگی', 'ستون فقرات'],
    'surgery' => ['جراحی', 'بافت نرم', 'جراح'],
    'vaccine' => ['واکسن', 'واکسیناسیون', 'زنجیره سرد'],
    'grooming' => ['گرومر', 'گرومینگ', 'آرایشگاه', 'اصلاح مو', 'اسپا'],
    'groomer' => ['گرومر', 'استایلیست', 'آرایشگر', 'اصلاح'],
    'ultrasound' => ['سونوگرافی', 'داپلر', 'تصویربرداری'],
    'bird' => ['پرنده', 'طوطی', 'کاسکو', 'عروس هلندی', 'اگزوتیک'],
    'exotic' => ['اگزوتیک', 'خرگوش', 'همستر', 'ایگوانا', 'خزندگان'],
    'parasite' => ['ضد انگل', 'انگل', 'کرم‌کش', 'کک', 'کنه']
];

// Expand query terms based on synonyms
$searchTerms = [$rawQ, $normalizedQuery];
foreach ($thesaurus as $key => $syns) {
    if (
        strpos($lowerRaw, $key) !== false || 
        strpos($key, $lowerRaw) !== false ||
        strpos($normalizedQuery, $key) !== false ||
        strpos($key, $normalizedQuery) !== false
    ) {
        $searchTerms = array_merge($searchTerms, $syns);
    }
}
$searchTerms = array_unique(array_filter(array_map('trim', $searchTerms)));

// ── 3. Digikala-Style Category Intent Generator ──────────────────────────────
$categories = [];
$catQuery = urlencode($rawQ);

// Determine intents
$hasCatIntent = (stripos($lowerRaw, 'cat') !== false || mb_strpos($normalizedQuery, 'گربه') !== false);
$hasDogIntent = (stripos($lowerRaw, 'dog') !== false || mb_strpos($normalizedQuery, 'سگ') !== false);
$hasFoodIntent = (stripos($lowerRaw, 'food') !== false || mb_strpos($normalizedQuery, 'غذا') !== false || mb_strpos($normalizedQuery, 'کنسرو') !== false);
$hasMedIntent = (stripos($lowerRaw, 'med') !== false || mb_strpos($normalizedQuery, 'دارو') !== false || mb_strpos($normalizedQuery, 'مکمل') !== false || mb_strpos($normalizedQuery, 'قرص') !== false || mb_strpos($normalizedQuery, 'واکسن') !== false);
$hasDocIntent = (stripos($lowerRaw, 'vet') !== false || stripos($lowerRaw, 'doc') !== false || mb_strpos($normalizedQuery, 'پزشک') !== false || mb_strpos($normalizedQuery, 'دکتر') !== false || mb_strpos($normalizedQuery, 'ویزیت') !== false || mb_strpos($normalizedQuery, 'نوبت') !== false);
$hasHospIntent = (stripos($lowerRaw, 'hosp') !== false || stripos($lowerRaw, 'clinic') !== false || mb_strpos($normalizedQuery, 'بیمارستان') !== false || mb_strpos($normalizedQuery, 'کلینیک') !== false);

if ($hasCatIntent && $hasFoodIntent) {
    $categories[] = [
        'title' => 'غذای گربه',
        'subtitle' => 'در دسته‌بندی غذای خشک و تر گربه',
        'url' => 'shop.php?category=' . urlencode('غذای گربه') . '&q=' . $catQuery,
        'icon' => 'pets',
        'type' => 'shop'
    ];
    $categories[] = [
        'title' => 'مکمل و کنسرو تقویتی گربه',
        'subtitle' => 'در دسته‌بندی مکمل‌های دارویی و تشویقی',
        'url' => 'shop.php?category=' . urlencode('مکمل دارویی') . '&q=' . $catQuery,
        'icon' => 'medication',
        'type' => 'pharmacy'
    ];
} elseif ($hasDogIntent && $hasFoodIntent) {
    $categories[] = [
        'title' => 'غذای سگ',
        'subtitle' => 'در دسته‌بندی غذای خشک و تر سگ',
        'url' => 'shop.php?category=' . urlencode('غذای سگ') . '&q=' . $catQuery,
        'icon' => 'pets',
        'type' => 'shop'
    ];
    $categories[] = [
        'title' => 'کنسرو و تشویقی سگ',
        'subtitle' => 'در دسته‌بندی کنسرو و پوچ سگ',
        'url' => 'shop.php?category=' . urlencode('غذای سگ') . '&brand=' . urlencode('شایر'),
        'icon' => 'fastfood',
        'type' => 'shop'
    ];
} elseif ($hasDocIntent || $hasHospIntent) {
    $categories[] = [
        'title' => 'پزشکان و متخصصان کشیک',
        'subtitle' => 'نوبت‌دهی آنلاین و رزرو وقت ویزیت',
        'url' => 'booking.php?q=' . $catQuery,
        'icon' => 'stethoscope',
        'type' => 'doctor'
    ];
    $categories[] = [
        'title' => 'بیمارستان‌ها و کلینیک‌های شبانه‌روزی',
        'subtitle' => 'مراکز مجهز به اورژانس، ICU و تصویربرداری',
        'url' => 'organizations.php?q=' . $catQuery,
        'icon' => 'domain',
        'type' => 'organization'
    ];
} else {
    // General category paths
    $categories[] = [
        'title' => 'کالاهای پت‌شاپ مرتبط با «' . htmlspecialchars($rawQ) . '»',
        'subtitle' => 'جستجو در کلیه برندها و محصولات فروشگاهی',
        'url' => 'shop.php?q=' . $catQuery,
        'icon' => 'storefront',
        'type' => 'shop'
    ];
    $categories[] = [
        'title' => 'داروها و مکمل‌های دامپزشکی «' . htmlspecialchars($rawQ) . '»',
        'subtitle' => 'جستجو در داروخانه تخصصی آسنا',
        'url' => 'pharmacy.php?q=' . $catQuery,
        'icon' => 'medication',
        'type' => 'pharmacy'
    ];
}

$results = [
    'products' => [],
    'pharmacy' => [],
    'doctors' => [],
    'organizations' => []
];

try {
    // ── 4. Query Pet Shop Products ────────────────────────────────────────────
    if (Feature::has('petshop_catalog')) {
        $pLikes = [];
        $pParams = [];
        foreach ($searchTerms as $term) {
            $pLikes[] = "(p.name LIKE ? OR p.category LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
            $pParams[] = "%$term%";
            $pParams[] = "%$term%";
            $pParams[] = "%$term%";
            $pParams[] = "%$term%";
        }

        // Custom relevance ordering: exact phrase first, then discount, then rating
        $orderClause = "
            CASE 
                WHEN p.name LIKE " . $pdo->quote('%' . $rawQ . '%') . " THEN 1
                WHEN p.category LIKE " . $pdo->quote('%' . $rawQ . '%') . " THEN 2
                WHEN p.brand LIKE " . $pdo->quote('%' . $rawQ . '%') . " THEN 3
                ELSE 4
            END, p.id DESC
        ";

        $pSql = "
            SELECT p.id, p.name, p.category, p.brand, p.price, p.discount_price, p.image_url, 
                   p.stock, p.target_animal, p.rating_cache, p.review_count_cache
            FROM products p
            WHERE " . implode(" OR ", $pLikes) . "
            ORDER BY $orderClause
            LIMIT 5
        ";
        $stmt = $pdo->prepare($pSql);
        $stmt->execute($pParams);
        $pRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pRows as $r) {
            $finalPrice = $r['discount_price'] > 0 ? (int)$r['discount_price'] : (int)$r['price'];
            $discountPercent = ($r['discount_price'] > 0 && $r['price'] > 0) ? round((1 - ($r['discount_price'] / $r['price'])) * 100) : 0;
            $results['products'][] = [
                'id' => (int)$r['id'],
                'title' => $r['name'],
                'category' => $r['category'] ?: 'پت‌شاپ',
                'brand' => $r['brand'] ?: 'آسنا',
                'price' => $finalPrice,
                'old_price' => $r['discount_price'] > 0 ? (int)$r['price'] : null,
                'discount_percent' => $discountPercent,
                'image' => !empty($r['image_url']) ? $r['image_url'] : 'assets/images/logo.png',
                'stock' => (int)$r['stock'],
                'rating' => (float)($r['rating_cache'] ?: 4.8),
                'reviews_count' => (int)($r['review_count_cache'] ?: 12),
                'url' => 'product_details.php?id=' . (int)$r['id'] . '&type=product',
                'type' => 'product',
                'type_label' => 'کالای پت‌شاپ'
            ];
        }
    }

    // ── 5. Query Pharmacy Medicines ───────────────────────────────────────────
    if (Feature::has('pharmacy_catalog')) {
        $mLikes = [];
        $mParams = [];
        foreach ($searchTerms as $term) {
            $mLikes[] = "(m.name LIKE ? OR m.category LIKE ? OR m.target_animal LIKE ? OR m.description LIKE ?)";
            $mParams[] = "%$term%";
            $mParams[] = "%$term%";
            $mParams[] = "%$term%";
            $mParams[] = "%$term%";
        }

        $mOrderClause = "
            CASE 
                WHEN m.name LIKE " . $pdo->quote('%' . $rawQ . '%') . " THEN 1
                WHEN m.category LIKE " . $pdo->quote('%' . $rawQ . '%') . " THEN 2
                ELSE 3
            END, m.id DESC
        ";

        $mSql = "
            SELECT m.id, m.name, m.category, m.target_animal, m.price, m.discount_price, 
                   m.image_url, m.requires_prescription, m.requires_cold_chain AS is_cold_chain
            FROM pharmacy_medicines m
            WHERE " . implode(" OR ", $mLikes) . "
            ORDER BY $mOrderClause
            LIMIT 4
        ";
        $stmt = $pdo->prepare($mSql);
        $stmt->execute($mParams);
        $mRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mRows as $r) {
            $finalPrice = $r['discount_price'] > 0 ? (int)$r['discount_price'] : (int)$r['price'];
            $discountPercent = ($r['discount_price'] > 0 && $r['price'] > 0) ? round((1 - ($r['discount_price'] / $r['price'])) * 100) : 0;
            $results['pharmacy'][] = [
                'id' => (int)$r['id'],
                'title' => $r['name'],
                'category' => $r['category'] ?: 'داروخانه دامپزشکی',
                'animal' => $r['target_animal'] ?: 'all',
                'price' => $finalPrice,
                'old_price' => $r['discount_price'] > 0 ? (int)$r['price'] : null,
                'discount_percent' => $discountPercent,
                'image' => !empty($r['image_url']) ? $r['image_url'] : 'assets/images/pharma-default.svg',
                'requires_prescription' => (bool)$r['requires_prescription'],
                'is_cold_chain' => (bool)$r['is_cold_chain'],
                'url' => 'product_details.php?id=' . (int)$r['id'] . '&type=pharmacy',
                'type' => 'pharmacy',
                'type_label' => 'داروخانه دامپزشکی'
            ];
        }
    }

    // ── 6. Query Doctors & Specialists ────────────────────────────────────────
    if (Feature::has('clinic_booking')) {
        $dLikes = [];
        $dParams = [];
        foreach ($searchTerms as $term) {
            $dLikes[] = "(d.name LIKE ? OR d.specialty LIKE ? OR d.clinic_name LIKE ? OR d.tags LIKE ? OR d.bio LIKE ?)";
            $dParams[] = "%$term%";
            $dParams[] = "%$term%";
            $dParams[] = "%$term%";
            $dParams[] = "%$term%";
            $dParams[] = "%$term%";
        }

        $dSql = "
            SELECT d.id, d.name, d.specialty, d.provider_type, d.rating, d.review_count, 
                   d.image_url, d.price, d.clinic_name, d.is_emergency, d.organization_id
            FROM doctors d
            WHERE " . implode(" OR ", $dLikes) . "
            ORDER BY d.rating DESC, d.review_count DESC
            LIMIT 4
        ";
        $stmt = $pdo->prepare($dSql);
        $stmt->execute($dParams);
        $dRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dRows as $r) {
            $results['doctors'][] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'specialty' => $r['specialty'],
                'provider_type' => $r['provider_type'] ?: 'doctor',
                'clinic_name' => $r['clinic_name'] ?: 'کلینیک همکار آسنا',
                'rating' => (float)$r['rating'],
                'review_count' => (int)$r['review_count'],
                'image' => !empty($r['image_url']) ? $r['image_url'] : 'assets/images/logo.png',
                'price' => (int)$r['price'],
                'is_emergency' => (bool)$r['is_emergency'],
                'booking_url' => 'booking.php?doctor_id=' . (int)$r['id'],
                'profile_url' => 'doctor_profile.php?id=' . (int)$r['id'],
                'type' => 'doctor',
                'type_label' => ($r['provider_type'] === 'groomer') ? 'گرومر و استایلیست' : 'پزشک متخصص'
            ];
        }
    }

    // ── 7. Query Organizations (Hospitals & Clinics) ──────────────────────────
    if (Feature::has('clinic_booking')) {
        $oLikes = [];
        $oParams = [];
        foreach ($searchTerms as $term) {
            $oLikes[] = "(o.name LIKE ? OR o.city LIKE ? OR o.address LIKE ? OR o.facilities LIKE ? OR o.description LIKE ?)";
            $oParams[] = "%$term%";
            $oParams[] = "%$term%";
            $oParams[] = "%$term%";
            $oParams[] = "%$term%";
            $oParams[] = "%$term%";
        }

        $oSql = "
            SELECT o.id, o.name, o.slug, o.type, o.city, o.address, o.phone, o.emergency_phone,
                   o.rating, o.review_count, o.is_24_7, o.logo_url, o.facilities
            FROM organizations o
            WHERE o.status = 'approved' AND (" . implode(" OR ", $oLikes) . ")
            ORDER BY o.is_24_7 DESC, o.rating DESC, o.review_count DESC
            LIMIT 3
        ";
        $stmt = $pdo->prepare($oSql);
        $stmt->execute($oParams);
        $oRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($oRows as $r) {
            $results['organizations'][] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'type' => $r['type'],
                'city' => $r['city'],
                'address' => $r['address'],
                'phone' => $r['emergency_phone'] ?: $r['phone'],
                'rating' => (float)$r['rating'],
                'review_count' => (int)$r['review_count'],
                'is_24_7' => (bool)$r['is_24_7'],
                'facilities' => $r['facilities'],
                'image' => !empty($r['logo_url']) ? $r['logo_url'] : 'assets/images/logo.png',
                'profile_url' => !empty($r['slug']) ? 'organization_profile.php?slug=' . urlencode($r['slug']) : 'organization_profile.php?id=' . (int)$r['id'],
                'booking_url' => 'booking.php?org=' . (int)$r['id'],
                'type' => 'organization',
                'type_label' => $r['is_24_7'] ? 'بیمارستان شبانه‌روزی ۲۴/۷' : 'کلینیک تخصصی'
            ];
        }
    }

    $totalCount = count($results['products']) + count($results['pharmacy']) + count($results['doctors']) + count($results['organizations']);

    echo json_encode([
        'status' => 'success',
        'query' => $rawQ,
        'total' => $totalCount,
        'categories' => $categories,
        'results' => $results
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
