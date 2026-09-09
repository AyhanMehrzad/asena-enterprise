<?php
http_response_code(504);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۵۰۴ | لاک‌پشت نامه‌رسان در ترافیک مونده! - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="/assets/css/material-symbols.css">
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

        .turtle-box {
            font-size: 64px;
            display: inline-block;
            animation: slowTurtle 4s ease-in-out infinite alternate;
        }

        @keyframes slowTurtle {
            0% { transform: translateX(14px) rotate(0deg); }
            100% { transform: translateX(-14px) rotate(-3deg); }
        }

        .hourglass-box {
            position: absolute;
            top: 6px;
            right: 12px;
            font-size: 26px;
            animation: flipHourglass 3s ease-in-out infinite;
        }

        @keyframes flipHourglass {
            0%, 40% { transform: rotate(0deg); }
            50%, 90% { transform: rotate(180deg); }
            100% { transform: rotate(360deg); }
        }

        .error-code {
            font-size: 76px;
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
            margin-bottom: 24px;
        }

        /* Countdown Card */
        .countdown-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 14px 20px;
            margin-bottom: 24px;
        }

        .countdown-text {
            font-size: 13px;
            font-weight: 800;
            color: #475569;
            margin-bottom: 10px;
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
            margin-bottom: 20px;
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
    </style>
</head>
<body>

    <div class="error-card">
        
        <div class="badge-error">
            <span class="material-symbols-outlined" style="font-size: 16px;">timer</span>
            <span>خطای ۵۰۴ - مهلت زمانی سرور (Gateway Timeout)</span>
        </div>

        <div class="mascot-area">
            <div class="turtle-box" id="turtleMascot">🐢</div>
            <div class="hourglass-box">⏳</div>
        </div>

        <div class="error-code">504</div>

        <h1>لاک‌پشت نامه‌رسان آسنا در ترافیک مونده!</h1>
        <p class="desc">
            سرور درگاه ارتباطی بیش از حد معطل پاسخ ماند و مهلت اتصال به پایان رسید. به احتمال زیاد ترافیک لحظه‌ای شبکه بالاست یا سرور در حال پردازش یک عملیات سنگین است.
        </p>

        <!-- Auto Countdown -->
        <div class="countdown-card">
            <div class="countdown-text">
                <span class="material-symbols-outlined" style="font-size: 18px; color: #d97706;">autorenew</span>
                <span>تلاش خودکار مجدد در <b id="countdownSec" style="color: #d97706; font-size: 15px;">10</b> ثانیه دیگر...</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="progressFill"></div>
            </div>
        </div>

        <div class="action-row">
            <button onclick="instantRetry()" class="btn-warning-custom" id="turboBtn">
                <span>🚀</span>
                <span>شلیک توربو به لاک‌پشت (تلاش مجدد فوری)</span>
            </button>
            <a href="/" class="btn-secondary-custom">
                <span class="material-symbols-outlined">home</span>
                صفحه اصلی
            </a>
        </div>

    </div>

    <script src="/assets/js/offline-icons.js"></script>
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
            if (turtle) turtle.style.transform = 'scale(1.3) translateX(-30px)';
            if (btn) {
                btn.innerHTML = '<span>🚀</span> در حال برقراری اتصال توربو...';
                btn.disabled = true;
            }
            setTimeout(() => {
                window.location.reload();
            }, 600);
        }
    </script>
</body>
</html>
