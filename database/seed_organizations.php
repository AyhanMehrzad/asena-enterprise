<?php
/**
 * ASENA Enterprise - Organizations & Clinical Ecosystem Seeder
 */
require_once __DIR__ . '/../includes/db.php';
global $pdo;

echo "Seeding Organizations and Clinical Ecosystem...\n";

// 1. Seed Verified Doctors
$doctorsData = [
    [
        'name' => 'دکتر سهراب علوی',
        'specialty' => 'بورد تخصصی جراحی ارتوپدی و ستون فقرات',
        'rating' => 4.9,
        'review_count' => 84,
        'phone' => '09121112233',
        'price' => 380000,
        'image_url' => 'assets/images/vet-hero.png',
        'schedule_info' => 'شنبه، دوشنبه، چهارشنبه از ساعت ۱۶:۰۰ الی ۲۱:۰۰'
    ],
    [
        'name' => 'دکتر هما مهرزاد',
        'specialty' => 'متخصص بیماری‌های داخلی و تصویربرداری تشخیصی',
        'rating' => 4.9,
        'review_count' => 96,
        'phone' => '09122223344',
        'price' => 320000,
        'image_url' => 'assets/images/vet-hero.png',
        'schedule_info' => 'یکشنبه، سه‌شنبه، پنجشنبه از ساعت ۱۰:۰۰ الی ۱۸:۰۰'
    ],
    [
        'name' => 'دکتر کامران شایان',
        'specialty' => 'متخصص جراحی بافت نرم و بیهوشی استنشاقی',
        'rating' => 4.8,
        'review_count' => 67,
        'phone' => '09123334455',
        'price' => 350000,
        'image_url' => 'assets/images/vet-hero.png',
        'schedule_info' => 'همه روزه به جز جمعه از ساعت ۱۴:۰۰ الی ۲۰:۰۰'
    ],
    [
        'name' => 'دکتر مریم صادقی',
        'specialty' => 'متخصص دندانپزشکی و جرم‌گیری اولتراسونیک پت',
        'rating' => 4.9,
        'review_count' => 52,
        'phone' => '09124445566',
        'price' => 290000,
        'image_url' => 'assets/images/vet-hero.png',
        'schedule_info' => 'شنبه تا چهارشنبه از ساعت ۹:۰۰ الی ۱۵:۰۰'
    ],
    [
        'name' => 'دکتر پوریا رستمی',
        'specialty' => 'فوق‌تخصص پرندگان زینتی، طوطی‌سانان و حیوانات اگزوتیک',
        'rating' => 4.8,
        'review_count' => 73,
        'phone' => '09125556677',
        'price' => 310000,
        'image_url' => 'assets/images/vet-hero.png',
        'schedule_info' => 'یکشنبه و چهارشنبه از ساعت ۱۵:۰۰ الی ۲۱:۰۰'
    ],
    [
        'name' => 'دکتر نیلوفر بختیاری',
        'specialty' => 'متخصص مراقبت‌های ویژه (ICU) و اورژانس دامپزشکی',
        'rating' => 5.0,
        'review_count' => 41,
        'phone' => '09126667788',
        'price' => 360000,
        'image_url' => 'assets/images/vet-hero.png',
        'schedule_info' => 'شیفت شب و روزهای فرد به صورت ۲۴ ساعته'
    ]
];

$docMap = [];
foreach ($doctorsData as $d) {
    $st = $pdo->prepare("SELECT id FROM doctors WHERE name = ?");
    $st->execute([$d['name']]);
    $existingId = $st->fetchColumn();
    if ($existingId) {
        $docMap[$d['name']] = $existingId;
    } else {
        $ins = $pdo->prepare("
            INSERT INTO doctors (name, specialty, rating, review_count, phone, price, image_url, schedule_info)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $d['name'], $d['specialty'], $d['rating'], $d['review_count'],
            $d['phone'], $d['price'], $d['image_url'], $d['schedule_info']
        ]);
        $docMap[$d['name']] = $pdo->lastInsertId();
    }
}
echo "Doctors seeded: " . count($docMap) . "\n";

