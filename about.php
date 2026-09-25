<?php
/**
 * ASENA Enterprise - Official About Page (درباره آسنا، مجوزهای رسمی، شرکای فناوری و شفافیت قانونی)
 * High-end corporate presentation featuring ecosystem pillars, official licences,
 * strategic technology partnership with Sama Shahr Khavaran Consulting Engineers,
 * and transparent statutory credentials.
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'درباره آسنا | معرفی زیست‌بوم جامع، مجوزهای رسمی و همراهی مهندسین مشاور سما شهر';
$page_description = 'داستان شکل‌گیری آسنا، ارکان سلامت و درمان پت، پروانه سازمان نظام صنفی رایانه‌ای، روزنامه رسمی کشور، اینماد و همراهی راهبردی شرکت مهندسین مشاور سما شهر خاوران.';

require_once 'includes/header.php';
?>

<style>
/* Scoped Enterprise Aesthetics for About Page */
.about-hero-mesh {
    background: radial-gradient(circle at 12% 18%, rgba(253, 129, 0, 0.20) 0%, transparent 45%),
                radial-gradient(circle at 88% 82%, rgba(56, 189, 248, 0.18) 0%, transparent 45%),
                linear-gradient(135deg, #000c24 0%, #001a48 50%, #002d72 100%);
}

.about-glass-panel {
    background: rgba(255, 255, 255, 0.90);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
}

.license-tab-btn.active {
    background-color: #001a48;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(0, 26, 72, 0.2);
}

.license-tab-btn {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.matrix-row:hover {
    background-color: rgba(248, 250, 252, 0.9);
}

/* Subtle Animated Mesh Orb */
@keyframes floatSubtle {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}
.animate-subtle {
    animation: floatSubtle 6s ease-in-out infinite;
}
</style>

<div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-16">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <a href="index.php" class="hover:text-primary transition-colors inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">home</span>
                <span>صفحه اصلی</span>
            </a>
            <span class="material-symbols-outlined text-xs text-slate-400">chevron_left</span>
            <span class="text-primary font-bold">درباره آسنا و شفافیت سازمانی</span>
        </nav>

        <!-- 1. HERO AUTHORITY ZONE (Executive Enterprise Presentation) -->
        <section class="about-hero-mesh text-white rounded-3xl p-8 sm:p-12 lg:p-14 shadow-2xl relative overflow-hidden border border-white/10">
            <!-- Radiant Background Glows -->
            <div class="absolute -left-20 -top-20 w-96 h-96 bg-[#fd8100]/20 rounded-full blur-3xl pointer-events-none animate-subtle"></div>
            <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 max-w-4xl space-y-6">
                
                <!-- Pill Tag -->
                <div class="inline-flex items-center gap-2.5 bg-white/10 backdrop-blur-md border border-white/20 px-4 py-1.5 rounded-full text-xs font-bold text-amber-300 shadow-xs">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#fd8100] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#fd8100]"></span>
                    </span>
                    <span>زیست‌بوم جامع سلامت و خدمات هوشمند حیوانات خانگی</span>
                    <span class="text-white/40">•</span>
                    <span class="font-mono text-slate-200">ASENA Enterprise</span>
                </div>

                <!-- Main Outcome-Driven Headline -->
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black leading-tight sm:leading-tight text-white tracking-tight">
                    آسنا؛ تلفیق تخصص سلامت با نوآوری و مهندسی داده
                </h1>

                <!-- Mission & Core Narrative -->
                <p class="text-slate-200 text-sm sm:text-base lg:text-lg leading-relaxed sm:leading-loose font-normal max-w-3xl">
                    ما آسنا را با این باور بنیادین بنا نهادیم که مراقبت از حیوانات خانگی، نیازمند یک <strong class="text-white font-bold">زیست‌بوم علمی، یکپارچه و قانون‌مدار</strong> است. آسنا پیوندگاه خدمات کلینیک‌های تخصصی دامپزشکی، داروخانه با کنترل زنجیره سرد، نظارت هوشمند دارویی و زیرساخت مهندسی ژئوانفورماتیک است تا سرپرستان پت در سراسر کشور با آرامش خاطر به درمان، تغذیه و سلامت همراهان خود بپردازند.
                </p>

                <!-- Action Links -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="#licences-section" class="px-6 py-3 rounded-2xl bg-[#fd8100] hover:bg-[#e07200] text-white font-black text-xs shadow-lg hover:shadow-xl transition-all flex items-center gap-2 group">
                        <span class="material-symbols-outlined text-base">verified</span>
                        <span>مشاهده مجوزها و اسناد رسمی</span>
                        <span class="material-symbols-outlined text-sm group-hover:translate-x-0.5 transition-transform">expand_more</span>
                    </a>
                    <a href="#samashahr-section" class="px-5 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-base text-amber-300">hub</span>
                        <span>همکاری با مهندسین مشاور سما شهر</span>
                    </a>
                </div>

                <!-- Key Metrics Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 pt-4 border-t border-white/10">
                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-white/10 text-center sm:text-right space-y-0.5">
                        <div class="text-2xl sm:text-3xl font-black text-amber-300 font-mono">۲۵,۰۰۰+</div>
                        <div class="text-[11px] sm:text-xs text-slate-300">پرونده سلامت ابری</div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-white/10 text-center sm:text-right space-y-0.5">
                        <div class="text-2xl sm:text-3xl font-black text-emerald-300 font-mono">۷ روز</div>
                        <div class="text-[11px] sm:text-xs text-slate-300">حساب امانی شاپرک</div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-white/10 text-center sm:text-right space-y-0.5">
                        <div class="text-2xl sm:text-3xl font-black text-sky-300 font-mono">۱۰۰٪</div>
                        <div class="text-[11px] sm:text-xs text-slate-300">پایش زنجیره سرد دارو</div>
                    </div>
                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-white/10 text-center sm:text-right space-y-0.5">
                        <div class="text-2xl sm:text-3xl font-black text-violet-300 font-mono">۱۸۰+</div>
                        <div class="text-[11px] sm:text-xs text-slate-300">مراکز درمانی همکار</div>
                    </div>
                </div>

            </div>
        </section>

        <!-- 2. THE 4 PILLARS OF ASENA (ارکان چهارگانه زیست‌بوم آسنا) -->
        <section class="space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-black text-[#fd8100] uppercase tracking-wider">
                    <span class="material-symbols-outlined text-sm">stars</span>
                    ارکان بنیادین پلتفرم
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    چهار ستون اصلی خدمات و پایداری در آسنا
                </h2>
                <p class="text-xs sm:text-sm text-slate-600">
                    ساختاری منسجم که پیوستگی میان معاینه بالینی، دارورسانی استاندارد و صیانت از حقوق سرپرستان را تضمین می‌کند.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Pillar 1 -->
                <div class="about-glass-panel rounded-3xl p-7 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-200/90 group flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors duration-300">
                            <span class="material-symbols-outlined text-3xl">medical_services</span>
                        </div>
                        <h3 class="font-black text-slate-900 text-base leading-snug">
                            یکپارچگی درمان و پرونده سلامت
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            پرونده الکترونیک سلامت، سوابق واکسیناسیون، نوبت‌های درمانگاهی و نسخه دارویی در یک پروفایل ابری واحد به هم متصل هستند؛ در دسترس دائمی سرپرست و پزشک معالج.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center gap-1.5 text-[11px] font-bold text-primary">
                        <span class="material-symbols-outlined text-sm text-[#fd8100]">check_circle</span>
                        <span>پرونده سلامت ابری با QR استعلام</span>
                    </div>
                </div>

                <!-- Pillar 2 -->
                <div class="about-glass-panel rounded-3xl p-7 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-200/90 group flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-2xl bg-amber-50 text-[#fd8100] flex items-center justify-center group-hover:bg-[#fd8100] group-hover:text-white transition-colors duration-300">
                            <span class="material-symbols-outlined text-3xl">verified_user</span>
                        </div>
                        <h3 class="font-black text-slate-900 text-base leading-snug">
                            حساب امانی و تضمین سلامت ۷ روزه
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            برای نخستین بار، وجوه پرداختی مشتریان تا ۷ روز کامل پس از دریافت کالا در حساب امانی آسنا نگهداری می‌شود و تنها پس از اطمینان کامل از سلامت کالا و رضایت خریدار با فروشنده تسویه می‌گردد.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center gap-1.5 text-[11px] font-bold text-amber-700">
                        <span class="material-symbols-outlined text-sm text-[#fd8100]">lock</span>
                        <span>امنیت ۱۰۰٪ واریزها از طریق شاپرک</span>
                    </div>
                </div>

                <!-- Pillar 3 -->
                <div class="about-glass-panel rounded-3xl p-7 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-200/90 group flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center group-hover:bg-emerald-700 group-hover:text-white transition-colors duration-300">
                            <span class="material-symbols-outlined text-3xl">psychology</span>
                        </div>
                        <h3 class="font-black text-slate-900 text-base leading-snug">
                            پایش هوشمند بالینی و تداخلات دارویی
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            سامانه هوشمند آسنا به صورت خودکار تداخلات دارویی، حساسیت‌های نژادی و محاسبات دوز تغذیه روزانه را بر اساس استانداردهای جهانی FEDIAF و WSAVA پیش از خرید هشدار می‌دهد.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center gap-1.5 text-[11px] font-bold text-emerald-800">
                        <span class="material-symbols-outlined text-sm text-emerald-600">health_and_safety</span>
                        <span>پیشگیری فعال از خطاهای بالینی</span>
                    </div>
                </div>

                <!-- Pillar 4 -->
                <div class="about-glass-panel rounded-3xl p-7 shadow-sm hover:shadow-xl transition-all duration-300 border border-slate-200/90 group flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center group-hover:bg-indigo-700 group-hover:text-white transition-colors duration-300">
                            <span class="material-symbols-outlined text-3xl">ac_unit</span>
                        </div>
                        <h3 class="font-black text-slate-900 text-base leading-snug">
                            ارسال زنجیره سرد و تحویل دوره‌ای خودکار
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            داروهای بیولوژیک و واکسن‌ها با بسته‌بندی عایق ۲ تا ۸ درجه حمل می‌شوند. همچنین امکان سفارش دوره‌ای خودکار ملزومات با تخفیف دائمی (Autoship) برای سرپرستان فراهم است.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex items-center gap-1.5 text-[11px] font-bold text-indigo-800">
                        <span class="material-symbols-outlined text-sm text-indigo-600">all_inclusive</span>
                        <span>سرویس اشتراک دوره‌ای Chewy Model</span>
                    </div>
                </div>

            </div>
        </section>

        <!-- 3. COMPREHENSIVE OFFICIAL LICENCES & REGULATORY ACCREDITATIONS (مرکز مجوزها و شفافیت قانونی) -->
        <section id="licences-section" class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-10 space-y-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-1.5 text-xs font-black text-primary">
                        <span class="material-symbols-outlined text-sm text-[#fd8100]">gavel</span>
                        <span>شفافیت اداری و قانونی</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        پروانه‌ها، مجوزهای قانونی و اعتبارسنجی مراجع کشور
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600">
                        مشخصات ثبتی، پروانه‌های صنفی و تاییدیه‌های حاکمیتی ناظر بر فعالیت‌های پلتفرم و شرکای فناوری.
                    </p>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <span class="text-xs bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1.5 rounded-xl font-bold inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-emerald-600">verified</span>
                        استعلام آنلاین مراجع حاکمیتی
                    </span>
                </div>
            </div>

            <!-- Tabbed Navigation for 4 Official Licences -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-100 p-1.5 rounded-2xl">
                <button type="button" onclick="switchLicenseTab('guild')" id="tabBtn-guild" class="license-tab-btn active py-2.5 px-3 rounded-xl text-xs font-bold text-center flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">badge</span>
                    <span>سازمان نظام صنفی رایانه‌ای</span>
                </button>
                <button type="button" onclick="switchLicenseTab('gazette')" id="tabBtn-gazette" class="license-tab-btn py-2.5 px-3 rounded-xl text-xs font-bold text-center flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900">
                    <span class="material-symbols-outlined text-sm">newspaper</span>
                    <span>روزنامه رسمی کشور</span>
                </button>
                <button type="button" onclick="switchLicenseTab('registry')" id="tabBtn-registry" class="license-tab-btn py-2.5 px-3 rounded-xl text-xs font-bold text-center flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900">
                    <span class="material-symbols-outlined text-sm">corporate_fare</span>
                    <span>اداره ثبت شرکت‌ها</span>
                </button>
                <button type="button" onclick="switchLicenseTab('enamad')" id="tabBtn-enamad" class="license-tab-btn py-2.5 px-3 rounded-xl text-xs font-bold text-center flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900">
                    <span class="material-symbols-outlined text-sm">credit_card</span>
                    <span>اینماد و شاپرک بانک مرکزی</span>
                </button>
            </div>

            <!-- Tab 1: Computer Guild License -->
            <div id="licenseContent-guild" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                    <div class="lg:col-span-7 space-y-4">
                        <div class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full border border-blue-200">
                            <span class="material-symbols-outlined text-sm">verified</span>
                            <span>پروانه رسمی عضویت و فعالیت صنفی</span>
                        </div>
                        <h3 class="text-lg sm:text-xl font-black text-slate-900">
                            پروانه فعالیت سازمان نظام صنفی رایانه‌ای استان آذربایجان شرقی
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            پروانه رسمی فعالیت شرکت مهندسین مشاور سما شهر خاوران به مدیریت <strong>مهندس جمال مهرزاد</strong> صادر شده از سوی سازمان نظام صنفی رایانه‌ای کشور، ناظر بر صلاحیت فنی، تولید نرم‌افزارهای تخصصی، پایش سیستم‌های اطلاعات جغرافیایی (GIS) و تطابق با استانداردهای فناوری اطلاعات.
                        </p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div>شماره پروانه نظام صنفی: <strong class="font-mono text-slate-900">14010608</strong></div>
                            <div>شناسه ملی شرکت: <strong class="font-mono text-slate-900">14011425578</strong></div>
                            <div>تاریخ اعتبار عضویت: <strong class="font-mono text-slate-900">1406/04/01</strong></div>
                            <div>مرجع صادرکننده: <strong class="text-slate-900">نظام صنفی رایانه‌ای آذربایجان شرقی</strong></div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="button" onclick="openDocModal('assets/images/partners/samashahr-license.jpg', 'پروانه سازمان نظام صنفی رایانه‌ای - شرکت سما شهر خاوران')" class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-light transition flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">zoom_in</span>
                                <span>بزرگ‌نمایی و مشاهده گواهینامه</span>
                            </button>
                            <a href="https://samashahr.ir/" target="_blank" rel="noopener noreferrer" class="text-primary font-bold text-xs hover:underline inline-flex items-center gap-1">
                                <span>سایت رسمی سما شهر</span>
                                <span class="material-symbols-outlined text-xs">open_in_new</span>
                            </a>
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="relative group cursor-pointer overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-md hover:shadow-xl transition-all" onclick="openDocModal('assets/images/partners/samashahr-license.jpg', 'پروانه سازمان نظام صنفی رایانه‌ای - شرکت سما شهر خاوران')">
                            <img src="assets/images/partners/samashahr-license.jpg" alt="پروانه نظام صنفی رایانه‌ای سما شهر خاوران" class="w-full h-64 object-cover object-top group-hover:scale-102 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex items-end p-4">
                                <div class="text-white flex items-center justify-between w-full">
                                    <span class="text-xs font-bold flex items-center gap-1.5 text-amber-300">
                                        <span class="material-symbols-outlined text-sm">open_in_full</span>
                                        کلیک برای مشاهده با کیفیت اصلی
                                    </span>
                                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-mono">14010608</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Official Gazette -->
            <div id="licenseContent-gazette" class="space-y-6 hidden">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                    <div class="lg:col-span-7 space-y-4">
                        <div class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-800 text-xs font-bold px-3 py-1 rounded-full border border-amber-200">
                            <span class="material-symbols-outlined text-sm">newspaper</span>
                            <span>آگهی قوه قضائیه - روزنامه رسمی جمهوری اسلامی ایران</span>
                        </div>
                        <h3 class="text-lg sm:text-xl font-black text-slate-900">
                            آگهی رسمی تاسیس شرکت در روزنامه رسمی کشور
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            درج آگهی رسمی تاسیس شرکت سما شهر خاوران در <strong>روزنامه رسمی کشور (شماره ۲۲۵۷۰، صفحه ۹۸، مورخ ۱۴۰۱/۰۶/۱۴)</strong>، با موضوع رسمی برنامه‌ریزی شهری، شهرسازی، سیستم‌های جامع اطلاعات جغرافیایی (GIS)، سنجش از دور و فتوگرامتری، و تولید نرم‌افزارهای کاربردی تخصصی.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div>شماره ثبت آگهی روزنامه رسمی: <strong class="font-mono text-slate-900">140130404075007443</strong></div>
                            <div>شماره روزنامه رسمی: <strong class="font-mono text-slate-900">22570 (صفحه 98)</strong></div>
                            <div>تاریخ انتشار روزنامه رسمی: <strong class="font-mono text-slate-900">1401/06/14</strong></div>
                            <div>سامانه رسمی پیگیری: <strong class="font-mono text-slate-900">RRK.ir / Dastour.ir</strong></div>
                        </div>

                        <div class="pt-2">
                            <button type="button" onclick="openDocModal('assets/images/partners/samashahr-official-gazette.jpg', 'آگهی تاسیس روزنامه رسمی جمهوری اسلامی ایران - شرکت سما شهر خاوران')" class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-light transition flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">zoom_in</span>
                                <span>مشاهده برگ روزنامه رسمی کشور</span>
                            </button>
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="relative group cursor-pointer overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-md hover:shadow-xl transition-all" onclick="openDocModal('assets/images/partners/samashahr-official-gazette.jpg', 'آگهی تاسیس روزنامه رسمی جمهوری اسلامی ایران - شرکت سما شهر خاوران')">
                            <img src="assets/images/partners/samashahr-official-gazette.jpg" alt="روزنامه رسمی کشور شرکت سما شهر خاوران" class="w-full h-64 object-cover object-top group-hover:scale-102 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex items-end p-4">
                                <div class="text-white flex items-center justify-between w-full">
                                    <span class="text-xs font-bold flex items-center gap-1.5 text-amber-300">
                                        <span class="material-symbols-outlined text-sm">open_in_full</span>
                                        مشاهده متن کامل روزنامه رسمی
                                    </span>
                                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-mono">شماره ۲۲۵۷۰</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Company Registry -->
            <div id="licenseContent-registry" class="space-y-6 hidden">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                    <div class="lg:col-span-7 space-y-4">
                        <div class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full border border-emerald-200">
                            <span class="material-symbols-outlined text-sm">corporate_fare</span>
                            <span>سازمان ثبت اسناد و املاک کشور - قوه قضائیه</span>
                        </div>
                        <h3 class="text-lg sm:text-xl font-black text-slate-900">
                            آگهی تاسیس و شرکتنامه رسمی ثبت شرکت‌ها
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            سند رسمی ثبت شرکت مهندسین مشاور سما شهر خاوران در <strong>اداره ثبت شرکت‌ها و موسسات غیرتجاری تبریز</strong> با شماره ثبت ۵۶۳۰۶ و شناسه ملی ۱۴۰۱۱۴۲۵۵۷۸ همراه با تعیین ارکان مدیریت و حق امضای اسناد رسمی.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div>شماره ثبت اداره شرکت‌ها: <strong class="font-mono text-slate-900">56306 تبریز</strong></div>
                            <div>شناسه ملی اشخاص حقوقی: <strong class="font-mono text-slate-900">14011425578</strong></div>
                            <div>شماره مکانیزه ثبتی: <strong class="font-mono text-slate-900">140130404075007343</strong></div>
                            <div>سامانه استعلام ثبت: <strong class="font-mono text-slate-900">irshekat.ssaa.ir</strong></div>
                        </div>

                        <div class="pt-2">
                            <button type="button" onclick="openDocModal('assets/images/partners/samashahr-registration.jpg', 'آگهی ثبتی شرکت مهندسین مشاور سما شهر خاوران - اداره ثبت شرکت‌ها')" class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-light transition flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">zoom_in</span>
                                <span>مشاهده سند اداره ثبت شرکت‌ها</span>
                            </button>
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="relative group cursor-pointer overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-md hover:shadow-xl transition-all" onclick="openDocModal('assets/images/partners/samashahr-registration.jpg', 'آگهی ثبتی شرکت مهندسین مشاور سما شهر خاوران - اداره ثبت شرکت‌ها')">
                            <img src="assets/images/partners/samashahr-registration.jpg" alt="آگهی اداره ثبت شرکت‌ها سما شهر خاوران" class="w-full h-64 object-cover object-top group-hover:scale-102 transition-transform duration-300">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex items-end p-4">
                                <div class="text-white flex items-center justify-between w-full">
                                    <span class="text-xs font-bold flex items-center gap-1.5 text-amber-300">
                                        <span class="material-symbols-outlined text-sm">open_in_full</span>
                                        مشاهده برگ اداره ثبت شرکت‌ها
                                    </span>
                                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-mono">ثبت ۵۶۳۰۶</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Enamad & Shaparak -->
            <div id="licenseContent-enamad" class="space-y-6 hidden">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                    <div class="lg:col-span-7 space-y-4">
                        <div class="inline-flex items-center gap-1.5 bg-sky-50 text-sky-800 text-xs font-bold px-3 py-1 rounded-full border border-sky-200">
                            <span class="material-symbols-outlined text-sm">verified_user</span>
                            <span>مرکز توسعه تجارت الکترونیکی (وزارت صمت) و شاپرک</span>
                        </div>
                        <h3 class="text-lg sm:text-xl font-black text-slate-900">
                            نماد اعتماد الکترونیکی (اینماد) و درگاه رسمی پرداخت شاپرک
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            پلتفرم جامع آسنا دارای نماد اعتماد الکترونیکی معتبر، گواهی امنیت ارتباطات داده SSL، و درگاه اختصاصی پرداخت متصل به سامانه شاپرک بانک مرکزی از طریق زرین‌پال می‌باشد.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div>شناسه رهگیری اینماد: <strong class="font-mono text-slate-900">7706608</strong></div>
                            <div>کد درگاه پرداخت شاپرک: <strong class="font-mono text-slate-900">92df0362-5e43...</strong></div>
                            <div>دامنه رسمی احراز شده: <strong class="font-mono text-slate-900">asena.company</strong></div>
                            <div>شبکه تسویه بانکی: <strong class="text-slate-900">پایا و ساتنا بانک مرکزی</strong></div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <a href="https://trustseal.enamad.ir/?id=7706608&Code=qBmonKZeAe36PvBvs1zpTGrrRb7uFJs8" target="_blank" rel="noopener noreferrer" class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-light transition flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">verified</span>
                                <span>استعلام زنده اینماد در سامانه صمت</span>
                            </a>
                        </div>
                    </div>

                    <div class="lg:col-span-5 bg-slate-50 p-6 rounded-2xl border border-slate-200 text-center space-y-4">
                        <div class="w-20 h-20 mx-auto rounded-2xl bg-white p-3 shadow-sm border border-slate-200 flex items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-primary">security</span>
                        </div>
                        <div>
                            <div class="font-black text-slate-900 text-sm">امنیت پرداخت با درگاه شاپرک</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">رمز دوم پویا، اتصال شاپرک و تسویه تحت نظارت بانک مرکزی</div>
                        </div>
                        <div class="text-[11px] text-emerald-700 bg-emerald-50 py-1.5 px-3 rounded-xl border border-emerald-200 font-bold inline-block">
                            پروتکل رمزنگاری امن داده‌های کاربران (TLS 1.3)
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <!-- 4. STRATEGIC TECHNOLOGY & GEOSPATIAL PARTNER: مهندسین مشاور سما شهر خاوران -->
        <section id="samashahr-section" class="bg-gradient-to-br from-slate-950 via-[#001a48] to-[#002d72] text-white rounded-3xl p-8 sm:p-12 lg:p-14 shadow-2xl border border-blue-900/50 relative overflow-hidden space-y-10">
            <!-- Geometric Grid -->
            <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 24px 24px;"></div>
            
            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-8 border-b border-white/10">
                <div class="space-y-3 max-w-2xl">
                    <div class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-300 border border-blue-400/30 px-3.5 py-1.5 rounded-full text-xs font-bold">
                        <span class="material-symbols-outlined text-sm text-[#fd8100]">hub</span>
                        <span>پشتوانه مهندسی ژئوانفورماتیک و پایداری زیرساخت فناوری</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-tight">
                        هم‌پیمانی راهبردی با شرکت مهندسین مشاور «سما شهر خاوران»
                    </h2>
                    <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                        توسعه فناوری‌های زیرساختی، تحلیل داده‌های مکانی مراکز درمانی کشور (GIS)، مسیریابی سریع اورژانس حیوانات و پایداری سرویس‌های کلان پلتفرم آسنا با بهره‌گیری از همراهی و تخصص مهندسی شرکت سما شهر خاوران صورت می‌پذیرد.
                    </p>
                </div>

                <div class="shrink-0 flex items-center">
                    <a href="https://samashahr.ir/" target="_blank" rel="noopener noreferrer" class="group inline-flex items-center gap-2.5 px-6 py-3.5 rounded-2xl bg-white text-slate-900 hover:bg-slate-100 shadow-xl hover:shadow-2xl transition-all text-xs font-black">
                        <span>مشاهده تارنمای رسمی سما شهر</span>
                        <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 group-hover:-translate-y-1 transition-transform text-[#fd8100]">north_east</span>
                    </a>
                </div>
            </div>

            <!-- Bento Layout: Sama Shahr Moat -->
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                
                <!-- Left 7 Cols: The Engineering Value Add -->
                <div class="lg:col-span-7 bg-white/5 backdrop-blur-md rounded-2xl p-6 sm:p-8 border border-white/10 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-16 h-16 rounded-2xl bg-white p-2.5 flex items-center justify-center shadow-md shrink-0">
                                    <img src="assets/images/partners/samashahr-logo.png" alt="لوگو شرکت مهندسین مشاور سما شهر خاوران" class="w-full h-auto object-contain">
                                </div>
                                <div>
                                    <h3 class="font-black text-white text-base sm:text-lg">شرکت مهندسین مشاور سما شهر خاوران</h3>
                                    <div class="text-[11px] text-slate-300 font-mono">شماره ثبت: ۵۶۳۰۶ تبریز • شناسه ملی: ۱۴۰۱۱۴۲۵۵۷۸</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 text-[10px] font-bold px-2.5 py-1 rounded-full shrink-0">
                                <span class="material-symbols-outlined text-xs">verified</span>
                                <span>شریک رسمی</span>
                            </span>
                        </div>

                        <p class="text-xs text-slate-300 leading-relaxed font-light">
                            شرکت مهندسین مشاور سما شهر خاوران با سال‌ها پیشینه ارزشمند در حوزه سیستم‌های جامع اطلاعات جغرافیایی (GIS)، شهرسازی، طرح‌های جامع و تفصیلی، سامانه‌های هوشمند پایش و توسعه نرم‌افزارهای سازمانی مقیاس‌پذیر فعالیت دارد. در چارچوب این هم‌پیمانی، آسنا از توانمندی‌های پیشرفته این مجموعه در مهندسی مکانی اورژانس‌های دامپزشکی، مسیریابی آمبولانس‌ها و بهینه‌سازی دایرکتوری مراکز بهره می‌گیرد.
                        </p>
                    </div>

                    <!-- 3 Synergy Capabilities -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div class="p-4 rounded-xl bg-white/5 border border-white/10 text-right space-y-1.5 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-1.5 text-amber-300">
                                <span class="material-symbols-outlined text-lg text-[#fd8100]">map</span>
                                <span class="text-xs font-bold text-white">سامانه‌های GIS</span>
                            </div>
                            <p class="text-[11px] text-slate-300 leading-tight">موقعیت‌یابی و تحلیل شعاع خدمت‌رسانی کلینیک‌ها و اورژانس</p>
                        </div>

                        <div class="p-4 rounded-xl bg-white/5 border border-white/10 text-right space-y-1.5 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-1.5 text-sky-300">
                                <span class="material-symbols-outlined text-lg text-sky-400">cloud_sync</span>
                                <span class="text-xs font-bold text-white">پایداری کلود</span>
                            </div>
                            <p class="text-[11px] text-slate-300 leading-tight">پایش تاب‌آوری زیرساخت‌های سرور و آپ‌تایم بالای ۹۹.۹٪</p>
                        </div>

                        <div class="p-4 rounded-xl bg-white/5 border border-white/10 text-right space-y-1.5 hover:bg-white/10 transition-colors">
                            <div class="flex items-center gap-1.5 text-emerald-300">
                                <span class="material-symbols-outlined text-lg text-emerald-400">gavel</span>
                                <span class="text-xs font-bold text-white">استاندارد نرم‌افزار</span>
                            </div>
                            <p class="text-[11px] text-slate-300 leading-tight">انطباق موازین معماری داده با ضوابط نظام صنفی رایانه‌ای</p>
                        </div>
                    </div>
                </div>

                <!-- Right 5 Cols: Leadership & Interactive Documents -->
                <div class="lg:col-span-5 bg-white/5 backdrop-blur-md rounded-2xl p-6 sm:p-8 border border-white/10 flex flex-col justify-between space-y-5">
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-amber-300 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base text-[#fd8100]">person_check</span>
                                ارکان مدیریتی و فنی شرکت
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">مهندسین مشاور</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 space-y-1">
                            <div class="text-xs font-bold text-white">مهندس جمال مهرزاد</div>
                            <div class="text-[11px] text-slate-300">رئیس هیئت مدیره و مدیر فنی پروژه‌ها</div>
                        </div>
                    </div>

                    <!-- 3 Document Mini Previews -->
                    <div class="space-y-2">
                        <div class="text-[11px] font-bold text-slate-300 flex items-center justify-between">
                            <span>مدارک و اسناد ثبتی (کلیک برای مشاهده):</span>
                            <span class="text-[10px] text-amber-400 font-mono">3 اسناد رسمی</span>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div onclick="openDocModal('assets/images/partners/samashahr-license.jpg', 'پروانه سازمان نظام صنفی رایانه‌ای - شرکت سما شهر خاوران')" class="group cursor-pointer rounded-xl overflow-hidden border border-white/20 bg-slate-800 p-1 hover:border-amber-400 transition-colors text-center">
                                <img src="assets/images/partners/samashahr-license.jpg" alt="پروانه نظام صنفی" class="w-full h-16 object-cover object-top rounded-lg group-hover:scale-105 transition-transform">
                                <span class="text-[10px] text-slate-300 block truncate mt-1">پروانه صنفی</span>
                            </div>

                            <div onclick="openDocModal('assets/images/partners/samashahr-official-gazette.jpg', 'آگهی تاسیس روزنامه رسمی جمهوری اسلامی ایران - شرکت سما شهر خاوران')" class="group cursor-pointer rounded-xl overflow-hidden border border-white/20 bg-slate-800 p-1 hover:border-amber-400 transition-colors text-center">
                                <img src="assets/images/partners/samashahr-official-gazette.jpg" alt="روزنامه رسمی کشور" class="w-full h-16 object-cover object-top rounded-lg group-hover:scale-105 transition-transform">
                                <span class="text-[10px] text-slate-300 block truncate mt-1">روزنامه رسمی</span>
                            </div>

                            <div onclick="openDocModal('assets/images/partners/samashahr-registration.jpg', 'آگهی ثبتی شرکت مهندسین مشاور سما شهر خاوران - اداره ثبت شرکت‌ها')" class="group cursor-pointer rounded-xl overflow-hidden border border-white/20 bg-slate-800 p-1 hover:border-amber-400 transition-colors text-center">
                                <img src="assets/images/partners/samashahr-registration.jpg" alt="ثبت شرکت‌ها" class="w-full h-16 object-cover object-top rounded-lg group-hover:scale-105 transition-transform">
                                <span class="text-[10px] text-slate-300 block truncate mt-1">ثبت شرکت‌ها</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-white/10 flex items-center justify-between text-[11px] text-slate-400">
                        <span>اعتبار پروانه صنفی: ۱۴۰۶/۰۴/۰۱</span>
                        <a href="https://samashahr.ir/" target="_blank" rel="noopener noreferrer" class="text-amber-300 hover:text-white font-bold inline-flex items-center gap-1 transition-colors">
                            <span>سامانه سما شهر</span>
                            <span class="material-symbols-outlined text-xs">north_east</span>
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- 5. INTERACTIVE DISTINCTION MATRIX (مقایسه شفاف آسنا با خرید سنتی) -->
        <section class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden p-6 sm:p-10 space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="space-y-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-black text-primary">
                        <span class="material-symbols-outlined text-sm text-[#fd8100]">compare_arrows</span>
                        ماتریس تمایز و شفافیت
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        آسنا در برابر فروشگاه‌ها و کانال‌های سنتی بازار
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600">
                        چرا خرید و دریافت خدمات از آسنا بالاترین سطح امنیت بالینی و مالی را برای پت و سرپرست به همراه دارد؟
                    </p>
                </div>
                <div class="shrink-0">
                    <span class="text-[11px] text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl font-medium inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs text-emerald-600">verified</span>
                        استانداردهای تضمین‌شده آسنا
                    </span>
                </div>
            </div>

            <!-- Comparison Table -->
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
                                <div>پرونده الکترونیک سلامت و سوابق بالینی پت</div>
                                <div class="text-[11px] text-slate-500 font-normal">دسترسی دائم به نسخه، سوابق درمان و واکسیناسیون</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg text-xs border border-emerald-200">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    پرونده ابری متمرکز + QR استعلام
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    دفترچه کاغذی مفقود یا نامشخص
                                </span>
                            </td>
                        </tr>

                        <tr class="matrix-row transition-colors">
                            <td class="p-4 sm:p-5 font-bold text-slate-900">
                                <div>ضمانت مالی و استرداد وجه (Escrow Shield)</div>
                                <div class="text-[11px] text-slate-500 font-normal">حفظ امنیت وجه تا زمان اطمینان کامل از سلامت کالا</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg text-xs border border-emerald-200">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    حساب امانی ۷ روزه نزد پلتفرم
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    واریز کارت‌به‌کارت بدون تضمین استرداد
                                </span>
                            </td>
                        </tr>

                        <tr class="matrix-row transition-colors">
                            <td class="p-4 sm:p-5 font-bold text-slate-900">
                                <div>پایش تداخلات دارویی و منع مصرف غذایی</div>
                                <div class="text-[11px] text-slate-500 font-normal">بررسی سازگاری همزمان داروها با مکمل و غذای پت</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg text-xs border border-emerald-200">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    موتور خودکار تداخلات فارماکولوژی + AI
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    عرضه بدون استعلام و خطای بالینی
                                </span>
                            </td>
                        </tr>

                        <tr class="matrix-row transition-colors">
                            <td class="p-4 sm:p-5 font-bold text-slate-900">
                                <div>ارسال واکسن و داروهای بیولوژیک</div>
                                <div class="text-[11px] text-slate-500 font-normal">کنترل استاندارد دما جهت حفظ کامل اثر دارویی</div>
                            </td>
                            <td class="p-4 sm:p-5 bg-blue-50/30 border-r border-l border-slate-200">
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg text-xs border border-emerald-200">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    پروتکل رسمی زنجیره سرد (Cold-Chain)
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg text-xs">
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
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg text-xs border border-emerald-200">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    پشتیبانی داده مکانی مهندسین سما شهر
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg text-xs">
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
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg text-xs border border-emerald-200">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    تخصیص سیستمی به پویش‌های امداد
                                </span>
                            </td>
                            <td class="p-4 sm:p-5 text-slate-500">
                                <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg text-xs">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    انگیزه تجاری محض بدون برنامه حمایتی
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 6. SOCIAL RESPONSIBILITY & SHELTER PROMISE (مسئولیت اجتماعی و امداد) -->
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

        <!-- 7. CONTACT & INQUIRIES (ارتباط و پشتیبانی رسمی) -->
        <section class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-8 sm:p-10 space-y-6">
            <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">contact_support</span>
                پاسخگویی، استعلام اداری و ارتباط با مدیریت
            </h2>
            <div class="space-y-4 text-xs sm:text-sm text-slate-600 leading-relaxed">
                <p>
                    واحد روابط عمومی، پشتیبانی و امور حقوقی پلتفرم آسنا در ساعات کاری همه روزه آماده پاسخگویی به استعلام‌ها، پیشنهادات همکاری کلینیک‌ها و پرسش‌های سرپرستان گرامی می‌باشد.
                </p>
                
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-5">
                    <div class="space-y-1 text-center sm:text-right">
                        <span class="font-bold text-slate-900 block text-xs sm:text-sm">نیاز به ارتباط رسمی یا همکاری کلینیکی دارید؟</span>
                        <span class="text-slate-500 text-[11px] sm:text-xs">همکاران ما در بخش پشتیبانی و کارشناسان امور درمان مشتاقانه پاسخگوی شما هستند.</span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <a href="contact.php" class="px-5 py-2.5 rounded-xl bg-primary text-white font-bold text-xs shadow-sm hover:bg-primary-light transition flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">phone</span>
                            <span>صفحه تماس و مشخصات</span>
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

<!-- ADVANCED INTERACTIVE DOCUMENT LIGHTBOX MODAL -->
<div id="docModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/85 backdrop-blur-md p-4 transition-all duration-300 opacity-0 pointer-events-none" onclick="closeDocModal(event)">
    <div class="relative bg-white rounded-3xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl space-y-4 border border-slate-100 transform scale-95 transition-transform duration-300" onclick="event.stopPropagation()">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">verified</span>
                <h3 id="docModalTitle" class="font-black text-slate-900 text-sm sm:text-base">سند رسمی</h3>
            </div>
            <button type="button" onclick="closeDocModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors" title="بستن">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>

        <div class="overflow-auto max-h-[75vh] flex justify-center rounded-2xl bg-slate-50 p-2 border border-slate-100">
            <img id="docModalImage" src="" alt="سند رسمی" class="w-full h-auto object-contain rounded-xl shadow-xs">
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 pt-1">
            <span>شماره ثبت: <strong class="font-mono text-slate-800">56306 تبریز</strong> • شناسه ملی: <strong class="font-mono text-slate-800">14011425578</strong> • پروانه صنفی: <strong class="font-mono text-slate-800">14010608</strong></span>
            <a href="https://samashahr.ir/" target="_blank" rel="noopener noreferrer" class="text-primary font-bold hover:underline inline-flex items-center gap-1">
                <span>تارنمای رسمی شرکت مهندسین مشاور سما شهر خاوران</span>
                <span class="material-symbols-outlined text-xs">open_in_new</span>
            </a>
        </div>
    </div>
</div>

<script>
// Switch License Tabs
function switchLicenseTab(tabKey) {
    const tabs = ['guild', 'gazette', 'registry', 'enamad'];
    tabs.forEach(t => {
        const btn = document.getElementById('tabBtn-' + t);
        const content = document.getElementById('licenseContent-' + t);
        if (btn) {
            if (t === tabKey) {
                btn.classList.add('active');
                btn.classList.remove('text-slate-600');
            } else {
                btn.classList.remove('active');
                btn.classList.add('text-slate-600');
            }
        }
        if (content) {
            if (t === tabKey) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        }
    });
}

// Open Multi-Doc Modal
function openDocModal(src, title) {
    const modal = document.getElementById('docModal');
    const img = document.getElementById('docModalImage');
    const titleEl = document.getElementById('docModalTitle');
    if (!modal || !img) return;

    img.src = src;
    if (titleEl) titleEl.textContent = title;

    modal.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
    modal.classList.add('flex', 'opacity-100', 'pointer-events-auto');
    const panel = modal.querySelector('div');
    if (panel) {
        panel.classList.remove('scale-95');
        panel.classList.add('scale-100');
    }
}

function closeDocModal(e) {
    const modal = document.getElementById('docModal');
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
        closeDocModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
