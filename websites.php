<?php
/**
 * ASENA Enterprise - Websites Showcase & Purchasing Catalog
 * Dedicated showcase for customizable tenant websites across 4 distinct archetypes:
 * 1. Doctor (Clinical Authority & Booking)
 * 2. Pharmacy (Cold-Chain & Rx Upload)
 * 3. Pet Shop (E-commerce & Autoship)
 * 4. Organization (Hospital Multi-Department & Emergency)
 * 
 * Version: 1.0.0
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/App.php';
require_once __DIR__ . '/includes/functions.php';

$tenantService = App::tenantSite();
$archetypes = $tenantService->getWebsiteArchetypes();
$tiersConfig = require __DIR__ . '/config/tiers.php';
$tiers = $tiersConfig['tiers'] ?? [];

$page_title = 'سفارش و خرید وب‌سایت اختصاصی دامپزشکی، داروخانه و پت‌شاپ | آسنا';
$page_desc = 'ساخت فوری وب‌سایت مستقل و حرفه‌ای متناسب با حوزه فعالیت شما: ویژه پزشکان، داروخانه‌ها، پت‌شاپ‌ها و بیمارستان‌های دامپزشکی، متصل به نوبت‌دهی و درگاه شاپرک.';

include __DIR__ . '/includes/header.php';
?>

<div class="w-[96%] max-w-[1550px] mx-auto py-6 md:py-10 space-y-16 md:space-y-24">

    <!-- 1. Hero Beat: Value Proposition & Subdomain Availability Checker -->
    <section class="relative overflow-hidden rounded-[2.5rem] md:rounded-[3.5rem] bg-gradient-to-br from-[#001a48] via-[#022869] to-[#043d99] text-white p-6 sm:p-10 md:p-16 shadow-2xl border border-white/10">
        <!-- Ambient Glowing Background Orbs -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#fd8100]/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[300px] bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-4xl mx-auto text-center space-y-6">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs md:text-sm font-bold text-amber-300 shadow-sm animate-pulse">
                <span class="material-symbols-outlined text-base">web_stories</span>
                <span>نسل نوین پلتفرم وب‌سایت‌ساز اختصاصی اکوسیستم آسنا</span>
            </div>

            <h1 class="text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-black tracking-tight leading-tight md:leading-tight">
                وب‌سایت اختصاصی، مستقل و مدرن؛<br class="hidden sm:inline">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-amber-300 via-orange-400 to-amber-200">
                    دقیقاً متناسب با تخصص و برند شما
                </span>
            </h1>

            <p class="text-sm sm:text-base md:text-lg text-slate-200 font-medium leading-relaxed max-w-2xl mx-auto">
                دیگر نیازی نیست همه وب‌سایت‌ها شبیه به هم باشند! ما برای پزشکان، داروخانه‌ها، پت‌شاپ‌ها و بیمارستان‌های دامپزشکی، وب‌سایت‌هایی با ساختار، هویت بصری و ماژول‌های کاملاً متفاوت و متناسب با نیاز مشتریانشان خلق کرده‌ایم.
            </p>

            <!-- Interactive Live Subdomain Availability Search -->
            <div class="max-w-2xl mx-auto pt-4">
                <div class="bg-white/10 backdrop-blur-xl p-2.5 rounded-2xl md:rounded-3xl border border-white/20 shadow-2xl flex flex-col sm:flex-row items-center gap-2">
                    <div class="flex-1 w-full flex items-center bg-white rounded-xl md:rounded-2xl px-4 py-2.5 border border-slate-200 text-slate-800 shadow-inner">
                        <span class="material-symbols-outlined text-slate-400 text-xl ml-2">language</span>
                        <input type="text" id="hero-subdomain-input" placeholder="نام برند یا نام خانوادگی شما (انگلیسی)" dir="ltr" class="w-full bg-transparent border-none outline-none text-left font-mono font-bold text-sm sm:text-base text-slate-900 placeholder:text-slate-400" autocomplete="off">
                        <span class="text-xs sm:text-sm font-mono text-slate-500 font-bold mr-1 shrink-0">.asena.company</span>
                    </div>
                    <button type="button" onclick="checkSubdomainFromHero()" id="hero-check-btn" class="w-full sm:w-auto px-6 py-3.5 rounded-xl md:rounded-2xl bg-gradient-to-r from-[#fd8100] to-amber-500 hover:from-[#e57400] hover:to-amber-600 text-white font-black text-xs sm:text-sm shadow-lg hover:shadow-orange-500/30 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95 shrink-0">
                        <span class="material-symbols-outlined text-lg">search_check</span>
                        <span>بررسی آدرس</span>
                    </button>
                </div>
                <!-- Status Feedback Badge -->
                <div id="hero-subdomain-feedback" class="mt-3 text-xs md:text-sm font-bold hidden transition-all duration-300"></div>
            </div>

            <!-- Quick Action Links -->
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <a href="#archetypes-section" class="px-6 py-3 rounded-xl bg-white/15 hover:bg-white/25 backdrop-blur-md text-white font-bold text-xs sm:text-sm transition-all flex items-center gap-2 border border-white/15">
                    <span class="material-symbols-outlined text-base">dashboard_customize</span>
                    <span>مشاهده و مقایسه ۴ قالب تخصصی</span>
                </a>
                <button type="button" onclick="openOrderModal()" class="px-6 py-3 rounded-xl bg-white text-[#001a48] hover:bg-slate-100 font-black text-xs sm:text-sm transition-all shadow-md flex items-center gap-2 cursor-pointer">
                    <span class="material-symbols-outlined text-base text-[#fd8100]">rocket_launch</span>
                    <span>سفارش فوری وب‌سایت</span>
                </button>
            </div>

            <!-- Trust Anchors Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-8 border-t border-white/15 text-center">
                <div class="p-3 rounded-2xl bg-white/5 backdrop-blur-xs border border-white/10">
                    <div class="text-base sm:text-lg font-black text-amber-300 flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-base">bolt</span>
                        <span>۵ دقیقه</span>
                    </div>
                    <div class="text-[11px] text-slate-300 mt-0.5">تحویل آنلاین و فوری</div>
                </div>
                <div class="p-3 rounded-2xl bg-white/5 backdrop-blur-xs border border-white/10">
                    <div class="text-base sm:text-lg font-black text-amber-300 flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-base">credit_card</span>
                        <span>شاپرک</span>
                    </div>
                    <div class="text-[11px] text-slate-300 mt-0.5">درگاه پرداخت و تسویه پایا</div>
                </div>
                <div class="p-3 rounded-2xl bg-white/5 backdrop-blur-xs border border-white/10">
                    <div class="text-base sm:text-lg font-black text-amber-300 flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-base">qr_code_2</span>
                        <span>کارت هوشمند</span>
                    </div>
                    <div class="text-[11px] text-slate-300 mt-0.5">استند رومیزی و vCard</div>
                </div>
                <div class="p-3 rounded-2xl bg-white/5 backdrop-blur-xs border border-white/10">
                    <div class="text-base sm:text-lg font-black text-amber-300 flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-base">verified</span>
                        <span>۱۰۰٪ مستقل</span>
                    </div>
                    <div class="text-[11px] text-slate-300 mt-0.5">دامنه اختصاصی (.ir / .com)</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. The Core Beat: 4 Dedicated Archetypes Showcase Matrix -->
    <section id="archetypes-section" class="space-y-8 scroll-mt-24">
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold">
                <span class="material-symbols-outlined text-sm">palette</span>
                <span>تنوع کامل ساختاری بر اساس زمینه کاری شما</span>
            </div>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900">
                قالب‌های اختصاصی ۴ حوزه فعالیت
            </h2>
            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                هر شغل، نیازمندی‌ها و مراجعین خاص خود را دارد. وب‌سایت‌های آسنا با شناخت عمیق از فرآیندهای دامپزشکی، ماژول‌های متفاوتی برای پزشکان، داروخانه‌ها، فروشگاه‌ها و بیمارستان‌ها ارائه می‌دهند.
            </p>
        </div>

        <!-- Archetype Navigation Tabs -->
        <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 p-1.5 bg-slate-100 rounded-2xl max-w-4xl mx-auto border border-slate-200/80 shadow-xs">
            <button type="button" onclick="selectArchetypeTab('doctor')" id="arch-tab-doctor" class="arch-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center justify-center gap-2 cursor-pointer shadow-sm bg-white text-emerald-800 border border-emerald-200">
                <span class="material-symbols-outlined text-lg text-emerald-600">stethoscope</span>
                <span>۱. پزشکان و متخصصین</span>
            </button>
            <button type="button" onclick="selectArchetypeTab('pharmacist')" id="arch-tab-pharmacist" class="arch-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-slate-600 hover:text-purple-700 hover:bg-white/80 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span class="material-symbols-outlined text-lg text-purple-600">medication</span>
                <span>۲. داروخانه‌های تخصصی</span>
            </button>
            <button type="button" onclick="selectArchetypeTab('seller')" id="arch-tab-seller" class="arch-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-slate-600 hover:text-orange-700 hover:bg-white/80 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span class="material-symbols-outlined text-lg text-orange-600">storefront</span>
                <span>۳. پت‌شاپ‌ها و فروشگاه‌ها</span>
            </button>
            <button type="button" onclick="selectArchetypeTab('organization')" id="arch-tab-organization" class="arch-tab-btn flex-1 min-w-[140px] sm:min-w-[180px] py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-slate-600 hover:text-blue-800 hover:bg-white/80 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span class="material-symbols-outlined text-lg text-blue-700">apartment</span>
                <span>۴. بیمارستان‌ها و مراکز جامع</span>
            </button>
        </div>

        <!-- Archetype Panels Wrapper -->
        <div class="relative">

            <!-- Panel 1: Doctor Archetype (Clinical Authority) -->
            <div id="arch-panel-doctor" class="arch-panel transition-all duration-300">
                <div class="bg-white rounded-[2rem] md:rounded-[3rem] p-6 sm:p-8 md:p-12 border-2 border-emerald-100 shadow-xl overflow-hidden relative">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6 text-right">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-black">
                                <span class="material-symbols-outlined text-sm text-emerald-600">verified</span>
                                <span>الگوی تخصصی اتوریتی بالینی و نوبت‌دهی آنلاین مراجعین</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                وب‌سایت پزشکان، جراحان و متخصصین دامپزشکی
                            </h3>
                            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                طراحی شده برای معرفی باوقار سوابق علمی، مدارک جراحی، بورد تخصصی و کد نظام دامپزشکی. مراجعین می‌توانند تایم‌اسلات‌های آزاد ویزیت را مشاهده و آنلاین رزرو کنند، تعرفه‌ها را به صورت شفاف محاسبه نمایند و نتایج درمان‌های قبلی را به صورت قبل و بعد مقایسه کنند.
                            </p>

                            <!-- Feature Badges -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">calendar_month</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">تقویم رزرو نوبت آنلاین و پایش تایم‌اسلات‌ها</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">انتخاب تاریخ و ساعت توسط کاربر با دریافت پیامک تایید و لوکیشن بدون تداخل نوبت‌ها.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">compare</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">اسلایدر تعاملی نتایج درمان قبل و بعد (Before & After)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">جلب اعتماد حداکثری مراجعین با نمایش نمونه‌های جراحی ارتوپدی، دندانپزشکی و پوست.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">calculate</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">محاسبه‌گر شفاف تعرفه خدمات با ۱۰٪ تخفیف رزرو آنلاین</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">محاسبه برآورد هزینه چکاپ، واکسیناسیون، عقیم‌سازی و آزمایش‌ها متناسب با گونه و وزن پت.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100">
                                    <span class="material-symbols-outlined text-emerald-600 text-xl shrink-0 mt-0.5">badge</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">اتصال به پروانه نظام دامپزشکی و کارت ویزیت دیجیتال هوشمند</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">استند رومیزی با کد QR اختصاصی مطب جهت ذخیره مستقیم شماره و آدرس در تلفن بیمار.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                <a href="site.php?slug=dr-alavi" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-emerald-700/20 transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>مشاهده دمو زنده وب‌سایت پزشک</span>
                                </a>
                                <button type="button" onclick="openOrderModal('doctor')" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base text-emerald-400">add_task</span>
                                    <span>سفارش و راه‌اندازی این قالب</span>
                                </button>
                            </div>
                        </div>

                        <!-- Visual Mockup Showcase -->
                        <div class="lg:col-span-5 flex flex-col items-center">
                            <div class="w-full bg-slate-900 rounded-3xl p-3 shadow-2xl border-4 border-slate-800 relative group overflow-hidden">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <span class="text-slate-300 font-bold">dr-alavi.asena.company</span>
                                    <span class="material-symbols-outlined text-xs text-emerald-400">lock</span>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 aspect-[4/3] flex flex-col justify-between p-4 text-white">
                                    <div class="space-y-2">
                                        <div class="inline-flex items-center gap-1 bg-emerald-500/20 text-emerald-300 text-[10px] px-2.5 py-1 rounded-full border border-emerald-400/30">
                                            <span class="material-symbols-outlined text-xs">verified</span>
                                            <span>نظام دامپزشکی: ۲۴۵۹۸</span>
                                        </div>
                                        <h4 class="text-lg font-black text-white">کلینیک و جراحی تخصصی دکتر علوی</h4>
                                        <p class="text-xs text-slate-300 leading-normal">بورد تخصصی جراحی بافت نرم و ارتوپدی حیوانات خانگی</p>
                                    </div>
                                    <!-- Interactive mini widgets teaser in mockup -->
                                    <div class="grid grid-cols-2 gap-2 text-center text-xs">
                                        <div class="bg-white/10 rounded-xl p-2 backdrop-blur-xs border border-white/10">
                                            <div class="text-emerald-400 font-bold">۹ نوبت آزاد امروز</div>
                                            <div class="text-[10px] text-slate-400">رزرو آنلاین ویزیت</div>
                                        </div>
                                        <div class="bg-white/10 rounded-xl p-2 backdrop-blur-xs border border-white/10">
                                            <div class="text-amber-400 font-bold">مشاوره تله‌هلث</div>
                                            <div class="text-[10px] text-slate-400">ویزیت فوری آنلاین</div>
                                        </div>
                                    </div>
                                    <a href="site.php?slug=dr-alavi" target="_blank" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 rounded-xl text-center text-xs font-bold transition-colors">
                                        مشاهده پیش‌نمایش زنده در تب جدید
                                    </a>
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-2 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
                                <span>پالت رنگی: سبز زمردی کلینیکی (#065f46) و سفید درمانی</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 2: Pharmacist Archetype (Cold-Chain & Rx) -->
            <div id="arch-panel-pharmacist" class="arch-panel hidden transition-all duration-300">
                <div class="bg-white rounded-[2rem] md:rounded-[3rem] p-6 sm:p-8 md:p-12 border-2 border-purple-100 shadow-xl overflow-hidden relative">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6 text-right">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-purple-50 border border-purple-200 text-purple-800 text-xs font-black">
                                <span class="material-symbols-outlined text-sm text-purple-600">vaccines</span>
                                <span>الگوی تخصصی دراگ‌استور، ارسال زنجیره سرد و آپلود نسخه پزشک</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                وب‌سایت داروخانه‌های تخصصی دامپزشکی و توزیع مکمل
                            </h3>
                            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                طراحی هدفمند برای استعلام و پذیرش سریع نسخه‌های دارویی، عرضه داروهای کمیاب، مکمل‌های درمانی و واکسیناسیون. مجهز به سیستم نمایش لحظه‌ای شرایط دمایی زنجیره سرد (۲ الی ۸ درجه سانتی‌گراد) و بررسی تداخل دارویی.
                            </p>

                            <!-- Feature Badges -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                                    <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">document_scanner</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">باکس تعاملی آپلود سریع نسخه پزشک (Rx Upload)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">بیمار عکس نسخه یا کد رهگیری را ثبت کرده و داروساز مقیم کمتر از ۱۵ دقیقه پاسخ و فاکتور صادر می‌کند.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                                    <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">ac_unit</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">دیده‌بان زنده پایش دمای زنجیره سرد (۲ تا ۸ درجه)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">تضمین سلامت واکسن‌ها و داروهای بیولوژیک با بسته‌بندی یونولیت و ژل یخ مخصوص.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                                    <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">warning</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">اتصال به پایشگر تداخلات دارویی دامپزشکی</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">بررسی خودکار سازگاری داروهای تجویزی با مکمل‌ها جهت پیشگیری از عوارض جانبی.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-purple-50/50 border border-purple-100">
                                    <span class="material-symbols-outlined text-purple-600 text-xl shrink-0 mt-0.5">local_shipping</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">ارسال فوری داخل‌شهری با پیک و بین‌شهری با پست پیشتاز</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">امکان انتخاب تحویل فوری با بیمه کالا و کد رهگیری مرسوله پستی.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                <a href="site.php?slug=sina-pharmacy" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-purple-700/20 transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>مشاهده دمو زنده وب‌سایت داروخانه</span>
                                </a>
                                <button type="button" onclick="openOrderModal('pharmacist')" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base text-purple-400">add_task</span>
                                    <span>سفارش و راه‌اندازی این قالب</span>
                                </button>
                            </div>
                        </div>

                        <!-- Visual Mockup Showcase -->
                        <div class="lg:col-span-5 flex flex-col items-center">
                            <div class="w-full bg-slate-900 rounded-3xl p-3 shadow-2xl border-4 border-slate-800 relative group overflow-hidden">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <span class="text-slate-300 font-bold">sina-pharmacy.asena.company</span>
                                    <span class="material-symbols-outlined text-xs text-purple-400">lock</span>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 aspect-[4/3] flex flex-col justify-between p-4 text-white">
                                    <div class="space-y-2">
                                        <div class="inline-flex items-center gap-1 bg-purple-500/20 text-purple-300 text-[10px] px-2.5 py-1 rounded-full border border-purple-400/30">
                                            <span class="material-symbols-outlined text-xs">ac_unit</span>
                                            <span>زنجیره سرد فعال: ۳.۸°C</span>
                                        </div>
                                        <h4 class="text-lg font-black text-white">داروخانه تخصصی دکتر فیروزی (سینا)</h4>
                                        <p class="text-xs text-slate-300 leading-normal">تأمین و توزیع تخصصی واکسن‌ها، سرم‌ها و مکمل‌های تقویتی</p>
                                    </div>
                                    <!-- Interactive mini widgets teaser in mockup -->
                                    <div class="grid grid-cols-2 gap-2 text-center text-xs">
                                        <div class="bg-white/10 rounded-xl p-2 backdrop-blur-xs border border-white/10">
                                            <div class="text-purple-300 font-bold">آپلود سریع نسخه</div>
                                            <div class="text-[10px] text-slate-400">پاسخگویی زیر ۱۵ دقیقه</div>
                                        </div>
                                        <div class="bg-white/10 rounded-xl p-2 backdrop-blur-xs border border-white/10">
                                            <div class="text-cyan-300 font-bold">بسته‌بندی ایزوله یخ</div>
                                            <div class="text-[10px] text-slate-400">ارسال بدون افت دما</div>
                                        </div>
                                    </div>
                                    <a href="site.php?slug=sina-pharmacy" target="_blank" class="w-full py-2 bg-purple-600 hover:bg-purple-500 rounded-xl text-center text-xs font-bold transition-colors">
                                        مشاهده پیش‌نمایش زنده در تب جدید
                                    </a>
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-2 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-purple-600">check_circle</span>
                                <span>پالت رنگی: بنفش دارویی های‌تک (#7c3aed) و آبی زنجیره سرد</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 3: Pet Shop Archetype (Retail & Autoship) -->
            <div id="arch-panel-seller" class="arch-panel hidden transition-all duration-300">
                <div class="bg-white rounded-[2rem] md:rounded-[3rem] p-6 sm:p-8 md:p-12 border-2 border-orange-100 shadow-xl overflow-hidden relative">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6 text-right">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-orange-50 border border-orange-200 text-orange-800 text-xs font-black">
                                <span class="material-symbols-outlined text-sm text-orange-600">shopping_bag</span>
                                <span>الگوی تخصصی فروشگاه کالا، تنوع گونه‌ها و تحویل خودکار دوره‌ای</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                وب‌سایت پت‌شاپ‌ها و هایپرمارکت‌های ملزومات حیوانات
                            </h3>
                            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                پلتفرم فروشگاهی قدرتمند برای عرضه غذای خشک، کنسرو، تشویقی، خاک و اکسسوری پت. مجهز به تفکیک سریع بر اساس گونه (سگ، گربه، پرنده، جوندگان)، شگفت‌انگیزها، انبارداری لحظه‌ای و سامانه اشتراک دوره‌ای خودکار (Autoship).
                            </p>

                            <!-- Feature Badges -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-orange-50/50 border border-orange-100">
                                    <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">pets</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">فیلتر سریع گونه حیوانات (سگ، گربه، پرنده، آبزیان)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">دسترسی فوری مشتری به غذای متناسب با نژاد، سن و حساسیت‌های غذایی حیوان.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-orange-50/50 border border-orange-100">
                                    <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">autorenew</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">سرویس تحویل ماهانه دوره‌ای با تخفیف دائمی (Autoship)</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">تثبیت وفاداری مشتری و ارسال خودکار ماهانه غذای خشک بدون نیاز به خرید مجدد.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-orange-50/50 border border-orange-100">
                                    <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">local_fire_department</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">ویترین شگفت‌انگیزها و شمارشگر معکوس تخفیف</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">افزایش چشمگیر نرخ تبدیل خرید با بنرهای تایمردار و نمایش موجودی انبار.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-orange-50/50 border border-orange-100">
                                    <span class="material-symbols-outlined text-orange-600 text-xl shrink-0 mt-0.5">account_balance_wallet</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">اتصال به درگاه بانکی شاپرک و تسویه حساب خودکار پایا</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">واریز مستقیم و منظم وجوه حاصل از فروش کالاها به شماره شبای بانکی شما.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                <a href="site.php?slug=petland-store" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-orange-600/20 transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>مشاهده دمو زنده وب‌سایت پت‌شاپ</span>
                                </a>
                                <button type="button" onclick="openOrderModal('seller')" class="px-6 py-3.5 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base text-orange-400">add_task</span>
                                    <span>سفارش و راه‌اندازی این قالب</span>
                                </button>
                            </div>
                        </div>

                        <!-- Visual Mockup Showcase -->
                        <div class="lg:col-span-5 flex flex-col items-center">
                            <div class="w-full bg-slate-900 rounded-3xl p-3 shadow-2xl border-4 border-slate-800 relative group overflow-hidden">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <span class="text-slate-300 font-bold">petland-store.asena.company</span>
                                    <span class="material-symbols-outlined text-xs text-orange-400">lock</span>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 aspect-[4/3] flex flex-col justify-between p-4 text-white">
                                    <div class="space-y-2">
                                        <div class="inline-flex items-center gap-1 bg-orange-500/20 text-orange-300 text-[10px] px-2.5 py-1 rounded-full border border-orange-400/30">
                                            <span class="material-symbols-outlined text-xs">local_shipping</span>
                                            <span>ارسال فوری به تمام کشور</span>
                                        </div>
                                        <h4 class="text-lg font-black text-white">هایپرمارکت آنلاین ملزومات پت‌لند</h4>
                                        <p class="text-xs text-slate-300 leading-normal">تنوع بی‌نظیر غذا، تشویقی و بهداشتی با ۱۰٪ تخفیف اتوشیپ</p>
                                    </div>
                                    <!-- Interactive mini widgets teaser in mockup -->
                                    <div class="grid grid-cols-2 gap-2 text-center text-xs">
                                        <div class="bg-white/10 rounded-xl p-2 backdrop-blur-xs border border-white/10">
                                            <div class="text-orange-400 font-bold">تخفیف اشتراک Autoship</div>
                                            <div class="text-[10px] text-slate-400">۱۰٪ کسر ماهانه دائمی</div>
                                        </div>
                                        <div class="bg-white/10 rounded-xl p-2 backdrop-blur-xs border border-white/10">
                                            <div class="text-amber-300 font-bold">ضمانت اصالت و مرجوعی</div>
                                            <div class="text-[10px] text-slate-400">مهلت تست ۷ روزه کالا</div>
                                        </div>
                                    </div>
                                    <a href="site.php?slug=petland-store" target="_blank" class="w-full py-2 bg-orange-600 hover:bg-orange-500 rounded-xl text-center text-xs font-bold transition-colors">
                                        مشاهده پیش‌نمایش زنده در تب جدید
                                    </a>
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-2 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-orange-600">check_circle</span>
                                <span>پالت رنگی: نارنجی پویا و پرانرژی (#ea580c) و زرد عسلی</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel 4: Hospital / Organization Archetype (Multi-Department Ecosystem) -->
            <div id="arch-panel-organization" class="arch-panel hidden transition-all duration-300">
                <div class="bg-white rounded-[2rem] md:rounded-[3rem] p-6 sm:p-8 md:p-12 border-2 border-slate-200 shadow-xl overflow-hidden relative">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6 text-right">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-900 text-xs font-black">
                                <span class="material-symbols-outlined text-sm text-blue-700">emergency</span>
                                <span>الگوی تخصصی مجتمع‌های بیمارستانی، اورژانس ۲۴/۷ و دپارتمان‌های پاراکلینیک</span>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                                وب‌سایت بیمارستان‌ها، پلی‌کلینیک‌ها و اورژانس شبانه‌روزی
                            </h3>
                            <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
                                ساختار فوق‌پیشرفته و سازمانی برای مراکز با کادر درمانی چندنفره، بخش بستری و آزمایشگاه. مجهز به نوار اورژانس قرمز رنگ با تماس تک‌لمسی، دایرکتوری بخش‌های تخصصی (جراحی، سونوگرافی، رادیولوژی، ICU) و نمایش روزهای حضور پزشکان.
                            </p>

                            <!-- Feature Badges -->
                            <div class="space-y-3 pt-2">
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                                    <span class="material-symbols-outlined text-rose-600 text-xl shrink-0 mt-0.5">e911_emergency</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">نوار قرمز اورژانس شبانه‌روزی و تریاژ ۲۴/۷ با اعزام آمبولانس</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">دکمه تماس بدون معطلی با اورژانس و نقشه مسیریابی مستقیم به درب بیمارستان.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                                    <span class="material-symbols-outlined text-blue-700 text-xl shrink-0 mt-0.5">grid_view</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">چیدمان بنتو برای دپارتمان‌های تخصصی و تجهیزات تشخیصی</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">معرفی بخش‌های جراحی ارتوپدی، اکوکاردیوگرافی، آندوسکوپی و بانک خون حیوانات.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                                    <span class="material-symbols-outlined text-teal-600 text-xl shrink-0 mt-0.5">groups</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">کارتابل معرفی پزشکان همکار با برنامه شیفت‌های بالینی</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">قابلیت رزرو آنلاین نوبت مستقیماً برای پزشک و دپارتمان مدنظر مراجع.</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                                    <span class="material-symbols-outlined text-indigo-600 text-xl shrink-0 mt-0.5">policy</span>
                                    <div>
                                        <div class="font-black text-xs sm:text-sm text-slate-900">صدور فاکتور رسمی بیمه و قوانین ملاقات بخش بستری</div>
                                        <div class="text-[11px] sm:text-xs text-slate-600">نمایش شرایط ناشتایی قبل از جراحی، تعرفه مصوب و لیست بیمه‌های طرف قرارداد.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- CTAs -->
                            <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100">
                                <a href="site.php?slug=razi-hospital" target="_blank" rel="noopener noreferrer" class="px-6 py-3.5 rounded-xl bg-[#001a48] hover:bg-[#002666] text-white font-black text-xs sm:text-sm shadow-md hover:shadow-blue-900/20 transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    <span>مشاهده دمو زنده وب‌سایت بیمارستان</span>
                                </a>
                                <button type="button" onclick="openOrderModal('organization')" class="px-6 py-3.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs sm:text-sm shadow-sm transition-all flex items-center gap-2 cursor-pointer">
                                    <span class="material-symbols-outlined text-base text-amber-300">add_task</span>
                                    <span>سفارش و راه‌اندازی این قالب</span>
                                </button>
                            </div>
                        </div>

                        <!-- Visual Mockup Showcase -->
                        <div class="lg:col-span-5 flex flex-col items-center">
                            <div class="w-full bg-slate-900 rounded-3xl p-3 shadow-2xl border-4 border-slate-800 relative group overflow-hidden">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-slate-800 rounded-xl mb-3 text-slate-400 text-[11px] font-mono" dir="ltr">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <span class="text-slate-300 font-bold">razi-hospital.asena.company</span>
                                    <span class="material-symbols-outlined text-xs text-teal-400">lock</span>
                                </div>
                                <div class="relative rounded-2xl overflow-hidden bg-slate-950 aspect-[4/3] flex flex-col justify-between p-4 text-white">
                                    <div class="space-y-2">
                                        <div class="inline-flex items-center gap-1 bg-rose-500/20 text-rose-300 text-[10px] px-2.5 py-1 rounded-full border border-rose-400/30">
                                            <span class="material-symbols-outlined text-xs">emergency</span>
                                            <span>اورژانس و تریاژ ۲۴ ساعته فعال</span>
                                        </div>
                                        <h4 class="text-lg font-black text-white">بیمارستان تخصصی دامپزشکی رازی</h4>
                                        <p class="text-xs text-slate-300 leading-normal">مجتمع درمانی ارجاعی، جراحی پیشرفته و رادیولوژی دیجیتال</p>
                                    </div>
                                    <!-- Interactive mini widgets teaser in mockup -->
                                    <div class="grid grid-cols-2 gap-2 text-center text-xs">
                                        <div class="bg-white/10 rounded-xl p-2 backdrop-blur-xs border border-white/10">
                                            <div class="text-teal-400 font-bold">۱۴ پزشک متخصص مقیم</div>
                                            <div class="text-[10px] text-slate-400">برنامه هفتگی شیفت‌ها</div>
                                        </div>
                                        <div class="bg-white/10 rounded-xl p-2 backdrop-blur-xs border border-white/10">
                                            <div class="text-rose-400 font-bold">اتاق عمل و بستری ICU</div>
                                            <div class="text-[10px] text-slate-400">تجهیزات بیهوشی ایمن</div>
                                        </div>
                                    </div>
                                    <a href="site.php?slug=razi-hospital" target="_blank" class="w-full py-2 bg-[#001a48] hover:bg-[#022869] border border-white/20 rounded-xl text-center text-xs font-bold transition-colors">
                                        مشاهده پیش‌نمایش زنده در تب جدید
                                    </a>
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-2 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-[#001a48]">check_circle</span>
                                <span>پالت رنگی: سرمه‌ای سازمانی (#001a48)، فیروزه‌ای تیره و قرمز اضطراری</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- 3. Beat 3: Ecosystem Advantages & Built-in Superpowers (Bento Grid) -->
    <section class="space-y-8">
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold">
                <span class="material-symbols-outlined text-sm">auto_awesome</span>
                <span>امکانات استاندارد موجود در تمام وب‌سایت‌های آسنا</span>
            </div>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900">
                همه آنچه برای یک حضور دیجیتال بی‌نقص نیاز دارید
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                بدون هزینه‌های پنهان یا نیازمندی به هاست و سرور جداگانه؛ هر وب‌سایت آسنا به صورت یک پکیج جامع تحویل داده می‌شود.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Bento 1 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">style</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">استودیو ویرایش بدون کد (Visual Studio)</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    با استودیوی اختصاصی آسنا، در هر ساعت از شبانه‌روز می‌توانید متون، لوگو، تصاویر، رنگ‌بندی و جایگاه بلوک‌ها را با پیش‌نمایش آنی شخصی‌سازی کنید.
                </p>
            </div>

            <!-- Bento 2 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-[#fd8100] flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">qr_code_scanner</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">کارت ویزیت دیجیتال و استند رومیزی QR</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    همراه با وب‌سایت، کارت ویزیت هوشمند با فایل مستقیم مخاطب (vCard) در اختیار شماست تا مراجعین با اسکن آن در مطب، اطلاعات شما را در گوشی ذخیره کنند.
                </p>
            </div>

            <!-- Bento 3 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">verified_user</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">درگاه رسمی شاپرک و امانت‌داری مالی</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    تسویه حساب مستقیم بانکی، صدور فاکتور رسمی نظام دامپزشکی، گزارش فصلی ماده ۱۶۹ و امنیت مالی ۱۰۰٪ با سیستم ضمانت امن (Escrow).
                </p>
            </div>

            <!-- Bento 4 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">travel_explore</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">سئو تضمینی گوگل (Local SEO)</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    تولید خودکار داده‌های ساختاریافته Schema.org، ثبت ساعات کاری، لوکیشن نقشه و مقالات وبلاگ برای حضور در نتایج صفحه اول جستجوی محلی گوگل.
                </p>
            </div>

            <!-- Bento 5 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">install_mobile</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">فول PWA و عملکرد فوق‌سریع در موبایل</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    وب‌سایت شما دقیقاً شبیه یک اپلیکیشن بومی روی گوشی‌های اندروید و iOS نصب می‌شود و در شرایط اینترنت ضعیف نیز عملکرد باثباتی دارد.
                </p>
            </div>

            <!-- Bento 6 -->
            <div class="p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-2xl">sms</span>
                </div>
                <h3 class="text-lg font-black text-slate-900">اتوماسیون پیامکی نوبت‌ها و یادآوری واکسن</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    ارسال خودکار پیامک یادآوری وقت ویزیت، پیامک تایید سفارش دارو و یادآوری دوره‌ای واکسیناسیون و ضدانگل به مراجعین بدون نیاز به دخالت دستی.
                </p>
            </div>
        </div>
    </section>

    <!-- 4. Beat 4: Pricing Matrix (Matching config/tiers.php) -->
    <section id="pricing-matrix" class="space-y-8 scroll-mt-24">
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold">
                <span class="material-symbols-outlined text-sm">sell</span>
                <span>پلن‌های شفاف، اقتصادی و بدون درصد کارمزد پنهان</span>
            </div>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-900">
                تعرفه راه‌اندازی و پکیج‌های لایسنس
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                متناسب با حجم مراجعین و نیازهای مرکز خود، پکیج مناسب را انتخاب کنید. امکان ارتقا در هر زمان وجود دارد.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- Tier 1: Basic -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm hover:shadow-lg transition-all flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div class="text-xs font-black text-slate-500 uppercase tracking-wider">شروع فعالیت آنلاین</div>
                    <h3 class="text-xl font-black text-slate-900">نسخه پایه (Basic)</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">مناسب پزشکان تک‌مطب و فروشگاه‌های نوپا جهت معرفی خدمات و نوبت‌دهی آنلاین.</p>
                    <div class="py-2 border-y border-slate-100">
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 font-mono">۴,۹۰۰,۰۰۰</div>
                        <div class="text-[11px] text-slate-400">تومان / سالانه (شامل هاست و پشتیبانی)</div>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-600">
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>سامانه نوبت‌دهی و رزرو آنلاین</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>ویترین آنلاین خدمات یا کالاها</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>کارت ویزیت دیجیتال و QR اختصاصی</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>ساب‌دامین اختصاصی رایگان (.asena.company)</li>
                        <li class="flex items-center gap-2 text-slate-400"><span class="material-symbols-outlined text-slate-300 text-sm">close</span>سفارش خودکار ادواری (Autoship)</li>
                        <li class="flex items-center gap-2 text-slate-400"><span class="material-symbols-outlined text-slate-300 text-sm">close</span>اتوماسیون پیامک و دانشنامه سئو</li>
                    </ul>
                </div>
                <button type="button" onclick="openOrderModal(null, 'basic')" class="w-full py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-black text-xs transition-colors cursor-pointer">
                    انتخاب نسخه پایه
                </button>
            </div>

            <!-- Tier 2: Standard (Popular) -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border-2 border-indigo-600 shadow-xl relative flex flex-col justify-between space-y-6">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-indigo-600 text-white text-[11px] font-black shadow-md">
                    محبوب‌ترین انتخاب پزشکان
                </div>
                <div class="space-y-4 pt-2">
                    <div class="text-xs font-black text-indigo-600 uppercase tracking-wider">تجاری و وفاداری</div>
                    <h3 class="text-xl font-black text-slate-900">نسخه تجاری (Standard)</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">مجهز به دانشنامه تخصصی جهت سئو گوگل، ثبت نظرات بیماران و باشگاه مشتریان.</p>
                    <div class="py-2 border-y border-slate-100">
                        <div class="text-2xl sm:text-3xl font-black text-indigo-700 font-mono">۸,۸۰۰,۰۰۰</div>
                        <div class="text-[11px] text-slate-400">تومان / سالانه (شامل هاست و لایسنس کامل)</div>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-600">
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>تمامی امکانات نسخه پایه</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>وبلاگ و دانشنامه سئو محلی گوگل</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>باشگاه مشتریان و سیستم امتیاز وفاداری</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>ثبت نظرات و اعتبارسنجی مراجعین</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>اتصال به دامنه اختصاصی (.ir / .com)</li>
                        <li class="flex items-center gap-2 text-slate-400"><span class="material-symbols-outlined text-slate-300 text-sm">close</span>سامانه ویزیت تله‌هلث و چت</li>
                    </ul>
                </div>
                <button type="button" onclick="openOrderModal(null, 'standard')" class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs shadow-md transition-colors cursor-pointer">
                    انتخاب نسخه تجاری
                </button>
            </div>

            <!-- Tier 3: Premium -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm hover:shadow-lg transition-all flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div class="text-xs font-black text-amber-600 uppercase tracking-wider">حرفه‌ای و تمام‌عیار</div>
                    <h3 class="text-xl font-black text-slate-900">نسخه حرفه‌ای (Premium)</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">ویژه کلینیک‌های پرتردد با سفارش دوره‌ای اتوشیپ، تله‌هلث و اتوماسیون پیامکی.</p>
                    <div class="py-2 border-y border-slate-100">
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 font-mono">۱۴,۵۰۰,۰۰۰</div>
                        <div class="text-[11px] text-slate-400">تومان / سالانه (شامل پشتیبانی VIP)</div>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-600">
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>تمامی امکانات نسخه استاندارد</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>اشتراک دوره‌ای اتوشیپ (Autoship)</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>مشاوره آنلاین و تله‌هلث تصویری</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>اتوماسیون پیامک‌های نوبت و یادآوری واکسن</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>مدیریت چند ادمین و منشی مطب</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-500 text-sm">check</span>سرعت لود اولترا با CDN اختصاصی</li>
                    </ul>
                </div>
                <button type="button" onclick="openOrderModal(null, 'premium')" class="w-full py-3 rounded-xl bg-slate-900 hover:bg-black text-white font-black text-xs transition-colors cursor-pointer">
                    انتخاب نسخه حرفه‌ای
                </button>
            </div>

            <!-- Tier 4: Enterprise -->
            <div class="bg-gradient-to-b from-[#001a48] to-[#01112e] text-white rounded-3xl p-6 sm:p-8 border border-white/10 shadow-xl flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div class="text-xs font-black text-amber-300 uppercase tracking-wider">سازمانی و بیمارستانی</div>
                    <h3 class="text-xl font-black text-white">اینترپرایز جامع (Enterprise)</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">اکوسیستم همه‌جانبه بیمارستان‌ها، پلی‌کلینیک‌ها، داروخانه‌ها و شعب چندگانه.</p>
                    <div class="py-2 border-y border-white/10">
                        <div class="text-2xl sm:text-3xl font-black text-amber-300 font-mono">۲۲,۰۰۰,۰۰۰</div>
                        <div class="text-[11px] text-slate-300">تومان / سالانه (مشاوره معماری و پشتیبانی ۲۴/۷)</div>
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-200">
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-amber-300 text-sm">check</span>تمامی امکانات تمامی نسخه‌ها بدون محدودیت</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-amber-300 text-sm">check</span>ماژول داروخانه تخصصی، نسخه الکترونیک و زنجیره سرد</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-amber-300 text-sm">check</span>پشتیبانی از چندین دپارتمان و ده‌ها پزشک مقیم</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-amber-300 text-sm">check</span>مدیریت دسترسی‌های پیشرفته و کارتابل حسابداری</li>
                        <li class="flex items-center gap-2"><span class="material-symbols-outlined text-amber-300 text-sm">check</span>پشتیبان فنی اختصاصی و توافق‌نامه SLA ۹۹.۹٪</li>
                    </ul>
                </div>
                <button type="button" onclick="openOrderModal(null, 'enterprise')" class="w-full py-3 rounded-xl bg-gradient-to-r from-[#fd8100] to-amber-500 hover:from-[#e57400] hover:to-amber-600 text-white font-black text-xs shadow-lg transition-all cursor-pointer">
                    سفارش نسخه اینترپرایز
                </button>
            </div>

        </div>
    </section>

    <!-- 5. Beat 5: FAQ Accordion -->
    <section class="max-w-4xl mx-auto space-y-6">
        <div class="text-center space-y-2">
            <h2 class="text-xl sm:text-3xl font-black text-slate-900">پرسش‌های پرتکرار متقاضیان وب‌سایت</h2>
            <p class="text-xs sm:text-sm text-slate-500">پاسخ شفاف به دغدغه‌های راه‌اندازی و نگهداری وب‌سایت در آسنا</p>
        </div>

        <div class="space-y-3">
            <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-md transition-all">
                <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                    <span>آیا می‌توانم دامنه اختصاصی مطب خودم (مثلاً dr-alavi.ir یا .com) را وصل کنم؟</span>
                    <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                    بله، ۱۰۰٪. تمام وب‌سایت‌های آسنا به صورت پیش‌فرض یک ساب‌دامین پرسرعت و رایگان (yourname.asena.company) دریافت می‌کنند، اما شما در هر زمان می‌توانید دامنه ملی (.ir) یا بین‌المللی (.com) خود را به سادگی از طریق تنظیم DNS به وب‌سایتتان متصل نمایید و گواهی SSL امن به رایگان برای شما فعال خواهد شد.
                </div>
            </details>

            <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-md transition-all">
                <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                    <span>چقدر طول می‌کشد تا وب‌سایت من آماده و در اینترنت منتشر شود؟</span>
                    <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                    وب‌سایت شما بلافاصله پس از ثبت سفارش و تایید تلفنی در کمتر از ۵ دقیقه ایجاد و فعال می‌گردد. همچنین اطلاعات اولیه مطب، ساعات کاری و خدمات شما توسط کارشناس پشتیبانی آسنا ظرف حداکثر ۲۴ ساعت کاری در سایت بارگذاری و تحویل داده می‌شود.
                </div>
            </details>

            <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-md transition-all">
                <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                    <span>آیا می‌توانم در آینده قالب یا حوزه فعالیتم را تغییر دهم؟</span>
                    <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                    بله! با ورود به «استودیو طراحی وب‌سایت آسنا»، می‌توانید در هر لحظه الگوی ساختاری و رنگ‌بندی سایت را بین قالب‌های پزشک، داروخانه، پت‌شاپ یا بیمارستان سوئیچ کنید، بلوک‌ها را جابجا نمایید و ماژول‌های جدید را تنها با یک کلیک فعال کنید.
                </div>
            </details>

            <details class="group bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer open:shadow-md transition-all">
                <summary class="flex items-center justify-between font-bold text-slate-900 text-xs sm:text-sm select-none">
                    <span>آیا برای هاست، ترافیک ماهانه یا پشتیبانی فنی باید هزینه جداگانه‌ای بپردازم؟</span>
                    <span class="material-symbols-outlined text-slate-400 group-open:rotate-180 transition-transform">expand_more</span>
                </summary>
                <div class="pt-3 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 mt-3">
                    خیر، هیچ هزینه پنهانی وجود ندارد. کلیه هزینه‌های میزبانی ابری با پهنای باند نامحدود، بک‌آپ‌گیری روزانه، آپدیت‌های امنیتی مداوم و گواهی رمزنگاری SSL در اشتراک سالانه گنجانده شده است.
                </div>
            </details>
        </div>
    </section>

    <!-- 6. Beat 6: Final Conversion CTA -->
    <section class="rounded-3xl bg-gradient-to-r from-amber-500 via-[#fd8100] to-orange-600 p-8 sm:p-12 text-white text-center space-y-4 shadow-xl">
        <h2 class="text-2xl sm:text-3xl font-black">آماده‌اید وب‌سایت اختصاصی خود را تحویل بگیرید؟</h2>
        <p class="text-xs sm:text-base text-amber-100 max-w-xl mx-auto font-medium">
            همین حالا شناسه دلخواهتان را رزرو کنید و با راه‌اندازی سریع وب‌سایت، یک قدم بزرگ به سمت اتوماسیون مطب و افزایش مراجعین بردارید.
        </p>
        <div class="pt-2">
            <button type="button" onclick="openOrderModal()" class="px-8 py-4 rounded-2xl bg-slate-900 hover:bg-black text-white font-black text-sm shadow-2xl transition-all cursor-pointer inline-flex items-center gap-2 active:scale-95">
                <span class="material-symbols-outlined text-amber-400 text-lg">rocket_launch</span>
                <span>همین الان سفارش خود را ثبت کنید</span>
            </button>
        </div>
    </section>

</div>

<!-- Interactive Order / Purchase Modal -->
<div id="websiteOrderModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-[2rem] max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6 relative text-right animate-scale-in my-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-lg sm:text-xl font-black text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#fd8100]">web</span>
                    <span>ثبت سفارش و راه‌اندازی وب‌سایت</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">تکمیل فرم در ۲ دقیقه؛ تحویل فوری با پشتیبانی کارشناسان آسنا</p>
            </div>
            <button type="button" onclick="closeOrderModal()" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Form -->
        <form id="websiteOrderForm" onsubmit="submitWebsiteOrder(event)" class="space-y-4">
            
            <!-- Step 1: Select Archetype -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">۱. انتخاب نوع وب‌سایت متناسب با تخصص شما *</label>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="order_archetype" value="doctor" checked class="text-emerald-600">
                        <span class="font-bold text-slate-800">پزشکان و جراحان</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-purple-600 has-[:checked]:bg-purple-50/50">
                        <input type="radio" name="order_archetype" value="pharmacist" class="text-purple-600">
                        <span class="font-bold text-slate-800">داروخانه‌های تخصصی</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-orange-600 has-[:checked]:bg-orange-50/50">
                        <input type="radio" name="order_archetype" value="seller" class="text-orange-600">
                        <span class="font-bold text-slate-800">پت‌شاپ‌ها و فروشگاه</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 cursor-pointer has-[:checked]:border-blue-700 has-[:checked]:bg-blue-50/50">
                        <input type="radio" name="order_archetype" value="organization" class="text-blue-700">
                        <span class="font-bold text-slate-800">بیمارستان‌ها و مراکز</span>
                    </label>
                </div>
            </div>

            <!-- Step 2: Select Tier -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">۲. سطح لایسنس و نسخه مورد نظر *</label>
                <select name="order_tier" id="order_tier" class="w-full text-xs p-3 rounded-xl border border-slate-200 font-bold text-slate-800 focus:border-indigo-600 focus:outline-none">
                    <option value="standard" selected>نسخه تجاری (Standard) - ۸,۸۰۰,۰۰۰ تومان (پیشنهادی)</option>
                    <option value="basic">نسخه پایه (Basic) - ۴,۹۰۰,۰۰۰ تومان</option>
                    <option value="premium">نسخه حرفه‌ای فول کلینیک (Premium) - ۱۴,۵۰۰,۰۰۰ تومان</option>
                    <option value="enterprise">نسخه اینترپرایز جامع سازمانی (Enterprise) - ۲۲,۰۰۰,۰۰۰ تومان</option>
                </select>
            </div>

            <!-- Step 3: Desired Subdomain -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">۳. شناسه و آدرس ساب‌دامین اختصاصی مورد نظر *</label>
                <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-indigo-600">
                    <span class="px-3 text-xs text-slate-400 font-mono bg-slate-50 border-l border-slate-200">.asena.company</span>
                    <input type="text" name="order_desired_slug" id="order_desired_slug" oninput="checkOrderSlug(this.value)" placeholder="مثلاً dr-alavi" class="flex-1 text-xs p-3 outline-none font-mono text-left font-bold" dir="ltr" required>
                </div>
                <div id="order-slug-feedback" class="text-[11px] mt-1 text-slate-400">شناسه اختصاصی سایت شما در اینترنت</div>
            </div>

            <!-- Step 4: Contact Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">نام و نام خانوادگی / نام مرکز *</label>
                    <input type="text" name="order_full_name" required placeholder="مثلاً دکتر محمدرضا علوی" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-indigo-600 focus:outline-none font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">شماره تماس همراه (جهت هماهنگی) *</label>
                    <input type="tel" name="order_phone" required placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-indigo-600 focus:outline-none font-mono font-bold text-left">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">ایمیل (اختیاری)</label>
                <input type="email" name="order_email" placeholder="info@example.com" dir="ltr" class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-indigo-600 focus:outline-none font-mono text-left">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">یادداشت یا توضیحات ویژه (اختیاری)</label>
                <textarea name="order_notes" rows="2" placeholder="اگر دامنه خاصی مدنظر دارید یا توضیحی برای کادر فنی دارید بنویسید..." class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:border-indigo-600 focus:outline-none"></textarea>
            </div>

            <!-- Submit Button & Feedback -->
            <div id="order-form-feedback" class="hidden text-xs font-bold p-3 rounded-xl"></div>

            <div class="pt-2 flex items-center justify-end gap-3">
                <button type="button" onclick="closeOrderModal()" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer">
                    انصراف
                </button>
                <button type="submit" id="order-submit-btn" class="px-7 py-3 rounded-xl bg-gradient-to-r from-[#fd8100] to-amber-500 hover:from-[#e57400] hover:to-amber-600 text-white font-black text-xs shadow-md transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined text-base">check_circle</span>
                    <span>تایید و ارسال درخواست فعال‌سازی</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
// Archetype tabs switcher
function selectArchetypeTab(archetype) {
    document.querySelectorAll('.arch-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.arch-tab-btn').forEach(b => {
        b.classList.remove('bg-white', 'text-emerald-800', 'text-purple-800', 'text-orange-800', 'text-blue-800', 'border', 'border-emerald-200', 'border-purple-200', 'border-orange-200', 'border-blue-200', 'shadow-sm');
        b.classList.add('text-slate-600');
    });

    const activePanel = document.getElementById('arch-panel-' + archetype);
    const activeBtn = document.getElementById('arch-tab-' + archetype);

    if (activePanel) activePanel.classList.remove('hidden');
    if (activeBtn) {
        activeBtn.classList.remove('text-slate-600');
        activeBtn.classList.add('bg-white', 'shadow-sm', 'border');
        if (archetype === 'doctor') activeBtn.classList.add('text-emerald-800', 'border-emerald-200');
        else if (archetype === 'pharmacist') activeBtn.classList.add('text-purple-800', 'border-purple-200');
        else if (archetype === 'seller') activeBtn.classList.add('text-orange-800', 'border-orange-200');
        else if (archetype === 'organization') activeBtn.classList.add('text-blue-800', 'border-blue-200');
    }
}

// Hero Subdomain Live Checker
let heroTimer = null;
function checkSubdomainFromHero() {
    const input = document.getElementById('hero-subdomain-input');
    const feedback = document.getElementById('hero-subdomain-feedback');
    const slug = (input ? input.value : '').trim();

    if (!slug) {
        feedback.className = 'mt-3 text-xs md:text-sm font-bold text-amber-300 block';
        feedback.innerText = 'لطفاً یک نام انگلیسی تایپ کنید.';
        return;
    }

    feedback.className = 'mt-3 text-xs md:text-sm font-bold text-slate-200 block';
    feedback.innerText = 'در حال استعلام در سامانه آسنا...';

    const formData = new FormData();
    formData.append('action', 'check_slug');
    formData.append('slug', slug);

    fetch('actions/website_order_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.available) {
            feedback.className = 'mt-3 text-xs md:text-sm font-black text-emerald-300 block';
            feedback.innerHTML = `✓ ${data.message} <button type="button" onclick="openOrderModal(null, null, '${data.slug}')" class="underline mr-2 text-white font-black hover:text-amber-200 cursor-pointer">سفارش و ثبت فوری</button>`;
        } else {
            feedback.className = 'mt-3 text-xs md:text-sm font-bold text-rose-300 block';
            feedback.innerText = '✕ ' + (data.message || 'این آدرس قبلاً ثبت شده است.');
        }
    })
    .catch(() => {
        feedback.className = 'mt-3 text-xs md:text-sm font-bold text-rose-300 block';
        feedback.innerText = 'خطا در ارتباط با سرور.';
    });
}

// Modal management
function openOrderModal(archetype = null, tier = null, slug = null) {
    const modal = document.getElementById('websiteOrderModal');
    if (!modal) return;
    
    if (archetype) {
        const rad = modal.querySelector(`input[name="order_archetype"][value="${archetype}"]`);
        if (rad) rad.checked = true;
    }

    if (tier) {
        const select = document.getElementById('order_tier');
        if (select) select.value = tier;
    }

    if (slug) {
        const slugInput = document.getElementById('order_desired_slug');
        if (slugInput) {
            slugInput.value = slug;
            checkOrderSlug(slug);
        }
    } else {
        const heroInput = document.getElementById('hero-subdomain-input');
        const slugInput = document.getElementById('order_desired_slug');
        if (heroInput && heroInput.value.trim() && slugInput && !slugInput.value.trim()) {
            slugInput.value = heroInput.value.trim();
            checkOrderSlug(slugInput.value);
        }
    }

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeOrderModal() {
    const modal = document.getElementById('websiteOrderModal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// Live slug checker inside order modal
let orderSlugTimer = null;
function checkOrderSlug(val) {
    clearTimeout(orderSlugTimer);
    const feedback = document.getElementById('order-slug-feedback');
    if (!val || val.trim().length < 3) {
        if (feedback) {
            feedback.innerText = 'حداقل ۳ کاراکتر انگلیسی وارد کنید.';
            feedback.className = 'text-[11px] mt-1 text-slate-400';
        }
        return;
    }

    orderSlugTimer = setTimeout(() => {
        const formData = new FormData();
        formData.append('action', 'check_slug');
        formData.append('slug', val.trim());

        fetch('actions/website_order_action.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (!feedback) return;
            if (data.available) {
                feedback.innerText = `✓ شناسه ${data.slug}.asena.company آزاد است.`;
                feedback.className = 'text-[11px] mt-1 text-emerald-600 font-bold';
            } else {
                feedback.innerText = `✕ ${data.message}`;
                feedback.className = 'text-[11px] mt-1 text-rose-600 font-bold';
            }
        })
        .catch(() => {});
    }, 350);
}

// Order Form Submission
function submitWebsiteOrder(e) {
    e.preventDefault();
    const form = document.getElementById('websiteOrderForm');
    const feedback = document.getElementById('order-form-feedback');
    const submitBtn = document.getElementById('order-submit-btn');

    if (!form) return;

    const formData = new FormData(form);
    formData.append('action', 'submit_order');
    formData.append('archetype', form.querySelector('input[name="order_archetype"]:checked')?.value || 'doctor');
    formData.append('tier', form.querySelector('#order_tier')?.value || 'standard');
    formData.append('desired_slug', form.querySelector('#order_desired_slug')?.value || '');
    formData.append('full_name', form.querySelector('input[name="order_full_name"]')?.value || '');
    formData.append('phone', form.querySelector('input[name="order_phone"]')?.value || '');
    formData.append('email', form.querySelector('input[name="order_email"]')?.value || '');
    formData.append('notes', form.querySelector('textarea[name="order_notes"]')?.value || '');

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">sync</span><span>در حال ثبت...</span>';
    }

    fetch('actions/website_order_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            feedback.className = 'text-xs font-bold p-4 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 block';
            feedback.innerHTML = `
                <div class="flex items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-lg text-emerald-600">check_circle</span>
                    <span class="text-sm">سفارش شماره #${data.order_id} با موفقیت ثبت گردید!</span>
                </div>
                <div class="text-[11px] leading-relaxed text-slate-700">${data.message}</div>
            `;
            form.reset();
            if (submitBtn) submitBtn.style.display = 'none';
        } else {
            feedback.className = 'text-xs font-bold p-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 block';
            feedback.innerText = '✕ ' + (data.message || 'خطا در ثبت اطلاعات.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="material-symbols-outlined text-base">check_circle</span><span>تلاش مجدد</span>';
            }
        }
    })
    .catch(() => {
        feedback.className = 'text-xs font-bold p-3 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 block';
        feedback.innerText = 'خطا در برقراری ارتباط با سامانه.';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-symbols-outlined text-base">check_circle</span><span>تلاش مجدد</span>';
        }
    });
}
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
