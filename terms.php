<?php
require_once 'includes/db.php';
require_once 'includes/ContractService.php';

// Supported roles
$roleKeys = ['consumer', 'doctor', 'pharmacist', 'seller', 'organization'];
$roleTitles = [
    'consumer'     => ['title' => 'سرپرستان پت و مشتریان', 'icon' => 'pets', 'badge' => 'حقوق مصرف‌کننده'],
    'doctor'       => ['title' => 'پزشکان و دامپزشکان', 'icon' => 'stethoscope', 'badge' => 'نظام دامپزشکی'],
    'pharmacist'   => ['title' => 'داروخانه‌ها و داروسازان', 'icon' => 'medication', 'badge' => 'سازمان غذا و دارو'],
    'seller'       => ['title' => 'فروشندگان و تأمین‌کنندگان', 'icon' => 'storefront', 'badge' => 'مارکت‌پلیس و انبار'],
    'organization' => ['title' => 'مراکز درمانی و کلینیک‌ها', 'icon' => 'local_hospital', 'badge' => 'پروانه بهداشتی']
];

$selectedRole = $_GET['role'] ?? 'consumer';
if (!in_array($selectedRole, $roleKeys, true)) {
    $selectedRole = 'consumer';
}

$page_title = 'قوانین و شرایط حقوقی خدمات بر اساس نقش‌ها | سامانه جامع آسنا';
$meta_description = 'مقررات جامع و تفکیک‌شده برای سرپرستان پت، دامپزشکان، داروخانه‌ها، فروشندگان مارکت‌پلیس و مراکز درمانی در سامانه آسنا.';

