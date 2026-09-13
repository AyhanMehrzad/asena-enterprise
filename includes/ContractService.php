<?php
/**
 * ASENA Enterprise - Electronic Contract & Multi-Role Compliance Service
 * Standardized under Iran Electronic Commerce Law (قانون تجارت الکترونیک، مواد ۶ و ۱۲)
 * Version: 2.0.0
 */

class ContractService
{
    public const CURRENT_VERSION = 'v2.0-2026';

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            $this->pdo = $pdo;
        }
    }

    /**
     * Normalize incoming user role for contract classification.
     */
    public static function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));
        if (in_array($role, ['organization', 'organization_manager', 'clinic', 'hospital'], true)) {
            return 'organization';
        }
        if (in_array($role, ['doctor', 'vet', 'specialist'], true)) {
            return 'doctor';
        }
        if (in_array($role, ['pharmacist', 'pharmacy'], true)) {
            return 'pharmacist';
        }
        if (in_array($role, ['seller', 'supplier', 'vendor'], true)) {
            return 'seller';
        }
        return 'user'; // Consumer / Pet Owner
    }

    /**
     * Check whether a user has accepted the latest contract version for their role.
     */
    public function hasAcceptedCurrentContract(int $userId, string $role): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $normRole = self::normalizeRole($role);

        // 1. Check users table cache
        $stmt = $this->pdo->prepare("SELECT contract_accepted_version FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $cachedVer = $stmt->fetchColumn();

        if ($cachedVer === self::CURRENT_VERSION) {
            return true;
        }

        // 2. Check contract_acceptances ledger
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM contract_acceptances 
                WHERE user_id = ? AND role = ? AND contract_version = ?
                LIMIT 1
            ");
            $stmt->execute([$userId, $normRole, self::CURRENT_VERSION]);
            $accepted = (bool)$stmt->fetchColumn();

            if ($accepted) {
                // Synchronize users table
                $this->pdo->prepare("
                    UPDATE users 
                    SET contract_accepted_version = ?, contract_accepted_at = NOW() 
                    WHERE id = ?
                ")->execute([self::CURRENT_VERSION, $userId]);
            }

            return $accepted;
        } catch (Throwable $e) {
            // Table might not be migrated yet
            return false;
        }
    }

    /**
     * Electronically sign and record contract acceptance.
     */
    public function recordAcceptance(int $userId, string $role, string $ip, string $userAgent): array
    {
        $normRole = self::normalizeRole($role);
        $contract = self::getContractData($normRole);
        $timestamp = date('Y-m-d H:i:s');

        // Cryptographic signature hash (HMAC-SHA256)
        $signaturePayload = implode('|', [
            $userId,
            $normRole,
            self::CURRENT_VERSION,
            $contract['hash'],
            $ip,
            $timestamp,
            'ASENA_E_SIGN_SECRET_SALT_2026'
        ]);
        $signatureHash = hash('sha256', $signaturePayload);

        // Insert into contract_acceptances
        $stmt = $this->pdo->prepare("
            INSERT INTO contract_acceptances 
                (user_id, role, contract_version, contract_title, signature_hash, ip_address, user_agent, accepted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $normRole,
            self::CURRENT_VERSION,
            $contract['title'],
            $signatureHash,
            $ip,
            substr($userAgent, 0, 500),
            $timestamp
        ]);

        // Update users table
        $upd = $this->pdo->prepare("
            UPDATE users 
            SET contract_accepted_version = ?, contract_accepted_at = ? 
            WHERE id = ?
        ");
        $upd->execute([self::CURRENT_VERSION, $timestamp, $userId]);

        // Security Audit Log
        if (class_exists('SecurityAuditService')) {
            $audit = new SecurityAuditService($this->pdo);
            $audit->logEvent(
                'contract_signed',
                'info',
                $userId,
                "User electronically signed contract {$contract['title']} [{$signatureHash}]"
            );
        }

        return [
            'success' => true,
            'contract_version' => self::CURRENT_VERSION,
            'signature_hash' => $signatureHash,
            'timestamp' => $timestamp
        ];
    }

    /**
     * Get contract text and mutual-benefit clauses for a given role.
     */
    public static function getContractData(string $role): array
    {
        $role = self::normalizeRole($role);

        $contracts = [
            'user' => [
                'role_fa' => 'سرپرستان پت و مشتریان عمومی',
                'title' => 'قرارداد خدمات کاربری، حریم خصوصی و خرید ایمن آسنا',
                'summary' => 'توافق‌نامه شفاف میان سرپرست پت و پلتفرم آسنا جهت تضمین اصالت سفارشات، ویزیت آنلاین، پرونده سلامت حیوان و امانت‌داری مالی ۷ روزه وجه.',
                'win_win' => [
                    'profit_for_user' => [
                        'تضمین ۱۰۰٪ اصالت کالاها و داروهای دارای مجوز معتبر دامپزشکی',
                        'مهلت تست و بازرسی ۷ روزه طبق ماده ۳۷ قانون تجارت الکترونیک (وجه نزد آسنا امانت است)',
                        'امکان استفاده از تخفیف دائمی ۱۵٪ در سرویس اشتراک تحویل خودکار (Autoship)',
                        'حفاظت کامل از محرمانگی پرونده سلامت و مشاوره‌های دامپزشکی'
                    ],
                    'profit_for_platform' => [
                        'ایجاد بستر شفاف، پیشگیری از کلاهبرداری و اختلافات تجاری',
                        'سلب مسئولیت از اقدامات خودسرانه پزشکی خارج از نظر متخصصین تاییدشده',
                        'کسب وفاداری بلندمدت مشتریان و گسترش شبکه سلامت حیوانات خانگی'
                    ]
                ],
                'articles' => [
                    [
                        'num' => '۱',
                        'title' => 'موضوع قرارداد و تعهدات بنیادین',
                        'content' => 'موضوع این قرارداد، ارائه خدمات نوبت‌دهی آنلاین مراکز دامپزشکی، مشاوره تله‌هلث، فروش ملزومات استاندارد حیوانات خانگی و تحویل ملزومات دارویی مجاز تحت نظارت مسئولین فنی است. کاربر متعهد است اطلاعات دقیق هویتی و حیوان خانگی خود را ثبت نماید.'
                    ],
                    [
                        'num' => '۲',
                        'title' => 'امانت‌داری مالی (Escrow) و بازگشت کالا',
                        'content' => 'کلیه وجوه پرداختی کاربر در خریدهای مارکت‌پلیس، تا زمان اعلام وصول توسط شرکت ملی پست یا پیک درون‌شهری و پایان مهلت ۷ روزه بازرسی در حساب واسط امانی آسنا نگهداری می‌شود. در صورت عدم تطابق کالا یا آسیب‌دیدگی در حمل، وجه بلافاصله مسترد خواهد شد.'
                    ],
                    [
                        'num' => '۳',
                        'title' => 'شرایط اشتراک دوره‌ای (Autoship)',
                        'content' => 'با فعال‌سازی اتوشیپ، ارسال اقلام بر اساس بازه انتخابی تمدید می‌گردد. کاربر مجاز است تا ۴۸ ساعت قبل از موعد ارسال، بدون جریمه اقدام به تعویق یا لغو اشتراک نماید. آسنا تضمین می‌نماید تنها تأمین‌کنندگانی که حداقل ۳ ماه ذخیره انبار دارند به عنوان گزینه اتوشیپ پیشنهاد گردند.'
                    ],
                    [
                        'num' => '۴',
                        'title' => 'مشاوره آنلاین و تریاژ دامپزشکی',
                        'content' => 'مشاوره‌های آنلاین راهنمای تریاژ اولیه است. کاربر می‌پذیرد در موارد اورژانسی شدید (خونریزی حاد، انسداد تنفسی و تشنج)، حیوان را بدون اتلاف وقت به نزدیک‌ترین بیمارستان شبانه‌روزی منتقل نماید.'
                    ]
                ]
            ],

            'doctor' => [
                'role_fa' => 'پزشکان و متخصصین دامپزشک',
                'title' => 'قرارداد همکاری تخصصی، نوبت‌دهی هوشمند و تله‌مدیسین دامپزشکی',
                'summary' => 'توافق‌نامه برد-برد میان پزشک متخصص و پلتفرم آسنا جهت گسترش مراجعین، نظم‌بخشی به نوبت‌ها، تضمین واریز حق‌الویزیت و رعایت آیین‌نامه انتظامی نظام دامپزشکی.',
                'win_win' => [
                    'profit_for_doctor' => [
                        'دسترسی به بزرگ‌ترین شبکه متمرکز سرپرستان پت و افزایش مراجعین هدفمند',
                        'تضمین دریافت ۱۰۰٪ حق‌الویزیت آنلاین و حضوری (جلوگیری از عدم حضور بدون پرداخت مراجع)',
                        'پرونده بالینی دیجیتال و ابزار تجویز نسخه آنلاین متصل به انبار داروخانه‌ها',
                        'سیستم مدیریت شیفت و مرخصی جهت جلوگیری از تداخل نوبت‌های شخصی و سازمانی'
                    ],
                    'profit_for_platform' => [
                        'ارتقای اعتبار علمی و بالینی شبکه با حضور نخبگان نظام دامپزشکی',
                        'دریافت کارمزد استاندارد پلتفرم جهت بازاریابی، نگهداری زیرساخت و پشتیبانی مراجعین',
                        'تثبیت جایگاه آسنا به عنوان مرجع شماره یک سلامت حیوانات در کشور'
                    ]
                ],
                'articles' => [
                    [
                        'num' => '۱',
                        'title' => 'صلاحیت حرفه‌ای و پروانه اشتغال',
                        'content' => 'پزشک تأیید می‌نماید دارای دانشنامه رسمی دکتری دامپزشکی و شماره عضویت معتبر در سازمان نظام دامپزشکی ج.ا.ایران بوده و مسئولیت کامل تشخیص‌ها و توصیه‌های بالینی بر عهده ایشان است.'
                    ],
                    [
                        'num' => '۲',
                        'title' => 'تعهد حضور در شیفت و زمان پاسخگویی (SLA)',
                        'content' => 'پزشک متعهد است در بازه‌های زمانی اعلام‌شده در تقویم نوبت‌دهی آنلاین یا محل کلینیک حضور داشته باشد. در مشاوره‌های متنی، پاسخگویی اولیه حداکثر ظرف ۴۵ دقیقه کاری از زمان ثبت نوبت الزامی است.'
                    ],
                    [
                        'num' => '۳',
                        'title' => 'کارمزد و تسویه حساب مالی',
                        'content' => 'کارمزد پلتفرم از هر ویزیت آنلاین یا حضوری معادل ۱۵٪ است. مابقی خالص دریافتی (۸۵٪) بلافاصله پس از اتمام جلسه در کیف‌پول پزشک منظور شده و در سیکل هفتگی پایا به شماره شبای ثبت‌شده واریز می‌گردد.'
                    ],
                    [
                        'num' => '۴',
                        'title' => 'محرمانگی اسرار پزشکی و اخلاق حرفه‌ای',
                        'content' => 'پزشک متعهد به حفظ اسرار هویتی مراجعین و رعایت موازین و شئون حرفه‌ای نظام دامپزشکی بوده و از تجویز داروهای غیرمجاز یا هدایت بیمار به کانال‌های غیرشفاف خودداری می‌نماید.'
                    ]
                ]
            ],

            'pharmacist' => [
                'role_fa' => 'داروخانه‌ها و دکترهای داروساز',
                'title' => 'قرارداد تأمین اقلام دارویی، حفظ زنجیره سرد و بررسی نسخه دامپزشکی',
                'summary' => 'توافق‌نامه راهبردی میان داروخانه دارای مجوز و پلتفرم آسنا جهت تأمین پایدار داروهای تخصصی، رعایت ضوابط سازمان غذا و دارو و تسویه آنی سفارشات.',
                'win_win' => [
                    'profit_for_pharmacist' => [
                        'فروش سراسری اقلام دارویی، مکمل‌ها و بهداشتی به مراجعین نیازمند نسخه و اشتراک‌های دوره‌ای',
                        'تضمین دریافت وجه اقلام با سیستم امانت‌داری مالی پایا بدون ریسک چک یا بدحسابی',
                        'پنل هوشمند ممیزی نسخه دیجیتال (BPMS) با امکان تأیید یا رد با درج نظر کارشناسی',
                        'کاهش خواب سرمایه انبار از طریق سیستم پیش‌بینی تقاضای اتوشیپ'
                    ],
                    'profit_for_platform' => [
                        'تأمین قانونی و تحت نظارت داروهای حیوانات با رعایت کامل موازین سازمان غذا و دارو',
                        'تضمین رضایت کاربران از دریافت به موقع داروهای نایاب و حساس',
                        'حفظ پایداری لجستیک زنجیره سرد با داروخانه‌های مجهز'
                    ]
                ],
                'articles' => [
                    [
                        'num' => '۱',
                        'title' => 'مجوزهای قانونی و نظارت داروساز مسئول فنی',
                        'content' => 'داروخانه متعهد است کلیه اقلام دارویی را صرفاً با برچسب اصالت (TTAC) یا مجوزهای سازمان غذا و دارو و تحت نظارت مستقیم مسئول فنی داروساز عرضه نماید. عرضه هرگونه داروی قاچاق یا تاریخ‌گذشته مشمول فسخ آنی و معرفی به مراجع قانونی است.'
                    ],
                    [
                        'num' => '۲',
                        'title' => 'الزام ممیزی نسخه برای داروهای تجویزی (Rx)',
                        'content' => 'اقلام دارویی که نیازمند نسخه دامپزشک هستند، تا قبل از تأیید دیجیتال نسخه در پنل داروخانه، به هیچ عنوان بسته‌بندی و ارسال نخواهند شد.'
                    ],
                    [
                        'num' => '۳',
                        'title' => 'پروتکل زنجیره سرد (Cold Chain)',
                        'content' => 'واکسن‌ها و سرم‌های بیولوژیک با برچسب ۲ الی ۸ درجه سانتی‌گراد، بایستی در یخدان‌های استاندارد آیس‌پک‌دار تحویل نماینده پست اکسپرس یا پیک گردند.'
                    ],
                    [
                        'num' => '۴',
                        'title' => 'تعهد سهمیه انبار اشتراک دوره‌ای (Autoship)',
                        'content' => 'جهت درج نشان اشتراک دوره‌ای بر روی مکمل‌ها، داروخانه متعهد به نگهداری حداقل موجودی معادل ۳ ماه سفارشات مشترکین بوده تا اختلالی در روند درمان حیوانات ایجاد نشود.'
                    ]
                ]
            ],

            'seller' => [
                'role_fa' => 'فروشندگان و تأمین‌کنندگان مارکت‌پلیس',
                'title' => 'قرارداد جامع غرفه فروشگاهی، تضمین کیفیت انبار و تسویه هفتگی پایا',
                'summary' => 'توافق‌نامه رسمی میان تأمین‌کننده مجاز و پلتفرم آسنا جهت عرضه محصولات استاندارد پت‌شاپ، لجستیک سریع پستی و صیانت از منافع تجاری طرفین.',
                'win_win' => [
                    'profit_for_seller' => [
                        'ورود رایگان به ویترین ملی بدون نیاز به پرداخت هزینه‌های گزاف طراحی سایت و تبلیغات',
                        'فروش تضمینی در سبد اشتراک دوره‌ای (Autoship) برای تأمین‌کنندگان با موجودی پایدار',
                        'تسویه حساب اتوماتیک هفتگی پایا در هر پنج‌شنبه بدون نیاز به پیگیری و واسطه',
                        'پوشش خسارت‌های لجستیکی ناشی از خطای سامانه‌های پستی با سیستم ثبت رهگیری ۲۴ رقمی'
                    ],
                    'profit_for_platform' => [
                        'ایجاد تنوع کالایی بی‌نظیر برای سرپرستان پت با قیمت رقابتی',
                        'کاهش هزینه‌های انبارداری مرکزی با استفاده از مدل دراپ‌شیپینگ و تأمین مستقیم فروشندگان',
                        'کسب کارمزد منصفانه مارکت‌پلیس و تضمین رشد جمعی اکوسیستم'
                    ]
                ],
                'articles' => [
                    [
                        'num' => '۱',
                        'title' => 'تطابق موجودی انبار و اصالت کالا',
                        'content' => 'فروشنده موظف است موجودی و قیمت پنل را لحظه‌ای بروزرسانی کند. در صورت ثبت سفارش توسط مشتری و اعلام عدم موجودی، ۵٪ از ارزش کالا بابت افت رضایت مشتری از بستانکاری فروشنده کسر می‌گردد.'
                    ],
                    [
                        'num' => '۲',
                        'title' => 'مهلت پردازش و الصاق بارکد پستی ۲۴ رقمی',
                        'content' => 'فروشنده متعهد است ظرف حداکثر ۲۴ ساعت کاری مرسوله را بسته‌بندی و کد رهگیری ۲۴ رقمی شرکت ملی پست یا بارنامه معتبر باربری را در پنل ثبت نماید.'
                    ],
                    [
                        'num' => '۳',
                        'title' => 'قانون امانت‌داری وجه و گارانتی بازگشت ۷ روزه',
                        'content' => 'مبلغ هر سفارش پس از تایید تحویل توسط وب‌سرویس شرکت پست، به مدت ۷ روز کاری طبق ماده ۳۷ قانون تجارت الکترونیک در وضعیت امانی می‌ماند و پس از آن در سیکل پایا واریز می‌گردد.'
                    ],
                    [
                        'num' => '۴',
                        'title' => 'قوانین احراز صلاحیت اشتراک دوره‌ای (Autoship)',
                        'content' => 'برای فعال شدن نشان اتوشیپ روی کالاهای فروشنده، حفظ موجودی حداقل ۵ عدد الزامی است. در صورت کاهش موجودی، کالا صرفاً به صورت فروش تکی عرضه می‌شود تا اختلالی در سفارش ماه آینده مشتری پیش نیاید.'
                    ]
                ]
            ],

            'organization' => [
                'role_fa' => 'مراکز درمانی، بیمارستان‌ها و کلینیک‌ها',
                'title' => 'قرارداد حاکمیت سازمانی، مدیریت کادر درمانی و پذیرش مستقیم بیمار',
                'summary' => 'توافق‌نامه رسمی میان مرکز درمانی دارای پروانه پروانه تأسیس و پلتفرم آسنا جهت نمایش در دایرکتوری رسمی، نوبت‌دهی چندپزشکه و تسویه شرکتی.',
                'win_win' => [
                    'profit_for_organization' => [
                        'دریافت پروفایل رسمی و معتبر سازمانی با قابلیت مسیریابی، برچسب ۲۴/۷ و معرفی بخش‌ها',
                        'مدیریت چندکاربره پزشکان، پرستاران و رزرو مستقیم پذیرش کلینیک با واریز به حساب حقوقی',
                        'سیستم اعلام آمادگی اورژانس (Emergency Beacon) برای هدایت سریع کیس‌های بحرانی',
                        'نمایش انبار و داروخانه اختصاصی مرکز در پلتفرم آسنا'
                    ],
                    'profit_for_platform' => [
                        'اتصال بیماران به معتبرترین مراکز درمانی مجهز در سرتاسر شهرهای کشور',
                        'مدیریت یکپارچه خدمات حضوری و درمانی در کنار خدمات آنلاین',
                        'توسعه شبکه زیرساخت اورژانس و تله‌مدیسین'
                    ]
                ],
                'articles' => [
                    [
                        'num' => '۱',
                        'title' => 'پروانه معتبر و مسئولیت مدیریت مرکز',
                        'content' => 'سازمان متعهد است مدارک ثبتی، پروانه تأسیس معتبر دامپزشکی و مشخصات مسئول فنی مرکز را ارائه دهد. مدیریت مرکز مسئول صحت عملکرد پزشکان منتسب در سامانه است.'
                    ],
                    [
                        'num' => '۲',
                        'title' => 'نظم شیفت‌ها و پذیرش مستقیم نوبت‌ها',
                        'content' => 'مرکز درمانی موظف است کلیه نوبت‌های رزروشده از طریق آسنا را در اولویت نوبت‌دهی قرار داده و در صورت بروز کنسلی، حداقل ۴ ساعت زودتر اطلاع‌رسانی یا پزشک جایگزین تعیین فرماید.'
                    ],
                    [
                        'num' => '۳',
                        'title' => 'تعهدات مراکز شبانه‌روزی (24/7)',
                        'content' => 'مراکزی که با برچسب شبانه‌روزی و اورژانس در آسنا معرفی می‌شوند، موظف به حفظ آمادگی پذیرش بیمار در تمامی ساعات شبانه‌روز می‌باشند.'
                    ],
                    [
                        'num' => '۴',
                        'title' => 'تسویه حساب مالی حقوقی',
                        'content' => 'وجوه نوبت‌ها و سفارشات مرکز پس از کسر کارمزد مصوب پلتفرم، به شماره شبای حقوقی معرفی‌شده توسط مدیر مرکز واریز می‌گردد.'
                    ]
                ]
            ]
        ];

        $target = $contracts[$role] ?? $contracts['user'];
        $target['version'] = self::CURRENT_VERSION;
        $target['hash'] = hash('sha256', json_encode($target['articles'], JSON_UNESCAPED_UNICODE));

        // Ensure universal 'profit_for_user' key exists for template rendering across all roles
        if (!isset($target['win_win']['profit_for_user'])) {
            $specificKey = "profit_for_{$role}";
            $target['win_win']['profit_for_user'] = $target['win_win'][$specificKey] ?? [];
        }

        return $target;
    }
}
