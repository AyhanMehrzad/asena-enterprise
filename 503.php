<?php
$base_path = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base_path === '/') $base_path = '';
http_response_code(503);
header('Retry-After: 300');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۵۰۳ | در حال به‌روزرسانی - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="<?= $base_path ?>/favicon.ico">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/material-symbols.css">
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
            background: radial-gradient(circle at 40% 40%, #ffffff 0%, #e0f2fe 65%, #bae6fd 100%);
            border: 3px solid #ffffff;
            box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.22), 0 0 0 1px #bae6fd;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .rabbit-avatar {
            font-size: 52px;
            line-height: 1;
            display: block;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.08));
            animation: bunnyFloat 2.8s ease-in-out infinite alternate;
        }

        @keyframes bunnyFloat {
            0% { transform: translateY(0); }
            100% { transform: translateY(-4px); }
        }

        .stethoscope-badge {
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
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
            border: 2px solid #bae6fd;
            animation: stethoWiggle 3s ease-in-out infinite alternate;
        }

        @keyframes stethoWiggle {
            0% { transform: rotate(-6deg); }
            100% { transform: rotate(6deg); }
        }

        .gear-badge {
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
            border: 2px solid #bae6fd;
            animation: spinTool 6s linear infinite;
        }

        @keyframes spinTool {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-code {
            font-size: 72px;
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
            margin-bottom: 22px;
        }

        .info-card {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            padding: 12px 18px;
            margin-bottom: 24px;
            font-size: 13px;
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
            margin-bottom: 12px;
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
      <symbol id="icon-cleaning_services" viewBox="0 -960 960 960">
        <path d="M160-120v-80h640v80H160Zm160-160v-200h80v200h-80Zm240 0v-200h80v200h-80ZM200-560v-80h160v-160q0-33 23.5-56.5T440-880h80q33 0 56.5 23.5T600-800v160h160v80H200Zm240-240h80v-80h-80v80Z"/>
      </symbol>
      <symbol id="icon-schedule" viewBox="0 -960 960 960">
        <path d="m612-292 56-56-148-148v-184h-80v216l172 172ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q133 0 226.5-93.5T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160Z"/>
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
            <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-cleaning_services"></use></svg>
            <span>خطای ۵۰۳ - در حال به‌روزرسانی</span>
        </div>

        <div class="mascot-area">
            <div class="mascot-circle">
                <span class="rabbit-avatar">🐰</span>
                <span class="stethoscope-badge">🩺</span>
                <span class="gear-badge">⚙️</span>
            </div>
        </div>

        <div class="error-code">503</div>

        <h1>کلینیک در حال به‌روزرسانی سرورهاست</h1>
        <p class="desc">
            پزشکان و مهندسان فنی آسنا در حال بهینه‌سازی و ارتقای پایداری زیرساخت هستند. سرویس تا دقایقی دیگر مجدداً در دسترس خواهد بود.
        </p>

        <div class="info-card">
            <span style="display: inline-flex; align-items: center; gap: 6px;">
                <svg class="asena-svg-icon" style="color: #0284c7;" aria-hidden="true"><use href="#icon-schedule"></use></svg>
                وضعیت عملیات:
            </span>
            <span style="font-weight: 800; color: #0284c7;">در حال اعمال آخرین تست‌ها...</span>
        </div>

        <div class="action-row">
            <button onclick="window.location.reload()" class="btn-info-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-refresh"></use></svg>
                <span>بررسی مجدد</span>
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
