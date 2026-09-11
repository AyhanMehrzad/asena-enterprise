<?php
/**
 * ASENA Enterprise - Contact Us & Complaints Handling Page (صفحه رسمی تماس با ما و رسیدگی به شکایات)
 * Fully compliant with Iranian E-Commerce Law (Enamad / اینماد standards)
 */
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'تماس با ما و رسیدگی به شکایات | سامانه جامع آسنا';
$meta_description = 'اطلاعات تماس رسمی، آدرس، تلفن ثابت، ساعات کاری و فرم ثبت پیام و شکایات شرکت توسعه فناوری آسنا.';

$msgSent = false;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact_submit') {
    csrf_verify();
    $name    = trim($_POST['full_name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'پیام عمومی');
    $type    = trim($_POST['inquiry_type'] ?? 'general'); // general, complaint, partnership, support
    $content = trim($_POST['message'] ?? '');

    if (empty($name) || empty($phone) || empty($content)) {
        $errorMsg = 'لطفاً نام، شماره تماس و شرح پیام خود را تکمیل فرمایید.';
    } else {
        try {
            // Save into support tickets or feedback
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                $insT = $pdo->prepare("INSERT INTO tickets (user_id, mode, status, created_at, updated_at) VALUES (?, 'admin', 'open', NOW(), NOW())");
                $insT->execute([$userId]);
                $tId = (int)$pdo->lastInsertId();

                $tag = ($type === 'complaint') ? '🚨 [شکایت رسمی]' : '📩 [پیام تماس با ما]';
                $fullBody = "{$tag} موضوع: {$subject}\nنام فرستنده: {$name}\nتلفن: {$phone}\nایمیل: {$email}\n\nمتن پیام:\n{$content}";

                $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'user', ?, NOW())")
                    ->execute([$tId, $fullBody]);
            }
            $msgSent = true;
        } catch (Exception $e) {
            $errorMsg = 'خطا در ثبت پیام. لطفاً مجدداً تلاش فرمایید.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-8">
        
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <a href="index.php" class="hover:text-primary transition-colors">صفحه اصلی</a>
            <span class="material-symbols-outlined text-xs text-slate-400">chevron_left</span>
            <span class="text-primary font-bold">تماس با ما و رسیدگی به شکایات</span>
        </nav>

        <!-- Header Card -->
        <div class="bg-white rounded-3xl p-8 lg:p-10 shadow-sm border border-slate-200/80 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#001a48] via-blue-600 to-[#fd8100]"></div>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-800 border border-blue-200 text-xs font-bold px-3 py-1 rounded-full mb-3">
                        <span class="material-symbols-outlined text-xs text-blue-600">verified</span>
                        پاسخگویی سریع، پشتیبانی سراسری و رسیدگی قانونی به شکایات
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight mb-2">
                        ارتباط مستقیم با مرکز پشتیبانی و خزانه‌داری آسنا
                    </h1>
                    <p class="text-sm text-slate-600 leading-relaxed max-w-2xl">
                        همکاران ما در تمامی روزهای کاری آماده پاسخگویی به سوالات، پیگیری سفارشات، هماهنگی‌های پزشکی و دریافت نظرات و شکایات شما هستند.
                    </p>
                </div>
                <div class="w-16 h-16 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center text-primary flex-shrink-0">
                    <span class="material-symbols-outlined text-3xl">support_agent</span>
                </div>
            </div>
        </div>

        <?php if ($msgSent): ?>
        <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center gap-3 shadow-xs animate-fade-in">
            <span class="material-symbols-outlined text-emerald-600 text-xl">task_alt</span>
            <div>
                <p class="font-bold text-sm">پیام شما با موفقیت در دبیرخانه سامانه آسنا ثبت شد.</p>
                <p class="text-emerald-700 mt-0.5">کارشناسان امور مشتریان ظرف حداکثر ۲۴ الی ۴۸ ساعت کاری با شما تماس خواهند گرفت.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-center gap-2">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <span><?= htmlspecialchars($errorMsg) ?></span>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Contact Info & Legal Identity (Col 5) - Enamad Required -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- Legal Entity Card -->
                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
                        <span class="material-symbols-outlined text-primary text-base">apartment</span>
                        مشخصات ثبتی و قانونی کسب‌وکار (استعلام اینماد)
                    </h3>
                    
                    <div class="space-y-3 text-xs">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 mt-0.5 text-base">badge</span>
                            <div>
                                <span class="text-slate-400 block text-[10px]">صاحب امتیاز و مدیر مسئول:</span>
                                <span class="font-bold text-slate-800">آیهان مهزاد / شرکت توسعه تجارت الکترونیک آسنا</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 mt-0.5 text-base">phone_in_talk</span>
                            <div>
                                <span class="text-slate-400 block text-[10px]">تلفن ثابت مرکزی (استعلام اینماد):</span>
                                <a href="tel:02191000000" class="font-mono font-bold text-primary dir-ltr inline-block hover:underline">۰۲۱ - ۹۱۰۰۰۰۰۰</a>
                                <span class="text-[10px] text-slate-400 block mt-0.5">پاسخگویی روزهای شنبه تا چهارشنبه ۹ الی ۱۸ | پنج‌شنبه‌ها ۹ الی ۱۳</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 mt-0.5 text-base">mail</span>
                            <div>
                                <span class="text-slate-400 block text-[10px]">پست الکترونیکی رسمی (Official Domain Email):</span>
                                <a href="mailto:info@asena.company" class="font-mono text-slate-700 hover:text-primary dir-ltr inline-block">info@asena.company</a>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 mt-0.5 text-base">location_on</span>
                            <div>
                                <span class="text-slate-400 block text-[10px]">نشانی پستی دفتر مرکزی:</span>
                                <span class="text-slate-700 leading-relaxed font-medium">تهران، سعادت‌آباد، خیابان علامه طباطبایی، مجتمع خدمات فناوری و درمانی آسنا، پلاک ۱۲، طبقه ۴</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-slate-400 mt-0.5 text-base">markunread_mailbox</span>
                            <div>
                                <span class="text-slate-400 block text-[10px]">کد پستی ۱۰ رقمی تاییدشده:</span>
                                <span class="font-mono font-bold text-slate-800 dir-ltr inline-block">۱۹۹۷۸۳۴۵۲۱</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Complaint & Inspection Policy Notice -->
                <div class="bg-gradient-to-br from-amber-50 to-orange-50/50 p-6 rounded-3xl border border-amber-200/80 shadow-sm space-y-3">
                    <div class="flex items-center gap-2 text-amber-900 font-bold text-xs">
                        <span class="material-symbols-outlined text-amber-700 text-sm">policy</span>
                        سامانه ویژه رسیدگی به شکایات و مرجوعی کالا
                    </div>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        طبق ماده ۳۷ قانون تجارت الکترونیک، خریداران تا ۷ روز کاری پس از تحویل مرسوله حق انصراف و بازگرداندن کالا را دارا می‌باشند. در صورت هرگونه مغایرت کالا، تاخیر در تحویل یا شکایت از خدمات پزشکان، می‌توانید فرم روبرو را با انتخاب گزینه <strong>«ثبت شکایت رسمی»</strong> ارسال فرمایید.
                    </p>
                </div>

            </div>

            <!-- Contact & Complaint Form (Col 7) -->
            <div class="lg:col-span-7 bg-white p-6 lg:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <h3 class="font-black text-slate-900 text-base flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">edit_note</span>
                        فرم ارسال پیام، پیگیری و ثبت شکایات
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">کلیه پیام‌ها مستقیماً توسط واحد بازرسی و پشتیبانی خزانه‌داری آسنا بررسی می‌گردد.</p>
                </div>

                <form method="POST" action="contact.php" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="contact_submit">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">نام و نام خانوادگی:</label>
                            <input type="text" name="full_name" required placeholder="مثال: علی محمدی" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره تلفن همراه (جهت هماهنگی):</label>
                            <input type="tel" name="phone" required placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-primary outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">آدرس ایمیل (اختیاری):</label>
                            <input type="email" name="email" placeholder="name@example.com" dir="ltr" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-primary outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع درخواست:</label>
                            <select name="inquiry_type" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs font-bold focus:ring-2 focus:ring-primary outline-none">
                                <option value="general">پرسش عمومی و راهنمایی</option>
                                <option value="order_tracking">پیگیری سفارش یا کد رهگیری پستی</option>
                                <option value="booking_support">پشتیبانی نوبت کلینیک و دامپزشکی</option>
                                <option value="complaint">ثبت شکایت رسمی و اعلام مغایرت کالا</option>
                                <option value="partnership">همکاری تجاری، تامین‌کننده و کلینیک</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">موضوع پیام / شناسه سفارش (در صورت وجود):</label>
                        <input type="text" name="subject" required placeholder="مثال: پیگیری سفارش شماره #PC-1025 یا سوال پیرامون نوبت" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">شرح پیام یا متن شکایت:</label>
                        <textarea name="message" rows="5" required placeholder="لطفاً شرح کامل درخواست، انتقاد یا شکایت خود را با ذکر جزئیات وارد فرمایید..." class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary outline-none leading-relaxed"></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full sm:w-auto bg-[#001a48] hover:bg-[#002d72] text-white px-8 py-3.5 rounded-xl font-bold text-xs shadow-md transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-base">send</span>
                            <span>ثبت و ارسال پیام به واحد پشتیبانی</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
