<?php
require_once 'includes/db.php';
require_once 'includes/ContractService.php';

// Supported roles & Legal Chapters
$roleKeys = ['consumer', 'doctor', 'pharmacist', 'seller', 'organization', 'general'];
$roleTitles = [
    'consumer'     => ['title' => 'سرپرستان پت و خریداران', 'icon' => 'pets', 'badge' => 'حقوق مصرف‌کننده'],
    'doctor'       => ['title' => 'پزشکان و دامپزشکان', 'icon' => 'stethoscope', 'badge' => 'نظام دامپزشکی'],
    'pharmacist'   => ['title' => 'داروخانه‌ها و داروسازان', 'icon' => 'medication', 'badge' => 'سازمان غذا و دارو'],
    'seller'       => ['title' => 'فروشندگان مارکت‌پلیس', 'icon' => 'storefront', 'badge' => 'مبارزه با قاچاق'],
    'organization' => ['title' => 'مراکز درمانی و کلینیک‌ها', 'icon' => 'local_hospital', 'badge' => 'پروانه بهداشتی'],
    'general'      => ['title' => 'شرایط عمومی و مصونیت پلتفرم', 'icon' => 'security', 'badge' => 'سلب مسئولیت واسط']
];

$selectedRole = $_GET['role'] ?? 'consumer';
if (!in_array($selectedRole, $roleKeys, true)) {
    $selectedRole = 'consumer';
}

$page_title = 'منشور حقوقی، قوانین و الزامات قانونی خدمات چندنقشی | سامانه جامع آسنا';
$meta_description = 'مقررات جامع، مستندات قانونی و شرایط استفاده سامانه آسنا برای خریداران، دامپزشکان، داروخانه‌ها، فروشندگان مارکت‌پلیس و مراکز درمانی منطبق بر قوانین جمهوری اسلامی ایران.';