// 2. Seed Rich Organizations
$organizationsData = [
    [
        'name' => 'بیمارستان فوق تخصصی دامپزشکی پایتخت',
        'slug' => 'payetakht-hospital',
        'type' => 'hospital',
        'license_number' => 'IR-VET-HOSP-9481',
        'manager_name' => 'دکتر کامران شایان',
        'phone' => '02188776655',
        'emergency_phone' => '09121112233',
        'email' => 'info@payetakht-vet.ir',
        'website' => 'https://payetakht-vet.ir',
        'instagram' => 'payetakht_vet_hospital',
        'province' => 'تهران',
        'city' => 'تهران',
        'address' => 'خیابان ولیعصر، بالاتر از پارک ساعی، نبش کوچه شمس، پلاک ۱۲',
        'latitude' => 35.73500000,
        'longitude' => 51.41100000,
        'operating_hours' => 'شبانه روزی ۲۴/۷ (شامل اورژانس و ICU)',
        'is_24_7' => 1,
        'logo_url' => 'assets/images/logo.png',
        'banner_url' => 'assets/images/cat-hero.jpg',
        'description' => 'مجهزترین مرکز درمانی، جراحی و تشخیصی حیوانات خانگی کشور با کادر اساتید دانشگاهی و بخش‌های بستری مجزا برای سگ و گربه. دارای بخش تصویربرداری پیشرفته، رادیولوژی دیجیتال، اکوکاردیوگرافی و آزمایشگاه بیوشیمی با جوابدهی آنلاین.',
        'facilities' => 'بخش جراحی قلب و ارتوپدی, رادیولوژی دیجیتال DR, سونوگرافی کالر داپلر, انکوباتور اکسیژن ICU, آزمایشگاه تخصصی خون, آمبولانس اختصاصی, داروخانه شبانه‌روزی',
        'rating' => 4.9,
        'review_count' => 148,
        'status' => 'approved'
    ],
    [
        'name' => 'بیمارستان مرکزی دامپزشکی شیراز',
        'slug' => 'shiraz-central-hospital',
        'type' => 'hospital',
        'license_number' => 'IR-VET-HOSP-7201',
        'manager_name' => 'دکتر سهراب علوی',
        'phone' => '07136280000',
        'emergency_phone' => '09173339900',
        'email' => 'contact@shiraz-vethospital.com',
        'website' => 'https://shiraz-vethospital.com',
        'instagram' => 'shiraz_central_vet',
        'province' => 'فارس',
        'city' => 'شیراز',
        'address' => 'بلوار قصرالدشت، روبروی کوچه ۵۸، جنب مجتمع پزشکی نگین',
        'latitude' => 29.63800000,
        'longitude' => 52.51200000,
        'operating_hours' => 'شبانه روزی ۲۴/۷ (اورژانس، ترومای جراحی و بستری)',
        'is_24_7' => 1,
        'logo_url' => 'assets/images/logo.png',
        'banner_url' => 'assets/images/cat-hero.jpg',
        'description' => 'بزرگترین بیمارستان مرجع دامپزشکی جنوب کشور مجهز به بخش جراحی مغز و اعصاب حیوانات، سی‌تی‌اسکن، فیزیوتراپی و استخر آب‌درمانی، با ظرفیت بستری ۵۰ قلاده سگ و گربه در فضایی کاملاً استاندارد و استریل.',
        'facilities' => 'اورژانس شبانه‌روزی ۲۴ ساعته, جراحی ستون فقرات و مفاصل, فیزیوتراپی و هیدروتراپی, آندوسکوپی گوارشی, آزمایشگاه پاتولوژی, داروخانه تخصصی',
        'rating' => 4.9,
        'review_count' => 112,
        'status' => 'approved'
    ],
    [
        'name' => 'کلینیک تخصصی و جراحی پرشین پت',
        'slug' => 'persian-pet-clinic',
        'type' => 'clinic',
        'license_number' => 'IR-VET-CLN-8812',
        'manager_name' => 'دکتر هما مهرزاد',
        'phone' => '02122334455',
        'emergency_phone' => '09122223344',
        'email' => 'contact@persianpetclinic.com',
        'website' => 'https://persianpetclinic.com',
        'instagram' => 'persian_pet_clinic',
        'province' => 'تهران',
        'city' => 'تهران',
        'address' => 'سعادت‌آباد، میدان کاج، خیابان سرو غربی، پلاک ۲۸، طبقه همکف',
        'latitude' => 35.78200000,
        'longitude' => 51.37400000,
        'operating_hours' => 'شنبه تا پنجشنبه ۹:۰۰ الی ۲۲:۰۰',
        'is_24_7' => 0,
        'logo_url' => 'assets/images/logo.png',
        'banner_url' => 'assets/images/dog-avatar.svg',
        'description' => 'ارائه کلیه خدمات واکسیناسیون، دندانپزشکی، جراحی بافت نرم، عقیم‌سازی و مشاوره تغذیه با پیشرفته‌ترین دستگاه‌های بیهوشی استنشاقی ایزوفلوران و پایش مداوم قلبی-عروقی حین جراحی.',
        'facilities' => 'جراحی بافت نرم و عقیم‌سازی, یونیت دندانپزشکی اولتراسونیک, پت‌شاپ دارویی, آرایش و شستشوی طبی, میکروچیپ و شناسنامه بین‌المللی',
        'rating' => 4.8,
        'review_count' => 92,
        'status' => 'approved'
    ],
    [
        'name' => 'کلینیک تخصصی دامپزشکی باران اصفهان',
        'slug' => 'baran-vet-clinic',
        'type' => 'clinic',
        'license_number' => 'IR-VET-CLN-5120',
        'manager_name' => 'دکتر مریم صادقی',
        'phone' => '03136691234',
        'emergency_phone' => '09132228811',
        'email' => 'info@baran-vet.ir',
        'website' => 'https://baran-vet.ir',
        'instagram' => 'baran_vet_isfahan',
        'province' => 'اصفهان',
        'city' => 'اصفهان',
        'address' => 'خیابان مرداویج، میدان برج، خیابان رسالت، پلاک ۱۴',
        'latitude' => 32.61500000,
        'longitude' => 51.66800000,
        'operating_hours' => 'شنبه تا پنجشنبه ۸:۳۰ الی ۲۱:۳۰ (جمعه‌ها با هماهنگی قبلی)',
        'is_24_7' => 0,
        'logo_url' => 'assets/images/logo.png',
        'banner_url' => 'assets/images/cat-hero.jpg',
        'description' => 'کلینیک پیشرو در استان اصفهان در زمینه چکاپ‌های منظم پیشگیرانه، واکسیناسیون استاندارد، دندانپزشکی بدون درد، چشم‌پزشکی و مراقبت‌های گوارشی گربه و سگ با محیطی آرامش‌بخش و بدون استرس (Fear-Free).',
        'facilities' => 'کلینیک دوستدار گربه (Cat Friendly), دندانپزشکی تخصصی, آزمایشگاه سریع و تست‌های ویروسی, داروخانه ملزومات, پانسیون روزانه',
        'rating' => 4.7,
        'review_count' => 64,
        'status' => 'approved'
    ],
    [
        'name' => 'کلینیک تخصصی پرندگان زینتی و اگزوتیک کاسپین',
        'slug' => 'caspian-exotic-clinic',
        'type' => 'clinic',
        'license_number' => 'IR-VET-CLN-6390',
        'manager_name' => 'دکتر پوریا رستمی',
        'phone' => '05138405555',
        'emergency_phone' => '09151234567',
        'email' => 'info@caspian-birds.ir',
        'website' => 'https://caspian-birds.ir',
        'instagram' => 'caspian_exotic_vet',
        'province' => 'خراسان رضوی',
        'city' => 'مشهد',
        'address' => 'خیابان احمدآباد، نبش ملاصدرا ۲، ساختمان پزشکان سپهر',
        'latitude' => 36.29700000,
        'longitude' => 59.57500000,
        'operating_hours' => 'شنبه تا چهارشنبه ۱۰:۰۰ الی ۲۰:۰۰',
        'is_24_7' => 0,
        'logo_url' => 'assets/images/logo.png',
        'banner_url' => 'assets/images/cat-hero.jpg',
        'description' => 'تنها مرکز فوق‌تخصصی شمال شرق کشور برای ویزیت، درمان بیماری‌های قارچی و تنفسی، جراحی ارتوپدی استخوان بال، منقار و بیهوشی ایمن طوطی کاسکو، مرغ عشق، خرگوش، همستر و خزندگان.',
        'facilities' => 'انکوباتور پرندگان, رادیوگرافی میکرو, آندوسکوپی تنفسی, تست‌های تعیین جنسیت DNA, آزمایشگاه تخصصی پرندگان',
        'rating' => 4.9,
        'review_count' => 78,
        'status' => 'approved'
    ],
    [
        'name' => 'داروخانه تخصصی دامپزشکی رازی',
        'slug' => 'razi-vet-pharmacy',
        'type' => 'pharmacy',
        'license_number' => 'IR-VET-PHAR-3392',
        'manager_name' => 'دکتر بهنام فرهمند',
        'phone' => '02166442211',
        'emergency_phone' => '09127778899',
        'email' => 'order@razi-vetpharmacy.com',
        'website' => 'https://razi-vetpharmacy.com',
        'instagram' => 'razi_vet_pharmacy',
        'province' => 'تهران',
        'city' => 'تهران',
        'address' => 'خیابان انقلاب، ابتدای خیابان فلسطین جنوبی، پلاک ۸۲',
        'latitude' => 35.70100000,
        'longitude' => 51.40300000,
        'operating_hours' => 'شبانه روزی ۲۴/۷ (تامین داروهای نایاب و مکمل‌های درمانی)',
        'is_24_7' => 1,
        'logo_url' => 'assets/images/logo.png',
        'banner_url' => 'assets/images/cat-hero.jpg',
        'description' => 'جامع‌ترین مرکز پخش و تامین داروهای تخصصی دامپزشکی، آنتی‌بیوتیک‌های کمیاب، داروهای قلبی و کلیوی، رژیم‌های درمانی رویال کنین و هیلز، و زنجیره سرد واکسن با ارسال فوری به سراسر کشور.',
        'facilities' => 'زنجیره سرد استاندارد واکسن, تایید آنلاین نسخ دامپزشکی, ارسال با پیک یخچالی, مشاوره داروساز دامی, رژیم‌های درمانی ویژه',
        'rating' => 4.9,
        'review_count' => 135,
        'status' => 'approved'
    ],
    [
        'name' => 'پناهگاه و نقاهتگاه حمایتی حیوانات وفا',
        'slug' => 'vafa-animal-shelter',
        'type' => 'shelter_charity',
        'license_number' => 'IR-NGO-SHELTER-104',
        'manager_name' => 'مهندس آرش شریفی',
        'phone' => '02644229988',
        'emergency_phone' => '09359998877',
        'email' => 'help@vafa-shelter.org',
        'website' => 'https://vafa-shelter.org',
        'instagram' => 'vafa_animal_shelter',
        'province' => 'البرز',
        'city' => 'کرج',
        'address' => 'جاده مخصوص کرج، انتهای هشتگرد، دشت بهشت، مجتمع توانبخشی حیوانات',
        'latitude' => 35.95200000,
        'longitude' => 50.68100000,
        'operating_hours' => 'همه روزه ۸:۰۰ الی ۱۸:۰۰ (پذیرش کیس امدادی ۲۴ ساعته)',
        'is_24_7' => 1,
        'logo_url' => 'assets/images/logo.png',
        'banner_url' => 'assets/images/dog-avatar.svg',
        'description' => 'بزرگترین پناهگاه مردم‌نهاد و غیرانتفاعی جهت نجات، درمان، عقیم‌سازی و بازپروری سگ‌ها و گربه‌های آسیب‌دیده با کلینیک صحرایی و همکاری داوطلبانه مجرب‌ترین جراحان کشور.',
        'facilities' => 'کلینیک جراحی و عقیم‌سازی امدادی, بخش قرنطینه و واکسیناسیون, حیاط‌های بازی و توانبخشی, سامانه آنلاین سرپرستی رایگان, آمبولانس امداد',
        'rating' => 5.0,
        'review_count' => 240,
        'status' => 'approved'
    ]
];

