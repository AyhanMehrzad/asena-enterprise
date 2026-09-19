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

                <!-- 2. Weight Slider -->
                <div class="space-y-2.5 bg-white/5 p-5 rounded-2xl border border-white/10">
                    <div class="flex justify-between items-center">
                        <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۲</span>
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

                <!-- 5. Body Condition Score (BCS 1 to 9 & BMI) -->
                <div class="space-y-2.5 bg-white/5 p-5 rounded-2xl border border-white/10">
                    <div class="flex justify-between items-center">
                        <label class="text-xs sm:text-sm font-bold text-white/90 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-white/15 flex items-center justify-center text-xs">۵</span>
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

                <!-- 6. Personalized Pet Name (Optional for Official Certificate) -->
                <div class="space-y-2 bg-white/5 p-4 rounded-2xl border border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <label for="calcPetName" class="text-xs font-bold text-white/80 flex items-center gap-2 shrink-0">
                        <span class="material-symbols-outlined text-sm text-emerald-400">badge</span>
                        <span>نام پت (جهت درج رسمی در کارنامه بالینی):</span>
                    </label>
                    <input type="text" id="calcPetName" value="تدی" placeholder="مثال: لوسی، تدی، میلو..." oninput="calcState.petName = this.value || 'پت من'; recalculateNutrition();" class="w-full sm:w-48 bg-white/10 border border-white/20 rounded-xl px-3 py-1.5 text-xs text-white font-bold text-center focus:outline-none focus:border-emerald-400">
                </div>

            </div>

            <!-- Right Column: Live Results Dashboard Card (5 cols) -->
            <div class="lg:col-span-5 flex flex-col justify-between bg-white/10 backdrop-blur-2xl rounded-[2rem] p-6 sm:p-8 border border-white/20 shadow-2xl relative">
                <div class="space-y-5">
                    
                    <div class="flex items-center justify-between border-b border-white/10 pb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="text-3xl" id="resPetEmoji">🐕</span>
                            <div>
                                <h3 class="text-sm font-black text-white" id="resPetTitle">برنامه غذایی سگ بالغ (۸.۵ کیلوگرم)</h3>
                                <p class="text-[11px] text-emerald-300 font-bold" id="resPetSubtitle">عقیم‌شده با وضعیت بدنی ایده‌آل</p>
                            </div>
                        </div>
                        <span class="bg-emerald-500/20 text-emerald-300 text-[10px] font-mono px-2.5 py-1 rounded-full border border-emerald-400/30">
                            استاندارد WSAVA
                        </span>
                    </div>

                    <!-- 6 Metric Widgets Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        
                        <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10">
                            <div class="text-[11px] text-white/70 font-medium mb-1">کالری روزانه (MER):</div>
                            <div class="text-xl sm:text-2xl font-black font-mono text-amber-300" id="resCalories">
                                ۵۴۰ <span class="text-xs font-normal text-white/80 font-sans">kcal</span>
                            </div>
                            <div class="text-[10px] text-white/50 mt-1">انرژی متابولیک خالص</div>
                        </div>

                        <div class="bg-emerald-500/20 p-3.5 rounded-2xl border border-emerald-400/30">
                            <div class="text-[11px] text-emerald-200 font-bold mb-1">غذای خشک روزانه:</div>
                            <div class="text-xl sm:text-2xl font-black font-mono text-emerald-300" id="resKibbleGrams">
                                ۱۴۵ <span class="text-xs font-normal text-white/80 font-sans">گرم</span>
                            </div>
                            <div class="text-[10px] text-emerald-200/80 mt-1 font-bold" id="resMealPortion">۲ وعده ۷۲ گرمی</div>
                        </div>

                        <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10">
                            <div class="text-[11px] text-white/70 font-medium mb-1">آب تازه مصرفی:</div>
                            <div class="text-xl sm:text-2xl font-black font-mono text-sky-300" id="resWaterMl">
                                ۵۱۰ <span class="text-xs font-normal text-white/80 font-sans">میلی‌لیتر</span>
                            </div>
                            <div class="text-[10px] text-white/50 mt-1">حداقل مایعات روزانه</div>
                        </div>

                        <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10">
                            <div class="text-[11px] text-white/70 font-medium mb-1">وزن ایده‌آل هدف:</div>
                            <div class="text-xl sm:text-2xl font-black font-mono text-emerald-400" id="resIdealWeight">
                                ۸.۵ <span class="text-xs font-normal text-white/80 font-sans">کیلوگرم</span>
                            </div>
                            <div class="text-[10px] text-emerald-200/70 mt-1" id="resWeightProg">ثبات وزن متناسب</div>
                        </div>

                        <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10">
                            <div class="text-[11px] text-white/70 font-medium mb-1">سقف مجاز تشویقی:</div>
                            <div class="text-lg sm:text-xl font-black font-mono text-purple-300" id="resTreats">
                                ۵۴ <span class="text-xs font-normal text-white/80 font-sans">کالری (قانون ۱۰٪)</span>
                            </div>
                            <div class="text-[10px] text-white/50 mt-1">حداکثر ۱۵ گرم در روز</div>
                        </div>

                        <div class="bg-white/10 p-3.5 rounded-2xl border border-white/10">
                            <div class="text-[11px] text-white/70 font-medium mb-1">تخفیف تحویل خودکار:</div>
                            <div class="text-xl sm:text-2xl font-black font-mono text-[#fd8100]">
                                ۱۵٪ <span class="text-xs font-normal text-white/80 font-sans">تخفیف دائمی</span>
                            </div>
                            <div class="text-[10px] text-white/50 mt-1">اشتراک اتوشیپ آسنا</div>
                        </div>

                    </div>

                    <!-- Recommended Kibble Match Box -->
                    <div class="bg-gradient-to-r from-emerald-900/40 to-primary/40 p-4 rounded-2xl border border-emerald-400/30 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-2xl shrink-0">
                            🥘
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-black text-white" id="resRecommendedFood">غذای خشک رویال کنین مینی ادالت (ویژه نژاد کوچک)</div>
                            <div class="text-[10px] text-white/70 mt-0.5">فرمول بالینی متناسب با متابولیسم و شاخص وزنی پت شما</div>
                        </div>
                    </div>

                </div>

                <!-- CTA Actions -->
                <div class="pt-5 mt-5 border-t border-white/10 space-y-2.5">
                    <!-- Primary Feature Button: Issue Official Report -->
                    <button type="button" onclick="openNutritionReportModal()" class="w-full bg-gradient-to-r from-[#fd8100] via-amber-500 to-[#ea580c] hover:opacity-95 text-white py-3.5 px-4 rounded-2xl font-black text-xs sm:text-sm text-center shadow-xl shadow-orange-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                        <span class="material-symbols-outlined text-lg">clinical_notes</span>
                        <span>صدور کارنامه و شناسنامه تغذیه بالینی پت (رسمی)</span>
                        <span class="bg-white/25 text-[10px] px-2 py-0.5 rounded-full font-sans">هدیه آسنا</span>
                    </button>

                    <div class="flex flex-col sm:flex-row gap-2.5">
                        <a href="shop.php" id="calcCtaShop" class="flex-1 bg-emerald-400 hover:bg-emerald-300 text-slate-900 py-3 px-3.5 rounded-xl font-black text-xs text-center shadow-md transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-base">shopping_cart</span>
                            <span>خرید غذای متناسب</span>
                        </a>
                        <a href="subscriptions.php" class="bg-white/15 hover:bg-white/25 text-white py-3 px-3.5 rounded-xl font-bold text-xs text-center transition-all flex items-center justify-center gap-1.5 border border-white/20">
                            <span class="material-symbols-outlined text-base">autorenew</span>
                            <span>اشتراک تحویل خودکار</span>
                        </a>
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
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">نام بیمار (پت):</span>
                    <span class="font-black text-slate-800 text-sm" id="certPetName">تدی</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">گونه و رده:</span>
                    <span class="font-bold text-slate-700" id="certSpecies">سگ بالغ</span>
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
            <div class="flex items-center gap-2 w-full sm:w-auto">
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

