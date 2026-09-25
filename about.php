<?php
/**
 * ASENA Enterprise - About Page (صفحه رسمی معرفی آسنا، وجوه تمایز، مجوزها و شرکای فناوری)
 * High-end bespoke design showcasing the ASENA difference, ecosystem pillars, 
 * strategic technology partnership with Sama Shahr Khavaran, and official accreditations.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'درباره آسنا | تمایز بنیادین در زیست‌بوم سلامت و خدمات هوشمند پت';
$page_description = 'آسنا تنها یک پت‌شاپ نیست؛ زیست‌بوم جامع سلامت پت با پرونده ابری، داروخانه زنجیره سرد، نظارت هوشمند و پشتیبانی فنی مهندسین مشاور سما شهر خاوران.';

require_once 'includes/header.php';
?>

<style>
/* Scoped Luxury Aesthetics for About Page */
.about-hero-mesh {
    background: radial-gradient(circle at 15% 20%, rgba(253, 129, 0, 0.18) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(56, 189, 248, 0.16) 0%, transparent 50%),
                linear-gradient(135deg, #000c24 0%, #001a48 50%, #002d72 100%);
}

.about-glass-card {
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
}

.about-glass-card-dark {
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.glow-pill {
    box-shadow: 0 0 25px -4px rgba(253, 129, 0, 0.35);
}

.matrix-row:hover {
    background-color: rgba(248, 250, 252, 0.85);
}

.diff-badge-active {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(5, 150, 105, 0.2) 100%);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.diff-badge-inactive {
    background: rgba(241, 245, 249, 0.8);
    color: #64748b;
    border: 1px solid rgba(203, 213, 225, 0.6);
}

/* Subtle Animated Mesh Orb */
@keyframes floatSlow {
    0%, 100% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-10px) scale(1.03); }
}
.animate-float {
    animation: floatSlow 6s ease-in-out infinite;
}
</style>

