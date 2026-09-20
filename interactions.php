<?php
/**
 * ASENA Enterprise - Pet Drug Interaction & Contraindication Checker
 * سامانه هوشمند پایش تداخلات دارویی و منع مصرف حیوانات خانگی با هوش مصنوعی بالینی
 */

// Route to partner portal if accessed from partner/SMS context
if (isset($_GET['tab']) || isset($_GET['portal']) || isset($_POST['send_direct_sms']) || (isset($_POST['action']) && in_array($_POST['action'], ['send_direct_sms', 'purchase_sms_package', 'purchase_sms_gateway', 'save_bank_settings'], true))) {
    require_once __DIR__ . '/partner_interactions.php';
    exit;
}

$page_title = "پایشگر هوشمند تداخلات دارویی و منع مصرف پت | هوش مصنوعی آسنا";
$page_description = "بررسی بالینی هم‌پوشانی داروها، مکمل‌ها و موارد منع مصرف در سگ، گربه، اسب و پرندگان با هوش مصنوعی بر اساس رفرنس‌های جهانی دامپزشکی Plumb's و BSAVA.";

require_once 'includes/header.php';

// Fetch current user's registered pets if logged in
$userPets = [];
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId > 0 && isset($pdo)) {
    try {
        $pStmt = $pdo->prepare("SELECT id, name, type, race, weight_kg FROM user_pets WHERE user_id = ? ORDER BY id DESC");
        $pStmt->execute([$userId]);
        $userPets = $pStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}
?>

<main class="max-w-container-max mx-auto overflow-hidden py-8 px-margin-desktop min-h-[85vh]">

    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-on-surface-variant mb-6">
        <a href="index.php" class="hover:text-primary transition-colors">خانه</a>
        <span>></span>
        <span class="text-on-surface-variant">ابزارهای هوشمند سلامت</span>
        <span>></span>
        <span class="text-primary font-bold">پایشگر تداخلات دارویی و منع مصرف</span>
    </div>

    <!-- Hero Header -->
    <div class="text-center max-w-3xl mx-auto mb-10 space-y-3">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-full text-xs font-bold shadow-2xs">
            <span class="material-symbols-outlined text-sm text-blue-600">verified_user</span>
            <span>استاندارد فارماکولوژی دامپزشکی Plumb's & BSAVA</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-800 tracking-tight leading-snug py-1">
            سامانه هوشمند سنجش تداخلات دارویی پت
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal">
            نام داروهای مصرفی یا تجویزی حیوان خانگی خود را وارد کنید تا هوش مصنوعی بالینی، تداخلات خطرناک، افت اثربخشی، مسمومیت‌های گونه‌ای و جدول ساعات فاصله مصرف را فوراً پایش و تحلیل کند.
        </p>
    </div>

    <!-- Main Drug Interaction Component -->
    <div class="bg-gradient-to-br from-[#001a48] via-[#002d72] to-slate-900 rounded-[2.5rem] p-6 sm:p-10 lg:p-12 text-white shadow-2xl relative overflow-hidden border border-white/10 mb-12">
        <!-- Background Decorative Ambient Glows -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#fd8100]/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 relative z-10">

            <!-- Left Form Column: Pet & Drug Selection (7 cols) -->
            <div class="lg:col-span-7 space-y-6">

                <!-- 1. Quick Pet Selection (For Logged In Users) -->
                <?php if (!empty($userPets)): ?>
                <div class="space-y-2 bg-white/5 p-3.5 rounded-2xl border border-white/10">
                    <label class="text-xs font-bold text-white/90 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-400 text-sm">pets</span>
                        <span>انتخاب سریع از میان پت‌های ثبت‌شده شما:</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($userPets as $up): ?>
                        <button type="button" 
                                onclick="selectRegisteredPet(<?= htmlspecialchars(json_encode($up), ENT_QUOTES, 'UTF-8') ?>)"
                                class="registered-pet-btn px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold text-white border border-white/15 transition flex items-center gap-1.5 cursor-pointer">
                            <span><?= ($up['type'] === 'cat') ? '🐈' : (($up['type'] === 'horse') ? '🐎' : '🐕') ?></span>
                            <span><?= htmlspecialchars($up['name']) ?></span>
                            <span class="text-[10px] text-white/60 font-mono"><?= $up['weight_kg'] > 0 ? (float)$up['weight_kg'] . ' kg' : '' ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 2. Pet Species & Clinical Profile -->
                <div class="space-y-3">
                    <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۱</span>
                        مشخصات بالینی بیمار (گونه و جثه):
                    </label>
                    
                    <!-- Species Switcher -->
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <button type="button" onclick="setDrugSpecies('dog')" id="drugSpeciesDog" class="drug-species-btn py-2.5 px-2 rounded-2xl font-black text-xs flex flex-col items-center justify-center gap-1 border-2 transition-all bg-blue-500 text-white border-blue-400 shadow-md cursor-pointer">
                            <span class="text-lg">🐕</span>
                            <span>سگ (Canine)</span>
                        </button>
                        <button type="button" onclick="setDrugSpecies('cat')" id="drugSpeciesCat" class="drug-species-btn py-2.5 px-2 rounded-2xl font-bold text-xs flex flex-col items-center justify-center gap-1 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15 cursor-pointer">
                            <span class="text-lg">🐈</span>
                            <span>گربه (Feline)</span>
                        </button>
                        <button type="button" onclick="setDrugSpecies('horse')" id="drugSpeciesHorse" class="drug-species-btn py-2.5 px-2 rounded-2xl font-bold text-xs flex flex-col items-center justify-center gap-1 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15 cursor-pointer">
                            <span class="text-lg">🐎</span>
                            <span>اسب (Equine)</span>
                        </button>
                        <button type="button" onclick="setDrugSpecies('bird')" id="drugSpeciesBird" class="drug-species-btn py-2.5 px-2 rounded-2xl font-bold text-xs flex flex-col items-center justify-center gap-1 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15 cursor-pointer">
                            <span class="text-lg">🦜</span>
                            <span>پرنده (Avian)</span>
                        </button>
                        <button type="button" onclick="setDrugSpecies('exotic')" id="drugSpeciesExotic" class="drug-species-btn py-2.5 px-2 rounded-2xl font-bold text-xs flex flex-col items-center justify-center gap-1 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15 cursor-pointer">
                            <span class="text-lg">🐇</span>
                            <span>اگزوتیک/خرگوش</span>
                        </button>
                    </div>

                    <!-- Pet Name, Breed & Weight Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-bold text-white/70 mb-1">نام بیمار:</label>
                            <input type="text" id="drugPetName" placeholder="مثال: لئو / بتی" class="w-full bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-xs text-white placeholder-white/40 focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-white/70 mb-1">نژاد پت:</label>
                            <input type="text" id="drugPetRace" placeholder="مثال: ژرمن شپرد / پرشین" class="w-full bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-xs text-white placeholder-white/40 focus:outline-none focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-white/70 mb-1">وزن بیمار (کیلوگرم):</label>
                            <div class="relative">
                                <input type="number" id="drugPetWeight" value="12" min="0.2" max="120" step="0.5" class="w-full bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-xs font-mono font-bold text-white focus:outline-none focus:border-blue-400 text-left dir-ltr pl-8">
                                <span class="absolute left-2.5 top-2 text-[10px] text-white/50">kg</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Pre-existing Sensitive Conditions (بیماری‌های زمینه‌ای حساس) -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-white/90 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm text-amber-300">clinical_notes</span>
                            بیماری‌های زمینه‌ای و شرایط ویژه بیمار (اختیاری):
                        </span>
                        <span class="text-[10px] text-white/50">کلیک جهت انتخاب چندگانه</span>
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="toggleCondition(this, 'renal')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 hover:bg-white/15 text-white/80 border border-white/15 transition cursor-pointer">
                            نارسایی کلیوی (CKD)
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'hepatic')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 hover:bg-white/15 text-white/80 border border-white/15 transition cursor-pointer">
                            اختلال یا نارسایی کبدی
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'cardiac')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 hover:bg-white/15 text-white/80 border border-white/15 transition cursor-pointer">
                            بیماری قلبی / فشار خون
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'epilepsy')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 hover:bg-white/15 text-white/80 border border-white/15 transition cursor-pointer">
                            صرع و سابقه تشنج
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'ulcer')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 hover:bg-white/15 text-white/80 border border-white/15 transition cursor-pointer">
                            زخم معده یا گوارشی
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'diabetes')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 hover:bg-white/15 text-white/80 border border-white/15 transition cursor-pointer">
                            دیابت
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'pregnancy')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 hover:bg-white/15 text-white/80 border border-white/15 transition cursor-pointer">
                            بارداری یا شیردهی
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'mdr1')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-white/10 hover:bg-white/15 text-white/80 border border-white/15 transition cursor-pointer">
                            جهش ژنی حساسیت MDR1
                        </button>
                    </div>
                </div>

                <!-- 4. Drug Search & Intake Manager -->
                <div class="space-y-3 pt-2 border-t border-white/10">
                    <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۲</span>
                            افزودن داروهای مصرفی همزمان:
                        </span>
                        <span class="text-[10px] text-blue-200">جستجو در داروخانه یا تایپ دستی</span>
                    </label>

                    <!-- Search Input with Autocomplete Dropdown -->
                    <div class="relative">
                        <div class="flex items-center bg-white rounded-2xl shadow-md overflow-hidden p-1">
                            <span class="material-symbols-outlined text-slate-400 px-3 text-xl">search</span>
                            <input type="text" id="drugSearchInput" 
                                   placeholder="نام دارو را جستجو یا تایپ کنید (مثلاً: کارپروفن، پردنیزولون، انروفلوکساسین...)" 
                                   autocomplete="off"
                                   class="w-full py-2.5 text-xs text-slate-800 focus:outline-none placeholder-slate-400">
                            <button type="button" onclick="addCustomDrugFromInput()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-1 shrink-0 cursor-pointer">
                                <span class="material-symbols-outlined text-base">add</span>
                                <span>افزودن دارو</span>
                            </button>
                        </div>

                        <!-- Autocomplete Results Menu -->
                        <div id="drugSearchResults" class="hidden absolute top-full right-0 left-0 mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden text-right max-h-64 overflow-y-auto">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <!-- Selected Medication Chips / Pills Container -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-[11px] text-white/70">
                            <span>فهرست داروهای انتخاب‌شده (<span id="drugCountBadge" class="font-bold text-amber-300 font-mono">۰</span> قلم):</span>
                            <button type="button" onclick="clearAllDrugs()" class="text-white/50 hover:text-white transition text-[10px] cursor-pointer">پاک کردن همه</button>
                        </div>
                        
                        <div id="selectedDrugsContainer" class="min-h-[70px] bg-white/5 rounded-2xl p-3 border border-white/10 flex flex-wrap items-center gap-2">
                            <span id="emptyDrugsPrompt" class="text-xs text-white/40 flex items-center gap-1.5 py-2 px-1">
                                <span class="material-symbols-outlined text-sm">info</span>
                                هنوز دارویی اضافه نشده است. حداقل ۲ داروی همزمان را جهت بررسی تداخل یا ۱ دارو را برای بررسی سمیت گونه‌ای وارد فرمایید.
                            </span>
                        </div>
                    </div>

                    <!-- One-Click Preset Clinical Scenarios -->
                    <div class="pt-2">
                        <div class="text-[11px] font-bold text-white/70 mb-1.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs text-amber-400">science</span>
                            <span>سناریوهای بالینی آماده (برای تست سریع سیستم):</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5 text-[10px]">
                            <button type="button" onclick="loadScenario('nsaid_steroid')" class="px-2.5 py-1 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-200 border border-red-400/30 font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>🚨 تداخل مرگبار: کارپروفن + پردنیزولون</span>
                            </button>
                            <button type="button" onclick="loadScenario('paracetamol_cat')" class="px-2.5 py-1 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-200 border border-red-400/30 font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>🚨 سمیت کشنده: استامینوفن در گربه</span>
                            </button>
                            <button type="button" onclick="loadScenario('antibiotic_sucralfate')" class="px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 border border-amber-400/30 font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>⚠️ افت جذب: انروفلوکساسین + سوکرالفات</span>
                            </button>
                            <button type="button" onclick="loadScenario('safe_duo')" class="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-200 border border-emerald-400/30 font-bold transition flex items-center gap-1 cursor-pointer">
                                <span>✅ هم‌افزای ایمن: آموکسی‌سیلین + پروبیوتیک</span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- 5. Primary Analysis Action Button -->
                <div class="pt-2">
                    <button type="button" onclick="runDrugInteractionAnalysis()" id="btnRunDrugAnalysis" class="w-full bg-gradient-to-r from-blue-500 via-blue-600 to-indigo-600 hover:from-blue-400 hover:to-indigo-500 text-white py-4 px-6 rounded-2xl font-black text-sm text-center shadow-xl shadow-blue-600/30 hover:shadow-blue-500/50 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                        <span class="material-symbols-outlined text-xl">psychology</span>
                        <span>شروع پایش و تحلیل تداخلات با هوش مصنوعی بالینی</span>
                    </button>
                </div>

            </div>

            <!-- Right Results Column (5 cols) -->
            <div class="lg:col-span-5 flex flex-col justify-between space-y-6">

                <!-- Clinical Verification Header Badge -->
                <div class="bg-white/10 backdrop-blur-md rounded-3xl p-5 border border-white/15 space-y-3">
                    <div class="flex items-center justify-between border-b border-white/10 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-400 text-xl">monitor_heart</span>
                            <span class="font-bold text-xs sm:text-sm">وضعیت ارزیابی فارماکوکینتیک</span>
                        </div>
                        <span id="analysisStatusBadge" class="text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-white/15 text-white/80">
                            در انتظار داروها
                        </span>
                    </div>

                    <!-- Dynamic Visual Safety Meter Card -->
                    <div id="safetyMeterBox" class="p-4 rounded-2xl bg-white/5 border border-white/10 text-center space-y-2 transition-all">
                        <div id="safetyIconWrap" class="w-14 h-14 rounded-2xl bg-blue-500/20 text-blue-300 flex items-center justify-center text-3xl mx-auto shadow-inner">
                            <span class="material-symbols-outlined text-3xl">medication_liquid</span>
                        </div>
                        <h4 id="safetyHeading" class="text-base font-black text-white">پایشگر آماده بررسی است</h4>
                        <p id="safetySummaryText" class="text-xs text-white/70 leading-relaxed">
                            پس از افزودن داروهای پت، روی دکمه شروع پایش کلیک کنید تا آنالیز کامل شیمیایی و فیزیولوژیک صادر گردد.
                        </p>
                    </div>

                    <!-- Detailed Interactive Results Container (Appears after analysis) -->
                    <div id="detailedResultsArea" class="space-y-3 hidden">

                        <!-- Interactions Accordion List -->
                        <div id="interactionsListWrapper" class="space-y-2">
                            <!-- Populated dynamically via JS -->
                        </div>

                        <!-- Species Contraindications Alert -->
                        <div id="contraindicationsWrapper" class="space-y-2">
                            <!-- Populated dynamically via JS -->
                        </div>

                        <!-- Time Spacing Guidelines -->
                        <div id="timeSpacingWrapper" class="bg-amber-950/40 border border-amber-400/30 p-3.5 rounded-2xl space-y-1.5 hidden text-xs">
                            <div class="font-bold text-amber-200 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">schedule</span>
                                <span>دستورالعمل فاصله زمانی بین داروها:</span>
                            </div>
                            <ul id="timeSpacingList" class="space-y-1 text-[11px] text-amber-100/90 list-disc list-inside"></ul>
                        </div>

                        <!-- Safe Synergies List -->
                        <div id="safeCombinationsWrapper" class="bg-emerald-950/40 border border-emerald-400/30 p-3.5 rounded-2xl space-y-1.5 hidden text-xs">
                            <div class="font-bold text-emerald-200 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">verified</span>
                                <span>هم‌پوشانی‌های ایمن و مفید:</span>
                            </div>
                            <ul id="safeCombinationsList" class="space-y-1 text-[11px] text-emerald-100/90 list-disc list-inside"></ul>
                        </div>

                        <!-- Action Buttons: Save to Dossier & Print -->
                        <div class="pt-3 border-t border-white/10 grid grid-cols-2 gap-2">
                            <button type="button" onclick="saveDrugReportToProfile()" id="btnSaveReport" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-2.5 px-3 rounded-xl font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-md cursor-pointer">
                                <span class="material-symbols-outlined text-base">save</span>
                                <span>ثبت در پرونده پت</span>
                            </button>
                            <button type="button" onclick="window.print()" class="w-full bg-white/15 hover:bg-white/25 text-white py-2.5 px-3 rounded-xl font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <span class="material-symbols-outlined text-base">print</span>
                                <span>چاپ نسخه بالینی</span>
                            </button>
                        </div>

                        <!-- Direct Bridge to Vet Booking or Pharmacy -->
                        <div class="pt-1 flex items-center justify-between text-[11px] text-white/70">
                            <a href="booking.php" class="hover:text-amber-300 transition flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs text-amber-400">videocam</span>
                                <span>مشاوره ویزیت با دامپزشک آنلاین</span>
                            </a>
                            <a href="pharmacy.php" class="hover:text-blue-300 transition flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs text-blue-400">local_pharmacy</span>
                                <span>داروخانه دامپزشکی آسنا</span>
                            </a>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Printable Official Assessment Report Container (For window.print) -->
    <div id="printableDrugReportArea" class="hidden">
        <div style="direction: rtl; text-align: right; font-family: Tahoma, sans-serif; padding: 25px; border: 2px solid #001a48; border-radius: 12px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #001a48; padding-bottom: 12px; margin-bottom: 15px;">
                <div>
                    <h2 style="margin: 0; color: #001a48; font-size: 18px;">سامانه جامع دامپزشکی آسنا | گزارش رسمی پایش تداخلات دارویی پت</h2>
                    <p style="margin: 4px 0 0 0; font-size: 11px; color: #666;">مطابق استانداردهای جهانی فارماکوپیا Plumb's Veterinary Drug Handbook</p>
                </div>
                <div style="text-align: left; font-size: 11px; font-family: monospace;">
                    <div>شماره رهگیری: <strong id="printReportSerial">-</strong></div>
                    <div>تاریخ صدور: <span id="printReportDate"><?= date('Y/m/d - H:i') ?></span></div>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 12px;">
                <strong>مشخصات بیمار:</strong> <span id="printPetDetails">-</span>
            </div>

            <div style="margin-bottom: 15px; font-size: 12px;">
                <strong>فهرست داروهای ارزیابی‌شده:</strong>
                <div id="printDrugsList" style="margin-top: 5px; font-size: 11px; color: #333;">-</div>
            </div>

            <div id="printInteractionsSection" style="margin-bottom: 15px;">
                <!-- Filled dynamically -->
            </div>

            <div style="margin-top: 30px; border-top: 1px dashed #ccc; padding-top: 10px; font-size: 10px; color: #777; text-align: center;">
                این سند صرفاً خروجی هوش مصنوعی بالینی و پایگاه دانش فارماکولوژی آسنا است و جایگزین تشخیص و دستور کتبی دکتر دامپزشک نمی‌باشد.
            </div>
        </div>
    </div>

    <!-- Educational Clinical FAQs Bento Grid -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm mb-12 space-y-6">
        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
            <span class="material-symbols-outlined text-blue-600 text-2xl">help</span>
            <h3 class="text-sm sm:text-base font-black text-slate-800">
                پرسش‌های متداول و راهنمای ایمنی مصرف داروی حیوانات خانگی
            </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs leading-relaxed text-slate-600">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                <h4 class="font-bold text-slate-800 text-xs flex items-center gap-1.5 text-red-600">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    چرا مصرف همزمان کورتون با مسکن‌های ضدالتهاب (NSAID) کشنده است؟
                </h4>
                <p>
                    مسکن‌های ضدالتهاب غیر استروئیدی (مانند کارپروفن و ملوکسیکام) و داروهای استروئیدی (پردنیزولون و دگزامتازون) هر دو تولید موکوس محافظ لایه داخلی معده را به شدت سرکوب می‌کنند. مصرف همزمان این دو دسته منجر به زخم‌های عمیق، سوراخ شدن دیواره روده و خونریزی داخلی مهلک می‌گردد. حداقل ۵ الی ۷ روز فاصله پاک‌سازی (Washout) الزامی است.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                <h4 class="font-bold text-slate-800 text-xs flex items-center gap-1.5 text-red-600">
                    <span class="material-symbols-outlined text-sm">dangerous</span>
                    چرا استامینوفن (پاراستامول) برای گربه‌ها سمی و کشنده است؟
                </h4>
                <p>
                    گربه‌ها به دلیل نقص فیزیولوژیک در سیستم آنزیمی کبد (آنزیم گلوکورونیل ترانسفراز)، توانایی متابولیسم پاراستامول را ندارند. مصرف حتی نصف قرص استامینوفن در گربه باعث متهموگلوبینمی (تغییر ساختار هموگلوبین و ناتوانی در حمل اکسیژن)، تورم صورت، زردی و مرگ در چند ساعت می‌شود.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                <h4 class="font-bold text-slate-800 text-xs flex items-center gap-1.5 text-amber-600">
                    <span class="material-symbols-outlined text-sm">schedule</span>
                    فاصله زمانی مجاز بین شربت‌های محافظ معده و آنتی‌بیوتیک‌ها چقدر است؟
                </h4>
                <p>
                    داروهایی نظیر سوکرالفات، شربت‌های آنتی‌اسید و مکمل‌های حاوی کلسیم یا آهن با اتصال به آنتی‌بیوتیک‌هایی مانند انروفلوکساسین و تتراسایکلین‌ها مانع از جذب آن‌ها می‌شوند. همواره حداقل ۲ ساعت فاصله بین این داروها رعایت گردد.
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                <h4 class="font-bold text-slate-800 text-xs flex items-center gap-1.5 text-blue-600">
                    <span class="material-symbols-outlined text-sm">pets</span>
                    جهش ژنتیکی MDR1 در سگ‌ها چیست و چه داروهایی خطرناک است؟
                </h4>
                <p>
                    در نژادهای گله نظیر کالی، استرالین شپرد و شلتی، جهش ژن MDR1 موجب نقص پمپ دفع دارویی در سد خونی-مغزی می‌شود. داروهایی مانند آیورمکتین و لوپرامید در این نژادها منجر به مسمومیت شدید عصبی و کما می‌گردد.
                </p>
            </div>
        </div>
    </div>