<!-- Interactive Calculator JavaScript Engine -->
<script>
(function() {
    window.calcState = {
        species: 'dog',
        weight: 8.5,
        stage: 'adult',
        activity: 'neutered',
        bcs: 5,
        petName: 'تدی',
        idealWeight: 8.5,
        mer: 540,
        kibbleGrams: 145,
        waterMl: 510,
        serial: 'ASENA-NUT-' + Math.random().toString(36).substring(2, 8).toUpperCase()
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

        // Update UI elements
        document.getElementById('resCalories').innerHTML = mer.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-white/80 font-sans">kcal</span>';
        document.getElementById('resKibbleGrams').innerHTML = kibbleGrams.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-white/80 font-sans">گرم</span>';
        document.getElementById('resWaterMl').innerHTML = waterMl.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-white/80 font-sans">میلی‌لیتر</span>';
        document.getElementById('resIdealWeight').innerHTML = idealW.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-white/80 font-sans">کیلوگرم</span>';
        document.getElementById('resWeightProg').textContent = progText;
        document.getElementById('resTreats').innerHTML = treatKcal.toLocaleString('fa-IR') + ' <span class="text-xs font-normal text-white/80 font-sans">kcal (قانون ۱۰٪)</span>';
        document.getElementById('resMealPortion').textContent = meals + ' وعده ' + portionGrams.toLocaleString('fa-IR') + ' گرمی';

        // Titles & Recommendations
        const petLabel = calcState.species === 'dog' ? 'سگ' : 'گربه';
        const stageLabel = calcState.stage === 'puppy' ? (calcState.species === 'dog' ? 'توله سگ' : 'بچه‌گربه') : (calcState.stage === 'senior' ? 'ارشد / مسن' : 'بالغ');
        document.getElementById('resPetEmoji').textContent = calcState.species === 'dog' ? '🐕' : '🐈';
        document.getElementById('resPetTitle').textContent = `برنامه غذایی ${petLabel} ${stageLabel} (${W.toFixed(1)} کیلوگرم)`;

        const actDesc = calcState.activity === 'neutered' ? 'عقیم‌شده با تحرک معمول' : (calcState.activity === 'active' ? 'پرتحرک و فعال' : 'کم‌تحرک / نیازمند پایش کالری');
        document.getElementById('resPetSubtitle').textContent = actDesc;

        // Recommended Product Text
        let foodName = '';
        if (calcState.species === 'dog') {
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
            shopCta.href = `shop.php?category=${calcState.species === 'dog' ? 'dog-food' : 'cat-food'}`;
        }
    };

    // Modal Handlers
    window.openNutritionReportModal = function() {
        const modal = document.getElementById('nutritionReportModal');
        if (!modal) return;

        const petName = document.getElementById('calcPetName')?.value || 'تدی';
        calcState.petName = petName;

        document.getElementById('certPetName').textContent = petName;
        document.getElementById('certSpecies').textContent = (calcState.species === 'dog' ? 'سگ' : 'گربه') + ' (' + (calcState.stage === 'puppy' ? 'توله/کیتن' : (calcState.stage === 'senior' ? 'ارشد' : 'بالغ')) + ')';
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
            prog = 'هدف: افزایش تدریجی بافت عضلانی با جیره پرکالری. پایش هفتگی وزن توصیه می‌شود.';
        } else if (calcState.bcs >= 7) {
            prog = 'هدف: کاهش چربی احشایی با نرخ ایمن ۱ تا ۱.۵ درصد در هفته. از دادن پس‌مانده غذای انسانی جداً پرهیز شود.';
        } else {
            prog = 'پت شما در محدوده شاخص استاندارد سلامت وزنی قرار دارد. تداوم این جیره مانع از ابتلا به دیابت و دردهای اسکلتی خواهد شد.';
        }
        document.getElementById('certPrognosis').textContent = prog;
        document.getElementById('certSerial').textContent = calcState.serial;

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

    window.saveNutritionReportToProfile = async function() {
        const btn = document.getElementById('btnSaveReport');
        const origContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span><span>در حال ثبت...</span>';

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
                    weight_kg: calcState.weight,
                    ideal_weight_kg: calcState.idealWeight,
                    bcs_score: calcState.bcs,
                    daily_calories: calcState.mer,
                    kibble_grams: calcState.kibbleGrams,
                    water_ml: calcState.waterMl,
                    stage: calcState.stage,
                    activity: calcState.activity,
                    csrf_token: csrf
                })
            });

            const data = await res.json();
            if (data.success) {
                btn.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span><span>در پرونده ثبت شد</span>';
                btn.className = 'px-5 py-2.5 rounded-xl bg-emerald-700 text-white text-xs font-black transition flex items-center justify-center gap-1.5 shadow-md';
                if (typeof showToast === 'function') {
                    showToast(data.message, 'success');
                } else {
                    alert(data.message);
                }
            } else if (data.require_login) {
                btn.innerHTML = origContent;
                btn.disabled = false;
                if (confirm('جهت ذخیره کارنامه در پرونده سلامت، ابتدا باید وارد حساب کاربری خود شوید. آیا مایل به ورود هستید؟')) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent('calculator.php');
                }
            } else {
                btn.innerHTML = origContent;
                btn.disabled = false;
                alert(data.message || 'خطا در ثبت کارنامه.');
            }
        } catch (e) {
            btn.innerHTML = origContent;
            btn.disabled = false;
            console.error(e);
            alert('خطا در برقراری ارتباط با سرور.');
        }
    };

    // Close on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeNutritionReportModal();
    });

    // Initial calculation on load
    recalculateNutrition();
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