<div class="min-h-screen bg-slate-50/70 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-16">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <a href="index.php" class="hover:text-primary transition-colors inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">home</span>
                <span>صفحه اصلی</span>
            </a>
            <span class="material-symbols-outlined text-xs text-slate-400">chevron_left</span>
            <span class="text-primary font-bold">درباره آسنا و تمایزهای پلتفرم</span>
        </nav>

        <!-- 1. HERO AUTHORITY ZONE (Above-The-Fold Distinction Arena) -->
        <section class="about-hero-mesh text-white rounded-3xl p-8 sm:p-14 shadow-2xl relative overflow-hidden border border-white/10">
            <!-- Ambient Background Glows -->
            <div class="absolute -left-20 -top-20 w-96 h-96 bg-[#fd8100]/15 rounded-full blur-3xl pointer-events-none animate-float"></div>
            <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 max-w-4xl space-y-7">
                
                <!-- Radiant Brand Tag -->
                <div class="inline-flex items-center gap-2.5 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-1.5 rounded-full shadow-xs">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#fd8100] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#fd8100]"></span>
                    </span>
                    <span class="text-xs font-bold text-amber-300">تمایز در استانداردهای سلامت حیوانات خانگی</span>
                    <span class="text-white/40 text-[10px] hidden sm:inline">•</span>
                    <span class="text-slate-300 text-[11px] font-mono hidden sm:inline">The ASENA Standard</span>
                </div>

                <!-- Main Outcome-Driven Headline -->
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black leading-tight sm:leading-tight text-white tracking-tight">
                    آسنا چیست و چرا با تمام پلتفرم‌های سنتی متفاوت است؟
                </h1>

                <!-- Core Distinction Narrative -->
                <p class="text-slate-200 text-sm sm:text-lg leading-relaxed sm:leading-loose font-normal max-w-3xl">
                    ما آسنا را ساختیم تا مراقبت از پت‌ها را از یک تجارت سنتی و پراکنده به یک <strong class="text-white font-black">زیست‌بوم جامع، مطمئن و دانش‌بنیان</strong> ارتقا دهیم. آسنا پیوندگاه درمان تخصصی دامپزشکی، داروخانه با کنترل زنجیره سرد، نظارت دارویی هوشمند و پشتوانه مهندسی ژئوانفورماتیک است تا سرپرستان حیوانات خانگی در سراسر ایران دغدغه‌ای جز عشق ورزیدن به همراهان باوفای خود نداشته باشند.
                </p>

                <!-- High-Impact Key Metrics Bento Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 pt-4">
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/15 text-center sm:text-right space-y-1 hover:bg-white/15 transition-colors">
                        <div class="text-2xl sm:text-3xl font-black text-amber-300 font-mono">۲۵,۰۰۰+</div>
                        <div class="text-[11px] sm:text-xs text-slate-300 font-medium">سرپرست پت و پرونده سلامت</div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/15 text-center sm:text-right space-y-1 hover:bg-white/15 transition-colors">
                        <div class="text-2xl sm:text-3xl font-black text-emerald-300 font-mono">۷ روز</div>
                        <div class="text-[11px] sm:text-xs text-slate-300 font-medium">حساب امانی و تضمین سلامت</div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/15 text-center sm:text-right space-y-1 hover:bg-white/15 transition-colors">
                        <div class="text-2xl sm:text-3xl font-black text-sky-300 font-mono">۱۰۰٪</div>
                        <div class="text-[11px] sm:text-xs text-slate-300 font-medium">پایش زنجیره سرد و اصالت دارو</div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/15 text-center sm:text-right space-y-1 hover:bg-white/15 transition-colors">
                        <div class="text-2xl sm:text-3xl font-black text-violet-300 font-mono">۱۸۰+</div>
                        <div class="text-[11px] sm:text-xs text-slate-300 font-medium">مرکز درمانی و کلینیک همکار</div>
                    </div>
                </div>

            </div>
        </section>

        <!-- 2. THE 4 PILLARS OF OUR UNFAIR ADVANTAGE (وجوه چهارگانه تمایز آسنا) -->
        <section class="space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-black text-[#fd8100] uppercase tracking-wider">
                    <span class="material-symbols-outlined text-sm">stars</span>
                    تمایزهای بنیادین
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    چرا آسنا یک انتخاب متفاوت و بی‌رقیب است؟
                </h2>
                <p class="text-xs sm:text-sm text-slate-600">
                    چهار رکن فناورانه و اخلاق‌مدار که آسنا را از فروشگاه‌ها و وب‌سایت‌های سنتی کاملاً متمایز می‌سازد.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Pillar 1: Integrated Health Loop -->
                <div class="about-glass-card rounded-3xl p-7 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-200/90 group flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors duration-300">
                            <span class="material-symbols-outlined text-3xl">medical_services</span>
                        </div>
                        <h3 class="font-black text-slate-900 text-base leading-snug">
                            یکپارچگی درمان و تغذیه
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            در آسنا، پرونده پزشکی، سوابق واکسیناسیون، نوبت دکتر دامپزشک، نسخه دارویی و ملزومات تغذیه‌ای در یک پروفایل ابری واحد به هم متصل هستند؛ بدون سردرگمی و بدون اسناد پراکنده.
                        </p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center gap-1.5 text-[11px] font-bold text-primary">
                        <span class="material-symbols-outlined text-sm text-[#fd8100]">check_circle</span>
                        <span>پرونده سلامت ابری با QR کد اختصاصی</span>
                    </div>
                </div>

                <!-- Pillar 2: 7-Day Escrow Buyer Protection -->
                <div class="about-glass-card rounded-3xl p-7 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-200/90 group flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-2xl bg-amber-50 text-[#fd8100] flex items-center justify-center group-hover:bg-[#fd8100] group-hover:text-white transition-colors duration-300">
                            <span class="material-symbols-outlined text-3xl">verified_user</span>
                        </div>
                        <h3 class="font-black text-slate-900 text-base leading-snug">
                            حساب امانی و تضمین سلامت ۷ روزه
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            برای نخستین بار در ایران، وجوه پرداختی مشتریان تا ۷ روز کامل پس از دریافت کالا در حساب امانی امن آسنا نگهداری می‌شود و تنها پس از اطمینان از سلامت کالا و رضایت سرپرست با فروشنده تسویه می‌گردد.
                        </p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center gap-1.5 text-[11px] font-bold text-amber-700">
                        <span class="material-symbols-outlined text-sm text-[#fd8100]">lock</span>
                        <span>امنیت ۱۰۰٪ واریزها از طریق شاپرک</span>
                    </div>
                </div>

                <!-- Pillar 3: AI Clinical Pharmacovigilance & Allergy Sentinel -->
                <div class="about-glass-card rounded-3xl p-7 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-200/90 group flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center group-hover:bg-emerald-700 group-hover:text-white transition-colors duration-300">
                            <span class="material-symbols-outlined text-3xl">psychology</span>
                        </div>
                        <h3 class="font-black text-slate-900 text-base leading-snug">
                            سنتینل بالینی و پایش تداخلات
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            موتور پایش بالینی آسنا به صورت خودکار تداخلات دارویی، حساسیت‌های نژادی و دوز تغذیه روزانه را بر اساس استانداردهای جهانی FEDIAF و WSAVA پیش از خرید هشدار می‌دهد تا جان پت به خطر نیفتد.
                        </p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center gap-1.5 text-[11px] font-bold text-emerald-800">
                        <span class="material-symbols-outlined text-sm text-emerald-600">health_and_safety</span>
                        <span>پیشگیری فعال از مسمومیت‌های دارویی</span>
                    </div>
                </div>

                <!-- Pillar 4: Cold-Chain Logistics & Autoship -->
                <div class="about-glass-card rounded-3xl p-7 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-200/90 group flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center group-hover:bg-indigo-700 group-hover:text-white transition-colors duration-300">
                            <span class="material-symbols-outlined text-3xl">ac_unit</span>
                        </div>
                        <h3 class="font-black text-slate-900 text-base leading-snug">
                            زنجیره سرد و تحویل ادواری خودکار
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            داروهای بیولوژیک و واکسن‌ها با بسته‌بندی عایق ۲ تا ۸ درجه سانتی‌گراد حمل می‌شوند. همچنین سرپرستان می‌توانند غذا و دارو را در سرویس اتوشیپ با تخفیف دائمی و تحویل خودکار ماهانه ثبت نمایند.
                        </p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center gap-1.5 text-[11px] font-bold text-indigo-800">
                        <span class="material-symbols-outlined text-sm text-indigo-600">all_inclusive</span>
                        <span>سرویس اشتراک دوره‌ای Chewy Model</span>
                    </div>
                </div>

            </div>
        </section>

        <!-- 3. INTERACTIVE DISTINCTION MATRIX (مقایسه شفاف آسنا با خرید سنتی) -->
        <section class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden p-6 sm:p-10 space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="space-y-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-black text-primary">
                        <span class="material-symbols-outlined text-sm text-[#fd8100]">compare_arrows</span>
                        ماتریس شفافیت
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        آسنا در برابر فروشگاه‌ها و کانال‌های سنتی بازار
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600">
                        چرا خرید و دریافت خدمات از آسنا بالاترین سطح امنیت بالینی و مالی را به همراه دارد؟
                    </p>
                </div>
                <div class="shrink-0">
                    <span class="text-[11px] text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl font-medium inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs text-emerald-600">verified</span>
                        استانداردهای تضمین‌شده آسنا
                    </span>
                </div>
            </div>

            <!-- Responsive Comparison Table -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-right border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-800">
                            <th class="p-4 sm:p-5 font-black w-2/5">معیار و استاندارد ارزیابی</th>
                            <th class="p-4 sm:p-5 font-black text-primary bg-blue-50/60 w-3/10 border-r border-l border-slate-200">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#fd8100]"></span>
                                    <span>زیست‌بوم جامع آسنا</span>
                                </div>
                            </th>
                            <th class="p-4 sm:p-5 font-bold text-slate-500 w-3/10">پت‌شاپ‌ها و کانال‌های سنتی</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr class="matrix-row transition-colors">
                            <td class="p-4 sm:p-5 font-bold text-slate-900">
                                <div>پرونده الکترونیک سلامت و سوابق بالینی</div>
                                <div class="text-[11px] text-slate-500 font-normal">دسترسی دائم به نسخه، سوابق درمان و واکسیناسیون</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold diff-badge-active px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    پرونده ابری متمرکز + QR کد استعلام
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 diff-badge-inactive px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    دفترچه کاغذی مفقود یا نامشخص
                                </span>
                            </td>
                        </tr>

                        <tr class="matrix-row transition-colors">
                            <td class="p-4 sm:p-5 font-bold text-slate-900">
                                <div>ضمانت مالی و استرداد وجه (Escrow)</div>
                                <div class="text-[11px] text-slate-500 font-normal">حفظ امنیت وجه تا زمان اطمینان از سلامت کالا</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold diff-badge-active px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    حساب امانی ۷ روزه نزد پلتفرم
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 diff-badge-inactive px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    واریز کارت‌به‌کارت بدون تضمین استرداد
                                </span>
                            </td>
                        </tr>

                        <tr class="matrix-row transition-colors">
                            <td class="p-4 sm:p-5 font-bold text-slate-900">
                                <div>پایش تداخلات دارویی و منع مصرف غذایی</div>
                                <div class="text-[11px] text-slate-500 font-normal">بررسی سازگاری همزمان داروها با مکمل و غذا</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold diff-badge-active px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    موتور خودکار تداخلات فارماکولوژی + AI
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 diff-badge-inactive px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    عرضه بدون استعلام و خطای بالینی
                                </span>
                            </td>
                        </tr>

                        <tr class="matrix-row transition-colors">
                            <td class="p-4 sm:p-5 font-bold text-slate-900">
                                <div>ارسال واکسن و داروهای بیولوژیک</div>
                                <div class="text-[11px] text-slate-500 font-normal">کنترل استاندارد دما جهت حفظ اثر دارویی</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold diff-badge-active px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    پروتکل رسمی زنجیره سرد (Cold-Chain)
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 diff-badge-inactive px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    پست معمولی و فساد واکسن در گرما
                                </span>
                            </td>
                        </tr>

                        <tr class="matrix-row transition-colors">
                            <td class="p-4 sm:p-5 font-bold text-slate-900">
                                <div>زیرساخت مکانی اورژانس و دایرکتوری GIS</div>
                                <div class="text-[11px] text-slate-500 font-normal">مسیریابی هوشمند به نزدیک‌ترین مرکز کشیک شبانه‌روزی</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold diff-badge-active px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    پشتیبانی داده مکانی مهندسین سما شهر
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 diff-badge-inactive px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    آدرس‌های قدیمی و عدم اطلاع از کشیک
                                </span>
                            </td>
                        </tr>

                        <tr class="matrix-row transition-colors">
                            <td class="p-4 sm:p-5 font-bold text-slate-900">
                                <div>مسئولیت اجتماعی و امداد حیوانات بی‌سرپرست</div>
                                <div class="text-[11px] text-slate-500 font-normal">تخصیص شفاف درآمد به واکسیناسیون پناهگاه‌ها</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold diff-badge-active px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    تخصیص سیستمی به پویش‌های امداد
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 diff-badge-inactive px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    انگیزه تجاری محض بدون برنامه حمایتی
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 4. STRATEGIC TECHNOLOGY & GEOSPATIAL PARTNER (شرکت مهندسین مشاور سما شهر خاوران) -->
        <section class="bg-gradient-to-br from-slate-900 via-[#001a48] to-[#002d72] text-white rounded-3xl p-8 sm:p-12 shadow-xl border border-blue-900/50 relative overflow-hidden space-y-10">
            <!-- Background Decorative Grid Lines -->
            <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 24px 24px;"></div>
            
            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-8 border-b border-white/10">
                <div class="space-y-3 max-w-2xl">
                    <div class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-300 border border-blue-400/30 px-3.5 py-1.5 rounded-full text-xs font-bold">
                        <span class="material-symbols-outlined text-sm text-[#fd8100]">hub</span>
                        <span>پشتوانه مهندسی ژئوانفورماتیک و پایداری زیرساخت فناوری</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        هم‌پیمانی راهبردی با شرکت مهندسین مشاور «سما شهر خاوران»
                    </h2>
                    <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                        سامانه جامع آسنا جهت تضمین پایداری خدمات کلان، پایش داده‌های مکانی مراکز درمانی کشور (GIS) و ارتقای تاب‌آوری سیستم‌های ابری، از تخصص و همراهی مهندسی شرکت مهندسین مشاور سما شهر خاوران بهره‌مند است.
                    </p>
                </div>

                <div class="shrink-0 flex items-center">
                    <a href="https://samashahr.ir/" target="_blank" rel="noopener noreferrer" class="group inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl bg-white text-slate-900 hover:bg-slate-100 shadow-lg hover:shadow-xl transition-all text-xs font-black">
                        <span>مشاهده تارنمای رسمی سما شهر</span>
                        <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 group-hover:-translate-y-1 transition-transform text-[#fd8100]">north_east</span>
                    </a>
                </div>
            </div>

            <!-- Bento Layout: Enterprise Partner Moat -->
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                
                <!-- Left 7 Cols: The Engineering Value Add -->
                <div class="lg:col-span-7 bg-white/5 backdrop-blur-md rounded-2xl p-6 sm:p-8 border border-white/10 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-14 h-14 rounded-2xl bg-white p-2.5 flex items-center justify-center shadow-md shrink-0">
                                    <img src="assets/images/partners/samashahr-logo.png" alt="شرکت مهندسین مشاور سما شهر خاوران" class="w-full h-auto object-contain">
                                </div>
                                <div>
                                    <h3 class="font-black text-white text-base sm:text-lg">شرکت مهندسین مشاور سما شهر خاوران</h3>
                                    <div class="text-[11px] text-slate-300 font-mono">شماره ثبت: ۵۶۳۰۶ تبریز • شناسه ملی: ۱۴۰۱۱۴۲۵۵۷۸</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 text-[10px] font-bold px-2.5 py-1 rounded-full shrink-0">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                <span>شریک فناوری</span>
                            </span>
                        </div>

                        <p class="text-xs text-slate-300 leading-relaxed font-light">
                            شرکت مهندسین مشاور سما شهر خاوران با سال‌ها پیشینه درخشان در طراحی سیستم‌های جامع اطلاعات جغرافیایی (GIS)، شهرسازی، طرح‌های آمایش سرزمین و معماری داده‌های سازمانی مقیاس‌پذیر فعالیت دارد. در این همکاری راهبردی، مهندسی مکان‌محور کلینیک‌ها و مسیریابی دسترسی اورژانسی با نظارت و استانداردهای این مجموعه پیاده‌سازی شده است.
                        </p>
                    </div>

                    <!-- 3 Synergy Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 text-right space-y-1 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-1.5 text-amber-300">
                                <span class="material-symbols-outlined text-base">map</span>
                                <span class="text-xs font-bold text-white">تحلیل مکانی GIS</span>
                            </div>
                            <p class="text-[11px] text-slate-300 leading-tight">پایش شعاع پوشش و تراکم کلینیک‌ها در شهرهای کشور</p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 text-right space-y-1 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-1.5 text-sky-300">
                                <span class="material-symbols-outlined text-base">cloud_sync</span>
                                <span class="text-xs font-bold text-white">تاب‌آوری کلاود</span>
                            </div>
                            <p class="text-[11px] text-slate-300 leading-tight">نظارت بر تعادل بار سرورها و آپ‌تایم بالای ۹۹.۹٪</p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 text-right space-y-1 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-1.5 text-emerald-300">
                                <span class="material-symbols-outlined text-base">gavel</span>
                                <span class="text-xs font-bold text-white">انطباق صنفی</span>
                            </div>
                            <p class="text-[11px] text-slate-300 leading-tight">رعایت موازین سازمان نظام صنفی رایانه‌ای کشور</p>
                        </div>
                    </div>
                </div>

                <!-- Right 5 Cols: Official Computer Guild Accreditation -->
                <div class="lg:col-span-5 bg-white/5 backdrop-blur-md rounded-2xl p-6 sm:p-8 border border-white/10 flex flex-col justify-between space-y-5">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-amber-300 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base text-[#fd8100]">badge</span>
                                گواهی سازمان نظام صنفی رایانه‌ای کشور
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">مجوز: ۱۴۰۱۰۶۰۸</span>
                        </div>
                        <p class="text-[11px] text-slate-300 leading-relaxed font-light">
                            پروانه رسمی عضویت و فعالیت معتبر در سازمان نظام صنفی رایانه‌ای استان آذربایجان شرقی به مدیریت مهندس جمال مهرزاد.
                        </p>
                    </div>

                    <!-- Certificate Interactive Card -->
                    <div class="relative group cursor-pointer overflow-hidden rounded-xl border border-white/20 bg-slate-800 shadow-md hover:border-amber-400 transition-all" onclick="openLicenseModal()">
                        <img src="assets/images/partners/samashahr-license.jpg" alt="گواهی رسمی نظام صنفی رایانه‌ای شرکت سما شهر خاوران" class="w-full h-40 object-cover object-top group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent flex items-end p-4">
                            <div class="flex items-center justify-between w-full text-white">
                                <span class="text-xs font-bold flex items-center gap-1.5 text-amber-300">
                                    <span class="material-symbols-outlined text-base">zoom_in</span>
                                    مشاهده گواهی و استعلام رسمی
                                </span>
                                <span class="text-[10px] bg-white/20 backdrop-blur-xs px-2.5 py-0.5 rounded-full font-mono">۱۴۰۱۰۶۰۸</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1 text-[11px] text-slate-400">
                        <span>اعتبار عضویت: ۱۴۰۶/۰۴/۰۱</span>
                        <button type="button" onclick="openLicenseModal()" class="text-amber-300 hover:text-white font-bold inline-flex items-center gap-1 transition-colors">
                            <span>بزرگ‌نمایی گواهینامه</span>
                            <span class="material-symbols-outlined text-xs">open_in_full</span>
                        </button>
                    </div>
                </div>

            </div>
        </section>

        <!-- 5. SOCIAL RESPONSIBILITY & CHARITY PROMISE (مسئولیت اجتماعی و امداد) -->
        <section class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-8 sm:p-10 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-3xl">volunteer_activism</span>
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            مسئولیت اجتماعی؛ سهمی برای فرشتگان بی‌پناه
                        </h2>
                        <div class="text-xs text-slate-500 font-medium">بخشی از هر تراکنش در آسنا، وقف درمان و واکسیناسیون حیوانات پناهگاه‌ها می‌شود.</div>
                    </div>
                </div>
                <div class="shrink-0">
                    <a href="charity.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition-colors">
                        <span class="material-symbols-outlined text-sm">favorite</span>
                        <span>مشاهده پویش‌های درمانی فعال</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs text-slate-600 leading-relaxed">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-1.5">
                    <span class="font-bold text-slate-900 block text-xs">واکسیناسیون رایگان پناهگاه‌ها</span>
                    <p class="text-[11px] text-slate-500">تامین واکسن‌های هاری و چندگانه برای مراکز نگهداری حیوانات بی‌پناه تحت نظارت پزشکان معتمد آسنا.</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-1.5">
                    <span class="font-bold text-slate-900 block text-xs">صندوق امداد جراحی و تروما</span>
                    <p class="text-[11px] text-slate-500">پوشش هزینه جراحی‌های اورژانسی سگ‌ها و گربه‌های خیابانی تصادفی بدون صاحب با همکاری کلینیک‌های همکار.</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-1.5">
                    <span class="font-bold text-slate-900 block text-xs">شفافیت کامل گزارش‌های مالی</span>
                    <p class="text-[11px] text-slate-500">انتشار فاکتورهای بالینی و فیلم بهبودی بیماران در بخش شفافیت خیریه جهت اعتمادسازی ۱۰۰٪ نیکوکاران.</p>
                </div>
            </div>
        </section>

        <!-- 6. LEGAL TRUST, STATUTORY COMPLIANCE & CONTACT (شفافیت قانونی و ارتباط) -->
        <section class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-8 sm:p-10 space-y-6">
            <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">gavel</span>
                شفافیت قانونی، الزامات نظارتی و درگاه رسمی شاپرک
            </h2>
            <div class="space-y-4 text-xs sm:text-sm text-slate-600 leading-relaxed">
                <p>
                    سامانه جامع آسنا بر مبنای قوانین جاری کشور، قانون تجارت الکترونیک، و تحت نظارت مرکز توسعه تجارت الکترونیکی (اینماد) فعالیت می‌نماید. کلیه تراکنش‌های مالی پلتفرم از طریق درگاه‌های پرداخت متصل به شاپرک بانک مرکزی جمهوری اسلامی ایران صورت گرفته و تسویه‌های بین‌بانکی با بهره‌گیری از بستر امن شبکه پایا و ساتنا مدیریت می‌شوند.
                </p>
                
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-5">
                    <div class="space-y-1 text-center sm:text-right">
                        <span class="font-bold text-slate-900 block text-xs sm:text-sm">پرسش، پیشنهاد همکاری یا نیاز به استعلام رسمی دارید؟</span>
                        <span class="text-slate-500 text-[11px] sm:text-xs">تیم پشتیبانی، روابط عمومی و امور حقوقی آسنا در ساعات کاری همه روزه پاسخگوی شماست.</span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <a href="contact.php" class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs shadow-sm hover:bg-primary-light transition flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">contact_support</span>
                            <span>صفحه تماس و پشتیبانی</span>
                        </a>
                        <a href="tel:09146676978" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 font-bold text-xs transition flex items-center gap-1.5 font-mono">
                            <span class="material-symbols-outlined text-sm text-[#fd8100]">call</span>
                            <span>09146676978</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>

<!-- License Lightbox Modal (Ultra-Smooth Interactive Preview) -->
<div id="licenseModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-md p-4 transition-all duration-300 opacity-0 pointer-events-none" onclick="closeLicenseModal(event)">
    <div class="relative bg-white rounded-3xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl space-y-4 border border-slate-100 transform scale-95 transition-transform duration-300" onclick="event.stopPropagation()">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">verified</span>
                <h3 class="font-black text-slate-900 text-sm sm:text-base">گواهی رسمی سازمان نظام صنفی رایانه‌ای - شرکت سما شهر خاوران</h3>
            </div>
            <button type="button" onclick="closeLicenseModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors" title="بستن پنجره">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>

        <div class="overflow-auto max-h-[75vh] flex justify-center rounded-2xl bg-slate-50 p-2 border border-slate-100">
            <img src="assets/images/partners/samashahr-license.jpg" alt="گواهی نظام صنفی رایانه‌ای شرکت مهندسین مشاور سما شهر خاوران" class="w-full h-auto object-contain rounded-xl shadow-xs">
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 pt-1">
            <span>شماره پروانه نظام صنفی: <strong class="font-mono text-slate-800">14010608</strong> • شناسه ملی: <strong class="font-mono text-slate-800">14011425578</strong> • شماره ثبت: <strong class="font-mono text-slate-800">56306 تبریز</strong></span>
            <a href="https://samashahr.ir/" target="_blank" rel="noopener noreferrer" class="text-primary font-bold hover:underline inline-flex items-center gap-1">
                <span>وب‌سایت رسمی سما شهر خاوران</span>
                <span class="material-symbols-outlined text-xs">open_in_new</span>
            </a>
        </div>
    </div>
</div>

<script>
function openLicenseModal() {
    const modal = document.getElementById('licenseModal');
    if (!modal) return;
    modal.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
    modal.classList.add('flex', 'opacity-100', 'pointer-events-auto');
    const panel = modal.querySelector('div');
    if (panel) {
        panel.classList.remove('scale-95');
        panel.classList.add('scale-100');
    }
}

function closeLicenseModal(e) {
    const modal = document.getElementById('licenseModal');
    if (!modal) return;
    modal.classList.remove('opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 200);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLicenseModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
