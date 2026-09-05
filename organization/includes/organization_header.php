<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/App.php';
require_once dirname(__DIR__, 2) . '/includes/AuthGuard.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Route Guard: Organization managers and administrators
$currentUser = AuthGuard::requireRole(['organization', 'admin'], $pdo);

// Find organization managed by this user
$orgStmt = $pdo->prepare("SELECT * FROM organizations WHERE manager_name = ? OR email = ? LIMIT 1");
$orgStmt->execute([$currentUser['name'], $currentUser['email'] ?? '']);
$currentOrg = $orgStmt->fetch(PDO::FETCH_ASSOC);

// If admin or newly assigned without linked row, default to first organization
if (!$currentOrg && $currentUser['role'] === 'admin') {
    $currentOrg = $pdo->query("SELECT * FROM organizations LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

if (!$currentOrg) {
    // If user has organization role but no organization record exists yet, create one
    $slug = 'org-' . $currentUser['id'] . '-' . time();
    $ins = $pdo->prepare("
        INSERT INTO organizations (name, slug, type, manager_name, phone, city, status, created_at)
        VALUES (?, ?, 'clinic', ?, ?, 'تهران', 'approved', NOW())
    ");
    $ins->execute([$currentUser['name'], $slug, $currentUser['name'], $currentUser['phone'] ?? '']);
    $newId = (int)$pdo->lastInsertId();
    $currentOrg = $pdo->query("SELECT * FROM organizations WHERE id = $newId")->fetch(PDO::FETCH_ASSOC);
}

$orgName = $currentOrg['name'] ?? 'مرکز درمانی';
$orgSlug = $currentOrg['slug'] ?? '';
$currentFile = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= htmlspecialchars($orgName) ?> | پنل مدیریت مرکز درمانی ASENA</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="../assets/css/material-symbols.css" rel="stylesheet"/>
    <link href="../assets/css/geist.css" rel="stylesheet"/>
    <script src="../assets/js/tailwindcss-cdn.js"></script>
    <script id="tailwind-config">
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: "#0f172a",
              "sky-theme": "#0284c7"
            }
          }
        }
      }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen">

    <!-- Sidebar Navigation -->
    <aside class="fixed inset-y-0 right-0 z-50 w-64 bg-slate-900 text-white flex flex-col justify-between shadow-2xl transition-all duration-300">
        <div>
            <!-- Header Brand -->
            <div class="h-18 p-5 border-b border-white/10 flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-sky-500/20 text-sky-400 flex items-center justify-center font-black">
                    <span class="material-symbols-outlined text-2xl">local_hospital</span>
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-black truncate block text-white"><?= htmlspecialchars($orgName) ?></span>
                    <span class="text-[11px] text-sky-400 font-bold block">پنل مدیریت درمانی</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-1.5 mt-2">
                <a href="index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all <?= $currentFile === 'index.php' ? 'bg-sky-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
                    <span class="material-symbols-outlined text-lg">dashboard</span>
                    <span>پیشخوان و مشخصات مرکز</span>
                </a>

                <a href="doctors.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all <?= $currentFile === 'doctors.php' ? 'bg-sky-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
                    <span class="material-symbols-outlined text-lg">stethoscope</span>
                    <span>پزشکان همکار و شیفت‌ها</span>
                </a>

                <a href="inventory.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all <?= $currentFile === 'inventory.php' ? 'bg-sky-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
                    <span class="material-symbols-outlined text-lg">medication</span>
                    <span>داروخانه و محصولات اختصاصی</span>
                </a>

                <a href="wallet.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all <?= $currentFile === 'wallet.php' ? 'bg-sky-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-white/5' ?>">
                    <span class="material-symbols-outlined text-lg">account_balance_wallet</span>
                    <span>کیف پول و تسویه حساب (Escrow)</span>
                </a>

                <div class="pt-3 border-t border-white/10">
                    <a href="../organization_profile.php?slug=<?= urlencode($orgSlug) ?>" target="_blank" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-lg">open_in_new</span>
                            <span>مشاهده پروفایل عمومی</span>
                        </div>
                    </a>
                </div>
            </nav>
        </div>

        <div class="p-4 border-t border-white/10 space-y-2">
            <a href="../index.php" class="w-full py-2.5 px-3 bg-white/5 hover:bg-white/10 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all">
                <span class="material-symbols-outlined text-base">storefront</span>
                <span>بازگشت به سایت</span>
            </a>
            <a href="../logout.php" class="w-full py-2 px-3 text-rose-400 hover:text-rose-300 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base">logout</span>
                <span>خروج از حساب</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Container -->
    <main class="mr-64 min-h-screen">
        <!-- Top App Bar -->
        <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between sticky top-0 z-40">
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500 font-bold">مدیریت مرکز درمانی:</span>
                <span class="text-xs font-black text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg"><?= htmlspecialchars($orgName) ?></span>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-600 font-bold"><?= htmlspecialchars($currentUser['name']) ?></span>
                <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-black">
                    <?= mb_substr($currentUser['name'], 0, 1) ?>
                </div>
            </div>
        </header>
