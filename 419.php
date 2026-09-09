<?php
http_response_code(419);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۴۱۹ | هاپو کوکی شما رو قورت داد! - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="/assets/css/material-symbols.css">
    <style>
        :root {
            --primary: #001a48;
            --primary-light: #002d72;
            --accent: #fd8100;
            --cookie-color: #b45309;
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
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 40%, #f8fafc 100%);
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
            box-shadow: 0 25px 60px -15px rgba(180, 83, 9, 0.15), 0 0 0 1px rgba(254, 243, 199, 0.8);
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
            background: linear-gradient(90deg, #b45309, #fd8100, #001a48);
        }

        .badge-error {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
            padding: 5px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .mascot-area {
            position: relative;
            width: 140px;
            height: 110px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .puppy-cookie {
            font-size: 64px;
            display: inline-block;
            animation: puppyMunch 2.5s ease-in-out infinite;
        }

        @keyframes puppyMunch {
            0%, 100% { transform: scale(1); }
            30% { transform: scale(1.1) rotate(5deg); }
            70% { transform: scale(1.05) rotate(-5deg); }
        }

        .crumbs {
            position: absolute;
            bottom: 4px;
            right: 14px;
            font-size: 24px;
        }

        .error-code {
            font-size: 76px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #b45309, #fd8100);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 21px;
            font-weight: 900;
            color: #92400e;
            margin-bottom: 8px;
        }

        p.desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 24px;
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .btn-cookie-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #b45309;
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            padding: 12px 24px;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(180, 83, 9, 0.25);
        }

        .btn-cookie-custom:hover {
            background: #92400e;
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
            <span class="material-symbols-outlined" style="font-size: 16px;">cookie</span>
            <span>خطای ۴۱۹ - نشست کاربری منقضی شد (CSRF)</span>
        </div>

        <div class="mascot-area">
            <div class="puppy-cookie">🐶🍪</div>
            <div class="crumbs">✨</div>
        </div>

        <div class="error-code">419</div>

        <h1>هاپوی شکمو کوکی نشست شما رو قورت داد!</h1>
        <p class="desc">
            به دلیل طولانی شدن زمان عدم فعالیت در این صفحه، کلید امنیتی نشست (CSRF Token) منقضی شده است. برای حفظ امنیت حساب، نیاز به یک رفرش ساده برای دریافت کوکی تازه دارید.
        </p>

        <div class="action-row">
            <button onclick="window.location.reload()" class="btn-cookie-custom">
                <span>🍪</span>
                <span>پختن کوکی تازه و ادامه کار!</span>
            </button>
            <a href="/" class="btn-secondary-custom">
                <span class="material-symbols-outlined">home</span>
                صفحه اصلی
            </a>
        </div>

    </div>

    <script src="/assets/js/offline-icons.js"></script>
</body>
</html>
