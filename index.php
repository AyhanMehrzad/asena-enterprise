<?php
$page_title = "آسنا | زیست‌بوم جامع خدمات دامپزشکی، داروخانه و پت‌شاپ آنلاین";
$page_description = "سامانه جامع سلامت حیوانات خانگی آسنا؛ نوبت‌دهی آنلاین مجرب‌ترین پزشکان دامپزشک، پت‌شاپ تخصصی، داروخانه ارسال زنجیره سرد و اشتراک دوره‌ای خودکار (Autoship).";
require_once 'includes/header.php';

// Fetch Top 4 verified doctors for spotlight
$top_doctors = [];
if (Feature::has('clinic_booking')) {
    try {
        $doc_stmt = $pdo->prepare("
            SELECT d.id, d.name, d.specialty, d.provider_type, d.rating, d.review_count,
                   d.image_url, d.price, d.clinic_name, d.is_emergency, d.bio,
                   o.name as org_name, o.city as org_city, u.vet_council_number
            FROM doctors d
            LEFT JOIN organizations o ON d.organization_id = o.id
            LEFT JOIN users u ON d.user_id = u.id
            ORDER BY d.rating DESC, d.review_count DESC
            LIMIT 4
        ");
        $doc_stmt->execute();
        $top_doctors = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Fetch 4 featured pet shop best sellers
$bestseller_products = [];
if (Feature::has('petshop_catalog')) {
    try {
        $bs_stmt = $pdo->prepare("
            SELECT id, name, category, price, discount_price, image_url, brand, stock, 
                   target_animal, is_autoship, autoship_discount, rating_cache, review_count_cache 
            FROM products 
            WHERE stock > 0 
            ORDER BY is_autoship DESC, rating_cache DESC, id DESC 
            LIMIT 4
        ");
        $bs_stmt->execute();
        $bestseller_products = $bs_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Live Platform Counters
$count_orgs = 12;
$count_doctors = 48;
$count_medicines = 160;
try {
    $count_orgs = (int)$pdo->query("SELECT count(*) FROM organizations WHERE status = 'approved'")->fetchColumn() ?: 12;
    $count_doctors = (int)$pdo->query("SELECT count(*) FROM doctors")->fetchColumn() ?: 48;
    $count_medicines = (int)$pdo->query("SELECT count(*) FROM pharmacy_medicines")->fetchColumn() ?: 160;
} catch (Exception $e) {}
?>

<main class="max-w-container-max mx-auto overflow-hidden py-4 lg:py-6 px-margin-desktop space-y-16 lg:space-y-24">

    <!-- ========================================================================= -->
    <!-- BEAT 1: THE AUTHORITY HERO ZONE (Action-Driven Hub)                       -->
    <!-- ========================================================================= -->
    <section class="relative rounded-[2.5rem] lg:rounded-[3rem] overflow-hidden bg-gradient-to-br from-[#001a48] via-[#002d72] to-[#001336] text-white p-6 sm:p-10 lg:p-16 shadow-2xl border border-white/10">
        <!-- Background Ambient Lighting -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#fd8100]/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-4xl mx-auto text-center space-y-6">
            
            <!-- Value Proposition Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold border border-white/15 text-white shadow-xs">
                <span class="w-2 h-2 rounded-full bg-[#fd8100] animate-ping"></span>
                <span>زیست‌بوم جامع و تخصصی سلامت حیوانات خانگی</span>
            </div>

            <!-- Main Heading -->
            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black leading-tight tracking-tight text-white">
                درمان، داروخانه و ملزومات پت؛ <br class="hidden sm:inline">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#fd8100] via-amber-300 to-emerald-300">سریع، مطمئن و یکپارچه در سراسر کشور</span>
            </h1>

            <p class="text-xs sm:text-sm lg:text-base text-white/80 max-w-2xl mx-auto font-normal leading-relaxed">
                نوبت‌دهی آنلاین پزشکان متخصص، داروخانه تخصصی با ارسال زنجیره سرد و فروشگاه ملزومات با ضمانت اصالت کالا و تحویل اکسپرس.
            </p>

            <!-- 3-Tab Instant Action Box -->
            <div class="bg-white/10 backdrop-blur-2xl rounded-3xl p-3 sm:p-4 border border-white/20 shadow-2xl text-right max-w-3xl mx-auto mt-8" id="heroActionBox">
                <!-- Tabs Selector -->
                <div class="grid grid-cols-3 gap-1.5 p-1 bg-black/20 rounded-2xl mb-4 text-xs font-bold text-center">
                    <button type="button" onclick="switchHeroTab('vet')" id="heroTabVet" class="hero-tab-btn py-2.5 px-3 rounded-xl transition bg-white text-[#001a48] font-black shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-base text-[#fd8100]">stethoscope</span>
                        <span>نوبت‌دهی دامپزشک</span>
                    </button>
                    <button type="button" onclick="switchHeroTab('shop')" id="heroTabShop" class="hero-tab-btn py-2.5 px-3 rounded-xl transition text-white/80 hover:text-white flex items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-base">storefront</span>
                        <span>پت‌شاپ و غذا</span>
                    </button>
                    <button type="button" onclick="switchHeroTab('pharmacy')" id="heroTabPharmacy" class="hero-tab-btn py-2.5 px-3 rounded-xl transition text-white/80 hover:text-white flex items-center justify-center gap-1.5 cursor-pointer">
                        <span class="material-symbols-outlined text-base">vaccines</span>
                        <span>داروخانه تخصصی</span>
                    </button>
                </div>

                <!-- Tab Content 1: Vet Booking Form -->
                <form action="booking.php" method="GET" id="heroFormVet" class="hero-tab-pane flex flex-col sm:flex-row gap-2.5">
                    <div class="flex-1 bg-white/15 rounded-2xl px-4 py-2.5 flex items-center gap-2.5 border border-white/10 focus-within:bg-white focus-within:text-slate-800 transition">
                        <span class="material-symbols-outlined text-lg opacity-60">pets</span>
                        <select name="animal" class="w-full bg-transparent text-xs outline-none border-none cursor-pointer">
                            <option value="" class="text-slate-800">همه گونه‌های پت (سگ، گربه، پرنده)</option>
                            <option value="dog" class="text-slate-800">سگ (Canine)</option>
                            <option value="cat" class="text-slate-800">گربه (Feline)</option>
                            <option value="bird" class="text-slate-800">پرندگان زینتی</option>
                            <option value="exotic" class="text-slate-800">حیوانات اگزوتیک و خاص</option>
                        </select>
                    </div>
                    <div class="flex-1 bg-white/15 rounded-2xl px-4 py-2.5 flex items-center gap-2.5 border border-white/10 focus-within:bg-white focus-within:text-slate-800 transition">
                        <span class="material-symbols-outlined text-lg opacity-60">medical_services</span>
                        <select name="specialty" class="w-full bg-transparent text-xs outline-none border-none cursor-pointer">
                            <option value="" class="text-slate-800">همه تخصص‌های درمانی</option>
                            <option value="جراحی" class="text-slate-800">جراحی تخصصی و بافت نرم</option>
                            <option value="داخلی" class="text-slate-800">بیماری‌های داخلی و غدد</option>
                            <option value="دندانپزشکی" class="text-slate-800">دندانپزشکی و جرم‌گیری</option>
                            <option value="چکاپ و واکسیناسیون" class="text-slate-800">چکاپ و واکسیناسیون</option>
                        </select>
                    </div>
                    <button type="submit" class="px-6 py-3 bg-[#fd8100] hover:bg-[#e07300] text-white text-xs font-black rounded-2xl transition shadow-lg flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap">
                        <span class="material-symbols-outlined text-sm">search</span>
                        <span>جستجوی نوبت فوری</span>
                    </button>
                </form>

                <!-- Tab Content 2: Pet Shop Form (Hidden by default) -->
                <form action="shop.php" method="GET" id="heroFormShop" class="hero-tab-pane hidden flex flex-col sm:flex-row gap-2.5">
                    <div class="flex-1 bg-white/15 rounded-2xl px-4 py-2.5 flex items-center gap-2.5 border border-white/10 focus-within:bg-white focus-within:text-slate-800 transition">
                        <span class="material-symbols-outlined text-lg opacity-60">search</span>
                        <input type="text" name="q" placeholder="نام غذا، برند (رویال کنین، رفلکس...) یا ملزومات..." class="w-full bg-transparent text-xs outline-none border-none placeholder-white/60 focus:placeholder-slate-400">
                    </div>
                    <button type="submit" class="px-6 py-3 bg-[#fd8100] hover:bg-[#e07300] text-white text-xs font-black rounded-2xl transition shadow-lg flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap">
                        <span class="material-symbols-outlined text-sm">shopping_bag</span>
                        <span>جستجو در پت‌شاپ</span>
                    </button>
                </form>

                <!-- Tab Content 3: Pharmacy Form (Hidden by default) -->
                <form action="pharmacy.php" method="GET" id="heroFormPharmacy" class="hero-tab-pane hidden flex flex-col sm:flex-row gap-2.5">
                    <div class="flex-1 bg-white/15 rounded-2xl px-4 py-2.5 flex items-center gap-2.5 border border-white/10 focus-within:bg-white focus-within:text-slate-800 transition">
                        <span class="material-symbols-outlined text-lg opacity-60">medication</span>
                        <input type="text" name="q" placeholder="نام داروی دامپزشکی، مکمل یا واکسن..." class="w-full bg-transparent text-xs outline-none border-none placeholder-white/60 focus:placeholder-slate-400">
                    </div>
                    <button type="submit" class="px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-black rounded-2xl transition shadow-lg flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap">
                        <span class="material-symbols-outlined text-sm">prescriptions</span>
                        <span>جستجوی دارو و آپلود نسخه</span>
                    </button>
                </form>
            </div>

            <!-- Social Proof Validation Strip -->
            <div class="flex flex-wrap items-center justify-center gap-6 pt-4 text-xs text-white/80 font-medium">
                <div class="flex items-center gap-2">
                    <div class="flex -space-x-2 space-x-reverse">
                        <div class="w-7 h-7 rounded-full bg-emerald-400 text-slate-900 font-bold text-[10px] flex items-center justify-center border-2 border-[#001a48]">A+</div>
                        <div class="w-7 h-7 rounded-full bg-amber-400 text-slate-900 font-bold text-[10px] flex items-center justify-center border-2 border-[#001a48]">★</div>
                        <div class="w-7 h-7 rounded-full bg-blue-400 text-slate-900 font-bold text-[10px] flex items-center justify-center border-2 border-[#001a48]">✓</div>
                    </div>
                    <span>بیش از ۲۵,۰۰۰ سرپرست پت فعال</span>
                </div>
                <div class="h-4 w-px bg-white/20 hidden sm:block"></div>
                <div class="flex items-center gap-1.5 text-amber-300 font-bold">
                    <span>★ ۴.۹ / ۵</span>
                    <span class="text-white/70 font-normal">رضایت مراجعین</span>
                </div>
                <div class="h-4 w-px bg-white/20 hidden sm:block"></div>
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-emerald-400 text-sm">verified</span>
                    <span><?= number_format($count_orgs) ?> مرکز درمانی تأیید شده</span>
                </div>
            </div>

        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- BEAT 2: INSTITUTIONAL MARQUEE & ACCREDITATION TICKER                      -->
    <!-- ========================================================================= -->
    <section class="py-2 border-y border-slate-200/80 overflow-hidden">
        <div class="flex items-center justify-between text-xs text-slate-400 mb-2 px-2">
            <span class="font-bold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-slate-400">domain_verification</span>
                مراکز و بیمارستان‌های همکار مورد تأیید نظام دامپزشکی:
            </span>
            <a href="organizations.php" class="text-primary hover:underline font-bold text-[11px]">مشاهده همه مراکز (<?= number_format($count_orgs) ?>) ➔</a>
        </div>
        <div class="flex items-center gap-8 overflow-x-auto no-scrollbar py-2 text-xs font-bold text-slate-600 whitespace-nowrap opacity-80">
            <span class="px-4 py-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                بیمارستان تخصصی دامپزشکی پایتخت (شبانه‌روزی)
            </span>
            <span class="px-4 py-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                پلی‌کلینیک دامپزشکی رازی
            </span>
            <span class="px-4 py-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                مرکز جراحی و ترومای حیوانات خانگی کاسپین
            </span>
            <span class="px-4 py-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                کلینیک حیوانات اگزوتیک و پرندگان مهر
            </span>
            <span class="px-4 py-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                پناهگاه و مرکز امداد وفا
            </span>
        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- BEAT 3: THE 3 CORE PILLARS BENTO GRID (Consumer Destinations)            -->
    <!-- ========================================================================= -->
    <section class="space-y-6">
        <div class="text-center max-w-2xl mx-auto space-y-2">
            <h2 class="text-xl sm:text-3xl font-black text-slate-800 leading-snug py-1">
                سه ستون خدمت‌رسانی هوشمند آسنا
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 font-medium">
                دسته‌بندی جامع خدمات برای رفع سریع و بی‌دغدغه تمام نیازهای پزشکی و مراقبتی پت شما
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Pillar 1: Booking & Clinics -->
            <a href="booking.php" class="group bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between space-y-6 relative overflow-hidden">
                <div class="absolute -right-8 -top-8 w-32 h-32 bg-blue-50 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative z-10 space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#001a48] text-[#fd8100] flex items-center justify-center shadow-md">
                        <span class="material-symbols-outlined text-3xl">stethoscope</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 group-hover:text-primary transition-colors">
                        نوبت‌دهی و خدمات درمانی
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        رزرو آنلاین ویزیت عمومی، جراحی تخصصی، دندانپزشکی، واکسیناسیون و اورژانس شبانه‌روزی با مجرب‌ترین کادر دامپزشکی کشور.
                    </p>
                </div>
                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-slate-100 text-xs font-bold text-primary group-hover:text-[#fd8100] transition-colors">
                    <span>انتخاب پزشک و رزرو نوبت</span>
                    <span class="material-symbols-outlined text-base transition-transform group-hover:-translate-x-1">arrow_back</span>
                </div>
            </a>

            <!-- Pillar 2: Pet Shop -->
            <a href="shop.php" class="group bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between space-y-6 relative overflow-hidden">
                <div class="absolute -right-8 -top-8 w-32 h-32 bg-orange-50 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative z-10 space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#fd8100] text-white flex items-center justify-center shadow-md">
                        <span class="material-symbols-outlined text-3xl">shopping_cart</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 group-hover:text-[#fd8100] transition-colors">
                        پت‌شاپ تخصصی ملزومات و غذا
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        خرید انواع غذای خشک و تر، مکمل‌های تقویتی، تشویقی، خاک بستر و ملزومات سگ و گربه با ضمانت اصالت ۱۰۰٪ و ارسال سریع.
                    </p>
                </div>
                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-slate-100 text-xs font-bold text-[#fd8100] transition-colors">
                    <span>مشاهده محصولات پت‌شاپ</span>
                    <span class="material-symbols-outlined text-base transition-transform group-hover:-translate-x-1">arrow_back</span>
                </div>
            </a>

            <!-- Pillar 3: Specialized Pharmacy -->
            <a href="pharmacy.php" class="group bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col justify-between space-y-6 relative overflow-hidden">
                <div class="absolute -right-8 -top-8 w-32 h-32 bg-emerald-50 rounded-full group-hover:scale-125 transition-transform duration-500"></div>
                <div class="relative z-10 space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-md">
                        <span class="material-symbols-outlined text-3xl">vaccines</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 group-hover:text-emerald-700 transition-colors">
                        داروخانه و ارسال زنجیره سرد
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        تأمین داروهای کمیاب و حیاتی دامپزشکی، واکسن‌ها و سرم‌ها با تأیید نسخه الکترونیک توسط دکتر داروساز و ارسال در باکس‌های دمای کنترل‌شده.
                    </p>
                </div>
                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-slate-100 text-xs font-bold text-emerald-700 transition-colors">
                    <span>ورود به داروخانه و آپلود نسخه</span>
                    <span class="material-symbols-outlined text-base transition-transform group-hover:-translate-x-1">arrow_back</span>
                </div>
            </a>

        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- BEAT 4: VERIFIED SPECIALISTS SPOTLIGHT (Top Doctors)                      -->
    <!-- ========================================================================= -->
    <?php if (!empty($top_doctors)): ?>
    <section class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-1.5 text-xs font-bold text-primary mb-1">
                    <span class="material-symbols-outlined text-sm">verified_user</span>
                    <span>کادر پزشکی تأیید شده</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-800 leading-snug py-1">
                    پزشکان و جراحان برتر دامپزشکی
                </h2>
            </div>
            <a href="booking.php" class="text-xs font-bold text-primary hover:text-[#fd8100] flex items-center gap-1 transition">
                <span>مشاهده همه متخصصین و نوبت‌دهی آنلاین</span>
                <span class="material-symbols-outlined text-sm">arrow_back</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($top_doctors as $doc): ?>
            <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200">
                            <?php if (!empty($doc['image_url'])): ?>
                                <img src="<?= htmlspecialchars($doc['image_url']) ?>" alt="<?= htmlspecialchars($doc['name']) ?>" class="w-full h-full object-cover" onerror="this.src='assets/images/vet-avatar.png'">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <span class="material-symbols-outlined text-2xl">person</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="overflow-hidden">
                            <h3 class="font-bold text-xs sm:text-sm text-slate-800 truncate"><?= htmlspecialchars($doc['name']) ?></h3>
                            <p class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($doc['specialty'] ?: 'دامپزشک عمومی') ?></p>
                            <?php if (!empty($doc['vet_council_number'])): ?>
                                <span class="text-[10px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-mono inline-block mt-0.5">نظام: <?= htmlspecialchars($doc['vet_council_number']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-2.5 rounded-2xl text-[11px] text-slate-600 flex items-center justify-between font-medium">
                        <span>مرکز: <?= htmlspecialchars($doc['org_name'] ?: $doc['clinic_name'] ?: 'کلینیک تخصصی') ?></span>
                        <span class="text-amber-500 font-bold font-mono">★ <?= number_format((float)($doc['rating'] ?: 5.0), 1) ?></span>
                    </div>
                </div>

                <a href="booking.php?doctor_id=<?= (int)$doc['id'] ?>" class="w-full py-2.5 rounded-xl bg-primary hover:bg-[#002d72] text-white text-xs font-bold text-center transition flex items-center justify-center gap-1.5 cursor-pointer shadow-xs">
                    <span class="material-symbols-outlined text-sm">event_available</span>
                    <span>رزرو آنلاین نوبت</span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- BEAT 5: FEATURED PET SHOP BEST SELLERS STRIP                             -->
    <!-- ========================================================================= -->
    <?php if (!empty($bestseller_products)): ?>
    <section class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-1.5 text-xs font-bold text-[#fd8100] mb-1">
                    <span class="material-symbols-outlined text-sm">local_fire_department</span>
                    <span>پرفروش‌ترین‌های پت‌شاپ</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-800 leading-snug py-1">
                    غذای خشک و ملزومات برتر پت
                </h2>
            </div>
            <a href="shop.php" class="text-xs font-bold text-primary hover:text-[#fd8100] flex items-center gap-1 transition">
                <span>مشاهده همه محصولات فروشگاه</span>
                <span class="material-symbols-outlined text-sm">arrow_back</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach ($bestseller_products as $prod): 
                $price = (int)$prod['price'];
                $discountPrice = !empty($prod['discount_price']) ? (int)$prod['discount_price'] : 0;
                $finalPrice = ($discountPrice > 0) ? $discountPrice : $price;
            ?>
            <div class="bg-white rounded-3xl p-4 border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-3 group">
                <div class="space-y-2">
                    <div class="aspect-square rounded-2xl bg-slate-50 overflow-hidden relative border border-slate-100 flex items-center justify-center">
                        <?php if (!empty($prod['image_url'])): ?>
                            <img src="<?= htmlspecialchars($prod['image_url']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" onerror="this.src='assets/images/product-placeholder.png'">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-4xl text-slate-300">inventory_2</span>
                        <?php endif; ?>

                        <?php if ($discountPrice > 0): ?>
                            <span class="absolute top-2 right-2 bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-xs">
                                تخفیف ویژه
                            </span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 block font-medium"><?= htmlspecialchars($prod['brand'] ?: 'آسنا') ?></span>
                        <h3 class="font-bold text-xs text-slate-800 line-clamp-2 mt-0.5 leading-snug"><?= htmlspecialchars($prod['name']) ?></h3>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold font-mono text-slate-800 block"><?= number_format($finalPrice) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></span>
                        <?php if ($discountPrice > 0): ?>
                            <span class="text-[10px] line-through text-slate-400 font-mono"><?= number_format($price) ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="product_details.php?id=<?= (int)$prod['id'] ?>" class="p-2 bg-primary hover:bg-[#002d72] text-white rounded-xl transition shadow-xs flex items-center justify-center cursor-pointer" title="مشاهده و خرید">
                        <span class="material-symbols-outlined text-base">add_shopping_cart</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- BEAT 6: SMART PET HEALTH TOOLS BENTO (Interactive 3D Hub)                 -->
    <!-- ========================================================================= -->
    <section class="space-y-8 scroll-mt-28 lg:scroll-mt-32 pt-6" id="health-tools">
        <div class="text-center max-w-3xl mx-auto space-y-3 px-4">
            <div class="inline-flex items-center gap-2 text-xs font-black text-emerald-800 bg-emerald-100/90 border border-emerald-300/80 px-4 py-1.5 rounded-full shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="material-symbols-outlined text-base text-emerald-700">neurology</span>
                <span>سامانه‌های هوشمند مراقبت و محاسبات بالینی</span>
            </div>
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-snug py-1">
                جعبه‌ابزار سلامت و بهداشت حیوانات خانگی
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 font-medium max-w-xl mx-auto leading-relaxed">
                ابزارهای مستقل ۳ بعدی و آنلاین برای محاسبه دقیق جیره غذایی، بررسی تعاملی تداخلات دارویی و اشتراک تحویل دوره‌ای خودکار
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 perspective-[1200px]">
            
            <!-- Tool 1: Calorie & Nutrition Calculator 3D Card -->
            <div class="asena-3d-card group rounded-3xl p-6 text-white shadow-xl flex flex-col justify-between relative overflow-hidden transition-all duration-500 hover:-translate-y-2"
                 style="background: linear-gradient(145deg, #022c22 0%, #064e3b 45%, #001a48 100%); border: 1px solid rgba(52, 211, 153, 0.4); box-shadow: 0 20px 40px -15px rgba(2, 44, 34, 0.5);">
                <!-- Ambient Glow Behind 3D Image -->
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-emerald-400/20 rounded-full blur-3xl pointer-events-none group-hover:scale-150 transition-transform duration-700"></div>

                <div class="space-y-4 relative z-10">
                    <!-- 3D Render Asset Showcase -->
                    <div class="w-full h-44 rounded-2xl bg-black/30 border border-emerald-500/20 overflow-hidden flex items-center justify-center p-2 relative shadow-inner">
                        <img src="assets/images/tool-calculator-3d.webp" alt="محاسبه‌گر کالری و تغذیه پت ۳ بعدی" class="w-full h-full object-contain filter drop-shadow-[0_12px_20px_rgba(0,0,0,0.6)] group-hover:scale-110 group-hover:-rotate-2 transition-transform duration-500" onerror="this.src='assets/images/tool-calculator-3d.jpg'">
                        <span class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded-lg bg-emerald-500/90 text-slate-950 font-black text-[10px] tracking-wide shadow-md flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">tune</span>
                            <span>استاندارد FEDIAF</span>
                        </span>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-base sm:text-lg font-black text-white flex items-center justify-between leading-snug">
                            <span>محاسبه‌گر کالری و تغذیه پت</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_8px_#34d399]"></span>
                        </h3>
                        <p class="text-xs text-emerald-100/90 leading-relaxed font-normal">
                            محاسبه دقیق انرژی متابولیک (MER)، گرم مصرفی غذای خشک بر حسب نژاد، وزن، عقیم‌سازی و سن بر اساس فرمول‌های معتبر جهانی.
                        </p>
                    </div>

                    <!-- Micro Feature Tags -->
                    <div class="flex flex-wrap gap-1.5 pt-1 text-[11px]">
                        <span class="px-2.5 py-1 rounded-md bg-white/10 text-emerald-200 border border-white/10">سگ و گربه</span>
                        <span class="px-2.5 py-1 rounded-md bg-white/10 text-emerald-200 border border-white/10">تخمین آب روزانه</span>
                        <span class="px-2.5 py-1 rounded-md bg-white/10 text-emerald-200 border border-white/10">رایگان و بدون لاگین</span>
                    </div>
                </div>

                <a href="calculator.php" class="relative z-10 mt-6 px-5 py-3 rounded-2xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 text-xs font-black transition-all duration-200 flex items-center justify-between shadow-[0_6px_20px_rgba(52,211,153,0.4)] hover:shadow-[0_8px_25px_rgba(52,211,153,0.6)] active:translate-y-0.5 cursor-pointer">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">calculate</span>
                        <span>شروع محاسبه رایگان کالری</span>
                    </span>
                    <span class="material-symbols-outlined text-base transition-transform group-hover:-translate-x-1">arrow_back</span>
                </a>
            </div>

            <!-- Tool 2: Drug Interaction Checker 3D Card -->
            <div class="asena-3d-card group rounded-3xl p-6 text-white shadow-xl flex flex-col justify-between relative overflow-hidden transition-all duration-500 hover:-translate-y-2"
                 style="background: linear-gradient(145deg, #0f172a 0%, #1e3a8a 45%, #001a48 100%); border: 1px solid rgba(96, 165, 250, 0.4); box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.6);">
                <!-- Ambient Glow Behind 3D Image -->
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-blue-400/20 rounded-full blur-3xl pointer-events-none group-hover:scale-150 transition-transform duration-700"></div>

                <div class="space-y-4 relative z-10">
                    <!-- 3D Render Asset Showcase -->
                    <div class="w-full h-44 rounded-2xl bg-black/30 border border-blue-500/20 overflow-hidden flex items-center justify-center p-2 relative shadow-inner">
                        <img src="assets/images/tool-drug-3d.webp" alt="سامانه پایش تداخلات دارویی ۳ بعدی" class="w-full h-full object-contain filter drop-shadow-[0_12px_20px_rgba(0,0,0,0.6)] group-hover:scale-110 group-hover:rotate-2 transition-transform duration-500" onerror="this.src='assets/images/tool-drug-3d.jpg'">
                        <span class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded-lg bg-blue-500/90 text-white font-black text-[10px] tracking-wide shadow-md flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">verified</span>
                            <span>پایش فارماکولوژی</span>
                        </span>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-base sm:text-lg font-black text-white flex items-center justify-between leading-snug">
                            <span>پایشگر هوشمند تداخلات دارویی</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-400 shadow-[0_0_8px_#60a5fa]"></span>
                        </h3>
                        <p class="text-xs text-blue-100/90 leading-relaxed font-normal">
                            پایش هوشمند هم‌پوشانی داروها و مکمل‌های تجویزی جهت جلوگیری از سمیت دارویی، عوارض ناخواسته کبدی و کلیوی با رفرنس‌های دامپزشکی.
                        </p>
                    </div>

                    <!-- Micro Feature Tags -->
                    <div class="flex flex-wrap gap-1.5 pt-1 text-[11px]">
                        <span class="px-2.5 py-1 rounded-md bg-white/10 text-blue-200 border border-white/10">۸۰۰+ قلم داروی حیوانی</span>
                        <span class="px-2.5 py-1 rounded-md bg-white/10 text-blue-200 border border-white/10">سنجش سمیت و دوز</span>
                        <span class="px-2.5 py-1 rounded-md bg-white/10 text-blue-200 border border-white/10">بررسی تداخل آنتی‌بیوتیک</span>
                    </div>
                </div>

                <a href="interactions.php" class="relative z-10 mt-6 px-5 py-3 rounded-2xl bg-blue-400 hover:bg-blue-300 text-slate-950 text-xs font-black transition-all duration-200 flex items-center justify-between shadow-[0_6px_20px_rgba(96,165,250,0.4)] hover:shadow-[0_8px_25px_rgba(96,165,250,0.6)] active:translate-y-0.5 cursor-pointer">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">medication</span>
                        <span>بررسی آنلاین تداخلات دارویی</span>
                    </span>
                    <span class="material-symbols-outlined text-base transition-transform group-hover:-translate-x-1">arrow_back</span>
                </a>
            </div>

            <!-- Tool 3: Autoship Delivery 3D Card -->
            <div class="asena-3d-card group rounded-3xl p-6 text-white shadow-xl flex flex-col justify-between relative overflow-hidden transition-all duration-500 hover:-translate-y-2"
                 style="background: linear-gradient(145deg, #451a03 0%, #7c2d12 45%, #001a48 100%); border: 1px solid rgba(251, 146, 60, 0.45); box-shadow: 0 20px 40px -15px rgba(69, 26, 3, 0.6);">
                <!-- Ambient Glow Behind 3D Image -->
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-[#fd8100]/25 rounded-full blur-3xl pointer-events-none group-hover:scale-150 transition-transform duration-700"></div>

                <div class="space-y-4 relative z-10">
                    <!-- 3D Render Asset Showcase -->
                    <div class="w-full h-44 rounded-2xl bg-black/30 border border-orange-500/20 overflow-hidden flex items-center justify-center p-2 relative shadow-inner">
                        <img src="assets/images/tool-autoship-3d.webp" alt="سرویس تحویل خودکار دوره‌ای ۳ بعدی" class="w-full h-full object-contain filter drop-shadow-[0_12px_20px_rgba(0,0,0,0.6)] group-hover:scale-110 group-hover:-rotate-2 transition-transform duration-500" onerror="this.src='assets/images/tool-autoship-3d.jpg'">
                        <span class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded-lg bg-[#fd8100] text-white font-black text-[10px] tracking-wide shadow-md flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">rocket_launch</span>
                            <span>مدل محبوب Chewy</span>
                        </span>
                    </div>

                    <div class="space-y-2">
                        <h3 class="text-base sm:text-lg font-black text-white flex items-center justify-between leading-snug">
                            <span>اشتراک تحویل خودکار <span dir="ltr" class="inline-block font-sans text-xs opacity-90">(Autoship)</span></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-[#fd8100] shadow-[0_0_8px_#fd8100]"></span>
                        </h3>
                        <p class="text-xs text-orange-100/90 leading-relaxed font-normal">
                            برنامه‌ریزی هوشمند ارسال دوره‌ای غذای خشک، خاک بستر و داروهای مزمن پت بدون نیاز به خرید مجدد، با تخفیف دائمی تا ۱۵٪.
                        </p>
                    </div>

                    <!-- Micro Feature Tags -->
                    <div class="flex flex-wrap gap-1.5 pt-1 text-[11px]">
                        <span class="px-2.5 py-1 rounded-md bg-white/10 text-orange-200 border border-white/10">۱۰٪ تا ۱۵٪ تخفیف ثابت</span>
                        <span class="px-2.5 py-1 rounded-md bg-white/10 text-orange-200 border border-white/10">لغو یا ویرایش در هر زمان</span>
                        <span class="px-2.5 py-1 rounded-md bg-white/10 text-orange-200 border border-white/10">ارسال اکسپرس در موعد</span>
                    </div>
                </div>

                <a href="subscriptions.php" class="relative z-10 mt-6 px-5 py-3 rounded-2xl bg-[#fd8100] hover:bg-[#e07300] text-white text-xs font-black transition-all duration-200 flex items-center justify-between shadow-[0_6px_20px_rgba(253,129,0,0.4)] hover:shadow-[0_8px_25px_rgba(253,129,0,0.6)] active:translate-y-0.5 cursor-pointer">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">autorenew</span>
                        <span>مشاهده و فعال‌سازی اشتراک</span>
                    </span>
                    <span class="material-symbols-outlined text-base transition-transform group-hover:-translate-x-1">arrow_back</span>
                </a>
            </div>

        </div>
    </section>

    <!-- ========================================================================= -->
    <!-- BEAT 7: VERIFIED REVIEWS, EMERGENCY HELPLINE & CLOSING                   -->
    <!-- ========================================================================= -->
    <section class="space-y-8">
        <div class="text-center max-w-xl mx-auto space-y-2">
            <h2 class="text-xl sm:text-2xl font-black text-slate-800 leading-snug py-1">
                تجربه سرپرستان حیوانات خانگی در آسنا
            </h2>
            <p class="text-xs text-slate-500 font-medium">
                نظرات واقعی و احراز هویت شده مراجعین درمانی، خریداران پت‌شاپ و داروخانه
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Review 1 -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 space-y-4 flex flex-col justify-between"
                 style="box-shadow: 0 10px 30px -5px rgba(0, 26, 72, 0.07);">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold text-amber-900 border border-amber-200"
                             style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
                            <span class="text-amber-500 tracking-wider">★★★★★</span>
                            <span class="text-[11px] font-bold mr-1">۵.۰</span>
                        </div>
                        <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg">سرپرست هاسکی (تهران)</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-800 font-medium leading-relaxed">
                        «رزرو نوبت دکتر جراحی در کمتر از ۲ دقیقه انجام شد. سیستم پرونده سلامت آنلاین باعث شد همه آزمایش‌ها و عکس‌های رادیولوژی بلافاصله در دسترس پزشک باشد و معطلی کلینیک به صفر برسد.»
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-blue-100 text-[#001a48] font-bold text-xs flex items-center justify-center border border-blue-200">
                            م.ک
                        </div>
                        <div>
                            <span class="text-xs font-black text-slate-900 block">مریم کاظمی</span>
                            <span class="text-[10px] text-slate-500 block">مراجع بیمارستان پایتخت</span>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                        <span class="material-symbols-outlined text-xs">verified</span>
                        <span>نوبت تأییدشده</span>
                    </span>
                </div>
            </div>

            <!-- Review 2 -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 space-y-4 flex flex-col justify-between"
                 style="box-shadow: 0 10px 30px -5px rgba(0, 26, 72, 0.07);">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold text-amber-900 border border-amber-200"
                             style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
                            <span class="text-amber-500 tracking-wider">★★★★★</span>
                            <span class="text-[11px] font-bold mr-1">۵.۰</span>
                        </div>
                        <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg">سرپرست گربه پرشین (کرج)</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-800 font-medium leading-relaxed">
                        «داروی خاص کلیوی گربه‌ام را هیچ جا پیدا نمی‌کردم، در بخش داروخانه آنلاین نسخه را بارگذاری کردم و ظرف چند ساعت با بسته‌بندی دمای کنترل‌شده و یخ به موقع به دستم رسید. واقعاً حیاتی بود.»
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-900 font-bold text-xs flex items-center justify-center border border-emerald-200">
                            ا.ف
                        </div>
                        <div>
                            <span class="text-xs font-black text-slate-900 block">امیررضا فیاض</span>
                            <span class="text-[10px] text-slate-500 block">سفارش داروخانه زنجیره سرد</span>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                        <span class="material-symbols-outlined text-xs">verified</span>
                        <span>نسخه تأییدشده</span>
                    </span>
                </div>
            </div>

            <!-- Review 3 -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 space-y-4 flex flex-col justify-between"
                 style="box-shadow: 0 10px 30px -5px rgba(0, 26, 72, 0.07);">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold text-amber-900 border border-amber-200"
                             style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
                            <span class="text-amber-500 tracking-wider">★★★★★</span>
                            <span class="text-[11px] font-bold mr-1">۵.۰</span>
                        </div>
                        <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg">سرپرست شیتزو (تبریز)</span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-800 font-medium leading-relaxed">
                        «اشتراک خودکار Autoship عالی و بدون دردسر است؛ هر ماه دقیقاً سر موعد غذای خشک و تشویقی با ۱۰٪ تخفیف دائمی ارسال می‌شود و دیگر استرس تمام شدن جیره و نایاب شدن برند را ندارم.»
                    </p>
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-orange-100 text-[#fd8100] font-bold text-xs flex items-center justify-center border border-orange-200">
                            س.ن
                        </div>
                        <div>
                            <span class="text-xs font-black text-slate-900 block">سارا نوبخت</span>
                            <span class="text-[10px] text-slate-500 block">مشترک فعال تحویل خودکار</span>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                        <span class="material-symbols-outlined text-xs">verified</span>
                        <span>مشترک دائمی</span>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- 24/7 Emergency Triage Banner (3D Command Bar) -->
    <section class="relative rounded-3xl lg:rounded-[2.5rem] p-6 sm:p-8 lg:p-10 shadow-2xl border overflow-hidden transition-all"
             style="background: linear-gradient(135deg, #450a0a 0%, #881337 40%, #001a48 100%); border-color: rgba(244, 63, 94, 0.45); box-shadow: 0 25px 50px -12px rgba(69, 10, 10, 0.5);">
        <!-- Ambient Glowing Core -->
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-rose-500/25 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-[#fd8100]/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <!-- 3D Emergency Radar Beacon -->
                <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-br from-rose-500 to-rose-700 flex items-center justify-center text-white text-3xl shrink-0 shadow-[0_0_30px_rgba(244,63,94,0.6)] border border-rose-300/40">
                    <span class="material-symbols-outlined text-3xl animate-pulse">emergency</span>
                    <span class="absolute inset-0 rounded-2xl border-2 border-rose-400/60 animate-ping pointer-events-none"></span>
                </div>
                <div class="space-y-1 text-right">
                    <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-rose-500/30 text-rose-200 text-[11px] font-bold border border-rose-400/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                        <span>سامانه تریاژ فوری شبانه‌روزی</span>
                    </div>
                    <h3 class="text-lg sm:text-2xl font-black text-white tracking-tight">
                        اورژانس دامپزشکی شبانه‌روزی و تله‌هلث ۲۴ ساعته
                    </h3>
                    <p class="text-xs sm:text-sm text-rose-100/90 max-w-2xl font-normal leading-relaxed">
                        در شرایط اضطراری، بلع اجسام خارجی، مسمومیت‌ها یا تروما، بدون فوت وقت با پزشک تریاژ ارتباط بگیرید یا نزدیک‌ترین بیمارستان شبانه‌روزی را بیابید.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-end">
                <a href="organizations.php" class="flex-1 sm:flex-initial px-5 py-3.5 rounded-2xl bg-white hover:bg-slate-100 text-rose-950 font-black text-xs transition shadow-lg hover:shadow-xl flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap active:translate-y-0.5">
                    <span class="material-symbols-outlined text-base text-rose-700">local_hospital</span>
                    <span>مراکز اورژانس شبانه‌روزی</span>
                </a>
                <a href="tel:09146676978" class="flex-1 sm:flex-initial px-5 py-3.5 rounded-2xl bg-rose-500 hover:bg-rose-400 text-white font-black text-xs transition shadow-[0_6px_20px_rgba(244,63,94,0.4)] hover:shadow-[0_8px_25px_rgba(244,63,94,0.6)] border border-rose-300/40 flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap active:translate-y-0.5">
                    <span class="material-symbols-outlined text-base animate-bounce">call</span>
                    <span>تماس فوری تریاژ: ۰۹۱۴۶۶۷۶۹۷۸</span>
                </a>
            </div>
        </div>
    </section>

</main>

<style>
.perspective-\[1200px\] {
    perspective: 1200px;
}
.asena-3d-card {
    transform-style: preserve-3d;
    transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.4, 1), box-shadow 0.3s ease, border-color 0.3s ease;
    will-change: transform;
}
.asena-3d-card:hover {
    box-shadow: 0 25px 50px -12px rgba(0, 26, 72, 0.45);
}
@keyframes beacon-ping {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.3); opacity: 0; }
    100% { transform: scale(1); opacity: 0; }
}
</style>

<script>
function switchHeroTab(tab) {
    const tabs = ['vet', 'shop', 'pharmacy'];
    tabs.forEach(t => {
        const btn = document.getElementById('heroTab' + t.charAt(0).toUpperCase() + t.slice(1));
        const form = document.getElementById('heroForm' + t.charAt(0).toUpperCase() + t.slice(1));
        if (t === tab) {
            btn.className = 'hero-tab-btn py-2.5 px-3 rounded-xl transition bg-white text-[#001a48] font-black shadow-sm flex items-center justify-center gap-1.5 cursor-pointer';
            form.classList.remove('hidden');
        } else {
            btn.className = 'hero-tab-btn py-2.5 px-3 rounded-xl transition text-white/80 hover:text-white flex items-center justify-center gap-1.5 cursor-pointer';
            form.classList.add('hidden');
        }
    });
}

// 3D Card Interactive Tilt Effect
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.asena-3d-card');
    cards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            const rotateX = -(y / (rect.height / 2)) * 5; // max 5 deg
            const rotateY = (x / (rect.width / 2)) * 5;
            card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(-8px)`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0px)';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