$orgMap = [];
foreach ($organizationsData as $o) {
    $ins = $pdo->prepare("
        INSERT INTO organizations (
            name, slug, type, license_number, manager_name, phone, emergency_phone,
            email, website, instagram, province, city, address, latitude, longitude,
            operating_hours, is_24_7, logo_url, banner_url, description, facilities,
            rating, review_count, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?
        )
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name),
            type = VALUES(type),
            license_number = VALUES(license_number),
            manager_name = VALUES(manager_name),
            phone = VALUES(phone),
            emergency_phone = VALUES(emergency_phone),
            email = VALUES(email),
            website = VALUES(website),
            instagram = VALUES(instagram),
            province = VALUES(province),
            city = VALUES(city),
            address = VALUES(address),
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            operating_hours = VALUES(operating_hours),
            is_24_7 = VALUES(is_24_7),
            facilities = VALUES(facilities),
            rating = VALUES(rating),
            review_count = VALUES(review_count),
            status = VALUES(status)
    ");
    $ins->execute([
        $o['name'], $o['slug'], $o['type'], $o['license_number'], $o['manager_name'],
        $o['phone'], $o['emergency_phone'], $o['email'], $o['website'], $o['instagram'],
        $o['province'], $o['city'], $o['address'], $o['latitude'], $o['longitude'],
        $o['operating_hours'], $o['is_24_7'], $o['logo_url'], $o['banner_url'],
        $o['description'], $o['facilities'], $o['rating'], $o['review_count'], $o['status']
    ]);

    $st = $pdo->prepare("SELECT id FROM organizations WHERE slug = ?");
    $st->execute([$o['slug']]);
    $orgMap[$o['slug']] = (int)$st->fetchColumn();
}
echo "Organizations seeded: " . count($orgMap) . "\n";

