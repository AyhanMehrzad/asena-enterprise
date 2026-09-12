<?php
/**
 * ASENA Enterprise Progressive Web App (PWA) Starter Page & Mobile App Shell
 * Version: 2.0.0
 * Ultra-Responsive, Zero-FOUC Native-Grade Veterinary & Pet Commerce Launcher
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fast non-blocking DB probe (max 0.2s) to guarantee zero-latency PWA launch
$pdo = null;
$socket = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 0.2);
if ($socket) {
    fclose($socket);
    $dbFiles = [
        __DIR__ . '/includes/db.php'
    ];
    foreach ($dbFiles as $df) {
        if (file_exists($df)) {
            @include_once $df;
            if (isset($pdo) && $pdo instanceof PDO) {
                break;
            }
        }
    }
}

// Fetch Featured Products
$featuredProducts = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, name, category, price, discount_price, image_url, brand FROM products WHERE stock > 0 ORDER BY id DESC LIMIT 6");
        $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Fallback curated products if database is empty or offline
if (empty($featuredProducts)) {
    $featuredProducts = [
        [
            'id' => 1,
            'name' => 'غذای خشک سگ بالغ نژاد متوسط رویال کنین',
            'category' => 'غذای سگ',
            'price' => 1450000,
            'discount_price' => 1280000,
            'image_url' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=400&auto=format&fit=crop&q=80',
            'brand' => 'Royal Canin'
        ],
        [
            'id' => 2,
            'name' => 'خمیر مالت ضد گلوله مویی (هربال) گربه جیم کت',
            'category' => 'مکمل گربه',
            'price' => 460000,
            'discount_price' => 390000,
            'image_url' => 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=400&auto=format&fit=crop&q=80',
            'brand' => 'GimCat'
        ],
        [
            'id' => 3,
            'name' => 'قرص غضروف‌ساز و مفاصل سگ آرتروفلکس پلاس',
            'category' => 'دارو و مکمل',
            'price' => 780000,
            'discount_price' => 690000,
            'image_url' => 'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?w=400&auto=format&fit=crop&q=80',
            'brand' => 'Beaphar'
        ],
        [
            'id' => 4,
            'name' => 'خاک بستر سوپر کلمپینگ معطر گربه پیست بیبی',
            'category' => 'بهداشت گربه',
            'price' => 290000,
            'discount_price' => 245000,
            'image_url' => 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=400&auto=format&fit=crop&q=80',
            'brand' => 'Best Clean'
        ]
    ];
}

// User state
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['name']) ? $_SESSION['name'] : 'کاربر گرامی');
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['id']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>آسنا | اپلیکیشن هوشمند سلامت حیوانات و خدمات دامپزشکی</title>
    
    <!-- PWA & Mobile Meta Tags -->
    <meta name="theme-color" content="#002d72">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ASENA">
    <meta name="application-name" content="ASENA">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    
    <link rel="manifest" href="site.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/images/pwa-icon-192.png">
    <link rel="apple-touch-icon" href="assets/images/pwa-icon-512.png">
    <link rel="shortcut icon" href="favicon.ico">
    
    <!-- Fonts & Icons (Self-Hosted Local) -->
    <link rel="stylesheet" href="assets/css/vazirmatn.css">
    <link rel="stylesheet" href="assets/css/material-symbols.css">
    
    <style>
        :root {
            --primary: #002d72;
            --primary-light: #0284c7;
            --primary-gradient: linear-gradient(135deg, #001e4d 0%, #002d72 50%, #0284c7 100%);
            --accent: #ea580c;
            --accent-light: #f97316;
            --teal: #0d9488;
            --emerald: #10b981;
            --surface: #ffffff;
            --surface-glass: rgba(255, 255, 255, 0.88);
            --surface-subtle: #f8fafc;
            --surface-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-light: rgba(226, 232, 240, 0.85);
            --shadow-sm: 0 2px 8px -2px rgba(0, 45, 114, 0.08);
            --shadow-md: 0 10px 25px -5px rgba(0, 45, 114, 0.12), 0 8px 10px -6px rgba(0, 45, 114, 0.06);
            --shadow-lg: 0 20px 35px -8px rgba(0, 45, 114, 0.18);
            --radius-sm: 12px;
            --radius-md: 18px;
            --radius-lg: 24px;
            --radius-full: 9999px;
            --dock-height: 68px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --surface: #0b1329;
                --surface-glass: rgba(15, 23, 42, 0.92);
                --surface-subtle: #0f172a;
                --surface-card: #1e293b;
                --text-main: #f8fafc;
                --text-muted: #94a3b8;
                --border-light: rgba(51, 65, 85, 0.7);
                --shadow-sm: 0 2px 8px -2px rgba(0, 0, 0, 0.3);
                --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: var(--surface-subtle);
            color: var(--text-main);
            min-height: 100vh;
            min-height: 100dvh;
            padding-top: env(safe-area-inset-top, 0px);
            padding-bottom: calc(var(--dock-height) + env(safe-area-inset-bottom, 16px) + 24px);
            overflow-x: hidden;
            line-height: 1.5;
            user-select: none;
            -webkit-user-select: none;
        }

        /* -------------------------------------------------------------
           1. In-App Splash Screen Overlay (Seamless Zero-FOUC Transition)
           ------------------------------------------------------------- */
        #pwa-splash {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            height: 100dvh;
            background: linear-gradient(175deg, #00173d 0%, #002d72 55%, #001333 100%);
            z-index: 999999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
            color: #ffffff;
            transition: opacity 0.45s cubic-bezier(0.4, 0, 0.2, 1), transform 0.45s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.45s;
        }

        #pwa-splash.splash-dismissed {
            opacity: 0;
            visibility: hidden;
            transform: scale(1.04);
            pointer-events: none;
        }

        .splash-emblem-wrap {
            position: relative;
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .splash-pulse-ring {
            position: absolute;
            width: 140px;
            height: 140px;
            border-radius: 38px;
            border: 2px solid rgba(56, 189, 248, 0.4);
            animation: splashPulse 2.2s ease-in-out infinite;
        }

        .splash-pulse-ring-2 {
            position: absolute;
            width: 164px;
            height: 164px;
            border-radius: 46px;
            border: 1px dashed rgba(255, 255, 255, 0.2);
            animation: splashRotate 18s linear infinite;
        }

        .splash-badge {
            width: 108px;
            height: 108px;
            border-radius: 32px;
            background: linear-gradient(145deg, #003380, #001a47);
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45), 0 0 24px rgba(56, 189, 248, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .splash-badge img {
            width: 72px;
            height: 72px;
            object-fit: contain;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.3));
            animation: pawHeartbeat 1.8s ease-in-out infinite;
        }

        .splash-title {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.5px;
            background: linear-gradient(90deg, #ffffff 0%, #bae6fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
        }

        .splash-sub {
            font-size: 13px;
            font-weight: 400;
            color: rgba(224, 242, 254, 0.85);
            text-align: center;
            max-width: 260px;
            line-height: 1.6;
        }

        .splash-loader-bar {
            width: 180px;
            height: 4px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.15);
            margin-top: 36px;
            overflow: hidden;
            position: relative;
        }

        .splash-loader-bar-inner {
            width: 45%;
            height: 100%;
            background: linear-gradient(90deg, #0284c7, #38bdf8, #ea580c);
            border-radius: 4px;
            position: absolute;
            animation: splashProgress 1.4s ease-in-out infinite alternate;
        }

        .splash-status-text {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 10px;
        }

        @keyframes splashPulse {
            0% { transform: scale(0.92); opacity: 0.6; }
            50% { transform: scale(1.08); opacity: 1; border-color: rgba(56, 189, 248, 0.8); }
            100% { transform: scale(0.92); opacity: 0.6; }
        }

        @keyframes splashRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pawHeartbeat {
            0%, 100% { transform: scale(1); }
            20% { transform: scale(1.07); }
            35% { transform: scale(0.98); }
            50% { transform: scale(1.04); }
        }

        @keyframes splashProgress {
            0% { left: -30%; width: 30%; }
            100% { left: 100%; width: 50%; }
        }

        /* -------------------------------------------------------------
           2. App Header & Status Bar
           ------------------------------------------------------------- */
        .app-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: var(--surface-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-light);
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .app-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
        }

        .app-brand-badge {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 45, 114, 0.25);
        }

        .app-brand-badge img {
            width: 24px;
            height: 24px;
            object-fit: contain;
        }

        .app-brand-text {
            display: flex;
            flex-direction: column;
        }

        .app-brand-title {
            font-size: 16px;
            font-weight: 900;
            color: var(--primary);
            line-height: 1.2;
        }

        @media (prefers-color-scheme: dark) {
            .app-brand-title { color: #38bdf8; }
        }

        .app-brand-desc {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .app-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            text-decoration: none;
            transition: all 0.2s;
        }

        .header-icon-btn:active {
            transform: scale(0.92);
        }

        .header-badge-dot {
            position: absolute;
            top: 7px;
            left: 7px;
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            border: 2px solid var(--surface);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: var(--radius-full);
            background: rgba(2, 132, 199, 0.1);
            color: var(--primary-light);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid rgba(2, 132, 199, 0.2);
        }

        /* -------------------------------------------------------------
           3. Pet Profile Switcher Banner
           ------------------------------------------------------------- */
        .pet-bar-container {
            padding: 14px 18px 6px;
        }

        .pet-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none;
            padding-bottom: 6px;
        }

        .pet-selector::-webkit-scrollbar {
            display: none;
        }

        .pet-chip {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: var(--radius-full);
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
        }

        .pet-chip.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 4px 14px rgba(0, 45, 114, 0.25);
        }

        .pet-chip:active {
            transform: scale(0.95);
        }

        .pet-chip-add {
            border-style: dashed;
            color: var(--primary-light);
            background: transparent;
        }

        /* -------------------------------------------------------------
           4. Smart Search & Emergency Alert
           ------------------------------------------------------------- */
        .search-section {
            padding: 6px 18px 14px;
        }

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input {
            width: 100%;
            height: 48px;
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 0 46px 0 16px;
            font-size: 13px;
            color: var(--text-main);
            outline: none;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s;
        }

        .search-input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
        }

        .search-icon {
            position: absolute;
            right: 14px;
            color: var(--text-muted);
            pointer-events: none;
            font-size: 22px;
        }

        .emergency-strip {
            margin: 0 18px 16px;
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
            color: #ffffff;
            border-radius: var(--radius-md);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 8px 20px -4px rgba(220, 38, 38, 0.35);
            text-decoration: none;
        }

        .emergency-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .emergency-icon-wrap {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            animation: emergencyPulse 1.6s ease-in-out infinite;
        }

        @keyframes emergencyPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.12); }
        }

        .emergency-title {
            font-size: 13px;
            font-weight: 800;
        }

        .emergency-sub {
            font-size: 10.5px;
            opacity: 0.9;
        }

        .emergency-btn {
            background: #ffffff;
            color: #b91c1c;
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-size: 11px;
            font-weight: 900;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* -------------------------------------------------------------
           5. Super-App 6-Module Grid (Primary Launch Actions)
           ------------------------------------------------------------- */
        .modules-section {
            padding: 0 18px 20px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-more-link {
            font-size: 12px;
            color: var(--primary-light);
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .module-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 16px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-decoration: none;
            color: var(--text-main);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .module-card:active {
            transform: scale(0.96);
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 3.5px;
            background: transparent;
        }

        .card-clinic::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
        .card-shop::before { background: linear-gradient(90deg, #ea580c, #f97316); }
        .card-pharma::before { background: linear-gradient(90deg, #0d9488, #14b8a6); }
        .card-autoship::before { background: linear-gradient(90deg, #8b5cf6, #a855f7); }
        .card-records::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .card-hotline::before { background: linear-gradient(90deg, #f43f5e, #fb7185); }

        .module-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .card-clinic .module-icon-wrap { background: #eff6ff; color: #0284c7; }
        .card-shop .module-icon-wrap { background: #fff7ed; color: #ea580c; }
        .card-pharma .module-icon-wrap { background: #f0fdfa; color: #0d9488; }
        .card-autoship .module-icon-wrap { background: #faf5ff; color: #8b5cf6; }
        .card-records .module-icon-wrap { background: #ecfdf5; color: #10b981; }
        .card-hotline .module-icon-wrap { background: #fff1f2; color: #f43f5e; }

        @media (prefers-color-scheme: dark) {
            .card-clinic .module-icon-wrap { background: rgba(2, 132, 199, 0.18); }
            .card-shop .module-icon-wrap { background: rgba(234, 88, 12, 0.18); }
            .card-pharma .module-icon-wrap { background: rgba(13, 148, 136, 0.18); }
            .card-autoship .module-icon-wrap { background: rgba(139, 92, 246, 0.18); }
            .card-records .module-icon-wrap { background: rgba(16, 185, 129, 0.18); }
            .card-hotline .module-icon-wrap { background: rgba(244, 63, 94, 0.18); }
        }

        .module-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            font-size: 9.5px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: var(--radius-full);
            background: rgba(2, 132, 199, 0.1);
            color: var(--primary-light);
        }

        .module-title {
            font-size: 13.5px;
            font-weight: 800;
            line-height: 1.3;
        }

        .module-desc {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* -------------------------------------------------------------
           6. Active Widget: Health Tracker & Appointment
           ------------------------------------------------------------- */
        .widget-section {
            padding: 0 18px 20px;
        }

        .tracker-card {
            background: linear-gradient(135deg, #002257 0%, #002d72 100%);
            color: #ffffff;
            border-radius: var(--radius-lg);
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .tracker-card::after {
            content: '';
            position: absolute;
            left: -20px;
            bottom: -20px;
            width: 110px;
            height: 110px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.25) 0%, transparent 70%);
            pointer-events: none;
        }

        .tracker-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .tracker-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(255, 255, 255, 0.18);
            font-size: 10.5px;
            font-weight: 700;
        }

        .tracker-body {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .tracker-avatar {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .tracker-main-text h4 {
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .tracker-main-text p {
            font-size: 11.5px;
            opacity: 0.85;
        }

        .tracker-action-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .tracker-date {
            font-size: 11px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .tracker-btn {
            background: #ffffff;
            color: var(--primary);
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-size: 11.5px;
            font-weight: 800;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* -------------------------------------------------------------
           7. Featured Products Carousel / Grid
           ------------------------------------------------------------- */
        .products-section {
            padding: 0 18px 24px;
        }

        .products-carousel {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            scrollbar-width: none;
            padding: 4px 2px 10px;
        }

        .products-carousel::-webkit-scrollbar {
            display: none;
        }

        .product-mini-card {
            flex: 0 0 155px;
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.2s;
        }

        .product-mini-card:active {
            transform: scale(0.96);
        }

        .product-img-wrap {
            width: 100%;
            height: 125px;
            background: #f1f5f9;
            position: relative;
            overflow: hidden;
        }

        .product-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-badge-pill {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(234, 88, 12, 0.9);
            color: #ffffff;
            font-size: 9px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: var(--radius-full);
        }

        .product-content {
            padding: 10px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        .product-name {
            font-size: 11.5px;
            font-weight: 700;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 8px;
            height: 32px;
        }

        .product-price-row {
            display: flex;
            flex-direction: column;
        }

        .product-price-old {
            font-size: 10px;
            color: var(--text-muted);
            text-decoration: line-through;
        }

        .product-price-curr {
            font-size: 12.5px;
            font-weight: 900;
            color: var(--primary);
        }

        @media (prefers-color-scheme: dark) {
            .product-price-curr { color: #38bdf8; }
        }

        /* -------------------------------------------------------------
           8. Switch to Full Web & PWA Health Indicator
           ------------------------------------------------------------- */
        .footer-cta-section {
            padding: 0 18px 20px;
            text-align: center;
        }

        .desktop-switch-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: var(--radius-full);
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-decoration: none;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
        }

        .desktop-switch-btn:active {
            background: rgba(2, 132, 199, 0.08);
            color: var(--primary-light);
        }

        .pwa-health-badge {
            margin-top: 14px;
            font-size: 10.5px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .health-dot {
            width: 7px;
            height: 7px;
            background: var(--emerald);
            border-radius: 50%;
        }

        /* -------------------------------------------------------------
           9. Bottom Floating App Navigation Dock
           ------------------------------------------------------------- */
        .app-dock {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(var(--dock-height) + env(safe-area-inset-bottom, 0px));
            padding-bottom: env(safe-area-inset-bottom, 0px);
            background: var(--surface-glass);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-top: 1px solid var(--border-light);
            z-index: 9000;
            display: flex;
            align-items: center;
            justify-content: space-around;
            box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.06);
        }

        .dock-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 10.5px;
            font-weight: 700;
            padding: 8px 0;
            transition: all 0.2s;
            position: relative;
        }

        .dock-item .material-symbols-outlined {
            font-size: 23px;
            transition: transform 0.2s;
        }

        .dock-item.active {
            color: var(--primary);
        }

        @media (prefers-color-scheme: dark) {
            .dock-item.active { color: #38bdf8; }
        }

        .dock-item.active .material-symbols-outlined {
            transform: translateY(-2px);
            font-variation-settings: 'FILL' 1;
        }

        .dock-item:active {
            transform: scale(0.92);
        }

        /* Offline Banner */
        #offline-banner {
            display: none;
            background: #e11d48;
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 700;
            text-align: center;
            padding: 6px 12px;
            position: sticky;
            top: 62px;
            z-index: 999;
        }
    </style>
</head>
<body>

    <!-- 1. IN-APP INSTANT SPLASH OVERLAY (Guarantees Seamless Transition from Android WebAPK) -->
    <div id="pwa-splash" aria-hidden="true">
        <div class="splash-emblem-wrap">
            <div class="splash-pulse-ring-2"></div>
            <div class="splash-pulse-ring"></div>
            <div class="splash-badge">
                <img src="assets/images/favicon-512x512.png" alt="ASENA Paw">
            </div>
        </div>
        <div class="splash-title">آسنا | ASENA</div>
        <div class="splash-sub">سامانه جامع کلینیک دامپزشکی، پت‌شاپ و داروخانه تخصصی</div>
        <div class="splash-loader-bar">
            <div class="splash-loader-bar-inner"></div>
        </div>
        <div class="splash-status-text" id="splash-status">آماده‌سازی خدمات و اطلاعات پت...</div>
    </div>

    <!-- Offline Notification Banner -->
    <div id="offline-banner">
        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; margin-left: 4px;">cloud_off</span>
        ارتباط اینترنتی قطع است • در حالت آفلاین PWA مرور می‌کنید
    </div>

    <!-- 2. APP HEADER -->
    <header class="app-header">
        <a href="pwa.php" class="app-brand">
            <div class="app-brand-badge">
                <img src="assets/images/favicon-512x512.png" alt="آسنا">
            </div>
            <div class="app-brand-text">
                <div class="app-brand-title">آسنا • ASENA</div>
                <div class="app-brand-desc">اکوسیستم هوشمند سلامت حیوانات</div>
            </div>
        </a>
        <div class="app-header-actions">
            <?php if ($isLoggedIn): ?>
                <a href="profile.php" class="user-pill">
                    <span class="material-symbols-outlined" style="font-size: 16px;">account_circle</span>
                    <span><?= htmlspecialchars(mb_substr($userName, 0, 10)) ?></span>
                </a>
            <?php else: ?>
                <a href="login.php" class="user-pill">
                    <span class="material-symbols-outlined" style="font-size: 16px;">login</span>
                    <span>ورود / عضویت</span>
                </a>
            <?php endif; ?>
            <a href="notifications.php" class="header-icon-btn" aria-label="پیام‌ها">
                <span class="material-symbols-outlined" style="font-size: 20px;">notifications</span>
                <span class="header-badge-dot"></span>
            </a>
        </div>
    </header>

    <!-- 3. PET SELECTOR CHIPS -->
    <section class="pet-bar-container">
        <div class="pet-selector">
            <div class="pet-chip active">
                <span>🐾</span>
                <span>همه حیوانات</span>
            </div>
            <div class="pet-chip">
                <span>🐶</span>
                <span>سگ من (ژرمن)</span>
            </div>
            <div class="pet-chip">
                <span>🐱</span>
                <span>گربه من (بریتیش)</span>
            </div>
            <div class="pet-chip">
                <span>🐥</span>
                <span>پرنده خانگی</span>
            </div>
            <div class="pet-chip pet-chip-add" onclick="window.location.href='my_pets.php?action=add'">
                <span class="material-symbols-outlined" style="font-size: 14px;">add</span>
                <span>افزودن پت جدید</span>
            </div>
        </div>
    </section>

    <!-- 4. SEARCH BAR -->
    <section class="search-section">
        <div class="search-box">
            <span class="material-symbols-outlined search-icon">search</span>
            <input type="text" class="search-input" id="pwaSearchInput" placeholder="جستجوی خدمات، دکتر، دارو، غذا یا واکسن..." onkeydown="if(event.key==='Enter') doSearch(this.value);">
        </div>
    </section>

    <!-- 5. 24/7 EMERGENCY CALL STRIP -->
    <a href="tel:+989146676978" class="emergency-strip">
        <div class="emergency-info">
            <div class="emergency-icon-wrap">
                <span class="material-symbols-outlined">emergency</span>
            </div>
            <div>
                <div class="emergency-title">اورژانس دامپزشکی شبانه‌روزی (۲۴ ساعته)</div>
                <div class="emergency-sub">مشاوره فوری مسمومیت و اعزام پزشک به محل</div>
            </div>
        </div>
        <div class="emergency-btn">
            <span class="material-symbols-outlined" style="font-size: 15px;">call</span>
            <span>تماس فوری</span>
        </div>
    </a>

    <!-- 6. SUPER-APP 6-ACTION MODULES GRID -->
    <main class="modules-section">
        <div class="section-header">
            <div class="section-title">
                <span class="material-symbols-outlined" style="color: var(--primary-light); font-size: 19px;">grid_view</span>
                <span>خدمات و بخش‌های اصلی سامانه</span>
            </div>
        </div>

        <div class="modules-grid">
            <!-- 1. Clinic Booking -->
            <a href="booking.php" class="module-card card-clinic">
                <span class="module-badge">تخصصی</span>
                <div class="module-icon-wrap">
                    <span class="material-symbols-outlined">calendar_month</span>
                </div>
                <div>
                    <div class="module-title">نوبت‌دهی کلینیک</div>
                    <div class="module-desc">ویزیت حضوری یا آنلاین با دامپزشک</div>
                </div>
            </a>

            <!-- 2. Pet Shop -->
            <a href="shop.php" class="module-card card-shop">
                <span class="module-badge" style="color: var(--accent); background: rgba(234, 88, 12, 0.1);">ارسال سریع</span>
                <div class="module-icon-wrap">
                    <span class="material-symbols-outlined">storefront</span>
                </div>
                <div>
                    <div class="module-title">پت‌شاپ تخصصی</div>
                    <div class="module-desc">خرید غذای خشک، کنسرو و لوازم</div>
                </div>
            </a>

            <!-- 3. Pharmacy -->
            <a href="pharmacy.php" class="module-card card-pharma">
                <span class="module-badge" style="color: var(--teal); background: rgba(13, 148, 136, 0.1);">زنجیره سرد</span>
                <div class="module-icon-wrap">
                    <span class="material-symbols-outlined">medication</span>
                </div>
                <div>
                    <div class="module-title">داروخانه دامپزشکی</div>
                    <div class="module-desc">تامین داروی مجاز و تایید نسخه</div>
                </div>
            </a>

            <!-- 4. Autoship Subscription -->
            <a href="autoship_orders.php" class="module-card card-autoship">
                <span class="module-badge" style="color: #8b5cf6; background: rgba(139, 92, 246, 0.1);">۲۰٪ تخفیف</span>
                <div class="module-icon-wrap">
                    <span class="material-symbols-outlined">autorenew</span>
                </div>
                <div>
                    <div class="module-title">تحویل دوره‌ای (Autoship)</div>
                    <div class="module-desc">ارسال خودکار منظم غذای پت</div>
                </div>
            </a>

            <!-- 5. Digital Health Records -->
            <a href="my_pets.php" class="module-card card-records">
                <span class="module-badge" style="color: var(--emerald); background: rgba(16, 185, 129, 0.1);">شناسنامه</span>
                <div class="module-icon-wrap">
                    <span class="material-symbols-outlined">assignment</span>
                </div>
                <div>
                    <div class="module-title">پرونده سلامت و واکسن</div>
                    <div class="module-desc">کارت دیجیتال واکسیناسیون پت</div>
                </div>
            </a>

            <!-- 6. Vet Knowledge / Hotline -->
            <a href="knowledge_base.php" class="module-card card-hotline">
                <span class="module-badge" style="color: #f43f5e; background: rgba(244, 63, 94, 0.1);">راهنما</span>
                <div class="module-icon-wrap">
                    <span class="material-symbols-outlined">auto_stories</span>
                </div>
                <div>
                    <div class="module-title">پایگاه دانش سلامت</div>
                    <div class="module-desc">مقالات تخصصی تغذیه و درمان</div>
                </div>
            </a>
        </div>
    </main>

    <!-- 7. ACTIVE HEALTH / REMINDER WIDGET -->
    <section class="widget-section">
        <div class="tracker-card">
            <div class="tracker-top">
                <span class="tracker-badge">
                    <span class="material-symbols-outlined" style="font-size: 13px;">alarm</span>
                    یادآور دوره درمان
                </span>
                <span style="font-size: 11px; opacity: 0.85;">ثبت در پرونده الکترونیک</span>
            </div>
            <div class="tracker-body">
                <div class="tracker-avatar">🐶</div>
                <div class="tracker-main-text">
                    <h4>نوبت یادآور واکسن هاری سگ</h4>
                    <p>زمان واکسیناسیون دوره‌ای سالیانه: ۳ روز آینده</p>
                </div>
            </div>
            <div class="tracker-action-row">
                <span class="tracker-date">
                    <span class="material-symbols-outlined" style="font-size: 14px;">event</span>
                    پنجشنبه • ساعت ۱۷:۰۰
                </span>
                <a href="booking.php" class="tracker-btn">
                    <span>رزرو کلینیک</span>
                    <span class="material-symbols-outlined" style="font-size: 14px;">arrow_back</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 8. FEATURED PRODUCTS CAROUSEL -->
    <section class="products-section">
        <div class="section-header">
            <div class="section-title">
                <span class="material-symbols-outlined" style="color: var(--accent); font-size: 19px;">local_fire_department</span>
                <span>کالاهای پرطرفدار پت‌شاپ</span>
            </div>
            <a href="shop.php" class="section-more-link">
                <span>مشاهده همه</span>
                <span class="material-symbols-outlined" style="font-size: 14px;">arrow_back</span>
            </a>
        </div>

        <div class="products-carousel">
            <?php foreach ($featuredProducts as $fp): ?>
                <a href="product_details.php?id=<?= (int)$fp['id'] ?>" class="product-mini-card">
                    <div class="product-img-wrap">
                        <img src="<?= htmlspecialchars($fp['image_url']) ?>" alt="<?= htmlspecialchars($fp['name']) ?>" loading="lazy">
                        <?php if (!empty($fp['discount_price']) && $fp['discount_price'] < $fp['price']): ?>
                            <span class="product-badge-pill">تخفیف</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-content">
                        <div class="product-name"><?= htmlspecialchars($fp['name']) ?></div>
                        <div class="product-price-row">
                            <?php if (!empty($fp['discount_price']) && $fp['discount_price'] < $fp['price']): ?>
                                <span class="product-price-old"><?= number_format($fp['price']) ?> تومان</span>
                                <span class="product-price-curr"><?= number_format($fp['discount_price']) ?> تومان</span>
                            <?php else: ?>
                                <span class="product-price-curr"><?= number_format($fp['price']) ?> تومان</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 9. SWITCH TO FULL DESKTOP WEB -->
    <div class="footer-cta-section">
        <a href="index.php" onclick="sessionStorage.setItem('asena_view_desktop_portal', '1');" class="desktop-switch-btn">
            <span class="material-symbols-outlined" style="font-size: 16px;">desktop_windows</span>
            <span>مشاهده پورتال کامل و دسکتاپ آسنا</span>
        </a>
        <div class="pwa-health-badge">
            <span class="health-dot"></span>
            <span>نسخه اپلیکیشن پیشرو (PWA) فعال است • نسخه ۲.۰</span>
        </div>
    </div>

    <!-- 10. BOTTOM FLOATING APP NAVIGATION DOCK -->
    <nav class="app-dock">
        <a href="pwa.php" class="dock-item active">
            <span class="material-symbols-outlined">home</span>
            <span>خانه</span>
        </a>
        <a href="booking.php" class="dock-item">
            <span class="material-symbols-outlined">calendar_month</span>
            <span>کلینیک</span>
        </a>
        <a href="shop.php" class="dock-item">
            <span class="material-symbols-outlined">storefront</span>
            <span>فروشگاه</span>
        </a>
        <a href="pharmacy.php" class="dock-item">
            <span class="material-symbols-outlined">medication</span>
            <span>داروخانه</span>
        </a>
        <a href="profile.php" class="dock-item">
            <span class="material-symbols-outlined">account_circle</span>
            <span>پروفایل</span>
        </a>
    </nav>

    <!-- JAVASCRIPT: In-App Splash Controller, PWA Service Worker, Search Handler -->
    <script>
    (function() {
        'use strict';

        // 1. Splash Screen Smooth Dismissal Controller
        const splash = document.getElementById('pwa-splash');
        const statusEl = document.getElementById('splash-status');

        function dismissSplash() {
            if (!splash || splash.classList.contains('splash-dismissed')) return;
            
            if (statusEl) statusEl.textContent = 'سامانه با موفقیت آماده شد!';
            
            setTimeout(() => {
                splash.classList.add('splash-dismissed');
                setTimeout(() => {
                    splash.style.display = 'none';
                }, 450);
            }, 550);
        }

        // Trigger dismissal when page finishes initial render
        if (document.readyState === 'complete') {
            dismissSplash();
        } else {
            window.addEventListener('load', dismissSplash);
        }

        // Safety fallback: guaranteed dismissal within 1.5s
        setTimeout(dismissSplash, 1500);

        // 2. Offline Detection
        const offlineBanner = document.getElementById('offline-banner');
        function updateOnlineStatus() {
            if (offlineBanner) {
                offlineBanner.style.display = navigator.onLine ? 'none' : 'block';
            }
        }
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        updateOnlineStatus();

        // 3. Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then((reg) => {
                    console.log('[ASENA PWA] Service worker active with scope:', reg.scope);
                }).catch((err) => {
                    console.warn('[ASENA PWA] SW registration notice:', err);
                });
            });
        }

        // 4. Quick Pet Chips Toggle
        const chips = document.querySelectorAll('.pet-chip:not(.pet-chip-add)');
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                chips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
            });
        });
    })();

    function doSearch(q) {
        if (!q || !q.trim()) return;
        window.location.href = 'shop.php?q=' + encodeURIComponent(q.trim());
    }
    </script>
</body>
</html>
