<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>۴۰۴ | هاپو این صفحه رو قایم کرده! - آسنا</title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/vazirmatn.css">
    <link rel="stylesheet" href="/assets/css/material-symbols.css">
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
            margin-bottom: 16px;
        }

        /* Animated Puppy Searching Visual */
        .mascot-area {
            position: relative;
            width: 140px;
            height: 120px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .puppy-box {
            font-size: 64px;
            display: inline-block;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: puppySniff 3s ease-in-out infinite;
        }

        @keyframes puppySniff {
            0%, 100% { transform: rotate(0deg) translateY(0); }
            25% { transform: rotate(-8deg) translateY(-4px); }
            75% { transform: rotate(8deg) translateY(-2px); }
        }

        .magnifier {
            position: absolute;
            bottom: 12px;
            right: 18px;
            font-size: 32px;
            animation: scanTrace 2.4s ease-in-out infinite alternate;
        }

        @keyframes scanTrace {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-30px, -15px) rotate(-25deg); }
        }

        /* 404 Big Display */
        .error-code {
            font-size: 76px;
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
            margin-bottom: 24px;
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
            margin-bottom: 24px;
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
            margin: 0 auto 24px;
        }

        .search-input {
            width: 100%;
            padding: 13px 44px 13px 18px;
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
            color: #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 20px;
        }

        /* Action Buttons */
        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 24px;
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
            padding-top: 18px;
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
    </style>
</head>
<body>

    <div class="bg-paw p1">🐾</div>
    <div class="bg-paw p2">🐾</div>
    <div class="bg-paw p3">🐾</div>
    <div class="bg-paw p4">🐾</div>

    <div class="error-card">
        
        <div class="badge-error">
            <span class="material-symbols-outlined" style="font-size: 16px;">search_off</span>
            <span>خطای ۴۰۴ - صفحه یافت نشد</span>
        </div>

        <div class="mascot-area">
            <div class="puppy-box" id="puppyMascot">🐕</div>
            <div class="magnifier">🔍</div>
        </div>

        <div class="error-code">404</div>

        <h1>هاپو این صفحه رو قایم کرده!</h1>
        <p class="desc">
            سگ‌های جستجوگر آسنا تمام کلینیک و پت‌شاپ رو بو کشیدند، ولی آدرسی که وارد کردید پیدا نشد! ممکنه این صفحه حذف شده یا آدرسش تغییر کرده باشه.
        </p>

        <!-- Whistle Button -->
        <button class="whistle-btn" onclick="whistleForPet()">
            <span>📣</span>
            <span>سوت زدن برای صدا کردن هاپو!</span>
        </button>

        <!-- Quick Search -->
        <form action="/shop.php" method="GET" class="search-form">
            <button type="submit" class="search-icon-btn">
                <span class="material-symbols-outlined">search</span>
            </button>
            <input type="text" name="q" placeholder="جستجوی محصول، خدمات یا نام کلینیک..." class="search-input" required>
        </form>

        <!-- Main Actions -->
        <div class="action-row">
            <a href="/" class="btn-primary-custom">
                <span class="material-symbols-outlined">home</span>
                صفحه اصلی آسنا
            </a>
            <button onclick="window.history.back()" class="btn-secondary-custom">
                <span class="material-symbols-outlined">arrow_back</span>
                بازگشت به صفحه قبل
            </button>
        </div>

        <!-- Helpful Quick Links -->
        <div class="quick-links">
            <a href="/booking.php" class="quick-link">🩺 نوبت‌دهی آنلاین</a>
            <a href="/shop.php" class="quick-link">🛍️ پت‌شاپ و محصولات</a>
            <a href="/organizations.php" class="quick-link">🏥 کلینیک‌ها و بیمارستان‌ها</a>
            <a href="/knowledge_base.php" class="quick-link">📚 دانشنامه سلامت پت</a>
        </div>

    </div>

    <script src="/assets/js/offline-icons.js"></script>
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
                mascot.style.transform = 'scale(1.35) rotate(-15deg)';
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
