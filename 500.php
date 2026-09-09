<?php
http_response_code(500);
$errorTrackingId = 'ERR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۵۰۰ | گربه بازیگوش سیم‌ها رو قاطی کرده! - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="/assets/css/material-symbols.css">
    <style>
        :root {
            --primary: #001a48;
            --primary-light: #002d72;
            --accent: #fd8100;
            --danger: #e11d48;
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
            background: linear-gradient(135deg, #fff1f2 0%, #fef2f2 40%, #f8fafc 100%);
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
            box-shadow: 0 25px 60px -15px rgba(225, 29, 72, 0.14), 0 0 0 1px rgba(254, 205, 211, 0.7);
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
            background: linear-gradient(90deg, #e11d48, #fd8100, #001a48);
        }

        .badge-error {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
            padding: 5px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        /* Mascot Area: Cat + Yarn + Server */
        .mascot-area {
            position: relative;
            width: 150px;
            height: 120px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cat-box {
            font-size: 68px;
            display: inline-block;
            animation: catPlay 3s ease-in-out infinite;
            z-index: 2;
        }

        @keyframes catPlay {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            30% { transform: translateY(-6px) rotate(-6deg); }
            70% { transform: translateY(-2px) rotate(6deg); }
        }

        .yarn-box {
            position: absolute;
            bottom: 6px;
            left: 14px;
            font-size: 32px;
            animation: rollYarn 4s linear infinite;
        }

        @keyframes rollYarn {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spark-box {
            position: absolute;
            top: 10px;
            right: 18px;
            font-size: 26px;
            animation: sparkFlicker 1.5s infinite;
        }

        @keyframes sparkFlicker {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.3); }
        }

        .error-code {
            font-size: 76px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #e11d48, #001a48);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 21px;
            font-weight: 900;
            color: #be123c;
            margin-bottom: 8px;
        }

        p.desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 22px;
        }

        /* Tracking Code Box */
        .tracking-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px 16px;
            font-size: 12px;
            color: #475569;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .tracking-code {
            font-family: monospace;
            font-weight: 800;
            color: #be123c;
            background: #fff1f2;
            padding: 3px 8px;
            border-radius: 8px;
            direction: ltr;
        }

        .btn-copy {
            background: none;
            border: none;
            color: #0284c7;
            cursor: pointer;
            font-size: 11.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Action Buttons */
        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .btn-calm-cat {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #e11d48;
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            padding: 12px 24px;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.25);
        }

        .btn-calm-cat:hover {
            background: #be123c;
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
            <span class="material-symbols-outlined" style="font-size: 16px;">crisis_alert</span>
            <span>خطای ۵۰۰ - اختلال موقت سرور</span>
        </div>

        <div class="mascot-area">
            <div class="cat-box" id="catMascot">🐱</div>
            <div class="yarn-box">🧶</div>
            <div class="spark-box">⚡</div>
        </div>

        <div class="error-code">500</div>

        <h1>گربه بازیگوش سیم‌های سرور رو به کلاف کاموا تبدیل کرده!</h1>
        <p class="desc">
            یک خطای فنی غیرمنتظره در سرور رخ داده است. مهندسان فنی و دامپزشکان آی‌تی آسنا بلافاصله لاگ‌ها را بررسی و در حال مرتب کردن کابل‌ها هستند!
        </p>

        <div class="tracking-box">
            <span>کد پیگیری خطا در پشتیبانی:</span>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="tracking-code" id="errCode"><?= htmlspecialchars($errorTrackingId) ?></span>
                <button class="btn-copy" onclick="copyTrackingCode()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">content_copy</span>
                    کپی
                </button>
            </div>
        </div>

        <div class="action-row">
            <button onclick="calmCatAndReload()" class="btn-calm-cat" id="calmBtn">
                <span>🐟</span>
                <span>دادن ماهی به گربه و بارگذاری مجدد!</span>
            </button>
            <a href="/" class="btn-secondary-custom">
                <span class="material-symbols-outlined">home</span>
                صفحه اصلی
            </a>
            <a href="/user_tickets.php" class="btn-secondary-custom">
                <span class="material-symbols-outlined">support_agent</span>
                ارسال تیکت به پشتیبانی
            </a>
        </div>

    </div>

    <script src="/assets/js/offline-icons.js"></script>
    <script>
        function calmCatAndReload() {
            const cat = document.getElementById('catMascot');
            const btn = document.getElementById('calmBtn');
            cat.innerText = '😸';
            cat.style.transform = 'scale(1.3) rotate(15deg)';
            btn.innerHTML = '<span>🐟</span> در حال تشکر گربه و راه‌اندازی سرور...';
            btn.disabled = true;

            setTimeout(() => {
                window.location.reload();
            }, 900);
        }

        function copyTrackingCode() {
            const code = document.getElementById('errCode').innerText;
            navigator.clipboard.writeText(code).then(() => {
                alert('کد پیگیری کپی شد: ' + code);
            });
        }
    </script>
</body>
</html>