</main>

<script>
(() => {
    // Current drug interaction state
    const state = {
        species: 'dog',
        petName: '',
        race: '',
        weight: 12,
        conditions: [],
        drugs: [],
        latestReport: null
    };

    // DOM Elements
    const searchInput = document.getElementById('drugSearchInput');
    const searchResults = document.getElementById('drugSearchResults');
    const selectedContainer = document.getElementById('selectedDrugsContainer');
    const emptyPrompt = document.getElementById('emptyDrugsPrompt');
    const drugCountBadge = document.getElementById('drugCountBadge');

    // Species switcher
    window.setDrugSpecies = function(species) {
        state.species = species;
        document.querySelectorAll('.drug-species-btn').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'border-blue-400', 'shadow-md');
            btn.classList.add('bg-white/10', 'text-white/80', 'border-white/15');
        });
        const activeBtn = document.getElementById('drugSpecies' + species.charAt(0).toUpperCase() + species.slice(1));
        if (activeBtn) {
            activeBtn.classList.remove('bg-white/10', 'text-white/80', 'border-white/15');
            activeBtn.classList.add('bg-blue-500', 'border-blue-400', 'shadow-md');
        }
    };

    // Pre-existing condition toggle
    window.toggleCondition = function(el, cond) {
        const idx = state.conditions.indexOf(cond);
        if (idx === -1) {
            state.conditions.push(cond);
            el.classList.add('bg-amber-500', 'text-slate-950', 'font-black', 'border-amber-400');
            el.classList.remove('bg-white/10', 'text-white/80', 'border-white/15');
        } else {
            state.conditions.splice(idx, 1);
            el.classList.remove('bg-amber-500', 'text-slate-950', 'font-black', 'border-amber-400');
            el.classList.add('bg-white/10', 'text-white/80', 'border-white/15');
        }
    };

    // Quick Pet Selection
    window.selectRegisteredPet = function(pet) {
        if (!pet) return;
        if (pet.type && ['dog', 'cat', 'horse', 'bird', 'exotic'].includes(pet.type)) {
            setDrugSpecies(pet.type);
        }
        const nameInp = document.getElementById('drugPetName');
        const raceInp = document.getElementById('drugPetRace');
        const weightInp = document.getElementById('drugPetWeight');

        if (nameInp) nameInp.value = pet.name || '';
        if (raceInp) raceInp.value = pet.race || '';
        if (weightInp && pet.weight_kg > 0) weightInp.value = pet.weight_kg;

        state.petName = pet.name || '';
        state.race = pet.race || '';
        state.weight = parseFloat(pet.weight_kg) || 12;
    };

    // Add drug item
    window.addDrug = function(drugName, category = '', dose = '') {
        const cleanName = drugName.trim();
        if (!cleanName) return;

        // Check if already added
        const exists = state.drugs.some(d => d.name.toLowerCase() === cleanName.toLowerCase());
        if (exists) {
            searchInput.value = '';
            searchResults.classList.add('hidden');
            return;
        }

        state.drugs.push({
            name: cleanName,
            category: category,
            dose: dose,
            frequency: ''
        });

        renderDrugsList();
        searchInput.value = '';
        searchResults.classList.add('hidden');
    };

    window.addCustomDrugFromInput = function() {
        const val = searchInput.value.trim();
        if (val) {
            addDrug(val, 'داروی تجویزی / آزاد');
        }
    };

    // Remove single drug
    window.removeDrug = function(idx) {
        if (idx >= 0 && idx < state.drugs.length) {
            state.drugs.splice(idx, 1);
            renderDrugsList();
        }
    };

    // Clear all drugs
    window.clearAllDrugs = function() {
        state.drugs = [];
        renderDrugsList();
        resetResultsView();
    };

    // Render drug chips
    function renderDrugsList() {
        if (drugCountBadge) drugCountBadge.textContent = state.drugs.length;

        if (state.drugs.length === 0) {
            selectedContainer.innerHTML = '';
            selectedContainer.appendChild(emptyPrompt);
            emptyPrompt.classList.remove('hidden');
            return;
        }

        emptyPrompt.classList.add('hidden');
        selectedContainer.innerHTML = '';

        state.drugs.forEach((d, idx) => {
            const chip = document.createElement('div');
            chip.className = 'flex items-center gap-2 bg-white text-slate-800 py-1.5 px-3 rounded-xl text-xs font-bold shadow-sm border border-slate-200 animate-in fade-in zoom-in-95 duration-150';
            chip.innerHTML = `
                <span class="material-symbols-outlined text-blue-600 text-sm">medication</span>
                <span>${escapeHtml(d.name)}</span>
                ${d.category ? `<span class="text-[9px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">${escapeHtml(d.category)}</span>` : ''}
                <button type="button" onclick="removeDrug(${idx})" class="w-4 h-4 rounded-full bg-slate-200 hover:bg-red-500 hover:text-white text-slate-600 flex items-center justify-center text-[10px] transition cursor-pointer">✕</button>
            `;
            selectedContainer.appendChild(chip);
        });
    }

    // Quick Preset Scenarios
    window.loadScenario = function(type) {
        state.drugs = [];
        if (type === 'nsaid_steroid') {
            setDrugSpecies('dog');
            document.getElementById('drugPetName').value = 'تدی';
            document.getElementById('drugPetRace').value = 'ژرمن شپرد';
            document.getElementById('drugPetWeight').value = '28';
            addDrug('کارپروفن ۵۰ (Rimadyl / Carprofen)', 'مسکن و ضدالتهاب NSAID');
            addDrug('پردنیزولون ۵ (Prednisolone)', 'کورتیکواستروئید');
        } else if (type === 'paracetamol_cat') {
            setDrugSpecies('cat');
            document.getElementById('drugPetName').value = 'لوسی';
            document.getElementById('drugPetRace').value = 'DSH گربه خیابانی';
            document.getElementById('drugPetWeight').value = '4';
            addDrug('استامینوفن / پاراستامول (Acetaminophen)', 'ضد درد و تب انسانی');
        } else if (type === 'antibiotic_sucralfate') {
            setDrugSpecies('dog');
            document.getElementById('drugPetName').value = 'مکس';
            document.getElementById('drugPetRace').value = 'گلدن رتریور';
            document.getElementById('drugPetWeight').value = '30';
            addDrug('انروفلوکساسین (Enrofloxacin)', 'آنتی‌بیوتیک فلوروکینولون');
            addDrug('شربت سوکرالفات (Sucralfate)', 'محافظ مخاط معده');
        } else if (type === 'safe_duo') {
            setDrugSpecies('dog');
            document.getElementById('drugPetName').value = 'برفی';
            document.getElementById('drugPetRace').value = 'پامرانین';
            document.getElementById('drugPetWeight').value = '5';
            addDrug('آموکسی‌سیلین-کلاوولانات (Clavamox)', 'آنتی‌بیوتیک پنی‌سیلین');
            addDrug('پودر پروبیوتیک پت (Probiotic)', 'مکمل بازسازی فلور روده');
        }
    };

    // Live search debounced against pharmacy_medicines
    let searchTimeout = null;
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const q = e.target.value.trim();
            clearTimeout(searchTimeout);
            if (q.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const res = await fetch(`actions/ai_drug_analysis.php?action=search&q=${encodeURIComponent(q)}&species=${state.species}`);
                    const data = await res.json();
                    if (data && data.success && data.results && data.results.length > 0) {
                        searchResults.innerHTML = '';
                        data.results.forEach(m => {
                            const item = document.createElement('div');
                            item.className = 'p-2.5 hover:bg-slate-50 border-b border-slate-100 flex items-center justify-between cursor-pointer transition';
                            item.innerHTML = `
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-blue-500 text-sm">vaccines</span>
                                    <div>
                                        <div class="text-xs font-bold text-slate-800">${escapeHtml(m.name)}</div>
                                        <div class="text-[10px] text-slate-400">${escapeHtml(m.category || '')} ${m.brand ? '• ' + escapeHtml(m.brand) : ''}</div>
                                    </div>
                                </div>
                                <span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded-lg font-bold">+ افزودن</span>
                            `;
                            item.onclick = () => addDrug(m.name, m.category || 'داروی داروخانه');
                            searchResults.appendChild(item);
                        });
                        searchResults.classList.remove('hidden');
                    } else {
                        searchResults.innerHTML = `
                            <div class="p-3 text-center text-xs text-slate-400">
                                دارویی در کاتالوگ با این نام یافت نشد. دکمه «افزودن دارو» را بزنید تا با همین عنوان دستی اضافه شود.
                            </div>
                        `;
                        searchResults.classList.remove('hidden');
                    }
                } catch(err) {
                    console.error(err);
                }
            }, 250);
        });

        // Keydown Enter on input
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCustomDrugFromInput();
            }
        });
    }

    // Hide search results on outside click
    document.addEventListener('click', (e) => {
        if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });

    // Run AI & Clinical Interaction Analysis
    window.runDrugInteractionAnalysis = async function() {
        if (state.drugs.length === 0) {
            alert('لطفاً ابتدا حداقل یک داروی مصرفی را به فهرست اضافه فرمایید.');
            searchInput.focus();
            return;
        }

        const nameInp = document.getElementById('drugPetName');
        const raceInp = document.getElementById('drugPetRace');
        const weightInp = document.getElementById('drugPetWeight');

        state.petName = nameInp ? nameInp.value.trim() : '';
        state.race = raceInp ? raceInp.value.trim() : '';
        state.weight = weightInp ? parseFloat(weightInp.value) || 12 : 12;

        const btn = document.getElementById('btnRunDrugAnalysis');
        const origContent = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-lg animate-spin">sync</span><span>در حال تطبیق فارماکوپیا و اسکن تداخلات...</span>';
        }

        try {
            const csrf = window.ASENA_CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('actions/ai_drug_analysis.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    csrf_token: csrf,
                    species: state.species,
                    pet_name: state.petName,
                    race: state.race,
                    weight_kg: state.weight,
                    conditions: state.conditions,
                    drugs: state.drugs
                })
            });

            const data = await res.json();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origContent;
            }

            if (data && data.success) {
                state.latestReport = data;
                displayAnalysisResults(data);
            } else {
                alert((data && data.message) || 'خطا در تحلیل تداخلات دارویی. لطفاً مجدداً تلاش فرمایید.');
            }
        } catch(err) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origContent;
            }
            console.error('Analysis error:', err);
            alert('خطا در برقراری ارتباط با سرور.');
        }
    };

    // Render Clinical Results to UI
    function displayAnalysisResults(data) {
        const safetyBadge = document.getElementById('analysisStatusBadge');
        const meterBox = document.getElementById('safetyMeterBox');
        const iconWrap = document.getElementById('safetyIconWrap');
        const heading = document.getElementById('safetyHeading');
        const summaryText = document.getElementById('safetySummaryText');
        const resultsArea = document.getElementById('detailedResultsArea');

        const overall = data.overall_safety || 'safe';

        // Reset styling classes
        meterBox.className = 'p-5 rounded-2xl border text-center space-y-2 transition-all';

        if (overall === 'critical') {
            meterBox.classList.add('bg-red-950/60', 'border-red-500', 'text-red-100');
            iconWrap.className = 'w-14 h-14 rounded-2xl bg-red-500/25 border border-red-400 text-red-300 flex items-center justify-center text-3xl mx-auto shadow-lg animate-pulse';
            iconWrap.innerHTML = '<span class="material-symbols-outlined text-3xl">dangerous</span>';
            heading.textContent = '🚨 هشدار بحرانی: منع مصرف قطعی / تداخل خطرناک';
            heading.className = 'text-base font-black text-red-300';
            safetyBadge.textContent = 'خطر بحرانی 🔴';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-red-500/30 text-red-200 border border-red-500/40';
        } else if (overall === 'warning') {
            meterBox.classList.add('bg-orange-950/60', 'border-orange-500', 'text-orange-100');
            iconWrap.className = 'w-14 h-14 rounded-2xl bg-orange-500/25 border border-orange-400 text-orange-300 flex items-center justify-center text-3xl mx-auto shadow-lg';
            iconWrap.innerHTML = '<span class="material-symbols-outlined text-3xl">warning</span>';
            heading.textContent = '⚠️ احتیاط بالینی: نیاز به پایش یا تنظیم دوز';
            heading.className = 'text-base font-black text-orange-300';
            safetyBadge.textContent = 'احتیاط بالا 🟠';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-orange-500/30 text-orange-200 border border-orange-500/40';
        } else if (overall === 'moderate') {
            meterBox.classList.add('bg-amber-950/50', 'border-amber-500', 'text-amber-100');
            iconWrap.className = 'w-14 h-14 rounded-2xl bg-amber-500/20 border border-amber-400 text-amber-300 flex items-center justify-center text-3xl mx-auto shadow-sm';
            iconWrap.innerHTML = '<span class="material-symbols-outlined text-3xl">schedule</span>';
            heading.textContent = 'تداخل متوسط: نیازمند رعایت فاصله زمانی';
            heading.className = 'text-base font-black text-amber-300';
            safetyBadge.textContent = 'تداخل متوسط 🟡';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-amber-500/30 text-amber-200 border border-amber-500/40';
        } else {
            meterBox.classList.add('bg-emerald-950/50', 'border-emerald-500', 'text-emerald-100');
            iconWrap.className = 'w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-400 text-emerald-300 flex items-center justify-center text-3xl mx-auto shadow-sm';
            iconWrap.innerHTML = '<span class="material-symbols-outlined text-3xl">check_circle</span>';
            heading.textContent = 'وضعیت ایمن: تداخل فارماکولوژیک ثبت نشد';
            heading.className = 'text-base font-black text-emerald-300';
            safetyBadge.textContent = 'سازگار و ایمن 🟢';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/30 text-emerald-200 border border-emerald-500/40';
        }

        summaryText.textContent = data.overall_summary || '';

        // Render Individual Interactions Cards
        const itWrapper = document.getElementById('interactionsListWrapper');
        itWrapper.innerHTML = '';
        const interactions = data.interactions_found || [];

        if (interactions.length > 0) {
            interactions.forEach(it => {
                const card = document.createElement('div');
                const isCrit = (it.severity === 'critical');
                card.className = isCrit 
                    ? 'p-3.5 rounded-2xl bg-red-950/40 border border-red-500/40 text-xs space-y-2'
                    : 'p-3.5 rounded-2xl bg-amber-950/30 border border-amber-500/30 text-xs space-y-2';
                card.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="font-black text-white flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-md bg-white/10 font-mono text-[11px]">${escapeHtml(it.drug1)}</span>
                            <span class="text-amber-400">⇄</span>
                            <span class="px-2 py-0.5 rounded-md bg-white/10 font-mono text-[11px]">${escapeHtml(it.drug2)}</span>
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${isCrit ? 'bg-red-500/30 text-red-200' : 'bg-amber-500/30 text-amber-200'}">
                            ${escapeHtml(it.level_fa || (isCrit ? 'خطر شدید' : 'متوسط'))}
                        </span>
                    </div>
                    <div class="font-bold text-[11px] ${isCrit ? 'text-red-300' : 'text-amber-300'}">${escapeHtml(it.title || '')}</div>
                    <p class="text-[11px] text-white/80 leading-relaxed">${escapeHtml(it.mechanism || '')}</p>
                    ${it.clinical_signs ? `<div class="text-[10px] text-white/60"><strong>علائم هشدار:</strong> ${escapeHtml(it.clinical_signs)}</div>` : ''}
                    ${it.recommendation ? `<div class="bg-white/5 p-2 rounded-xl text-[11px] text-emerald-200 border border-white/5"><strong>دستورالعمل:</strong> ${escapeHtml(it.recommendation)}</div>` : ''}
                `;
                itWrapper.appendChild(card);
            });
        }

        // Render Contraindications Cards
        const ctWrapper = document.getElementById('contraindicationsWrapper');
        ctWrapper.innerHTML = '';
        const contra = data.species_contraindications || [];

        if (contra.length > 0) {
            contra.forEach(c => {
                const card = document.createElement('div');
                card.className = 'p-3.5 rounded-2xl bg-red-950/60 border-2 border-red-500 text-xs space-y-2 text-white';
                card.innerHTML = `
                    <div class="flex items-center gap-2 text-red-300 font-black">
                        <span class="material-symbols-outlined text-lg">block</span>
                        <span>${escapeHtml(c.title || 'منع مصرف')}</span>
                    </div>
                    <p class="text-[11px] text-red-100/90 leading-relaxed">${escapeHtml(c.mechanism || '')}</p>
                    ${c.action ? `<div class="bg-red-500/20 p-2 rounded-xl text-[11px] text-red-200 font-bold">اقدام فوری: ${escapeHtml(c.action)}</div>` : ''}
                `;
                ctWrapper.appendChild(card);
            });
        }

        // Time spacing list
        const timeWrap = document.getElementById('timeSpacingWrapper');
        const timeList = document.getElementById('timeSpacingList');
        timeList.innerHTML = '';
        const timings = data.time_spacing_schedule || [];
        if (timings.length > 0) {
            timings.forEach(t => {
                const li = document.createElement('li');
                li.textContent = t;
                timeList.appendChild(li);
            });
            timeWrap.classList.remove('hidden');
        } else {
            timeWrap.classList.add('hidden');
        }

        // Safe combinations list
        const safeWrap = document.getElementById('safeCombinationsWrapper');
        const safeList = document.getElementById('safeCombinationsList');
        safeList.innerHTML = '';
        const safes = data.safe_combinations || [];
        if (safes.length > 0) {
            safes.forEach(s => {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${escapeHtml(s.drug1 || '')} + ${escapeHtml(s.drug2 || '')}:</strong> ${escapeHtml(s.benefit || '')}`;
                safeList.appendChild(li);
            });
            safeWrap.classList.remove('hidden');
        } else {
            safeWrap.classList.add('hidden');
        }

        // Populate Printable view
        populatePrintView(data);

        // Show detailed results area
        resultsArea.classList.remove('hidden');
        resultsArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Populate Printable Hidden Element
    function populatePrintView(data) {
        const serialEl = document.getElementById('printReportSerial');
        const detailsEl = document.getElementById('printPetDetails');
        const drugsEl = document.getElementById('printDrugsList');
        const itSection = document.getElementById('printInteractionsSection');

        if (serialEl) serialEl.textContent = data.report_serial || 'ASENA-INT-001';
        if (detailsEl) {
            const spText = (state.species === 'cat') ? 'گربه' : ((state.species === 'horse') ? 'اسب' : 'سگ');
            detailsEl.textContent = `نام: ${state.petName || 'پت'} | گونه: ${spText} | نژاد: ${state.race || 'عمومی'} | وزن: ${state.weight} کیلوگرم`;
        }
        if (drugsEl) {
            drugsEl.textContent = state.drugs.map(d => d.name).join(' + ');
        }
        if (itSection) {
            itSection.innerHTML = '';
            const allIt = data.interactions_found || [];
            if (allIt.length === 0) {
                itSection.innerHTML = '<div style="color: green; font-weight: bold;">تداخل فارماکولوژیک خطرناکی بین داروهای فوق مشاهده نشد.</div>';
            } else {
                allIt.forEach((it, i) => {
                    const d = document.createElement('div');
                    d.style.marginBottom = '8px';
                    d.style.padding = '8px';
                    d.style.background = '#fff5f5';
                    d.style.border = '1px solid #fed7d7';
                    d.style.borderRadius = '6px';
                    d.innerHTML = `
                        <div style="font-weight: bold; color: #9b2c2c;">${i + 1}. تداخل: ${escapeHtml(it.drug1)} با ${escapeHtml(it.drug2)} (${escapeHtml(it.level_fa || '')})</div>
                        <div style="font-size: 11px; margin-top: 3px;"><strong>مکانیسم:</strong> ${escapeHtml(it.mechanism)}</div>
                        <div style="font-size: 11px; margin-top: 3px; color: #22543d;"><strong>دستورالعمل:</strong> ${escapeHtml(it.recommendation || '')}</div>
                    `;
                    itSection.appendChild(d);
                });
            }
        }
    }

    // Reset results back to initial prompt
    function resetResultsView() {
        const resultsArea = document.getElementById('detailedResultsArea');
        if (resultsArea) resultsArea.classList.add('hidden');
        const safetyBadge = document.getElementById('analysisStatusBadge');
        if (safetyBadge) {
            safetyBadge.textContent = 'در انتظار داروها';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-white/15 text-white/80';
        }
    }

    // Save report to pet health dossier in profile
    window.saveDrugReportToProfile = async function() {
        if (!state.latestReport) return;

        const btn = document.getElementById('btnSaveReport');
        const origText = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span><span>در حال ذخیره...</span>';
        }

        try {
            const csrf = window.ASENA_CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('actions/save_drug_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    csrf_token: csrf,
                    pet_name: state.petName,
                    species: state.species,
                    race: state.race,
                    weight_kg: state.weight,
                    report_serial: state.latestReport.report_serial,
                    overall_safety: state.latestReport.overall_safety,
                    overall_summary: state.latestReport.overall_summary,
                    drugs: state.drugs,
                    interactions: state.latestReport.interactions_found || [],
                    contraindications: state.latestReport.species_contraindications || []
                })
            });

            const data = await res.json();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origText;
            }

            if (data && data.success) {
                alert('✅ کارنامه بررسی تداخلات دارویی با موفقیت در پرونده سلامت پت شما ذخیره گردید.');
            } else if (data && data.require_login) {
                alert('جهت بایگانی کارنامه در پرونده سلامت، لطفاً ابتدا وارد حساب کاربری خود شوید.');
                window.location.href = 'login.php?redirect=' + encodeURIComponent('interactions.php');
            } else {
                alert((data && data.message) || 'خطا در ثبت سند.');
            }
        } catch(err) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origText;
            }
            alert('خطا در برقراری ارتباط با سرور.');
        }
    };

    function escapeHtml(str) {
        if (!str) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(str).replace(/[&<>"']/g, m => map[m]);
    }

})();
</script>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #printableDrugReportArea, #printableDrugReportArea * {
        visibility: visible !important;
    }
    #printableDrugReportArea {
        display: block !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        background: white !important;
        color: black !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