require_once 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 font-medium">
            <a href="index.php" class="hover:text-primary transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs text-slate-400">chevron_left</span>
            <span class="text-primary font-bold">قوانین و ضوابط حقوقی کاربران و همکاران</span>
        </nav>

        <!-- Header Card -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-200/80 mb-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-accent via-primary to-emerald-500"></div>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 bg-primary/10 text-primary border border-primary/20 text-xs font-bold px-3 py-1 rounded-full mb-3">
                        <span class="material-symbols-outlined text-xs">gavel</span>
                        منشور حقوقی و ضوابط حاکمیت چندنقشی آسنا اینترپرایز
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight mb-2">
                        قوانین، مقررات و الزامات قانونی استفاده از سامانه
                    </h1>
                    <p class="text-sm text-slate-600 leading-relaxed max-w-2xl">
                        به جهت شفافیت صددرصدی و حفظ منافع دوطرفه پلتفرم و جامعه کاربران، ضوابط قانونی برای تمامی نقش‌های عضو در پلتفرم آسنا به صورت تخصصی و مستند بر قوانین تجارت الکترونیک و سازمان‌های بهداشتی تدوین شده است.
                    </p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-accent/10 flex items-center justify-center text-accent flex-shrink-0 border border-accent/20">
                    <span class="material-symbols-outlined text-3xl">policy</span>
                </div>
            </div>
        </div>

        <!-- Role Navigation Tabs -->
        <div class="bg-white rounded-2xl p-2 shadow-sm border border-slate-200/80 mb-8">
            <div class="flex flex-wrap items-center gap-2 justify-center sm:justify-start">
                <?php foreach ($roleTitles as $rk => $rMeta): ?>
                    <?php $isActive = ($selectedRole === $rk); ?>
                    <a href="terms.php?role=<?= $rk ?>" 
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all <?= $isActive ? 'bg-primary text-white shadow-md shadow-primary/20' : 'bg-slate-100/70 text-slate-600 hover:bg-slate-200/70' ?>">
                        <span class="material-symbols-outlined text-sm"><?= $rMeta['icon'] ?></span>
                        <span><?= $rMeta['title'] ?></span>
                        <span class="text-[10px] opacity-75 px-1.5 py-0.5 rounded-md <?= $isActive ? 'bg-white/20 text-white' : 'bg-white text-slate-500' ?>"><?= $rMeta['badge'] ?></span>
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
                        <h2 class="text-xl font-bold text-slate-900">حقوق و تکالیف خریداران و مراجعین درمانی آسنا</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-amber-900">
                            <span class="material-symbols-outlined text-sm text-accent">verified</span>
                            مهلت تست و امانت‌داری مالی ۷ روزه (ماده ۳۷ قانون تجارت الکترونیک)
                        </div>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            کلیه مبالغ پرداخت‌شده برای خرید محصولات و ملزومات پت‌شاپ، تا ۷ روز پس از اعلام تحویل بسته توسط شرکت ملی پست، در حساب واسط امانی آسنا نگهداری می‌شود. تنها در صورت عدم اعلام نقص یا مغایرت از سوی خریدار، وجه با فروشنده تسویه می‌گردد.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">ضوابط لغو و استرداد نوبت‌های درمانی و مشاوره</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    لغو نوبت‌های رزروشده کلینیک یا ویزیت آنلاین تا ۴ ساعت پیش از موعد مقرر، بدون کسر کارمزد با استرداد ۱۰۰٪ به کیف‌پول انجام می‌شود. لغو کمتر از ۴ ساعت، شامل کسر ۱۰٪ بابت جبران خسارت نوبت خالی پزشک خواهد بود.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">خدمات سفارش دوره‌ای و تحویل خودکار (Autoship)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    با عضویت در برنامه‌های تحویل دوره‌ای، سفارش‌های شما با ۱۵٪ تخفیف ثابت و ارسال رایگان در مواعد انتخابی ارسال می‌شوند. آسنا متعهد است صرفاً محصولاتی را در بخش اتوشیپ پیشنهاد نماید که تأمین‌کننده حداقل موجودی برای ۳ ماه آینده را تضمین نموده باشد. کاربر تا ۴۸ ساعت قبل از صدور هر فاکتور مجاز به تغییر اقلام یا لغو بدون جریمه است.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۳</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">کالاهای غیرقابل عودت به دلایل بهداشتی</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    طبق پروتکل‌های وزارت بهداشت و سازمان نظام دامپزشکی، مکمل‌های دارویی پلمپ‌بازشده، داروهای بیولوژیک نیازمند زنجیره سرد و غذاهای کنسروی باز شده، پس از خروج از پلمپ اولیه به دلایل ایمنی زیستی قابل مرجوعی نمی‌باشند مگر اینکه دارای فساد ظاهری یا انقضای منقضی‌شده در زمان تحویل باشند.
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
                        <h2 class="text-xl font-bold text-slate-900">منشور اخلاق حرفه‌ای، تله‌مدیسین و تسویه حساب ویزیت</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-blue-900">
                            <span class="material-symbols-outlined text-sm text-primary">medical_services</span>
                            رعایت موازین انتظامی سازمان نظام دامپزشکی کشور
                        </div>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            پزشک عضو در آسنا متعهد به رعایت کامل شئون حرفه‌ای، عدم تجویز داروهای خارج از فهرست رسمی دامپزشکی و حفظ محرمانگی پرونده‌های بالینی مراجعین می‌باشد.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">مرزهای تله‌هلث و ارجاع اورژانسی</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    مشاوره‌های از راه دور صرفاً جهت تریاژ، راهنمایی بالینی اولیه و پیگیری درمان‌های قبلی است. در صورت مشاهده هرگونه نشانه خطرزا برای حیات حیوان، پزشک متعهد است مراجعه‌کننده را به نزدیک‌ترین مرکز درمانی حضوری شبانه‌روزی ارجاع دهد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">حقوق مالی و تسویه پایا</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    سهم پزشک از هر ویزیت آنلاین ۸۵٪ کل مبلغ دریافتی از مراجع بوده و کارمزد پلتفرم ۱۵٪ است. وجوه نوبت‌های انجام‌شده هر هفته پنج‌شنبه از طریق حواله پایا به شماره شبای بانکی ثبت‌شده در پروفایل واریز می‌گردد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۳</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">نسخه‌نویسی الکترونیک</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    نسخه‌های ثبت‌شده در ماژول پرونده الکترونیک مستقیماً به داروخانه‌های تاییدشده شبکه متصل می‌گردد تا از تداخلات دارویی و مصرف خودسرانه آنتی‌بیوتیک‌ها ممانعت به عمل آید.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($selectedRole === 'pharmacist'): ?>
                <!-- 3. Pharmacist Terms -->
                <div class="space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <span class="text-xs font-bold text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full inline-block mb-2">فصل سوم: ضوابط داروخانه‌ها و داروسازان</span>
                        <h2 class="text-xl font-bold text-slate-900">موازین عرضه اقلام دارویی، حفظ زنجیره سرد و کنترل نسخه</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-teal-50/70 border border-teal-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-teal-900">
                            <span class="material-symbols-outlined text-sm text-teal-600">ac_unit</span>
                            استاندارد زنجیره سرد ۲ تا ۸ درجه سانتی‌گراد
                        </div>
                        <p class="text-xs text-teal-800 leading-relaxed">
                            کلیه سرم‌ها، واکسن‌ها و آنتی‌بادی‌های نیازمند برودت، باید با باکس‌های عایق و ژل‌های خنک‌کننده دارای دماسنج تاییدشده بسته‌بندی شوند.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">مسئولیت داروساز و مسئول فنی داروخانه</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    تأیید اصالت کالا، بررسی تاریخ انقضا و مطابقت اقلام تجویزی نسخه با قوانین سازمان غذا و دارو، مستقیماً تحت نظارت دکتر داروساز مسئول فنی داروخانه است.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">تعهد پایداری موجودی اشتراک دارویی (Autoship)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    داروخانه برای نمایش نشان اشتراک بر روی مکمل‌های حیاتی و درمانی، متعهد به تأمین سهمیه انبار حداقل ۳ ماهه است. در صورت کمبود موجودی، محصول به طور خودکار به حالت «فروش تکی» سوئیچ می‌شود تا حقوق مشترکین تضییع نگردد.
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
                        <h2 class="text-xl font-bold text-slate-900">تعهدات موجودی انبار، الصاق بارکد پستی و تسویه حساب هفتگی</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-purple-900">
                            <span class="material-symbols-outlined text-sm text-purple-600">local_shipping</span>
                            الزام بارکد رهگیری ۲۴ رقمی پست و لجستیک سریع
                        </div>
                        <p class="text-xs text-purple-800 leading-relaxed">
                            فروشندگان موظفند کلیه سفارش‌ها را ظرف حداکثر ۲۴ ساعت کاری بسته‌بندی نموده و کد رهگیری پستی ۲۴ رقمی را در پنل ثبت فرمایند.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">تطابق دقیق موجودی فیزیکی با پنل</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    فروشنده ملزم به کنترل روزانه انبار است. در صورت سفارش مشتری و لغو توسط فروشنده به دلیل کسری موجودی، جریمه جبران نارضایتی مشتری طبق جدول تخلفات اعمال خواهد شد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">تسویه حساب هفتگی پایا و دوره بازرسی ۷ روزه</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    وجوه حاصل از فروش پس از تأیید تحویل توسط سامانه رهگیری پستی و سپری شدن ۷ روز کاری مهلت تست خریدار، به کیف‌پول قابل برداشت منتقل شده و پنج‌شنبه هر هفته به شماره شبا تسویه می‌گردد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۳</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">ضوابط احراز صلاحیت نشان طلایی اتوشیپ</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    برای فعال ماندن نشان اتوشیپ کالاها و حضور در سبدهای پیشنهادی، فروشنده باید موجودی حداقل ۵ عدد را حفظ نماید. در صورتی که موجودی بین ۱ تا ۴ عدد باشد، کالا کماکان برای فروش تک‌باره در دسترس است و هیچ جریمه‌ای متوجه فروشنده نخواهد بود.
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
                        <h2 class="text-xl font-bold text-slate-900">پروانه تأسیس رسمی، مدیریت کادر درمان و سرویس ۲۴/۷ اورژانس</h2>
                    </div>

                    <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200/80 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-xs text-blue-900">
                            <span class="material-symbols-outlined text-sm text-primary">domain</span>
                            نظارت سازمانی و مدیریت چندکاربره
                        </div>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            مدیر مسئول مرکز درمانی مسئولیت احراز صلاحیت پزشکان و پرسنل منتسب در سامانه آسنا را بر عهده دارد.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۱</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">الزامات مراکز دارای برچسب شبانه‌روزی (24/7)</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    مراکزی که با عنوان شبانه‌روزی معرفی می‌گردند موظفند کشیک فعال و پاسخگویی تلفنی اورژانس را به صورت مستمر حفظ نمایند. در صورت گزارش تخلف، نشان اورژانس مرکز تعلیق خواهد شد.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">۲</span>
                            <div>
                                <h3 class="font-bold text-slate-900 mb-1">مدیریت نوبت‌های حضوری و ارتباط با داروخانه داخلی</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    کلینیک‌ها می‌توانند انبار دارویی و پت‌شاپ داخلی خود را به صورت اختصاصی برای مراجعین فعال نموده و نوبت‌های پذیرش مستقیم را بدون تداخل مدیریت نمایند.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <hr class="border-slate-100">

            <!-- Universal Regulatory Footer Compliance -->
            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200/70 space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-800">
                    <span class="material-symbols-outlined text-sm text-primary">gavel</span>
                    مراجع نظارتی و صلاحیت داوری حل اختلاف
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    این مجموعه قوانین طبق قوانین جمهوری اسلامی ایران از جمله قانون تجارت الکترونیک مصوب ۱۳۸۲، آیین‌نامه ساماندهی پایگاه‌های اینترنتی و دستورالعمل‌های سازمان نظام دامپزشکی و سازمان غذا و دارو تنظیم شده است. در صورت بروز هرگونه اختلاف، واحد ممیزی و داوری حقوقی آسنا به عنوان مرجع صلح و سازش اولیه اقدام خواهد نمود.
                </p>
                <div class="flex flex-wrap items-center gap-4 pt-2 text-xs text-slate-500 font-medium">
                    <span>شماره تماس واحد حقوقی: ۰۲۱-۹۱۰۰۰۰۰۰</span>
                    <span>•</span>
                    <span>ایمیل امور حقوقی: legal@asena.company</span>
                </div>
            </div>

        </div>

        <!-- Back to Home Button -->
        <div class="text-center mt-8 flex items-center justify-center gap-4">
            <a href="index.php" class="inline-flex items-center gap-2 bg-primary text-white text-xs font-bold px-6 py-3 rounded-2xl shadow-lg shadow-primary/20 hover:bg-primary-light transition-all">
                <span class="material-symbols-outlined text-base">arrow_forward</span>
                <span>تایید و بازگشت به صفحه اصلی</span>
            </a>

            <button onclick="window.print()" class="inline-flex items-center gap-2 bg-white text-slate-700 border border-slate-200 text-xs font-bold px-5 py-3 rounded-2xl shadow-sm hover:bg-slate-100 transition-all cursor-pointer">
                <span class="material-symbols-outlined text-base">print</span>
                <span>چاپ نسخه رسمی</span>
            </button>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
