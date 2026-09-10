<?php
$base_path = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base_path === '/') $base_path = '';
http_response_code(500);
$errorTrackingId = 'ERR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۵۰۰ | خطای داخلی سرور - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="<?= $base_path ?>/favicon.ico">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/material-symbols.css">
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
            max-width: 580px;
            width: 100%;
            padding: 40px 32px;
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
            margin-bottom: 20px;
        }

        /* Tidy Mascot Layout */
        .mascot-area {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .mascot-circle {
            width: 105px;
            height: 105px;
            border-radius: 50%;
            background: radial-gradient(circle at 40% 40%, #ffffff 0%, #ffe4e6 65%, #fecdd3 100%);
            border: 3px solid #ffffff;
            box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.22), 0 0 0 1px #fecdd3;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .cat-avatar {
            font-size: 52px;
            line-height: 1;
            display: block;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.08));
            animation: catPlay 3s ease-in-out infinite;
        }

        @keyframes catPlay {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            30% { transform: translateY(-4px) rotate(-5deg); }
            70% { transform: translateY(-2px) rotate(5deg); }
        }

        .yarn-badge {
            position: absolute;
            bottom: -4px;
            left: -4px;
            width: 36px;
            height: 36px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            line-height: 1;
            box-shadow: 0 4px 10px rgba(225, 29, 72, 0.25);
            border: 2px solid #fecdd3;
            animation: rollYarn 4s linear infinite;
        }

        @keyframes rollYarn {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spark-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 32px;
            height: 32px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            line-height: 1;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 2px solid #fecdd3;
            animation: sparkFlicker 1.5s infinite;
        }

        @keyframes sparkFlicker {
            0%, 100% { opacity: 0.5; transform: scale(0.9); }
            50% { opacity: 1; transform: scale(1.15); }
        }

        .error-code {
            font-size: 72px;
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
            margin-bottom: 20px;
        }

        /* Tracking Code Box */
        .tracking-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px 16px;
            font-size: 12px;
            color: #475569;
            margin-bottom: 22px;
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
            margin-bottom: 12px;
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

        .asena-svg-icon {
            width: 18px;
            height: 18px;
            fill: currentColor;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
</head>
<body>

    <!-- Direct Embedded SVG Icons (100% Zero-Latency, Never raw text) -->
    <svg id="error-icons-defs" xmlns="http://www.w3.org/2000/svg" style="display: none;">
      <symbol id="icon-crisis_alert" viewBox="0 -960 960 960">
        <path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-114 59.5-210.5T301-838q1 19 4 38.5t10 45.5q-72 44-113.5 116.5T160-480q0 134 93 227t227 93q134 0 227-93t93-227q0-85-41.5-158T644-755q7-26 10-45.5t5-37.5q102 51 161.5 147T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-160q-100 0-170-70t-70-170q0-58 25.5-109t72.5-85q5 15 11 34.5t16 48.5q-22 23-33.5 51T320-480q0 66 47 113t113 47q66 0 113-47t47-113q0-32-11.5-60T595-591q8-24 14.5-44.5T621-674q47 34 73 85t26 109q0 100-70 170t-170 70Zm-40-380q-37-112-48.5-157.5T380-860q0-42 29-71t71-29q42 0 71 29t29 71q0 37-11.5 82.5T520-620h-80Zm40 220q-33 0-56.5-23.5T400-480q0-33 23.5-56.5T480-560q33 0 56.5 23.5T560-480q0 33-23.5 56.5T480-400Z"/>
      </symbol>
      <symbol id="icon-content_copy" viewBox="0 -960 960 960">
        <path d="M300-200q-24 0-42-18t-18-42v-560q0-24 18-42t42-18h440q24 0 42 18t18 42v560q0 24-18 42t-42 18H300Zm0-60h440v-560H300v560ZM180-80q-24 0-42-18t-18-42v-620h60v620h500v60H180Zm120-180v-560 560Z"/>
      </symbol>
      <symbol id="icon-home" viewBox="0 -960 960 960">
        <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/>
      </symbol>
      <symbol id="icon-support_agent" viewBox="0 -960 960 960">
        <path d="M440-120v-80h320v-284q0-117-81.5-198.5T480-764q-117 0-198.5 81.5T200-484v244h-40q-33 0-56.5-23.5T80-320v-80q0-21 10.5-39.5T120-469l3-53q8-68 39.5-126t79-101q47.5-43 109-67T480-840q68 0 129 24t109 66.5Q766-707 797-649t40 126l3 52q19 9 29.5 27t10.5 38v92q0 20-10.5 38T840-249v49q0 33-23.5 56.5T760-120H440Zm-80-280q-17 0-28.5-11.5T320-440q0-17 11.5-28.5T360-480q17 0 28.5 11.5T400-440q0 17-11.5 28.5T360-400Zm240 0q-17 0-28.5-11.5T560-440q0-17 11.5-28.5T600-480q17 0 28.5 11.5T640-440q0 17-11.5 28.5T600-400Zm-359-62q-7-106 64-182t177-76q89 0 156.5 56.5T720-519q-91-1-167.5-49T435-698q-16 80-67.5 142.5T241-462Z"/>
      </symbol>
    </svg>

    <div class="error-card">
        
        <div class="badge-error">
            <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-crisis_alert"></use></svg>
            <span>خطای ۵۰۰ - خطای داخلی سرور</span>
        </div>

        <div class="mascot-area">
            <div class="mascot-circle">
                <span class="cat-avatar" id="catMascot">🐱</span>
                <span class="yarn-badge">🧶</span>
                <span class="spark-badge">⚡</span>
            </div>
        </div>

        <div class="error-code">500</div>

        <h1>گربه بازیگوش سیم‌ها رو قاطی کرده!</h1>
        <p class="desc">
            یک خطای فنی غیرمنتظره در سرور رخ داده است. مهندسان فنی آسنا بلافاصله گزارش خطا را دریافت کردند و در حال بررسی مشکل هستند.
        </p>

        <div class="tracking-box">
            <span>کد پیگیری خطا:</span>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="tracking-code" id="errCode"><?= htmlspecialchars($errorTrackingId) ?></span>
                <button class="btn-copy" onclick="copyTrackingCode()">
                    <svg class="asena-svg-icon" style="width: 14px; height: 14px;" aria-hidden="true"><use href="#icon-content_copy"></use></svg>
                    <span>کپی</span>
                </button>
            </div>
        </div>

        <div class="action-row">
            <button onclick="calmCatAndReload()" class="btn-calm-cat" id="calmBtn">
                <span>🐟</span>
                <span>راه‌اندازی مجدد</span>
            </button>
            <a href="<?= $base_path ?>/" class="btn-secondary-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-home"></use></svg>
                <span>صفحه اصلی</span>
            </a>
            <a href="<?= $base_path ?>/user_tickets.php" class="btn-secondary-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-support_agent"></use></svg>
                <span>پشتیبانی</span>
            </a>
        </div>

    </div>

    <script src="<?= $base_path ?>/assets/js/offline-icons.js"></script>
    <script>
        function calmCatAndReload() {
            const cat = document.getElementById('catMascot');
            const btn = document.getElementById('calmBtn');
            cat.innerText = '😸';
            cat.style.transform = 'scale(1.25) rotate(10deg)';
            btn.innerHTML = '<span>🐟</span> در حال راه‌اندازی سرور...';
            btn.disabled = true;

            setTimeout(() => {
                window.location.reload();
            }, 800);
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
