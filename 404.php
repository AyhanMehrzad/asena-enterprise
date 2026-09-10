<?php
$base_path = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base_path === '/') $base_path = '';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۴۰۴ | صفحه پیدا نشد - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="<?= $base_path ?>/favicon.ico">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="<?= $base_path ?>/assets/css/material-symbols.css">
    <style>
        :root {
            --primary: #001a48;
            --primary-light: #002d72;
            --accent: #fd8100;
            --accent-light: #ffedd5;
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
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 50%, #e2e8f0 100%);
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

        /* Floating background paw decorations */
        .bg-paw {
            position: absolute;
            font-size: 32px;
            opacity: 0.12;
            user-select: none;
            pointer-events: none;
            animation: floatPaw 6s ease-in-out infinite alternate;
        }
        .bg-paw.p1 { top: 10%; left: 8%; animation-delay: 0s; font-size: 40px; }
        .bg-paw.p2 { bottom: 15%; left: 12%; animation-delay: 1.5s; }
        .bg-paw.p3 { top: 15%; right: 10%; animation-delay: 2.5s; font-size: 48px; }
        .bg-paw.p4 { bottom: 12%; right: 8%; animation-delay: 3.5s; }

        @keyframes floatPaw {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-20px) rotate(15deg); }
        }

        .error-card {
            background: #ffffff;
            border-radius: 32px;
            box-shadow: 0 25px 60px -15px rgba(0, 26, 72, 0.12), 0 0 0 1px rgba(226, 232, 240, 0.8);
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
            background: linear-gradient(90deg, #fd8100, #002d72, #0284c7);
        }

        .badge-error {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
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
            background: radial-gradient(circle at 40% 40%, #ffffff 0%, #ffedd5 65%, #fed7aa 100%);
            border: 3px solid #ffffff;
            box-shadow: 0 10px 25px -5px rgba(253, 129, 0, 0.22), 0 0 0 1px #fed7aa;
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
            animation: puppySniff 3s ease-in-out infinite;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes puppySniff {
            0%, 100% { transform: rotate(0deg) translateY(0); }
            25% { transform: rotate(-6deg) translateY(-3px); }
            75% { transform: rotate(6deg) translateY(-2px); }
        }

        .magnifier-badge {
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
            box-shadow: 0 4px 10px rgba(253, 129, 0, 0.25);
            border: 2px solid #fed7aa;
            animation: scanTrace 2.4s ease-in-out infinite alternate;
        }

        @keyframes scanTrace {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-15deg); }
        }

        .error-code {
            font-size: 72px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 21px;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 8px;
        }

        p.desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        /* Interactive Whistle Button */
        .whistle-btn {
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            color: var(--primary);
            font-size: 12.5px;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 9999px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 22px;
            transition: all 0.2s;
        }

        .whistle-btn:hover {
            background: #ffedd5;
            border-color: #fd8100;
            color: #c2410c;
            transform: scale(1.04);
        }

        /* Search Box */
        .search-form {
            position: relative;
            max-width: 440px;
            margin: 0 auto 22px;
        }

        .search-input {
            width: 100%;
            padding: 12px 42px 12px 16px;
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .search-input:focus {
            border-color: var(--accent);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(253, 129, 0, 0.15);
        }

        .search-icon-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 0;
        }

        /* Action Buttons */
        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary);
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            padding: 12px 24px;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(0, 26, 72, 0.25);
        }

        .btn-primary-custom:hover {
            background: var(--primary-light);
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
            cursor: pointer;
        }

        .btn-secondary-custom:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        /* Quick Links */
        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            padding-top: 16px;
            border-top: 1px dashed #e2e8f0;
        }

        .quick-link {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-decoration: none;
            background: #f8fafc;
            padding: 6px 14px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .quick-link:hover {
            color: var(--accent);
            border-color: #fed7aa;
            background: #fff7ed;
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
      <symbol id="icon-search_off" viewBox="0 -960 960 960">
        <path d="M280-80q-83 0-141.5-58.5T80-280q0-83 58.5-141.5T280-480q83 0 141.5 58.5T480-280q0 83-58.5 141.5T280-80Zm544-40L568-376q-12-13-25.5-26.5T516-428q38-24 61-64t23-88q0-75-52.5-127.5T420-760q-75 0-127.5 52.5T240-580q0 6 .5 11.5T242-557q-18 2-39.5 8T164-535q-2-11-3-22t-1-23q0-109 75.5-184.5T420-840q109 0 184.5 75.5T680-580q0 43-13.5 81.5T629-428l251 252-56 56Zm-615-61 71-71 70 71 29-28-71-71 71-71-28-28-71 71-71-71-28 28 71 71-71 71 28 28Z"/>
      </symbol>
      <symbol id="icon-search" viewBox="0 -960 960 960">
        <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56Zm380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
      </symbol>
      <symbol id="icon-home" viewBox="0 -960 960 960">
        <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z"/>
      </symbol>
      <symbol id="icon-arrow_back" viewBox="0 -960 960 960">
        <path d="m313-440 224 224-57 56-320-320 320-320 57 56-224 224h487v80H313Z"/>
      </symbol>
    </svg>

    <div class="bg-paw p1">🐾</div>
    <div class="bg-paw p2">🐾</div>
    <div class="bg-paw p3">🐾</div>
    <div class="bg-paw p4">🐾</div>

    <div class="error-card">
        
        <div class="badge-error">
            <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-search_off"></use></svg>
            <span>خطای ۴۰۴ - صفحه پیدا نشد</span>
        </div>

        <div class="mascot-area">
            <div class="mascot-circle">
                <span class="puppy-avatar" id="puppyMascot">🐕</span>
                <span class="magnifier-badge">🔍</span>
            </div>
        </div>

        <div class="error-code">404</div>

        <h1>هاپو این صفحه رو قایم کرده!</h1>
        <p class="desc">
            سگ‌های جستجوگر آسنا تمام کلینیک رو گشتند اما صفحه‌ای با این آدرس پیدا نشد! احتمالاً صفحه جابه‌جا شده یا آدرس اشتباه وارد شده است.
        </p>

        <!-- Whistle Button -->
        <button class="whistle-btn" onclick="whistleForPet()">
            <span>📣</span>
            <span>سوت زدن برای صدا کردن هاپو!</span>
        </button>

        <!-- Quick Search -->
        <form action="<?= $base_path ?>/shop.php" method="GET" class="search-form">
            <button type="submit" class="search-icon-btn" aria-label="جستجو">
                <svg class="asena-svg-icon" style="color: #94a3b8;" aria-hidden="true"><use href="#icon-search"></use></svg>
            </button>
            <input type="text" name="q" placeholder="جستجوی محصول، خدمات یا نام کلینیک..." class="search-input" required>
        </form>

        <!-- Main Actions -->
        <div class="action-row">
            <a href="<?= $base_path ?>/" class="btn-primary-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-home"></use></svg>
                <span>صفحه اصلی</span>
            </a>
            <button onclick="window.history.back()" class="btn-secondary-custom">
                <svg class="asena-svg-icon" aria-hidden="true"><use href="#icon-arrow_back"></use></svg>
                <span>بازگشت</span>
            </button>
        </div>

        <!-- Helpful Quick Links -->
        <div class="quick-links">
            <a href="<?= $base_path ?>/booking.php" class="quick-link">🩺 نوبت‌دهی آنلاین</a>
            <a href="<?= $base_path ?>/shop.php" class="quick-link">🛍️ پت‌شاپ و محصولات</a>
            <a href="<?= $base_path ?>/organizations.php" class="quick-link">🏥 کلینیک‌ها و بیمارستان‌ها</a>
            <a href="<?= $base_path ?>/knowledge_base.php" class="quick-link">📚 دانشنامه سلامت پت</a>
        </div>

    </div>

    <script src="<?= $base_path ?>/assets/js/offline-icons.js"></script>
    <script>
        // Web Audio Whistle / Bark Synthesizer
        let audioCtx = null;
        function whistleForPet() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!audioCtx && AudioContext) audioCtx = new AudioContext();
                if (audioCtx.state === 'suspended') audioCtx.resume();

                // Whistle frequency ramp
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(1400, audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(2200, audioCtx.currentTime + 0.2);
                osc.frequency.exponentialRampToValueAtTime(1600, audioCtx.currentTime + 0.35);

                gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.38);

                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.4);

                // Puppy jump animation
                const mascot = document.getElementById('puppyMascot');
                mascot.style.transform = 'scale(1.25) rotate(-10deg)';
                mascot.innerText = '🐶';
                setTimeout(() => {
                    mascot.style.transform = 'scale(1) rotate(0deg)';
                    setTimeout(() => { mascot.innerText = '🐕'; }, 400);
                }, 300);
            } catch(e){}
        }
    </script>
</body>
</html>
