<?php
http_response_code(503);
header('Retry-After: 300');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۵۰۳ | کلینیک در حال ضدعفونی و نگهداری دوره‌ای است - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="/assets/css/material-symbols.css">
    <style>
        :root {
            --primary: #001a48;
            --primary-light: #002d72;
            --accent: #fd8100;
            --info: #0284c7;
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
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 40%, #f8fafc 100%);
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
            box-shadow: 0 25px 60px -15px rgba(2, 132, 199, 0.15), 0 0 0 1px rgba(224, 242, 254, 0.8);
            max-width: 600px;
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
            background: linear-gradient(90deg, #0284c7, #10b981, #fd8100);
        }

        .badge-error {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0f9ff;
            color: #0369a1;
            border: 1px solid #bae6fd;
            padding: 5px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .mascot-area {
            position: relative;
            width: 150px;
            height: 110px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .doctor-pets {
            font-size: 64px;
            display: inline-block;
            animation: bounceMascot 2.8s ease-in-out infinite alternate;
        }

        @keyframes bounceMascot {
            0% { transform: scale(1) translateY(0); }
            100% { transform: scale(1.05) translateY(-6px); }
        }

        .tools-badge {
            position: absolute;
            top: 6px;
            right: 16px;
            font-size: 26px;
            animation: spinTool 5s linear infinite;
        }

        @keyframes spinTool {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-code {
            font-size: 76px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #0284c7, #001a48);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 21px;
            font-weight: 900;
            color: #0369a1;
            margin-bottom: 8px;
        }

        p.desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 24px;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 12px 18px;
            margin-bottom: 24px;
            font-size: 12.5px;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .btn-info-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #0284c7;
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            padding: 12px 24px;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
        }

        .btn-info-custom:hover {
            background: #0369a1;
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
            <span class="material-symbols-outlined" style="font-size: 16px;">cleaning_services</span>
            <span>خطای ۵۰۳ - سرویس در حال تعمیر و به‌روزرسانی</span>
        </div>

        <div class="mascot-area">
            <div class="doctor-pets">🩺🐰</div>
            <div class="tools-badge">⚙️</div>
        </div>

        <div class="error-code">503</div>

        <h1>کلینیک در حال ضدعفونی و چکاپ دوره‌ای سرورهاست!</h1>
        <p class="desc">
            پزشکان و مهندسان آسنا در حال اجرای عملیات ارتقای پایداری و بهینه‌سازی سرورها هستند. سرویس به زودی و در کمتر از چند دقیقه مجدداً در دسترس قرار خواهد گرفت.
        </p>

        <div class="info-card">
            <span>⏱️ وضعیت عملیات نگهداری:</span>
            <span style="font-weight: 800; color: #0284c7;">در حال اعمال آخرین تست‌ها...</span>
        </div>

        <div class="action-row">
            <button onclick="window.location.reload()" class="btn-info-custom">
                <span class="material-symbols-outlined">refresh</span>
                بررسی مجدد در دسترس بودن
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
