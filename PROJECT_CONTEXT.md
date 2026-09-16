# شناسنامه و کانتکس جامع سیستم سازمانی آسنا (ASENA Enterprise)
> **نسخه مستند:** ۱.۰.۰  
> **آخرین به‌روزرسانی:** سپتامبر ۲۰۲۶  
> **وضعیت مخزن:** کامیت و سینک‌شده با `origin/main` در گیت‌هاب  
> **هدف این سند:** ایجاد یک مرجع پایدار، متمرکز و مختصر از تمام فایل‌ها و معماری پروژه تا در شروع هر گفتگو یا ریست کانتکس، هوش مصنوعی سریعاً بدون نیاز به خواندن مجدد ده‌ها فایل در جریان وضعیت قرار گیرد.

---

## ۱. معماری و استک فنی (System Architecture)

- **هسته بک‌اند:** PHP 8.2+ با شیوه ساختاری ماژولار (Modular Monolith)، پایگاه داده MySQL/MariaDB با لایه انتزاع PDO، تراکنش‌های اتمیک (`beginTransaction`, `commit`, `rollBack`) و قفل‌های سطری انبار (`FOR UPDATE`).
- **فرانت‌اند و ظاهر:** Vanilla CSS سفارشی در کنار Tailwind CSS v3، فونت‌های محلی `Vazirmatn` و `Geist`، آیکون‌های محلی Material Symbols، طراحی کاملاً راست‌چین (`dir="rtl" lang="fa"`).
- **رنگ‌های رسمی برند آسنا:** سرمه‌ای تیره سازمانی (`#001a48`) و نارنجی آذرین (`#fd8100` / `#ea580c`).
- **معماری لایسنس و فیچرفلگ‌ها:** کنترل دسترسی قابلیت‌ها از طریق `Feature::has('feature_key')` و فایل `config/tiers.php` با ۵ سطح لایسنس: `basic`، `standard`، `premium`، `pharmacy`، `enterprise`.
- **ابزار خط فرمان:** ابزار اختصاصی `bin/asena` برای بررسی وضعیت ماژول‌ها و ساخت زیپ پکیج نصبی مشتریان (`php bin/asena package --tier=...`).
- **درگاه پرداخت بانکی:** درگاه دوگانه `ZarinPalGateway` در [`includes/gateway.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/gateway.php) با پشتیبانی همزمان از درگاه واقعی زرین‌پال و شبیه‌ساز پرداخت (`mock_payment_gateway.php`).
- **سرویس پیامک:** سامانه ملی‌پیامک (Melipayamak REST & SOAP) با خطوط خدماتی و پترن‌های OTP و سفارشات در [`includes/SmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/SmsService.php).
- **وب‌اپلیکیشن پیش‌رونده (PWA):** پشتیبانی کامل با `sw.js`، فایل `site.webmanifest` و صفحه آفلاین `offline.html`.

---

## ۲. گزارش و دسته‌بندی فایل‌های پروژه (File Registry)

### ۲.۱. فایل‌های پیکربندی و راه‌انداز هسته
- [`config.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/config.php): تشخیص هوشمند لوکال‌هاست (XAMPP) و هاست اشتراکی cPanel با رعایت محدودیت‌های `open_basedir`.
- [`config/tiers.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/config/tiers.php): ماتریس تعریف ماژول‌ها و سطوح ۵ گانه لایسنس.
- [`includes/Env.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/Env.php): لودر متغیرهای محیطی `.env`.
- [`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php): کانکشن امن PDO، تنظیمات کوکی سشن (SameSite=Lax, HttpOnly).
- [`includes/App.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/App.php): کانتینر مرکزی و دسترسی Singleton به سرویس‌های پلتفرم.
- [`includes/functions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/functions.php): توابع عمومی امنیتی، تاریخ شمسی، اعتبارسنجی CSRF و متادیتای سیستم.
- [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) & [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php): هدر و فوتر سراسری همراه با منوی ریسپانسیو دسکتاپ و نوبار موبایل، همگام با شمارنده سبد خرید.

