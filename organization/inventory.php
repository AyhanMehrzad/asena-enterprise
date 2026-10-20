<?php
require_once 'includes/organization_header.php';

$orgService = App::organization();
$orgId = (int)$currentOrg['id'];
$message = '';
$messageType = '';

// Handle Inventory Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_verify();
    $action = $_POST['action'];

    if ($action === 'add_medicine_direct') {
        $name        = trim($_POST['name'] ?? '');
        $genericName = trim($_POST['generic_name'] ?? '');
        $category    = trim($_POST['category'] ?? 'عمومی');
        $price       = clean_toman_amount($_POST['price'] ?? 0);
        $brand       = trim($_POST['brand'] ?? '');
        $stock       = (int)($_POST['stock'] ?? 10);
        $expiryDate  = trim($_POST['expiry_date'] ?? date('Y-m-d', strtotime('+1 year')));
        $batchNumber = trim($_POST['batch_number'] ?? ('BT-' . rand(1000, 9999)));
        $reqCold     = isset($_POST['requires_cold_chain']) ? 1 : 0;
        $dosage      = trim($_POST['dosage_instructions'] ?? '');

        if (!empty($name)) {
            $ins = $pdo->prepare("
                INSERT INTO pharmacy_medicines 
                (organization_id, user_id, name, generic_name, category, price, brand, stock, expiry_date, batch_number, requires_prescription, requires_cold_chain, dosage_instructions, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW())
            ");
            if ($ins->execute([$orgId, (int)$currentUser['id'], $name, $genericName, $category, $price, $brand, $stock, $expiryDate, $batchNumber, $reqCold, $dosage])) {
                $message = "داروی جدید «{$name}» با موفقیت در انبار مرکز درمانی ثبت گردید.";
                $messageType = 'success';
            } else {
                $message = 'خطا در ثبت دارو.';
                $messageType = 'error';
            }
        } else {
            $message = 'لطفاً نام دارو را وارد فرمایید.';
            $messageType = 'error';
        }
    } elseif ($action === 'update_stock') {
        $source = trim($_POST['source'] ?? 'pharmacy_medicines');
        $itemId = (int)($_POST['item_id'] ?? 0);
        $delta  = (int)($_POST['delta'] ?? 0);

        if ($source === 'pharmacy_medicines' && $itemId > 0) {
            $upd = $pdo->prepare("UPDATE pharmacy_medicines SET stock = GREATEST(0, stock + ?) WHERE id = ? AND (organization_id = ? OR organization_id IS NULL)");
            if ($upd->execute([$delta, $itemId, $orgId])) {
                $message = 'موجودی انبار دارویی به‌روزرسانی شد.';
                $messageType = 'success';
            }
        } elseif ($source === 'organization_inventory' && $itemId > 0) {
            $upd = $pdo->prepare("UPDATE organization_inventory SET stock = GREATEST(0, stock + ?) WHERE id = ? AND organization_id = ?");
            if ($upd->execute([$delta, $itemId, $orgId])) {
                $message = 'موجودی قلم اختصاصی به‌روزرسانی شد.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'toggle_stock') {
        $source = trim($_POST['source'] ?? 'pharmacy_medicines');
        $itemId = (int)($_POST['item_id'] ?? 0);

        if ($source === 'pharmacy_medicines' && $itemId > 0) {
            $up = $pdo->prepare("UPDATE pharmacy_medicines SET stock = IF(stock > 0, 0, 10) WHERE id = ? AND (organization_id = ? OR organization_id IS NULL)");
            if ($up->execute([$itemId, $orgId])) {
                $message = 'وضعیت موجودی دارو تغییر یافت.';
                $messageType = 'success';
            }
        } elseif ($source === 'organization_inventory' && $itemId > 0) {
            $up = $pdo->prepare("UPDATE organization_inventory SET is_in_stock = IF(is_in_stock = 1, 0, 1) WHERE id = ? AND organization_id = ?");
            if ($up->execute([$itemId, $orgId])) {
                $message = 'وضعیت موجودی قلم به‌روزرسانی شد.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'delete_item') {
        $source = trim($_POST['source'] ?? 'pharmacy_medicines');
        $itemId = (int)($_POST['item_id'] ?? 0);

        if ($source === 'pharmacy_medicines' && $itemId > 0) {
            $del = $pdo->prepare("DELETE FROM pharmacy_medicines WHERE id = ? AND organization_id = ?");
            if ($del->execute([$itemId, $orgId])) {
                $message = 'داروی انتخابی از انبار مرکز حذف شد.';
                $messageType = 'success';
            }
        } elseif ($source === 'organization_inventory' && $itemId > 0) {
            $del = $pdo->prepare("DELETE FROM organization_inventory WHERE id = ? AND organization_id = ?");
            if ($del->execute([$itemId, $orgId])) {
                $message = 'قلم مورد نظر از موجودی مرکز حذف شد.';
                $messageType = 'success';
            }
        }
    }
}

// Fetch comprehensive clinic & pharmacy inventory
$inventory = $orgService->getComprehensiveInventory($orgId);

// Filter by view tab / category
$activeFilter = $_GET['filter'] ?? 'all';
$filteredInventory = array_filter($inventory, function($item) use ($activeFilter) {
    if ($activeFilter === 'medicines') {
        return ($item['item_type'] ?? '') === 'medicine';
    } elseif ($activeFilter === 'cold_chain') {
        return !empty($item['requires_cold_chain']);
    } elseif ($activeFilter === 'low_stock') {
        return (int)($item['stock'] ?? 0) <= 5;
    }
    return true;
});

// Calculate metrics
$totalItems = count($inventory);
$totalMeds = count(array_filter($inventory, fn($i) => ($i['item_type'] ?? '') === 'medicine'));
$coldChainCount = count(array_filter($inventory, fn($i) => !empty($i['requires_cold_chain'])));
$lowStockCount = count(array_filter($inventory, fn($i) => (int)($i['stock'] ?? 0) <= 5));
?>

<div class="p-4 sm:p-6 max-w-6xl mx-auto space-y-6">

    <!-- Header Cockpit -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                    <span>انبار دارویی همگام‌سازی شده مرکز</span>
                </span>
                <span class="text-xs text-slate-400">| <?= htmlspecialchars($currentOrg['name']) ?></span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-teal-600 text-3xl">medication</span>
                <span>داروخانه داخلی و انبار تجهیزات درمانی</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">مشاهده و مدیریت یکپارچه اقلام داروسازان و فارماکولوژیست‌های همکار، کنترل سری ساخت و تاریخ انقضا</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="members.php" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base text-purple-600">groups</span>
                <span>کادر داروسازان و پرسنل</span>
            </a>

            <button onclick="document.getElementById('add-medicine-modal').classList.toggle('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/20 flex items-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-base">add_circle</span>
                <span>افزودن داروی جدید به انبار</span>
            </button>
        </div>
    </div>

    <!-- Alert Banner -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">کل اقلام در انبار</p>
                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($totalItems) ?> قلم</h3>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">medication</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">داروها و مکمل‌های تجویزی</p>
                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($totalMeds) ?> قلم دارو</h3>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">ac_unit</span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">پایش زنجیره سرد</p>
                <h3 class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($coldChainCount) ?> زیستی / واکسن</h3>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl <?= $lowStockCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' ?> flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl"><?= $lowStockCount > 0 ? 'warning' : 'verified' ?></span>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500">هشدار کسری انبار</p>
                <h3 class="text-xl font-black <?= $lowStockCount > 0 ? 'text-amber-600' : 'text-emerald-700' ?> mt-0.5"><?= number_format($lowStockCount) ?> کسری موجودی</h3>
            </div>
        </div>
    </div>

    <!-- Filter Pills Navigation -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        <a href="inventory.php?filter=all" class="px-4 py-2 rounded-xl font-bold transition-all whitespace-nowrap <?= $activeFilter === 'all' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
            همه اقلام انبار (<?= $totalItems ?>)
        </a>
        <a href="inventory.php?filter=medicines" class="px-4 py-2 rounded-xl font-bold transition-all whitespace-nowrap <?= $activeFilter === 'medicines' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
            داروهای دامپزشکی (<?= $totalMeds ?>)
        </a>
        <a href="inventory.php?filter=cold_chain" class="px-4 py-2 rounded-xl font-bold transition-all whitespace-nowrap <?= $activeFilter === 'cold_chain' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
            نیازمند یخچال و زنجیره سرد (<?= $coldChainCount ?>)
        </a>
        <a href="inventory.php?filter=low_stock" class="px-4 py-2 rounded-xl font-bold transition-all whitespace-nowrap <?= $activeFilter === 'low_stock' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
            اقلام رو به اتمام (<?= $lowStockCount ?>)
        </a>
    </div>

    <!-- Add Medicine Modal -->
    <div id="add-medicine-modal" class="hidden bg-white p-6 rounded-3xl border border-teal-200 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-black text-teal-950 flex items-center gap-2">
                <span class="material-symbols-outlined text-teal-600">medication</span>
                <span>ثبت مستقیم داروی جدید در انبار مرکز درمانی</span>
            </h2>
            <button type="button" onclick="document.getElementById('add-medicine-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form method="POST" action="inventory.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_medicine_direct">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نام تجاری دارو *</label>
                <input type="text" name="name" required placeholder="مثال: شربت آموکسی‌سیلین ۲۵۰" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نام ژنریک علمی</label>
                <input type="text" name="generic_name" placeholder="Amoxicillin Oral Suspension" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs" dir="ltr">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">دسته‌بندی درمانی *</label>
                <select name="category" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="آنتی‌بیوتیک">آنتی‌بیوتیک</option>
                    <option value="ضدانگل">ضدانگل</option>
                    <option value="واکسن و سرم">واکسن و سرم</option>
                    <option value="مسکن و ضدالتهاب">مسکن و ضدالتهاب</option>
                    <option value="پوستی و موضعی">پوستی و موضعی</option>
                    <option value="چشمی و گوشی">چشمی و گوشی</option>
                    <option value="قلبی و عروقی">قلبی و عروقی</option>
                    <option value="گوارشی">گوارشی</option>
                    <option value="مکمل درمانی">مکمل درمانی</option>
                    <option value="عمومی">عمومی</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">قیمت واحد عرضه (تومان) *</label>
                <input type="number" name="price" required placeholder="مثال: 120000" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">تعداد موجودی اولیه در انبار *</label>
                <input type="number" name="stock" required value="20" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">برند / شرکت سازنده</label>
                <input type="text" name="brand" placeholder="مثال: رویان دارو، نصر فارما..." class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">شماره سری ساخت (Batch No)</label>
                <input type="text" name="batch_number" placeholder="مثال: BT-1403-902" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs" dir="ltr">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">تاریخ انقضا</label>
                <input type="date" name="expiry_date" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="flex items-center gap-2 pt-6">
                <label class="flex items-center gap-2 text-xs font-bold text-cyan-800 cursor-pointer bg-cyan-50 p-2.5 rounded-xl border border-cyan-200 w-full">
                    <input type="checkbox" name="requires_cold_chain" value="1" class="rounded text-cyan-600 focus:ring-cyan-500 w-4 h-4">
                    <span>نیازمند پایش زنجیره سرد (۲ الی ۸ درجه)</span>
                </label>
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">دستور مصرف / منع مصرف ویژه</label>
                <input type="text" name="dosage_instructions" placeholder="مثال: روزی ۲ بار هر ۱۲ ساعت بعد از غذا - منع مصرف در آبستنی" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="sm:col-span-3 flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('add-medicine-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-700 text-white shadow-sm transition-all">
                    ثبت نهایی در انبار مرکز
                </button>
            </div>
        </form>
    </div>

    <!-- Inventory Table (Desktop & Tablet) / Cards (Mobile) -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">مشخصات دارو / محصول</th>
                        <th class="p-4">دسته‌بندی درمانی</th>
                        <th class="p-4">موجودی انبار</th>
                        <th class="p-4">قیمت مرکز</th>
                        <th class="p-4">سری ساخت / انقضا</th>
                        <th class="p-4">مدیریت موجودی سریع</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($filteredInventory)): ?>
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">medication</span>
                                هیچ قلمی در این دسته‌بندی یافت نشد. داروسازان همکار یا مدیر مرکز می‌توانند اقلام را از دکمه بالا ثبت نمایند.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filteredInventory as $row): 
                            $isMed = ($row['item_type'] ?? '') === 'medicine';
                            $stock = (int)($row['stock'] ?? 0);
                            $isLow = $stock <= 5;
                            $hasCold = !empty($row['requires_cold_chain']);
                            $source = $row['source'] ?? 'pharmacy_medicines';
                            $rowId = (int)$row['id'];
                            $diffDays = !empty($row['expiry_date']) ? (strtotime($row['expiry_date']) - time()) / 86400 : 999;
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 <?= $isMed ? 'text-teal-600' : 'text-amber-600' ?>">
                                        <span class="material-symbols-outlined text-lg"><?= $isMed ? 'medication' : 'pets' ?></span>
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900"><?= htmlspecialchars($row['item_name']) ?></div>
                                        <?php if (!empty($row['generic_name'])): ?>
                                            <div class="text-[11px] text-slate-400 font-mono" dir="ltr"><?= htmlspecialchars($row['generic_name']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['pharmacist_name'])): ?>
                                            <div class="text-[10px] text-purple-700 font-bold mt-0.5">ثبت توسط داروساز: <?= htmlspecialchars($row['pharmacist_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-bold text-[11px]">
                                    <?= htmlspecialchars($row['item_category'] ?? 'عمومی') ?>
                                </span>
                                <?php if ($hasCold): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-cyan-50 text-cyan-700 border border-cyan-200 text-[10px] font-bold mt-1 block">
                                        <span class="material-symbols-outlined text-[12px]">ac_unit</span>
                                        زنجیره سرد
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold <?= $isLow ? 'bg-rose-100 text-rose-800 animate-pulse' : 'bg-emerald-100 text-emerald-800' ?>">
                                    <?= $stock ?> عدد
                                </span>
                                <?php if ($isLow): ?>
                                    <span class="text-[10px] text-rose-600 block font-bold mt-1">هشدار کسری!</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4 font-black text-emerald-700 font-mono">
                                <?= number_format((float)$row['effective_price']) ?> تومان
                            </td>

                            <td class="p-4 text-[11px]">
                                <?php if (!empty($row['batch_number'])): ?>
                                    <div class="font-mono text-slate-600" dir="ltr">بچ: <?= htmlspecialchars($row['batch_number']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($row['expiry_date'])): ?>
                                    <div class="font-mono mt-0.5 <?= $diffDays <= 30 ? 'text-rose-600 font-bold' : ($diffDays <= 60 ? 'text-amber-600 font-bold' : 'text-slate-400') ?>">
                                        انقضا: <?= htmlspecialchars($row['expiry_date']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-400">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="p-4">
                                <div class="flex items-center gap-1.5">
                                    <!-- Quick +1 -->
                                    <form method="POST" action="inventory.php" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="source" value="<?= $source ?>">
                                        <input type="hidden" name="item_id" value="<?= $rowId ?>">
                                        <input type="hidden" name="delta" value="1">
                                        <button type="submit" class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center justify-center transition-all" title="افزایش یک عدد">
                                            +۱
                                        </button>
                                    </form>

                                    <!-- Quick -1 -->
                                    <form method="POST" action="inventory.php" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="source" value="<?= $source ?>">
                                        <input type="hidden" name="item_id" value="<?= $rowId ?>">
                                        <input type="hidden" name="delta" value="-1">
                                        <button type="submit" class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs flex items-center justify-center transition-all" title="کاهش یک عدد">
                                            -۱
                                        </button>
                                    </form>

                                    <!-- Quick +5 -->
                                    <form method="POST" action="inventory.php" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="source" value="<?= $source ?>">
                                        <input type="hidden" name="item_id" value="<?= $rowId ?>">
                                        <input type="hidden" name="delta" value="5">
                                        <button type="submit" class="px-2 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] flex items-center justify-center transition-all" title="شارژ ۵ عدد">
                                            +۵
                                        </button>
                                    </form>
                                </div>
                            </td>

                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Toggle in/out of stock -->
                                    <form method="POST" action="inventory.php" class="inline m-0">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_stock">
                                        <input type="hidden" name="source" value="<?= $source ?>">
                                        <input type="hidden" name="item_id" value="<?= $rowId ?>">
                                        <button type="submit" class="px-2 py-1 rounded-lg text-[11px] font-bold border border-slate-200 hover:bg-slate-100 text-slate-600 transition-colors">
                                            <?= $stock > 0 ? 'صفر کردن' : 'شارژ' ?>
                                        </button>
                                    </form>

                                    <!-- Delete Item -->
                                    <form method="POST" action="inventory.php" class="inline m-0" onsubmit="return confirm('آیا از حذف این قلم دارویی از انبار مرکز مطمئن هستید؟')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="source" value="<?= $source ?>">
                                        <input type="hidden" name="item_id" value="<?= $rowId ?>">
                                        <button type="submit" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 transition-colors" title="حذف از انبار">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once 'includes/organization_footer.php'; ?>
