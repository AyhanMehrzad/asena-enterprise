<?php
/**
 * test_sms.php — Dedicated Temporary SMS Testing Dashboard
 * 
 * Allows live interactive testing of:
 * 1. Pattern / OTP SMS delivery (Service line passing telecom blacklist)
 * 2. Custom Pattern with parameters
 * 3. Direct SMS delivery (from line 2170002198)
 * 4. Account balance & credit live inquiry
 * 5. Full gateway diagnostics and telecommunication RecId tracking
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/SmsService.php';

$sms = new SmsService();

$result = null;
$actionType = '';
$recipient = $_POST['phone'] ?? '09146676978';
$testCode = $_POST['code'] ?? (string)random_int(100000, 999999);
$customBodyId = $_POST['body_id'] ?? '518597';
$directMessage = $_POST['message'] ?? 'آسنا: پیامک آزمایشی سامانه جامع سلامت و پت‌شاپ آنلاین آسنا';

// Handle Action Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $recipient = trim((string)($_POST['phone'] ?? ''));

    if ($action === 'send_otp') {
        $actionType = 'OTP (الگوی خدماتی ' . htmlspecialchars($customBodyId) . ')';
        $sent = $sms->sendPatternRequest($recipient, $customBodyId, [$testCode], 'TEST_OTP');
        $result = [
            'success' => $sent,
            'log' => $sms->getLastLog(),
            'error' => $sms->getLastError()
        ];
    } elseif ($action === 'send_direct') {
        $actionType = 'پیامک مستقیم متنی (خط اختصاصی)';
        $sent = $sms->sendDirectSms($recipient, $directMessage, 'TEST_DIRECT');
        $result = [
            'success' => $sent,
            'log' => $sms->getLastLog(),
            'error' => $sms->getLastError()
        ];
    }
}

// Live Credit Inquiry
$creditBalance = null;
try {
    $creditBalance = $sms->getCredit();
} catch (\Throwable $e) {}

// Retrieve configured settings from database
$dbApiKey   = get_setting($pdo, 'melipayamak_api_key', '');
$dbUser     = get_setting($pdo, 'melipayamak_username', '');
$dbFrom     = get_setting($pdo, 'melipayamak_from', '2170002198');
$dbSandbox  = get_setting($pdo, 'melipayamak_sandbox', '0');

$maskedApiKey = !empty($dbApiKey) ? substr($dbApiKey, 0, 8) . '••••••••' . substr($dbApiKey, -4) : 'تنظیم نشده';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحه موقت تست و عیب‌یابی درگاه پیامک | پلتفرم آسنا</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #e0e7ff;
            --success: #10b981;
            --success-dark: #059669;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #0f172a;
            --surface: #1e293b;
            --card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-lg: 16px;
            --radius-md: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Vazirmatn', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 16px 60px;
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
        }

        /* Banner */
        .temp-badge-banner {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 12px 18px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.08);
        }

        /* Header */
        .header {
            background: white;
            padding: 24px 28px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            margin-bottom: 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .credit-card {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: white;
            padding: 14px 20px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .credit-info h4 {
            font-size: 12px;
            opacity: 0.85;
            font-weight: 400;
        }

        .credit-val {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        /* Config Grid */
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .config-pill {
            background: white;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .config-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .config-value {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            direction: ltr;
            text-align: right;
            font-family: monospace;
        }

        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--success);
            margin-left: 6px;
        }

        /* Testing Section Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        @media (max-width: 800px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .card-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .card-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }

        .card-header p {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Form elements */
        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: var(--radius-md);
            font-size: 14px;
            color: #1e293b;
            transition: all 0.2s ease;
            outline: none;
            direction: ltr;
            text-align: right;
        }

        textarea {
            direction: rtl;
            text-align: right;
            resize: vertical;
            min-height: 85px;
        }

        input[type="text"]:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .input-group {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 18px;
            font-size: 14px;
            font-weight: 700;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
            width: 100%;
            margin-top: auto;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .helper-text {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 5px;
        }

        /* Result Section */
        .result-box {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-success {
            background: #d1fae5;
            color: #065f46;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .recid-highlight {
            background: #eff6ff;
            border: 1.5px dashed #3b82f6;
            color: #1e40af;
            padding: 14px 18px;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .code-display {
            background: #0f172a;
            color: #38bdf8;
            padding: 16px;
            border-radius: var(--radius-md);
            font-family: monospace;
            font-size: 12px;
            direction: ltr;
            text-align: left;
            overflow-x: auto;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .links-bar {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Temporary Banner -->
    <div class="temp-badge-banner">
        <span>⚡ <strong>محیط موقت تست زنده درگاه پیامک پلتفرم آسنا</strong> — کدهای ارسالی این صفحه به صورت واقعی به مخابرات تحویل می‌شوند.</span>
        <span style="font-size: 12px; opacity: 0.85;">پایان تست: قابل حذف امن</span>
    </div>

    <!-- Header & Live Credit -->
    <div class="header">
        <div class="header-title">
            <h1>
                <span>📱 کنسول تست و راستی‌آزمایی پیامک</span>
                <span class="status-dot"></span>
            </h1>
            <p>بررسی درگاه، ارسال کد احراز هویت OTP، ارسال پیام متنی و رهگیری شناسه دکل مخابراتی (RecId)</p>
        </div>

        <div class="credit-card">
            <div class="credit-info">
                <h4>مانده شارژ فعال ملی‌پیامک</h4>
                <div class="credit-val">
                    <?= ($creditBalance !== null) ? number_format($creditBalance, 2) : '—' ?>
                    <span style="font-size: 14px; font-weight: 400;">پیامک</span>
                </div>
            </div>
            <a href="test_sms.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 6px 12px; font-size: 12px;">
                🔄 بروزرسانی
            </a>
        </div>
    </div>

    <!-- Active Configurations -->
    <div class="config-grid">
        <div class="config-pill">
            <span class="config-label">نام کاربری سامانه</span>
            <span class="config-value"><?= htmlspecialchars($dbUser ?: '9146676978') ?></span>
        </div>
        <div class="config-pill">
            <span class="config-label">کلید امنیتی وب‌سرویس (API Key)</span>
            <span class="config-value"><?= htmlspecialchars($maskedApiKey) ?></span>
        </div>
        <div class="config-pill">
            <span class="config-label">خط اختصاصی فرستنده</span>
            <span class="config-value"><?= htmlspecialchars($dbFrom ?: '2170002198') ?></span>
        </div>
        <div class="config-pill">
            <span class="config-label">حالت ارسال</span>
            <span class="config-value" style="color: #059669; font-weight: 800;">
                <?= ($dbSandbox === '1') ? 'شبیه‌ساز (Sandbox)' : '🟢 عملیاتی مخابراتی (Live)' ?>
            </span>
        </div>
    </div>

    <!-- Result Display if Action Executed -->
    <?php if ($result !== null): ?>
        <div class="result-box">
            <div class="result-header">
                <div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0f172a;">
                        نتیجه ارسال <?= $actionType ?>
                    </h3>
                    <span style="font-size: 12px; color: var(--text-muted);">
                        زمان اجرا: <?= date('Y-m-d H:i:s') ?> | شماره مقصد: <?= htmlspecialchars($recipient) ?>
                    </span>
                </div>
                <div>
                    <?php if ($result['success']): ?>
                        <span class="status-badge status-success">
                            ✔ تایید ارسال موفق به مخابرات
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-failed">
                            ✖ خطای ارسال درگاه
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php 
                $val = $result['log']['value'] ?? null;
                $retStatus = $result['log']['ret_status'] ?? null;
                $interp = $result['log']['interpretation'] ?? $result['error'];
            ?>

            <?php if ($result['success'] && !empty($val)): ?>
                <div class="recid-highlight">
                    <span>📡 <strong>کد پیگیری مخابرات (RecId):</strong> <?= htmlspecialchars((string)$val) ?></span>
                    <span style="font-size: 12px; color: #1e40af;">پیامک با موفقیت وارد صف ارسال دکل مخابراتی شد</span>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 14px;">
                <strong>تفسیر وضعیت درگاه:</strong>
                <span style="font-weight: 700; color: <?= $result['success'] ? '#059669' : '#dc2626' ?>;">
                    <?= htmlspecialchars($interp ?: 'بدون پیام') ?>
                </span>
            </div>

            <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #475569;">گزارش تشخیصی کامل درگاه (Diagnostic Payload & Response):</h4>
            <div class="code-display"><?= htmlspecialchars(json_encode($result['log'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></div>
        </div>
    <?php endif; ?>

    <!-- Testing Cards Grid -->
    <div class="cards-grid">
        
        <!-- Card 1: OTP / Pattern (Fast Service Line) -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon">⚡</div>
                <div>
                    <h3>ارسال کد تایید OTP (الگوی خدماتی)</h3>
                    <p>عبور ۱۰۰٪ از بلک‌لیست تبلیغاتی با خط خدماتی اشتراکی</p>
                </div>
            </div>

            <form method="POST" action="test_sms.php">
                <input type="hidden" name="action" value="send_otp">

                <div class="form-group">
                    <label for="phone1">شماره موبایل گیرنده:</label>
                    <input type="text" id="phone1" name="phone" value="<?= htmlspecialchars($recipient) ?>" required placeholder="09146676978">
                    <div class="helper-text">شماره موبایل خود را با 09 وارد کنید.</div>
                </div>

                <div class="form-group">
                    <label for="code">کد تایید انتخابی یا تصادفی:</label>
                    <div class="input-group">
                        <input type="text" id="code" name="code" value="<?= htmlspecialchars($testCode) ?>" required maxlength="8">
                        <button type="button" class="btn btn-secondary" onclick="generateCode()" title="تولید کد جدید">🎲 تصادفی</button>
                    </div>
                    <div class="helper-text">این مقدار در قالب به عنوان متغیر {0} ارسال می‌گردد.</div>
                </div>

                <div class="form-group">
                    <label for="body_id">شناسه الگوی ملی‌پیامک (BodyId):</label>
                    <input type="text" id="body_id" name="body_id" value="<?= htmlspecialchars($customBodyId) ?>" required>
                    <div class="helper-text">الگوی پیش‌فرض ثبت‌شده تایید کد: <strong>518597</strong></div>
                </div>

                <button type="submit" class="btn btn-primary">
                    📤 ارسال کد تایید یک‌بارمصرف (OTP)
                </button>
            </form>
        </div>

        <!-- Card 2: Direct Custom SMS (Line 2170002198) -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon">💬</div>
                <div>
                    <h3>ارسال پیامک مستقیم متنی</h3>
                    <p>ارسال از شماره اختصاصی ثابت ۲۱۷۰۰۰۲۱۹۸</p>
                </div>
            </div>

            <form method="POST" action="test_sms.php">
                <input type="hidden" name="action" value="send_direct">

                <div class="form-group">
                    <label for="phone2">شماره موبایل گیرنده:</label>
                    <input type="text" id="phone2" name="phone" value="<?= htmlspecialchars($recipient) ?>" required placeholder="09146676978">
                    <div class="helper-text">توجه: خطوط اختصاصی پیامک به شماره‌های دارای بلک‌لیست مخابراتی تحویل نمی‌شوند.</div>
                </div>

                <div class="form-group">
                    <label for="message">متن پیامک دلخواه:</label>
                    <textarea id="message" name="message" required><?= htmlspecialchars($directMessage) ?></textarea>
                    <div class="helper-text">طول استاندارد یک پارت پیامک فارسی تا ۷۰ کاراکتر است.</div>
                </div>

                <button type="submit" class="btn btn-primary" style="background: #059669;">
                    ✉ ارسال پیامک متنی مستقیم
                </button>
            </form>
        </div>

    </div>

    <!-- Quick Navigation Links -->
    <div class="links-bar">
        <a href="login.php" class="btn btn-secondary">🔐 مراجعه به صفحه ورود (login.php)</a>
        <a href="admin/sms_settings.php" class="btn btn-secondary">⚙ تنظیمات دائمی پیامک در پنل ادمین</a>
        <a href="index.php" class="btn btn-secondary">🏠 صفحه اصلی سایت</a>
    </div>

</div>

<script>
    function generateCode() {
        const rand = Math.floor(100000 + Math.random() * 900000);
        document.getElementById('code').value = rand;
    }
</script>

</body>
</html>
