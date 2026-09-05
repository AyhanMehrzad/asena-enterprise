<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آسنا | خطای غیرمنتظره سرور (خطای ۵۰۰)</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="/assets/css/material-symbols.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            text-align: center;
        }
        .error-card {
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(220, 38, 38, 0.12);
            border: 1px solid #fee2e2;
            max-width: 580px;
            width: 100%;
            padding: 48px 36px;
            position: relative;
            overflow: hidden;
        }
        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #dc2626, #f97316, #002d72);
        }
        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef2f2;
            color: #dc2626;
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }
        .error-code {
            font-size: 72px;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
            letter-spacing: -2px;
        }
        h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }
        p {
            font-size: 14px;
            color: #64748b;
            line-height: 1.8;
            margin-bottom: 28px;
        }
        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            margin-bottom: 28px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: #002d72;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(0, 45, 114, 0.25);
        }
        .btn-primary:hover {
            background: #001f4d;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        .emergency-box {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px dashed #e2e8f0;
            font-size: 13px;
            color: #64748b;
        }
        .emergency-link {
            color: #ef4444;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: 15px;
            direction: ltr;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-badge">
            <span class="material-symbols-outlined" style="font-size: 18px;">error</span>
            خطای موقت پردازش سرور
        </div>
        <div class="error-code">۵۰۰</div>
        <h1>مشکلی در ارتباط با سرور رخ داده است</h1>
        <p>
            سیستم در حال حاضر قادر به پردازش این درخواست نمی‌باشد. رویداد در لاگ امنیتی سرور ثبت شده و تیم پشتیبانی فنی آسنا در حال بررسی آن است. لطفاً چند لحظه دیگر دوباره تلاش نمایید.
        </p>

        <div class="action-group">
            <button onclick="window.location.reload()" class="btn btn-primary">
                <span class="material-symbols-outlined" style="font-size: 20px;">refresh</span>
                تلاش مجدد
            </button>
            <a href="/index.php" class="btn btn-secondary">
                <span class="material-symbols-outlined" style="font-size: 20px;">home</span>
                صفحه اصلی آسنا
            </a>
        </div>

        <div class="emergency-box">
            <div>در صورت استمرار مشکل با بخش پشتیبانی فنی تماس بگیرید:</div>
            <a href="tel:+989146676978" class="emergency-link">
                <span class="material-symbols-outlined" style="font-size: 18px;">support_agent</span>
                +98 914 667 6978
            </a>
        </div>
    </div>
</body>
</html>
