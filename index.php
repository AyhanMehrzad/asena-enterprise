<?php
include 'includes/header.php';

// Fetch top 12 latest pharmacy medicines for the high-density grid
$stmt = $pdo->prepare("SELECT * FROM pharmacy_medicines ORDER BY created_at DESC LIMIT 12");
$stmt->execute();
$premium_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch featured pet shop products
$petshop_products = [];
$bestseller_products = [];
if (Feature::has('petshop_catalog')) {
    try {
        $pStmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC LIMIT 12");
        $pStmt->execute();
        $petshop_products = $pStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch top best sellers & trending items with autoship prioritization
        $bs_stmt = $pdo->prepare("
            SELECT id, name, category, price, discount_price, image_url, brand, stock, 
                   target_animal, is_autoship, autoship_discount, rating_cache, review_count_cache 
            FROM products 
            WHERE stock > 0 
            ORDER BY is_autoship DESC, rating_cache DESC, review_count_cache DESC, id DESC 
            LIMIT 12
        ");
        $bs_stmt->execute();
        $bestseller_products = $bs_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}
if (empty($bestseller_products) && !empty($petshop_products)) {
    $bestseller_products = array_slice($petshop_products, 0, 12);
}


// Fetch top rated doctors based on rating & reviews
$top_doctors = [];
if (Feature::has('clinic_booking')) {
    try {
        $doc_stmt = $pdo->prepare("
            SELECT d.id, d.name, d.specialty, d.provider_type, d.rating, d.review_count,
                   d.image_url, d.price, d.clinic_name, d.is_emergency, d.bio, d.tags,
                   d.schedule_info, d.services_json, d.organization_id,
                   o.name as org_name, o.city as org_city, o.slug as org_slug, o.is_24_7 as org_is_24_7,
                   u.vet_council_number
            FROM doctors d
            LEFT JOIN organizations o ON d.organization_id = o.id
            LEFT JOIN users u ON d.user_id = u.id
            ORDER BY d.rating DESC, d.review_count DESC
            LIMIT 8
        ");
        $doc_stmt->execute();
        $top_doctors = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Fetch featured 24/7 emergency medical centers & clinics
$featured_organizations = [];
if (Feature::has('clinic_booking')) {
    try {
        $org_stmt = $pdo->prepare("
            SELECT id, name, slug, type, city, address, phone, emergency_phone, rating, review_count, is_24_7, facilities, logo_url, banner_url, description
            FROM organizations 
            WHERE status = 'approved' 
            ORDER BY is_24_7 DESC, rating DESC, review_count DESC
            LIMIT 6
        ");
        $org_stmt->execute();
        $featured_organizations = $org_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Compute upcoming 7 days for the fast online time reservation widget
$reservation_days = [];
for ($i = 0; $i < 7; $i++) {
    $ts = strtotime("+$i day");
    $gDate = date('Y-m-d', $ts);
    $dayOfWeekEn = strtolower(date('D', $ts));
    $dayName = function_exists('jdate') ? jdate('l', $ts) : date('l', $ts);
    $dayNumber = function_exists('jdate') ? jdate('j F', $ts) : date('d M', $ts);
    $shamsiPrefix = ($i === 0) ? 'امروز' : (($i === 1) ? 'فردا' : '');
    $reservation_days[] = [
        'gDate' => $gDate,
        'dayKey' => $dayOfWeekEn,
        'dayName' => $dayName,
        'dayNumber' => $dayNumber,
        'label' => $shamsiPrefix ? "$shamsiPrefix ($dayName)" : $dayName,
        'isToday' => ($i === 0)
    ];
}

// Fetch booked slots for the next 7 days
$booked_slots = [];
if (Feature::has('clinic_booking')) {
    try {
        $bStmt = $pdo->query("SELECT doctor_id, appointment_date, appointment_time FROM appointments WHERE appointment_date >= CURDATE() AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status != 'cancelled'");
        while ($row = $bStmt->fetch(PDO::FETCH_ASSOC)) {
            $docId = $row['doctor_id'];
            $d = $row['appointment_date'];
            $t = substr($row['appointment_time'], 0, 5);
            if (!isset($booked_slots[$docId])) $booked_slots[$docId] = [];
            if (!isset($booked_slots[$docId][$d])) $booked_slots[$docId][$d] = [];
            $booked_slots[$docId][$d][] = $t;
        }
    } catch (Exception $e) {}
}
$booked_slots_json = json_encode($booked_slots, JSON_UNESCAPED_UNICODE);
$doctors_json = json_encode($top_doctors, JSON_UNESCAPED_UNICODE);

// Live Platform Counters
$count_orgs = 12;
$count_doctors = 48;
$count_medicines = 160;
try {
    $count_orgs = (int)$pdo->query("SELECT count(*) FROM organizations WHERE status = 'approved'")->fetchColumn() ?: 12;
    $count_doctors = (int)$pdo->query("SELECT count(*) FROM doctors")->fetchColumn() ?: 48;
    $count_medicines = (int)$pdo->query("SELECT count(*) FROM pharmacy_medicines")->fetchColumn() ?: 160;
} catch (Exception $e) {}

// Fetch user wishlist if logged in or guest session
$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    $wishlist_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $wishlist_stmt->execute([$_SESSION['user_id']]);
    $user_wishlist = $wishlist_stmt->fetchAll(PDO::FETCH_COLUMN);
} elseif (isset($_SESSION['guest_wishlist']) && is_array($_SESSION['guest_wishlist'])) {
    $user_wishlist = $_SESSION['guest_wishlist'];
}

// Fetch active campaigns
$campaign_stmt = $pdo->query("SELECT * FROM campaigns WHERE status = 'active' ORDER BY created_at DESC");
$active_campaigns = $campaign_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch top 5 donors
$donor_stmt = $pdo->query("
    SELECT donor_name, SUM(amount) as total_donated 
    FROM donations 
    WHERE status = 'successful' AND donor_name != 'ناشناس'
    GROUP BY donor_name 
    ORDER BY total_donated DESC 
    LIMIT 5
");
$top_donors = $donor_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="w-full max-w-container-max mx-auto space-y-24">
        <!-- Hero Software-Style Interface -->
        <section class="relative min-h-[650px] lg:h-[800px] h-auto py-16 lg:py-0 rounded-[2.5rem] overflow-hidden workstation-module border-none">
            <div class="absolute inset-0 bg-[#f8fafc]">
                <div class="blurred-orb w-[800px] h-[800px] bg-primary-container -top-64 -right-64"></div>
                <div class="blurred-orb w-[600px] h-[600px] bg-secondary-container -bottom-32 -left-32 opacity-10">
                </div>
            </div>
            <div class="relative h-full flex items-center px-6 lg:px-20">
                <div class="w-full lg:w-1/2 space-y-8 z-10" id="hero-content">
                    <div id="hero-badge"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-outline-variant/30 rounded-full text-xs font-bold text-primary shadow-sm">
                        <span id="hero-badge-icon" class="material-symbols-outlined text-sm text-secondary-container">autorenew</span>
                        <span id="hero-badge-text">سیستم تحویل خودکار (Autoship)</span>
                    </div>
                    <h1 id="hero-title" class="text-4xl md:text-6xl lg:text-8xl font-bold text-primary leading-[1.2] lg:leading-[1] tracking-tight">
                        اشتراک هوشمند؛<br />همیشه در دسترس
                    </h1>
                    <p id="hero-desc" class="text-lg lg:text-xl text-on-surface-variant font-light max-w-lg leading-relaxed">
                        برنامه غذایی و دارویی پت شما هرگز متوقف نمی‌شود. با فعال‌سازی اشتراک، از تخفیف دائمی و اولویت در
                        خدمات بهره‌مند شوید.
                    </p>
                    <div class="flex items-center gap-4 pt-4">
                        <a id="hero-link" href="subscriptions.php"
                            class="inline-block text-center bg-primary-container text-white px-10 py-5 rounded-2xl font-bold shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                            شروع تجربه اشتراک
                        </a>
                        <div class="flex -space-x-3 space-x-reverse items-center pr-6">
                            <div class="w-12 h-12 rounded-full border-4 border-white bg-slate-200"></div>
                            <div class="w-12 h-12 rounded-full border-4 border-white bg-slate-300"></div>
                            <div
                                class="w-12 h-12 rounded-full border-4 border-white bg-primary-container flex items-center justify-center text-white text-[10px] font-bold">
                                ۵۰۰+</div>
                        </div>
                    </div>
                </div>
                <div id="hero-bg" class="absolute inset-0 md:left-0 md:top-0 h-full w-full md:w-1/2 opacity-20 md:opacity-100 bg-cover bg-center mask-fade-l md:mr-auto transition-all duration-700"
                    style='background-image: url("assets/images/cat-hero.jpg");'>
                </div>
            </div>
            <!-- Redesigned Slider Controls -->
            <!-- Arrow Buttons -->
            <div class="absolute inset-y-0 left-4 right-4 lg:left-8 lg:right-8 flex items-center justify-between pointer-events-none z-30">
                <button onclick="nextSlide()"
                    class="pointer-events-auto w-16 h-16 rounded-full backdrop-blur-md bg-white/20 border border-white/30 shadow-2xl flex items-center justify-center text-primary hover:bg-white hover:scale-110 transition-all">
                    <span class="material-symbols-outlined text-4xl font-bold">chevron_right</span>
                </button>
                <button onclick="prevSlide()"
                    class="pointer-events-auto w-16 h-16 rounded-full backdrop-blur-md bg-white/20 border border-white/30 shadow-2xl flex items-center justify-center text-primary hover:bg-white hover:scale-110 transition-all">
                    <span class="material-symbols-outlined text-4xl font-bold">chevron_left</span>
                </button>
            </div>
            <!-- Pagination Pills -->
            <div class="absolute bottom-12 left-1/2 -translate-x-1/2 z-30 flex items-center gap-3" id="hero-pills">
                <button onclick="goToSlide(0)" class="w-8 h-2 rounded-full bg-primary-container scale-125 transition-all cursor-pointer"></button>
                <button onclick="goToSlide(1)" class="w-3 h-2 rounded-full bg-primary-container/20 hover:bg-primary-container/40 transition-all cursor-pointer"></button>
                <button onclick="goToSlide(2)" class="w-3 h-2 rounded-full bg-primary-container/20 hover:bg-primary-container/40 transition-all cursor-pointer"></button>
                <button onclick="goToSlide(3)" class="w-3 h-2 rounded-full bg-primary-container/20 hover:bg-primary-container/40 transition-all cursor-pointer"></button>
            </div>
        </section>
        
        <!-- Live Platform Metrics & Trust Counters -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5 max-w-5xl mx-auto px-4 mt-6 mb-4">
            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">verified_user</span>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-black text-primary tracking-tight">+۲۵,۰۰۰</div>
                    <div class="text-[11px] text-slate-500 font-medium">تحویل موفق در کشور</div>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">local_hospital</span>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-black text-primary tracking-tight">+<?php echo $count_orgs; ?> مرکز</div>
                    <div class="text-[11px] text-slate-500 font-medium">بیمارستان و کلینیک</div>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">medical_services</span>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-black text-primary tracking-tight">+<?php echo $count_doctors; ?> پزشک</div>
                    <div class="text-[11px] text-slate-500 font-medium">متخصص و جراح کشیک</div>
                </div>
            </div>

            <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">ac_unit</span>
                </div>
                <div>
                    <div class="text-xl sm:text-2xl font-black text-primary tracking-tight">۲۴/۷ کشیک</div>
                    <div class="text-[11px] text-slate-500 font-medium">ارسال زنجیره سرد</div>
                </div>
            </div>
        </div>
        
        <!-- Asena Ecosystem Quick Services Hub (Relocated & Enhanced from Header) -->
        <section class="quick-services-hub my-6 lg:my-10" id="asenaServicesHub">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 px-2">
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-9 bg-primary rounded-full"></div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-primary tracking-tight">سامانه‌ها و خدمات تخصصی آسنا</h2>
                        <p class="text-xs sm:text-sm text-on-surface-variant font-medium mt-0.5">دسترسی سریع به کلیه بخش‌های سلامت، پزشکی، اشتراک هوشمند و ملزومات حیوانات خانگی</p>
                    </div>
                </div>
                <div class="hidden sm:flex items-center gap-2 bg-surface-container-low px-3.5 py-1.5 rounded-full border border-outline-variant/20 text-xs font-bold text-primary">
                    <span class="material-symbols-outlined text-sm text-secondary-container">verified_user</span>
                    <span>اکوسیستم یکپارچه آسنا</span>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 lg:gap-5">
                <!-- 1. مراکز درمانی و کلینیک‌ها -->
                <a href="organizations.php" class="group bg-white hover:bg-teal-50/40 border border-outline-variant/25 hover:border-teal-500/40 rounded-3xl p-5 flex flex-col items-center text-center transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1.5 relative overflow-hidden">
                    <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-teal-600 group-hover:text-white transition-all duration-300 shadow-inner">
                        <span class="material-symbols-outlined text-3xl">domain</span>
                    </div>
                    <span class="font-bold text-sm text-slate-800 group-hover:text-teal-700 transition-colors">مراکز درمانی</span>
                    <span class="text-[11px] text-slate-400 mt-1 group-hover:text-slate-600 transition-colors">کلینیک‌ها و آزمایشگاه‌ها</span>
                    <span class="mt-3 text-[10px] font-bold text-teal-600 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                        مشاهده مراکز <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </span>
                </a>

                <!-- 2. اشتراک خودکار (Autoship) -->
                <a href="subscriptions.php" class="group bg-white hover:bg-orange-50/40 border border-outline-variant/25 hover:border-secondary-container/40 rounded-3xl p-5 flex flex-col items-center text-center transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1.5 relative overflow-hidden">
                    <div class="w-14 h-14 rounded-2xl bg-orange-50 text-secondary-container flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-secondary-container group-hover:text-white transition-all duration-300 shadow-inner">
                        <span class="material-symbols-outlined text-3xl">autorenew</span>
                    </div>
                    <span class="font-bold text-sm text-slate-800 group-hover:text-secondary-container transition-colors">اشتراک خودکار</span>
                    <span class="text-[11px] text-slate-400 mt-1 group-hover:text-slate-600 transition-colors">تحویل دوره‌ای با تخفیف</span>
                    <span class="mt-3 text-[10px] font-bold text-secondary-container opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                        شروع اشتراک <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </span>
                </a>

                <!-- 3. دانشنامه تخصصی سلامت -->
                <a href="knowledge_base.php" class="group bg-white hover:bg-blue-50/40 border border-outline-variant/25 hover:border-blue-500/40 rounded-3xl p-5 flex flex-col items-center text-center transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1.5 relative overflow-hidden">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300 shadow-inner">
                        <span class="material-symbols-outlined text-3xl">auto_stories</span>
                    </div>
                    <span class="font-bold text-sm text-slate-800 group-hover:text-blue-700 transition-colors">دانشنامه و مقالات</span>
                    <span class="text-[11px] text-slate-400 mt-1 group-hover:text-slate-600 transition-colors">راهنمای جامع بیماری و غذا</span>
                    <span class="mt-3 text-[10px] font-bold text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                        مطالعه مقالات <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </span>
                </a>

                <!-- 4. خیریه و نجات حیوانات -->
                <a href="charity.php" class="group bg-white hover:bg-rose-50/40 border border-outline-variant/25 hover:border-rose-500/40 rounded-3xl p-5 flex flex-col items-center text-center transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1.5 relative overflow-hidden">
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-rose-500 group-hover:text-white transition-all duration-300 shadow-inner">
                        <span class="material-symbols-outlined text-3xl">volunteer_activism</span>
                    </div>
                    <span class="font-bold text-sm text-slate-800 group-hover:text-rose-600 transition-colors">خیریه و امداد</span>
                    <span class="text-[11px] text-slate-400 mt-1 group-hover:text-slate-600 transition-colors">درمان و غذارسانی حیوانات</span>
                    <span class="mt-3 text-[10px] font-bold text-rose-500 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                        حمایت و پویش‌ها <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </span>
                </a>

                <!-- 5. داروخانه تخصصی آنلاین -->
                <a href="pharmacy.php" class="group bg-white hover:bg-indigo-50/40 border border-outline-variant/25 hover:border-indigo-500/40 rounded-3xl p-5 flex flex-col items-center text-center transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1.5 relative overflow-hidden">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300 shadow-inner">
                        <span class="material-symbols-outlined text-3xl">medication</span>
                    </div>
                    <span class="font-bold text-sm text-slate-800 group-hover:text-indigo-700 transition-colors">داروخانه تخصصی</span>
                    <span class="text-[11px] text-slate-400 mt-1 group-hover:text-slate-600 transition-colors">ارسال نسخه و داروهای کمیاب</span>
                    <span class="mt-3 text-[10px] font-bold text-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                        ورود به داروخانه <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </span>
                </a>

                <!-- 6. نوبت‌دهی آنلاین -->
                <a href="booking.php" class="group bg-white hover:bg-emerald-50/40 border border-outline-variant/25 hover:border-emerald-500/40 rounded-3xl p-5 flex flex-col items-center text-center transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1.5 relative overflow-hidden">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300 shadow-inner">
                        <span class="material-symbols-outlined text-3xl">calendar_month</span>
                    </div>
                    <span class="font-bold text-sm text-slate-800 group-hover:text-emerald-700 transition-colors">نوبت‌دهی آنلاین</span>
                    <span class="text-[11px] text-slate-400 mt-1 group-hover:text-slate-600 transition-colors">رزرو ویزیت با پزشک متخصص</span>
                    <span class="mt-3 text-[10px] font-bold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                        رزرو آنلاین <span class="material-symbols-outlined text-xs">arrow_back</span>
                    </span>
                </a>
            </div>
        </section>
        
        <?php
        $activeFlash = App::flashSale()->getActiveFlashSale();
        if ($activeFlash):
            $discountPercent = round((1 - ($activeFlash['flash_price'] / max(1, $activeFlash['original_price']))) * 100);
            $endsTimestamp = strtotime($activeFlash['ends_at']);
        ?>
        <!-- Digikala Benchmark: پیشنهاد شگفت‌انگیز (Incredible Offer) Flash Sale Banner -->
        <section class="flash-sale-wrapper my-6 relative overflow-hidden" id="flashSaleBanner">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-6 relative z-10">
                <div class="flex flex-col sm:flex-row items-center gap-5 text-center sm:text-right">
                    <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white shadow-inner flex-shrink-0">
                        <span class="material-symbols-outlined text-4xl animate-pulse">local_fire_department</span>
                    </div>
                    <div>
                        <div class="flex items-center justify-center sm:justify-start gap-2 mb-1">
                            <span class="bg-white text-[#ef394e] text-xs font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">پیشنهاد شگفت‌انگیز</span>
                            <span class="text-xs text-white/80 font-medium">فرصت محدود</span>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-bold text-white"><?php echo htmlspecialchars($activeFlash['title'] ?? $activeFlash['product_name'] ?? 'پیشنهاد ویژه آسنا'); ?></h3>
                        <p class="text-xs sm:text-sm text-white/90 mt-1"><?php echo htmlspecialchars($activeFlash['product_name'] ?? ''); ?></p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-6 w-full lg:w-auto justify-end">
                    <!-- Countdown Clock -->
                    <div class="flex flex-col items-center sm:items-end">
                        <span class="text-xs text-white/80 mb-1.5 font-medium">زمان باقی‌مانده تا پایان تخفیف:</span>
                        <div class="countdown-clock" id="flashCountdown" data-end="<?php echo $endsTimestamp; ?>">
                            <div class="countdown-box"><span id="cdSeconds">00</span><div class="text-[9px] font-normal text-white/70">ثانیه</div></div>
                            <span class="text-lg font-bold text-white/60">:</span>
                            <div class="countdown-box"><span id="cdMinutes">00</span><div class="text-[9px] font-normal text-white/70">دقیقه</div></div>
                            <span class="text-lg font-bold text-white/60">:</span>
                            <div class="countdown-box"><span id="cdHours">00</span><div class="text-[9px] font-normal text-white/70">ساعت</div></div>
                        </div>
                    </div>

                    <!-- Price & Action -->
                    <div class="flex flex-col items-center sm:items-end gap-2">
                        <div class="flex items-baseline gap-2">
                            <span class="bg-amber-400 text-slate-900 font-extrabold text-sm px-2 py-0.5 rounded-lg"><?php echo $discountPercent; ?>% تخفیف</span>
                            <span class="line-through text-white/60 text-xs toman-price"><?php echo number_format($activeFlash['original_price']); ?></span>
                            <span class="text-2xl font-extrabold text-white toman-price"><?php echo number_format($activeFlash['flash_price']); ?> تومان</span>
                        </div>
                        
                        <!-- Quota Progress Bar -->
                        <div class="w-48 sm:w-56">
                            <div class="flex justify-between text-[11px] text-white/90 mb-1">
                                <span>ظرفیت باقیمانده</span>
                                <span><?php echo $activeFlash['claimed_percent']; ?>% فروخته شد</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar-fill" style="width: <?php echo min(100, $activeFlash['claimed_percent']); ?>%;"></div>
                            </div>
                        </div>

                        <a href="product_details.php?id=<?php echo $activeFlash['product_id']; ?>" class="mt-2 w-full sm:w-auto px-6 py-2.5 rounded-xl bg-white text-[#ef394e] font-bold text-sm hover:bg-white/90 transition-all shadow-lg text-center">
                            مشاهده و خرید با تخفیف ویژه
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <script>
        // Real-time Digikala Countdown Ticker
        (function() {
            const clock = document.getElementById('flashCountdown');
            if (!clock) return;
            const end = parseInt(clock.getAttribute('data-end')) * 1000;

            function update() {
                const now = Date.now();
                const diff = Math.max(0, end - now);
                const s = Math.floor((diff / 1000) % 60);
                const m = Math.floor((diff / 1000 / 60) % 60);
                const h = Math.floor(diff / (1000 * 60 * 60));

                document.getElementById('cdSeconds').innerText = String(s).padStart(2, '0');
                document.getElementById('cdMinutes').innerText = String(m).padStart(2, '0');
                document.getElementById('cdHours').innerText = String(h).padStart(2, '0');

                if (diff <= 0) {
                    document.getElementById('flashSaleBanner')?.remove();
                }
            }
            update();
            setInterval(update, 1000);
        })();
        </script>
        <?php endif; ?>
        

        <!-- 1. BEST DOCTORS & SPECIALISTS SHOWCASE (Based on Real User Ratings)       -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($top_doctors)): ?>
        <section class="best-doctors-showcase space-y-6 my-10" id="bestDoctorsSection">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 px-2">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-amber-50 text-amber-800 border border-amber-200/70 rounded-full text-xs font-black">
                        <span class="material-symbols-outlined text-sm text-amber-500" style="font-variation-settings: 'FILL' 1;">star</span>
                        <span>برترین پزشکان و متخصصان مورد اعتماد کاربران</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-primary tracking-tight">
                        پزشکان و جراحان برتر دامپزشکی کشور
                    </h2>
                    <p class="text-xs sm:text-sm text-on-surface-variant font-medium max-w-2xl">
                        انتخاب شده بر اساس بالاترین امتیاز و رضایت بیش از ۲۵,۰۰۰ سرپرست پت؛ دارای بورد تخصصی جراحی، داخلی، دندانپزشکی، پرندگان و اورژانس شبانه‌روزی.
                    </p>
                </div>
                <div class="flex items-center gap-2 self-start md:self-auto">
                    <a href="booking.php" class="bg-primary hover:bg-primary-container text-white px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all shadow-md flex items-center gap-1.5 shrink-0">
                        <span>مشاهده همه پزشکان</span>
                        <span class="material-symbols-outlined text-sm">arrow_left_alt</span>
                    </a>
                </div>
            </div>

            <!-- Doctor Category Filter Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 px-2 custom-scrollbar" id="doctorFilterPills">
                <button type="button" onclick="filterLandingDoctors('all', this)" class="landing-doc-filter active bg-primary text-white px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-sm">verified</span>
                    <span>همه متخصصین</span>
                </button>
                <button type="button" onclick="filterLandingDoctors('surgery', this)" class="landing-doc-filter bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-indigo-600">medical_services</span>
                    <span>جراحی و ارتوپدی</span>
                </button>
                <button type="button" onclick="filterLandingDoctors('internal', this)" class="landing-doc-filter bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-teal-600">health_and_safety</span>
                    <span>بیماری‌های داخلی و سونوگرافی</span>
                </button>
                <button type="button" onclick="filterLandingDoctors('emergency', this)" class="landing-doc-filter bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-rose-600">emergency</span>
                    <span>اورژانس و ICU شبانه‌روزی</span>
                </button>
                <button type="button" onclick="filterLandingDoctors('dental', this)" class="landing-doc-filter bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-sky-600">dentistry</span>
                    <span>دندانپزشکی تخصصی</span>
                </button>
                <button type="button" onclick="filterLandingDoctors('exotic', this)" class="landing-doc-filter bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-emerald-600">flutter_dash</span>
                    <span>پرندگان و اگزوتیک</span>
                </button>
                <button type="button" onclick="filterLandingDoctors('groomer', this)" class="landing-doc-filter bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-pink-600">content_cut</span>
                    <span>گرومینگ و استایل</span>
                </button>
            </div>

            <!-- Doctors High-Density Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5" id="landingDoctorsGrid">
                <?php foreach ($top_doctors as $doc): 
                    $docSpecialty = mb_strtolower($doc['specialty'], 'UTF-8');
                    $docCat = 'other';
                    if (strpos($docSpecialty, 'جراح') !== false || strpos($docSpecialty, 'ارتوپد') !== false) $docCat = 'surgery';
                    elseif (strpos($docSpecialty, 'داخلی') !== false || strpos($docSpecialty, 'سونوگرافی') !== false) $docCat = 'internal';
                    elseif ($doc['is_emergency'] || strpos($docSpecialty, 'اورژانس') !== false || strpos($docSpecialty, 'icu') !== false) $docCat = 'emergency';
                    elseif (strpos($docSpecialty, 'دندان') !== false) $docCat = 'dental';
                    elseif (strpos($docSpecialty, 'پرند') !== false || strpos($docSpecialty, 'اگزوتیک') !== false) $docCat = 'exotic';
                    elseif ($doc['provider_type'] === 'groomer' || strpos($docSpecialty, 'گرومر') !== false || strpos($docSpecialty, 'آرایشگر') !== false) $docCat = 'groomer';
                ?>
                <div class="landing-doc-card bg-white rounded-3xl p-5 border border-slate-100 hover:border-primary/40 shadow-sm hover:shadow-2xl transition-all duration-300 flex flex-col group relative overflow-hidden" data-category="<?= $docCat ?>">
                    <!-- Top Seal & Rating -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-800 text-[10px] font-black px-2.5 py-1 rounded-full border border-emerald-200/60">
                            <span class="material-symbols-outlined text-xs text-emerald-600">verified</span>
                            <span>پزشک تأیید شده</span>
                        </span>

                        <div class="flex items-center gap-1 text-amber-500 text-xs font-black bg-amber-50 px-2.5 py-1 rounded-xl border border-amber-200/50">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span><?= number_format($doc['rating'], 1) ?></span>
                            <span class="text-[10px] text-slate-400 font-normal">(<?= (int)$doc['review_count'] ?>)</span>
                        </div>
                    </div>

                    <!-- Doctor Avatar & Identity -->
                    <div class="flex items-center gap-3.5 mb-3.5">
                        <div class="relative w-16 h-16 rounded-2xl bg-indigo-50 border-2 border-indigo-100 overflow-hidden shrink-0 flex items-center justify-center text-indigo-600 group-hover:scale-105 transition-transform shadow-inner">
                            <?php if (!empty($doc['image_url'])): ?>
                                <img src="<?= htmlspecialchars($doc['image_url']) ?>" alt="<?= htmlspecialchars($doc['name']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="material-symbols-outlined text-3xl">stethoscope</span>
                            <?php endif; ?>
                            <span class="absolute bottom-1 right-1 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></span>
                        </div>
                        <div class="overflow-hidden">
                            <h3 class="font-black text-slate-900 text-base group-hover:text-primary transition-colors truncate">
                                <?= htmlspecialchars($doc['name']) ?>
                            </h3>
                            <div class="text-[11px] font-bold text-indigo-600 line-clamp-1 mt-0.5">
                                <?= htmlspecialchars($doc['specialty']) ?>
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-slate-400 mt-1 truncate">
                                <span class="material-symbols-outlined text-xs text-slate-400">local_hospital</span>
                                <span class="truncate"><?= htmlspecialchars($doc['clinic_name'] ?: ($doc['org_name'] ?: 'بیمارستان همکار آسنا')) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Bio Snippet -->
                    <?php if (!empty($doc['bio'])): ?>
                    <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed mb-3 bg-slate-50/70 p-2.5 rounded-xl border border-slate-100">
                        <?= htmlspecialchars($doc['bio']) ?>
                    </p>
                    <?php endif; ?>

                    <!-- Price & Consultation Fee -->
                    <div class="flex items-center justify-between pt-2 mb-4 border-t border-slate-100">
                        <span class="text-[11px] text-slate-400 font-medium">تعرفه ویزیت حضوری:</span>
                        <div class="text-xs font-black text-primary font-mono">
                            <?= number_format($doc['price']) ?> <span class="text-[9px] font-normal text-slate-500">تومان</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-auto grid grid-cols-2 gap-2 pt-2">
                        <button type="button" onclick="quickSelectDoctorForBooking(<?= (int)$doc['id'] ?>)" class="bg-primary hover:bg-primary-container text-white py-2.5 px-3 rounded-xl text-xs font-bold text-center transition-all shadow-sm flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-xs">calendar_month</span>
                            <span>رزرو سریع نوبت</span>
                        </button>
                        <a href="doctor_profile.php?id=<?= (int)$doc['id'] ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 px-3 rounded-xl text-xs font-bold text-center transition-colors flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-xs">account_circle</span>
                            <span>پروفایل و سوابق</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- 2. BEST ORGANIZATIONS & HOSPITALS SHOWCASE                                 -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($featured_organizations)): ?>
        <section class="medical-centers-showcase space-y-6 my-10" id="bestOrganizationsSection">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 px-2">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-teal-50 text-teal-800 border border-teal-200/70 rounded-full text-xs font-black">
                        <span class="w-2 h-2 rounded-full bg-teal-500 animate-ping"></span>
                        <span class="material-symbols-outlined text-sm">local_hospital</span>
                        <span>شبکه بیمارستان‌های شبانه‌روزی و کلینیک‌های طرف قرارداد</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-primary tracking-tight">
                        برترین مراکز درمانی و اورژانس ۲۴ ساعته
                    </h2>
                    <p class="text-xs sm:text-sm text-on-surface-variant font-medium max-w-2xl">
                        مجهز به جدیدترین دستگاه‌های رادیولوژی دیجیتال، سونوگرافی داپلر، آزمایشگاه خون و آی‌سی‌یو فوق تخصصی با امکان رزرو مستقیم.
                    </p>
                </div>
                <a href="organizations.php" class="bg-primary/5 text-primary hover:bg-primary hover:text-white px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all shadow-sm flex items-center gap-1.5 self-start md:self-auto shrink-0">
                    <span>مشاهده تمام مراکز درمانی</span>
                    <span class="material-symbols-outlined text-sm">arrow_left_alt</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($featured_organizations as $org): ?>
                <div class="bg-white rounded-3xl p-6 border border-slate-100 hover:border-teal-500/40 shadow-sm hover:shadow-2xl transition-all duration-300 flex flex-col group relative overflow-hidden">
                    <!-- Top Status Badges -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <?php if ($org['is_24_7']): ?>
                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-[11px] font-black px-3 py-1 rounded-full flex items-center gap-1.5 shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>شبانه‌روزی ۲۴/۷</span>
                            </span>
                        <?php else: ?>
                            <span class="bg-slate-100 text-slate-700 text-[11px] font-bold px-3 py-1 rounded-full">
                                کلینیک تخصصی
                            </span>
                        <?php endif; ?>

                        <div class="flex items-center gap-1 text-amber-500 text-xs font-black bg-amber-50 px-2.5 py-1 rounded-xl border border-amber-200/50">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span><?= number_format($org['rating'], 1) ?></span>
                            <span class="text-[10px] text-slate-400 font-normal">(<?= (int)$org['review_count'] ?> نظر)</span>
                        </div>
                    </div>

                    <!-- Organization Info -->
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-16 h-16 rounded-2xl bg-white text-teal-700 flex items-center justify-center shrink-0 border border-slate-200 overflow-hidden group-hover:scale-105 transition-transform shadow-sm p-1.5">
                            <img src="<?= htmlspecialchars($org['logo_url'] ?: 'assets/images/logo.png') ?>" alt="<?= htmlspecialchars($org['name'] ?? 'کلینیک دامپزشکی آسنا') ?>" class="w-full h-full object-contain">
                        </div>
                        <div class="overflow-hidden">
                            <h3 class="font-black text-slate-900 text-base group-hover:text-primary transition-colors line-clamp-1">
                                <?= htmlspecialchars($org['name']) ?>
                            </h3>
                            <div class="flex items-center gap-1 text-[11px] text-slate-500 mt-1">
                                <span class="material-symbols-outlined text-xs text-slate-400">location_on</span>
                                <span class="font-bold text-slate-700"><?= htmlspecialchars($org['city']) ?></span> — <span class="truncate max-w-[200px]"><?= htmlspecialchars($org['address']) ?></span>
                            </div>
                            <?php if(!empty($org['phone'])): ?>
                            <div class="text-[10px] text-slate-400 mt-1 font-mono flex items-center gap-1">
                                <span class="material-symbols-outlined text-[13px] text-emerald-600">call</span>
                                <span><?= htmlspecialchars($org['phone']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Facilities Chips -->
                    <?php if (!empty($org['facilities'])): 
                        $facList = array_slice(explode(',', $org['facilities']), 0, 4);
                    ?>
                    <div class="flex items-center gap-1.5 flex-wrap my-3">
                        <?php foreach ($facList as $fac): ?>
                            <span class="bg-slate-50 text-slate-600 text-[10px] font-medium px-2.5 py-1 rounded-lg border border-slate-100"><?= htmlspecialchars(trim($fac)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="mt-auto pt-4 border-t border-slate-100 flex items-center gap-2">
                        <button type="button" onclick="quickSelectOrgForBooking(<?= (int)$org['id'] ?>)" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white py-2.5 rounded-xl text-xs font-bold text-center transition-all shadow-sm flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-sm">calendar_month</span>
                            <span>رزرو نوبت در این مرکز</span>
                        </button>
                        <a href="organization_profile.php?slug=<?= urlencode($org['slug'] ?: $org['id']) ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2.5 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1" title="مشاهده تجهیزات و پزشکان این مرکز">
                            <span class="material-symbols-outlined text-sm">visibility</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- 3. INTERACTIVE TIME RESERVATION SECTION (Fast Online Booking on Landing)   -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <section class="time-reservation-section my-12" id="timeReservationSection">
            <div class="bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#0f172a] rounded-[2.5rem] p-6 sm:p-10 lg:p-12 text-white shadow-2xl relative overflow-hidden border border-slate-800">
                <!-- Ambient Background Glow Orbs -->
                <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-teal-500/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 max-w-5xl mx-auto space-y-8">
                    
                    <!-- Section Header -->
                    <div class="text-center space-y-3">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-xs font-extrabold text-emerald-400 border border-white/10">
                            <span class="material-symbols-outlined text-sm animate-pulse">event_available</span>
                            <span>رزرو آنی نوبت ویزیت و گرومینگ با تاییدیه پیامکی</span>
                        </div>
                        <h2 class="text-2xl sm:text-4xl font-black tracking-tight text-white">
                            سامانه آنلاین نوبت‌دهی و انتخاب زمان حضور
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                            پزشک یا خدمات مورد نظر را انتخاب نموده، تاریخ و ساعت دلخواه را تعیین و نوبت خود را ظرف چند ثانیه بدون معطلی نهایی کنید.
                        </p>
                    </div>

                    <!-- Multi-Step Interactive Booking Card -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 text-slate-800 shadow-xl border border-slate-100">
                        <form id="landingBookingForm" onsubmit="handleLandingBookingSubmit(event)">
                            <div class="space-y-8">
                                
                                <!-- STEP 1: Select Service / Specialty -->
                                <div>
                                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-xs">۱</span>
                                        <span>انتخاب نوع خدمت یا تخصص</span>
                                    </label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5" id="serviceSelectionGrid">
                                        <button type="button" onclick="selectLandingService('consultation', this)" class="landing-service-btn active p-3 rounded-2xl border-2 border-primary bg-primary/5 text-primary flex flex-col items-center gap-1.5 text-center transition-all">
                                            <span class="material-symbols-outlined text-2xl text-primary">health_and_safety</span>
                                            <span class="text-xs font-bold">معاینه و واکسیناسیون</span>
                                        </button>
                                        <button type="button" onclick="selectLandingService('internal', this)" class="landing-service-btn p-3 rounded-2xl border-2 border-slate-100 bg-slate-50 hover:bg-slate-100 text-slate-700 flex flex-col items-center gap-1.5 text-center transition-all">
                                            <span class="material-symbols-outlined text-2xl text-teal-600">monitor_heart</span>
                                            <span class="text-xs font-bold">ویزیت داخلی و سونوگرافی</span>
                                        </button>
                                        <button type="button" onclick="selectLandingService('surgery', this)" class="landing-service-btn p-3 rounded-2xl border-2 border-slate-100 bg-slate-50 hover:bg-slate-100 text-slate-700 flex flex-col items-center gap-1.5 text-center transition-all">
                                            <span class="material-symbols-outlined text-2xl text-indigo-600">medical_services</span>
                                            <span class="text-xs font-bold">جراحی و ارتوپدی</span>
                                        </button>
                                        <button type="button" onclick="selectLandingService('emergency', this)" class="landing-service-btn p-3 rounded-2xl border-2 border-slate-100 bg-slate-50 hover:bg-slate-100 text-slate-700 flex flex-col items-center gap-1.5 text-center transition-all">
                                            <span class="material-symbols-outlined text-2xl text-rose-600">emergency</span>
                                            <span class="text-xs font-bold">اورژانس ۲۴ ساعته</span>
                                        </button>
                                        <button type="button" onclick="selectLandingService('dental', this)" class="landing-service-btn p-3 rounded-2xl border-2 border-slate-100 bg-slate-50 hover:bg-slate-100 text-slate-700 flex flex-col items-center gap-1.5 text-center transition-all">
                                            <span class="material-symbols-outlined text-2xl text-sky-600">dentistry</span>
                                            <span class="text-xs font-bold">دندانپزشکی و جرم‌گیری</span>
                                        </button>
                                        <button type="button" onclick="selectLandingService('grooming', this)" class="landing-service-btn p-3 rounded-2xl border-2 border-slate-100 bg-slate-50 hover:bg-slate-100 text-slate-700 flex flex-col items-center gap-1.5 text-center transition-all">
                                            <span class="material-symbols-outlined text-2xl text-pink-600">content_cut</span>
                                            <span class="text-xs font-bold">گرومینگ و اصلاح مو</span>
                                        </button>
                                    </div>
                                    <input type="hidden" name="service_type" id="inputLandingServiceType" value="consultation">
                                </div>

                                <!-- STEP 2: Doctor Selection Grid -->
                                <div>
                                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-3 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-xs">۲</span>
                                            <span>انتخاب پزشک متخصص یا گرومر</span>
                                        </div>
                                        <span class="text-[11px] font-normal text-slate-400">یک متخصص را انتخاب کنید</span>
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" id="reservationDoctorPicker">
                                        <?php foreach ($top_doctors as $idx => $d): ?>
                                        <div onclick="selectReservationDoctor(<?= (int)$d['id'] ?>)" class="reservation-doc-opt <?= $idx === 0 ? 'selected ring-2 ring-primary border-primary bg-primary/5' : 'border-slate-200 bg-white hover:border-slate-300' ?> border rounded-2xl p-3.5 flex items-center gap-3 cursor-pointer transition-all relative" data-id="<?= (int)$d['id'] ?>">
                                            <img src="<?= htmlspecialchars($d['image_url'] ?: 'assets/images/logo.png') ?>" alt="<?= htmlspecialchars($d['name'] ?? 'دامپزشک متخصص') ?>" class="w-12 h-12 rounded-xl object-cover border border-slate-200 shrink-0 bg-slate-100">
                                            <div class="overflow-hidden flex-1">
                                                <div class="text-xs font-black text-slate-900 truncate"><?= htmlspecialchars($d['name']) ?></div>
                                                <div class="text-[10px] text-slate-500 truncate"><?= htmlspecialchars($d['specialty']) ?></div>
                                                <div class="text-[10px] font-bold text-primary mt-0.5 font-mono"><?= number_format($d['price']) ?> تومان</div>
                                            </div>
                                            <div class="flex items-center gap-0.5 text-amber-500 text-[10px] font-bold shrink-0 bg-amber-50 px-1.5 py-0.5 rounded-md">
                                                <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">star</span>
                                                <span><?= $d['rating'] ?></span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="doctor_id" id="inputLandingDoctorId" value="<?= !empty($top_doctors[0]['id']) ? (int)$top_doctors[0]['id'] : 31 ?>">
                                </div>

                                <!-- STEP 3: Persian Calendar Days & Available Time Slots -->
                                <div class="space-y-4">
                                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-xs">۳</span>
                                        <span>انتخاب روز و ساعت ویزیت</span>
                                    </label>

                                    <!-- 7-Day Shamsi Calendar Cards -->
                                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5" id="reservationDayChips">
                                        <?php foreach ($reservation_days as $dIdx => $day): ?>
                                        <button type="button" onclick="selectReservationDate('<?= $day['gDate'] ?>', '<?= $day['dayKey'] ?>', this)" class="landing-day-btn <?= $dIdx === 0 ? 'active bg-primary text-white shadow-md' : 'bg-slate-50 hover:bg-slate-100 text-slate-700' ?> p-3 rounded-2xl border border-slate-200/80 flex flex-col items-center justify-center gap-1 transition-all">
                                            <span class="text-[11px] font-bold opacity-80"><?= htmlspecialchars($day['label']) ?></span>
                                            <span class="text-sm font-black"><?= htmlspecialchars($day['dayNumber']) ?></span>
                                        </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="appointment_date" id="inputLandingDate" value="<?= $reservation_days[0]['gDate'] ?? date('Y-m-d') ?>">
                                    <input type="hidden" name="appointment_time" id="inputLandingTime" value="">

                                    <!-- Time Slots Grid -->
                                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-3">
                                        <div class="flex items-center justify-between text-xs text-slate-500 font-bold">
                                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-indigo-600">schedule</span> ساعت‌های حضور پزشک در این روز:</span>
                                            <span id="selectedDayLabel" class="text-primary font-black"><?= $reservation_days[0]['label'] ?></span>
                                        </div>
                                        <div id="landingTimeSlotsContainer" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                                            <!-- Dynamically generated by JS -->
                                        </div>
                                    </div>
                                </div>

                                <!-- STEP 4: Pet Information & Owner Contact -->
                                <div>
                                    <label class="block text-xs font-black text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-xs">۴</span>
                                        <span>اطلاعات بیمار و شماره همراه سرپرست</span>
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">نوع حیوان خانگی</label>
                                            <select name="pet_type" id="landingPetType" required class="w-full h-11 px-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                                                <option value="سگ">سگ (Dog)</option>
                                                <option value="گربه" selected>گربه (Cat)</option>
                                                <option value="پرنده">پرنده زینتی (Bird)</option>
                                                <option value="خرگوش">خرگوش / جونده (Rabbit)</option>
                                                <option value="سایر">سایر حیوانات خانگی</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">نام پت (اختیاری)</label>
                                            <input type="text" name="pet_name" id="landingPetName" placeholder="مثلاً: لوسی، تدی، ملوس" class="w-full h-11 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">نام سرپرست</label>
                                            <input type="text" name="owner_name" id="landingOwnerName" placeholder="نام و نام خانوادگی شما" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required class="w-full h-11 px-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">شماره موبایل (جهت دریافت پیامک)</label>
                                            <input type="tel" name="owner_phone" id="landingOwnerPhone" placeholder="۰۹۱۲۳۴۵۶۷۸۹" value="<?= htmlspecialchars($_SESSION['user_phone'] ?? '') ?>" required dir="ltr" class="w-full h-11 px-3 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-800 focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Section -->
                                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <span class="material-symbols-outlined text-emerald-600 text-lg">sms</span>
                                        <span>پس از ثبت، مشخصات نوبت و آدرس به صورت پیامک فوری برای شما ارسال می‌شود.</span>
                                    </div>
                                    <button type="submit" id="landingBookingSubmitBtn" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 rounded-2xl text-sm font-black shadow-xl hover:shadow-2xl transition-all flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-lg">check_circle</span>
                                        <span>تأیید و دریافت کد رهگیری نوبت</span>
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </section>

        <!-- Booking Success Confirmation Modal -->
        <div id="bookingSuccessModal" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 text-slate-800 shadow-2xl border border-slate-100 text-center space-y-6 animate-scale-up">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-inner">
                    <span class="material-symbols-outlined text-5xl">task_alt</span>
                </div>
                <div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900">نوبت شما با موفقیت رزرو گردید!</h3>
                    <p class="text-xs text-slate-500 mt-1">پیامک حاوی جزئیات نوبت و کد رهگیری به شماره شما ارسال شد.</p>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 text-right space-y-2.5 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                        <span class="text-slate-400">کد رهگیری سامانه:</span>
                        <span id="modalTrackingCode" class="font-mono font-black text-primary text-sm tracking-wider">ASN-00000</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                        <span class="text-slate-400">پزشک معالج:</span>
                        <span id="modalDoctorName" class="font-bold text-slate-800">---</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                        <span class="text-slate-400">مرکز درمانی:</span>
                        <span id="modalClinicName" class="font-bold text-slate-800">---</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
                        <span class="text-slate-400">تاریخ و زمان ویزیت:</span>
                        <span id="modalDateTime" class="font-bold text-emerald-700 font-sans">---</span>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <span class="text-slate-400">تعرفه ویزیت:</span>
                        <span id="modalFee" class="font-black text-slate-900 font-mono">---</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('bookingSuccessModal').classList.add('hidden')" class="flex-1 bg-primary text-white py-3 rounded-xl text-xs font-bold shadow-md hover:bg-primary-container transition-colors">
                        متوجه شدم و بستن
                    </button>
                    <a href="profile.php" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                        مشاهده در پنل کاربری
                    </a>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- NEW SECTION: BEST SELLERS & TRENDING PRODUCTS SHOWCASE                     -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($bestseller_products)): ?>
        <section class="bestsellers-showcase space-y-6 my-12" id="bestSellersSection">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 px-2">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-amber-50 text-amber-800 border border-amber-200/70 rounded-full text-xs font-black">
                        <span class="material-symbols-outlined text-sm text-amber-500" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                        <span>پرفروش‌ترین و محبوب‌ترین‌های پت‌شاپ و مکمل‌ها</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-primary tracking-tight">
                        محبوب‌ترین انتخاب‌های سرپرستان پت
                    </h2>
                    <p class="text-xs sm:text-sm text-on-surface-variant font-medium max-w-2xl">
                        برترین محصولات غذایی، درمانی، بهداشتی و اسباب‌بازی بر اساس بیش از ۲۵,۰۰۰ خرید موفق؛ دارای امتیاز بالای ۴.۸ و امکان فعال‌سازی ارسال خودکار (Autoship).
                    </p>
                </div>
                <div class="flex items-center gap-2 self-start md:self-auto">
                    <a href="shop.php" class="bg-primary hover:bg-primary-container text-white px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all shadow-md flex items-center gap-1.5 shrink-0">
                        <span>مشاهده کل ویترین فروشگاه</span>
                        <span class="material-symbols-outlined text-sm">arrow_left_alt</span>
                    </a>
                </div>
            </div>

            <!-- Best Seller Category Filters -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 px-2 custom-scrollbar" id="bestSellerFilterPills">
                <button type="button" onclick="filterBestSellers('all', this)" class="bestseller-tab-btn active bg-primary text-white px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-sm">auto_awesome</span>
                    <span>همه پرفروش‌ها</span>
                </button>
                <button type="button" onclick="filterBestSellers('dog', this)" class="bestseller-tab-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="text-sm">🐕</span>
                    <span>غذای سگ</span>
                </button>
                <button type="button" onclick="filterBestSellers('cat', this)" class="bestseller-tab-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="text-sm">🐈</span>
                    <span>غذای گربه</span>
                </button>
                <button type="button" onclick="filterBestSellers('supplement', this)" class="bestseller-tab-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-teal-600">medication_liquid</span>
                    <span>مکمل و درمانی</span>
                </button>
                <button type="button" onclick="filterBestSellers('hygiene', this)" class="bestseller-tab-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-sky-600">cleaning_services</span>
                    <span>خاک و بهداشتی</span>
                </button>
                <button type="button" onclick="filterBestSellers('toy', this)" class="bestseller-tab-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-amber-600">toys</span>
                    <span>اسباب‌بازی و سرگرمی</span>
                </button>
            </div>

            <!-- Best Sellers Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6" id="bestSellersGrid">
                <?php 
                $rank = 1;
                foreach ($bestseller_products as $prod): 
                    $cat = mb_strtolower($prod['category'] ?? '', 'UTF-8');
                    $target = mb_strtolower($prod['target_animal'] ?? '', 'UTF-8');
                    $filterGroup = 'other';
                    if (strpos($cat, 'سگ') !== false || $target === 'dog') $filterGroup = 'dog';
                    elseif (strpos($cat, 'گربه') !== false || $target === 'cat') $filterGroup = 'cat';
                    elseif (strpos($cat, 'مکمل') !== false || strpos($cat, 'دارو') !== false) $filterGroup = 'supplement';
                    elseif (strpos($cat, 'بهداشت') !== false || strpos($prod['name'], 'خاک') !== false || strpos($prod['name'], 'شامپو') !== false) $filterGroup = 'hygiene';
                    elseif (strpos($cat, 'اسباب') !== false || strpos($cat, 'toy') !== false) $filterGroup = 'toy';

                    $hasDiscount = !empty($prod['discount_price']) && $prod['discount_price'] < $prod['price'];
                    $discountPct = $hasDiscount ? round((1 - ($prod['discount_price'] / $prod['price'])) * 100) : 0;
                    $in_wishlist = in_array($prod['id'], $user_wishlist);
                ?>
                <div class="bestseller-card bg-white rounded-3xl p-4 sm:p-5 border border-slate-100 hover:border-amber-400/50 shadow-sm hover:shadow-2xl transition-all duration-300 flex flex-col group relative overflow-hidden" data-group="<?= $filterGroup ?>">
                    
                    <!-- Sales Rank Ribbon & Discount Badge -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="bestseller-rank-badge text-[10px] sm:text-xs px-2.5 py-1 rounded-full flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">military_tech</span>
                            <span>رتبه <?= $rank ?> فروش</span>
                        </span>

                        <?php if ($hasDiscount): ?>
                        <span class="bg-rose-500 text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm">
                            <?= $discountPct ?>% تخفیف
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Product Image & Wishlist -->
                    <div class="aspect-square bg-slate-50 rounded-2xl mb-3.5 overflow-hidden relative border border-slate-100/60">
                        <img loading="lazy" src="<?= htmlspecialchars($prod['image_url'] ?: 'assets/images/logo.png') ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        
                        <button type="button" onclick="toggleWishlist(this, <?= (int)$prod['id'] ?>)" class="absolute top-2.5 right-2.5 z-10 w-8 h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-400 hover:text-rose-600 transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' <?= $in_wishlist ? '1' : '0' ?>; color: <?= $in_wishlist ? '#dc2626' : 'inherit' ?>;">favorite</span>
                        </button>

                        <?php if (!empty($prod['is_autoship'])): ?>
                        <div class="absolute bottom-2 inset-x-2 bg-orange-600/90 backdrop-blur-md text-white py-1 px-2 rounded-xl text-[10px] font-bold flex items-center justify-center gap-1 shadow-md">
                            <span class="material-symbols-outlined text-xs">autorenew</span>
                            <span><?= (int)($prod['autoship_discount'] ?: 15) ?>٪ تخفیف با تحویل خودکار</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Category & Brand -->
                    <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1">
                        <span class="font-bold text-indigo-600"><?= htmlspecialchars($prod['category'] ?? 'پت‌شاپ') ?></span>
                        <?php if (!empty($prod['brand'])): ?>
                        <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md font-bold text-[10px]"><?= htmlspecialchars($prod['brand']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Product Title -->
                    <a href="product_details.php?id=<?= (int)$prod['id'] ?>&type=product" class="font-black text-slate-900 text-xs sm:text-sm line-clamp-2 hover:text-primary transition-colors leading-snug mb-2">
                        <?= htmlspecialchars($prod['name']) ?>
                    </a>

                    <!-- Rating and Reviews -->
                    <div class="flex items-center gap-1.5 text-xs text-amber-500 font-bold mb-3">
                        <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                        <span><?= number_format($prod['rating_cache'] ?: 4.8, 1) ?></span>
                        <span class="text-[10px] text-slate-400 font-normal">(<?= (int)($prod['review_count_cache'] ?: 18) ?> نظر)</span>
                    </div>

                    <!-- Price & Actions -->
                    <div class="mt-auto pt-3 border-t border-slate-100">
                        <div class="flex items-baseline justify-between mb-3">
                            <span class="text-[10px] text-slate-400 font-medium">قیمت:</span>
                            <div class="text-right">
                                <?php if ($hasDiscount): ?>
                                <div class="text-[10px] text-slate-400 line-through"><?= number_format($prod['price']) ?> تومان</div>
                                <div class="text-xs sm:text-sm font-black text-primary font-mono"><?= number_format($prod['discount_price']) ?> <span class="text-[9px] font-normal text-slate-500">تومان</span></div>
                                <?php else: ?>
                                <div class="text-xs sm:text-sm font-black text-primary font-mono"><?= number_format($prod['price']) ?> <span class="text-[9px] font-normal text-slate-500">تومان</span></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2">
                            <button type="button" onclick="addToCart(this, <?= (int)$prod['id'] ?>, 'standard')" class="w-full bg-primary hover:bg-primary-container text-white py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                                <span>افزودن به سبد خرید</span>
                            </button>
                        </div>
                    </div>

                </div>
                <?php 
                $rank++;
                endforeach; 
                ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- NEW SECTION: SMART AUTOSHIP & ROUTINE CARE (Chewy Benchmark)              -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <section class="smart-autoship-section my-14" id="autoshipFeatureSection">
            <div class="bg-gradient-to-br from-[#002d72] via-[#001f4d] to-[#081226] rounded-[2.5rem] p-6 sm:p-10 lg:p-14 text-white shadow-2xl relative overflow-hidden border border-blue-900/50">
                <!-- Glowing Ambient Light Spheres -->
                <div class="absolute -top-32 -left-32 w-80 h-80 bg-orange-500/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 space-y-10">
                    
                    <!-- Header -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="space-y-3 max-w-2xl">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-orange-500/20 text-orange-400 border border-orange-500/30 rounded-full text-xs font-black shadow-sm">
                                <span class="material-symbols-outlined text-sm animate-spin" style="animation-duration: 8s;">autorenew</span>
                                <span>سرویس تحویل خودکار دوره‌ای (Smart Autoship)</span>
                            </div>
                            <h2 class="text-2xl sm:text-4xl font-black tracking-tight text-white leading-tight">
                                دیگر نگران تمام شدن غذای پت نباشید؛ همیشه سر وقت تحویل بگیرید
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-300 font-light leading-relaxed">
                                بیش از ۸۰٪ کاربران آسنا غذای خشک، کنسرو، خاک و مکمل‌های پت خود را به صورت خودکار دریافت می‌کنند. تا <strong>۱۵٪ تخفیف همیشگی</strong>، <strong>ارسال رایگان درب منزل</strong> و امکان لغو یا تغییر زمان با یک کلیک بدون هیچ جریمه‌ای.
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row items-center gap-3 shrink-0">
                            <a href="subscriptions.php" class="w-full sm:w-auto bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white px-7 py-3.5 rounded-2xl text-xs sm:text-sm font-black shadow-lg hover:shadow-orange-500/25 transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-base">rocket_launch</span>
                                <span>ساخت پکیج اشتراک اختصاصی</span>
                            </a>
                            <a href="#suggested-plans" class="w-full sm:w-auto bg-white/10 hover:bg-white/20 text-white border border-white/20 px-5 py-3.5 rounded-2xl text-xs font-bold transition-all text-center">
                                مشاهده پلن‌های آماده
                            </a>
                        </div>
                    </div>

                    <!-- 3 Visual Workflow Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="bg-white/5 backdrop-blur-md rounded-3xl p-6 border border-white/10 flex flex-col space-y-3 hover:bg-white/10 transition-colors">
                            <div class="w-12 h-12 rounded-2xl bg-orange-500 text-white flex items-center justify-center font-black text-lg shadow-lg">
                                ۱
                            </div>
                            <h3 class="text-base font-black text-white">انتخاب محصول و زمان‌بندی</h3>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                برند و کالای مورد نیاز پت خود را مشخص کرده و دوره تحویل را بر اساس مصرف (هر ۲ هفته، ماهانه یا ۲ ماهه) تعیین فرمایید.
                            </p>
                        </div>

                        <div class="bg-white/5 backdrop-blur-md rounded-3xl p-6 border border-white/10 flex flex-col space-y-3 hover:bg-white/10 transition-colors">
                            <div class="w-12 h-12 rounded-2xl bg-teal-500 text-white flex items-center justify-center font-black text-lg shadow-lg">
                                ۲
                            </div>
                            <h3 class="text-base font-black text-white">۱۵٪ تخفیف دائمی و ارسال رایگان</h3>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                تمام فاکتورهای اشتراک خودکار شما مشمول تخفیف ویژه دائمی شده و بدون هزینه پیک مستقیماً به درب واحد شما تحویل داده می‌شود.
                            </p>
                        </div>

                        <div class="bg-white/5 backdrop-blur-md rounded-3xl p-6 border border-white/10 flex flex-col space-y-3 hover:bg-white/10 transition-colors">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500 text-white flex items-center justify-center font-black text-lg shadow-lg">
                                ۳
                            </div>
                            <h3 class="text-base font-black text-white">مدیریت ۱۰۰٪ منعطف و بدون قید</h3>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                به مسافرت می‌روید؟ فقط با یک کلیک در پنل کاربری، تاریخ تحویل را تغییر دهید، بسته‌ها را تعلیق یا در صورت تمایل لغو کنید.
                            </p>
                        </div>
                    </div>

                    <!-- Interactive Autoship Savings Calculator Simulator -->
                    <div class="bg-white rounded-3xl p-6 sm:p-8 text-slate-800 shadow-xl border border-slate-100">
                        <div class="flex flex-col lg:flex-row items-center justify-between gap-6 pb-6 border-b border-slate-100">
                            <div>
                                <h3 class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-orange-500">calculate</span>
                                    <span>محاسبه‌گر هوشمند میزان صرفه‌جویی اشتراک خودکار</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-1">
                                    پت خود را انتخاب کنید تا ببینید با اشتراک آسنا چه مقدار در هزینه‌های سالانه صرفه‌جویی می‌کنید:
                                </p>
                            </div>

                            <!-- Pet Selector Chips -->
                            <div class="flex items-center gap-2 bg-slate-100 p-1.5 rounded-2xl self-stretch sm:self-auto justify-center">
                                <button type="button" onclick="updateAutoshipSim('cat', this)" class="autoship-sim-btn active px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 bg-primary text-white shadow-sm">
                                    <span>🐈</span>
                                    <span>گربه خانگی</span>
                                </button>
                                <button type="button" onclick="updateAutoshipSim('small_dog', this)" class="autoship-sim-btn px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 text-slate-600 hover:text-primary">
                                    <span>🐕</span>
                                    <span>سگ کوچک (&lt;۱۰kg)</span>
                                </button>
                                <button type="button" onclick="updateAutoshipSim('large_dog', this)" class="autoship-sim-btn px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 text-slate-600 hover:text-primary">
                                    <span>🦮</span>
                                    <span>سگ بزرگ (&gt;۱۰kg)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Calculator Results Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6 text-center">
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                <div class="text-[11px] text-slate-400 font-bold mb-1">هزینه معمول خرید تکی ماهانه</div>
                                <div class="text-lg sm:text-xl font-black text-slate-800 font-mono" id="simRegularPrice">۱,۸۵۰,۰۰۰ تومان</div>
                                <div class="text-[10px] text-slate-400 mt-1" id="simBasketDetails">غذای خشک + ۲ کنسرو + خاک ۱۰L</div>
                            </div>

                            <div class="bg-orange-50 rounded-2xl p-4 border border-orange-200">
                                <div class="text-[11px] text-orange-700 font-bold mb-1">با تحویل خودکار آسنا (Autoship)</div>
                                <div class="text-lg sm:text-xl font-black text-orange-600 font-mono" id="simAutoshipPrice">۱,۵۷۰,۰۰۰ تومان</div>
                                <div class="text-[10px] text-orange-600 mt-1">۱۵٪ تخفیف همیشگی + ارسال کاملاً رایگان</div>
                            </div>

                            <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-200">
                                <div class="text-[11px] text-emerald-800 font-bold mb-1">میزان سود و صرفه‌جویی سالانه شما</div>
                                <div class="text-xl sm:text-2xl font-black text-emerald-700 font-mono" id="simYearlySavings">۳,۳۶۰,۰۰۰ تومان</div>
                                <div class="text-[10px] text-emerald-700 font-bold mt-1">معادل ۲ ماه خرید رایگان برای پت شما!</div>
                            </div>
                        </div>

                        <div class="mt-6 text-center">
                            <a href="subscriptions.php#custom-box-builder" class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white px-8 py-3.5 rounded-2xl text-xs sm:text-sm font-black shadow-lg hover:shadow-xl transition-all">
                                <span class="material-symbols-outlined text-sm">inventory_2</span>
                                <span>تنظیم سبد اشتراک خودکار برای این پت</span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- NEW SECTION: VISUAL SHOP CATEGORIES NAVIGATOR                              -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <section class="visual-categories-section space-y-6 my-12" id="visualShopCategories">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 px-2">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-indigo-50 text-indigo-800 border border-indigo-200/70 rounded-full text-xs font-black">
                        <span class="material-symbols-outlined text-sm text-indigo-600">category</span>
                        <span>دسته‌بندی‌های مصور ملزومات حیوانات خانگی</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-primary tracking-tight">
                        خرید آسان بر اساس نیاز و سلامت پت
                    </h2>
                    <p class="text-xs sm:text-sm text-on-surface-variant font-medium max-w-2xl">
                        تفکیک دقیق و استاندارد محصولات غذایی، درمانی، بهداشتی و سرگرمی بر اساس تاییدیه کلینیکال و استانداردهای جهانی.
                    </p>
                </div>
                <a href="shop.php" class="text-xs sm:text-sm font-bold text-primary hover:text-secondary-container transition-colors flex items-center gap-1 self-start sm:self-auto">
                    <span>مشاهده تمام دسته‌ها</span>
                    <span class="material-symbols-outlined text-sm">arrow_left_alt</span>
                </a>
            </div>

            <!-- 8 Visual Tiles Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 sm:gap-4">
                <a href="shop.php?q=غذای خشک" class="category-tile-modern p-4 text-center flex flex-col items-center gap-2.5 group">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-3xl group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white transition-all shadow-inner">
                        🥣
                    </div>
                    <span class="text-xs font-black text-slate-800 group-hover:text-primary transition-colors">غذای خشک</span>
                    <span class="text-[10px] text-slate-400">تخصصی و درمانی</span>
                </a>

                <a href="shop.php?q=کنسرو" class="category-tile-modern p-4 text-center flex flex-col items-center gap-2.5 group">
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-3xl group-hover:scale-110 group-hover:bg-rose-500 group-hover:text-white transition-all shadow-inner">
                        🥫
                    </div>
                    <span class="text-xs font-black text-slate-800 group-hover:text-primary transition-colors">کنسرو و پوچ</span>
                    <span class="text-[10px] text-slate-400">سوپ و غذای تر</span>
                </a>

                <a href="shop.php?q=تشویقی" class="category-tile-modern p-4 text-center flex flex-col items-center gap-2.5 group">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all shadow-inner">
                        🦴
                    </div>
                    <span class="text-xs font-black text-slate-800 group-hover:text-primary transition-colors">تشویقی و دنتال</span>
                    <span class="text-[10px] text-slate-400">سلامت دندان و لثه</span>
                </a>

                <a href="shop.php?category=مکمل دارویی" class="category-tile-modern p-4 text-center flex flex-col items-center gap-2.5 group">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl group-hover:scale-110 group-hover:bg-blue-500 group-hover:text-white transition-all shadow-inner">
                        💊
                    </div>
                    <span class="text-xs font-black text-slate-800 group-hover:text-primary transition-colors">مکمل و ویتامین</span>
                    <span class="text-[10px] text-slate-400">پوست، مو و مفاصل</span>
                </a>

                <a href="shop.php?q=خاک" class="category-tile-modern p-4 text-center flex flex-col items-center gap-2.5 group">
                    <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-3xl group-hover:scale-110 group-hover:bg-purple-500 group-hover:text-white transition-all shadow-inner">
                        🚽
                    </div>
                    <span class="text-xs font-black text-slate-800 group-hover:text-primary transition-colors">خاک و بهداشت</span>
                    <span class="text-[10px] text-slate-400">بنتونیت و کربن‌دار</span>
                </a>

                <a href="shop.php?category=اسباب‌بازی" class="category-tile-modern p-4 text-center flex flex-col items-center gap-2.5 group">
                    <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-3xl group-hover:scale-110 group-hover:bg-orange-500 group-hover:text-white transition-all shadow-inner">
                        🎾
                    </div>
                    <span class="text-xs font-black text-slate-800 group-hover:text-primary transition-colors">اسباب‌بازی</span>
                    <span class="text-[10px] text-slate-400">اسکرچر و درخت</span>
                </a>

                <a href="shop.php?category=لوازم بهداشتی" class="category-tile-modern p-4 text-center flex flex-col items-center gap-2.5 group">
                    <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-3xl group-hover:scale-110 group-hover:bg-teal-500 group-hover:text-white transition-all shadow-inner">
                        🧴
                    </div>
                    <span class="text-xs font-black text-slate-800 group-hover:text-primary transition-colors">شامپو و برس</span>
                    <span class="text-[10px] text-slate-400">ضد ریزش و کک</span>
                </a>

                <a href="shop.php?q=جای خواب" class="category-tile-modern p-4 text-center flex flex-col items-center gap-2.5 group">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center text-3xl group-hover:scale-110 group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                        🛏️
                    </div>
                    <span class="text-xs font-black text-slate-800 group-hover:text-primary transition-colors">جای خواب و باکس</span>
                    <span class="text-[10px] text-slate-400">تجهیزات نگهداری</span>
                </a>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- NEW SECTION: VALUE COMBO BOXES & ROUTINE CARE BUNDLES                      -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <section class="combo-boxes-section space-y-6 my-14" id="comboBundlesSection">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 px-2">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-rose-50 text-rose-800 border border-rose-200/70 rounded-full text-xs font-black">
                        <span class="material-symbols-outlined text-sm text-rose-500">inventory_2</span>
                        <span>پکیج‌های اقتصادی و کمبو باکس‌های ماهانه آسنا</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-primary tracking-tight">
                        باکس‌های سلامت و تغذیه با ۲۰٪ صرفه‌جویی
                    </h2>
                    <p class="text-xs sm:text-sm text-on-surface-variant font-medium max-w-2xl">
                        ترکیب هوشمندانه ملزومات ضروری یک ماه بر اساس سن و نژاد؛ دستچین شده توسط دامپزشکان متخصص آسنا با قیمتی بسیار به صرفه‌تر از خرید تکی.
                    </p>
                </div>
                <a href="subscriptions.php" class="bg-primary hover:bg-primary-container text-white px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all shadow-md flex items-center gap-1.5 self-start md:self-auto shrink-0">
                    <span>ساخت باکس شخصی</span>
                    <span class="material-symbols-outlined text-sm">tune</span>
                </a>
            </div>

            <!-- 3 Combo Box Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                
                <!-- Combo Box 1: Cat Starter Deluxe -->
                <div class="combo-box-card p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="bg-rose-50 text-rose-700 font-extrabold text-[11px] px-3 py-1 rounded-full border border-rose-200 flex items-center gap-1">
                                <span>🐈</span>
                                <span>ویژه گربه‌های خانگی</span>
                            </span>
                            <span class="bg-rose-500 text-white font-black text-xs px-2.5 py-0.5 rounded-lg shadow-sm">۲۳٪ تخفیف</span>
                        </div>

                        <h3 class="text-lg font-black text-slate-900 mb-2">باکس جامع سلامت و نشاط گربه</h3>
                        <p class="text-xs text-slate-500 mb-5 leading-relaxed">
                            پکیج کامل غذایی و بهداشتی یک‌ماهه برای گربه‌های عقیم شده یا خانگی با مواد درجه یک.
                        </p>

                        <!-- Items Included -->
                        <div class="space-y-2.5 bg-slate-50 rounded-2xl p-4 border border-slate-100 text-xs mb-6">
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>غذای خشک رفلکس پلاس عقیم شده (۱.۵ کیلوگرم)</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>۲ عدد کنسرو مرغ سوپرپرمیوم گورمت گلد</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>خاک بستر سوپر کلمپینگ پتوپیا (۱۰ کیلوگرم)</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>خمیر مالت ضد گلوله مو (Hairball) تریکسی</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-baseline justify-between mb-4">
                            <span class="text-xs text-slate-400">قیمت خرید جداگانه:</span>
                            <div class="text-right">
                                <span class="text-xs text-slate-400 line-through">۱,۲۸۰,۰۰۰ تومان</span>
                                <div class="text-lg font-black text-rose-600 font-mono">۹۹۰,۰۰۰ <span class="text-xs font-normal text-slate-500">تومان</span></div>
                            </div>
                        </div>

                        <button type="button" onclick="addToCart(this, 9, 'standard')" class="w-full bg-rose-600 hover:bg-rose-700 text-white py-3 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                            <span>سفارش این کمبو باکس با تخفیف</span>
                        </button>
                    </div>
                </div>

                <!-- Combo Box 2: Dog Vitality & Shine -->
                <div class="combo-box-card p-6 sm:p-7 flex flex-col justify-between border-2 border-primary/40 shadow-lg relative">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-primary text-white text-[10px] font-black px-4 py-1 rounded-full shadow-md">
                        🔥 پرفروش‌ترین پکیج ماه
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-4 mt-1">
                            <span class="bg-blue-50 text-blue-700 font-extrabold text-[11px] px-3 py-1 rounded-full border border-blue-200 flex items-center gap-1">
                                <span>🐕</span>
                                <span>ویژه سگ‌های نژاد کوچک و متوسط</span>
                            </span>
                            <span class="bg-primary text-white font-black text-xs px-2.5 py-0.5 rounded-lg shadow-sm">۱۹٪ تخفیف</span>
                        </div>

                        <h3 class="text-lg font-black text-slate-900 mb-2">باکس شادابی و درخشش سگ</h3>
                        <p class="text-xs text-slate-500 mb-5 leading-relaxed">
                            تغذیه پرمیوم، مولتی‌ویتامین و اسباب‌بازی دندانی برای افزایش انرژی و جلوگیری از ریزش مو.
                        </p>

                        <!-- Items Included -->
                        <div class="space-y-2.5 bg-slate-50 rounded-2xl p-4 border border-slate-100 text-xs mb-6">
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>غذای خشک رویال کنین مینی ادالت (۲ کیلوگرم)</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>قطره مولتی‌ویتامین و مواد معدنی شایر</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>توپ دندانی طناب‌دار ضد جرم و پلاک دندان</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>شامپو تقویتی ضد ریزش مو و لطافت پوست تریکسی</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-baseline justify-between mb-4">
                            <span class="text-xs text-slate-400">قیمت خرید جداگانه:</span>
                            <div class="text-right">
                                <span class="text-xs text-slate-400 line-through">۳,۹۸۰,۰۰۰ تومان</span>
                                <div class="text-lg font-black text-primary font-mono">۳,۲۵۰,۰۰۰ <span class="text-xs font-normal text-slate-500">تومان</span></div>
                            </div>
                        </div>

                        <button type="button" onclick="addToCart(this, 1, 'standard')" class="w-full bg-primary hover:bg-primary-container text-white py-3 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                            <span>سفارش این کمبو باکس با تخفیف</span>
                        </button>
                    </div>
                </div>

                <!-- Combo Box 3: Joint & Mobility Care -->
                <div class="combo-box-card p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="bg-teal-50 text-teal-700 font-extrabold text-[11px] px-3 py-1 rounded-full border border-teal-200 flex items-center gap-1">
                                <span>💊</span>
                                <span>ویژه مفاصل و ایمنی (سگ و گربه)</span>
                            </span>
                            <span class="bg-teal-600 text-white font-black text-xs px-2.5 py-0.5 rounded-lg shadow-sm">۲۱٪ تخفیف</span>
                        </div>

                        <h3 class="text-lg font-black text-slate-900 mb-2">باکس مراقبت درمانی و تقویت مفاصل</h3>
                        <p class="text-xs text-slate-500 mb-5 leading-relaxed">
                            فرموله شده برای حیوانات با سن بالای ۵ سال، مستعد آرتروز یا بعد از دوره‌های جراحی.
                        </p>

                        <!-- Items Included -->
                        <div class="space-y-2.5 bg-slate-50 rounded-2xl p-4 border border-slate-100 text-xs mb-6">
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>قرص گلوکوزامین و کندرویتین تخصصی مفاصل</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>قطره پروبیوتیک تنظیم فلور میکروبی روده</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>روغن سالمون غنی شده با امگا ۳ و ۶</span>
                            </div>
                            <div class="flex items-center gap-2 text-slate-700">
                                <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                                <span>تشویقی ارتوپدی بدون نمک و شکر</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-baseline justify-between mb-4">
                            <span class="text-xs text-slate-400">قیمت خرید جداگانه:</span>
                            <div class="text-right">
                                <span class="text-xs text-slate-400 line-through">۱,۷۵۰,۰۰۰ تومان</span>
                                <div class="text-lg font-black text-teal-700 font-mono">۱,۳۸۰,۰۰۰ <span class="text-xs font-normal text-slate-500">تومان</span></div>
                            </div>
                        </div>

                        <button type="button" onclick="addToCart(this, 6, 'standard')" class="w-full bg-teal-600 hover:bg-teal-700 text-white py-3 rounded-xl text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                            <span>سفارش این کمبو باکس با تخفیف</span>
                        </button>
                    </div>
                </div>

            </div>
        </section>

        <!-- Cycle Section - Rail Density (Functional & Clickable) -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
            <div class="lg:col-span-3 flex flex-col justify-center p-10 bg-surface-container-low rounded-[2rem] space-y-4">
                <h2 class="text-3xl font-bold text-primary">چرخه مراقبت هوشمند</h2>
                <p class="text-sm text-on-surface-variant leading-relaxed">خدمات یکپارچه برای سلامت همیشگی پت شما که به صورت ۲۴ ساعته مانیتور می‌شود.</p>
                <div class="flex gap-2 pt-4">
                    <div class="w-3 h-3 rounded-full bg-primary-container"></div>
                    <div class="w-3 h-3 rounded-full bg-outline-variant/30"></div>
                    <div class="w-3 h-3 rounded-full bg-outline-variant/30"></div>
                </div>
            </div>
            <div class="lg:col-span-9 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- 1. Checkup -->
                <a href="booking.php" class="group workstation-module rounded-[2rem] p-8 cursor-pointer hover:-translate-y-1.5 transition-all block">
                    <div class="w-16 h-16 bg-surface-container-low rounded-2xl flex items-center justify-center mb-6 group-hover:bg-primary-container group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-3xl">stethoscope</span>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2 group-hover:text-primary-container flex items-center justify-between">
                        <span>چک‌آپ دوره‌ای</span>
                        <span class="material-symbols-outlined text-sm opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                    </h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">یادآوری هوشمند معاینات بر اساس سن و نژاد دقیق پت شما.</p>
                </a>

                <!-- 2. Nutrition -->
                <a href="subscriptions.php" class="group workstation-module rounded-[2rem] p-8 cursor-pointer hover:-translate-y-1.5 transition-all block">
                    <div class="w-16 h-16 bg-surface-container-low rounded-2xl flex items-center justify-center mb-6 group-hover:bg-primary-container group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-3xl">nutrition</span>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2 group-hover:text-primary-container flex items-center justify-between">
                        <span>تغذیه اختصاصی</span>
                        <span class="material-symbols-outlined text-sm opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                    </h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">آنالیز تخصصی رژیم غذایی بر اساس فعالیت‌های روزانه.</p>
                </a>

                <!-- 3. Vaccination -->
                <a href="pharmacy.php?tag=vaccines" class="group workstation-module rounded-[2rem] p-8 cursor-pointer hover:-translate-y-1.5 transition-all block">
                    <div class="w-16 h-16 bg-surface-container-low rounded-2xl flex items-center justify-center mb-6 group-hover:bg-primary-container group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-3xl">vaccines</span>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2 group-hover:text-primary-container flex items-center justify-between">
                        <span>واکسیناسیون</span>
                        <span class="material-symbols-outlined text-sm opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                    </h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">مدیریت خودکار پرونده سلامت دیجیتال و هشدارهای واکسن.</p>
                </a>

                <!-- 4. Online Pharmacy -->
                <a href="pharmacy.php" class="group workstation-module rounded-[2rem] p-8 cursor-pointer hover:-translate-y-1.5 transition-all block border-2 border-secondary-container/30 bg-gradient-to-b from-white to-orange-50/30">
                    <div class="w-16 h-16 bg-secondary-container/10 text-secondary-container rounded-2xl flex items-center justify-center mb-6 group-hover:bg-secondary-container group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-3xl">medication</span>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2 group-hover:text-secondary-container flex items-center justify-between">
                        <span>داروخانه آنلاین</span>
                        <span class="material-symbols-outlined text-sm opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                    </h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">تامین و ارسال سریع نسخه‌های تخصصی درب منزل با زنجیره سرد.</p>
                </a>
            </div>
        </section>

        <!-- Pet Pharmacy Featured Section (Prominent Spotlight for Pharmacy Clients) -->
        <section class="bg-gradient-to-r from-primary via-primary-container to-[#001a48] text-white rounded-[2.5rem] p-8 lg:p-12 shadow-2xl relative overflow-hidden">
            <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8 border-b border-white/10 pb-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-secondary-container text-white rounded-full text-xs font-bold mb-3 shadow-md">
                            <span class="material-symbols-outlined text-sm">local_pharmacy</span>
                            داروخانه تخصصی حیوانات و ملزومات پزشکی
                        </div>
                        <h2 class="text-2xl lg:text-4xl font-bold tracking-tight">داروخانه آنلاین آسنا؛ همراه سلامت تمام گونه‌ها</h2>
                        <p class="text-sm text-white/75 mt-1 max-w-xl">تامین مستقیم داروهای کمیاب، واکسن‌ها، ضد انگل‌ها و مراقبت‌های ویژه برای اسب، دام، طیور، سگ و گربه.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="pharmacy.php" class="bg-secondary-container text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#ea580c] transition-all shadow-lg flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                            مشاهده تمام داروهای تخصصی
                        </a>
                    </div>
                </div>

                <!-- Species Quick Navigation Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-center">
                    <a href="pharmacy.php?animal=dog" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐕</span>
                        <span class="text-xs font-bold">داروهای سگ</span>
                    </a>
                    <a href="pharmacy.php?animal=cat" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐈</span>
                        <span class="text-xs font-bold">داروهای گربه</span>
                    </a>
                    <a href="pharmacy.php?animal=horse" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐎</span>
                        <span class="text-xs font-bold">داروهای اسب</span>
                    </a>
                    <a href="pharmacy.php?animal=cow" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐄</span>
                        <span class="text-xs font-bold">داروهای دام</span>
                    </a>
                    <a href="pharmacy.php?animal=chick" class="bg-white/10 hover:bg-white/20 border border-white/15 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center group-hover:scale-110 transition-transform">🐥</span>
                        <span class="text-xs font-bold">داروهای طیور</span>
                    </a>
                    <a href="pharmacy.php?tag=vaccines" class="bg-secondary-container/20 hover:bg-secondary-container/40 border border-secondary-container/40 p-4 rounded-2xl transition-all group flex flex-col items-center gap-2">
                        <span class="w-12 h-12 rounded-full bg-secondary-container text-white flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[20px]">vaccines</span>
                        </span>
                        <span class="text-xs font-bold text-secondary-container">واکسن و سرم</span>
                    </a>
                </div>
            </div>
        </section>
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- NEW FEATURE: INTERACTIVE PET CALORIE & NUTRITION CALCULATOR               -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <section class="pet-calorie-calculator-section my-16" id="petNutritionCalculator">
            <div class="bg-gradient-to-br from-slate-900 via-primary-container to-[#001d4a] rounded-[2.5rem] sm:rounded-[3rem] p-6 sm:p-10 lg:p-14 text-white shadow-2xl relative overflow-hidden border border-white/10">
                <!-- Background Decorative Glows -->
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Section Header -->
                <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-10 relative z-10 border-b border-white/10 pb-8">
                    <div class="space-y-3 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-emerald-400/15 text-emerald-300 border border-emerald-400/30 rounded-full text-xs font-black backdrop-blur-md">
                            <span class="material-symbols-outlined text-sm animate-pulse">calculate</span>
                            <span>محاسبه‌گر تخصصی جیره و رژیم غذایی</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black leading-tight">
                            محاسبه‌گر هوشمند کالری و مقدار غذای روزانه پت
                        </h2>
                        <p class="text-xs sm:text-sm text-white/80 font-light leading-relaxed">
                            بر اساس فرمول‌های معتبر دامپزشکی بین‌المللی (FEDIAF & WSAVA)؛ مشخصات پت خود را مشخص کنید تا نیاز انرژی روزانه (MER)، گرم غذای خشک دقیق و حجم آب مصرفی را در لحظه دریافت کنید.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md px-5 py-3 rounded-2xl border border-white/15 self-start lg:self-auto text-xs font-bold text-white/90 shadow-sm">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                        <span>محاسبه فوری بر اساس استاندارد جهانی دامپزشکی</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 relative z-10">
                    
                    <!-- Left Column: Interactive Inputs Form (7 cols) -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- 1. Pet Species -->
                        <div class="space-y-2.5">
                            <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۱</span>
                                انتخاب گونه پت:
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" onclick="setCalcSpecies('dog')" id="calcBtnDog" class="calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/25">
                                    <span class="text-xl">🐕</span>
                                    <span>سگ (Canine)</span>
                                </button>
                                <button type="button" onclick="setCalcSpecies('cat')" id="calcBtnCat" class="calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15">
                                    <span class="text-xl">🐈</span>
                                    <span>گربه (Feline)</span>
                                </button>
                            </div>
                        </div>

                        <!-- 2. Weight Slider -->
                        <div class="space-y-2.5 bg-white/5 p-5 rounded-2xl border border-white/10">
                            <div class="flex justify-between items-center">
                                <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۲</span>
                                    وزن دقیق پت:
                                </label>
                                <div class="flex items-center gap-1.5 bg-white/15 px-3 py-1 rounded-xl">
                                    <span id="calcWeightDisplay" class="font-mono text-base sm:text-lg font-black text-emerald-300">8.5</span>
                                    <span class="text-xs text-white/70">کیلوگرم</span>
                                </div>
                            </div>
                            <input type="range" id="calcWeightSlider" min="0.5" max="60" step="0.5" value="8.5" oninput="updateWeightFromSlider(this.value)" class="w-full accent-emerald-400 cursor-pointer h-2 bg-white/20 rounded-lg">
                            <div class="flex justify-between text-[11px] text-white/50 font-mono">
                                <span>۰.۵ کیلو (خیلی کوچک)</span>
                                <span>۱۵ کیلو</span>
                                <span>۳۰ کیلو</span>
                                <span>۶۰+ کیلو (غول‌پیکر)</span>
                            </div>
                        </div>

                        <!-- 3. Age / Life Stage -->
                        <div class="space-y-2.5">
                            <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۳</span>
                                مرحله زندگی و سن:
                            </label>
                            <div class="grid grid-cols-3 gap-2.5">
                                <button type="button" onclick="setCalcStage('puppy')" id="stageBtnPuppy" class="calc-stage-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs font-bold text-center transition-all">
                                    <div class="text-sm mb-0.5">🍼</div>
                                    <div class="font-black" id="labelPuppy">توله / رشد</div>
                                    <div class="text-[10px] text-white/60">زیر ۱ سال</div>
                                </button>
                                <button type="button" onclick="setCalcStage('adult')" id="stageBtnAdult" class="calc-stage-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs font-bold text-center transition-all shadow-md">
                                    <div class="text-sm mb-0.5">⭐</div>
                                    <div class="font-black">بالغ</div>
                                    <div class="text-[10px] text-white/60">۱ تا ۷ سال</div>
                                </button>
                                <button type="button" onclick="setCalcStage('senior')" id="stageBtnSenior" class="calc-stage-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs font-bold text-center transition-all">
                                    <div class="text-sm mb-0.5">👑</div>
                                    <div class="font-black">مسن / ارشد</div>
                                    <div class="text-[10px] text-white/60">بالای ۷ سال</div>
                                </button>
                            </div>
                        </div>

                        <!-- 4. Physiological Status & Activity -->
                        <div class="space-y-2.5">
                            <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۴</span>
                                وضعیت فعالیت و تحرک روزانه:
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <button type="button" onclick="setCalcActivity('neutered')" id="actBtnNeutered" class="calc-act-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs font-bold text-right transition-all flex items-center gap-2.5">
                                    <span class="material-symbols-outlined text-base">check_circle</span>
                                    <div>
                                        <div class="font-black">عقیم‌شده / معمول</div>
                                        <div class="text-[10px] text-white/60">تحرک متوسط آپارتمانی</div>
                                    </div>
                                </button>
                                <button type="button" onclick="setCalcActivity('active')" id="actBtnActive" class="calc-act-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-white/80 text-xs font-bold text-right transition-all flex items-center gap-2.5">
                                    <span class="material-symbols-outlined text-base opacity-70">directions_run</span>
                                    <div>
                                        <div class="font-black">بسیار پرتحرک</div>
                                        <div class="text-[10px] text-white/60">ورزشی، حیاطی یا بازی مداوم</div>
                                    </div>
                                </button>
                                <button type="button" onclick="setCalcActivity('diet')" id="actBtnDiet" class="calc-act-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-white/80 text-xs font-bold text-right transition-all flex items-center gap-2.5">
                                    <span class="material-symbols-outlined text-base opacity-70">scale</span>
                                    <div>
                                        <div class="font-black">نیازمند کاهش وزن</div>
                                        <div class="text-[10px] text-white/60">اضافه وزن / رژیم درمانی</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Live Results Dashboard Card (5 cols) -->
                    <div class="lg:col-span-5 flex flex-col justify-between bg-white/10 backdrop-blur-2xl rounded-[2rem] p-6 sm:p-8 border border-white/20 shadow-2xl relative">
                        <div class="space-y-6">
                            
                            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl" id="resPetEmoji">🐕</span>
                                    <div>
                                        <h3 class="text-sm font-black text-white" id="resPetTitle">برنامه غذایی سگ بالغ (۸.۵ کیلوگرم)</h3>
                                        <p class="text-[11px] text-emerald-300 font-bold" id="resPetSubtitle">عقیم‌شده با تحرک متوسط</p>
                                    </div>
                                </div>
                                <span class="bg-emerald-500/20 text-emerald-300 text-[10px] font-mono px-2.5 py-1 rounded-full border border-emerald-400/30">
                                    توصیه FEDIAF
                                </span>
                            </div>

                            <!-- 3 Main Metric Widgets -->
                            <div class="grid grid-cols-2 gap-3.5">
                                
                                <div class="bg-white/10 p-4 rounded-2xl border border-white/10">
                                    <div class="text-[11px] text-white/70 font-medium mb-1">کالری روزانه (MER):</div>
                                    <div class="text-xl sm:text-2xl font-black font-mono text-amber-300" id="resCalories">
                                        ۵۴۰ <span class="text-xs font-normal text-white/80 font-sans">کیلوکالری</span>
                                    </div>
                                    <div class="text-[10px] text-white/50 mt-1">انرژی متابولیک پایه روزانه</div>
                                </div>

                                <div class="bg-emerald-500/20 p-4 rounded-2xl border border-emerald-400/30">
                                    <div class="text-[11px] text-emerald-200 font-bold mb-1">غذای خشک روزانه:</div>
                                    <div class="text-xl sm:text-2xl font-black font-mono text-emerald-300" id="resKibbleGrams">
                                        ۱۴۵ <span class="text-xs font-normal text-white/80 font-sans">گرم در روز</span>
                                    </div>
                                    <div class="text-[10px] text-emerald-200/70 mt-1" id="resMealPortion">۲ وعده ۷۲ گرمی</div>
                                </div>

                                <div class="bg-white/10 p-4 rounded-2xl border border-white/10">
                                    <div class="text-[11px] text-white/70 font-medium mb-1">آب تازه روزانه:</div>
                                    <div class="text-xl sm:text-2xl font-black font-mono text-sky-300" id="resWaterMl">
                                        ۵۱۰ <span class="text-xs font-normal text-white/80 font-sans">میلی‌لیتر</span>
                                    </div>
                                    <div class="text-[10px] text-white/50 mt-1">حداقل آب شرب تصفیه‌شده</div>
                                </div>

                                <div class="bg-white/10 p-4 rounded-2xl border border-white/10">
                                    <div class="text-[11px] text-white/70 font-medium mb-1">صرفه‌جویی اشتراک:</div>
                                    <div class="text-xl sm:text-2xl font-black font-mono text-rose-300">
                                        ۲۰٪ <span class="text-xs font-normal text-white/80 font-sans">تخفیف دائمی</span>
                                    </div>
                                    <div class="text-[10px] text-white/50 mt-1">با سرویس تحویل خودکار</div>
                                </div>

                            </div>

                            <!-- Recommended Kibble Match Box -->
                            <div class="bg-gradient-to-r from-emerald-900/40 to-primary/40 p-4 rounded-2xl border border-emerald-400/30 flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-2xl shrink-0">
                                    🥘
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-black text-white" id="resRecommendedFood">غذای خشک رویال کنین مینی ادالت (ویژه نژاد کوچک)</div>
                                    <div class="text-[10px] text-white/70 mt-0.5">غنی از پروتئین هیدرولیزه‌شده و امگا ۳ برای مفاصل</div>
                                </div>
                            </div>

                        </div>

                        <!-- CTA Actions -->
                        <div class="pt-6 mt-6 border-t border-white/10 flex flex-col sm:flex-row gap-3">
                            <a href="shop.php" id="calcCtaShop" class="flex-1 bg-emerald-400 hover:bg-emerald-300 text-slate-900 py-3.5 px-4 rounded-xl font-black text-xs sm:text-sm text-center shadow-lg shadow-emerald-400/20 transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-lg">shopping_cart</span>
                                <span>سفارش غذای متناسب با پت</span>
                            </a>
                            <a href="subscriptions.php" class="bg-white/15 hover:bg-white/25 text-white py-3.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-center transition-all flex items-center justify-center gap-1.5 border border-white/20">
                                <span class="material-symbols-outlined text-lg">autorenew</span>
                                <span>اشتراک ماهانه</span>
                            </a>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        <!-- Interactive Calculator JavaScript Engine -->
        <script>
        (function() {
            let calcState = {
                species: 'dog',
                weight: 8.5,
                stage: 'adult',
                activity: 'neutered'
            };

            window.setCalcSpecies = function(species) {
                calcState.species = species;
                
                const btnDog = document.getElementById('calcBtnDog');
                const btnCat = document.getElementById('calcBtnCat');
                const labelPuppy = document.getElementById('labelPuppy');

                if (species === 'dog') {
                    btnDog.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/25';
                    btnCat.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15';
                    labelPuppy.textContent = 'توله سگ (زیر ۱ سال)';
                    if (calcState.weight > 60) calcState.weight = 60;
                } else {
                    btnCat.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/25';
                    btnDog.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15';
                    labelPuppy.textContent = 'بچه گربه (زیر ۱ سال)';
                    // If weight is above 12kg for cat, adjust slider
                    if (calcState.weight > 12) {
                        calcState.weight = 4.5;
                        document.getElementById('calcWeightSlider').value = 4.5;
                    }
                }
                recalculateNutrition();
            };

            window.updateWeightFromSlider = function(val) {
                calcState.weight = parseFloat(val);
                document.getElementById('calcWeightDisplay').textContent = calcState.weight.toFixed(1);
                recalculateNutrition();
            };

            window.setCalcStage = function(stage) {
                calcState.stage = stage;
                ['puppy', 'adult', 'senior'].forEach(s => {
                    const btn = document.getElementById('stageBtn' + s.charAt(0).toUpperCase() + s.slice(1));
                    if (s === stage) {
                        btn.className = 'calc-stage-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs font-bold text-center transition-all shadow-md';
                    } else {
                        btn.className = 'calc-stage-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs font-bold text-center transition-all text-white/80';
                    }
                });
                recalculateNutrition();
            };

            window.setCalcActivity = function(act) {
                calcState.activity = act;
                ['neutered', 'active', 'diet'].forEach(a => {
                    const btn = document.getElementById('actBtn' + a.charAt(0).toUpperCase() + a.slice(1));
                    if (a === act) {
                        btn.className = 'calc-act-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs font-bold text-right transition-all flex items-center gap-2.5 shadow-md';
                    } else {
                        btn.className = 'calc-act-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-white/80 text-xs font-bold text-right transition-all flex items-center gap-2.5';
                    }
                });
                recalculateNutrition();
            };

            function recalculateNutrition() {
                const W = calcState.weight;
                // RER = 70 * (Weight ^ 0.75) (Standard WSAVA/FEDIAF Formula)
                const rer = 70 * Math.pow(W, 0.75);

                let factor = 1.6;
                if (calcState.species === 'dog') {
                    if (calcState.stage === 'puppy') factor = 2.8;
                    else if (calcState.stage === 'senior') factor = 1.2;
                    else {
                        if (calcState.activity === 'neutered') factor = 1.6;
                        else if (calcState.activity === 'active') factor = 2.0;
                        else if (calcState.activity === 'diet') factor = 1.1;
                    }
                } else {
                    // Cat
                    if (calcState.stage === 'puppy') factor = 2.5;
                    else if (calcState.stage === 'senior') factor = 1.0;
                    else {
                        if (calcState.activity === 'neutered') factor = 1.2;
                        else if (calcState.activity === 'active') factor = 1.4;
                        else if (calcState.activity === 'diet') factor = 0.95;
                    }
                }

                const mer = Math.round(rer * factor);
                // Average premium dry kibble contains 3.75 kcal per gram
                const kibbleGrams = Math.round(mer / 3.75);

                // Water requirement (ml) ~ 55-65ml/kg for dogs, 45-55ml/kg for cats
                const waterMl = Math.round(W * (calcState.species === 'dog' ? 60 : 50));

                // Portions
                const meals = calcState.stage === 'puppy' ? 3 : 2;
                const portionGrams = Math.round(kibbleGrams / meals);

                // Update UI elements
                document.getElementById('resCalories').innerHTML = mer.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-white/80 font-sans">کیلوکالری</span>';
                document.getElementById('resKibbleGrams').innerHTML = kibbleGrams.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-white/80 font-sans">گرم در روز</span>';
                document.getElementById('resWaterMl').innerHTML = waterMl.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-white/80 font-sans">میلی‌لیتر</span>';
                document.getElementById('resMealPortion').textContent = meals + ' وعده ' + portionGrams.toLocaleString('fa-IR') + ' گرمی';

                // Titles & Recommendations
                const petLabel = calcState.species === 'dog' ? 'سگ' : 'گربه';
                const stageLabel = calcState.stage === 'puppy' ? (calcState.species === 'dog' ? 'توله سگ' : 'بچه‌گربه') : (calcState.stage === 'senior' ? 'ارشد / مسن' : 'بالغ');
                document.getElementById('resPetEmoji').textContent = calcState.species === 'dog' ? '🐕' : '🐈';
                document.getElementById('resPetTitle').textContent = `برنامه غذایی ${petLabel} ${stageLabel} (${W.toFixed(1)} کیلوگرم)`;

                const actDesc = calcState.activity === 'neutered' ? 'عقیم‌شده با تحرک متوسط' : (calcState.activity === 'active' ? 'پرتحرک و فعال' : 'نیازمند کاهش وزن و رژیمی');
                document.getElementById('resPetSubtitle').textContent = actDesc;

                // Recommended Product Text
                let foodName = '';
                if (calcState.species === 'dog') {
                    if (calcState.stage === 'puppy') foodName = 'غذای خشک رویال کنین مینی پاپی (توله‌های در حال رشد)';
                    else if (calcState.activity === 'diet') foodName = 'غذای خشک رژیمی هیلز پرفکت ویت (مدیریت وزن سگ)';
                    else foodName = 'غذای خشک رویال کنین مینی ادالت (ویژه سگ‌های نژاد کوچک)';
                } else {
                    if (calcState.stage === 'puppy') foodName = 'غذای خشک رویال کنین کیتن (بچه‌گربه‌های ۲ تا ۱۲ ماه)';
                    else if (calcState.activity === 'neutered') foodName = 'غذای خشک استرلایزد رفلکس پلاس گربه عقیم‌شده';
                    else foodName = 'غذای خشک رفلکس پلاس ادالت مرغ و برنج گربه';
                }
                document.getElementById('resRecommendedFood').textContent = foodName;

                // CTA Link
                const shopCta = document.getElementById('calcCtaShop');
                if (shopCta) {
                    shopCta.href = `shop.php?category=${calcState.species === 'dog' ? 'dog-food' : 'cat-food'}`;
                }
            }

            // Initial calculation on load
            recalculateNutrition();
        })();
        </script>
        <!-- AI Clinical Assistant & Support -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center" id="support-section">
            <div class="lg:col-span-4 space-y-8 p-10 workstation-module rounded-[2.5rem] h-full flex flex-col justify-center border-none relative overflow-hidden">
                <div class="absolute -right-20 -bottom-20 opacity-5">
                    <span class="material-symbols-outlined text-[300px]">cruelty_free</span>
                </div>
                <div class="inline-flex items-center gap-3 px-4 py-2 bg-primary-container/10 text-primary-container rounded-lg font-bold text-xs uppercase tracking-wider w-fit z-10">
                    <span class="material-symbols-outlined text-sm">support_agent</span>
                    پشتیبانی و دستیار هوشمند آسنا
                </div>
                <h2 class="text-3xl lg:text-4xl font-black text-primary leading-tight z-10">
                    دستیار هوش مصنوعی یا<br />پشتیبانی مدیریت آسنا؟
                </h2>
                <p class="text-base lg:text-lg text-on-surface-variant font-light leading-relaxed z-10">
                    برای بررسی سریع سوالات و راهنمایی سلامت با <b>لئو</b> مشورت کنید؛ جهت پیگیری اداری و سامانه‌ای با <b>مدیریت آسنا</b> ارتباط برقرار نمایید، و برای پرسش‌های بالینی و پذیرش، <b>مرکز درمانی</b> خود را انتخاب کنید.
                </p>
                <div class="space-y-3 z-10 pt-2">
                    <div class="bg-surface-container rounded-2xl p-1.5 flex items-center justify-between shadow-sm relative gap-1">
                        <button onclick="setChatMode('ai')" id="btn-mode-ai" class="flex-1 flex items-center justify-center gap-1.5 py-3 px-2 rounded-xl text-xs font-black bg-primary-container text-white shadow-md transition-all">
                            <span class="material-symbols-outlined text-base">smart_toy</span>
                            <span>لئو (AI)</span>
                        </button>
                        <button onclick="setChatMode('admin')" id="btn-mode-admin" class="flex-1 flex items-center justify-center gap-1.5 py-3 px-2 rounded-xl text-xs font-black text-on-surface-variant hover:text-primary transition-all">
                            <span class="material-symbols-outlined text-base">support_agent</span>
                            <span>مدیریت آسنا</span>
                        </button>
                        <button onclick="setChatMode('organization')" id="btn-mode-org" class="flex-1 flex items-center justify-center gap-1.5 py-3 px-2 rounded-xl text-xs font-black text-on-surface-variant hover:text-primary transition-all">
                            <span class="material-symbols-outlined text-base">apartment</span>
                            <span>مرکز درمانی</span>
                        </button>
                    </div>

                    <!-- Organization Selector for Clinic Communication -->
                    <div id="org-selector-container" class="hidden bg-white/80 backdrop-blur-sm p-3 rounded-2xl border border-outline-variant/30 space-y-1.5 shadow-sm">
                        <label class="block text-[11px] font-black text-primary flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm text-secondary-container">local_hospital</span>
                            <span>انتخاب مرکز درمانی طرف گفتگو:</span>
                        </label>
                        <select id="chat-org-select" onchange="changeChatOrganization(this.value)" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-primary cursor-pointer">
                            <?php
                            $activeClinics = $pdo->query("SELECT id, name, city FROM organizations WHERE status = 'approved' ORDER BY name ASC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
                            foreach($activeClinics as $ac): ?>
                                <option value="<?= (int)$ac['id'] ?>"><?= htmlspecialchars($ac['name']) ?> (<?= htmlspecialchars($ac['city']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[10px] text-slate-500">مکاتبه مستقیماً در تیکت اختصاصی این مرکز درمانی ثبت می‌شود (بدون دسترسی مستقیم به پزشک).</p>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-8 workstation-module rounded-[2.5rem] overflow-hidden flex flex-col h-[650px] border-none shadow-xl bg-surface-container-lowest">
                <!-- Chat Header -->
                <div class="bg-white border-b border-outline-variant/20 px-6 py-4 flex items-center justify-between shadow-sm z-10">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-14 h-14 rounded-full bg-primary-container/10 flex items-center justify-center text-primary-container border-2 border-primary-container" id="chat-avatar">
                                <span class="material-symbols-outlined text-3xl">cruelty_free</span> <!-- Lion/Animal Icon -->
                            </div>
                            <div class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white"></div>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-primary" id="chat-title">لئو (Leo)</h3>
                            <p class="text-xs text-on-surface-variant flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                آنلاین - آماده تشخیص
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-on-surface-variant transition-colors">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                    </div>
                </div>
                
                <!-- Chat Body -->
                <div class="flex-1 p-6 space-y-6 overflow-y-auto custom-scrollbar bg-surface-container-lowest relative" id="chat-messages">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                    <div class="absolute inset-0 bg-white/60 backdrop-blur-sm flex flex-col items-center justify-center z-20">
                        <span class="material-symbols-outlined text-6xl text-primary-container mb-4">lock</span>
                        <h3 class="text-xl font-bold text-primary mb-2">نیاز به ورود</h3>
                        <p class="text-sm text-on-surface-variant mb-6">برای استفاده از سیستم پشتیبانی، لطفا وارد حساب کاربری خود شوید.</p>
                        <a href="login.php" class="bg-primary-container text-white px-8 py-3 rounded-xl font-bold shadow-lg hover:-translate-y-1 transition-transform">ورود / ثبت‌نام</a>
                    </div>
                    <?php else: ?>
                    <div class="flex justify-center mb-8">
                        <div class="bg-surface-container px-4 py-1 rounded-full text-[10px] text-on-surface-variant font-bold shadow-sm">امروز</div>
                    </div>
                    <!-- Messages will be injected here via JS -->
                    <?php endif; ?>
                </div>
                
                <!-- Loading Indicator -->
                <div id="chat-typing" class="px-6 py-2 bg-surface-container-lowest hidden items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <div class="flex gap-1">
                        <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-1.5 h-1.5 bg-primary-container rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                    <span>لئو در حال تایپ است...</span>
                </div>

                <!-- Image Preview Overlay -->
                <div id="image-preview-container" class="hidden px-6 py-4 bg-surface-container border-t border-outline-variant/20 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img id="image-preview" src="" class="w-16 h-16 object-cover rounded-lg shadow-sm border border-outline-variant/30" alt="پیش‌نمایش تصویر انتخابی">
                        <div class="text-xs font-bold text-primary">تصویر ضمیمه شد</div>
                    </div>
                    <button type="button" onclick="clearImage()" class="w-8 h-8 bg-error/10 text-error rounded-full flex items-center justify-center hover:bg-error hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>

                <!-- Chat Footer (Input Bar) -->
                <div class="p-4 bg-white border-t border-outline-variant/20 z-10 <?php echo !isset($_SESSION['user_id']) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    <form id="chat-form" class="flex items-center gap-3 relative" onsubmit="sendChatMessage(event)">
                        <input type="file" id="chat-image-input" class="hidden" accept="image/*" onchange="handleImageSelect(this)">
                        <button type="button" onclick="document.getElementById('chat-image-input').click()" class="w-12 h-12 rounded-full hover:bg-primary-container/10 text-on-surface-variant hover:text-primary-container flex items-center justify-center transition-colors shrink-0">
                            <span class="material-symbols-outlined text-2xl">attach_file</span>
                        </button>
                        
                        <div class="flex-1 relative">
                            <input id="chat-input" class="w-full bg-surface-container-low border-none rounded-full pl-14 pr-6 py-4 focus:ring-2 focus:ring-primary-container transition-all text-sm font-medium" placeholder="پیام خود را بنویسید..." type="text" autocomplete="off" />
                            <button type="button" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full hover:bg-black/5 flex items-center justify-center text-on-surface-variant transition-colors">
                                <span class="material-symbols-outlined">sentiment_satisfied</span>
                            </button>
                        </div>
                        
                        <button type="submit" id="chat-send-btn" class="w-14 h-14 bg-primary-container text-white rounded-full hover:scale-105 hover:bg-primary transition-all flex items-center justify-center shadow-lg shrink-0">
                            <span class="material-symbols-outlined text-2xl -ml-1">send</span>
                        </button>
                    </form>
                </div>
            </div>
        </section>
        <!-- Charity Section -->
        <section class="bg-primary-container rounded-[2rem] md:rounded-[3.5rem] overflow-hidden relative text-white shadow-2xl p-6 md:p-12 lg:p-24">
            <div class="paw-pattern absolute inset-0"></div>
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-20 items-center text-center lg:text-right">
                <div class="space-y-6 md:space-y-10 flex flex-col items-center lg:items-start">
                    <div class="flex items-center gap-3 text-secondary-container font-bold bg-white/10 w-fit px-6 py-2 rounded-full border border-white/10 backdrop-blur-md">
                        <span class="material-symbols-outlined" style='font-variation-settings: "FILL" 1;'>volunteer_activism</span>
                        مسئولیت اجتماعی آسنا
                    </div>
                    <h2 class="text-4xl lg:text-6xl font-bold leading-tight">
                        حمایت از گربه‌های خیابان؛<br />با هر خرید شما
                    </h2>
                    <p class="text-xl text-white/70 font-light leading-relaxed">
                        ما بخشی از سود هر تراکنش را مستقیماً صرف تامین غذا و درمان سارقان قلب خیابان‌ها می‌کنیم.
                    </p>
                    <div class="flex flex-wrap justify-center lg:justify-start gap-8 md:gap-12 pt-4">
                        <div class="space-y-1">
                            <div class="text-3xl font-bold text-secondary-container">۱۲.۵ تن</div>
                            <div class="text-xs text-white/50 uppercase font-bold tracking-widest">غذای توزیع شده</div>
                        </div>
                        <div class="space-y-1">
                            <div class="text-3xl font-bold text-secondary-container">۳,۸۰۰+</div>
                            <div class="text-xs text-white/50 uppercase font-bold tracking-widest">فرشته درمان شده</div>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 pt-4 w-full sm:w-auto">
                        <a href="charity.php" class="inline-block bg-secondary-container text-white px-10 py-5 rounded-2xl font-bold shadow-xl hover:scale-105 transition-transform text-center">مشاهده کمپین‌ها و حمایت</a>
                    </div>
                </div>
                
                <div class="glass-card !bg-white/10 !border-white/10 rounded-[2.5rem] p-1.5 shadow-2xl relative">
                    <?php if(empty($active_campaigns)): ?>
                    <div class="bg-white rounded-[2.4rem] overflow-hidden p-10 text-center">
                        <h3 class="text-2xl font-bold text-primary">کمپین فعالی وجود ندارد</h3>
                        <p class="text-on-surface-variant mt-4">منتظر کمپین‌های جدید باشید.</p>
                    </div>
                    <?php else: ?>
                    
                    <div class="swiper-container charity-index-slider relative rounded-[2.4rem] overflow-hidden bg-white">
                        <div class="swiper-wrapper">
                            <?php foreach($active_campaigns as $camp): 
                                $percent = $camp['goal_amount'] > 0 ? min(100, round(($camp['current_amount'] / $camp['goal_amount']) * 100)) : 0;
                            ?>
                            <div class="swiper-slide">
                                <div class="h-64 md:h-80 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($camp['image_url'] ?: 'assets/images/placeholders/placeholder-campaign.svg'); ?>');"></div>
                                <div class="p-10 space-y-8 bg-white text-right">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-2xl font-bold text-primary line-clamp-1"><?php echo htmlspecialchars($camp['title']); ?></h3>
                                        <span class="bg-emerald-500 text-white text-[10px] px-3 py-1 rounded-full font-bold animate-pulse">فعال</span>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden flex items-stretch p-0.5">
                                            <div class="bg-gradient-to-l from-secondary-container to-orange-400 rounded-full transition-all duration-1000" style="width: <?php echo $percent; ?>%;"></div>
                                        </div>
                                        <div class="flex justify-between text-xs font-bold text-on-surface-variant">
                                            <span class="">هدف: <?php echo number_format($camp['goal_amount']); ?> تومان</span>
                                            <span class="text-primary"><?php echo number_format($camp['current_amount']); ?> جمع شده</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-pagination !bottom-4"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Top Donors Removed (Moved to Community Heroes) -->
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 md:gap-6 pb-24">
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">restaurant</span>
                </div>
                <a href="shop.php?q=غذای خشک" class="text-sm font-bold text-primary before:absolute before:inset-0">غذای خشک</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">medication_liquid</span>
                </div>
                <a href="shop.php?category=مکمل دارویی" class="text-sm font-bold text-primary before:absolute before:inset-0">مکمل و دارو</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">toys</span>
                </div>
                <a href="shop.php?category=اسباب‌بازی" class="text-sm font-bold text-primary before:absolute before:inset-0">اسباب‌بازی</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">cleaning_services</span>
                </div>
                <a href="shop.php?category=لوازم بهداشتی" class="text-sm font-bold text-primary before:absolute before:inset-0">بهداشتی</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-surface-container/50">
                <div
                    class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center group-hover:bg-primary-container group-hover:text-white transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">house</span>
                </div>
                <a href="shop.php?q=جای خواب" class="text-sm font-bold text-primary before:absolute before:inset-0">جای خواب</a>
            </div>
            <div
                class="relative workstation-module rounded-[2rem] p-10 flex flex-col items-center gap-6 group cursor-pointer border-none bg-primary-container text-white">
                <div
                    class="w-20 h-20 rounded-2xl bg-white/10 flex items-center justify-center group-hover:bg-white group-hover:text-primary-container transition-all shadow-sm">
                    <span class="material-symbols-outlined text-4xl">more_horiz</span>
                </div>
                <a href="shop.php" class="text-sm font-bold text-white before:absolute before:inset-0">مشاهده همه</a>
            </div>
        </section>
        <!-- Dual-Catalog Showcase: Pet Shop & Veterinary Pharmacy -->
        <section class="space-y-8 pb-12" id="catalogShowcase">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-secondary-container/10 text-secondary-container rounded-full text-xs font-bold">
                        <span class="material-symbols-outlined text-sm">shopping_bag</span>
                        ویترین جامع کاتالوگ آسنا
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black text-primary tracking-tight">محصولات برگزیده و داروهای تخصصی</h2>
                    <p class="text-on-surface-variant font-medium text-sm sm:text-base">تامین معتبرترین برندهای غذای پت، تشویقی، داروها و مکمل‌های درمانی با ضمانت اصالت</p>
                </div>
                
                <!-- Catalog Tabs -->
                <div class="flex items-center gap-2 bg-slate-100 p-1.5 rounded-2xl border border-slate-200/60 self-start sm:self-auto">
                    <button type="button" onclick="switchCatalogTab('petshop')" id="btn-tab-petshop" class="catalog-tab-btn px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 bg-primary text-white shadow-sm">
                        <span class="material-symbols-outlined text-sm">pets</span>
                        <span>پت‌شاپ و ملزومات</span>
                    </button>
                    <button type="button" onclick="switchCatalogTab('pharmacy')" id="btn-tab-pharmacy" class="catalog-tab-btn px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 text-slate-600 hover:text-primary">
                        <span class="material-symbols-outlined text-sm">medication</span>
                        <span>داروخانه تخصصی</span>
                    </button>
                </div>
            </div>
            
            <!-- Panel 1: Pet Shop Products -->
            <div id="panel-petshop" class="catalog-panel grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php 
                $shopItems = !empty($petshop_products) ? $petshop_products : $premium_products;
                foreach($shopItems as $product): 
                ?>
                <div class="bg-white rounded-3xl p-4 shadow-md hover:shadow-2xl transition-all duration-300 group flex flex-col relative border border-outline-variant/10">
                    <?php if(!empty($product['discount_price'])): ?>
                    <div class="absolute top-4 left-4 bg-secondary-container text-white text-[10px] px-2 py-1 rounded-full z-10 font-bold shadow-sm">تخفیف ویژه</div>
                    <?php endif; ?>
                    
                    <?php $in_wishlist = in_array($product['id'], $user_wishlist); ?>
                    <button type="button" onclick="toggleWishlist(this, <?php echo $product['id']; ?>)" class="absolute top-4 right-4 z-10 w-8 h-8 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-on-surface hover:text-error transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' <?php echo $in_wishlist ? '1' : '0'; ?>; color: <?php echo $in_wishlist ? '#dc2626' : 'inherit'; ?>;">favorite</span>
                    </button>

                    <div class="aspect-square bg-surface-container-lowest rounded-2xl mb-4 overflow-hidden relative">
                        <img loading="lazy" src="<?php echo htmlspecialchars($product['image_url'] ?: 'assets/images/logo.png'); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        
                        <div class="absolute inset-x-0 bottom-0 p-2 translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-gradient-to-t from-black/60 to-transparent flex justify-center z-20">
                            <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>, 'standard')" class="bg-primary text-white w-full py-2 rounded-xl text-xs font-bold flex justify-center items-center gap-1 hover:bg-primary-container">
                                <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                                افزودن به سبد
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col">
                        <p class="text-[10px] text-on-surface-variant mb-1 line-clamp-1">
                            <?php echo htmlspecialchars($product['category'] ?? 'پت‌شاپ'); ?>
                            <?php if(!empty($product['brand'])) echo ' • <span class="text-primary font-bold">' . htmlspecialchars($product['brand']) . '</span>'; ?>
                        </p>
                        <a href="product_details.php?id=<?php echo $product['id']; ?>&type=product"><h3 class="text-sm font-bold text-on-surface mb-2 line-clamp-2 hover:text-primary transition-colors cursor-pointer leading-tight"><?php echo htmlspecialchars($product['name']); ?></h3></a>
                        <div class="mt-auto flex justify-between items-center">
                            <div class="flex flex-col">
                                <?php if(!empty($product['discount_price'])): ?>
                                <span class="text-[10px] text-on-surface-variant line-through mb-0.5"><?php echo number_format($product['price']); ?> تومان</span>
                                <span class="text-sm font-bold text-primary"><?php echo number_format($product['discount_price']); ?> تومان</span>
                                <?php else: ?>
                                <span class="text-sm font-bold text-primary"><?php echo number_format($product['price']); ?> تومان</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Panel 2: Veterinary Pharmacy Medicines -->
            <div id="panel-pharmacy" class="catalog-panel hidden grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach($premium_products as $product): ?>
                <div class="bg-white rounded-3xl p-4 shadow-md hover:shadow-2xl transition-all duration-300 group flex flex-col relative border border-outline-variant/10">
                    <div class="absolute top-4 left-4 flex flex-col gap-1 z-10">
                        <?php if(!empty($product['requires_prescription'])): ?>
                            <span class="bg-rose-500 text-white text-[9px] px-2 py-0.5 rounded-full font-bold shadow-sm">نسخه‌ای</span>
                        <?php endif; ?>
                        <?php if(!empty($product['is_cold_chain'])): ?>
                            <span class="bg-blue-500 text-white text-[9px] px-2 py-0.5 rounded-full font-bold shadow-sm">زنجیره سرد</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php $in_wishlist = in_array($product['id'], $user_wishlist); ?>
                    <button type="button" onclick="toggleWishlist(this, <?php echo $product['id']; ?>)" class="absolute top-4 right-4 z-10 w-8 h-8 bg-white/80 backdrop-blur-md rounded-full flex items-center justify-center text-on-surface hover:text-error transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' <?php echo $in_wishlist ? '1' : '0'; ?>; color: <?php echo $in_wishlist ? '#dc2626' : 'inherit'; ?>;">favorite</span>
                    </button>

                    <div class="aspect-square bg-surface-container-lowest rounded-2xl mb-4 overflow-hidden relative">
                        <img loading="lazy" src="<?php echo htmlspecialchars($product['image_url'] ?: 'assets/images/logo.png'); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        
                        <div class="absolute inset-x-0 bottom-0 p-2 translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-gradient-to-t from-black/60 to-transparent flex justify-center z-20">
                            <button type="button" onclick="addToCart(this, <?php echo $product['id']; ?>, 'standard')" class="bg-indigo-600 text-white w-full py-2 rounded-xl text-xs font-bold flex justify-center items-center gap-1 hover:bg-indigo-700">
                                <span class="material-symbols-outlined text-sm">add_shopping_cart</span>
                                افزودن به سبد
                            </button>
                        </div>
                    </div>
                    <div class="flex-1 flex flex-col">
                        <p class="text-[10px] text-indigo-600 font-bold mb-1 line-clamp-1">
                            <?php echo htmlspecialchars($product['category'] ?? 'داروخانه'); ?>
                        </p>
                        <a href="product_details.php?id=<?php echo $product['id']; ?>&type=pharmacy"><h3 class="text-sm font-bold text-on-surface mb-2 line-clamp-2 hover:text-indigo-600 transition-colors cursor-pointer leading-tight"><?php echo htmlspecialchars($product['name']); ?></h3></a>
                        <div class="mt-auto flex justify-between items-center">
                            <div class="flex flex-col">
                                <?php if(!empty($product['discount_price'])): ?>
                                <span class="text-[10px] text-on-surface-variant line-through mb-0.5"><?php echo number_format($product['price']); ?> تومان</span>
                                <span class="text-sm font-bold text-indigo-700"><?php echo number_format($product['discount_price']); ?> تومان</span>
                                <?php else: ?>
                                <span class="text-sm font-bold text-indigo-700"><?php echo number_format($product['price']); ?> تومان</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer Link -->
            <div class="text-center pt-4">
                <a href="shop.php" class="inline-flex items-center gap-2 bg-primary text-white hover:bg-primary-container px-8 py-3.5 rounded-2xl text-sm font-bold shadow-lg hover:shadow-xl transition-all">
                    <span>مشاهده کلیه کاتالوگ فروشگاه و داروخانه</span>
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                </a>
            </div>
        </section>

        <script>
        function switchCatalogTab(tab) {
            document.querySelectorAll('.catalog-tab-btn').forEach(b => {
                b.classList.remove('bg-primary', 'text-white', 'shadow-sm');
                b.classList.add('text-slate-600');
            });
            document.querySelectorAll('.catalog-panel').forEach(p => p.classList.add('hidden'));

            const btn = document.getElementById('btn-tab-' + tab);
            const panel = document.getElementById('panel-' + tab);
            if (btn && panel) {
                btn.classList.add('bg-primary', 'text-white', 'shadow-sm');
                btn.classList.remove('text-slate-600');
                panel.classList.remove('hidden');
            }
        }
        </script>

        <!-- Community Heroes -->
        <section class="bg-white py-24 rounded-[4rem] border border-outline-variant/10 text-center space-y-12">
            <div class="space-y-4">
                <h2 class="text-4xl font-bold text-primary">قهرمانان جامعه آسنا</h2>
                <p class="text-on-surface-variant">ما به داشتن چنین همراهانی افتخار می‌کنیم</p>
            </div>
            <div class="flex flex-wrap justify-center gap-16">
                <?php if(!empty($top_donors)): ?>
                    <?php foreach($top_donors as $index => $donor): 
                        $medal_icon = 'stars';
                        $medal_color = 'bg-primary-container';
                        $border_color = 'border-primary-container';
                        if ($index === 0) {
                            $medal_icon = 'workspace_premium';
                            $medal_color = 'bg-amber-400';
                            $border_color = 'border-amber-400';
                        } elseif ($index === 1) {
                            $medal_icon = 'military_tech';
                            $medal_color = 'bg-slate-300';
                            $border_color = 'border-slate-300';
                        } elseif ($index === 2) {
                            $medal_icon = 'military_tech';
                            $medal_color = 'bg-amber-600';
                            $border_color = 'border-amber-600';
                        }
                    ?>
                    <div class="flex flex-col items-center gap-4 group cursor-pointer">
                        <div class="relative w-28 h-28">
                            <div class="absolute inset-0 rounded-full border-4 <?php echo $border_color; ?> p-1 group-hover:scale-110 transition-transform">
                                <div class="w-full h-full rounded-full bg-cover bg-center"
                                    style='background-image: url("https://ui-avatars.com/api/?name=<?php echo urlencode($donor['donor_name']); ?>&background=random&color=fff&size=150");'>
                                </div>
                            </div>
                            <div class="absolute -bottom-1 -right-1 <?php echo $medal_color; ?> text-white w-10 h-10 rounded-full flex items-center justify-center border-4 border-white shadow-lg">
                                <?php if($index < 3): ?>
                                    <span class="material-symbols-outlined text-lg"><?php echo $medal_icon; ?></span>
                                <?php else: ?>
                                    <span class="text-sm font-bold">#<?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold text-primary text-lg"><?php echo htmlspecialchars($donor['donor_name']); ?></div>
                            <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest"><?php echo number_format($donor['total_donated']); ?> تومان</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="flex flex-col items-center justify-center gap-4 group cursor-pointer" onclick="window.location.href='charity.php'">
                    <div
                        class="w-28 h-28 rounded-full border-2 border-dashed border-outline-variant flex items-center justify-center group-hover:border-primary-container transition-all">
                        <span
                            class="material-symbols-outlined text-4xl text-outline-variant group-hover:text-primary-container">add</span>
                    </div>
                    <div class="font-bold text-on-surface-variant group-hover:text-primary-container">شما هم بپیوندید
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- NEW SECTION: CUSTOMER REVIEWS & VERIFIED SOCIAL PROOF WALL                -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <section class="customer-reviews-section space-y-6 my-16" id="customerReviewsSection">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 px-2">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200/70 rounded-full text-xs font-black">
                        <span class="material-symbols-outlined text-sm text-emerald-600" style="font-variation-settings: 'FILL' 1;">rate_review</span>
                        <span>تجربه و صدای سرپرستان پت</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-primary tracking-tight">
                        رضایت بیش از ۲۵,۰۰۰ سرپرست پت در سراسر کشور
                    </h2>
                    <p class="text-xs sm:text-sm text-on-surface-variant font-medium max-w-2xl">
                        نظرات واقعی خریداران محصولات، کاربران اشتراک دوره‌ای تحویل خودکار و مراجعین کلینیک‌ها و بیمارستان‌های همکار آسنا.
                    </p>
                </div>
                <div class="flex items-center gap-2 bg-emerald-50 px-4 py-2 rounded-2xl border border-emerald-200 self-start sm:self-auto text-emerald-800 text-xs font-black">
                    <span class="text-base font-mono font-black">۹۸.۶٪</span>
                    <span>شاخص رضایت عمومی</span>
                </div>
            </div>

            <!-- 4 Testimonial Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <div class="testimonial-card-modern p-6 flex flex-col justify-between">
                    <div>
                        <!-- Header / Pet Avatar -->
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center text-2xl shadow-inner">
                                    🐱
                                </div>
                                <div>
                                    <div class="text-xs font-black text-slate-900">لوسی (گربه پرشین)</div>
                                    <div class="text-[10px] text-slate-400">سرپرست: مریم رضایی</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-md border border-emerald-200">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                <span>خریدار تایید شده</span>
                            </span>
                        </div>

                        <!-- Rating Stars -->
                        <div class="flex items-center gap-1 text-amber-500 text-xs mb-3">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed">
                            «من اشتراک ماهانه غذای رفلکس و خاک پتوپیا رو فعال کردم. هر ماه سر تاریخ مقرر بدون اینکه حتی یادم باشه میرسه دستم و ۱۵٪ تخفیف اشتراک هم واقعاً عالیه.»
                        </p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                        <span>خریداری شده: کمبو باکس سلامت گربه</span>
                        <span class="font-mono">۱۴۰۳/۰۶/۱۰</span>
                    </div>
                </div>

                <div class="testimonial-card-modern p-6 flex flex-col justify-between">
                    <div>
                        <!-- Header / Pet Avatar -->
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center text-2xl shadow-inner">
                                    🐶
                                </div>
                                <div>
                                    <div class="text-xs font-black text-slate-900">تدی (پامرانین)</div>
                                    <div class="text-[10px] text-slate-400">سرپرست: امیرحسین کریمی</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-md border border-emerald-200">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                <span>خریدار تایید شده</span>
                            </span>
                        </div>

                        <!-- Rating Stars -->
                        <div class="flex items-center gap-1 text-amber-500 text-xs mb-3">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed">
                            «غذای رویال کنین مینی ادالت رو از آسنا سفارش دادم. اصالت بارکد کاملاً معتبر بود و تاریخ انقضایش تا ۲۰۲۷ بود. بسته‌بندی عالی و پیک هم بسیار محترم بود.»
                        </p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                        <span>خریداری شده: رویال کنین مینی ادالت</span>
                        <span class="font-mono">۱۴۰۳/۰۶/۰۷</span>
                    </div>
                </div>

                <div class="testimonial-card-modern p-6 flex flex-col justify-between">
                    <div>
                        <!-- Header / Pet Avatar -->
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-teal-100 flex items-center justify-center text-2xl shadow-inner">
                                    🦮
                                </div>
                                <div>
                                    <div class="text-xs font-black text-slate-900">میلو (گلدن رتریور)</div>
                                    <div class="text-[10px] text-slate-400">سرپرست: دکتر نیلوفر بهرامی</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-md border border-emerald-200">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                <span>کاربر طلایی</span>
                            </span>
                        </div>

                        <!-- Rating Stars -->
                        <div class="flex items-center gap-1 text-amber-500 text-xs mb-3">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed">
                            «برای جراحی دیسک میلو نیاز به مکمل مفاصل و رزرو نوبت با جراح متخصص داشتیم. هم نوبت‌دهی آنلاین عالی عمل کرد هم داروها با زنجیره یخ ارسال شدند.»
                        </p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                        <span>خریداری شده: مکمل گلوکوزامین و رزرو کلینیک</span>
                        <span class="font-mono">۱۴۰۳/۰۶/۰۲</span>
                    </div>
                </div>

                <div class="testimonial-card-modern p-6 flex flex-col justify-between">
                    <div>
                        <!-- Header / Pet Avatar -->
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-purple-100 flex items-center justify-center text-2xl shadow-inner">
                                    🐈‍⬛
                                </div>
                                <div>
                                    <div class="text-xs font-black text-slate-900">سزار (بریتیش شورت‌هیر)</div>
                                    <div class="text-[10px] text-slate-400">سرپرست: سینا رستمی</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-md border border-emerald-200">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                <span>خریدار تایید شده</span>
                            </span>
                        </div>

                        <!-- Rating Stars -->
                        <div class="flex items-center gap-1 text-amber-500 text-xs mb-3">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed">
                            «پشتیبانی آنلاین با لئو (هوش مصنوعی) در نصف شب کمکم کرد دوز مناسب داروی ضد کک رو حساب کنم و فرداش دارو رو دم در تحویل گرفتم. واقعاً فوق‌العاده است.»
                        </p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                        <span>خریداری شده: شامپو و قطره تریکسی</span>
                        <span class="font-mono">۱۴۰۳/۰۵/۲۸</span>
                    </div>
                </div>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <!-- NEW SECTION: BRAND PARTNERS & 4 GOLDEN GUARANTEES                          -->
        <!-- ══════════════════════════════════════════════════════════════════════════ -->
        <section class="brand-guarantees-section space-y-10 my-16" id="brandPartnersSection">
            
            <!-- 4 Golden Guarantees Bar -->
            <div class="bg-gradient-to-r from-primary via-primary-container to-[#001a48] text-white rounded-[2.5rem] p-6 sm:p-8 shadow-xl">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-amber-400 shrink-0 border border-white/10">
                            <span class="material-symbols-outlined text-3xl">verified_user</span>
                        </div>
                        <div>
                            <div class="font-black text-sm text-white">ضمانت ۱۰۰٪ اصالت فیزیکی</div>
                            <div class="text-[11px] text-white/70 mt-0.5">تضمین تاریخ انقضا و بارکد بین‌المللی</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-teal-400 shrink-0 border border-white/10">
                            <span class="material-symbols-outlined text-3xl">rocket_launch</span>
                        </div>
                        <div>
                            <div class="font-black text-sm text-white">ارسال اکسپرس و زنجیره سرد</div>
                            <div class="text-[11px] text-white/70 mt-0.5">زیر ۲ ساعت در تهران و ۲۴ ساعته کشور</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-sky-400 shrink-0 border border-white/10">
                            <span class="material-symbols-outlined text-3xl">medical_information</span>
                        </div>
                        <div>
                            <div class="font-black text-sm text-white">مشاوره رایگان دامپزشکی</div>
                            <div class="text-[11px] text-white/70 mt-0.5">بررسی آنلاین تداخل و تغذیه قبل از خرید</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-rose-400 shrink-0 border border-white/10">
                            <span class="material-symbols-outlined text-3xl">assignment_return</span>
                        </div>
                        <div>
                            <div class="font-black text-sm text-white">۷ روز ضمانت تعویض و بازگشت</div>
                            <div class="text-[11px] text-white/70 mt-0.5">در صورت عدم رضایت یا عدم تطابق کالا</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Official Brands Bar -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-2">
                    <span class="text-xs font-black text-slate-400 uppercase tracking-wider">برندهای رسمی و بین‌المللی طرف قرارداد مستقیم آسنا</span>
                    <span class="text-[11px] text-primary font-bold">بیش از ۵۰ تامین‌کننده معتبر</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                    <div class="brand-badge-partner">
                        <span class="font-black text-xs text-slate-700 tracking-tight">ROYAL CANIN</span>
                    </div>
                    <div class="brand-badge-partner">
                        <span class="font-black text-xs text-slate-700 tracking-tight">JOSERA</span>
                    </div>
                    <div class="brand-badge-partner">
                        <span class="font-black text-xs text-slate-700 tracking-tight">REFLEX PLUS</span>
                    </div>
                    <div class="brand-badge-partner">
                        <span class="font-black text-xs text-slate-700 tracking-tight">BRIT CARE</span>
                    </div>
                    <div class="brand-badge-partner">
                        <span class="font-black text-xs text-slate-700 tracking-tight">SHAYER PET</span>
                    </div>
                    <div class="brand-badge-partner">
                        <span class="font-black text-xs text-slate-700 tracking-tight">NUTRI PET</span>
                    </div>
                    <div class="brand-badge-partner">
                        <span class="font-black text-xs text-slate-700 tracking-tight">TRIXIE</span>
                    </div>
                    <div class="brand-badge-partner">
                        <span class="font-black text-xs text-slate-700 tracking-tight">BEAPHAR</span>
                    </div>
                </div>
            </div>

        </section>
    </main>

<!-- Interaction Layer -->
    <div class="fixed bottom-6 left-6 md:bottom-12 md:left-12 flex flex-col gap-3 md:gap-4 z-50">
        <a href="chat.php"
            class="w-12 h-12 md:w-16 md:h-16 bg-white border border-outline-variant text-primary rounded-2xl shadow-2xl flex items-center justify-center hover:scale-110 hover:bg-primary hover:text-white transition-all group relative">
            <span class="material-symbols-outlined text-xl md:text-3xl">chat_bubble</span>
            <span
                class="absolute left-14 md:left-20 bg-primary text-white px-4 py-2 rounded-xl text-xs font-bold opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-xl">پشتیبانی
                آنلاین</span>
        </a>
    </div>

<script>
// --- Landing Page: Best Sellers Filter Controller ---
function filterBestSellers(group, btn) {
    document.querySelectorAll('.bestseller-tab-btn').forEach(b => {
        b.className = "bestseller-tab-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5";
    });
    btn.className = "bestseller-tab-btn active bg-primary text-white px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 shadow-sm";

    const cards = document.querySelectorAll('.bestseller-card');
    cards.forEach(card => {
        const cardGroup = card.getAttribute('data-group');
        if (group === 'all' || cardGroup === group) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}

// --- Landing Page: Autoship Savings Simulator ---
function updateAutoshipSim(petType, btn) {
    document.querySelectorAll('.autoship-sim-btn').forEach(b => {
        b.className = "autoship-sim-btn px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 text-slate-600 hover:text-primary";
    });
    btn.className = "autoship-sim-btn active px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 bg-primary text-white shadow-sm";

    const regElem = document.getElementById('simRegularPrice');
    const autoElem = document.getElementById('simAutoshipPrice');
    const saveElem = document.getElementById('simYearlySavings');
    const detailsElem = document.getElementById('simBasketDetails');

    if (!regElem || !autoElem || !saveElem) return;

    if (petType === 'cat') {
        regElem.textContent = '۱,۸۵۰,۰۰۰ تومان';
        autoElem.textContent = '۱,۵۷۰,۰۰۰ تومان';
        saveElem.textContent = '۳,۳۶۰,۰۰۰ تومان';
        if (detailsElem) detailsElem.textContent = 'غذای خشک + ۲ کنسرو + خاک ۱۰L';
    } else if (petType === 'small_dog') {
        regElem.textContent = '۲,۲۰۰,۰۰۰ تومان';
        autoElem.textContent = '۱,۸۷۰,۰۰۰ تومان';
        saveElem.textContent = '۳,۹۶۰,۰۰۰ تومان';
        if (detailsElem) detailsElem.textContent = 'غذای مینی ادالت + تشویقی دنتال + پد بهداشتی';
    } else if (petType === 'large_dog') {
        regElem.textContent = '۳,۶۰۰,۰۰۰ تومان';
        autoElem.textContent = '۳,۰۶۰,۰۰۰ تومان';
        saveElem.textContent = '۶,۴۸۰,۰۰۰ تومان';
        if (detailsElem) detailsElem.textContent = 'غذای ماکسی ادالت + مکمل گلوکوزامین مفاصل';
    }
}

// --- Chat System Logic ---
let chatMode = 'ai';
let currentTicketId = null;
let lastMessageId = 0;
let chatPollingInterval = null;

function setChatMode(mode) {
    chatMode = mode;
    
    // Update UI Buttons
    const btnAi = document.getElementById('btn-mode-ai');
    const btnAdmin = document.getElementById('btn-mode-admin');
    const btnOrg = document.getElementById('btn-mode-org');
    const orgSelector = document.getElementById('org-selector-container');
    
    const activeClass = 'flex-1 flex items-center justify-center gap-1.5 py-3 px-2 rounded-xl text-xs font-black bg-primary-container text-white shadow-md transition-all';
    const inactiveClass = 'flex-1 flex items-center justify-center gap-1.5 py-3 px-2 rounded-xl text-xs font-black text-on-surface-variant hover:text-primary transition-all';

    if (btnAi) btnAi.className = (mode === 'ai') ? activeClass : inactiveClass;
    if (btnAdmin) btnAdmin.className = (mode === 'admin') ? activeClass : inactiveClass;
    if (btnOrg) btnOrg.className = (mode === 'organization') ? activeClass : inactiveClass;

    if (mode === 'ai') {
        if (orgSelector) orgSelector.classList.add('hidden');
        document.getElementById('chat-title').innerText = 'لئو (Leo)';
        document.getElementById('chat-avatar').innerHTML = '<span class="material-symbols-outlined text-3xl">cruelty_free</span>';
        document.querySelector('#chat-typing span').innerText = 'لئو در حال تایپ است...';
    } else if (mode === 'organization') {
        if (orgSelector) orgSelector.classList.remove('hidden');
        const orgSelect = document.getElementById('chat-org-select');
        const selectedOrgName = orgSelect ? orgSelect.options[orgSelect.selectedIndex]?.text : 'مرکز درمانی';
        document.getElementById('chat-title').innerText = selectedOrgName;
        document.getElementById('chat-avatar').innerHTML = '<span class="material-symbols-outlined text-3xl">apartment</span>';
        document.querySelector('#chat-typing span').innerText = 'پذیرش مرکز درمانی در حال پاسخگویی...';
    } else {
        if (orgSelector) orgSelector.classList.add('hidden');
        document.getElementById('chat-title').innerText = 'پشتیبانی مدیریت آسنا';
        document.getElementById('chat-avatar').innerHTML = '<span class="material-symbols-outlined text-3xl">support_agent</span>';
        document.querySelector('#chat-typing span').innerText = 'کارشناس مدیریت آسنا در حال پاسخگویی است...';
    }
    
    // Reset Chat State
    lastMessageId = 0;
    const msgContainer = document.getElementById('chat-messages');
    if (msgContainer.querySelector('.bg-white\\/60')) return; // locked state
    
    msgContainer.innerHTML = '<div class="flex justify-center mb-8"><div class="bg-surface-container px-4 py-1 rounded-full text-[10px] text-on-surface-variant font-bold shadow-sm">امروز</div></div>';
    
    initChat();
}

function changeChatOrganization(orgId) {
    const orgSelect = document.getElementById('chat-org-select');
    const selectedOrgName = orgSelect ? orgSelect.options[orgSelect.selectedIndex]?.text : 'مرکز درمانی';
    document.getElementById('chat-title').innerText = selectedOrgName;
    lastMessageId = 0;
    const msgContainer = document.getElementById('chat-messages');
    msgContainer.innerHTML = '<div class="flex justify-center mb-8"><div class="bg-surface-container px-4 py-1 rounded-full text-[10px] text-on-surface-variant font-bold shadow-sm">امروز</div></div>';
    initChat();
}

function initChat() {
    if (chatPollingInterval) clearInterval(chatPollingInterval);
    
    const fd = new FormData();
    fd.append('action', 'init');
    fd.append('mode', chatMode);
    if (chatMode === 'organization') {
        const orgSelect = document.getElementById('chat-org-select');
        fd.append('organization_id', orgSelect ? orgSelect.value : '');
    }
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                currentTicketId = data.ticket_id;
                fetchMessages();
                chatPollingInterval = setInterval(fetchMessages, 3000);
            }
        });
}

function fetchMessages() {
    if (!currentTicketId) return;
    
    const fd = new FormData();
    fd.append('action', 'fetch');
    fd.append('ticket_id', currentTicketId);
    fd.append('last_id', lastMessageId);
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.messages.length > 0) {
                renderMessages(data.messages);
                lastMessageId = data.messages[data.messages.length - 1].id;
                scrollToBottom();
                document.getElementById('chat-typing').style.display = 'none';
            }
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function renderMessages(messages) {
    const container = document.getElementById('chat-messages');
    
    messages.forEach(msg => {
        const isUser = msg.sender_type === 'user';
        let avatar = 'support_agent';
        if (msg.sender_type === 'ai') avatar = 'cruelty_free';
        else if (chatMode === 'organization') avatar = 'apartment';

        const safeMessage = escapeHtml(msg.message).replace(/\n/g, '<br>');
        
        let imgHtml = '';
        if (msg.image_url) {
            const safeImg = escapeHtml(msg.image_url);
            imgHtml = `<img loading="lazy" src="${safeImg}" class="rounded-xl mb-3 max-w-full h-auto cursor-pointer hover:opacity-90 transition-opacity" alt="ضمیمه پیام">`;
        }

        const time = new Date(msg.created_at).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });

        if (isUser) {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-4 max-w-[85%] flex-row-reverse ml-auto group">
                    <div class="bg-primary text-white px-5 py-4 rounded-3xl rounded-tl-sm shadow-md text-sm leading-relaxed">
                        ${imgHtml}
                        <div dir="auto" class="chat-message-text" style="unicode-bidi: plaintext; text-align: start;">${safeMessage}</div>
                        <div class="text-[9px] text-white/70 mt-2 text-left w-full block">${time} <span class="material-symbols-outlined text-[10px] ml-0.5" style="vertical-align: middle">done_all</span></div>
                    </div>
                </div>
            `);
        } else {
            container.insertAdjacentHTML('beforeend', `
                <div class="flex gap-4 max-w-[85%]">
                    <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center shrink-0 border-2 border-white shadow-sm mt-auto">
                        <span class="material-symbols-outlined text-lg">${avatar}</span>
                    </div>
                    <div class="bg-white px-5 py-4 rounded-3xl rounded-br-sm shadow-md text-sm border border-outline-variant/10 leading-relaxed text-on-surface">
                        ${imgHtml}
                        <div class="markdown-body chat-message-text" dir="auto" style="unicode-bidi: plaintext; text-align: start;">${safeMessage}</div>
                        <div class="text-[9px] text-on-surface-variant/70 mt-2 text-right w-full block">${time}</div>
                    </div>
                </div>
            `);
        }
    });
}

function scrollToBottom() {
    const container = document.getElementById('chat-messages');
    container.scrollTop = container.scrollHeight;
}

function handleImageSelect(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').src = e.target.result;
            document.getElementById('image-preview-container').classList.remove('hidden');
            document.getElementById('image-preview-container').classList.add('flex');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function clearImage() {
    document.getElementById('chat-image-input').value = '';
    document.getElementById('image-preview-container').classList.add('hidden');
    document.getElementById('image-preview-container').classList.remove('flex');
    document.getElementById('image-preview').src = '';
}

function sendChatMessage(e) {
    e.preventDefault();
    if (!currentTicketId) return;
    
    const input = document.getElementById('chat-input');
    const imageInput = document.getElementById('chat-image-input');
    const msg = input.value.trim();
    
    if (!msg && imageInput.files.length === 0) return;
    
    // Show Optimistic UI for User Message
    const container = document.getElementById('chat-messages');
    let imgHtml = '';
    if (imageInput.files.length > 0) {
        imgHtml = `<img loading="lazy" src="${document.getElementById('image-preview').src}" class="rounded-xl mb-3 max-w-[200px] opacity-70" alt="تصویر ارسالی کاربر">`;
    }
    
    const time = new Date().toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
    
    container.insertAdjacentHTML('beforeend', `
        <div class="flex gap-4 max-w-[85%] flex-row-reverse ml-auto opacity-70" id="temp-msg">
            <div class="bg-primary text-white px-5 py-4 rounded-3xl rounded-tl-sm shadow-md text-sm leading-relaxed">
                ${imgHtml}
                <div>${msg.replace(/\n/g, '<br>')}</div>
                <div class="text-[9px] text-white/70 mt-2 text-left w-full block"><span class="material-symbols-outlined text-[10px] animate-spin">sync</span></div>
            </div>
        </div>
    `);
    scrollToBottom();
    
    // Clear Inputs
    input.value = '';
    const file = imageInput.files[0];
    clearImage();
    
    // Show Typing Indicator
    document.getElementById('chat-typing').style.display = 'flex';
    scrollToBottom();
    
    const fd = new FormData();
    fd.append('action', 'send');
    fd.append('ticket_id', currentTicketId);
    fd.append('message', msg);
    if (file) {
        fd.append('image', file);
    }
    
    fetch('actions/chat_action.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                // Redirect user to the dedicated chat page to continue the conversation
                window.location.href = 'chat.php?ticket_id=' + currentTicketId;
            } else {
                alert('خطا در ارسال پیام');
                document.getElementById('temp-msg')?.remove();
                input.disabled = false;
                document.getElementById('chat-send-btn').disabled = false;
            }
        });
}

// Initialize chat if user is logged in
document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('#chat-messages .bg-white\\/60')) {
        setChatMode('ai');
    }
});

// Cart Logic
function addToCart(btn, productId, type = 'standard') {
    if(window.event) window.event.preventDefault();
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">sync</span>';
    
    fetch('actions/cart_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add&ajax=1&csrf_token=<?php echo csrf_token(); ?>&product_id=' + productId + '&type=' + type
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            btn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span>';
            btn.classList.add('bg-green-500', 'text-white');
            btn.classList.remove('bg-primary');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('bg-green-500');
                btn.classList.add('bg-primary');
            }, 2000);
        } else {
            alert('خطا در افزودن به سبد خرید');
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
    });
}

// Wishlist interactions are handled universally by assets/js/wishlist-manager.js
// (Optimistic zero-latency UI + Particle burst + Floating toast)

// --- Slider Logic ---
<?php
$curated_banners = function_exists('get_curated_recommendations') ? get_curated_recommendations($pdo, 'banner', 5) : [];
$slides_data = [];

foreach ($curated_banners as $banner) {
    $slides_data[] = [
        'title' => !empty($banner['custom_title']) ? nl2br(htmlspecialchars($banner['custom_title'])) : htmlspecialchars($banner['product_name']),
        'desc' => !empty($banner['custom_subtitle']) ? htmlspecialchars($banner['custom_subtitle']) : ('تامین مستقیم با تخفیف ویژه ' . number_format($banner['product_discount_price'] ?: $banner['product_price']) . ' تومان همراه با تحویل اکسپرس.'),
        'badgeIcon' => 'auto_awesome',
        'badgeText' => !empty($banner['custom_badge']) ? htmlspecialchars($banner['custom_badge']) : '🔥 پیشنهاد برگزیده',
        'link' => 'product_details.php?id=' . (int)$banner['product_id'],
        'linkText' => 'مشاهده و خرید محصول',
        'img' => !empty($banner['product_image_url']) ? $banner['product_image_url'] : 'assets/images/cat-hero.jpg'
    ];
}

$default_slides = [
    [
        'title' => "اشتراک هوشمند؛<br />همیشه در دسترس",
        'desc' => "برنامه غذایی و دارویی پت شما هرگز متوقف نمی‌شود. با فعال‌سازی اشتراک، از تخفیف دائمی و اولویت در خدمات بهره‌مند شوید.",
        'badgeIcon' => "autorenew",
        'badgeText' => "سیستم تحویل خودکار (Autoship)",
        'link' => "subscriptions.php",
        'linkText' => "شروع تجربه اشتراک",
        'img' => "assets/images/cat-hero.jpg"
    ],
    [
        'title' => "برترین محصولات<br />برای سلامت پت",
        'desc' => "فروشگاه ما با مجموعه‌ای بی‌نظیر از بهترین برندهای جهانی، تضمین‌کننده سلامت و نشاط حیوان خانگی شماست.",
        'badgeIcon' => "local_shipping",
        'badgeText' => "ارسال رایگان سفارشات بالای ۵۰۰ هزار تومان",
        'link' => "shop.php",
        'linkText' => "مشاهده فروشگاه",
        'img' => "assets/images/toy-mouse.jpg"
    ],
    [
        'title' => "کلینیک تخصصی<br />در دستان شما",
        'desc' => "با استفاده از سیستم یکپارچه رزرواسیون آنلاین، بدون معطلی و در سریع‌ترین زمان ممکن برای پت خود نوبت بگیرید.",
        'badgeIcon' => "medical_services",
        'badgeText' => "پشتیبانی درمانی حرفه‌ای",
        'link' => "booking.php",
        'linkText' => "رزرو نوبت کلینیک",
        'img' => "assets/images/presentation-dog.jpg"
    ],
    [
        'title' => "داروخانه تخصصی؛<br />ارسال فوری نسخه",
        'desc' => "تامین مستقیم داروهای کمیاب، واکسن‌های زنجیره سرد و مکمل‌های تقویتی انواع گونه‌های حیوانات با تاییدیه دامپزشکی.",
        'badgeIcon' => "local_pharmacy",
        'badgeText' => "داروخانه آنلاین دامپزشکی",
        'link' => "pharmacy.php",
        'linkText' => "ورود به داروخانه",
        'img' => "assets/images/dog-hero.jpg"
    ]
];

$all_slides = !empty($slides_data) ? array_merge($slides_data, array_slice($default_slides, 0, max(0, 4 - count($slides_data)))) : $default_slides;
?>
const slides = <?= json_encode($all_slides, JSON_UNESCAPED_UNICODE) ?>;

let currentSlide = 0;
let slideInterval = setInterval(nextSlide, 6000);

function updateSlide() {
    const slide = slides[currentSlide];
    
    // Animate content out
    const content = document.getElementById('hero-content');
    content.style.opacity = 0;
    
    setTimeout(() => {
        document.getElementById('hero-title').innerHTML = slide.title;
        document.getElementById('hero-desc').innerText = slide.desc;
        document.getElementById('hero-badge-icon').innerText = slide.badgeIcon;
        document.getElementById('hero-badge-text').innerText = slide.badgeText;
        document.getElementById('hero-link').href = slide.link;
        document.getElementById('hero-link').innerText = slide.linkText;
        document.getElementById('hero-bg').style.backgroundImage = `url("${slide.img}")`;
        
        // Animate content in
        content.style.opacity = 1;
        content.style.transition = 'opacity 0.5s ease';
    }, 300);

    // Update Pills
    const pills = document.getElementById('hero-pills').children;
    for(let i=0; i<pills.length; i++) {
        if(i === currentSlide) {
            pills[i].className = "w-8 h-2 rounded-full bg-primary-container scale-125 transition-all cursor-pointer";
        } else {
            pills[i].className = "w-3 h-2 rounded-full bg-primary-container/20 hover:bg-primary-container/40 transition-all cursor-pointer";
        }
    }
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    updateSlide();
    resetInterval();
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    updateSlide();
    resetInterval();
}

function goToSlide(index) {
    currentSlide = index;
    updateSlide();
    resetInterval();
}

function resetInterval() {
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 6000);
}
</script>
<script src="assets/js/swiper-bundle.min.js"></script>
<link rel="stylesheet" href="assets/css/swiper-bundle.min.css" />
<script>
    if (document.querySelector('.charity-index-slider')) {
        new Swiper('.charity-index-slider', {
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            }
        });
    }

    // ── Interactive Landing Page Reservation & Doctor Filtering Controller ───
    const doctorsData = <?= !empty($doctors_json) ? $doctors_json : '[]' ?>;
    const bookedSlotsData = <?= !empty($booked_slots_json) ? $booked_slots_json : '{}' ?>;
    
    let selectedDoctorId = <?= !empty($top_doctors[0]['id']) ? (int)$top_doctors[0]['id'] : 31 ?>;
    let selectedDate = '<?= $reservation_days[0]['gDate'] ?? date('Y-m-d') ?>';
    let selectedDayKey = '<?= $reservation_days[0]['dayKey'] ?? 'sat' ?>';
    let selectedTime = '';

    // Filter Doctors on Landing Page by Specialty Category
    function filterLandingDoctors(cat, btn) {
        document.querySelectorAll('.landing-doc-filter').forEach(b => {
            b.className = "landing-doc-filter bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5";
        });
        btn.className = "landing-doc-filter active bg-primary text-white px-4 py-2 rounded-2xl text-xs font-bold transition-all shrink-0 flex items-center gap-1.5 shadow-sm";

        const cards = document.querySelectorAll('.landing-doc-card');
        cards.forEach(card => {
            const cardCat = card.getAttribute('data-category');
            if (cat === 'all' || cardCat === cat) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    // Quick Select Doctor and Smooth Scroll to Reservation Widget
    window.quickSelectDoctorForBooking = function(docId) {
        selectReservationDoctor(docId);
        const section = document.getElementById('timeReservationSection');
        if (section) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    // Quick Select Organization and Smooth Scroll
    window.quickSelectOrgForBooking = function(orgId) {
        const found = doctorsData.find(d => parseInt(d.organization_id, 10) === parseInt(orgId, 10));
        if (found) {
            quickSelectDoctorForBooking(found.id);
        } else {
            const section = document.getElementById('timeReservationSection');
            if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    // Step 1: Service Selection
    function selectLandingService(type, btn) {
        document.querySelectorAll('.landing-service-btn').forEach(b => {
            b.className = "landing-service-btn p-3 rounded-2xl border-2 border-slate-100 bg-slate-50 hover:bg-slate-100 text-slate-700 flex flex-col items-center gap-1.5 text-center transition-all";
            const icon = b.querySelector('.material-symbols-outlined');
            if (icon) icon.className = "material-symbols-outlined text-2xl text-slate-500";
        });
        btn.className = "landing-service-btn active p-3 rounded-2xl border-2 border-primary bg-primary/5 text-primary flex flex-col items-center gap-1.5 text-center transition-all";
        const activeIcon = btn.querySelector('.material-symbols-outlined');
        if (activeIcon) activeIcon.className = "material-symbols-outlined text-2xl text-primary";

        document.getElementById('inputLandingServiceType').value = type;

        // Auto-filter matching doctor if applicable
        if (type === 'surgery') filterLandingDoctors('surgery', document.querySelector('.landing-doc-filter[onclick*="surgery"]'));
        else if (type === 'internal') filterLandingDoctors('internal', document.querySelector('.landing-doc-filter[onclick*="internal"]'));
        else if (type === 'grooming') filterLandingDoctors('groomer', document.querySelector('.landing-doc-filter[onclick*="groomer"]'));
    }

    // Step 2: Doctor Selection
    function selectReservationDoctor(docId) {
        selectedDoctorId = parseInt(docId, 10);
        document.getElementById('inputLandingDoctorId').value = selectedDoctorId;

        document.querySelectorAll('.reservation-doc-opt').forEach(opt => {
            const optId = parseInt(opt.getAttribute('data-id'), 10);
            if (optId === selectedDoctorId) {
                opt.className = "reservation-doc-opt selected ring-2 ring-primary border-primary bg-primary/5 border rounded-2xl p-3.5 flex items-center gap-3 cursor-pointer transition-all relative";
            } else {
                opt.className = "reservation-doc-opt border-slate-200 bg-white hover:border-slate-300 border rounded-2xl p-3.5 flex items-center gap-3 cursor-pointer transition-all relative";
            }
        });

        renderLandingTimeSlots();
    }

    // Step 3: Date Selection
    function selectReservationDate(gDate, dayKey, btn) {
        selectedDate = gDate;
        selectedDayKey = dayKey;
        document.getElementById('inputLandingDate').value = selectedDate;

        document.querySelectorAll('.landing-day-btn').forEach(b => {
            b.className = "landing-day-btn bg-slate-50 hover:bg-slate-100 text-slate-700 p-3 rounded-2xl border border-slate-200/80 flex flex-col items-center justify-center gap-1 transition-all";
        });
        btn.className = "landing-day-btn active bg-primary text-white shadow-md p-3 rounded-2xl border border-primary flex flex-col items-center justify-center gap-1 transition-all";

        const label = btn.querySelector('span:first-child')?.textContent || '';
        document.getElementById('selectedDayLabel').textContent = label;

        renderLandingTimeSlots();
    }

    // Render Time Slots
    function renderLandingTimeSlots() {
        const container = document.getElementById('landingTimeSlotsContainer');
        if (!container) return;
        container.innerHTML = '';
        selectedTime = '';
        document.getElementById('inputLandingTime').value = '';

        const currentDoctor = doctorsData.find(d => parseInt(d.id, 10) === selectedDoctorId);
        if (!currentDoctor) {
            container.innerHTML = '<div class="col-span-full py-4 text-center text-xs text-slate-400">پزشک مورد نظر یافت نشد.</div>';
            return;
        }

        let schedule = {};
        try {
            schedule = typeof currentDoctor.schedule_info === 'string' ? JSON.parse(currentDoctor.schedule_info) : (currentDoctor.schedule_info || {});
        } catch(e) {
            schedule = {};
        }

        const daySched = schedule[selectedDayKey];
        if (!daySched || (!daySched.m_active && !daySched.a_active)) {
            container.innerHTML = `
                <div class="col-span-full py-6 text-center text-xs text-slate-500 bg-white rounded-xl border border-slate-200">
                    <span class="material-symbols-outlined text-2xl text-slate-400 block mb-1">event_busy</span>
                    این پزشک در روز انتخابی شیفت حضور ندارد. لطفاً روز دیگری از تقویم بالا را انتخاب فرمایید.
                </div>
            `;
            return;
        }

        // Helper to generate slots
        function generateSlots(startTime, endTime) {
            const slots = [];
            let [startH, startM] = startTime.split(':').map(Number);
            let [endH, endM] = endTime.split(':').map(Number);
            let cur = startH * 60 + startM;
            let end = endH * 60 + endM;

            while (cur < end) {
                let h = Math.floor(cur / 60).toString().padStart(2, '0');
                let m = (cur % 60).toString().padStart(2, '0');
                slots.push(`${h}:${m}`);
                cur += 45; // 45-min appointments
            }
            return slots;
        }

        let availableSlots = [];
        if (daySched.m_active !== false && daySched.m_start && daySched.m_end) {
            availableSlots = availableSlots.concat(generateSlots(daySched.m_start, daySched.m_end));
        }
        if (daySched.a_active !== false && daySched.a_start && daySched.a_end) {
            availableSlots = availableSlots.concat(generateSlots(daySched.a_start, daySched.a_end));
        }

        if (availableSlots.length === 0) {
            availableSlots = ['09:00', '10:00', '11:00', '12:00', '16:00', '17:00', '18:00', '19:00', '20:00'];
        }

        const doctorBookedToday = (bookedSlotsData[selectedDoctorId] && bookedSlotsData[selectedDoctorId][selectedDate]) 
            ? bookedSlotsData[selectedDoctorId][selectedDate] 
            : [];

        availableSlots.forEach((slot, idx) => {
            const isBooked = doctorBookedToday.includes(slot);
            const btn = document.createElement('button');
            btn.type = 'button';
            if (isBooked) {
                btn.className = "py-2 px-2 rounded-xl bg-slate-100 text-slate-400 border border-slate-200 text-xs font-mono font-bold cursor-not-allowed text-center opacity-60 flex flex-col items-center justify-center gap-0.5";
                btn.disabled = true;
                btn.innerHTML = `<span>${slot}</span><span class="text-[9px] text-rose-500 font-sans">تکمیل</span>`;
            } else {
                btn.className = "landing-slot-btn py-2.5 px-2 rounded-xl bg-white border border-slate-200 hover:border-primary text-slate-800 text-xs font-mono font-bold transition-all text-center flex items-center justify-center shadow-sm hover:shadow";
                btn.textContent = slot;
                btn.onclick = function() {
                    document.querySelectorAll('.landing-slot-btn').forEach(b => {
                        b.className = "landing-slot-btn py-2.5 px-2 rounded-xl bg-white border border-slate-200 hover:border-primary text-slate-800 text-xs font-mono font-bold transition-all text-center flex items-center justify-center shadow-sm hover:shadow";
                    });
                    this.className = "landing-slot-btn py-2.5 px-2 rounded-xl bg-primary text-white border-primary shadow-md font-mono font-bold transition-all text-center flex items-center justify-center ring-2 ring-primary/30";
                    selectedTime = slot;
                    document.getElementById('inputLandingTime').value = selectedTime;
                };

                // Default pick first available slot
                if (!selectedTime && idx === 0) {
                    btn.click();
                }
            }
            container.appendChild(btn);
        });
    }

    // Step 4: Fast Booking Form Submission via AJAX
    async function handleLandingBookingSubmit(e) {
        e.preventDefault();
        const form = document.getElementById('landingBookingForm');
        const submitBtn = document.getElementById('landingBookingSubmitBtn');
        const timeVal = document.getElementById('inputLandingTime').value;

        if (!timeVal) {
            alert('لطفاً ابتدا یکی از ساعت‌های مجاز را انتخاب نمایید.');
            return;
        }

        const phoneVal = document.getElementById('landingOwnerPhone').value.trim();
        if (!/^09\d{9}$/.test(phoneVal)) {
            alert('لطفاً یک شماره موبایل معتبر ۱۱ رقمی (مثلاً ۰۹۱۲۳۴۵۶۷۸۹) وارد فرمایید.');
            return;
        }

        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-symbols-outlined text-lg animate-spin">sync</span><span>در حال ثبت نوبت و ارسال پیامک...</span>';

        try {
            const formData = new FormData(form);
            const response = await fetch('actions/process_landing_booking.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                const appt = data.appointment;
                document.getElementById('modalTrackingCode').textContent = appt.tracking_code;
                document.getElementById('modalDoctorName').textContent = appt.doctor_name + ' (' + appt.specialty + ')';
                document.getElementById('modalClinicName').textContent = appt.clinic_name;
                document.getElementById('modalDateTime').textContent = appt.date_shamsi + ' — ساعت ' + appt.time;
                document.getElementById('modalFee').textContent = appt.fee_formatted;

                // Mark slot as booked locally
                if (!bookedSlotsData[selectedDoctorId]) bookedSlotsData[selectedDoctorId] = {};
                if (!bookedSlotsData[selectedDoctorId][selectedDate]) bookedSlotsData[selectedDoctorId][selectedDate] = [];
                bookedSlotsData[selectedDoctorId][selectedDate].push(appt.time);

                document.getElementById('bookingSuccessModal').classList.remove('hidden');
                renderLandingTimeSlots();
            } else {
                alert(data.message || 'خطا در ثبت نوبت.');
            }
        } catch(err) {
            alert('خطای ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی نمایید.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Initialize First Available Doctor Time Slots
        if (typeof renderLandingTimeSlots === 'function') {
            renderLandingTimeSlots();
        }
    });
</script>

<!-- Schema.org JSON-LD Structured Data for Pharmacy & Pet Care Organization -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Pharmacy",
  "name": "داروخانه آنلاین و پت‌شاپ تخصصی آسنا",
  "alternateName": "ASENA Pet Care & Veterinary Pharmacy",
  "url": "<?php echo $proto . '://' . $host; ?>/",
  "logo": "<?php echo $proto . '://' . $host; ?>/assets/images/logo.png",
  "description": "مرجع تخصصی خرید آنلاین داروهای دامپزشکی، مکمل‌ها، واکسن‌ها و ملزومات حیوانات خانگی با تاییدیه دکتر داروساز و ارسال زنجیره سرد",
  "telephone": "+98-914-667-6978",
  "priceRange": "$$",
  "currenciesAccepted": "IRR",
  "paymentAccepted": "Cash, Credit Card, Online",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "خیابان ولیعصر، بالاتر از پارک ساعی",
    "addressLocality": "تهران",
    "addressRegion": "تهران",
    "addressCountry": "IR"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 35.7350,
    "longitude": 51.4110
  },
  "hasMap": "https://maps.google.com/?q=35.7350,51.4110",
  "areaServed": {
    "@type": "Country",
    "name": "Iran"
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Saturday", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
      "opens": "00:00",
      "closes": "23:59"
    }
  ],
  "medicalSpecialty": "VeterinaryCare"
}
</script>

<?php include 'includes/footer.php'; ?>