require_once 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 font-medium">
            <a href="index.php" class="hover:text-primary transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs text-slate-400">chevron_left</span>
            <span class="text-primary font-bold">مرکز اسناد حقوقی و قوانین حاکم بر سامانه آسنا</span>
        </nav>

        <!-- Header Card -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-200/80 mb-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-accent via-primary to-emerald-500"></div>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 bg-primary/10 text-primary border border-primary/20 text-xs font-bold px-3 py-1 rounded-full mb-3">
                        <span class="material-symbols-outlined text-xs">gavel</span>
                        منشور رسمی حقوقی، شفافیت تجاری و حاکمیت چندنقشی آسنا اینترپرایز
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight mb-2">
                        قوانین، ضوابط قانونی و شرایط الزام‌آور استفاده از سامانه
                    </h1>
                    <p class="text-sm text-slate-600 leading-relaxed max-w-2xl">
                        این مجموعه قوانین و مقررات به منظور صیانت تام از حقوق متقابل کاربران، متخصصین و شرکای تجاری، و تضمین انطباق صددرصدی با موازین قانونی و نهادهای نظارتی جمهوری اسلامی ایران تدوین گردیده است.
                    </p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-accent/10 flex items-center justify-center text-accent flex-shrink-0 border border-accent/20">
                    <span class="material-symbols-outlined text-3xl">verified_user</span>
                </div>
            </div>

            <!-- Statutory Framework Badges -->
            <div class="mt-6 pt-6 border-t border-slate-100">
                <div class="text-[11px] font-bold text-slate-500 mb-2.5 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-xs text-primary">account_balance</span>
                    <span>اسناد بالادستی و موازین قانونی حاکم:</span>
                </div>
                <div class="flex flex-wrap gap-2 text-[11px] font-semibold text-slate-600">
                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200/70">قانون تجارت الکترونیکی (مصوب ۱۳۸۲)</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200/70">قانون حمایت از حقوق مصرف‌کنندگان (مصوب ۱۳۸۸)</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200/70">قانون سازمان نظام دامپزشکی ج.ا.ایران (مصوب ۱۳۷۶)</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200/70">مقررات سازمان غذا و دارو (IFDA)</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200/70">قانون مبارزه با قاچاق کالا و ارز (مصوب ۱۳۹۲)</span>
                    <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200/70">قانون جرایم رایانه‌ای و ضوابط پلیس فتا (مصوب ۱۳۸۸)</span>
                </div>
            </div>
        </div>

        <!-- Role Navigation Tabs -->
        <div class="bg-white rounded-2xl p-2 shadow-sm border border-slate-200/80 mb-8 sticky top-4 z-20 backdrop-blur-md bg-white/90">
            <div class="flex flex-wrap items-center gap-2 justify-center sm:justify-start">
                <?php foreach ($roleTitles as $rk => $rMeta): ?>
                    <?php $isActive = ($selectedRole === $rk); ?>
                    <a href="terms.php?role=<?= $rk ?>" 
                       class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all <?= $isActive ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-slate-100/70 text-slate-600 hover:bg-slate-200/70' ?>">
                        <span class="material-symbols-outlined text-sm"><?= $rMeta['icon'] ?></span>
                        <span><?= $rMeta['title'] ?></span>
                        <span class="text-[10px] opacity-80 px-1.5 py-0.5 rounded-md <?= $isActive ? 'bg-white/20 text-white' : 'bg-white text-slate-500' ?>"><?= $rMeta['badge'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Role Content Sections -->
        <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-slate-200/80 space-y-10 text-slate-700 leading-relaxed text-sm">
            
            <?php if ($selectedRole === 'consumer'): ?>
                <!-- 1. Consumer / Pet Parent Terms -->
                <div class="space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full inline-block mb-2">فصل اول: ضوابط سرپرستان پت و خریداران عمومی</span>
                        <h2 class="text-xl font-black text-slate-900">حقوق، تکالیف و تعهدات خریداران، مراجعین درمانی و مشترکین آسنا</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-emerald-900">
                            <span class="material-symbols-outlined text-sm text-emerald-600">verified</span>
                            امانت‌داری مالی و تضمین مهلت تست ۷ روزه (ماده ۳۷ قانون تجارت الکترونیکی)
                        </div>
                        <p class="text-xs text-emerald-800 leading-relaxed">
                            کلیه مبالغ پرداختی شما برای سفارش‌های ملزومات پت، تا ۷ روز پس از اعلام تحویل رسمی توسط وب‌سرویس شرکت ملی پست یا پیک درون‌شهری، در حساب واسط امانی (Escrow) آسنا مسدود می‌ماند و تنها پس از تایید انطباق و عدم اعلام نقص یا مغایرت از سوی خریدار، با فروشنده تسویه می‌گردد.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">اهلیت قانونی و احراز هویت سرپرست (ماده ۳۳ قانون تجارت الکترونیک)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    ثبت سفارش و دریافت خدمات درمانی در آسنا مستلزم داشتن حداقل ۱۸ سال تمام خورشیدی یا اذن ولی/قیم قانونی است. شماره تلفن همراه ثبت‌شده باید منطبق با کد ملی شخص در سامانه شاهکار باشد. ثبت هرگونه اطلاعات هویتی غیرواقعی، منجر به سلب کلیه حقوق پیگیری و مسئولیت کیفری احتمالی متوجه کاربر خواهد بود.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">استثنائات قانونی عودت کالا به دلایل ایمنی زیستی و بهداشتی (ماده ۳۸ قانون تجارت الکترونیک)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    طبق پروتکل‌های پیشگیری از شیوع زئونوزها (بیماری‌های مشترک انسان و حیوان) و استانداردهای سازمان غذا و دارو، اقلام ذیل پس از تحویل و باز شدن پلمپ به هیچ وجه مشمول حق انصراف ۷ روزه نمی‌شوند مگر در صورت اثبات فساد ظاهری، شکستگی یا انقضای تاریخ مصرف در لحظه تحویل:
                                </p>
                                <ul class="list-disc list-inside text-xs text-slate-500 space-y-1 pr-2 pt-1">
                                    <li>بسته‌های باز شده غذای خشک، کنسرو، پوچ و تشویقی‌های خوراکی حیوانات</li>
                                    <li>مکمل‌های تقویتی، ویتامین‌ها، داروهای OTC و فرآورده‌های زیستی-درمانی</li>
                                    <li>محصولات آرایشی، بهداشتی، قطره‌های چشمی و گوشی باز شده</li>
                                    <li>قلاده و لباس‌های تن‌خورده که خطر انتقال انگل‌ها و قارچ‌های پوستی را دارند</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۳</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">ضوابط سرویس سفارش دوره‌ای و تحویل خودکار (Autoship)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    مشترکین اتوشیپ از تخفیف دائمی ۱۵٪ و ارسال در فواصل زمانی انتخابی بهره‌مند می‌شوند. آسنا تضمین می‌نماید صرفاً کالاهایی در باکس‌های پیشنهادی قرار گیرند که تأمین‌کننده حداقل ذخیره ۳ ماهه انبار را تضمین کرده باشد. کاربر مجاز است تا ۴۸ ساعت قبل از موعد صدور فاکتور دوره‌ای، اشتراک را بدون کسر کارمزد یا جریمه لغو یا موعد آن را جابجا نماید.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۴</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">مرزهای قانونی تله‌هلث و سلب مسئولیت صریح در شرایط اورژانس</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    مشاوره‌های آنلاین متنی و صوتی-تصویری صرفاً جهت تریاژ اولیه، راهنمایی مراقبتی و پیگیری درمان قبلی است. در موارد اورژانسی شدید نظیر خونریزی‌های کنترل‌نشده، تشنج مداوم، مسمومیت‌های حاد دارویی/شیمیایی، تصادفات شدید و خفگی، سرپرست موظف است بیمار را بلادرنگ به نزدیک‌ترین بیمارستان یا کلینیک شبانه‌روزی حضوری منتقل نماید. آسنا و پزشکان همکار هیچ مسئولیتی در قبال تلف شدن یا وخامت حال حیوان ناشی از تاخیر سرپرست در مراجعه حضوری ندارند.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۵</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">ضوابط کنسلی نوبت‌های رزروشده کلینیک و مشاوره آنلاین</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    لغو نوبت تا ۴ ساعت پیش از زمان موعد، بدون کسر کارمزد با استرداد ۱۰۰٪ به کیف‌پول آسنا انجام می‌شود. لغو کمتر از ۴ ساعت تا نوبت، شامل کسر ۱۰٪ بابت جبران خسارت نوبت خالی پزشک خواهد بود. در صورت عدم حضور پزشک، ۱۰۰٪ مبلغ عودت و کد تخفیف عذرخواهی برای ویزیت بعدی به کاربر اعطا می‌شود.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۶</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">صیانت از محرمانگی پرونده سلامت و داده‌ها (مواد ۵۸ و ۵۹ قانون تجارت الکترونیک)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    سوابق پزشکی، تاریخچه واکسیناسیون و نسخه‌های حیوان خانگی در زیرساخت ابری رمزنگاری‌شده نگهداری شده و دسترسی به آن صرفاً برای پزشکان معالج و داروخانه‌های مجری مجاز است. اطلاعات شخصی کاربران تحت هیچ عنوان به نهادهای تجاری متفرقه واگذار نمی‌گردد.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($selectedRole === 'doctor'): ?>
                <!-- 2. Doctor Terms -->
                <div class="space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <span class="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1 rounded-full inline-block mb-2">فصل دوم: ضوابط پزشکان و متخصصین دامپزشک</span>
                        <h2 class="text-xl font-black text-slate-900">منشور اخلاق حرفه‌ای، موازین تله‌مدیسین، نظام انتظامی و تسویه حساب</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-blue-900">
                            <span class="material-symbols-outlined text-sm text-primary">medical_services</span>
                            رعایت موازین انتظامی سازمان نظام دامپزشکی جمهوری اسلامی ایران (قانون مصوب ۱۳۷۶)
                        </div>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            پزشک عضو در آسنا ملزم به رعایت کامل شئون حرفه‌ای، پروانه اشتغال معتبر به درمان، عدم تجویز داروهای خارج از فهرست رسمی دارویی دامپزشکی کشور و حفظ محرمانگی اسرار مراجعین است.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">احراز صلاحیت علمی و پروانه اشتغال به درمان (ماده ۴ قانون نظام دامپزشکی)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    پزشک اقرار می‌نماید دارای دانشنامه دکتری عمومی یا تخصصی دامپزشکی از دانشگاه‌های معتبر، کارت عضویت فعال در سازمان نظام دامپزشکی و پروانه اشتغال به درمان معتبر است. در صورت هرگونه تعلیق یا ابطال پروانه توسط مراجع انتظامی، پزشک مکلف است مراتب را فوراً به آسنا اطلاع داده و فعالیت خود را متوقف نماید.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">حدود قانونی مشاوره تله‌مدیسین و ممنوعیت گواهی سلامت غیابی</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    خدمات آنلاین پزشک منحصراً تریاژ، تفسیر نتایج آزمایشگاهی، راهنمایی تغذیه و مراقبت‌های پس از عمل را در بر می‌گیرد. صدور هرگونه گواهی سلامت رسمی، تاییدیه بهداشتی خروج از کشور، یا میکروچیپ‌گذاری نیازمند معاینه فیزیکی حضوری بوده و صدور آن‌ها در بستر آنلاین تخلف انتظامی قطعی محسوب می‌شود.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۳</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">مسئولیت مدنی، کیفری و جبران خسارت و مصونیت کامل پلتفرم (Platform Indemnity)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    مسئولیت کامل تشخیص بالینی، نسخه دارویی، عوارض ناخواسته تجویزها و هرگونه ادعای خطای پزشکی مستقیماً و منحصراً بر عهده شخص پزشک معالج است. آسنا صرفاً حامل فناوری اطلاعات و بستر ارتباطی است و از هرگونه دادخواست خسارت، دیه یا مسئولیت انتظامی در قبال اشخاص ثالث مبرا (Indemnified) می‌باشد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۴</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">تعهد پاسخگویی زمانی (SLA) و اخلاق حرفه‌ای</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    پزشک متعهد است در نوبت‌های آنلاین متنی، پاسخ اولیه به مراجع را حداکثر ظرف ۴۵ دقیقه کاری ارسال نماید. در صورت عدم امکان حضور در شیفت، پزشک موظف است حداقل ۱۲ ساعت زودتر تقویم نوبت‌دهی خود را مسدود نموده تا مراجعین با نوبت‌های رهاشده مواجه نشوند.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۵</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">نسخه‌نویسی الکترونیک و منع فروش مستقیم داروی فاقد مجوز</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    کلیه نسخه‌ها باید در ماژول نسخه الکترونیک آسنا ثبت گردند تا به داروخانه‌های تاییدشده ارجاع داده شوند. فروش مستقیم دارو توسط پزشک در بستر آنلاین بدون پروانه معتبر داروخانه، یا تجویز داروهای قاچاق و تاییدنشده توسط سازمان دامپزشکی اکیداً ممنوع است.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۶</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">نظام مالی، کارمزد و تسویه حساب هفتگی پایا</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    سهم پزشک از هر ویزیت ۸۵٪ و کارمزد نگهداری زیرساخت پلتفرم ۱۵٪ است. وجوه ویزیت‌های انجام‌شده هر هفته پنج‌شنبه از طریق سامانه پایا بانک مرکزی به شماره شبای ثبت‌شده در پروفایل واریز می‌گردد. تکالیف مالیاتی مربوط به درآمدهای پزشکی طبق قوانین سازمان امور مالیاتی کشور بر عهده پزشک است.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($selectedRole === 'pharmacist'): ?>
                <!-- 3. Pharmacist Terms -->
                <div class="space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <span class="text-xs font-bold text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full inline-block mb-2">فصل سوم: ضوابط داروخانه‌ها و دکترهای داروساز</span>
                        <h2 class="text-xl font-black text-slate-900">موازین عرضه اقلام دارویی، حفظ زنجیره سرد، نظارت مسئول فنی و کد رهگیری TTAC</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-teal-50/70 border border-teal-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-teal-900">
                            <span class="material-symbols-outlined text-sm text-teal-600">ac_unit</span>
                            استاندارد زنجیره سرد (۲ الی ۸ درجه سانتی‌گراد) و ضوابط توزیع خوب دارو (GDP)
                        </div>
                        <p class="text-xs text-teal-800 leading-relaxed">
                            کلیه سرم‌ها، واکسن‌ها، آنتی‌بادی‌ها و داروهای بیولوژیک نیازمند برودت، باید منحصراً در ظروف عایق دوجداره به همراه ژل‌های منجمد استاندارد و نشانگر سلامت دمایی بسته‌بندی گردند.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">پروانه تأسیس معتبر و نظارت پیوسته دکتر داروساز مسئول فنی</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    داروخانه متعهد به بارگذاری پروانه تأسیس معتبر صادره از سازمان دامپزشکی یا سازمان غذا و دارو (وزارت بهداشت) و معرفی مسئول فنی واجد شرایط است. بررسی اعتبار نسخه، تداخلات دارویی و تاریخ انقضای اقلام مستقیماً تحت نظارت مسئول فنی انجام می‌شود.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">الزام استعلام شناسه رهگیری و ردیابی (TTAC / UID) و اصالت اقلام</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    عرضه هرگونه دارو یا مکمل فاقد فاکتور رسمی شرکت پخش سراسری، بدون برچسب اصالت یا با تاریخ انقضای مخدوش اکیداً ممنوع است. در صورت احراز ارسال داروی تقلبی یا قاچاق، پنل داروخانه فوراً مسدود و متخلف به مراجع قضایی و تعزیرات حکومتی معرفی خواهد شد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۳</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">ممنوعیت فروش اینترنتی داروهای تحت کنترل و مخدر</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    ارسال پستی یا پیکی داروهای بیهوشی دامی (نظیر کتامین، زایلازین)، مسکن‌های شبه‌مخدر تحت کنترل، و فرآورده‌های سقط جنین از طریق سامانه آسنا مطلقاً ممنوع است. این اقلام صرفاً با حضور مستقیم و تحویل نسخه کاغذی مهرشده در محل فیزیکی داروخانه مجاز است.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۴</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">تعهد پایداری ذخیره انبار اشتراک دوره‌ای (Autoship)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    برای فعال‌ماندن نشان اشتراک دوره‌ای بر روی مکمل‌ها و اقلام درمانی مزمن، داروخانه متعهد است حداقل ذخیره ۳ ماهه سفارشات جاری را حفظ نماید. در صورت افت موجودی به کمتر از ۵ واحد، محصول به طور خودکار به حالت «خرید تکی» منتقل شده تا کاربران دچار نقص دارویی نگردند.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۵</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">مسئولیت قانونی مستقل داروخانه و سلب مسئولیت پلتفرم</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    داروخانه به عنوان موسسه پزشکی مستقل، شخصاً مسئول پاسخگویی به سازمان غذا و دارو، بازرسان بهداشتی و ادعای خریداران در خصوص کیفیت اقلام دارویی است و پلتفرم آسنا به عنوان بستر نرم‌افزاری هیچ‌گونه مسئولیتی در قبال عوارض بیولوژیک داروها ندارد.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($selectedRole === 'seller'): ?>
                <!-- 4. Seller / Supplier Terms -->
                <div class="space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <span class="text-xs font-bold text-purple-700 bg-purple-50 border border-purple-200 px-3 py-1 rounded-full inline-block mb-2">فصل چهارم: ضوابط فروشندگان و تأمین‌کنندگان مارکت‌پلیس</span>
                        <h2 class="text-xl font-black text-slate-900">مبارزه با قاچاق کالا، تعهدات انبارداری، بارکد پستی ۲۴ رقمی و تسویه پایا</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-purple-900">
                            <span class="material-symbols-outlined text-sm text-purple-600">local_shipping</span>
                            الزام بارکد رهگیری ۲۴ رقمی پست و بسته‌بندی ضدضربه ظرف ۲۴ ساعت کاری
                        </div>
                        <p class="text-xs text-purple-800 leading-relaxed">
                            فروشندگان ملزم هستند کلیه سفارش‌های مارکت‌پلیس را ظرف حداکثر ۲۴ ساعت کاری بسته‌بندی نموده و کد رهگیری پستی ۲۴ رقمی یا بارنامه معتبر را در پنل فروشندگی ثبت نمایند.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">رعایت قانون مبارزه با قاچاق کالا و ارز (مواد ۱۳ و ۱۸)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    فروشنده تضمین می‌نماید کلیه ملزومات، خوراک‌ها و تجهیزات پت‌شاپ دارای فاکتور رسمی، شناسه کالا و برگ سبز گمرکی معتبر بوده و از مبادی رسمی وارد کشور شده‌اند. در صورت کشف کالای قاچاق یا غیرمجاز، پنل مسدود، مطالبات فروشنده به نفع خریداران متضرر بلوکه و موضوع به ستاد مبارزه با قاچاق کالا ارجاع می‌گردد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">تطابق موجودی انبار فیزیکی با پنل و نرخ‌گذاری مصوب</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    فروشنده موظف به بروزرسانی لحظه‌ای موجودی است. لغو سفارش خریدار به دلیل کسری موجودی موجب کسر ۵٪ جریمه جبران خسارت از بستانکاری فروشنده خواهد شد. نرخ‌گذاری اقلام نباید تحت هیچ عنوان از قیمت مصرف‌کننده مصوب بالاتر باشد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۳</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">دوره بازرسی ۷ روزه و تسویه حساب هفتگی پایا</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    وجوه حاصل از فروش پس از تایید وصول پستی توسط مشتری و اتمام مهلت تست قانونی ۷ روزه، آزاد شده و پنج‌شنبه هر هفته به شماره شبای رسمی فروشنده واریز می‌گردد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۴</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">ضوابط نشان طلایی اشتراک دوره‌ای (Autoship Eligibility)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    حفظ موجودی حداقل ۵ عدد برای حضور کالا در پیشنهادهای سبد اتوشیپ الزامی است. در صورتی که موجودی بین ۱ تا ۴ عدد باشد، محصول بدون هیچ جریمه‌ای در حالت «خرید تکی» باقی می‌ماند تا مشترکین دچار توقف تامین نشوند.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۵</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">پوشش خسارت و مصونیت پلتفرم (Seller Indemnity)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    فروشنده شخصاً پاسخگوی هرگونه خسارت ناشی از عیب کالا، آسیب در حمل نامناسب یا مغایرت رنگ و مدل است و آسنا از هرگونه ادعای مالی و حقوقی مصرف‌کنندگان مبرا می‌باشد.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($selectedRole === 'organization'): ?>
                <!-- 5. Organization Terms -->
                <div class="space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <span class="text-xs font-bold text-blue-800 bg-blue-50 border border-blue-200 px-3 py-1 rounded-full inline-block mb-2">فصل پنجم: ضوابط مراکز درمانی، بیمارستان‌ها و کلینیک‌ها</span>
                        <h2 class="text-xl font-black text-slate-900">پروانه تأسیس رسمی، نظارت سازمانی، مدیریت کادر درمان و سرویس اورژانس ۲۴/۷</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-blue-900">
                            <span class="material-symbols-outlined text-sm text-primary">domain</span>
                            نظارت حقوقی، پروانه بهره‌برداری بهداشتی و مدیریت چندکاربره کادر درمان
                        </div>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            مدیر مسئول مرکز درمانی مسئولیت احراز صلاحیت حرفه‌ای، مدارک نظام دامپزشکی و نظارت بر کلیه پزشکان و پرسنل منتسب در پنل آسنا را به صورت تضامنی بر عهده دارد.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">اصالت پروانه تأسیس و اختیارات قانونی مدیر مرکز (ماده ۳ و ۵ قانون نظام دامپزشکی)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    سازمان متعهد به بارگذاری آخرین آگهی روزنامه رسمی، پروانه معتبر کلینیک یا بیمارستان صادره از سازمان دامپزشکی و معرفی نماینده مجاز امضا است. کلیه تعهدات ثبت‌شده در پنل سازمانی آسنا به عنوان تعهد رسمی شخصیت حقوقی مرکز تلقی می‌گردد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">الزامات مراکز دارای برچسب شبانه‌روزی (24/7 Emergency)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    مراکزی که با نشان ۲۴ ساعته و اورژانس در دایرکتوری آسنا معرفی می‌شوند، موظف به حفظ کشیک درمانی فعال و پذیرش فوری کیس‌های بحرانی ارجاع‌شده در تمامی ساعات شبانه‌روز می‌باشند. گزارش تخلف یا عدم پذیرش کیس اورژانسی منجر به تعلیق نشان اورژانس مرکز خواهد شد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۳</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">انضباط پذیرش مستقیم نوبت‌ها و اولویت‌دهی به مراجعین آنلاین</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    مرکز درمانی متعهد است نوبت‌های ثبت‌شده از طریق سامانه آسنا را بدون معطلی و با اولویت در سامانه پذیرش داخلی اعمال نماید. هرگونه لغو نوبت توسط مرکز باید حداقل ۴ ساعت زودتر اطلاع‌رسانی شده و پزشک جانشین هماهنگ گردد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۴</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">تسویه حساب شرکتی و فاکتور رسمی</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    درآمدهای حاصل از نوبت‌دهی و خدمات مرکز، پس از کسر کارمزد مصوب پلتفرم، به شماره شبای حساب حقوقی مرکز واریز می‌گردد. صدور فاکتور رسمی خدمات درمانی حضوری با مرکز درمانی است.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($selectedRole === 'general'): ?>
                <!-- 6. Universal Governance & Safe Harbor -->
                <div class="space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <span class="text-xs font-bold text-slate-700 bg-slate-100 border border-slate-300 px-3 py-1 rounded-full inline-block mb-2">فصل ششم: حاکمیت پلتفرم و شرایط عمومی</span>
                        <h2 class="text-xl font-black text-slate-900">سلب مسئولیت واسط نرم‌افزاری (Safe Harbor)، داوری، فورس ماژور و اسقاط خیارات</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-300/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-amber-950">
                            <span class="material-symbols-outlined text-sm text-accent">shield</span>
                            جایگاه حقوقی آسنا به عنوان واسط فناوری اطلاعات (ماده ۶۲ قانون تجارت الکترونیکی)
                        </div>
                        <p class="text-xs text-amber-900 leading-relaxed">
                            پلتفرم آسنا صرفاً بستر نرم‌افزاری و حامل داده‌پیام است و خود به عنوان ارائه‌دهنده کالا، تولیدکننده دارو، یا پزشک معالج عمل نمی‌نماید. کلیه مسئولیت‌های مدنی، کیفری و مالی کالاها و خدمات مستقیماً متوجه ارائه‌دهنده مربوطه است.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">مصونیت پلتفرم از خطاهای درمانی و عیوب کیفی کالاها (Platform Safe Harbor)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    کاربران، پزشکان، داروخانه‌ها و فروشندگان صراحتاً می‌پذیرند که آسنا در تولید محصولات، نگهداری انبارها، تشخیص‌های پزشکی و نسخه‌پیچی داروها هیچ‌گونه دخالتی ندارد. هرگونه ادعای جبران خسارت جانی، مالی یا تعزیراتی صرفاً علیه شخص یا نهاد مسبب قابل طرح بوده و آسنا از این دعاوی مبرا است.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">حوادث قهریه و فورس ماژور (Force Majeure)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    در شرایط اضطراری نظیر قطع سراسری شبکه اینترنت، اختلالات شبکه پرداخت شاپرک، حوادث غیرمترقبه طبیعی (سیل، زلزله)، تعطیلات اضطراری حاکمیتی و جنگ، پلتفرم آسنا در خصوص تاخیر یا عدم اجرای موقت تعهدات هیچ مسئولیتی بر عهده نخواهد داشت.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۳</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">شرط داوری مرضی‌الطرفین و حل اختلاف</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    کلیه مناقشات ناشی از سفارش‌ها یا خدمات ابتدا به کمیته حل اختلاف داخلی آسنا به عنوان داور مرضی‌الطرفین صلح و سازش ارجاع می‌گردد. در صورت عدم حصول توافق ظرف مدت ۳۰ روز کاری، مراجع قضایی و دادگاه‌های عمومی و انقلاب شهر تهران صالح به رسیدگی خواهند بود.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۴</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">اسقاط کافه خیارات قانونی</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    کلیه کاربران و همکاران تجاری با پذیرش این سند و امضای قرارداد الکترونیک، کافه خیارات قانونی من‌جمله خیار غبن ولو فاحش یا افحش را از خود سلب و ساقط می‌نمایند، مگر خیار تدلیس فروشنده که مشمول قوانین عام حاکم بر معاملات است.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۵</span>
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900">ارزش اثباتی اسناد الکترونیکی و امضای دیجیتال (مواد ۶، ۱۰ و ۱۲ قانون تجارت الکترونیک)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    داده‌پیام‌ها، گزارش‌های سیستمی لاگ، پیامک‌های ارسالی سامانه و امضاهای دیجیتال رمزنگاری‌شده با الگوریتم HMAC-SHA256 در دادگاه‌ها و مراجع قضایی و انتظامی به عنوان سند رسمی معتبر و ادله قطعی اثبات دعوی مورد استناد قرار می‌گیرند.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Cross-Role Master Legal Shield (Shown across all tabs) -->
            <?php if ($selectedRole !== 'general'): ?>
                <div class="pt-6 border-t border-slate-100">
                    <div class="bg-slate-50/80 rounded-2xl p-4 border border-slate-200/80 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-800">
                            <span class="material-symbols-outlined text-sm text-primary">security</span>
                            <span>مطالعه شرایط عمومی حاکمیت پلتفرم، سلب مسئولیت واسط و اسقاط خیارات</span>
                        </div>
                        <a href="terms.php?role=general" class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1">
                            <span>مشاهده متن کامل فصل ششم</span>
                            <span class="material-symbols-outlined text-xs">arrow_back</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <hr class="border-slate-100">

            <!-- Universal Regulatory Audit Footer -->
            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200/70 space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-800">
                    <span class="material-symbols-outlined text-sm text-primary">gavel</span>
                    واحد ممیزی، بازرسی حقوقی و رسیدگی فوری به شکایات آسنا
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    این سند بر مبنای قوانین و مقررات حاکم بر بستر تجارت الکترونیک و بهداشت کشور تدوین شده است. هرگونه تغییر در قوانین جاری از طریق اعلان سیستمی و الزام به امضای مجدد نسخه جدید قرارداد به اطلاع کاربران خواهد رسید. در صورت وجود هرگونه ابهام یا ثبت گزارش تخلف صنفی، واحد حقوقی پلتفرم پاسخگوی شماست.
                </p>
                <div class="flex flex-wrap items-center gap-4 pt-2 text-xs text-slate-500 font-medium">
                    <span>خط مستقیم واحد حقوقی: ۰۲۱-۹۱۰۰۰۰۰۰</span>
                    <span>•</span>
                    <span>سامانه رسیدگی به شکایات: complaint@asena.company</span>
                    <span>•</span>
                    <span>کد ثبت حقوقی سند: ASENA-LAW-2026-V2</span>
                </div>
            </div>

        </div>

        <!-- Back to Home & Print Button -->
        <div class="text-center mt-8 flex items-center justify-center gap-4">
            <a href="index.php" class="inline-flex items-center gap-2 bg-primary text-white text-xs font-bold px-6 py-3 rounded-2xl shadow-lg shadow-primary/20 hover:bg-primary-light transition-all">
                <span class="material-symbols-outlined text-base">arrow_forward</span>
                <span>تایید و بازگشت به صفحه اصلی</span>
            </a>

            <button onclick="window.print()" class="inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-200 text-xs font-bold px-5 py-3 rounded-2xl shadow-sm hover:bg-slate-100 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-base">print</span>
                <span>چاپ نسخه رسمی ممهور</span>
            </button>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
