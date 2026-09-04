# گام‌های تکمیل‌شده در معماری سازمانی آسنا (ASENA Enterprise)

معماری یکپارچه سازمانی آسنا با موفقیت در دایرکتوری مستقل `/opt/lampp/htdocs/asena/asena-enterprise/` راه‌اندازی و پیاده‌سازی شد؛ بدون اینکه کوچک‌ترین تغییری در وب‌سایت فعال یا پوشه‌های قبلی مشتریان (`asena.company`) ایجاد شود.

---

## ۱. خلاصه‌ی تغییرات کلیدی (Executive Summary)

| چالش قبلی | راه‌حل ارائه‌شده در معماری Enterprise |
| :--- | :--- |
| **تکثیر کدها در ۷ پوشه مجزا** (`basic`, `standard`, `premium`, `pharmacy` و...) صرفاً جهت ارسال به هاست مشتریان | **یک هسته واحد کُد (Single Source of Truth)** همراه با ابزار خط‌فرمان پکیج‌ساز خودکار (`bin/asena package`) که در ۱ ثانیه زیپ مخصوص هر نسخه را می‌سازد. |
| **تعریف امکانات با کدهای دستی** | **ماتریس ویژگی‌ها و فیچرفلگ‌ها (`Feature::has(...)`)** بر اساس فایل پیکربندی استاندارد `config/tiers.php` و متغیر `ASENA_TIER` در `.env`. |
| **جدایی موجودی و سبد خرید پت‌شاپ و داروخانه** | **پل یکپارچه کاتالوگ و انبار**؛ سبد خرید (`cart.php`)، پرداخت (`complete_payment.php`)، و جزئیات محصول (`product_details.php`) به صورت خودکار هر دو جدول `products` و `pharmacy_medicines` را با قفل اتمیک سطری (`FOR UPDATE`) مدیریت می‌کنند. |
| **نیاز به هماهنگی تقویم برای نوبت‌ها** | **افزودن خروجی تقویم گوگل و فایل استاندارد `.ics`** در `actions/calendar_export.php` و دکمه‌های مستقیم در پنل کاربری (`profile.php`). |

---

## ۲. ساختار فیچرفلگ‌ها و سطوح لایسنس (Tier Matrix)

فایل مرکزی: [`config/tiers.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/config/tiers.php) و کلاس [`includes/Feature.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/Feature.php)

```
                       ┌───────────────────────┐
                       │   ASENA_TIER (.env)   │
                       └───────────┬───────────┘
                                   │
                                   ▼
                       ┌───────────────────────┐
                       │  Feature::has('...')  │
                       └───────────┬───────────┘
         ┌─────────────────────────┼─────────────────────────┐
         ▼                         ▼                         ▼
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│ Storefront Nav  │       │  Admin Sidebar  │       │ Doctor Console  │
│ (header.php)    │       │(admin_header.php│       │(doctor_header.ph│
└─────────────────┘       └─────────────────┘       └─────────────────┘
```

### سطوح ۵ گانه لایسنس:
1. **`basic` (نسخه پایه کلینیک و پت‌شاپ)**:
   - نوبت‌دهی آنلاین، کاتالوگ پت‌شاپ، پروفایل کاربری، سبد خرید و درگاه پرداخت.
2. **`standard` (نسخه تجاری استاندارد)**:
   - تمامی امکانات Basic + وبلاگ و دانشنامه پزشکی، سیستم وفاداری و باشگاه مشتریان، نظرات کاربران.
3. **`premium` (نسخه حرفه‌ای فول کلینیک)**:
   - تمامی امکانات Standard + سفارش دوره‌ای خودکار (Autoship)، باکس‌های سفارشی، امتیازدهی بیزی (Bayesian)، مشاوره آنلاین (Telehealth)، کمپین‌های خیریه، اتوماسیون پیامک.
4. **`pharmacy` (نسخه تخصصی داروخانه دامپزشکی)**:
   - کاتالوگ داروهای دام، طیور و پت، مدیریت نسخه الکترونیک (Rx)، زنجیره سرد، پنل داروساز، سفارش ادواری دارو (Autoship)، اتوماسیون پیامک.
5. **`enterprise` (نسخه جامع اکوسیستم آسنا)**:
   - فعال بودن تمامی ۱۸ ماژول به صورت همزمان (کلینیک + پت‌شاپ + داروخانه تخصصی).

---

## ۳. ابزار خط‌فرمان سازمانی (`bin/asena`)

ابزار CLI طراحی‌شده در [`bin/asena`](file:///opt/lampp/htdocs/asena/asena-enterprise/bin/asena) به شما امکان می‌دهد سیستم را به راحتی مدیریت و پکیج‌های خریداران را در چند ثانیه بسازید:

### دستورات کاربردی:

#### مشاهده وضعیت سیستم و ماژول‌های فعال:
```bash
/opt/lampp/bin/php bin/asena status
```

#### تغییر آنی لایسنس سرور محلی:
```bash
/opt/lampp/bin/php bin/asena set-tier pharmacy
/opt/lampp/bin/php bin/asena set-tier enterprise
```

#### ساخت زیپ آماده نصب مشتری با یک دستور:
```bash
/opt/lampp/bin/php bin/asena package --tier=pharmacy --name=Razi_Pet_Pharmacy
/opt/lampp/bin/php bin/asena package --tier=premium --name=Aras_Clinic
/opt/lampp/bin/php bin/asena package --tier=basic --name=Tabriz_Clinic
```
- خروجی مستقیماً در پوشه `dist/` قرار می‌گیرد.
- شامل نصب‌کننده خودکار `install.php`، فایل `.env` با تنظیم دقیق نسخه انتخابی مشتری، پایگاه‌داده ساختاریافته و حذف هرگونه فایل موقت/تست است.

---

## ۴. فایل‌ها و ماژول‌های ارتقاء‌یافته

1. [`includes/header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/includes/header.php): منوی هدر دسکتاپ و موبایل بر اساس `Feature::has()` شخصی‌سازی می‌شود.
2. [`admin/includes/admin_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/admin/includes/admin_header.php): سایدبار و دکمه‌های نوار بالا متناسب با لایسنس فعال پالایش شده و نشان وضعیت زنده نسخه فعال (مانند `نسخه اینترپرایز جامع`) را نمایش می‌دهد.
3. [`doctor/includes/doctor_header.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/doctor/includes/doctor_header.php): کنترل دسترسی منوها و بررسی مجوز `clinic_booking`.
4. [`cart.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/cart.php) و [`actions/cart_action.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/cart_action.php): سبد خرید چندمنظوره برای کالاها و داروهای دامپزشکی.
5. [`product_details.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/product_details.php): نمایش هوشمند اقلام پت‌شاپ و داروخانه همراه با اطلاعات زنجیره سرد، نسخه، پیشنهادات مشابه و داده‌های ساختاریافته Schema.org JSON-LD.
6. [`actions/calendar_export.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/actions/calendar_export.php): تولید خودکار فایل استاندارد `.ics` و لینک مستقیم تقویم گوگل.
7. [`profile.php`](file:///opt/lampp/htdocs/asena/asena-enterprise/profile.php): افزوده شدن دکمه‌های خروجی تقویم برای نوبت‌های تاییدشده.
8. حفاظت از دسترسی مستقیم به صفحات نامرتبط با نسخه (`booking.php`, `shop.php`, `pharmacy.php`, `subscriptions.php`, `charity.php`, `knowledge_base.php`).
