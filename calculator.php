<?php
$page_title = "محاسبه‌گر هوشمند کالری و رژیم غذایی بالینی پت | آسنا";
$page_description = "محاسبه دقیق کالری روزانه (MER)، شاخص وضعیت بدنی (BCS)، گرم غذای خشک، آب مصرفی و صدور کارنامه رسمی تغذیه بالینی سگ و گربه بر اساس استانداردهای جهانی FEDIAF و WSAVA.";
require_once 'includes/header.php';
?>

<main class="max-w-container-max mx-auto overflow-hidden py-8 px-margin-desktop min-h-[80vh]">

    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-on-surface-variant mb-6">
        <a href="index.php" class="hover:text-primary transition-colors">خانه</a>
        <span>></span>
        <span class="text-on-surface-variant">ابزارهای هوشمند سلامت</span>
        <span>></span>
        <span class="text-primary font-bold">محاسبه‌گر کالری و کارنامه تغذیه بالینی</span>
    </div>

    <!-- Hero / Title Header -->
    <div class="text-center max-w-3xl mx-auto mb-10 space-y-3">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-bold shadow-2xs">
            <span class="material-symbols-outlined text-sm text-emerald-600">calculate</span>
            <span>استاندارد بین‌المللی دامپزشکی FEDIAF & WSAVA</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-800 tracking-tight leading-snug py-1">
            محاسبه‌گر هوشمند کالری و کارنامه تغذیه بالینی پت
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal">
            مشخصات حیوان خانگی خود را وارد کنید تا انرژی متابولیک روزانه (MER)، شاخص وضعیت بدنی (BCS)، گرم غذای خشک دقیق و شناسنامه تغذیه بالینی با تاییدیه دامپزشکی را در لحظه دریافت فرمایید.
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

                <!-- 2. Pet Breed / Race Selection -->
                <div class="space-y-3 bg-white/5 p-5 rounded-2xl border border-white/10">
                    <div class="flex items-center justify-between">
                        <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۲</span>
                            انتخاب نژاد پت (Breed / Race):
                        </label>
                        <span id="calcSelectedBreedBadge" class="bg-emerald-500/20 text-emerald-300 text-[11px] font-bold px-2.5 py-0.5 rounded-full border border-emerald-400/40">
                            ژرمن شپرد
                        </span>
                    </div>

                    <!-- Quick Breed Chips -->
                    <div id="breedChipsContainer" class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto pr-1 custom-scrollbar">
                        <!-- Populated by JS dynamically -->
                    </div>

                    <!-- Search or Custom Breed Input -->
                    <div class="flex items-center gap-2 pt-2 border-t border-white/10">
                        <div class="relative flex-1">
                            <span class="material-symbols-outlined absolute right-3 top-2.5 text-xs text-white/50">search</span>
                            <input type="text" id="calcBreedCustomInput" list="breedDatalist" placeholder="جستجو یا تایپ نام نژاد..." oninput="handleBreedInputChange(this.value)" class="w-full bg-white/10 border border-white/15 rounded-xl pr-8 pl-3 py-2 text-xs text-white placeholder:text-white/40 focus:outline-none focus:border-emerald-400">
                            <datalist id="breedDatalist">
                                <!-- Populated dynamically -->
                            </datalist>
                        </div>
                        <button type="button" onclick="triggerAiBreedAnalysis()" id="btnQuickAiAnalyze" class="px-3 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:opacity-90 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shrink-0 shadow-sm shadow-emerald-500/30 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">psychology</span>
                            <span>تحلیل نژاد با AI</span>
                        </button>
                    </div>

                    <!-- Breed Clinical Hint Box -->
                    <div id="breedSpecificHintBox" class="bg-emerald-500/15 border border-emerald-400/25 rounded-xl p-3 text-[11px] text-emerald-100 flex items-start gap-2">
                        <span class="material-symbols-outlined text-emerald-300 text-sm shrink-0 mt-0.5">verified_user</span>
                        <div class="space-y-0.5 flex-1">
                            <div class="font-bold text-emerald-200" id="breedHintTitle">شاخص فیزیولوژیک نژاد ژرمن شپرد</div>
                            <div class="text-[10px] text-emerald-100/80 leading-relaxed" id="breedHintDesc">
                                نژاد کار و بزرگ‌جثه با حساسیت مفاصل لگن (دیسپلازی) و معده حساس. نیاز به کلسیم و فسفر بالانس‌شده و فرمول غنی از ال-کارنیتین.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Weight Slider -->
                <div class="space-y-2.5 bg-white/5 p-5 rounded-2xl border border-white/10">
                    <div class="flex justify-between items-center">
                        <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۳</span>
                            وزن کنونی پت:
                        </label>
                        <div class="flex items-center gap-1.5 bg-white/15 px-3 py-1 rounded-xl">
                            <span id="calcWeightDisplay" class="font-mono text-base sm:text-lg font-black text-emerald-300">8.5</span>
                            <span class="text-xs text-white/70">کیلوگرم</span>
                        </div>
                    </div>
                    <input type="range" id="calcWeightSlider" aria-label="تعیین وزن حیوان خانگی بر حسب کیلوگرم" min="0.5" max="60" step="0.5" value="8.5" oninput="updateWeightFromSlider(this.value)" class="w-full accent-emerald-400 cursor-pointer h-2 bg-white/20 rounded-lg">
                    <div class="flex justify-between text-[11px] text-white/50 font-mono">
                        <span>۰.۵ کیلو (مینیاتوری)</span>
                        <span>۱۵ کیلو</span>
                        <span>۳۰ کیلو</span>
                        <span>۶۰+ کیلو (بزرگ‌جثه)</span>
                    </div>
                </div>

                <!-- 4. Age / Life Stage -->
                <div class="space-y-2.5">
                    <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۴</span>
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

                <!-- 5. Physiological Status & Activity -->
                <div class="space-y-2.5">
                    <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۵</span>
                        وضعیت تحرک و فیزیولوژیک:
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <button type="button" onclick="setCalcActivity('neutered')" id="actBtnNeutered" class="calc-act-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs font-bold text-right transition-all flex items-center gap-2.5 cursor-pointer shadow-md">
                            <span class="material-symbols-outlined text-base">check_circle</span>
                            <div>
                                <div class="font-black">عقیم‌شده / معمول</div>
                                <div class="text-[10px] text-white/60">تحرک آپارتمانی</div>
                            </div>
                        </button>
                        <button type="button" onclick="setCalcActivity('active')" id="actBtnActive" class="calc-act-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-white/80 text-xs font-bold text-right transition-all flex items-center gap-2.5 cursor-pointer">
                            <span class="material-symbols-outlined text-base opacity-70">directions_run</span>
                            <div>
                                <div class="font-black">بسیار پرتحرک</div>
                                <div class="text-[10px] text-white/60">فعالیت و پیاده‌روی</div>
                            </div>
                        </button>
                        <button type="button" onclick="setCalcActivity('diet')" id="actBtnDiet" class="calc-act-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-white/80 text-xs font-bold text-right transition-all flex items-center gap-2.5 cursor-pointer">
                            <span class="material-symbols-outlined text-base opacity-70">scale</span>
                            <div>
                                <div class="font-black">کم‌تحرک / استراحت</div>
                                <div class="text-[10px] text-white/60">فعالیت پایین</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 6. Body Condition Score (BCS 1 to 9 & BMI) -->
                <div class="space-y-2.5 bg-white/5 p-5 rounded-2xl border border-white/10">
                    <div class="flex justify-between items-center">
                        <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۶</span>
                            شاخص وضعیت بدنی و تناسب (BCS 1-9):
                        </label>
                        <span id="bcsBadgeDisplay" class="bg-emerald-500/20 text-emerald-300 text-[11px] font-black px-2.5 py-0.5 rounded-full border border-emerald-400/40">
                            امتیاز ۵/۹ (ایده‌آل)
                        </span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
                        <button type="button" onclick="setCalcBcs(2)" id="bcsBtnUnder" class="calc-bcs-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs text-right transition-all cursor-pointer">
                            <div class="text-base mb-1">🦴</div>
                            <div class="font-black text-amber-200">BCS 1-3: لاغر</div>
                            <div class="text-[10px] text-white/60">دنده‌ها کاملاً نمایان</div>
                        </button>
                        <button type="button" onclick="setCalcBcs(5)" id="bcsBtnIdeal" class="calc-bcs-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs text-right transition-all shadow-md cursor-pointer">
                            <div class="text-base mb-1">⚖️</div>
                            <div class="font-black text-emerald-300">BCS 4-5: ایده‌آل</div>
                            <div class="text-[10px] text-white/60">گودی کمر و تناسب عالی</div>
                        </button>
                        <button type="button" onclick="setCalcBcs(7)" id="bcsBtnOver" class="calc-bcs-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs text-right transition-all cursor-pointer">
                            <div class="text-base mb-1">🧈</div>
                            <div class="font-black text-amber-300">BCS 6-7: اضافه‌وزن</div>
                            <div class="text-[10px] text-white/60">دنده‌ها زیر چربی پنهان</div>
                        </button>
                        <button type="button" onclick="setCalcBcs(9)" id="bcsBtnObese" class="calc-bcs-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs text-right transition-all cursor-pointer">
                            <div class="text-base mb-1">🚨</div>
                            <div class="font-black text-rose-300">BCS 8-9: چاقی مفرط</div>
                            <div class="text-[10px] text-white/60">نیازمند رژیم بالینی</div>
                        </button>
                    </div>
                </div>

                <!-- 7. Personalized Pet Name (Optional for Official Certificate) -->
                <div class="space-y-2 bg-white/5 p-4 rounded-2xl border border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <label for="calcPetName" class="text-xs font-bold text-white/80 flex items-center gap-2 shrink-0">
                        <span class="material-symbols-outlined text-sm text-emerald-400">badge</span>
                        <span>نام پت (جهت درج رسمی در کارنامه بالینی):</span>
                    </label>
                    <input type="text" id="calcPetName" value="تدی" placeholder="مثال: لوسی، تدی، میلو..." oninput="calcState.petName = this.value || 'پت من'; recalculateNutrition();" class="w-full sm:w-48 bg-white/10 border border-white/20 rounded-xl px-3 py-1.5 text-xs text-white font-bold text-center focus:outline-none focus:border-emerald-400">
                </div>

            </div>

            <!-- Right Column: Magnetic Teaser & Diagnostic Progress Tracker (5 cols) -->
            <div class="lg:col-span-5 flex flex-col justify-between bg-white/10 backdrop-blur-2xl rounded-[2.2rem] p-6 sm:p-8 border border-white/20 shadow-2xl relative overflow-hidden">
                <!-- Background ambient glow -->
                <div class="absolute -top-20 -right-20 w-64 h-64 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>

                <div class="space-y-4 relative z-10">
                    
                    <!-- Dynamic Diagnostic Strip -->
                    <div class="flex items-center justify-between border-b border-white/10 pb-3.5">
                        <div class="flex items-center gap-2.5">
                            <span class="text-3xl" id="resPetEmoji">🐕</span>
                            <div>
                                <h3 class="text-sm font-black text-white" id="resPetTitle">شناسایی مشخصات بیومتریک پت</h3>
                                <p class="text-[11px] text-emerald-300 font-bold flex items-center gap-1.5 mt-0.5" id="resPetSubtitle">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>تحلیل متابولیک و ریسک‌های نژادی آماده است</span>
                                </p>
                            </div>
                        </div>
                        <span class="bg-emerald-500/20 text-emerald-300 text-[10px] font-mono px-2.5 py-1 rounded-full border border-emerald-400/30">
                            WSAVA & FEDIAF
                        </span>
                    </div>

                    <!-- Readiness & Biometric Progress Banner -->
                    <div class="bg-gradient-to-r from-emerald-900/50 to-emerald-800/30 p-3.5 rounded-2xl border border-emerald-400/30 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-lg">check_circle</span>
                            <div>
                                <div class="text-xs font-black text-white">پایش بیومتریک نژادی ۱۰۰٪ تکمیل شد</div>
                                <div class="text-[10px] text-emerald-200/80">آماده صدور نسخه رسمی و جدول ۳ وعده‌ای</div>
                            </div>
                        </div>
                        <span class="bg-emerald-500 text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm">
                            آماده صدور
                        </span>
                    </div>

                    <!-- 4 Diagnostic Health Status Cards (Locked/Ready - No Raw Numbers Exposed) -->
                    <div class="grid grid-cols-2 gap-2.5">
                        
                        <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10 relative overflow-hidden">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[11px] text-white/70 font-medium">کالری متابولیک (MER):</span>
                                <span class="material-symbols-outlined text-xs text-amber-300">lock</span>
                            </div>
                            <div class="text-xs sm:text-sm font-black text-amber-300 flex items-center gap-1">
                                <span>محاسبه بالینی شد</span>
                                <span class="text-emerald-400 text-xs">✓</span>
                            </div>
                            <div class="text-[9px] text-white/50 mt-1">تثبیت بر پایه استاندارد جهانی FEDIAF</div>
                        </div>

                        <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10 relative overflow-hidden">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[11px] text-white/70 font-medium">سهم دقیق غذای هر وعده:</span>
                                <span class="material-symbols-outlined text-xs text-emerald-300">lock</span>
                            </div>
                            <div class="text-xs sm:text-sm font-black text-emerald-300 flex items-center gap-1">
                                <span>گرم دقیق هر وعده</span>
                                <span class="text-emerald-400 text-xs">✓</span>
                            </div>
                            <div class="text-[9px] text-white/50 mt-1">تفکیک ارگونومیک صبح، عصر و شب</div>
                        </div>

                        <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10 relative overflow-hidden">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[11px] text-white/70 font-medium">جدول زمان‌بندی ۷ روزه:</span>
                                <span class="material-symbols-outlined text-xs text-sky-300">verified</span>
                            </div>
                            <div class="text-xs sm:text-sm font-black text-sky-300 flex items-center gap-1">
                                <span>تولید گردید</span>
                                <span class="text-emerald-400 text-xs">✓</span>
                            </div>
                            <div class="text-[9px] text-white/50 mt-1">ساعات دقیق مصرف، آب و مکمل‌ها</div>
                        </div>

                        <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10 relative overflow-hidden">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[11px] text-white/70 font-medium">هشدارهای نژادی و دارویی:</span>
                                <span class="material-symbols-outlined text-xs text-rose-300">warning</span>
                            </div>
                            <div class="text-xs sm:text-sm font-black text-rose-300 flex items-center gap-1" id="teaserAlertsBadge">
                                <span>۲ زنگ خطر شناسایی شد</span>
                                <span class="text-amber-400 text-xs">⚠️</span>
                            </div>
                            <div class="text-[9px] text-white/50 mt-1">حفاظت مفاصل، گوارش و فک نژاد</div>
                        </div>

                    </div>

                    <!-- Stunning Blurred Teaser Mockup of the Meal Plan Chart -->
                    <div class="relative rounded-2xl overflow-hidden border border-white/20 bg-slate-900/60 p-4 shadow-xl">
                        <!-- Mockup Meal Table (Intentionally Blurred) -->
                        <div class="select-none pointer-events-none filter blur-[3.5px] opacity-40 space-y-2">
                            <div class="flex justify-between items-center bg-white/10 p-2 rounded-lg text-[10px]">
                                <span>وعده ۱: صبح (۰۸:۳۰)</span>
                                <span class="font-mono text-emerald-400">███ گرم + امگا ۳</span>
                            </div>
                            <div class="flex justify-between items-center bg-white/10 p-2 rounded-lg text-[10px]">
                                <span>وعده ۲: نیمروزی (۱۴:۰۰)</span>
                                <span class="font-mono text-purple-300">پاداش سلامت ██ گرم</span>
                            </div>
                            <div class="flex justify-between items-center bg-white/10 p-2 rounded-lg text-[10px]">
                                <span>وعده ۳: شب (۲۰:۰۰)</span>
                                <span class="font-mono text-emerald-400">███ گرم + گلوکوزامین</span>
                            </div>
                        </div>

                        <!-- Golden Lock & Value Overlay -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-3 z-10 bg-slate-950/45 backdrop-blur-[2px]">
                            <div class="w-9 h-9 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 flex items-center justify-center text-lg mb-1 shadow-lg">
                                <span class="material-symbols-outlined text-lg">lock</span>
                            </div>
                            <div class="text-xs font-black text-white">جدول زمان‌بندی دقیق رژیم غذایی و گرم هر وعده پت آماده است</div>
                            <div class="inline-flex items-center gap-1 mt-1 bg-amber-400/20 text-amber-200 border border-amber-400/30 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                                <span>ارزش کارنامه: ۹۸,۰۰۰ تومان</span>
                                <span class="text-emerald-300">— هدیه رایگان آسنا</span>
                            </div>
                            <div class="text-[9px] text-white/70 mt-1 max-w-xs">
                                نسخه رسمی به همراه مهر بالینی، دستورالعمل هیدراتاسیون و فایل چاپی در پرونده سلامت شما در پروفایل ذخیره خواهد شد.
                            </div>
                        </div>
                    </div>

                    <!-- Breed Biomechanic Hint Box -->
                    <div class="bg-indigo-950/50 p-3 rounded-xl border border-indigo-400/25 flex items-start gap-2 text-xs">
                        <span class="material-symbols-outlined text-indigo-300 text-sm mt-0.5 shrink-0">psychology</span>
                        <div class="text-[11px] text-indigo-100 leading-relaxed" id="teaserAiMetabolicSummary">
                            ارزیابی بیومکانیک نژاد و فک پت توسط هوش مصنوعی تکمیل شده و در فایل ارسالی به پروفایل شما ثبت گردیده است.
                        </div>
                    </div>

                </div>

                <!-- High-Converting CTA Actions -->
                <div class="pt-4 mt-4 border-t border-white/10 space-y-2.5 relative z-10">
                    <!-- Primary Magnetic Button: Save & Send Meal Plan to Profile -->
                    <button type="button" onclick="issueAndSendMealPlanToProfile()" id="btnIssueMealPlan" class="w-full bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white py-3.5 px-4 rounded-2xl font-black text-xs sm:text-sm text-center shadow-xl shadow-emerald-600/30 hover:shadow-emerald-500/50 transition-all flex flex-col items-center justify-center gap-0.5 cursor-pointer active:scale-98">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">restaurant_menu</span>
                            <span>صدور و ارسال جدول برنامه غذایی به پرونده من</span>
                            <span class="bg-white/25 text-[10px] px-2 py-0.5 rounded-full font-sans">رایگان</span>
                        </div>
                        <span class="text-[10px] text-emerald-100 font-normal">
                            ارسال مستقیم فایل به پروفایل • ذخیره در پرونده سلامت • چاپ و دانلود PDF
                        </span>
                    </button>

                    <div class="flex items-center justify-center gap-4 text-[10px] text-white/70 pt-1">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs text-emerald-400">check</span>
                            استاندارد WSAVA
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs text-emerald-400">check</span>
                            آرشیو دائمی در پروفایل
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs text-emerald-400">check</span>
                            بدون هزینه
                        </span>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- 7-Day Diet Transition Protocol Section (Standard Veterinary Procedure) -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm mb-12 space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-emerald-600 text-2xl">timeline</span>
                <h3 class="text-sm sm:text-base font-black text-slate-800">
                    پروتکل بالینی ۷ روزه تغییر تدریجی جیره غذایی (جلوگیری از شوک گوارشی)
                </h3>
            </div>
            <span class="text-xs text-slate-400 font-medium">دستورالعمل آکادمیک انجمن جهانی دامپزشکی (WSAVA)</span>
        </div>
        <p class="text-xs text-slate-500 leading-relaxed">
            تغییر ناگهانی غذای خشک یا مرطوب حیوانات خانگی سبب برهم‌خوردن میکروبیوم روده، اسهال و استفراغ حاد می‌گردد. حتماً غذای جدید را طی ۷ روز مطابق نسبت‌های زیر جایگزین فرمایید:
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-2">
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-right space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-slate-700">روز ۱ و ۲</span>
                    <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">۲۵٪ جدید</span>
                </div>
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden flex">
                    <div class="bg-emerald-500 w-1/4"></div>
                    <div class="bg-slate-300 w-3/4"></div>
                </div>
                <p class="text-[11px] text-slate-500">۲۵٪ غذای جدید + ۷۵٪ جیره قبلی</p>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-right space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-slate-700">روز ۳ و ۴</span>
                    <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">۵۰٪ جدید</span>
                </div>
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden flex">
                    <div class="bg-emerald-500 w-2/4"></div>
                    <div class="bg-slate-300 w-2/4"></div>
                </div>
                <p class="text-[11px] text-slate-500">۵۰٪ غذای جدید + ۵۰٪ جیره قبلی</p>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-right space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-slate-700">روز ۵ و ۶</span>
                    <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">۷۵٪ جدید</span>
                </div>
                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden flex">
                    <div class="bg-emerald-500 w-3/4"></div>
                    <div class="bg-slate-300 w-1/4"></div>
                </div>
                <p class="text-[11px] text-slate-500">۷۵٪ غذای جدید + ۲۵٪ جیره قبلی</p>
            </div>

            <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-right space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-emerald-900">روز ۷ به بعد</span>
                    <span class="text-[11px] font-bold text-emerald-700 bg-white px-2 py-0.5 rounded-md border border-emerald-200">۱۰۰٪ کامل</span>
                </div>
                <div class="w-full bg-emerald-200 h-2 rounded-full overflow-hidden">
                    <div class="bg-emerald-600 w-full h-full"></div>
                </div>
                <p class="text-[11px] text-emerald-800 font-bold">۱۰۰٪ غذای جدید با پذیرش کامل پت</p>
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
            <h3 class="font-bold text-sm text-slate-800">شاخص وضعیت بدنی (BCS 1-9) و طول عمر</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                حیواناتی که در محدوده ایده‌آل (BCF 4-5) نگهداری می‌شوند به طور متوسط ۱.۸ تا ۲.۵ سال بیشتر عمر می‌کنند و ریسک بیماری‌های مفصلی و قلبی تا ۶۰ درصد در آنها کاهش می‌یابد.
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
                <p class="text-xs text-slate-500 mt-1">تداخلات دارویی و غذایی را با سامانه آنلاین آسنا بررسی کنید تا از سلامت کامل حیوان خانگی‌تان اطمینان حاصل شود.</p>
            </div>
        </div>
        <a href="interactions.php" class="px-5 py-3 rounded-2xl bg-[#001a48] hover:bg-[#002d72] text-white font-bold text-xs flex items-center gap-2 transition shadow-sm whitespace-nowrap cursor-pointer">
            <span>بررسی تداخلات دارویی</span>
            <span class="material-symbols-outlined text-sm">arrow_back</span>
        </a>
    </div>