// 3. Link Doctors to Organizations
$affiliations = [
    // Payetakht Hospital
    ['slug' => 'payetakht-hospital', 'doc' => 'دکتر کامران شایان', 'is_head' => 1, 'days' => 'شنبه تا چهارشنبه', 'hours' => '۱۴:۰۰ الی ۲۰:۰۰'],
    ['slug' => 'payetakht-hospital', 'doc' => 'دکتر سهراب علوی', 'is_head' => 0, 'days' => 'یکشنبه و سه‌شنبه', 'hours' => '۱۶:۰۰ الی ۲۱:۰۰'],
    ['slug' => 'payetakht-hospital', 'doc' => 'دکتر نیلوفر بختیاری', 'is_head' => 0, 'days' => 'همه روزه (شیفت اورژانس)', 'hours' => '۲۱:۰۰ الی ۰۸:۰۰'],
    
    // Shiraz Central Hospital
    ['slug' => 'shiraz-central-hospital', 'doc' => 'دکتر سهراب علوی', 'is_head' => 1, 'days' => 'شنبه، دوشنبه، چهارشنبه', 'hours' => '۱۶:۰۰ الی ۲۱:۰۰'],
    ['slug' => 'shiraz-central-hospital', 'doc' => 'دکتر نیلوفر بختیاری', 'is_head' => 0, 'days' => 'روزهای فرد و پنجشنبه', 'hours' => '۱۴:۰۰ الی ۲۲:۰۰'],

    // Persian Pet Clinic
    ['slug' => 'persian-pet-clinic', 'doc' => 'دکتر هما مهرزاد', 'is_head' => 1, 'days' => 'شنبه تا پنجشنبه', 'hours' => '۱۰:۰۰ الی ۱۸:۰۰'],
    ['slug' => 'persian-pet-clinic', 'doc' => 'دکتر مریم صادقی', 'is_head' => 0, 'days' => 'یکشنبه و سه‌شنبه', 'hours' => '۱۴:۰۰ الی ۲۰:۰۰'],

    // Baran Vet Clinic Isfahan
    ['slug' => 'baran-vet-clinic', 'doc' => 'دکتر مریم صادقی', 'is_head' => 1, 'days' => 'شنبه تا چهارشنبه', 'hours' => '۰۹:۰۰ الی ۱۵:۰۰'],
    ['slug' => 'baran-vet-clinic', 'doc' => 'دکتر هما مهرزاد', 'is_head' => 0, 'days' => 'پنجشنبه‌ها', 'hours' => '۱۰:۰۰ الی ۱۷:۰۰'],

    // Caspian Exotic Clinic
    ['slug' => 'caspian-exotic-clinic', 'doc' => 'دکتر پوریا رستمی', 'is_head' => 1, 'days' => 'شنبه تا پنجشنبه', 'hours' => '۱۰:۰۰ الی ۲۰:۰۰'],

    // Vafa Shelter
    ['slug' => 'vafa-animal-shelter', 'doc' => 'دکتر کامران شایان', 'is_head' => 1, 'days' => 'جمعه‌ها (امداد و جراحی)', 'hours' => '۰۹:۰۰ الی ۱۸:۰۰']
];

