<?php
$base_path = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base_path === '/') $base_path = '';
$proto = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
header("$proto 419 Page Expired");
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۴۱۹ | نشست منقضی شد - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="<?= $base_path ?>/favicon.ico">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/material-symbols.css">
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
            background: radial-gradient(circle at 40% 40%, #ffffff 0%, #fef3c7 65%, #fde68a 100%);
            border: 3px solid #ffffff;
            box-shadow: 0 10px 25px -5px rgba(180, 83, 9, 0.22), 0 0 0 1px #fde68a;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .puppy-avatar {
            font-size: 52px;
            line-height: 1;
            display: block;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.08));
            animation: puppyNod 2.5s ease-in-out infinite alternate;
        }

        @keyframes puppyNod {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-4px) rotate(4deg); }
        }

        .cookie-badge {
            position: absolute;
            bottom: -4px;
            left: -4px;
            width: 38px;
            height: 38px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            line-height: 1;
            box-shadow: 0 4px 10px rgba(180, 83, 9, 0.25);
            border: 2px solid #fde68a;
            animation: cookieBob 2s ease-in-out infinite alternate;
        }

        @keyframes cookieBob {
            0% { transform: scale(1); }
            100% { transform: scale(1.08); }
        }

        .sparkle-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 30px;
            height: 30px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            line-height: 1;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 2px solid #fde68a;
        }

        .error-code {
            font-size: 72px;
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
            margin-bottom: 12px;
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
      <symbol id="icon-cookie" viewBox="0 -960 960 960">
        <path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-75 25.5-142.5T177-742l15 14q28 26 64 39.5t76 8.5q48-6 87.5-31t61.5-65q11-19 28-31t38-16q11-2 21.5-1t21.5 5l26-38q38 12 71.5 32.5T727-679q41 41 67 92.5T829-477l-37 11q-16 5-29 16.5T744-421q-17 32-47.5 50.5T627-352q-48 0-85-30t-48-78h-46q-11 48-48 78t-85 30q-21 0-40.5-6.5T337-328l-25 39q38 23 81.5 36t86.5 13q63 0 119.5-22.5T700-324l36 34q-54 53-124 81.5T480-80Zm-160-320q25 0 42.5-17.5T380-460q0-25-17.5-42.5T320-520q-25 0-42.5 17.5T260-460q0 25 17.5 42.5T320-400Zm240 160q25 0 42.5-17.5T620-300q0-25-17.5-42.5T560-360q-25 0-42.5 17.5T500-300q0 25 17.5 42.5T560-240Zm120-160q25 0 42.5-17.5T740-460q0-25-17.5-42.5T680-520q-25 0-42.5 17.5T620-460q0 25 17.5 42.5T680-400Z"/>
      </symbol>
      <symbol id="icon-refresh" viewBox="0 -960 960 960">
        <path d="M480-160q-134 0-227-93t-93-227q0-134 93-227t227-93q69 0 132 28.5T720-690v-110h80v280H520v-80h168q-32-56-87.5-88T480-720q-100 0-170 70t-70 170q0 100 70 170t170 70q77 0 139-44t87-116h84q-28 106-114 173t-196 67Z"/>
      </symbol>
      <symbol id="icon-home" viewBox="0 -960 960 960">
        <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/>
      </symbol>
    </svg>

    <div class="error-card">
        
        <div class="badge-error">
            <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-cookie"></use></svg>
            <span>خطای ۴۱۹ - انقضای نشست کاربری</span>
        </div>

        <div class="mascot-area">
            <div class="mascot-circle">
                <span class="puppy-avatar">🐶</span>
                <span class="cookie-badge">🍪</span>
                <span class="sparkle-badge">✨</span>
            </div>
        </div>

        <div class="error-code">419</div>

        <h1>نشست کاربری شما منقضی شده است</h1>
        <p class="desc">
            به دلیل عدم فعالیت طولانی، اعتبار امنیتی این صفحه منقضی شده است. با یک تازه‌سازی ساده می‌توانید به ادامه فعالیت خود بپردازید.
        </p>

        <div class="action-row">
            <button onclick="window.location.reload()" class="btn-cookie-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-refresh"></use></svg>
                <span>تازه‌سازی و ادامه</span>
            </button>
            <a href="<?= $base_path ?>/" class="btn-secondary-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-home"></use></svg>
                <span>صفحه اصلی</span>
            </a>
        </div>

    </div>

    <script src="<?= $base_path ?>/assets/js/offline-icons.js"></script>
</body>
</html>