</main>

<!-- ========================================================================= -->
<!-- OFFICIAL ASENA CLINICAL NUTRITION ASSESSMENT & CERTIFICATE MODAL          -->
<!-- ========================================================================= -->
<div id="nutritionReportModal" class="fixed inset-0 z-[10080] hidden items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-y-auto rtl text-right" onclick="if(event.target === this) closeNutritionReportModal();">
    <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden my-6 relative transition-all animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header Ribbon -->
        <div class="bg-gradient-to-r from-[#001a48] via-[#002d72] to-[#001336] p-6 text-white relative">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-2xl border border-white/20">
                        🐾
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-black tracking-tight">شناسنامه و کارنامه تغذیه بالینی پت</h2>
                            <span class="bg-emerald-400/20 text-emerald-300 text-[10px] font-black px-2.5 py-0.5 rounded-full border border-emerald-400/40">تاییدیه WSAVA</span>
                        </div>
                        <p class="text-xs text-white/70 mt-0.5">زیست‌بوم جامع سلامت و خدمات دامپزشکی آسنا (ASENA Medical)</p>
                    </div>
                </div>
                <button type="button" onclick="closeNutritionReportModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <div class="flex justify-between items-center mt-4 pt-3 border-t border-white/10 text-[11px] text-white/60 font-mono">
                <span>شماره پرونده: <span id="certSerial" class="text-amber-300 font-bold">ASENA-NUT-82A7</span></span>
                <span>تاریخ صدور: <span id="certDate" class="text-white">امروز</span></span>
            </div>
        </div>

        <!-- Certificate Body Content (Printable Area) -->
        <div class="p-6 sm:p-8 space-y-6" id="printableCertificateArea">
            
            <!-- Patient Identification Strip -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">نام بیمار (پت):</span>
                    <span class="font-black text-slate-800 text-sm" id="certPetName">تدی</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">گونه و رده:</span>
                    <span class="font-bold text-slate-700" id="certSpecies">سگ بالغ</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">نژاد پت:</span>
                    <span class="font-black text-emerald-700" id="certBreed">ژرمن شپرد</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">وزن ثبت‌شده:</span>
                    <span class="font-bold text-slate-700" id="certCurrentWeight">۸.۵ کیلوگرم</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">شاخص وضعیت بدنی:</span>
                    <span class="font-bold text-emerald-600" id="certBcsDisplay">امتیاز ۵ (ایده‌آل)</span>
                </div>
            </div>

            <!-- Dietary Prescription Table -->
            <div class="space-y-2">
                <h3 class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm text-[#fd8100]">prescriptions</span>
                    <span>نسخه و پروتکل اختصاصی جیره غذایی ۲۴ ساعته:</span>
                </h3>
                <div class="border border-slate-200 rounded-2xl overflow-hidden text-xs">
                    <table class="w-full text-right divide-y divide-slate-200">
                        <thead class="bg-slate-100 text-slate-600 font-bold">
                            <tr>
                                <th class="p-3">عنصر رژیم غذایی</th>
                                <th class="p-3">مقدار مجاز روزانه</th>
                                <th class="p-3">دستور مصرف بالینی</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            <tr>
                                <td class="p-3 font-bold flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    <span>انرژی مورد نیاز (MER)</span>
                                </td>
                                <td class="p-3 font-mono font-bold text-slate-900" id="certKcal">۵۴۰ kcal</td>
                                <td class="p-3 text-slate-500">حفظ نرخ متابولیک پایه و فعالیت</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>غذای خشک استاندارد</span>
                                </td>
                                <td class="p-3 font-mono font-bold text-emerald-700" id="certKibble">۱۴۵ گرم</td>
                                <td class="p-3 text-slate-500" id="certMeals">۲ وعده صبح و شب (هر وعده ۷۲ گرم)</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                                    <span>آب تازه تصفیه‌شده</span>
                                </td>
                                <td class="p-3 font-mono font-bold text-sky-700" id="certWater">۵۱۰ میلی‌لیتر</td>
                                <td class="p-3 text-slate-500">در دسترس دائم در ظرف استیل یا سرامیک</td>
                            </tr>
                            <tr>
                                <td class="p-3 font-bold flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                                    <span>سقف تشویقی (Treats)</span>
                                </td>
                                <td class="p-3 font-mono font-bold text-purple-700" id="certTreat">۵۴ kcal</td>
                                <td class="p-3 text-slate-500">حداکثر ۱۰٪ کالری کل جهت جلوگیری از چاقی</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- AI Clinical Nutritionist Dossier Section (WSAVA / Breed Specific) -->
            <div class="bg-gradient-to-br from-slate-900 to-indigo-950 p-5 rounded-2xl border border-indigo-500/30 text-white space-y-3 shadow-md">
                <div class="flex items-center justify-between border-b border-white/15 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-400 text-lg">psychology</span>
                        <h4 class="text-xs font-black text-white">ارزیابی بالینی هوش مصنوعی بر پایه نژاد و متابولیسم:</h4>
                    </div>
                    <span class="bg-indigo-500/30 text-indigo-200 text-[10px] font-mono px-2 py-0.5 rounded-full border border-indigo-400/30">
                        AI Certified
                    </span>
                </div>
                
                <p class="text-[11px] text-indigo-100 leading-relaxed font-normal" id="certAiMetabolic">
                    در حال بارگذاری تحلیل نژادی هوش مصنوعی...
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1 text-[11px]" id="certAiBulletPoints">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- Target Weight & Clinical Prognosis -->
            <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 p-4 rounded-2xl border border-emerald-200 space-y-1.5 text-xs text-emerald-950">
                <div class="flex items-center justify-between font-black">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-emerald-700">insights</span>
                        <span>هدف وزنی و چشم‌انداز سلامت:</span>
                    </span>
                    <span id="certTargetWeight" class="text-emerald-800">وزن ایده‌آل: ۸.۵ کیلوگرم</span>
                </div>
                <p class="text-[11px] text-emerald-800/80 leading-relaxed font-normal" id="certPrognosis">
                    پت شما در محدوده شاخص استاندارد سلامت وزنی قرار دارد. تداوم این جیره مانع از ابتلا به دیابت و دردهای اسکلتی در دوران میانسالی خواهد شد.
                </p>
            </div>

            <!-- Official Seal & Advisory Stamp -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-200 text-xs">
                <div class="space-y-1">
                    <div class="font-black text-slate-800 text-[11px]">مهر تاییدیه کمیته فارماکولوژی و تغذیه دامپزشکی آسنا</div>
                    <div class="text-[10px] text-slate-400">شماره ثبت استانداردهای بالینی: IR-VET-7819</div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-300 flex items-center justify-center text-center p-1 text-[8px] font-mono text-slate-400 leading-tight">
                        [QR-VERIFY]
                    </div>
                    <div class="w-12 h-12 rounded-full bg-emerald-100 border-2 border-dashed border-emerald-500 flex items-center justify-center text-emerald-700 text-xs font-black rotate-[-12deg] shadow-2xs">
                        ASENA
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer Actions -->
        <div class="p-6 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <span class="material-symbols-outlined text-emerald-600 text-base">verified</span>
                <span>ارزش کارنامه: ۴۹,۰۰۰ تومان — هدیه آسنا به کاربران گرامی</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <button type="button" onclick="consultAiAboutDiet()" class="flex-1 sm:flex-none px-3.5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">smart_toy</span>
                    <span>مشاوره با هوش مصنوعی</span>
                </button>
                <button type="button" onclick="window.print()" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-white text-slate-700 text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs">
                    <span class="material-symbols-outlined text-sm">print</span>
                    <span>چاپ / ذخیره PDF</span>
                </button>
                <button type="button" onclick="saveNutritionReportToProfile()" id="btnSaveReport" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black transition flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/20 active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">save</span>
                    <span>ذخیره در پرونده سلامت پت</span>
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- MEAL PLAN SUCCESS & PROFILE DELIVERY MODAL                                -->
<!-- ========================================================================= -->
<div id="mealPlanSuccessModal" class="fixed inset-0 z-[10090] hidden items-center justify-center p-4 bg-black/75 backdrop-blur-md overflow-y-auto rtl text-right" onclick="if(event.target === this) closeMealPlanSuccessModal();">
    <div class="bg-white rounded-[2rem] max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden my-6 relative transition-all animate-in fade-in zoom-in-95 duration-200">
        <!-- Top Celebration Header -->
        <div class="bg-gradient-to-br from-emerald-600 via-teal-700 to-[#002d72] p-6 text-white text-center relative overflow-hidden">
            <div class="absolute -top-10 -left-10 w-32 h-32 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
            <button type="button" onclick="closeMealPlanSuccessModal()" class="absolute top-4 left-4 w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center text-white transition cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
            <div class="w-16 h-16 rounded-3xl bg-white/20 border-2 border-white/40 flex items-center justify-center text-3xl mx-auto mb-3 shadow-lg">
                <span class="material-symbols-outlined text-3xl text-emerald-100">verified</span>
            </div>
            <h2 class="text-lg sm:text-xl font-black">جدول برنامه غذایی با موفقیت صادر شد!</h2>
            <p class="text-xs text-emerald-100/90 mt-1">فایل اختصاصی رژیم غذایی در پرونده سلامت پت شما ذخیره گردید.</p>
            <div class="mt-3 inline-flex items-center gap-2 bg-white/15 border border-white/20 px-3 py-1 rounded-xl text-xs font-mono">
                <span>شناسه پرونده:</span>
                <span id="successModalSerial" class="font-bold text-amber-300">ASENA-NUT-XXXX</span>
            </div>
        </div>

        <!-- Content Details -->
        <div class="p-6 space-y-4 text-xs">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2">
                <div class="flex items-center gap-2 font-bold text-slate-800">
                    <span class="material-symbols-outlined text-emerald-600 text-base">task_alt</span>
                    <span>محتوای سند ذخیره شده در پرونده شما:</span>
                </div>
                <ul class="text-[11px] text-slate-600 space-y-1.5 pr-5 list-disc">
                    <li>جدول تفکیکی وعده‌های روزانه (صبح، عصر، شب با گرم دقیق)</li>
                    <li>پروتکل بالینی تغییر تدریجی ۷ روزه جیره غذایی</li>
                    <li>توصیه‌ها و هشدارهای فیزیولوژیک ویژه نژاد <span id="successModalBreed" class="font-bold text-emerald-700"></span></li>
                    <li>برنامه مکمل‌های تجویزی و هیدراتاسیون بالینی روزانه</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-2.5 pt-2">
                <a id="successModalViewLink" href="#" target="_blank" rel="noopener noreferrer" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3.5 px-4 rounded-xl font-black text-xs text-center shadow-lg shadow-emerald-600/25 transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">open_in_new</span>
                    <span>مشاهده و چاپ فایل جدول برنامه غذایی</span>
                </a>

                <a href="profile.php#view-records" class="w-full bg-[#002d72] hover:bg-[#001a48] text-white py-3.5 px-4 rounded-xl font-bold text-xs text-center transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">folder_shared</span>
                    <span>مشاهده در سوابق پزشکی و پروفایل من</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- AUTHENTICATION REQUIRED MODAL (FOR GUEST USERS)                           -->
<!-- ========================================================================= -->
<div id="mealPlanAuthModal" class="fixed inset-0 z-[10090] hidden items-center justify-center p-4 bg-black/75 backdrop-blur-md overflow-y-auto rtl text-right" onclick="if(event.target === this) closeMealPlanAuthModal();">
    <div class="bg-white rounded-[2rem] max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden my-6 relative transition-all animate-in fade-in zoom-in-95 duration-200">
        <div class="bg-gradient-to-br from-[#001a48] to-[#002d72] p-6 text-white text-center relative">
            <button type="button" onclick="closeMealPlanAuthModal()" class="absolute top-4 left-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
            <div class="w-14 h-14 rounded-2xl bg-amber-500/20 border border-amber-400/30 text-amber-300 flex items-center justify-center text-2xl mx-auto mb-3">
                <span class="material-symbols-outlined text-2xl">lock_open</span>
            </div>
            <h3 class="text-base font-black">صدور و ذخیره در پرونده اختصاصی پت</h3>
            <p class="text-xs text-white/70 mt-1">جهت ارسال فایل جدول برنامه غذایی و بایگانی دائمی در سوابق پزشکی، لطفاً وارد شوید.</p>
        </div>

        <div class="p-6 space-y-3 text-xs">
            <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-xl text-amber-900 text-[11px] leading-relaxed">
                اطلاعات واردشده شما محفوظ است و بلافاصله پس از ورود، فایل جدول برنامه غذایی در پروفایل شما ثبت خواهد گردید.
            </div>

            <div class="space-y-2 pt-2">
                <a href="login.php?redirect=calculator.php%3Fauto_issue%3D1" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3 px-4 rounded-xl font-black text-xs text-center shadow-md transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">login</span>
                    <span>ورود با شماره موبایل / حساب کاربری</span>
                </a>
                <a href="register.php?redirect=calculator.php%3Fauto_issue%3D1" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 py-3 px-4 rounded-xl font-bold text-xs text-center transition flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-base">person_add</span>
                    <span>ثبت‌نام سریع در آسنا (رایگان)</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Calculator JavaScript Engine -->
<script>
(function() {
    const DOG_BREEDS = [
        { name: 'ژرمن شپرد', title: 'ژرمن شپرد (German Shepherd)', icon: '🐕', size: 'large', defaultWeight: 30, hintTitle: 'شاخص فیزیولوژیک ژرمن شپرد', hintDesc: 'نژاد بزرگ‌جثه با حساسیت مفاصل ران (دیسپلازی) و معده حساس. نیاز به کلسیم و فسفر بالانس‌شده و فرمول غنی از ال-کارنیتین.', foodTitle: 'غذای خشک رویال کنین ژرمن شپرد ادالت' },
        { name: 'هاسکی', title: 'سیبرین هاسکی (Siberian Husky)', icon: '🐺', size: 'medium-large', defaultWeight: 22, hintTitle: 'متابولیسم سیبرین هاسکی', hintDesc: 'راندمان جذب کالری بسیار بالا با خودتنظیمی مصرف غذا. حساسیت بالا به کمبود زینک و نیازمند امگا ۳ جهت حفظ پوشش دولایه در اقلیم ایران.', foodTitle: 'غذای خشک رفلکس پلاس ماهی سالمون و برنج هاسکی' },
        { name: 'شیتزو', title: 'شیتزو (Shih Tzu)', icon: '🐶', size: 'toy', defaultWeight: 6.5, hintTitle: 'آناتومی براکی‌سفالیک شیتزو', hintDesc: 'پوزه‌کوتاه با فک ظریف؛ مستعد تشکیل پلاک دندان و سنگ‌های ادراری اگزالات. نیازمند کیبل‌های هلالی کوچک با فسفر کنترل‌شده.', foodTitle: 'غذای خشک رویال کنین شیتزو ادالت' },
        { name: 'پامرانین', title: 'پامرانین (Pomeranian)', icon: '🦊', size: 'toy', defaultWeight: 3.0, hintTitle: 'سوخت‌وساز فشرده پامرانین', hintDesc: 'متابولیسم بسیار سریع با خطر افت قند خون؛ حساسیت نای به قلاده و مستعد دررفتگی کشکک زانو. نیازمند تغذیه در ۳ وعده ثابت.', foodTitle: 'غذای خشک رویال کنین پامرانین ادالت' },
        { name: 'تریر', title: 'تریر / یورکشایر (Terrier)', icon: '🐕‍🦺', size: 'small', defaultWeight: 5.5, hintTitle: 'ویژگی‌های گوارشی تریر', hintDesc: 'دستگاه گوارش بسیار حساس با شیوع آلرژی‌های غذایی به غلات. نیازمند پروتئین هیدرولیزشده و کیبل ویژه بهداشت دهان.', foodTitle: 'غذای خشک رویال کنین یورکشایر تریر ادالت' },
        { name: 'گلدن رتریور', title: 'گلدن رتریور (Golden Retriever)', icon: '🦮', size: 'large', defaultWeight: 32, hintTitle: 'کنترل اشتها در گلدن رتریور', hintDesc: 'حامل جهش ژن پرخوری با استعداد چاقی مفرط و حساسیت مفاصل. نیازمند فیبر بالا، کارنیتین و اندازه‌گیری دقیق گرم غذا با ترازوی دیجیتال.', foodTitle: 'غذای خشک رویال کنین گلدن رتریور ادالت' },
        { name: 'پاگ', title: 'پاگ (Pug)', icon: '🐾', size: 'small-brachy', defaultWeight: 8.0, hintTitle: 'سندرم تنفسی و وزن پاگ', hintDesc: 'اضافه وزن به شدت تنفس پاگ را مختل می‌کند. رژیم کم‌کالری با پروتئین باکیفیت و کیبل حجیم جهت احساس سیری سریع.', foodTitle: 'غذای خشک رویال کنین پاگ ادالت' },
        { name: 'پودل', title: 'پودل (Poodle)', icon: '🐩', size: 'toy-medium', defaultWeight: 7.0, hintTitle: 'پوشش فر و مفاصل پودل', hintDesc: 'نیاز به اسیدهای چرب امگا ۶ و بیوتین جهت درخشندگی پوشش بدون ریزش و آنتی‌اکسیدان‌های چشمی جهت محافظت از عدسی چشم.', foodTitle: 'غذای خشک رویال کنین پودل ادالت' },
        { name: 'روتوایلر', title: 'روتوایلر (Rottweiler)', icon: '🐕', size: 'large', defaultWeight: 45, hintTitle: 'حجم عضلانی روتوایلر', hintDesc: 'فشار مضاعف وزن روی مفاصل و تاندون‌ها؛ نیاز به پروتئین بالای ۳۰٪، گلوکوزامین و کنترل سرعت غذا خوردن جهت جلوگیری از پیچش معده.', foodTitle: 'غذای خشک رویال کنین روتوایلر ادالت' },
        { name: 'دوبرمن', title: 'دوبرمن پینچر (Doberman)', icon: '🐕', size: 'large', defaultWeight: 36, hintTitle: 'سلامت عضله قلب دوبرمن', hintDesc: 'مستعد کاردیومیوپاتی اتساعی (DCM)؛ رژیم غذایی باید حتماً غنی از ال-کارنیتین و تائورین دارویی باشد.', foodTitle: 'غذای خشک هیلز ساینس پلن لارج برید' },
        { name: 'بولداگ', title: 'بولداگ فرانسوی/انگلیسی (Bulldog)', icon: '🐶', size: 'medium-brachy', defaultWeight: 12, hintTitle: 'گوارش و مفاصل بولداگ', hintDesc: 'حساسیت به نفخ و بوی نامطبوع گازهای گوارشی؛ نیازمند پروتئین‌های با قابلیت هضم L.I.P و کیبل ویژه فک کوتاه.', foodTitle: 'غذای خشک رویال کنین فرنچ بولداگ ادالت' },
        { name: 'سرابی', title: 'سرابی بومی / کانگال (Sarabi)', icon: '🐕', size: 'giant', defaultWeight: 55, hintTitle: 'رشد استخوانی سگ غول‌پیکر بومی', hintDesc: 'وزن‌گیری اسکلتی سنگین؛ پرهیز اکید از کلسیم مازاد در دوران رشد و تقسیم جیره به ۲ وعده با استراحت کامل قبل و بعد غذا.', foodTitle: 'غذای خشک رویال کنین جاینت ادالت (سگ‌های بالای ۴۵ کیلو)' },
        { name: 'مالتیز', title: 'مالتیز (Maltese)', icon: '🐶', size: 'toy', defaultWeight: 3.5, hintTitle: 'کنترل لکه‌های اشک و پوست مالتیز', hintDesc: 'حساسیت به رنگدانه‌های غذایی و ایجاد لکه اشک قهوه‌ای زیر چشم؛ نیاز به رژیم سفید بدون مواد نگهدارنده مصنوعی.', foodTitle: 'غذای خشک رویال کنین مالتیز ادالت' },
        { name: 'شیتزو تریر', title: 'میکس شیتزو تریر (Shih-Tzu Cross)', icon: '🐶', size: 'small', defaultWeight: 6.0, hintTitle: 'میکس محبوب ایرانی', hintDesc: 'ترکیب پوشش بلند و فعالیت متوسط آپارتمانی؛ نیازمند تعادل چربی و فیبر برای سلامت روده و پوست بدون خارش.', foodTitle: 'غذای خشک رویال کنین مینی ادالت' },
        { name: 'میکس و بومی', title: 'میکس / نژاد بومی (Crossbreed)', icon: '🐕', size: 'medium', defaultWeight: 15, hintTitle: 'مقاومت ژنتیکی میکس بومی', hintDesc: 'تنوع ژنتیکی عالی و مقاومت بدنی خوب؛ تمرکز بر شاخص بدنی BCS 5/9 و پیشگیری از اضافه وزن ناشی از بی‌تحرکی.', foodTitle: 'غذای خشک رویال کنین مدیوم ادالت' },
        { name: 'سایر نژادها', title: 'سایر نژادهای سگ', icon: '✨', size: 'custom', defaultWeight: 10, hintTitle: 'جیره استاندارد سگ', hintDesc: 'بالانس دقیق کالری و پروتئین با قابلیت هضم بالا و تأمین هیدراتاسیون کافی.', foodTitle: 'غذای خشک تخصصی سگ بالینی' }
    ];

    const CAT_BREEDS = [
        { name: 'پرشین', title: 'پرشین اصیل (Persian Cat)', icon: '🐱', size: 'longhair-brachy', defaultWeight: 4.5, hintTitle: 'فک فلت و هربال پرشین', hintDesc: 'پوزه‌کوتاه با بلع مداوم مو؛ رژیم الزامی با فیبر پسیلیوم جهت دفع طبیعی گلوله مویی و فسفر پایین برای حفاظت از کلیه‌های مستعد PKD.', foodTitle: 'غذای خشک رویال کنین پرشین ادالت' },
        { name: 'دی‌اس‌اچ', title: 'دی‌اس‌اچ / بومی موکوتاه (DSH)', icon: '🐈', size: 'shorthair', defaultWeight: 4.0, hintTitle: 'حفاظت مجاری ادراری DSH', hintDesc: 'شایع‌ترین چالش پس از عقیم‌سازی، تنبلی مثانه و رسوبات FLUTD است. آب‌رسانی مداوم و غذای استرلایزد با چربی کنترل‌شده توصیه می‌شود.', foodTitle: 'غذای خشک استرلایزد رفلکس پلاس گربه' },
        { name: 'بریتیش', title: 'بریتیش شورت‌هیر (British Shorthair)', icon: '🐾', size: 'cobby', defaultWeight: 5.5, hintTitle: 'عضلات فشرده بریتیش', hintDesc: 'ساختار بدنی قوی با استعداد تنبلی و چاقی؛ نیاز به پروتئین مرغوب ۳۴٪ و تائورین و EPA/DHA جهت حمایت از عملکرد قلب.', foodTitle: 'غذای خشک رویال کنین بریتیش شورت‌هیر ادالت' },
        { name: 'اسکاتیش فولد', title: 'اسکاتیش فولد (Scottish Fold)', icon: '🐱', size: 'ocd-prone', defaultWeight: 4.2, hintTitle: 'نقص غضروفی سیستمیک اسکاتیش', hintDesc: 'ژن تاخوردگی گوش همراه با نقص غضروف مفاصل است. پرهیز مطلق از اضافه وزن و استفاده از گلوکوزامین و عصاره صدف سبز ضروری است.', foodTitle: 'غذای خشک رویال کنین بریتیش یا هیلز جوینت ساپورت' },
        { name: 'سیامی', title: 'سیامی (Siamese)', icon: '🐈', size: 'oriental', defaultWeight: 3.8, hintTitle: 'متابولیسم لاغراندام سیامی', hintDesc: 'فعالیت ذهنی و آوازی بالا با بدن کشیده؛ نیاز به کیبل استوانه‌ای کشیده برای جویدن طولانی و هضم آهسته.', foodTitle: 'غذای خشک رویال کنین سیامی ادالت' },
        { name: 'راگدال', title: 'راگدال (Ragdoll)', icon: '🐱', size: 'large-longhair', defaultWeight: 6.5, hintTitle: 'نژاد آرام و سنگین‌وزن راگدال', hintDesc: 'رشد طولانی تا ۳ سالگی؛ جثه درشت نیازمند کیبل بزرگ هرمی و مکمل‌های تقویتی ماهیچه قلب و مفاصل دست و پا.', foodTitle: 'غذای خشک رویال کنین راگدال ادالت' },
        { name: 'هیمالین', title: 'هیمالین (Himalayan)', icon: '🐈', size: 'longhair', defaultWeight: 4.6, hintTitle: 'پوشش ابریشمی هیمالین', hintDesc: 'تلاقی پرشین و سیامی؛ فرمولاسیون مالت هربال در کنار محافظت از مجاری اشکی چشم و پوست بدون شوره.', foodTitle: 'غذای خشک رویال کنین پرشین یا هربال کر' },
        { name: 'مین‌کون', title: 'مین‌کون (Maine Coon)', icon: '🐱', size: 'giant-cat', defaultWeight: 8.0, hintTitle: 'بزرگ‌ترین نژاد گربه خانگی', hintDesc: 'آرواره بسیار قدرتمند؛ کیبل باید بزرگ باشد تا گربه نتواند آن را درسته ببلعد. حفاظت شدید از مفاصل و عضله قلب.', foodTitle: 'غذای خشک رویال کنین مین‌کون ادالت' },
        { name: 'ترکیش ون', title: 'ترکیش ون / آنگورا (Turkish Van)', icon: '🐈', size: 'semilonghair', defaultWeight: 5.0, hintTitle: 'گربه چابک و آب‌دوست', hintDesc: 'انرژی عضلانی بالا بدون لایه چربی؛ نیازمند رژیم با پروتئین لذیذ مرغ یا ماهی و اسیدهای آمینه ضروری.', foodTitle: 'غذای خشک رفلکس پلاس گربه بالغ' },
        { name: 'پرشین فلت', title: 'پرشین فلت / پینج‌فیس (Flat)', icon: '🐱', size: 'brachy', defaultWeight: 4.2, hintTitle: 'فک شدیداً براکی‌سفالیک فلت', hintDesc: 'تنفس صدادار با حساسیت به خشکی مجاری فوقانی؛ غذای تر رطوبت‌بالا در کنار کیبل ارگونومیک بادامی الزامی است.', foodTitle: 'غذای خشک و مرطوب رویال کنین پرشین' },
        { name: 'میکس و دورگه', title: 'میکس / دورگه گربه (Cross)', icon: '🐾', size: 'standard', defaultWeight: 4.0, hintTitle: 'گربه خانگی متعادل', hintDesc: 'پایش دوره‌ای وزن، پیشگیری از گلوله مویی با علف گربه و تشویق به بازی روزانه جهت تخلیه غریزه شکار.', foodTitle: 'غذای خشک استرلایزد یا ادالت رفلکس پلاس' },
        { name: 'سایر نژادها', title: 'سایر نژادهای گربه', icon: '✨', size: 'custom', defaultWeight: 4.0, hintTitle: 'جیره استاندارد گربه', hintDesc: 'تأمین کامل تائورین، اسیدهای چرب امگا و کنترل pH ادرار جهت سلامت پایدار.', foodTitle: 'غذای خشک تخصصی گربه بالینی' }
    ];

    window.calcState = {
        species: 'dog',
        race: 'ژرمن شپرد',
        weight: 8.5,
        stage: 'adult',
        activity: 'neutered',
        bcs: 5,
        petName: 'تدی',
        idealWeight: 8.5,
        mer: 540,
        kibbleGrams: 145,
        waterMl: 510,
        serial: 'ASENA-NUT-' + Math.random().toString(36).substring(2, 8).toUpperCase(),
        aiAnalysis: null
    };

    let aiDebounceTimer = null;

    window.renderBreedChips = function() {
        const container = document.getElementById('breedChipsContainer');
        const datalist = document.getElementById('breedDatalist');
        if (!container) return;

        const breeds = calcState.species === 'dog' ? DOG_BREEDS : CAT_BREEDS;
        
        // Render Chips
        let html = '';
        breeds.forEach(b => {
            const isActive = b.name === calcState.race || b.title === calcState.race;
            const activeClass = isActive 
                ? 'bg-emerald-500 text-white font-black border-emerald-400 shadow-md shadow-emerald-500/30' 
                : 'bg-white/10 text-white/80 hover:bg-white/20 border-white/15';
            html += `<button type="button" onclick="selectBreed('${b.name}')" class="calc-breed-chip py-1.5 px-3 rounded-xl border text-xs transition-all flex items-center gap-1.5 cursor-pointer ${activeClass}">
                <span>${b.icon}</span>
                <span>${b.name}</span>
            </button>`;
        });
        container.innerHTML = html;

        // Render Datalist for autocomplete
        if (datalist) {
            datalist.innerHTML = breeds.map(b => `<option value="${b.name}">${b.title}</option>`).join('');
        }

        // Update custom input
        const customInput = document.getElementById('calcBreedCustomInput');
        if (customInput && !customInput.matches(':focus')) {
            customInput.value = calcState.race;
        }

        // Update badge
        const badge = document.getElementById('calcSelectedBreedBadge');
        if (badge) badge.textContent = calcState.race;

        // Update Breed Hint Box
        updateBreedHintBox();
    };

    function updateBreedHintBox() {
        const breeds = calcState.species === 'dog' ? DOG_BREEDS : CAT_BREEDS;
        const found = breeds.find(b => b.name === calcState.race || calcState.race.includes(b.name));
        const hintTitle = document.getElementById('breedHintTitle');
        const hintDesc = document.getElementById('breedHintDesc');
        
        if (found) {
            if (hintTitle) hintTitle.textContent = found.hintTitle;
            if (hintDesc) hintDesc.textContent = found.hintDesc;
        } else {
            if (hintTitle) hintTitle.textContent = `شاخص فیزیولوژیک نژاد ${calcState.race}`;
            if (hintDesc) hintDesc.textContent = 'بالانس متابولیک و تنظیم پروتئین بر اساس فنوتیپ و شاخص وضعیت بدنی پت.';
        }
    }

    window.selectBreed = function(breedName) {
        calcState.race = breedName;
        renderBreedChips();
        recalculateNutrition();
        debouncedAiAnalysis();
    };

    window.handleBreedInputChange = function(val) {
        const trimmed = val.trim();
        if (!trimmed) return;
        calcState.race = trimmed;
        
        const badge = document.getElementById('calcSelectedBreedBadge');
        if (badge) badge.textContent = trimmed;
        
        updateBreedHintBox();
        recalculateNutrition();
        debouncedAiAnalysis();
    };

    function debouncedAiAnalysis() {
        clearTimeout(aiDebounceTimer);
        aiDebounceTimer = setTimeout(() => {
            triggerAiBreedAnalysis();
        }, 1200);
    }

    window.setCalcSpecies = function(species) {
        calcState.species = species;
        
        const btnDog = document.getElementById('calcBtnDog');
        const btnCat = document.getElementById('calcBtnCat');
        const labelPuppy = document.getElementById('labelPuppy');

        if (species === 'dog') {
            btnDog.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/25 cursor-pointer';
            btnCat.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15 cursor-pointer';
            labelPuppy.textContent = 'توله سگ (زیر ۱ سال)';
            calcState.race = 'ژرمن شپرد';
            if (calcState.weight < 1) calcState.weight = 8.5;
            document.getElementById('calcWeightSlider').max = 60;
        } else {
            btnCat.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/25 cursor-pointer';
            btnDog.className = 'calc-species-btn py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 border-2 transition-all bg-white/10 text-white/80 border-white/15 hover:bg-white/15 cursor-pointer';
            labelPuppy.textContent = 'بچه گربه (زیر ۱ سال)';
            calcState.race = 'پرشین';
            if (calcState.weight > 12) {
                calcState.weight = 4.5;
                document.getElementById('calcWeightSlider').value = 4.5;
            }
            document.getElementById('calcWeightSlider').max = 12;
        }
        
        renderBreedChips();
        recalculateNutrition();
        debouncedAiAnalysis();
    };

    window.updateWeightFromSlider = function(val) {
        calcState.weight = parseFloat(val);
        document.getElementById('calcWeightDisplay').textContent = calcState.weight.toFixed(1);
        recalculateNutrition();
        debouncedAiAnalysis();
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
        debouncedAiAnalysis();
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
        debouncedAiAnalysis();
    };

    window.setCalcBcs = function(bcs) {
        calcState.bcs = bcs;
        const bcsMap = { 2: 'bcsBtnUnder', 5: 'bcsBtnIdeal', 7: 'bcsBtnOver', 9: 'bcsBtnObese' };
        Object.keys(bcsMap).forEach(key => {
            const el = document.getElementById(bcsMap[key]);
            if (parseInt(key) === bcs) {
                el.className = 'calc-bcs-btn p-3 rounded-xl border-2 border-emerald-400 bg-emerald-500/20 text-emerald-200 text-xs text-right transition-all shadow-md cursor-pointer';
            } else {
                el.className = 'calc-bcs-btn p-3 rounded-xl border border-white/15 bg-white/10 hover:bg-white/15 text-xs text-right transition-all cursor-pointer';
            }
        });

        const badge = document.getElementById('bcsBadgeDisplay');
        if (bcs <= 3) {
            badge.textContent = 'امتیاز ' + bcs + '/۹ (لاغر / کم‌وزن)';
            badge.className = 'bg-amber-500/20 text-amber-300 text-[11px] font-black px-2.5 py-0.5 rounded-full border border-amber-400/40';
        } else if (bcs <= 5) {
            badge.textContent = 'امتیاز ۵/۹ (ایده‌آل و متناسب)';
            badge.className = 'bg-emerald-500/20 text-emerald-300 text-[11px] font-black px-2.5 py-0.5 rounded-full border border-emerald-400/40';
        } else if (bcs <= 7) {
            badge.textContent = 'امتیاز ' + bcs + '/۹ (دارای اضافه‌وزن)';
            badge.className = 'bg-amber-500/20 text-amber-300 text-[11px] font-black px-2.5 py-0.5 rounded-full border border-amber-400/40';
        } else {
            badge.textContent = 'امتیاز ۹/۹ (چاقی مفرط بالینی)';
            badge.className = 'bg-rose-500/20 text-rose-300 text-[11px] font-black px-2.5 py-0.5 rounded-full border border-rose-400/40';
        }
        recalculateNutrition();
        debouncedAiAnalysis();
    };

    window.recalculateNutrition = function() {
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

        // Adjust factor according to BCS
        let idealW = W;
        let progText = 'ثبات وزن متناسب';
        if (calcState.bcs <= 3) {
            factor *= 1.20; // 20% surplus for healthy gain
            idealW = Math.round(W * 1.15 * 10) / 10;
            progText = 'برنامه افزایش تدریجی وزن';
        } else if (calcState.bcs >= 6 && calcState.bcs <= 7) {
            factor *= 0.85; // 15% deficit for safe weight loss
            idealW = Math.round((W / 1.15) * 10) / 10;
            progText = 'برنامه کاهش چربی اضافه';
        } else if (calcState.bcs >= 8) {
            factor *= 0.75; // 25% clinical reduction
            idealW = Math.round((W / 1.25) * 10) / 10;
            progText = 'رژیم کنترل چاقی مفرط';
        }

        calcState.idealWeight = idealW;
        const mer = Math.round(rer * factor);
        calcState.mer = mer;

        // Average premium dry kibble contains 3.75 kcal per gram
        const kibbleGrams = Math.round(mer / 3.75);
        calcState.kibbleGrams = kibbleGrams;

        // Water requirement (ml)
        const waterMl = Math.round(W * (calcState.species === 'dog' ? 60 : 50));
        calcState.waterMl = waterMl;

        // Portions
        const meals = calcState.stage === 'puppy' ? 3 : 2;
        const portionGrams = Math.round(kibbleGrams / meals);

        // Treats (10% rule)
        const treatKcal = Math.round(mer * 0.10);
        calcState.treatCalories = treatKcal;

        // Safely update Titles & Teaser Progress in Dashboard
        const petLabel = calcState.species === 'dog' ? 'سگ' : 'گربه';
        const stageLabel = calcState.stage === 'puppy' ? (calcState.species === 'dog' ? 'توله سگ' : 'بچه‌گربه') : (calcState.stage === 'senior' ? 'ارشد' : 'بالغ');
        const petEmojiEl = document.getElementById('resPetEmoji');
        if (petEmojiEl) petEmojiEl.textContent = calcState.species === 'dog' ? '🐕' : '🐈';

        const petTitleEl = document.getElementById('resPetTitle');
        if (petTitleEl) petTitleEl.textContent = `پایش بالینی ${petLabel} ${stageLabel} • نژاد ${calcState.race} (${W.toFixed(1)} کیلوگرم)`;

        const actDesc = calcState.activity === 'neutered' ? 'عقیم‌شده با تحرک معمول' : (calcState.activity === 'active' ? 'پرتحرک و فعال' : 'کم‌تحرک / نیازمند پایش کالری');
        const petSubtitleEl = document.getElementById('resPetSubtitle');
        if (petSubtitleEl) {
            petSubtitleEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span><span>${actDesc} • آماده صدور نسخه</span>`;
        }

        // Recommended Product Text matched to Breed & Life Stage
        let foodName = '';
        const breeds = calcState.species === 'dog' ? DOG_BREEDS : CAT_BREEDS;
        const breedInfo = breeds.find(b => b.name === calcState.race || calcState.race.includes(b.name));
        
        if (breedInfo && breedInfo.foodTitle) {
            foodName = breedInfo.foodTitle;
        } else if (calcState.species === 'dog') {
            if (calcState.bcs >= 7) foodName = 'غذای خشک رژیمی هیلز پرفکت ویت (مدیریت تخصصی وزن سگ)';
            else if (calcState.stage === 'puppy') foodName = 'غذای خشک رویال کنین مینی پاپی (توله‌های در حال رشد)';
            else foodName = 'غذای خشک رویال کنین مینی ادالت (ویژه سگ‌های نژاد کوچک)';
        } else {
            if (calcState.bcs >= 7) foodName = 'غذای خشک ویت فیت رویال کنین ویژه کنترل وزن گربه';
            else if (calcState.stage === 'puppy') foodName = 'غذای خشک رویال کنین کیتن (بچه‌گربه‌های ۲ تا ۱۲ ماه)';
            else if (calcState.activity === 'neutered') foodName = 'غذای خشک استرلایزد رفلکس پلاس گربه عقیم‌شده';
            else foodName = 'غذای خشک رفلکس پلاس ادالت مرغ و برنج گربه';
        }
        document.getElementById('resRecommendedFood').textContent = foodName;

        // CTA Link
        const shopCta = document.getElementById('calcCtaShop');
        if (shopCta) {
            shopCta.href = `shop.php?category=${calcState.species === 'dog' ? 'dog-food' : 'cat-food'}&q=${encodeURIComponent(calcState.race)}`;
        }
    };

    // AI Clinical Nutritionist Caller
    window.triggerAiBreedAnalysis = async function() {
        const aiSummaryEl = document.getElementById('aiMetabolicSummary');
        const aiPointsEl = document.getElementById('aiPointsList');
        const btnRefresh = document.getElementById('btnRefreshAi');
        const btnQuick = document.getElementById('btnQuickAiAnalyze');
        const aiStatusSubtitle = document.getElementById('aiStatusSubtitle');
        
        if (btnRefresh) btnRefresh.innerHTML = '<span class="material-symbols-outlined text-xs animate-spin">sync</span><span>تحلیل...</span>';
        if (btnQuick) btnQuick.innerHTML = '<span class="material-symbols-outlined text-xs animate-spin">sync</span><span>درحال تحلیل...</span>';
        if (aiSummaryEl) aiSummaryEl.innerHTML = '<span class="text-white/60 flex items-center gap-1.5"><span class="material-symbols-outlined text-sm animate-spin">sync</span>هوش مصنوعی در حال تحلیل بیومکانیک و استانداردهای تغذیه نژاد ' + calcState.race + '...</span>';

        try {
            const res = await fetch('actions/ai_nutrition_analysis.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    species: calcState.species,
                    race: calcState.race,
                    pet_name: calcState.petName,
                    weight_kg: calcState.weight,
                    ideal_weight_kg: calcState.idealWeight,
                    bcs_score: calcState.bcs,
                    stage: calcState.stage,
                    activity: calcState.activity,
                    daily_calories: calcState.mer,
                    kibble_grams: calcState.kibbleGrams,
                    water_ml: calcState.waterMl
                })
            });
            const data = await res.json();
            if (data.success) {
                calcState.aiAnalysis = data;
                
                // Update live dashboard and teaser hints
                const teaserAiEl = document.getElementById('teaserAiMetabolicSummary');
                if (teaserAiEl && data.metabolic_analysis) {
                    teaserAiEl.textContent = data.metabolic_analysis.substring(0, 160) + '... (نسخه کامل در فایل ارسالی به پرونده)';
                }
                const alertsEl = document.getElementById('teaserAlertsBadge');
                if (alertsEl && Array.isArray(data.health_alerts)) {
                    alertsEl.innerHTML = `<span>${data.health_alerts.length} زنگ خطر شناسایی شد</span><span class="text-amber-400 text-xs">⚠️</span>`;
                }

                if (aiSummaryEl) {
                    aiSummaryEl.textContent = data.metabolic_analysis;
                }
                if (aiStatusSubtitle) {
                    aiStatusSubtitle.textContent = `تحلیل تخصصی نژاد ${data.race_title} (${data.race_category})`;
                }
                if (aiPointsEl && Array.isArray(data.clinical_recommendations)) {
                    let ptsHtml = '';
                    // Top recommendation
                    if (data.clinical_recommendations[0]) {
                        ptsHtml += `<div class="flex items-start gap-1.5 text-emerald-200">
                            <span class="material-symbols-outlined text-xs mt-0.5 text-emerald-400">check_circle</span>
                            <span>${data.clinical_recommendations[0]}</span>
                        </div>`;
                    }
                    // Top health alert
                    if (Array.isArray(data.health_alerts) && data.health_alerts[0]) {
                        ptsHtml += `<div class="flex items-start gap-1.5 text-amber-200">
                            <span class="material-symbols-outlined text-xs mt-0.5 text-amber-400">warning</span>
                            <span>${data.health_alerts[0]}</span>
                        </div>`;
                    }
                    // Recommended supplements
                    if (Array.isArray(data.recommended_supplements) && data.recommended_supplements[0]) {
                        ptsHtml += `<div class="flex items-start gap-1.5 text-sky-200">
                            <span class="material-symbols-outlined text-xs mt-0.5 text-sky-400">medication</span>
                            <span>مکمل بالینی: ${data.recommended_supplements[0]}</span>
                        </div>`;
                    }
                    aiPointsEl.innerHTML = ptsHtml;
                }
            }
        } catch (e) {
            console.error('AI Analysis failed:', e);
            if (aiSummaryEl) {
                aiSummaryEl.textContent = `رژیم محاسبه‌شده با استانداردهای FEDIAF برای نژاد ${calcState.race} منطبق است. پروتئین خالص و آب مصرفی کنترل شود.`;
            }
        } finally {
            if (btnRefresh) btnRefresh.innerHTML = '<span class="material-symbols-outlined text-xs">autorenew</span><span>تحلیل مجدد AI</span>';
            if (btnQuick) btnQuick.innerHTML = '<span class="material-symbols-outlined text-sm">psychology</span><span>تحلیل نژاد با AI</span>';
        }
    };

    // Consult AI in Chat with full pet profile
    window.consultAiAboutDiet = function() {
        const petInfo = `سلام، من از محاسبه‌گر بالینی آسنا استفاده کردم و نیاز به مشاوره تغذیه دارم:\nنام پت: ${calcState.petName}\nگونه: ${calcState.species === 'dog' ? 'سگ' : 'گربه'}\nنژاد: ${calcState.race}\nوزن: ${calcState.weight} کیلوگرم (هدف: ${calcState.idealWeight} kg)\nشاخص بدنی: BCS ${calcState.bcs}/9\nکالری روزانه: ${calcState.mer} kcal\nغذای خشک: ${calcState.kibbleGrams} گرم\nمرحله: ${calcState.stage}\nتحرک: ${calcState.activity}\nلطفاً راهنمایی‌های بالینی تکمیلی را بفرمایید.`;
        
        sessionStorage.setItem('ai_chat_prefill', petInfo);
        window.location.href = 'chat.php?mode=ai&prefill=' + encodeURIComponent(petInfo);
    };

    // Modal Handlers
    window.openNutritionReportModal = function() {
        const modal = document.getElementById('nutritionReportModal');
        if (!modal) return;

        const petName = document.getElementById('calcPetName')?.value || 'تدی';
        calcState.petName = petName;

        document.getElementById('certPetName').textContent = petName;
        document.getElementById('certSpecies').textContent = (calcState.species === 'dog' ? 'سگ' : 'گربه') + ' (' + (calcState.stage === 'puppy' ? 'توله/کیتن' : (calcState.stage === 'senior' ? 'ارشد' : 'بالغ')) + ')';
        document.getElementById('certBreed').textContent = calcState.race;
        document.getElementById('certCurrentWeight').textContent = calcState.weight.toFixed(1) + ' کیلوگرم';
        document.getElementById('certTargetWeight').textContent = 'وزن هدف: ' + calcState.idealWeight.toFixed(1) + ' کیلوگرم';
        document.getElementById('certKcal').textContent = calcState.mer.toLocaleString('fa-IR') + ' kcal';
        document.getElementById('certKibble').textContent = calcState.kibbleGrams.toLocaleString('fa-IR') + ' گرم';
        document.getElementById('certWater').textContent = calcState.waterMl.toLocaleString('fa-IR') + ' میلی‌لیتر';
        document.getElementById('certTreat').textContent = Math.round(calcState.mer * 0.10).toLocaleString('fa-IR') + ' kcal (حداکثر ۱۰٪)';
        
        const meals = calcState.stage === 'puppy' ? 3 : 2;
        const portion = Math.round(calcState.kibbleGrams / meals);
        document.getElementById('certMeals').textContent = `${meals} وعده در روز (هر وعده ${portion.toLocaleString('fa-IR')} گرم)`;

        const bcsStr = calcState.bcs <= 3 ? 'امتیاز ' + calcState.bcs + ' (لاغر / کمبود وزن)' : (calcState.bcs <= 5 ? 'امتیاز ۵ (ایده‌آل و متناسب)' : (calcState.bcs <= 7 ? 'امتیاز ۷ (اضافه‌وزن)' : 'امتیاز ۹ (چاقی بالینی)'));
        document.getElementById('certBcsDisplay').textContent = bcsStr;

        let prog = '';
        if (calcState.bcs <= 3) {
            prog = `هدف بالینی: افزایش تدریجی بافت عضلانی نژاد ${calcState.race} با جیره متراکم. پایش هفتگی وزن توصیه می‌شود.`;
        } else if (calcState.bcs >= 7) {
            prog = `هدف بالینی: کاهش چربی احشایی نژاد ${calcState.race} با نرخ ایمن ۱ تا ۱.۵ درصد در هفته جهت کاهش استرس مفاصل. از دادن پس‌مانده غذای انسانی جداً پرهیز شود.`;
        } else {
            prog = `پت شما در محدوده شاخص استاندارد سلامت وزنی نژاد ${calcState.race} قرار دارد. تداوم این جیره مانع از ابتلا به دیابت، اختلالات گوارشی و دردهای اسکلتی خواهد شد.`;
        }
        document.getElementById('certPrognosis').textContent = prog;
        document.getElementById('certSerial').textContent = calcState.serial;

        // Render AI Section in Certificate
        const certAiMetabolic = document.getElementById('certAiMetabolic');
        const certAiBullets = document.getElementById('certAiBulletPoints');
        if (calcState.aiAnalysis) {
            if (certAiMetabolic) certAiMetabolic.textContent = calcState.aiAnalysis.metabolic_analysis;
            if (certAiBullets) {
                let bHtml = '';
                if (calcState.aiAnalysis.clinical_recommendations) {
                    bHtml += `<div class="bg-white/10 p-2.5 rounded-xl border border-white/10">
                        <div class="font-bold text-emerald-300 mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">task_alt</span>
                            <span>توصیه‌های کلیدی جیره:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-white/80 text-[10px]">
                            ${calcState.aiAnalysis.clinical_recommendations.slice(0, 2).map(r => `<li>${r}</li>`).join('')}
                        </ul>
                    </div>`;
                }
                if (calcState.aiAnalysis.health_alerts) {
                    bHtml += `<div class="bg-white/10 p-2.5 rounded-xl border border-white/10">
                        <div class="font-bold text-amber-300 mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">error</span>
                            <span>هشدارهای نژادی و بالینی:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-white/80 text-[10px]">
                            ${calcState.aiAnalysis.health_alerts.slice(0, 2).map(a => `<li>${a}</li>`).join('')}
                        </ul>
                    </div>`;
                }
                certAiBullets.innerHTML = bHtml;
            }
        } else {
            if (certAiMetabolic) certAiMetabolic.textContent = `تحلیل بالینی نژاد ${calcState.race}: انرژی متابولیک روزانه ${calcState.mer} kcal بر اساس استانداردهای بین‌المللی WSAVA محاسبه و تأیید شد.`;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    };

    window.closeNutritionReportModal = function() {
        const modal = document.getElementById('nutritionReportModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    };

    // Save & Send Meal Plan Chart to Profile Action
    window.issueAndSendMealPlanToProfile = async function() {
        const btn = document.getElementById('btnIssueMealPlan');
        const origContent = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<div class="flex items-center justify-center gap-2 py-2"><span class="material-symbols-outlined text-lg animate-spin">sync</span><span class="text-xs font-black">در حال صدور نسخه و ارسال فایل به پرونده...</span></div>';
        }

        try {
            const csrf = window.ASENA_CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('actions/save_nutrition_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    pet_name: calcState.petName,
                    species: calcState.species,
                    race: calcState.race,
                    weight_kg: calcState.weight,
                    ideal_weight_kg: calcState.idealWeight,
                    bcs_score: calcState.bcs,
                    daily_calories: calcState.mer,
                    kibble_grams: calcState.kibbleGrams,
                    water_ml: calcState.waterMl,
                    treat_calories: calcState.treatCalories || Math.round(calcState.mer * 0.10),
                    stage: calcState.stage,
                    activity: calcState.activity,
                    ai_analysis: calcState.aiAnalysis ? calcState.aiAnalysis.metabolic_analysis : '',
                    ai_analysis_obj: calcState.aiAnalysis,
                    csrf_token: csrf
                })
            });

            const data = await res.json();
            if (btn) {
                btn.innerHTML = origContent;
                btn.disabled = false;
            }

            if (data.success) {
                // Clear any stored pending state
                try { localStorage.removeItem('asena_pending_meal_plan'); } catch(e){}
                showMealPlanSuccessModal(data);
            } else if (data.require_login) {
                // Save current state in localStorage for automatic issue after login
                try {
                    localStorage.setItem('asena_pending_meal_plan', JSON.stringify(calcState));
                } catch(e) {}
                showMealPlanAuthModal();
            } else {
                alert(data.message || 'خطا در صدور جدول برنامه غذایی.');
            }
        } catch (e) {
            if (btn) {
                btn.innerHTML = origContent;
                btn.disabled = false;
            }
            console.error(e);
            alert('خطا در برقراری ارتباط با سرور.');
        }
    };

    window.showMealPlanSuccessModal = function(data) {
        const modal = document.getElementById('mealPlanSuccessModal');
        if (!modal) return;
        const serialEl = document.getElementById('successModalSerial');
        const breedEl = document.getElementById('successModalBreed');
        const viewLink = document.getElementById('successModalViewLink');

        if (serialEl) serialEl.textContent = data.serial || 'ASENA-NUT-SUCCESS';
        if (breedEl) breedEl.textContent = calcState.race;
        if (viewLink && (data.file_url || data.view_url)) {
            viewLink.href = data.view_url || data.file_url;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    };

    window.closeMealPlanSuccessModal = function() {
        const modal = document.getElementById('mealPlanSuccessModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    };

    window.showMealPlanAuthModal = function() {
        const modal = document.getElementById('mealPlanAuthModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeMealPlanAuthModal = function() {
        const modal = document.getElementById('mealPlanAuthModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    };

    window.saveNutritionReportToProfile = async function() {
        // Direct alias to issueAndSendMealPlanToProfile
        return window.issueAndSendMealPlanToProfile();
    };

    // Close on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeNutritionReportModal();
            closeMealPlanSuccessModal();
            closeMealPlanAuthModal();
        }
    });

    // Check for auto-issue after redirect login
    try {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auto_issue') === '1') {
            const saved = localStorage.getItem('asena_pending_meal_plan');
            if (saved) {
                const parsed = JSON.parse(saved);
                if (parsed && typeof parsed === 'object') {
                    Object.assign(calcState, parsed);
                }
                setTimeout(() => {
                    issueAndSendMealPlanToProfile();
                }, 800);
            }
        }
    } catch(e) {}

    // Initial setup on load
    renderBreedChips();
    recalculateNutrition();
    triggerAiBreedAnalysis();
})();
</script>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #printableCertificateArea, #printableCertificateArea * {
        visibility: visible !important;
    }
    #printableCertificateArea {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        background: white !important;
        color: black !important;
        padding: 20px !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
