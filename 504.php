<?php
$base_path = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base_path === '/') $base_path = '';
http_response_code(504);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۵۰۴ | مهلت زمانی سرور - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="<?= $base_path ?>/favicon.ico">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/material-symbols.css">
    <style>
        :root {
            --primary: #001a48;
            --primary-light: #002d72;
            --accent: #fd8100;
            --warning: #d97706;
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
            box-shadow: 0 25px 60px -15px rgba(217, 119, 6, 0.15), 0 0 0 1px rgba(254, 243, 199, 0.8);
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
            background: linear-gradient(90deg, #d97706, #fd8100, #001a48);
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
            box-shadow: 0 10px 25px -5px rgba(217, 119, 6, 0.22), 0 0 0 1px #fde68a;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .turtle-avatar {
            font-size: 52px;
            line-height: 1;
            display: block;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.08));
            animation: turtleWobble 3s ease-in-out infinite alternate;
        }

        @keyframes turtleWobble {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-4px) rotate(-3deg); }
        }

        .hourglass-badge {
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
            box-shadow: 0 4px 10px rgba(217, 119, 6, 0.25);
            border: 2px solid #fde68a;
            animation: flipHourglass 3.5s ease-in-out infinite;
        }

        @keyframes flipHourglass {
            0%, 40% { transform: rotate(0deg); }
            50%, 90% { transform: rotate(180deg); }
            100% { transform: rotate(360deg); }
        }

        .error-code {
            font-size: 72px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, #d97706, #001a48);
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
            margin-bottom: 22px;
        }

        /* Countdown Card */
        .countdown-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 12px 18px;
            margin-bottom: 24px;
        }

        .countdown-text {
            font-size: 13px;
            font-weight: 800;
            color: #475569;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .progress-bar-bg {
            height: 6px;
            background: #e2e8f0;
            border-radius: 9999px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #fd8100, #d97706);
            width: 100%;
            transition: width 1s linear;
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 12px;
        }

        .btn-warning-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #d97706;
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            padding: 12px 24px;
            border-radius: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.25);
        }

        .btn-warning-custom:hover {
            background: #b45309;
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
      <symbol id="icon-timer" viewBox="0 -960 960 960">
        <path d="M360-840v-80h240v80H360Zm80 440h80v-240h-80v240Zm40 320q-74 0-139.5-28.5T226-186q-49-49-77.5-114.5T120-440q0-74 28.5-139.5T226-694q49-49 114.5-77.5T480-800q62 0 119 20t107 58l56-56 56 56-56 56q38 50 58 107t20 119q0 74-28.5 139.5T734-186q-49 49-114.5 77.5T480-80Zm0-80q116 0 198-82t82-198q0-116-82-198t-198-82q-116 0-198 82t-82 198q0 116 82 198t198 82Zm0-280Z"/>
      </symbol>
      <symbol id="icon-autorenew" viewBox="0 -960 960 960">
        <path d="M204-318q-22-38-33-78t-11-82q0-134 93-228t227-94h7l-64-64 56-56 160 160-160 160-56-56 64-64h-7q-100 0-170 70.5T240-478q0 26 6 51t18 49l-60 60ZM481-40 321-200l160-160 56 56-64 64h7q100 0 170-70.5T720-482q0-26-6-51t-18-49l60-60q22 38 33 78t11 82q0 134-93 228t-227 94h-7l64 64-56 56Z"/>
      </symbol>
      <symbol id="icon-rocket_launch" viewBox="0 -960 960 960">
        <path d="m226-559 78 33q14-28 29-54t33-52l-56-11-84 84Zm142 83 114 113q42-16 90-49t90-75q70-70 109.5-155.5T806-800q-72-5-158 34.5T492-656q-42 42-75 90t-49 90Zm178-65q-23-23-23-56.5t23-56.5q23-23 57-23t57 23q23 23 23 56.5T660-541q-23 23-57 23t-57-23Zm19 321 84-84-11-56q-26 18-52 32.5T532-299l33 79Zm313-653q19 121-23.5 235.5T708-419l20 99q4 20-2 39t-20 33L538-80l-84-197-171-171-197-84 167-168q14-14 33.5-20t39.5-2l99 20q104-104 218-147t235-24ZM157-321q35-35 85.5-35.5T328-322q35 35 34.5 85.5T327-151q-25 25-83.5 43T82-76q14-103 32-161.5t43-83.5Zm57 56q-10 10-20 36.5T180-175q27-4 53.5-13.5T270-208q12-12 13-29t-11-29q-12-12-29-11.5T214-265Z"/>
      </symbol>
      <symbol id="icon-home" viewBox="0 -960 960 960">
        <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/>
      </symbol>
    </svg>

    <div class="error-card">
        
        <div class="badge-error">
            <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-timer"></use></svg>
            <span>خطای ۵۰۴ - مهلت زمانی سرور</span>
        </div>

        <div class="mascot-area">
            <div class="mascot-circle">
                <span class="turtle-avatar" id="turtleMascot">🐢</span>
                <span class="hourglass-badge">⏳</span>
            </div>
        </div>

        <div class="error-code">504</div>

        <h1>لاک‌پشت نامه‌رسان آسنا در ترافیک مونده!</h1>
        <p class="desc">
            سرور درگاه ارتباطی بیش از حد معطل پاسخ ماند و مهلت اتصال به پایان رسید. به احتمال زیاد ترافیک لحظه‌ای شبکه بالاست یا سرور در حال پردازش یک عملیات سنگین است.
        </p>

        <!-- Auto Countdown -->
        <div class="countdown-card">
            <div class="countdown-text">
                <svg class="asena-svg-icon" style="color: #d97706;" aria-hidden="true"><use href="#icon-autorenew"></use></svg>
                <span>تلاش خودکار مجدد در <b id="countdownSec" style="color: #d97706; font-size: 15px;">10</b> ثانیه دیگر...</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="progressFill"></div>
            </div>
        </div>

        <div class="action-row">
            <button onclick="instantRetry()" class="btn-warning-custom" id="turboBtn">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-rocket_launch"></use></svg>
                <span>تلاش مجدد فوری (شلیک توربو)</span>
            </button>
            <a href="<?= $base_path ?>/" class="btn-secondary-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-home"></use></svg>
                <span>صفحه اصلی</span>
            </a>
        </div>

    </div>

    <script src="<?= $base_path ?>/assets/js/offline-icons.js"></script>
    <script>
        let timeLeft = 10;
        const countdownEl = document.getElementById('countdownSec');
        const progressEl = document.getElementById('progressFill');

        const interval = setInterval(() => {
            timeLeft--;
            if (countdownEl) countdownEl.innerText = timeLeft;
            if (progressEl) progressEl.style.width = (timeLeft * 10) + '%';

            if (timeLeft <= 0) {
                clearInterval(interval);
                instantRetry();
            }
        }, 1000);

        function instantRetry() {
            clearInterval(interval);
            const btn = document.getElementById('turboBtn');
            const turtle = document.getElementById('turtleMascot');
            if (turtle) turtle.style.transform = 'scale(1.25) translateX(-8px)';
            if (btn) {
                btn.innerHTML = '<svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-rocket_launch"></use></svg><span>در حال اتصال توربو...</span>';
                btn.disabled = true;
            }
            setTimeout(() => {
                window.location.reload();
            }, 600);
        }
    </script>
</body>
</html>