### ۲.۲. صفحات فروشگاه، سفارشات و خدمات عمومی
- [`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php): لندینگ پیج اصلی آسنا، اسلایدرها، محصولات پرفروش و نوبت‌دهی سریع.
- [`shop.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/shop.php): ویترین فروشگاه پت‌شاپ آنلاین با فیلتر دسته‌بندی و مرتب‌سازی.
- [`pharmacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacy.php): داروخانه تخصصی دامپزشکی، داروهای نسخه‌ای (Rx)، مکمل‌ها و زنجیره سرد.
- [`product_details.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/product_details.php): صفحه جزئیات کالا، انتخاب خرید عادی یا اشتراک ادواری اتوشیپ، مشخصات و نظرات.
- [`cart.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/cart.php): سبد خرید هوشمند تفکیک‌شده (تب کالاهای عادی و تب اتوشیپ)، محاسبه مالیات و تخفیف‌ها.
- [`payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/payment.php): اتصال امن به درگاه پرداخت برای سبد خرید، نوبت کلینیک و بسته‌های پیامک.
- [`mock_payment_gateway.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/mock_payment_gateway.php): درگاه شبیه‌ساز شاپرک جهت تست بدون نیاز به کارت واقعی بانکی.
- [`booking.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/booking.php): تقویم نوبت‌دهی آنلاین پزشکان متخصص کلینیک با بررسی تایم‌اسلات‌های آزاد.
- [`chat.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/chat.php): تله‌هلث و مشاوره آنلاین هوشمند با دستیار هوش مصنوعی و دامپزشک.
- [`charity.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/charity.php) & [`charity_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/charity_payment.php): کمپین‌های نجات حیوانات حمایتی و واریز دونیشن شفاف.
- [`rewards.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/rewards.php): باشگاه مشتریان آسنا، جوایز وفاداری، گردونه شانس و امتیازات.
- [`wishlist.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/wishlist.php): لیست علاقه‌مندی‌های کاربر.
- [`subscriptions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/subscriptions.php) & [`subscription_checkout.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/subscription_checkout.php): پلن‌های عضویت و اتوشیپ.
- [`user_tickets.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/user_tickets.php): ثبت و پیگیری تیکت‌های پشتیبانی مشتریان.
- [`organizations.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/organizations.php) & [`organization_profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/organization_profile.php): دایرکتوری و مشخصات مراکز درمانی و کلینیک‌های عضو.
- [`terms.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/terms.php)، [`privacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/privacy.php)، [`contract_acceptance.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/contract_acceptance.php): شرایط و قوانین حقوقی، حریم خصوصی و گیت قراردادها.

### ۲.۳. احراز هویت و حساب کاربری
- [`login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/login.php): ورود با رمز یکبارمصرف پیامکی (OTP)، پسورد یا ثبت‌نام سریع.
- [`register.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/register.php), [`logout.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/logout.php), [`forgot_password.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/forgot_password.php), [`reset_password.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/reset_password.php).
- [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php): داشبورد جامع مشتریان با معماری دیجی‌کالا (مشخصات، حیوانات من، نوبت‌ها، تاریخچه سفارشات، کیف‌پول و بخش مدیریت نشانی‌ها با نقشه).
- [`profile_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile_settings.php): تنظیمات جانبی نشانی و رمز عبور.

### ۲.۴. پنل‌های چندنقشی (Multi-Role Portals)
- [`admin/`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/): پنل مدیریت ارشد سامانه (۳۰+ بخش شامل سفارشات، انبار، تراکنش‌های اسکرو، پزشکان، کاربران، تیکت‌ها، تسویه‌حساب‌ها و لاگ‌های امنیتی).
- [`organization/`](file:///opt/lampp/htdocs/asena/asena-enterprise/organization/): پورتال کلینیک‌ها و بیمارستان‌ها (مدیریت پزشکان، نوبت‌ها، شیفت‌بندی، انبار دارویی و مالی).
- [`doctor/`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/): کنسول تخصصی پزشک (برنامه ویزیت‌ها، ثبت پرونده بالینی، راهنمای پزشکی).
- [`pharmacist/`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacist/): صف بررسی نسخه‌های پزشکی، تأیید اصالت دارو، شرایط زنجیره سرد.
- [`seller/`](file:///opt/lampp/htdocs/asena/asena-enterprise/seller/): پنل فروشندگان مارکت‌پلیس (محصولات، سفارشات ارجاعی و کیف‌پول).

### ۲.۵. سرویس‌های اصلی بک‌اند (`includes/`)
- `SmsService.php`: ارسال پیامک‌های اعتبارسنجی، نوبت‌دهی و فاکتور از طریق ملی‌پیامک.
- `MarketplaceEscrowService.php`: امانت‌داری مالی (Escrow) ۷ روزه وجوه فروشندگان تا تحویل قطعی مرسوله.
- `AutoshipService.php`: زمان‌بندی و ایجاد سفارشات ادواری خودکار برای غذای پت و داروها.
- `PostexShippingService.php`, `IranPostService.php`, `ShippingCalculator.php`: استعلام نرخ پست و رهگیری مرسولات.
- `AiContentService.php`: سرویس هوش مصنوعی با اتصال چندمدله AvalAI برای چت و تولید محتوا.
- `SecurityMiddleware.php`, `WafMiddleware.php`, `RateLimiter.php`, `DataSecurityService.php`: دیواره آتش نرم‌افزاری و مقابله با حملات Brute-Force / XSS / SQLi.

### ۲.۶. پردازشگرها و اکشن‌های AJAX (`actions/`)
- [`actions/cart_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/cart_action.php): افزودن، حذف، تغییر تعداد و تغییر مدل خرید (عادی/اتوشیپ) با خروجی استاندارد JSON.
- [`actions/complete_payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/complete_payment.php): وریفای نهایی تراکنش، ثبت فاکتور `#PC-...`، کسر انبار، شارژ امتیاز و ارسال پیامک.
- [`actions/reverse_geocode.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/reverse_geocode.php): تبدیل آنی مختصات جغرافیایی به نشانی استاندارد فارسی.
- سایر اکشن‌ها: `booking_action.php`, `chat_action.php`, `profile_action.php`, `settings_action.php`, `autoship_worker.php`, `generate_invoice.php` و...

### ۲.۷. فایل‌های استاتیک و دارایی‌های بصری (`assets/`)
- [`assets/js/cart-manager.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/cart-manager.js): ماژول جاوااسکریپت سراسری برای به‌روزرسانی فوری شمارنده سبد خرید با تأخیر ۰ میلی‌ثانیه‌ای، افکت ذرات و نوتیفیکیشن شیشه‌ای.
- [`assets/css/enterprise-ui.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/enterprise-ui.css): استایل‌های سازمانی، انیمیشن‌های Keyframe سبد خرید و توست‌ها.

---

## ۳. تاریخچه تغییرات اخیر (Change Log)

### نسخه ۱.۰.۲ (سپتامبر ۲۰۲۶ - نگارش قبلی)
1. **رفع خطای انقضای اطلاعات سفارش در درگاه (`actions/complete_payment.php`):**
   - مقداردهی متغیر `$pending` از سشن قبل از شروط گیت ۳.
   - لود کردن توابع کمکی `functions.php` جهت جلوگیری از خطای ناشناخته بودن متدها.
   - ذخیره صریح `user_id` در سشن `pending_order` در [`payment.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/payment.php) برای مقابله با تغییر نشست‌ها.
   - رول‌بک اتمیک دیتابیس با بلوک سراسری `catch (Throwable $e)`.
2. **شمارنده بدون تأخیر سبد خرید در هدر (Optimistic Live Cart Counter):**
   - ایجاد ماژول مستقل [`assets/js/cart-manager.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/cart-manager.js) و انیمیشن‌های جهش در هدر و نوبار موبایل.
   - اتصال کلیه دکمه‌های افزودن به سبد خرید در `shop.php`, `index.php`, `pharmacy.php`, `product_details.php` به این متد.
3. **انتخاب موقعیت روی نقشه و تکمیل خودکار آدرس (Reverse Geocoding):**
   - توسعه اندپوینت سبک [`actions/reverse_geocode.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/reverse_geocode.php) جهت استخراج فارسی شهر، محله، خیابان و کدپستی از روی مختصات.
   - ارتقای نقشه تعاملی در [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php) و [`profile_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile_settings.php)؛ با درگ پین یا کلیک روی نقشه، فیلد نشانی، شهر و کدپستی خودکار پر می‌شوند.
   - اضافه شدن دکمه GPS، چیپ شناور نشانی و کلیدهای پرش سریع به تبریز و تهران.
4. **سینک و پوش گیت‌هاب:**
   - حل تعارض‌های ادغام در `config.php` و `includes/header.php` و انتشار موفق کامیت‌های `130af37` و `98bde81` در شاخه `main`.

### نسخه ۱.۰.۳ (سپتامبر ۲۰۲۶ - رفع جامع ممیزی فنی ۱۶ صفحه‌ای و امن‌سازی سرور)
1. **امن‌سازی ورود تست و ربات‌ها (C-1 & H-2):**
   - حفظ دسترسی `auto_login.php` صرفاً برای فاز تست کاربری طبق دستور کارفرما، با اعمال هدر و تگ متای ضد ایندکس `noindex, nofollow, noarchive`.
   - مسدودسازی دسترسی خزنده‌های گوگل و بینگ به فایل ورود خودکار و فولدر `includes/` در [`robots.txt`](file:///opt/lampp/htdocs/asena/asena-enterprise/robots.txt).
   - اصلاح نگاشت نقش داروخانه (`pharmacy` به `pharmacist`)، فعال‌سازی وضعیت تایید (`approved`) و تایید خودکار قراردادها در ورود سریع.
2. **اصلاح کوئری‌ها و سرویس BPMS نسخ دارویی (C-2 & C-3):**
   - اصلاح ستون‌های نامعتبر پزشک و جوین جدول سازمان‌ها در [`includes/BpmsService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/BpmsService.php).
   - مجهزسازی پنل پزشک ([`doctor/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/index.php)) و داروخانه ([`pharmacist/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacist/index.php)) به بلوک‌های تاب‌آور `try/catch` با کوئری‌های جایگزین جهت جلوگیری از Fatal Error.
3. **همگام‌سازی جداول اعلانات و نسخه‌ها (C-4):**
   - نگارش مایگریشن پایگاه‌داده [`database/migrations/14_user_notifications_and_prescriptions_alignment.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/database/migrations/14_user_notifications_and_prescriptions_alignment.sql) جهت ایجاد جدول‌های `user_notifications`، `pwa_subscriptions` و `live_social_proof_events`.
   - ایجاد بلوک خودترمیم `CREATE TABLE IF NOT EXISTS` و مدیریت خطای PDO در پنل مدیریت اعلانات ([`admin/notifications.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/notifications.php)).
