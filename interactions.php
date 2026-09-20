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
    <div class="max-w-4xl mx-auto bg-white rounded-3xl p-6 sm:p-10 shadow-xl border border-slate-200/80 mb-12 space-y-8">

        <!-- 1. Pet Species Selector -->
        <div class="space-y-2.5">
            <label class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-bold">۱</span>
                <span>گونه حیوان خانگی را مشخص کنید:</span>
            </label>
            <div class="flex flex-wrap sm:flex-nowrap gap-2 sm:gap-3">
                <button type="button" onclick="setDrugSpecies('dog')" id="drugSpeciesDog" class="drug-species-btn flex-1 min-w-[75px] py-2.5 px-2 rounded-2xl font-bold text-xs flex flex-row items-center justify-center gap-1.5 border transition cursor-pointer bg-[#001a48] text-white border-[#001a48] shadow-sm">
                    <span class="text-base sm:text-lg">🐕</span>
                    <span>سگ</span>
                </button>
                <button type="button" onclick="setDrugSpecies('cat')" id="drugSpeciesCat" class="drug-species-btn flex-1 min-w-[75px] py-2.5 px-2 rounded-2xl font-bold text-xs flex flex-row items-center justify-center gap-1.5 border transition cursor-pointer bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200">
                    <span class="text-base sm:text-lg">🐈</span>
                    <span>گربه</span>
                </button>
                <button type="button" onclick="setDrugSpecies('horse')" id="drugSpeciesHorse" class="drug-species-btn flex-1 min-w-[75px] py-2.5 px-2 rounded-2xl font-bold text-xs flex flex-row items-center justify-center gap-1.5 border transition cursor-pointer bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200">
                    <span class="text-base sm:text-lg">🐎</span>
                    <span>اسب</span>
                </button>
                <button type="button" onclick="setDrugSpecies('bird')" id="drugSpeciesBird" class="drug-species-btn flex-1 min-w-[75px] py-2.5 px-2 rounded-2xl font-bold text-xs flex flex-row items-center justify-center gap-1.5 border transition cursor-pointer bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200">
                    <span class="text-base sm:text-lg">🦜</span>
                    <span>پرنده</span>
                </button>
                <button type="button" onclick="setDrugSpecies('exotic')" id="drugSpeciesExotic" class="drug-species-btn flex-1 min-w-[75px] py-2.5 px-2 rounded-2xl font-bold text-xs flex flex-row items-center justify-center gap-1.5 border transition cursor-pointer bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200">
                    <span class="text-base sm:text-lg">🐇</span>
                    <span>اگزوتیک</span>
                </button>
            </div>
        </div>

        <!-- 2. Drug Search & Intake Manager -->
        <div class="space-y-3 pt-2">
            <label class="text-xs sm:text-sm font-bold text-slate-800 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-bold">۲</span>
                    <span>نام داروهای مصرفی همزمان را اضافه کنید:</span>
                </span>
                <span class="text-[11px] text-slate-500 font-normal">حداقل ۲ قلم جهت بررسی تداخل</span>
            </label>

            <!-- Search Input with Autocomplete Dropdown -->
            <div class="relative">
                <div class="flex items-center bg-slate-50 border-2 border-slate-200 rounded-2xl overflow-hidden p-1 focus-within:border-blue-600 focus-within:bg-white focus-within:ring-4 focus-within:ring-blue-100 transition">
                    <span class="material-symbols-outlined text-slate-400 px-3.5 text-2xl">search</span>
                    <input type="text" id="drugSearchInput" 
                           placeholder="نام دارو را جستجو یا تایپ کنید (مثلاً: کارپروفن، پردنیزولون، انروفلوکساسین...)" 
                           autocomplete="off"
                           class="w-full py-3 text-xs sm:text-sm text-slate-800 bg-transparent focus:outline-none placeholder-slate-400">
                    <button type="button" onclick="addCustomDrugFromInput()" class="bg-[#001a48] hover:bg-[#002d72] text-white text-xs sm:text-sm font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-1 shrink-0 cursor-pointer shadow-sm">
                        <span class="material-symbols-outlined text-lg">add</span>
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
                <div class="flex items-center justify-between text-[11px] text-slate-500">
                    <span>داروهای انتخاب‌شده (<span id="drugCountBadge" class="font-bold text-blue-600 font-mono">۰</span> قلم):</span>
                    <button type="button" onclick="clearAllDrugs()" class="text-slate-400 hover:text-red-600 transition text-[11px] cursor-pointer">پاک کردن همه</button>
                </div>
                
                <div id="selectedDrugsContainer" class="min-h-[64px] bg-slate-50 rounded-2xl p-3 sm:p-4 border border-slate-200/80 flex flex-wrap items-center gap-2">
                    <span id="emptyDrugsPrompt" class="text-xs text-slate-400 flex items-center gap-1.5 py-1 px-1">
                        <span class="material-symbols-outlined text-base text-slate-400">info</span>
                        هنوز دارویی اضافه نشده است. نام دارو را جستجو کرده یا مستقیماً تایپ و دکمه افزودن را بزنید.
                    </span>
                </div>
            </div>

            <!-- Subtle Quick Testing Presets -->
            <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500 pt-1">
                <span class="text-slate-400 font-medium">نمونه‌های آزمایشی:</span>
                <button type="button" onclick="loadScenario('nsaid_steroid')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 border border-slate-200 text-slate-600 transition cursor-pointer font-medium">
                    کارپروفن + پردنیزولون
                </button>
                <button type="button" onclick="loadScenario('paracetamol_cat')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 border border-slate-200 text-slate-600 transition cursor-pointer font-medium">
                    استامینوفن در گربه
                </button>
                <button type="button" onclick="loadScenario('antibiotic_sucralfate')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-200 border border-slate-200 text-slate-600 transition cursor-pointer font-medium">
                    انروفلوکساسین + سوکرالفات
                </button>
                <button type="button" onclick="loadScenario('safe_duo')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200 border border-slate-200 text-slate-600 transition cursor-pointer font-medium">
                    آموکسی‌سیلین + پروبیوتیک
                </button>
            </div>
        </div>

        <!-- 3. Progressive Disclosure Accordion: Optional Clinical Details -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/50">
            <button type="button" onclick="togglePetDetailsAccordion()" class="w-full flex items-center justify-between p-3.5 sm:p-4 text-xs sm:text-sm font-bold text-slate-700 hover:bg-slate-100/80 transition cursor-pointer">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-500 text-lg">tune</span>
                    <span>مشخصات تکمیلی، وزن، بیماری‌های زمینه و یادداشت بالینی (اختیاری)</span>
                </span>
                <span id="accordionChevron" class="material-symbols-outlined text-slate-400 text-xl transition-transform duration-200">expand_more</span>
            </button>
            <div id="petDetailsContent" class="hidden p-4 sm:p-6 pt-2 border-t border-slate-200 space-y-4 bg-white">
                
                <!-- Quick Pet Selection (For Logged In Users) -->
                <?php if (!empty($userPets)): ?>
                <div class="space-y-1.5 bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <label class="text-[11px] font-bold text-slate-700 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-amber-500 text-sm">pets</span>
                        <span>انتخاب سریع از میان پت‌های ثبت‌شده شما:</span>
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($userPets as $up): ?>
                        <button type="button" 
                                onclick="selectRegisteredPet(<?= htmlspecialchars(json_encode($up), ENT_QUOTES, 'UTF-8') ?>)"
                                class="registered-pet-btn px-3 py-1.5 rounded-lg bg-white hover:bg-blue-50 hover:text-blue-700 hover:border-blue-300 text-xs font-bold text-slate-700 border border-slate-200 transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                            <span><?= ($up['type'] === 'cat') ? '🐈' : (($up['type'] === 'horse') ? '🐎' : '🐕') ?></span>
                            <span><?= htmlspecialchars($up['name']) ?></span>
                            <span class="text-[10px] text-slate-400 font-mono"><?= $up['weight_kg'] > 0 ? (float)$up['weight_kg'] . ' kg' : '' ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Pet Name, Breed & Weight Fields -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">نام بیمار:</label>
                        <input type="text" id="drugPetName" placeholder="مثال: لئو / بتی" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">نژاد پت:</label>
                        <input type="text" id="drugPetRace" placeholder="مثال: ژرمن شپرد / پرشین" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">وزن بیمار (کیلوگرم):</label>
                        <div class="relative">
                            <input type="number" id="drugPetWeight" value="12" min="0.2" max="120" step="0.5" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-blue-500 text-left dir-ltr pl-8">
                            <span class="absolute left-2.5 top-2 text-[10px] text-slate-400">kg</span>
                        </div>
                    </div>
                </div>

                <!-- Pre-existing Sensitive Conditions -->
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-slate-600 block">بیماری‌های زمینه‌ای یا شرایط حساس بیمار:</label>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="toggleCondition(this, 'renal')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer">
                            نارسایی کلیوی (CKD)
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'hepatic')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer">
                            اختلال یا نارسایی کبدی
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'cardiac')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer">
                            بیماری قلبی / فشار خون
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'epilepsy')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer">
                            صرع و سابقه تشنج
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'ulcer')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer">
                            زخم معده یا گوارشی
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'diabetes')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer">
                            دیابت
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'pregnancy')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer">
                            بارداری یا شیردهی
                        </button>
                        <button type="button" onclick="toggleCondition(this, 'mdr1')" class="condition-chip px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer">
                            جهش ژنی حساسیت MDR1
                        </button>
                    </div>
                </div>

                <!-- Pet Condition & Clinical History Input -->
                <div class="space-y-1.5">
                    <label class="text-[11px] font-bold text-slate-600 block">شرح وضعیت بالینی، علائم، آلرژی یا توضیحات سرپرست (جهت تحلیل هوش مصنوعی):</label>
                    <textarea id="drugUserNotes" rows="2" placeholder="اگر حیوان شما دارای علائم خاصی مثل بی‌اشتهایی، استفراغ، آلرژی به داروی خاص یا سابقه جراحی اخیر است در اینجا بنویسید..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 leading-relaxed"></textarea>
                </div>
            </div>
        </div>

        <!-- 4. Primary Action Button -->
        <div>
            <button type="button" onclick="runDrugInteractionAnalysis()" id="btnRunDrugAnalysis" class="w-full bg-[#001a48] hover:bg-[#002d72] text-white py-4 px-6 rounded-2xl font-black text-sm text-center shadow-lg shadow-blue-950/20 hover:shadow-xl transition-all flex items-center justify-center gap-2.5 cursor-pointer active:scale-98">
                <span class="material-symbols-outlined text-2xl">psychology</span>
                <span>شروع پایش و تحلیل بالینی تداخلات دارویی</span>
            </button>
        </div>

        <!-- 5. Detailed Interactive Results Container (Appears after analysis) -->
        <div id="detailedResultsArea" class="space-y-4 pt-6 border-t-2 border-slate-100 hidden">

            <!-- Verdict Banner / Safety Meter Box -->
            <div id="safetyMeterBox" class="p-5 sm:p-6 rounded-2xl border text-center space-y-2 transition-all">
                <div class="flex items-center justify-between border-b pb-3 mb-2">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
                        <span class="material-symbols-outlined text-blue-600 text-lg">monitor_heart</span>
                        <span>نتیجه ارزیابی بالینی فارماکوکینتیک</span>
                    </div>
                    <span id="analysisStatusBadge" class="text-[10px] font-mono font-bold px-3 py-1 rounded-full">
                    </span>
                </div>
                <div id="safetyIconWrap" class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl mx-auto shadow-xs">
                    <span class="material-symbols-outlined text-3xl">medication_liquid</span>
                </div>
                <h4 id="safetyHeading" class="text-base sm:text-lg font-black"></h4>
                <p id="safetySummaryText" class="text-xs sm:text-sm leading-relaxed max-w-xl mx-auto"></p>
            </div>

            <!-- AI Condition Assessment Callout -->
            <div id="conditionAnalysisWrap" class="hidden"></div>

            <!-- Interactions Accordion List -->
            <div id="interactionsListWrapper" class="space-y-3">
                <!-- Populated dynamically via JS -->
            </div>

            <!-- Species Contraindications Alert -->
            <div id="contraindicationsWrapper" class="space-y-3">
                <!-- Populated dynamically via JS -->
            </div>

            <!-- Time Spacing Guidelines -->
            <div id="timeSpacingWrapper" class="bg-amber-50 border border-amber-200 p-4 sm:p-5 rounded-2xl space-y-2 hidden text-xs">
                <div class="font-bold text-amber-900 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-amber-700">schedule</span>
                    <span>دستورالعمل فاصله زمانی بین داروها:</span>
                </div>
                <ul id="timeSpacingList" class="space-y-1.5 text-[11px] sm:text-xs text-amber-900 list-disc list-inside"></ul>
            </div>

            <!-- Safe Synergies List -->
            <div id="safeCombinationsWrapper" class="bg-emerald-50 border border-emerald-200 p-4 sm:p-5 rounded-2xl space-y-2 hidden text-xs">
                <div class="font-bold text-emerald-900 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base text-emerald-700">verified</span>
                    <span>هم‌پوشانی‌های ایمن و مفید:</span>
                </div>
                <ul id="safeCombinationsList" class="space-y-1.5 text-[11px] sm:text-xs text-emerald-900 list-disc list-inside"></ul>
            </div>

            <!-- Action Buttons: Save to Dossier & Print -->
            <div class="pt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button type="button" onclick="saveDrugReportToProfile()" id="btnSaveReport" class="w-full bg-[#001a48] hover:bg-[#002d72] text-white py-3 px-4 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                    <span class="material-symbols-outlined text-lg">save</span>
                    <span>ثبت در پرونده سلامت پت</span>
                </button>
                <button type="button" onclick="window.print()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 py-3 px-4 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 border border-slate-200 cursor-pointer">
                    <span class="material-symbols-outlined text-lg">print</span>
                    <span>چاپ نسخه بالینی</span>
                </button>
            </div>

            <!-- Direct Bridge to Vet Booking or Pharmacy -->
            <div class="pt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500 border-t border-slate-100">
                <a href="booking.php" class="hover:text-blue-700 transition flex items-center gap-1.5 font-medium">
                    <span class="material-symbols-outlined text-base text-amber-500">videocam</span>
                    <span>مشاوره ویزیت با دامپزشک آنلاین</span>
                </a>
                <a href="pharmacy.php" class="hover:text-blue-700 transition flex items-center gap-1.5 font-medium">
                    <span class="material-symbols-outlined text-base text-blue-600">local_pharmacy</span>
                    <span>داروخانه دامپزشکی آسنا</span>
                </a>
            </div>

        </div>

        <!-- Mandatory Medical Disclaimer Box -->
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-950 text-xs leading-relaxed space-y-1.5">
            <div class="font-bold text-rose-900 flex items-center gap-1.5 text-xs">
                <span class="material-symbols-outlined text-sm text-rose-700">gavel</span>
                <span>سلب مسئولیت پزشکی و هشدار سلامت:</span>
            </div>
            <p class="text-[11px] sm:text-xs text-rose-900/90 leading-relaxed">
                این ابزار صرفاً جنبه محاسبات تغذیه و شاخص بدنی دارد. تجویز هرگونه دارو، قرص ضدانگل، قطره ضدکک یا واکسیناسیون باید منحصراً توسط دکتر دامپزشک پس از معاینه بالینی حضوری انجام پذیرد. مصرف خودسرانه داروهای انسانی برای پتها خطر مسمومیت مرگبار دارد.
            </p>
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
        userNotes: '',
        latestReport: null
    };

    // DOM Elements
    const searchInput = document.getElementById('drugSearchInput');
    const searchResults = document.getElementById('drugSearchResults');
    const selectedContainer = document.getElementById('selectedDrugsContainer');
    const emptyPrompt = document.getElementById('emptyDrugsPrompt');
    const drugCountBadge = document.getElementById('drugCountBadge');

    // Toggle Pet Details Accordion Drawer
    window.togglePetDetailsAccordion = function(forceOpen = false) {
        const content = document.getElementById('petDetailsContent');
        const chevron = document.getElementById('accordionChevron');
        if (!content) return;
        const isClosed = content.classList.contains('hidden');
        if (forceOpen || isClosed) {
            content.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    };

    // Species switcher
    window.setDrugSpecies = function(species) {
        state.species = species;
        document.querySelectorAll('.drug-species-btn').forEach(btn => {
            btn.classList.remove('bg-[#001a48]', 'text-white', 'border-[#001a48]', 'shadow-sm');
            btn.classList.add('bg-slate-100', 'text-slate-700', 'border-slate-200');
        });
        const activeBtn = document.getElementById('drugSpecies' + species.charAt(0).toUpperCase() + species.slice(1));
        if (activeBtn) {
            activeBtn.classList.remove('bg-slate-100', 'text-slate-700', 'border-slate-200');
            activeBtn.classList.add('bg-[#001a48]', 'text-white', 'border-[#001a48]', 'shadow-sm');
        }
    };

    // Pre-existing condition toggle
    window.toggleCondition = function(el, cond) {
        const idx = state.conditions.indexOf(cond);
        if (idx === -1) {
            state.conditions.push(cond);
            el.classList.add('bg-blue-600', 'text-white', 'font-bold', 'border-blue-600', 'shadow-2xs');
            el.classList.remove('bg-slate-100', 'text-slate-700', 'border-slate-200');
        } else {
            state.conditions.splice(idx, 1);
            el.classList.remove('bg-blue-600', 'text-white', 'font-bold', 'border-blue-600', 'shadow-2xs');
            el.classList.add('bg-slate-100', 'text-slate-700', 'border-slate-200');
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

        togglePetDetailsAccordion(true);
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
            chip.className = 'flex items-center gap-2 bg-white text-slate-800 py-1.5 px-3 rounded-xl text-xs font-bold shadow-xs border border-slate-200 animate-in fade-in zoom-in-95 duration-150';
            chip.innerHTML = `
                <span class="material-symbols-outlined text-blue-600 text-sm">medication</span>
                <span>${escapeHtml(d.name)}</span>
                ${d.category ? `<span class="text-[9px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">${escapeHtml(d.category)}</span>` : ''}
                <button type="button" onclick="removeDrug(${idx})" class="w-4 h-4 rounded-full bg-slate-100 hover:bg-red-500 hover:text-white text-slate-500 flex items-center justify-center text-[10px] transition cursor-pointer">✕</button>
            `;
            selectedContainer.appendChild(chip);
        });
    }

    // Quick Preset Scenarios
    window.loadScenario = function(type) {
        state.drugs = [];
        togglePetDetailsAccordion(true);
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
        const notesInp = document.getElementById('drugUserNotes');

        state.petName = nameInp ? nameInp.value.trim() : '';
        state.race = raceInp ? raceInp.value.trim() : '';
        state.weight = weightInp ? parseFloat(weightInp.value) || 12 : 12;
        state.userNotes = notesInp ? notesInp.value.trim() : '';

        const btn = document.getElementById('btnRunDrugAnalysis');
        const origContent = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined text-lg animate-spin">sync</span><span>در حال تطبیق فارماکوپیا و اسکن بالینی...</span>';
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
                    user_notes: state.userNotes,
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
        meterBox.className = 'p-5 sm:p-6 rounded-2xl border text-center space-y-2 transition-all';

        if (overall === 'critical') {
            meterBox.classList.add('bg-rose-50', 'border-rose-300', 'text-rose-950');
            iconWrap.className = 'w-14 h-14 rounded-2xl bg-rose-100 border border-rose-300 text-rose-600 flex items-center justify-center text-3xl mx-auto shadow-xs';
            iconWrap.innerHTML = '<span class="material-symbols-outlined text-3xl">dangerous</span>';
            heading.textContent = '🚨 هشدار بحرانی: منع مصرف قطعی / تداخل خطرناک';
            heading.className = 'text-base sm:text-lg font-black text-rose-900';
            summaryText.className = 'text-xs sm:text-sm text-rose-900/90 leading-relaxed max-w-xl mx-auto';
            safetyBadge.textContent = 'خطر بحرانی 🔴';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-3 py-1 rounded-full bg-rose-100 text-rose-800 border border-rose-300';
        } else if (overall === 'warning') {
            meterBox.classList.add('bg-amber-50', 'border-amber-300', 'text-amber-950');
            iconWrap.className = 'w-14 h-14 rounded-2xl bg-amber-100 border border-amber-300 text-amber-700 flex items-center justify-center text-3xl mx-auto shadow-xs';
            iconWrap.innerHTML = '<span class="material-symbols-outlined text-3xl">warning</span>';
            heading.textContent = '⚠️ احتیاط بالینی: نیاز به پایش یا تنظیم دوز';
            heading.className = 'text-base sm:text-lg font-black text-amber-900';
            summaryText.className = 'text-xs sm:text-sm text-amber-900/90 leading-relaxed max-w-xl mx-auto';
            safetyBadge.textContent = 'احتیاط بالا 🟠';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-3 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-300';
        } else if (overall === 'moderate') {
            meterBox.classList.add('bg-yellow-50', 'border-yellow-300', 'text-yellow-950');
            iconWrap.className = 'w-14 h-14 rounded-2xl bg-yellow-100 border border-yellow-300 text-yellow-800 flex items-center justify-center text-3xl mx-auto shadow-xs';
            iconWrap.innerHTML = '<span class="material-symbols-outlined text-3xl">schedule</span>';
            heading.textContent = 'تداخل متوسط: نیازمند رعایت فاصله زمانی';
            heading.className = 'text-base sm:text-lg font-black text-yellow-900';
            summaryText.className = 'text-xs sm:text-sm text-yellow-900/90 leading-relaxed max-w-xl mx-auto';
            safetyBadge.textContent = 'تداخل متوسط 🟡';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 border border-yellow-300';
        } else {
            meterBox.classList.add('bg-emerald-50', 'border-emerald-300', 'text-emerald-950');
            iconWrap.className = 'w-14 h-14 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-700 flex items-center justify-center text-3xl mx-auto shadow-xs';
            iconWrap.innerHTML = '<span class="material-symbols-outlined text-3xl">check_circle</span>';
            heading.textContent = 'وضعیت ایمن: تداخل دارویی خطرناکی ثبت نشد';
            heading.className = 'text-base sm:text-lg font-black text-emerald-900';
            summaryText.className = 'text-xs sm:text-sm text-emerald-900/90 leading-relaxed max-w-xl mx-auto';
            safetyBadge.textContent = 'سازگار و ایمن 🟢';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300';
        }

        summaryText.textContent = data.overall_summary || '';

        // AI Condition Analysis Callout
        const condWrap = document.getElementById('conditionAnalysisWrap');
        if (condWrap) {
            if (data.condition_analysis) {
                condWrap.innerHTML = `
                    <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 text-xs space-y-1.5 text-slate-800">
                        <div class="flex items-center gap-1.5 text-blue-800 font-bold text-xs">
                            <span class="material-symbols-outlined text-base text-blue-600">psychology</span>
                            <span>تحلیل اختصاصی هوش مصنوعی بالینی بر اساس وضعیت پت:</span>
                        </div>
                        <p class="text-[11px] sm:text-xs text-slate-700 leading-relaxed">${escapeHtml(data.condition_analysis)}</p>
                    </div>
                `;
                condWrap.classList.remove('hidden');
            } else {
                condWrap.innerHTML = '';
                condWrap.classList.add('hidden');
            }
        }

        // Render Individual Interactions Cards
        const itWrapper = document.getElementById('interactionsListWrapper');
        itWrapper.innerHTML = '';
        const interactions = data.interactions_found || [];

        if (interactions.length > 0) {
            interactions.forEach(it => {
                const card = document.createElement('div');
                const isCrit = (it.severity === 'critical');
                card.className = isCrit 
                    ? 'p-4 sm:p-5 rounded-2xl bg-rose-50/70 border border-rose-200 text-xs space-y-2 text-slate-800'
                    : 'p-4 sm:p-5 rounded-2xl bg-amber-50/70 border border-amber-200 text-xs space-y-2 text-slate-800';
                card.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 font-mono text-[11px] text-slate-700">${escapeHtml(it.drug1)}</span>
                            <span class="text-amber-500 font-bold">⇄</span>
                            <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 font-mono text-[11px] text-slate-700">${escapeHtml(it.drug2)}</span>
                        </span>
                        <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full ${isCrit ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-amber-100 text-amber-800 border border-amber-200'}">
                            ${escapeHtml(it.level_fa || (isCrit ? 'خطر شدید' : 'متوسط'))}
                        </span>
                    </div>
                    <div class="font-bold text-xs sm:text-sm ${isCrit ? 'text-rose-900' : 'text-amber-900'}">${escapeHtml(it.title || '')}</div>
                    <p class="text-[11px] sm:text-xs text-slate-600 leading-relaxed">${escapeHtml(it.mechanism || '')}</p>
                    ${it.clinical_signs ? `<div class="text-[10px] sm:text-[11px] text-slate-500"><strong>علائم هشدار:</strong> ${escapeHtml(it.clinical_signs)}</div>` : ''}
                    ${it.recommendation ? `<div class="bg-white p-3 rounded-xl text-[11px] sm:text-xs text-emerald-800 border border-emerald-200"><strong>دستورالعمل بالینی:</strong> ${escapeHtml(it.recommendation)}</div>` : ''}
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
                card.className = 'p-4 sm:p-5 rounded-2xl bg-rose-100/80 border-2 border-rose-300 text-xs space-y-2 text-rose-950';
                card.innerHTML = `
                    <div class="flex items-center gap-2 text-rose-900 font-black text-xs sm:text-sm">
                        <span class="material-symbols-outlined text-lg text-rose-600">block</span>
                        <span>${escapeHtml(c.title || 'منع مصرف گونه‌ای')}</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-rose-900/90 leading-relaxed">${escapeHtml(c.mechanism || '')}</p>
                    ${c.action ? `<div class="bg-white/80 p-2.5 rounded-xl text-[11px] sm:text-xs text-rose-800 font-bold border border-rose-200">اقدام فوری: ${escapeHtml(c.action)}</div>` : ''}
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
        resultsArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
        const condWrap = document.getElementById('conditionAnalysisWrap');
        if (condWrap) {
            condWrap.innerHTML = '';
            condWrap.classList.add('hidden');
        }
        const safetyBadge = document.getElementById('analysisStatusBadge');
        if (safetyBadge) {
            safetyBadge.textContent = 'در انتظار داروها';
            safetyBadge.className = 'text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200';
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
                    user_notes: state.userNotes,
                    condition_analysis: state.latestReport.condition_analysis || '',
                    report_serial: state.latestReport.report_serial,
                    overall_safety: state.latestReport.overall_safety,
                    overall_summary: state.latestReport.overall_summary,
                    drugs: state.drugs,
                    interactions: state.latestReport.interactions_found || [],
                    contraindications: state.latestReport.species_contraindications || [],
                    safe_combinations: state.latestReport.safe_combinations || [],
                    time_spacing_schedule: state.latestReport.time_spacing_schedule || [],
                    vet_recommendations: state.latestReport.vet_recommendations || []
                })
            });

            const data = await res.json();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origText;
            }

            if (data && data.success) {
                const viewUrl = data.view_url || 'profile.php?tab=pets#pets';
                if (confirm('✅ کارنامه بررسی تداخلات دارویی با موفقیت در پرونده سلامت پت شما ذخیره گردید.\n\nآیا مایلید هم‌اکنون کارنامه رسمی بالینی را مشاهده فرمایید؟')) {
                    window.open(viewUrl, '_blank');
                }
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
