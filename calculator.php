<?php
$page_title = "محاسبه‌گر هوشمند کالری و رژیم غذایی پت | آسنا";
$page_description = "محاسبه دقیق کالری روزانه (MER)، مقدار گرم غذای خشک و آب مورد نیاز سگ و گربه بر اساس استانداردهای بین‌المللی دامپزشکی FEDIAF و WSAVA در آسنا.";
require_once 'includes/header.php';
?>

<main class="max-w-container-max mx-auto overflow-hidden py-8 px-margin-desktop min-h-[80vh]">

    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-on-surface-variant mb-6">
        <a href="index.php" class="hover:text-primary transition-colors">خانه</a>
        <span>></span>
        <span class="text-on-surface-variant">ابزارهای هوشمند سلامت</span>
        <span>></span>
        <span class="text-primary font-bold">محاسبه‌گر کالری و رژیم غذایی پت</span>
    </div>

    <!-- Hero / Title Header -->
    <div class="text-center max-w-3xl mx-auto mb-10 space-y-3">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold shadow-2xs">
            <span class="material-symbols-outlined text-sm text-emerald-600">calculate</span>
            <span>استاندارد بین‌المللی دامپزشکی FEDIAF & WSAVA</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-800 tracking-tight">
            محاسبه‌گر هوشمند کالری و مقدار غذای روزانه پت
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal">
            مشخصات حیوان خانگی خود را وارد کنید تا انرژی روزانه (MER)، گرم غذای خشک دقیق، حجم آب مصرفی و بهترین فرمول تغذیه‌ای را به رایگان و در لحظه دریافت کنید.
        </p>
    </div>

    <!-- Main Calculator Component -->
    <div class="bg-gradient-to-br from-[#001a48] via-[#002d72] to-slate-900 rounded-[2.5rem] p-6 sm:p-10 lg:p-12 text-white shadow-2xl relative overflow-hidden border border-white/10 mb-12">
        <!-- Background Decorative Glows -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#fd8100]/15 rounded-full blur-3xl pointer-events-none"></div>

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
                        <button type="button" onclick="setCalcSpecies('dog')" id="calcBtnDog" class="calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/25 cursor-pointer">
                            <span class="text-xl">🐕</span>
                            <span>سگ (Canine)</span>
                        </button>
                        <button type="button" onclick="setCalcSpecies('cat')" id="calcBtnCat" class="calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15 cursor-pointer">
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
                    <input type="range" id="calcWeightSlider" aria-label="تعیین وزن حیوان خانگی بر حسب کیلوگرم" min="0.5" max="60" step="0.5" value="8.5" oninput="updateWeightFromSlider(this.value)" class="w-full accent-emerald-400 cursor-pointer h-2 bg-white/20 rounded-lg">
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
                        <button type="button" onclick="setCalcStage('puppy')" id="stageBtnPuppy" class="calc-stage-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs font-bold text-center transition-all cursor-pointer">
                            <div class="text-sm mb-0.5">🍼</div>
                            <div class="font-black" id="labelPuppy">توله / رشد</div>
                            <div class="text-[10px] text-white/60">زیر ۱ سال</div>
                        </button>
                        <button type="button" onclick="setCalcStage('adult')" id="stageBtnAdult" class="calc-stage-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs font-bold text-center transition-all shadow-md cursor-pointer">
                            <div class="text-sm mb-0.5">⭐</div>
                            <div class="font-black">بالغ</div>
                            <div class="text-[10px] text-white/60">۱ تا ۷ سال</div>
                        </button>
                        <button type="button" onclick="setCalcStage('senior')" id="stageBtnSenior" class="calc-stage-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs font-bold text-center transition-all cursor-pointer">
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
                        وضعیت تحرک و فعالیت روزانه:
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <button type="button" onclick="setCalcActivity('neutered')" id="actBtnNeutered" class="calc-act-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs font-bold text-right transition-all flex items-center gap-2.5 cursor-pointer shadow-md">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            <div>
                                <div class="font-black">عقیم‌شده / معمول</div>
                                <div class="text-[10px] text-white/60">تحرک متوسط آپارتمانی</div>
                            </div>
                        </button>
                        <button type="button" onclick="setCalcActivity('active')" id="actBtnActive" class="calc-act-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-white/80 text-xs font-bold text-right transition-all flex items-center gap-2.5 cursor-pointer">
                            <span class="material-symbols-outlined text-base opacity-70">directions_run</span>
                            <div>
                                <div class="font-black">بسیار پرتحرک</div>
                                <div class="text-[10px] text-white/60">فعالیت و بازی مداوم</div>
                            </div>
                        </button>
                        <button type="button" onclick="setCalcActivity('diet')" id="actBtnDiet" class="calc-act-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-white/80 text-xs font-bold text-right transition-all flex items-center gap-2.5 cursor-pointer">
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
                            فرمول FEDIAF
                        </span>
                    </div>

                    <!-- 4 Metric Widgets -->
                    <div class="grid grid-cols-2 gap-3.5">
                        
                        <div class="bg-white/10 p-4 rounded-2xl border border-white/10">
                            <div class="text-[11px] text-white/70 font-medium mb-1">کالری روزانه (MER):</div>
                            <div class="text-xl sm:text-2xl font-black font-mono text-amber-300" id="resCalories">
                                ۵۴۰ <span class="text-xs font-normal text-white/80 font-sans">کیلوکالری</span>
                            </div>
                            <div class="text-[10px] text-white/50 mt-1">انرژی متابولیک پایه</div>
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
                            <div class="text-[10px] text-white/50 mt-1">شرب تصفیه‌شده</div>
                        </div>

                        <div class="bg-white/10 p-4 rounded-2xl border border-white/10">
                            <div class="text-[11px] text-white/70 font-medium mb-1">صرفه‌جویی اشتراک:</div>
                            <div class="text-xl sm:text-2xl font-black font-mono text-rose-300">
                                ۱۵٪ <span class="text-xs font-normal text-white/80 font-sans">تخفیف خودکار</span>
                            </div>
                            <div class="text-[10px] text-white/50 mt-1">تحویل دوره‌ای Autoship</div>
                        </div>

                    </div>

                    <!-- Recommended Kibble Match Box -->
                    <div class="bg-gradient-to-r from-emerald-900/40 to-primary/40 p-4 rounded-2xl border border-emerald-400/30 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-2xl shrink-0">
                            🥘
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-black text-white" id="resRecommendedFood">غذای خشک رویال کنین مینی ادالت (ویژه نژاد کوچک)</div>
                            <div class="text-[10px] text-white/70 mt-0.5">فرمول متناسب با متابولیسم و وزن انتخابی پت شما</div>
                        </div>
                    </div>

                </div>

                <!-- CTA Actions -->
                <div class="pt-6 mt-6 border-t border-white/10 flex flex-col sm:flex-row gap-3">
                    <a href="shop.php" id="calcCtaShop" class="flex-1 bg-emerald-400 hover:bg-emerald-300 text-slate-900 py-3.5 px-4 rounded-xl font-black text-xs sm:text-sm text-center shadow-lg shadow-emerald-400/20 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">shopping_cart</span>
                        <span>خرید غذای متناسب از پت‌شاپ</span>
                    </a>
                    <a href="subscriptions.php" class="bg-white/15 hover:bg-white/25 text-white py-3.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-center transition-all flex items-center justify-center gap-1.5 border border-white/20">
                        <span class="material-symbols-outlined text-lg">autorenew</span>
                        <span>اشتراک ماهانه</span>
                    </a>
                </div>

            </div>

        </div>
    </div>

    <!-- Educational & Scientific Guide (FEDIAF Standards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-50 text-[#fd8100] flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">speed</span>
            </div>
            <h3 class="font-bold text-sm text-slate-800">فرمول متابولیسم پایه (RER) چیست؟</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                انرژی متابولیک استراحت (RER) بر اساس فرمول استاندارد جهانی <span class="font-mono font-bold">70 × (Weight)<sup>0.75</sup></span> محاسبه می‌شود که بیانگر حداقل کالری لازم برای تنفس، گردش خون و فعالیت ارگان‌های داخلی است.
            </p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">vital_signs</span>
            </div>
            <h3 class="font-bold text-sm text-slate-800">تأثیر عقیم‌سازی بر کالری دریافتی</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                پس از جراحی عقیم‌سازی، متابولیسم پایه پت بین ۲۰ تا ۲۵ درصد افت می‌کند. در صورت عدم اصلاح جیره غذایی و کالری روزانه، ریسک چاقی مفرط و دیابت افزایش می‌یابد.
            </p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-3">
            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">water_drop</span>
            </div>
            <h3 class="font-bold text-sm text-slate-800">اهمیت آب تازه در تغذیه خشک</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                به ازای هر گرم غذای خشک، پت حداقل به ۲.۵ تا ۳ میلی‌لیتر آب تازه نیاز دارد. سگ‌ها به طور میانگین ۶۰ و گربه‌ها ۵۰ میلی‌لیتر آب به ازای هر کیلوگرم وزن خود مصرف می‌کنند.
            </p>
        </div>
    </div>

    <!-- Cross Promotion Hub (Related Tools) -->
    <div class="bg-slate-50 p-6 sm:p-8 rounded-3xl border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6 mb-12">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-[#001a48] text-[#fd8100] flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-symbols-outlined text-2xl">medication</span>
            </div>
            <div>
                <h3 class="font-bold text-sm sm:text-base text-slate-800">آیا پت شما همزمان دارو مصرف می‌کند؟</h3>
                <p class="text-xs text-slate-500 mt-1">تداخلات دارویی و غذایی را با ابزار آنلاین بررسی کنید تا از سلامت کامل حیوان خانگی‌تان اطمینان حاصل شود.</p>
            </div>
        </div>
        <a href="interactions.php" class="px-5 py-3 rounded-2xl bg-[#001a48] hover:bg-[#002d72] text-white font-bold text-xs flex items-center gap-2 transition shadow-sm whitespace-nowrap cursor-pointer">
            <span>بررسی تداخلات دارویی</span>
            <span class="material-symbols-outlined text-sm">arrow_back</span>
        </a>
    </div>

</main>

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
            btnDog.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/25 cursor-pointer';
            btnCat.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15 cursor-pointer';
            labelPuppy.textContent = 'توله سگ (زیر ۱ سال)';
            if (calcState.weight > 60) calcState.weight = 60;
        } else {
            btnCat.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/25 cursor-pointer';
            btnDog.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15 cursor-pointer';
            labelPuppy.textContent = 'بچه گربه (زیر ۱ سال)';
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
                btn.className = 'calc-stage-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs font-bold text-center transition-all shadow-md cursor-pointer';
            } else {
                btn.className = 'calc-stage-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs font-bold text-center transition-all text-white/80 cursor-pointer';
            }
        });
        recalculateNutrition();
    };

    window.setCalcActivity = function(act) {
        calcState.activity = act;
        ['neutered', 'active', 'diet'].forEach(a => {
            const btn = document.getElementById('actBtn' + a.charAt(0).toUpperCase() + a.slice(1));
            if (a === act) {
                btn.className = 'calc-act-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs font-bold text-right transition-all flex items-center gap-2.5 shadow-md cursor-pointer';
            } else {
                btn.className = 'calc-act-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-white/80 text-xs font-bold text-right transition-all flex items-center gap-2.5 cursor-pointer';
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

        // Water requirement (ml)
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

<?php require_once 'includes/footer.php'; ?>