4. **رفع تداخل و بازگشت بی‌پایان توابع افزودن به سبد خرید (C-5):**
   - اصلاح ماژول [`assets/js/cart-manager.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/cart-manager.js) و اکسپورت تابع سراسری `window.cartManagerAddToCart` با خواندن خودکار نوع خرید (عادی/اتوشیپ).
   - رفع بازتعریف توابع و همگام‌سازی دکمه‌های خرید در [`product_details.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/product_details.php)، [`shop.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/shop.php)، [`pharmacy.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/pharmacy.php) و [`index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/index.php).
5. **سخت‌سازی امنیتی و جلوگیری از دسترسی مستقیم به هسته (C-6 & H-5):**
   - ایجاد فایل [`includes/.htaccess`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/.htaccess) برای انسداد هرگونه فراخوانی مستقیم فایلهای پوشه هسته.
   - اعمال گارد امنیتی بررسی `SCRIPT_FILENAME` در [`includes/db.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/db.php)، [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) و [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php).
   - خاموش کردن نمایش خطاهای حساس سمت سرور (`display_errors = 0`)، فعال‌سازی لاگ سرور، حذف هدر شناسایی فناوری (`X-Powered-By`) و بافرینگ خروجی سراسری `ob_start()`.
6. **رفع خطای هدرهای ارسال شده در علاقه‌مندی‌ها (H-1):**
   - اصلاح ترتیب بررسی نشست احراز هویت و ریدایرکت قبل از ارسال هدرهای HTML در [`wishlist.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/wishlist.php).
7. **یکپارچه‌سازی امور مالی، کارمزدها و زمان‌بندی تسویه‌حساب (H-3 & H-4):**
   - استانداردسازی کارمزد پلتفرم روی **۱۵ درصد** و سهم خالص ۸۵ درصدی در پنل‌های فروشنده و ادمین ([`seller/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/seller/index.php)، [`admin/finance_settings.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/finance_settings.php)، [`admin/payouts.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/payouts.php) و سایر بخش‌ها).
   - هماهنگ‌سازی زمان تسویه پایا به **پنج‌شنبه‌ها ساعت ۹:۰۰ صبح** در سراسر متون سیستم.