foreach ($affiliations as $aff) {
    $orgId = $orgMap[$aff['slug']] ?? null;
    $docId = $docMap[$aff['doc']] ?? null;
    if ($orgId && $docId) {
        $st = $pdo->prepare("
            INSERT INTO organization_doctors (organization_id, doctor_id, is_head_physician, working_days, working_hours)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                is_head_physician = VALUES(is_head_physician),
                working_days = VALUES(working_days),
                working_hours = VALUES(working_hours)
        ");
        $st->execute([$orgId, $docId, $aff['is_head'], $aff['days'], $aff['hours']]);
    }
}
echo "Affiliations linked.\n";

// 4. Seed Verified Reviews for Organizations
$reviewsData = [
    [
        'slug' => 'payetakht-hospital',
        'rating' => 5,
        'comment' => 'سگ من تصادف کرده بود و ساعت ۳ بامداد رسوندیمش بیمارستان پایتخت. بخش اورژانس و دکتر شایان فوق‌العاده سریع عمل جراحی لگن رو انجام دادن و الان کاملاً سلامته. دست مریزاد به کادر دلسوزتون.',
        'user_name' => 'علیرضا حسینی'
    ],
    [
        'slug' => 'payetakht-hospital',
        'rating' => 5,
        'comment' => 'نظافت و استریل بودن بخش بستری گربه‌ها واقعاً در حد بیمارستان‌های انسانی بود. گزارش‌های دوره‌ای با ویدیو برام ارسال می‌شد که خیلی خیالم رو راحت کرد.',
        'user_name' => 'سحر ناصری'
    ],
    [
        'slug' => 'persian-pet-clinic',
        'rating' => 5,
        'comment' => 'برای عقیم‌سازی گربه‌ام به کلینیک پرشین مراجعه کردم. خانم دکتر مهرزاد با بیهوشی استنشاقی جراحی رو انجام دادن و بعد از ۳ ساعت کاملاً سرحال و بدون درد راه می‌رفت.',
        'user_name' => 'مهرداد پاکزاد'
    ],
    [
        'slug' => 'persian-pet-clinic',
        'rating' => 4,
        'comment' => 'کادر پذیرش بسیار خوش‌برخورد، سیستم نوبت‌دهی آنلاین بدون هیچ معطلی اجرا شد. پت‌شاپ دارویی هم هر چی نیاز داشتیم داشت.',
        'user_name' => 'فرناز ابراهیمی'
    ],
    [
        'slug' => 'shiraz-central-hospital',
        'rating' => 5,
        'comment' => 'بهترین و مجهزترین مرکز درمانی در کل استان فارس. سونوگرافی داپلر با دقت عالی انجام شد و داروها رو بلافاصله از داروخانه داخلی تحویل گرفتیم.',
        'user_name' => 'بابک شیرازی'
    ],
    [
        'slug' => 'caspian-exotic-clinic',
        'rating' => 5,
        'comment' => 'کاسکوی من مشکل شدید تنفسی داشت و هیچ کلینیکی قبولش نمی‌کرد. آقای دکتر رستمی با مهارت عالی اکسیژن‌تراپی و نبولایزر انجام دادن و نجاتش دادن.',
        'user_name' => 'مسعود رضوی'
    ],
    [
        'slug' => 'razi-vet-pharmacy',
        'rating' => 5,
        'comment' => 'داروی کاردیولوژی برای سگم پیدا نمی‌شد، داروخانه رازی بلافاصله برام ارسال کرد با پک یخ و زنجیره سرد کامل. قیمت‌ها هم کاملاً منصفانه و شرکتی بود.',
        'user_name' => 'زهرا کیانی'
    ]
];

// Ensure we have a user to associate reviews with
$userId = (int)$pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
if (!$userId) {
    $pdo->exec("INSERT INTO users (full_name, phone, role) VALUES ('کاربر آزمایشی آسنا', '09120000000', 'user')");
    $userId = (int)$pdo->lastInsertId();
}

foreach ($reviewsData as $r) {
    $orgId = $orgMap[$r['slug']] ?? null;
    if ($orgId) {
        $st = $pdo->prepare("
            INSERT INTO reviews (user_id, target_type, target_id, rating, comment, is_verified_buyer, status, created_at)
            VALUES (?, 'organization', ?, ?, ?, 1, 'approved', NOW() - INTERVAL FLOOR(RAND()*20) DAY)
        ");
        $st->execute([$userId, $orgId, $r['rating'], $r['comment']]);
    }
}
echo "Verified reviews seeded.\n";

echo "Seeding completed successfully!\n";
