<?php
$base_path = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base_path === '/') $base_path = '';
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۴۰۳ | دسترسی غیرمجاز - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="<?= $base_path ?>/favicon.ico">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/material-symbols.css">
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
            background: radial-gradient(circle at 40% 40%, #ffffff 0%, #fee2e2 65%, #fecaca 100%);
            border: 3px solid #ffffff;
            box-shadow: 0 10px 25px -5px rgba(220, 38, 38, 0.22), 0 0 0 1px #fecaca;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .dog-avatar {
            font-size: 52px;
            line-height: 1;
            display: block;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.08));
            animation: dogAlert 2.5s ease-in-out infinite alternate;
        }

        @keyframes dogAlert {
            0% { transform: scale(1) translateY(0); }
            100% { transform: scale(1.05) translateY(-3px); }
        }

        .siren-badge {
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
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.25);
            border: 2px solid #fecaca;
            animation: sirenFlash 1.2s infinite;
        }

        @keyframes sirenFlash {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.9; }
            50% { transform: scale(1.15) rotate(15deg); opacity: 1; filter: drop-shadow(0 0 6px rgba(220, 38, 38, 0.5)); }
        }

        .error-code {
            font-size: 72px;
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
            margin-bottom: 22px;
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
            margin-bottom: 12px;
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
      <symbol id="icon-lock" viewBox="0 -960 960 960">
        <path d="M240-80q-33 0-56.5-23.5T160-160v-400q0-33 23.5-56.5T240-640h40v-80q0-83 58.5-141.5T480-920q83 0 141.5 58.5T680-720v80h40q33 0 56.5 23.5T800-560v400q0 33-23.5 56.5T720-80H240Zm0-80h480v-400H240v400Zm240-120q33 0 56.5-23.5T560-360q0-33-23.5-56.5T480-440q-33 0-56.5 23.5T400-360q0 33 23.5 56.5T480-280ZM360-640h240v-80q0-50-35-85t-85-35q-50 0-85 35t-35 85v80ZM240-160v-400 400Z"/>
      </symbol>
      <symbol id="icon-shield_lock" viewBox="0 -960 960 960">
        <path d="M480-80q-139-35-229.5-159.5T160-516v-244l320-120 320 120v244q0 152-90.5 276.5T480-80Zm0-84q104-33 172-132t68-220v-189l-240-90-240 90v189q0 121 68 220t172 132Zm0-316Zm-80 160h160q17 0 28.5-11.5T600-360v-120q0-17-11.5-28.5T560-520v-40q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560v40q-17 0-28.5 11.5T360-480v120q0 17 11.5 28.5T400-320Zm40-200v-40q0-17 11.5-28.5T480-600q17 0 28.5 11.5T520-560v40h-80Z"/>
      </symbol>
      <symbol id="icon-login" viewBox="0 -960 960 960">
        <path d="M480-120v-80h280v-560H480v-80h280q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H480Zm-80-160-55-58 102-102H120v-80h327L345-622l55-58 200 200-200 200Z"/>
      </symbol>
      <symbol id="icon-home" viewBox="0 -960 960 960">
        <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/>
      </symbol>
    </svg>

    <div class="error-card">
        
        <div class="badge-error">
            <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-lock"></use></svg>
            <span>خطای ۴۰۳ - دسترسی غیرمجاز</span>
        </div>

        <div class="mascot-area">
            <div class="mascot-circle">
                <span class="dog-avatar">🐕‍🦺</span>
                <span class="siren-badge">🚨</span>
            </div>
        </div>

        <div class="error-code">403</div>

        <h1>ایست! سگ نگهبان شیفت کشیک است</h1>
        <p class="desc">
            ورود به این بخش محافظت‌شده است و نیاز به مجوز امنیتی یا سطح دسترسی متفاوتی (مدیر، پزشک، سازمان یا فروشنده) دارد. سگ نگهبان آسنا بدون تأیید هویت اجازه عبور نمی‌دهد!
        </p>

        <div class="alert-box">
            <svg class="asena-svg-icon" style="font-size: 20px; color: #ea580c; flex-shrink: 0;" aria-hidden="true"><use href="#icon-shield_lock"></use></svg>
            <span>اگر فکر می‌کنید دسترسی شما اشتباهاً محدود شده، با حساب کاربری دیگری وارد شوید یا با پشتیبانی تماس بگیرید.</span>
        </div>

        <div class="action-row">
            <a href="<?= $base_path ?>/login.php" class="btn-danger-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-login"></use></svg>
                <span>ورود به حساب کاربری</span>
            </a>
            <a href="<?= $base_path ?>/" class="btn-secondary-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-home"></use></svg>
                <span>صفحه اصلی</span>
            </a>
        </div>

    </div>

    <script src="<?= $base_path ?>/assets/js/offline-icons.js"></script>
</body>
</html>