8. **ناوبری هوشمند هدر و دسترسی سریع به پنل‌های کاربری (H-7):**
   - اضافه شدن دکمه‌های هوشمند هدایت به پنل اختصاصی نقش‌های فعال (مدیر، پزشک، کلینیک، داروخانه، فروشنده و پروفایل مشتری) در هدر دسکتاپ و دراور منوی موبایل در [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php).
9. **اصلاح آمار و فیلترهای مدیریت کاربران (M-2):**
   - تراز کردن شمارنده‌های کارت‌های KPI با فیلترهای نقش در [`admin/user_management.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/user_management.php) به طوری که مجموع نقش‌ها دقیقاً برابر کل کاربران باشد.
10. **پاکسازی داده‌های آزمایشی و اصلاح Enum دیتابیس (H-6 & M-3):**
    - جایگزینی داده‌های آزمایشی ('تستر آسنا' به 'فروشگاه تجهیزات پت‌پارس') در فایل [`asena_enterprise_host_ready.sql`](file:///opt/lampp/htdocs/asena/asena-enterprise/asena_enterprise_host_ready.sql).
    - اضافه کردن مقدار `'pharmacy'` به تعریف فیلد `role` در جدول `users` دامپ پایگاه داده.
11. **سئو و عناوین داینامیک صفحات (M-5):**
    - افزودن تایتل‌های بهینه‌سازی شده فارسی به `$seo_defaults` در [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php) برای کلیه صفحات داخلی.
12. **پاکسازی فایل‌های زائد و منسوخ پروژه (Clean-up):**
    - حذف فایل‌های متنی سرگردان (`5162730.txt`, `5271122.txt`).
    - حذف اسکریپت‌های تست و موقت روت (`test_uri.php`, `setup_db.php`, `server_ip.php`, `test_sms.php`, `actions/test_sms.php`).
    - حذف اسکریپت‌های مایگریشن مجزای قدیمی در پوشه اکشن‌ها (`actions/migrate_tickets.php`, `actions/migrate_pharmacy_features.php`, `actions/migrate_charity.php`, `bin/apply_migration_12.php`, `bin/apply_migration_13.php`) به دلیل تجمیع رسمی در مایگریشن‌های چهارده‌گانه [`database/migrations/`](file:///opt/lampp/htdocs/asena/asena-enterprise/database/migrations/).
13. **ارتقای امنیتی لایه‌های فرانت‌اند، ریت‌لیمیتر و کامپایل استاتیک استایل‌ها:**
    - صف‌بندی هوشمند بنرهای موبایل در [`includes/footer.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/footer.php) و [`includes/cookie_consent.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/cookie_consent.php) جهت ممانعت از تداخل بنر کوکی با پاپ‌آپ PWA.
    - دوطرفه‌سازی کنترل نرخ ارسال پیامک در [`includes/functions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/functions.php) (بررسی همزمان سقف IP و شماره تلفن کاربر برای پیشگیری از حملات بات‌نت به درگاه پیامک).
    - پاکسازی هدرهای افشاگر `X-Powered-By` و منسوخ `X-XSS-Protection` در سطح آپاچی در [`.htaccess`](file:///opt/lampp/htdocs/asena/asena-enterprise/.htaccess).
    - غنی‌سازی فال‌بک‌های سئو برای صفحات کلینیک‌ها، پزشکان و محصولات در [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php).
    - راه‌اندازی فایل پیکربندی [`tailwind.config.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/tailwind.config.js)، اسکریپت [`bin/build-css`](file:///opt/lampp/htdocs/asena/asena-enterprise/bin/build-css) و تولید استایل کامپایل‌شده سبک ۲۰۰ کیلوبایتی [`assets/css/tailwind.output.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/tailwind.output.css).
14. **بازگردانی پالت رنگی رسمی سرمه‌ای تیره سازمانی (Navy Blue & Orange) مطابق نسخه کلینیکال Premium:**
    - بازنشانی کدهای رنگ در [`assets/js/tailwind-config.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/js/tailwind-config.js) و [`tailwind.config.js`](file:///opt/lampp/htdocs/asena/asena-enterprise/tailwind.config.js) به سرمه‌ای سازمانی آسنا (`primary: #001a48`، `primary-container: #002d72`)، آکسان‌های نارنجی آذرین (`secondary-container: #fd8100`) و رنگ‌های وابسته بر اساس مستندات [`PROJECT_GUIDELINES.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/PROJECT_GUIDELINES.md).
    - به‌روزرسانی متغیرهای روت در [`assets/css/enterprise-ui.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/enterprise-ui.css) جهت اعمال کنتراست عمیق سرمه‌ای.
    - بیلد و بازتولید فایل نهایی استایل پروداکشن [`assets/css/tailwind.output.css`](file:///opt/lampp/htdocs/asena/asena-enterprise/assets/css/tailwind.output.css).
15. **همگام‌سازی و انتشار روی شاخه `master` گیت‌هاب جهت استقرار خودکار سی‌پنل (cPanel Deployment):**
    - مخزن Git™ Version Control در سی‌پنل هاست روی شاخه `master` تنظیم شده بود، در حالی که کامیت‌های اخیر روی شاخه `main` ارسال شده بودند.
    - کلیه تغییرات اصلاحی ممیزی، تم رنگی سرمه‌ای و امن‌سازی در پوشه `asena.company` بر روی شاخه `master` تجمیع گردیده و با کامیت `1cb9537` به مخزن گیت‌هاب `AyhanMehrzad/asena.company` پوش شد.
    - قابلیت دریافت مستقیم از طریق دکمه «Update from Remote» و اعمال آنی روی هاست از طریق «Deploy HEAD Commit» در پنل سی‌پنل فعال گردید.
16. **رفع خطاهای ۵۰۰ آنلاین، بازتعریف توابع جلالی و تاب‌آوری سراسری احراز هویت (Online Reliability & Self-Healing Fixes):**
    - **رفع خطای ۵۰۰ صفحه اشتراک‌ها ([`subscriptions.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/subscriptions.php)):** بازنویسی کوئری‌های محصولات و داروخانه با بلوک‌های حفاظتی `try/catch` و مقادیر پیش‌فرض؛ رفع خطای عدم وجود ستون‌های `autoship_min_months_stock`، `organization_id` و جوین‌های اضافی.
    - **رفع خطای ۵۰۰ تقویم یادداشت‌های ادمین ([`admin/calendar_notes.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/calendar_notes.php)):** افزودن گاردهای `if (!function_exists(...))` برای توابع تبدیل تاریخ شمسی `gregorian_to_jalali` و `jalali_to_gregorian` جهت ممانعت از Fatal Error بازتعریف توابع `jdf.php`.
    - **رفع خطای ۵۰۰ و قفل سراسری پنل‌های نقشی در گیت قرارداد ([`includes/ContractService.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/ContractService.php) & [`includes/AuthGuard.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/AuthGuard.php)):** افزودن متد خودترمیم `ensureSchema()` جهت ایجاد جدول `contract_acceptances`؛ ایمن‌سازی کوئری کش با `try/catch` روی ستون `contract_accepted_version`؛ بای‌پس امن سشن و رفتار Fail-Open در محیط‌های آزمایشی.
    - **همگام‌سازی ورود خودکار سریع ([`auto_login.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/auto_login.php)):** ثبت فوری مقدار نسخه قرارداد در نشست سشن کاربر و ایمن‌سازی دستورات درج در جدول `users`.
    - **تزریق مکانیسم خودترمیم خودکار پایگاه داده در داشبورد مدیریت ([`admin/index.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/index.php)):** افزودن روال خودکار ایجاد جدول `contract_acceptances` و افزودن تک‌تک ۱۱ ستون ضروری پایگاه داده به محض ورود مدیر به پیشخوان، بدون نیاز به اجرای دستی یا بروز خطای سینتکس در phpMyAdmin.

---

## ۴. پروتکل ثبت تغییرات آینده (Maintenance Rule)
> **دستورالعمل برای هوش مصنوعی در ادامه کار:**  
> هر زمان که فایل جدیدی ایجاد یا فایلی ویرایش شد:
> ۱. بلافاصله تغییر انجام‌شده را با ذکر نام فایل و دلیل فنی، به انتهای بخش **۳. تاریخچه تغییرات اخیر (Change Log)** در همین فایل ([`PROJECT_CONTEXT.md`](file:///opt/lampp/htdocs/asena/asena-enterprise/PROJECT_CONTEXT.md)) اضافه کن.  
> ۲. نیاز به نگهداری تاریخچه طولانی در پنجره پرامپت نیست؛ هر زمان کانتکس پر یا ریست شد، با استناد به این فایل می‌توان کار را بدون اتلاف وقت ادامه داد.
