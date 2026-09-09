<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۴۰۳ | ورود ممنوع! سگ نگهبان شیفت کشیک - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="/assets/css/material-symbols.css">
    <style>
        :root {
            --primary: #001a48;
            --primary-light: #002d72;
            --danger: #dc2626;
            --accent: #fd8100;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 40%, #f1f5f9 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            text-align: center;
            overflow-x: hidden;
            position: relative;
        }

        .error-card {
            background: #ffffff;
            border-radius: 32px;
            box-shadow: 0 25px 60px -15px rgba(220, 38, 38, 0.15), 0 0 0 1px rgba(254, 226, 226, 0.8);
            max-width: 580px;
            width: 100%;
            padding: 44px 32px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #dc2626, #f97316, #001a48);
        }

        .badge-error {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 5px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        /* Mascot Container */
        .mascot-area {
            position: relative;
            width: 130px;
            height: 120px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dog-guard {
            font-size: 68px;
            display: inline-block;
            animation: guardAlert 2.5s ease-in-out infinite;
        }

        @keyframes guardAlert {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05) translateY(-4px); }
        }

        .siren-badge {
            position: absolute;
            top: 6px;
            right: 14px;
            font-size: 26px;
            animation: sirenFlash 1.2s infinite;
        }

        @keyframes sirenFlash {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.9; }
            50% { transform: scale(1.2) rotate(15deg); opacity: 1; filter: drop-shadow(0 0 8px rgba(220, 38, 38, 0.6)); }
        }

        .error-code {
            font-size: 76px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 21px;
            font-weight: 900;
            color: #991b1b;
            margin-bottom: 8px;
        }

        p.desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 24px;
        }

        .alert-box {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 16px;
            padding: 12px 16px;
            font-size: 12px;
            color: #9a3412;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: right;
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .btn-danger-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #dc2626;
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            padding: 12px 24px;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.25);
        }

        .btn-danger-custom:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .btn-secondary-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #ffffff;
            color: var(--primary);
            font-size: 14px;
            font-weight: 700;
            padding: 12px 20px;
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-secondary-custom:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }
    </style>
</head>
<body>

    <div class="error-card">
        
        <div class="badge-error">
            <span class="material-symbols-outlined" style="font-size: 16px;">lock</span>
            <span>خطای ۴۰۳ - دسترسی غیرمجاز</span>
        </div>

        <div class="mascot-area">
            <div class="dog-guard">🐕‍🦺</div>
            <div class="siren-badge">🚨</div>
        </div>

        <div class="error-code">403</div>

        <h1>ایست! سگ نگهبان شیفت کشیک است</h1>
        <p class="desc">
            ورود به این بخش محافظت‌شده است و نیاز به مجوز امنیتی یا سطح دسترسی متفاوتی (مدیر، پزشک، سازمان یا فروشنده) دارد. سگ نگهبان آسنا بدون تأیید هویت اجازه عبور نمی‌دهد!
        </p>

        <div class="alert-box">
            <span class="material-symbols-outlined" style="font-size: 22px; color: #ea580c;">shield_lock</span>
            <span>اگر فکر می‌کنید دسترسی شما اشتباهاً محدود شده، با حساب کاربری دیگری وارد شوید یا با پشتیبانی تماس بگیرید.</span>
        </div>

        <div class="action-row">
            <a href="/login.php" class="btn-danger-custom">
                <span class="material-symbols-outlined">login</span>
                ورود به حساب یا تغییر نقش
            </a>
            <a href="/" class="btn-secondary-custom">
                <span class="material-symbols-outlined">home</span>
                بازگشت به منطقه امن (صفحه اصلی)
            </a>
        </div>

    </div>

    <script src="/assets/js/offline-icons.js"></script>
</body>
</html>
