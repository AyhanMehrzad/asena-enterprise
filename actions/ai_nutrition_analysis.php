<?php
/**
 * ASENA Enterprise - AI Clinical Veterinary Nutrition Analysis Engine
 * Generates tailored dietary assessment, breed metabolic evaluation, and clinical warnings
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rawInput = file_get_contents('php://input');
$inputData = [];
if (!empty($rawInput) && ($decoded = json_decode($rawInput, true))) {
    $inputData = $decoded;
} elseif (!empty($GLOBALS['TEST_INPUT']) && is_array($GLOBALS['TEST_INPUT'])) {
    $inputData = $GLOBALS['TEST_INPUT'];
} else {
    $inputData = $_POST;
}

$species = in_array($inputData['species'] ?? '', ['dog', 'cat']) ? $inputData['species'] : 'dog';
$race = trim((string)($inputData['race'] ?? $inputData['breed'] ?? 'سایر'));
$petName = trim((string)($inputData['pet_name'] ?? 'پت'));
$weightKg = (float)($inputData['weight_kg'] ?? 8.5);
$idealWeightKg = (float)($inputData['ideal_weight_kg'] ?? $weightKg);
$bcsScore = (int)($inputData['bcs_score'] ?? 5);
$dailyCalories = (int)($inputData['daily_calories'] ?? 550);
$kibbleGrams = (int)($inputData['kibble_grams'] ?? 145);
$waterMl = (int)($inputData['water_ml'] ?? 510);
$activity = trim((string)($inputData['activity'] ?? 'neutered'));
$stage = trim((string)($inputData['stage'] ?? 'adult'));

// Translations for prompts
$speciesFa = $species === 'dog' ? 'سگ' : 'گربه';
$stageFa = match ($stage) {
    'puppy' => ($species === 'dog' ? 'توله سگ (در حال رشد)' : 'بچه‌گربه (کیتن)'),
    'senior' => 'مسن و ارشد (بالای ۷ سال)',
    default => 'بالغ'
};
$activityFa = match ($activity) {
    'active' => 'بسیار پرتحرک و فعال',
    'diet' => 'کم‌تحرک / نیازمند مدیریت وزن و رژیم کالری‌کنترل',
    default => 'عقیم‌شده با تحرک آپارتمانی معمول'
};

$bcsFa = match (true) {
    $bcsScore <= 3 => "امتیاز $bcsScore/۹ (لاغر / کمبود وزن)",
    $bcsScore >= 8 => "امتیاز $bcsScore/۹ (چاقی مفرط بالینی)",
    $bcsScore >= 6 => "امتیاز $bcsScore/۹ (دارای اضافه‌وزن)",
    default => "امتیاز $bcsScore/۹ (ایده‌آل و متناسب)"
};

// 1. Attempt Live AI Call (AvalAI / Gemini)
$aiResponseText = null;
$aiSource = null;

$avalai_api_key = getenv('AVALAI_API_KEY') ?: 'aa-OYnaadEq49DVrgUetouRgFRhmNjSuS7ZknCL5FdEQqHAehsl';
$avalai_model = getenv('AVALAI_MODEL_CHAT') ?: 'gemini-3.5-flash-lite';
$avalai_url = 'https://api.avalai.ir/v1/chat/completions';

if (!empty($avalai_api_key)) {
    $systemPrompt = "شما یک متخصص تغذیه بالینی دامپزشکی (Veterinary Clinical Nutritionist) در پلتفرم تخصصی سلامت حیوانات خانگی آسنا هستید. وظیفه شما تحلیل علمی، بالینی، دقیق و نژادمحور رژیم غذایی پت بر اساس استانداردهای بین‌المللی WSAVA و FEDIAF است. لحن پاسخ باید پزشکی، علمی، دلسوزانه و به زبان فارسی شیوا با ساختار کاملاً تفکیک‌شده باشد.";
    $userPrompt = "تحلیل جامع تغذیه و هشدار بالینی پت:
- نام پت: {$petName}
- گونه: {$speciesFa}
- نژاد پت: {$race}
- وزن فعلی: {$weightKg} کیلوگرم (وزن ایده‌آل هدف: {$idealWeightKg} کیلوگرم)
- مرحله سنی: {$stageFa}
- سطح فعالیت: {$activityFa}
- شاخص وضعیت بدنی (BCS): {$bcsFa}
- انرژی متابولیک محاسبه‌شده (MER): {$dailyCalories} کیلوکالری در روز
- غذای خشک استاندارد: {$kibbleGrams} گرم در روز
- حداقل آب مورد نیاز: {$waterMl} میلی‌لیتر

لطفاً به صورت ساختاریافته در بخش‌های زیر تحلیل بالینی ارائه دهید:
۱. ارزیابی بیومکانیک و متابولیسم نژاد {$race}
۲. توصیه‌های کلیدی فرمولاسیون جیره غذایی و نوع کیبل
۳. هشدارهای مراقبتی و منع مصرف ویژه این نژاد و وضعیت وزنی
۴. مکمل‌های فارماکولوژیک و تشویقی‌های بالینی مجاز";

    try {
        $ch = curl_init($avalai_url);
        $payload = [
            'model' => $avalai_model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'max_tokens' => 700,
            'temperature' => 0.4
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $avalai_api_key
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $resp) {
            $data = json_decode($resp, true);
            if (!empty($data['choices'][0]['message']['content'])) {
                $aiResponseText = trim($data['choices'][0]['message']['content']);
                $aiSource = 'avalai_' . $avalai_model;
            }
        }
    } catch (Throwable $e) {
        error_log("AvalAI Nutrition Analysis error: " . $e->getMessage());
    }
}

// 2. High-Precision Clinical Veterinary Knowledge Base (Always Guaranteed & Instant Fallback)
function get_clinical_breed_profile(string $species, string $race, float $weight, int $bcs, string $stage, string $activity): array {
    $raceLower = mb_strtolower($race);
    
    // Default fallback profiles
    $profile = [
        'title' => $race,
        'category' => 'استاندارد فیزیولوژیک',
        'kibble_type' => 'کیبل استاندارد ارگونومیک با پروتئین باکیفیت و بالانس فسفر و کلسیم',
        'metabolic_note' => "نژاد {$race} دارای ساختار بدنی منحصر‌به‌فرد است و نیازمند رعایت جیره متوازن و پایش فصلی شاخص توده عضلانی می‌باشد.",
        'recommendations' => [
            "توزیع جیره روزانه به ۲ وعده با فواصل منظم جهت تثبیت ترشح انسولین و جلوگیری از پرخوری.",
            "استفاده از پروتئین حیوانی هیدرولیزشده با قابلیت هضم بالای ۹۰٪ جهت حفظ بافت عضلانی بدون فشار بر کبد.",
            "تأمین دائم آب تصفیه شده (حداقل به میزان محاسبه شده) در ظروف سرامیکی یا استیل ضدزنگ."
        ],
        'alerts' => [
            "پرهیز قاطع از خوراکی‌های حاوی پیاز، سیر، کاکائو، کشمش، زایلیتول و استخوان‌های پخته مرغ.",
            "محدودیت جدی تشویقی‌ها به زیر ۱۰ درصد کالری کل روزانه برای جلوگیری از برهم خوردن تعادل الکترولیت‌ها."
        ],
        'supplements' => [
            "اسیدهای چرب امگا ۳ و ۶ (روغن ماهی سالمون خالص) جهت تقویت سد دفاعی پوست و درخشندگی پوشش.",
            "پروبیوتیک‌های تخصصی با پوشش روده‌ای جهت بهبود میکرو فلورای گوارشی."
        ]
    ];

    if ($species === 'dog') {
        if (str_contains($raceLower, 'ژرمن') || str_contains($raceLower, 'german')) {
            $profile['category'] = 'نژاد کار و بزرگ‌جثه (Large Breed)';
            $profile['kibble_type'] = 'کیبل درشت با بافت کرانچی متراکم جهت کاهش سرعت بلع و تحریک جویدن';
            $profile['metabolic_note'] = "سگ‌های نژاد ژرمن شپرد به دلیل فنوتیپ اسکلتی شیب‌دار، به طور ژنتیکی مستعد دیسپلازی مفاصل ران و زانو (Hip/Elbow Dysplasia) و نارسایی آنزیمی پانکراس (EPI) هستند. رژیم غذایی باید از تراکم انرژی کنترل‌شده برخوردار باشد تا از اضافه وزن روی مفاصل پیشگیری شود.";
            $profile['recommendations'] = [
                "تغذیه در دو وعده با ظرف غذای ارتفاع‌دار ملایم جهت کاهش خطر نفخ و پیچ‌خوردگی حاد معده (GDV).",
                "پروتئین خالص مرغوب (حداقل ۲۸٪) به همراه ال-کارنیتین برای حفظ حجم عضلات فعال بدون تجمع چربی.",
                "پرهیز از تحرک شدید و دویدن تا ۴۵ دقیقه پس از صرف غذا."
            ];
            $profile['alerts'] = [
                "هشدار دیسپلازی مفصلی: هرگونه افزایش وزن بیش از وزن ایده‌آل، استرس فشاری را بر مفصل هیپ تا ۴ برابر تشدید می‌کند.",
                "حساسیت گوارشی: تغییر برند یا نوع غذای ژرمن شپرد باید الزاماً طبق پروتکل ۷ روزه تدریجی انجام شود."
            ];
            $profile['supplements'] = [
                "مکمل پیشرفته کندرویتین + گلوکوزامین + MSM جهت ترمیم و محافظت از غضروف مفاصل لگن.",
                "روغن امگا ۳ دریایی غنی از EPA و DHA برای کاهش التهابات مفصلی و حمایت از پوست."
            ];
        } elseif (str_contains($raceLower, 'شیتزو') || str_contains($raceLower, 'shih')) {
            $profile['category'] = 'نژاد براکی‌سفالیک مینیاتوری (Toy Brachycephalic)';
            $profile['kibble_type'] = 'کیبل ارگونومیک هلالی کوچک ویژه آرواره پوزه‌کوتاه جهت تسهیل برداشت با زبان';
            $profile['metabolic_note'] = "نژاد شیتزو به دلیل ساختار فک براکی‌سفالیک (پوزه‌کوتاه)، مستعد مشکلات دندانی، تجمع پلاک و تنفس دشوار است. همچنین استعداد ابتلا به سنگ‌های کلیوی و مثانه (اگزالات کلسیم) و حساسیت قرنیه چشم نیازمند جیره با فسفر کنترل‌شده و ویتامین A کافی است.";
            $profile['recommendations'] = [
                "استفاده از غذای خشک حاوی کلاتورهای کلسیم (پلی‌فسفات سدیم) جهت جلوگیری از تشکیل پلاک دندانی.",
                "شستشو و تمیز کردن شیارهای پوستی اطراف بینی و چشم پس از هر وعده غذایی.",
                "تقسیم جیره روزانه به ۲ تا ۳ وعده کوچک برای جلوگیری از افت قند خون مینیاتوری."
            ];
            $profile['alerts'] = [
                "به دلیل دهان کوتاه و حرارت‌گیری بالا در فصل گرم، آب تازه خنک باید در چند نقطه در دسترس باشد.",
                "استعداد تشکیل سنگ ادراری: تشویقی‌های شور یا پروتئین‌های فرآوری شده صنعتی اکیداً ممنوع است."
            ];
            $profile['supplements'] = [
                "لوتئین و ویتامین E و A جهت محافظت از سلامت بینایی و قرنیه چشم.",
                "مکمل‌های بایودرمال امگا برای کاهش شوره و آلرژی پوستی زیر موهای پرپشت شیتزو."
            ];
        } elseif (str_contains($raceLower, 'پامرانین') || str_contains($raceLower, 'pomeranian')) {
            $profile['category'] = 'نژاد توی با متابولیسم بسیار بالا (High-Metabolism Toy)';
            $profile['kibble_type'] = 'کیبل بسیار ریز پرکالری با قابلیت هضم سریع و طعم‌پذیری بالا';
            $profile['metabolic_note'] = "پامرانین‌ها علی‌رغم جثه ظریف، نرخ سوخت‌وساز استراحتی (RMR) فوق‌العاده بالایی به ازای هر کیلوگرم وزن دارند. آنها مستعد فروپاشی نای (Tracheal Collapse)، دررفتگی کشکک زانو (Patellar Luxation) و بیماری آلوپسی X (ریزش موی هورمونی) هستند.";
            $profile['recommendations'] = [
                "تراکم کالری بالا در حجم کم تا انرژی مورد نیاز بدون نیاز به مصرف حجم زیاد غذا تامین گردد.",
                "تغذیه ترجیحاً در ۳ وعده ثابت برای تثبیت گلیکوژن کبد و ممانعت از شوک هایپوگلیسمی.",
                "استفاده از دیسپنسر یا ظروف آرام‌خوار در صورت بلع حریصانه دانه‌ها."
            ];
            $profile['alerts'] = [
                "هشدار نای: استفاده از قلاده گردنی هنگام پیاده‌روی ممنوع؛ حتماً از قلاده بدنی (هارنس) استفاده فرمایید.",
                "کنترل جرم دندانی: دندان‌های شیری افتاده نشده باید توسط دندانپزشک دامپزشک چک شود."
            ];
            $profile['supplements'] = [
                "بیوتین + زینک شلاته + متیونین برای تقویت فولیکول‌های مو و پیشگیری از ریزش پوشش دولایه.",
                "کلاژن هیدرولیزشده برای انعطاف‌پذیری غضروف‌های نای و مفاصل دست و پا."
            ];
        } elseif (str_contains($raceLower, 'هاسکی') || str_contains($raceLower, 'husky') || str_contains($raceLower, 'مالاموت')) {
            $profile['category'] = 'نژاد قطبی با راندمان متابولیک منحصر (Nordic High-Efficiency)';
            $profile['kibble_type'] = 'کیبل با چربی مفید متعادل و غنی از اسیدهای چرب زنجیره متوسط (MCT)';
            $profile['metabolic_note'] = "سیبرین هاسکی دارای راندمان متابولیکی استثنایی است و بر خلاف ظاهر پرابهت، غذای کمتری نسبت به سایر نژادهای هم‌وزن مصرف می‌کند. هاسکی‌ها در برابر کمبود زینک (Dermatosis Responsive to Zinc) و عدم تحمل لاکتوز بسیار حساس هستند.";
            $profile['recommendations'] = [
                "پرهیز از خوراندن اجباری: هاسکی‌ها خودتنظیم‌ترین نژاد در مصرف غذا هستند و در روزهای کم‌تحرک غذای کمتری می‌خورند.",
                "جیره غنی از گوشت بره یا ماهی سالمون به همراه فیبر پری‌بیوتیک (FOS/MOS) برای پایداری مدفوع."
            ];
            $profile['alerts'] = [
                "حساسیت به حرارت: در محیط‌های گرم آپارتمانی ایران، مصرف آب و کالری کاهش می‌یابد؛ به هیچ وجه غذای چرب سنگین خورانده نشود.",
                "کمبود زینک: ایجاد پوسته‌پوسته دور بینی و چشم نیازمند سنجش فوری سطح روی سرم خون است."
            ];
            $profile['supplements'] = [
                "زینک گلوکونات دارویی به همراه بیوتین جهت شادابی پوشش و سلامت بالشتک‌های کف پا.",
                "آنتی‌اکسیدان‌های طبیعی (عصاره رزماری و ویتامین C) برای حمایت سیستم ایمنی."
            ];
        } elseif (str_contains($raceLower, 'پاگ') || str_contains($raceLower, 'بولداگ') || str_contains($raceLower, 'bulldog') || str_contains($raceLower, 'pug')) {
            $profile['category'] = 'براکی‌سفالیک با ریسک بالای چاقی (High Obesity Risk Brachycephalic)';
            $profile['kibble_type'] = 'کیبل حجیم با کالری کنترل‌شده و فیبر سیرکننده ویژه سهولت بلع بدون خفگی';
            $profile['metabolic_note'] = "پاگ‌ها و بولداگ‌ها به سندرم انسداد راه هوایی براکی‌سفالیک (BOAS) مبتلا هستند. هر ۱۰۰ گرم اضافه وزن مستقیماً فشار تنفسی و ضربان قلب آنها را افزایش داده و احتمال گرمازدگی کشنده را بالا می‌برد.";
            $profile['recommendations'] = [
                "پایبندی سختگیرانه به سقف کالری MER روزانه و عدم تسلیم شدن در برابر گدایی غذا.",
                "استفاده از سبزیجات کم‌کالری پخته (مانند کدو حلوایی یا هویج بخارپز) به عنوان تشویقی بدون چربی."
            ];
            $profile['alerts'] = [
                "خطر خفگی و گرمازدگی: آب و غذا در زمان اوج گرما داده نشود؛ پس از هیجان یا تنفس تند غذا ندهید.",
                "پایش روزانه چین‌های پوستی روی بینی برای جلوگیری از پیودرما و عفونت قارچی."
            ];
            $profile['supplements'] = [
                "ال-کارنیتین جهت سوزاندن چربی‌های ذخیره‌ای احشایی و ارتقای توان عضلات قلب.",
                "اسیدهای چرب امگا برای کاهش التهاب مجاری تنفسی فوقانی."
            ];
        } elseif (str_contains($raceLower, 'رتریور') || str_contains($raceLower, 'retriever') || str_contains($raceLower, 'گلدن') || str_contains($raceLower, 'لابرادور')) {
            $profile['category'] = 'نژاد خوش‌اشتها و مستعد اضافه وزن (High Appetite Sporting)';
            $profile['kibble_type'] = 'کیبل با فیبر نامحلول بالا جهت احساس سیری طولانی‌مدت و محافظت مفاصل';
            $profile['metabolic_note'] = "گلدن و لابرادور رتریور حامل جهش در ژن POMC هستند که باعث می‌شود احساس سیری مغزی را دیرتر درک کنند! استعداد چاقی بالا، در کنار حساسیت‌های پوستی آتوپیک و خطر تومورهای هم‌آنجیوسارکوما، نیازمند رژیم با آنتی‌اکسیدان غنی است.";
            $profile['recommendations'] = [
                "اندازه‌گیری گرم دقیق غذای خشک با ترازوی دیجیتال آشپزخانه نه با پیمانه‌های چشمی.",
                "استفاده از پازل‌فیدرها و اسباب‌بازی‌های هوشمند تقویت هوش برای تخلیه انرژی ذهنی حین غذا خوردن."
            ];
            $profile['alerts'] = [
                "چاقی عامل شماره یک کوتاهی عمر در رتریورهاست؛ خط گودی کمر پت باید همیشه از زاویه بالا واضح باشد."
            ];
            $profile['supplements'] = [
                "گلوکوزامین + کندرویتین فارماکوپه اروپا برای محافظت دائم از تاندون‌ها و مفاصل شانه و لگن.",
                "روغن کریل یا سالمون جهت پیشگیری از اگزماهای فصلی و خارش پوست."
            ];
        } elseif (str_contains($raceLower, 'سرابی') || str_contains($raceLower, 'کانگال') || str_contains($raceLower, 'ماستیف')) {
            $profile['category'] = 'نژاد غول‌پیکر بومی (Giant Livestock Guardian)';
            $profile['kibble_type'] = 'کیبل بسیار بزرگ (Maxi/Giant) با کلسیم و فسفر فوق دقیق';
            $profile['metabolic_note'] = "سگ‌های بومی سرابی و نژادهای غول‌پیکر رشد اسکلتی بسیار سریع و وزن‌گیری سنگینی دارند. در دوران رشد، کلسیم بیش از حد یا کالری مازاد می‌تواند سبب بیماری‌های بدشکلی استخوان (OCD و HOD) شود.";
            $profile['recommendations'] = [
                "تثبیت رشد تدریجی؛ به هیچ وجه نباید برای وزن‌گیری سریع به سگ غذای مازاد داده شود.",
                "تغذیه در ۲ الی ۳ وعده و استراحت مطلق قبل و بعد از مصرف خوراک."
            ];
            $profile['alerts'] = [
                "خطر چرخش و اتساع حاد معده (GDV): مصرف آب فراوان بلافاصله پس از بلع غذای خشک اکیداً ممنوع است."
            ];
            $profile['supplements'] = [
                "مکمل‌های هیالورونیک اسید و کلاژن نوع ۲ برای استقامت کپسول مفاصل نژاد سنگین‌وزن."
            ];
        }
    } else {
        // Cat breeds
        if (str_contains($raceLower, 'پرشین') || str_contains($raceLower, 'persian') || str_contains($raceLower, 'فلت') || str_contains($raceLower, 'هیمالین')) {
            $profile['category'] = 'نژاد مو بلند و پوزه‌کوتاه ایرانی (Longhair Brachycephalic)';
            $profile['kibble_type'] = 'کیبل بادامی‌شکل ارگونومیک جهت سهولت چنگ‌زدن با زبان فک براکی‌سفالیک';
            $profile['metabolic_note'] = "گربه‌های اصیل پرشین به دلیل پوزه فلت، فرم فک کوتاه و موهای ابریشمی بسیار متراکم، به شدت مستعد بلع مو و ایجاد گلوله‌های مویی (Hairball) در معده، انسداد مجاری اشکی و بیماری ژنتیکی کلیه پلی‌کیستیک (PKD) هستند.";
            $profile['recommendations'] = [
                "فرمولاسیون غنی از پسیلیوم (اسفرزه) و فیبرهای نامحلول برای عبور طبیعی موها از روده بدون استفراغ.",
                "سطح بسیار دقیق منیزیم و فسفر و حفظ اسیدیته ادرار (pH 6.2 - 6.5) جهت جلوگیری از سنگ استروویت و اگزالات.",
                "ترکیب روزانه غذای تر (پوچ یا کنسرو رطوبت‌بالا) در کنار غذای خشک جهت شستشوی دائمی کلیه‌ها."
            ];
            $profile['alerts'] = [
                "کلیه‌های حساس پرشین: تشویقی‌های نامرغوب و غذای حاوی نمک می‌تواند بیماری تحت بالینی PKD را فعال کند.",
                "انسداد مویی روده: در صورت مشاهده تلاش‌های مکرر برای استفراغ خشک، فوراً به کلینیک مراجعه شود."
            ];
            $profile['supplements'] = [
                "خمیر مالت تخصصی پرشین حاوی فیبر گیاهی و مالت جو مرغوب (روزانه ۲ تا ۳ سانتی‌متر).",
                "امگا ۳ با منشا دریایی با غلظت بالای EPA برای نرمی و ابریشمی ماندن موهای زیرین."
            ];
        } elseif (str_contains($raceLower, 'اسکاتیش') || str_contains($raceLower, 'scottish') || str_contains($raceLower, 'بریتیش') || str_contains($raceLower, 'british')) {
            $profile['category'] = 'نژاد با جهش غضروفی و اسکلتی (Osteochondrodysplasia Prone)';
            $profile['kibble_type'] = 'کیبل دایره‌ای با بافت فشرده جهت تقویت فک عضلانی بریتیش و اسکاتیش';
            $profile['metabolic_note'] = "نژاد اسکاتیش فولد به علت ژن تاخوردگی گوش، دارای نقص سیستمیک غضروفی (OCD) در سراسر بدن به ویژه در مفاصل دم، مچ دست و پا است. نژاد بریتیش نیز جثه عضلانی فشرده دارد و کم‌تحرکی آن در آپارتمان سریعاً به اضافه وزن منجر می‌شود.";
            $profile['recommendations'] = [
                "جلوگیری وسواس‌گونه از اضافه وزن؛ حتی ۲۰۰ گرم وزن اضافی درد مفاصل اسکاتیش را مضاعف می‌کند.",
                "میزان بالای پروتئین خالص مرغوب (حداقل ۳۴٪) با کربوهیدرات پایین برای حفظ فیبرهای عضلانی بدون چربی احشایی."
            ];
            $profile['alerts'] = [
                "سفتی انتهای دم یا بی‌میلی به پریدن نشانه قطعی دردهای غضروفی است و نیازمند مداخله دارویی است.",
                "پرهیز از مکمل کلسیم خالص به اسکاتیش؛ کلسیم اضافی رسوب غضروفی را تشدید می‌کند."
            ];
            $profile['supplements'] = [
                "کندرویتین سولفات، گلوکوزامین و غشای طبیعی پوسته تخم‌مرغ (NEM) جهت روان‌سازی مفاصل.",
                "عصاره صدف لبه سبز نیوزیلندی (Green Lipped Mussel) به عنوان قوی‌ترین ضدالتهاب طبیعی مفاصل گربه."
            ];
        } elseif (str_contains($raceLower, 'dsh') || str_contains($raceLower, 'خیابانی') || str_contains($raceLower, 'موکوتاه') || str_contains($raceLower, 'بومی') || str_contains($raceLower, 'میکس')) {
            $profile['category'] = 'نژاد بومی موکوتاه با تنوع ژنتیکی بالا (Domestic Shorthair)';
            $profile['kibble_type'] = 'کیبل ترد دندان‌پزشکی با پروتئین مرغوب متوازن';
            $profile['metabolic_note'] = "گربه‌های DSH از بالاترین تنوع ژنتیکی و سیستم ایمنی مستحکم برخوردارند. بزرگ‌ترین چالش سلامت آنها در زندگی خانگی، کاهش فعالیت بدنی پس از عقیم‌سازی و بیماری‌های دستگاه ادراری تحتانی (FLUTD) ناشی از کم‌آبی مزمن است.";
            $profile['recommendations'] = [
                "تقویت مصرف آب با فواره آب گربه (آب در جریان) به منظور ممانعت از ایجاد رسوبات مثانه.",
                "استفاده از غذای خشک فرموله شده مخصوص گربه‌های عقیم‌شده (Sterilised) با چربی کنترل‌شده ۱۲٪."
            ];
            $profile['alerts'] = [
                "هرگونه زور زدن در جعبه خاک یا قطره‌قطره ادرار کردن اورژانس پزشکی انسداد مجاری ادراری است."
            ];
            $profile['supplements'] = [
                "تائورین خالص جهت تقویت بینایی شبانه و عملکرد ماهیچه قلب.",
                "تشویقی‌های غنی از ال-تریتوفان در صورت بروز استرس‌های خانگی."
            ];
        }
    }

    return $profile;
}

$breedProfile = get_clinical_breed_profile($species, $race, $weightKg, $bcsScore, $stage, $activity);

// Build structured response
$response = [
    'success' => true,
    'source' => $aiSource ?: 'asena_clinical_knowledge_engine',
    'pet_name' => $petName,
    'species' => $species,
    'species_fa' => $speciesFa,
    'race' => $race,
    'race_title' => $breedProfile['title'],
    'race_category' => $breedProfile['category'],
    'kibble_type' => $breedProfile['kibble_type'],
    'metabolic_analysis' => $aiResponseText ?: $breedProfile['metabolic_note'],
    'clinical_recommendations' => $breedProfile['recommendations'],
    'health_alerts' => $breedProfile['alerts'],
    'recommended_supplements' => $breedProfile['supplements'],
    'metrics' => [
        'weight_kg' => $weightKg,
        'ideal_weight_kg' => $idealWeightKg,
        'daily_calories' => $dailyCalories,
        'kibble_grams' => $kibbleGrams,
        'water_ml' => $waterMl,
        'bcs_score' => $bcsScore,
        'stage' => $stage,
        'activity' => $activity
    ],
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
