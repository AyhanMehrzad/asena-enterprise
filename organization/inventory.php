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

    if ($action === 'add_item') {
        $itemType    = trim($_POST['item_type'] ?? 'medicine');
        $itemId      = (int)($_POST['item_id'] ?? 0);
        $customPrice = !empty($_POST['custom_price']) ? (float)$_POST['custom_price'] : null;
        $stock       = (int)($_POST['stock'] ?? 10);

        if ($itemId > 0) {
            $ins = $pdo->prepare("
                INSERT INTO organization_inventory (organization_id, item_type, item_id, custom_price, stock, is_in_stock)
                VALUES (?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    custom_price = VALUES(custom_price),
                    stock = VALUES(stock),
                    is_in_stock = 1
            ");
            if ($ins->execute([$orgId, $itemType, $itemId, $customPrice, $stock])) {
                $message = 'قلم دارویی یا محصول با موفقیت به انبار داروخانه مرکز افزوده شد.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'toggle_stock') {
        $rowId = (int)($_POST['inventory_id'] ?? 0);
        if ($rowId > 0) {
            $up = $pdo->prepare("UPDATE organization_inventory SET is_in_stock = IF(is_in_stock = 1, 0, 1) WHERE id = ? AND organization_id = ?");
            if ($up->execute([$rowId, $orgId])) {
                $message = 'وضعیت موجودی قلم به‌روزرسانی شد.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'delete_item') {
        $rowId = (int)($_POST['inventory_id'] ?? 0);
        if ($rowId > 0) {
            $del = $pdo->prepare("DELETE FROM organization_inventory WHERE id = ? AND organization_id = ?");
            if ($del->execute([$rowId, $orgId])) {
                $message = 'قلم مورد نظر از موجودی مرکز حذف شد.';
                $messageType = 'success';
            }
        }
    }
}

// Fetch clinic inventory
$inventory = $orgService->getInventory($orgId);

// Fetch medicines and products for adding dropdown
$medicines = $pdo->query("SELECT id, name, price FROM pharmacy_medicines LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$products  = $pdo->query("SELECT id, name, price FROM products LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6 max-w-5xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-teal-600 text-3xl">medication</span>
                <span>داروخانه داخلی و انبار تجهیزات درمانی</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">مدیریت موجودی داروهای تخصصی، قیمت‌گذاری و عرضه مکمل‌های تجویزی</p>
        </div>

        <button onclick="document.getElementById('add-item-modal').classList.toggle('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/20 flex items-center gap-1.5 transition-all">
            <span class="material-symbols-outlined text-base">add_circle</span>
            <span>افزودن داروی جدید به انبار</span>
        </button>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div class="p-4 rounded-2xl flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800' ?>">
            <span class="material-symbols-outlined <?= $messageType === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>">
                <?= $messageType === 'success' ? 'check_circle' : 'error' ?>
            </span>
            <span class="text-sm font-bold"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Add Item Modal -->
    <div id="add-item-modal" class="hidden bg-white p-6 rounded-3xl border border-teal-200 shadow-lg">
        <h2 class="text-sm font-black text-teal-950 mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-teal-600">playlist_add</span>
            <span>افزودن قلم دارویی یا محصول به موجودی مرکز</span>
        </h2>

        <form method="POST" action="inventory.php" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_item">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">نوع قلم *</label>
                <select name="item_type" id="item_type_select" onchange="toggleItemType(this.value)" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <option value="medicine">داروی دامپزشکی (نسخه و مکمل)</option>
                    <option value="product">محصول مصرفی پت‌شاپ / غذا</option>
                </select>
            </div>

            <div class="sm:col-span-2" id="medicine_select_container">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">انتخاب از کاتالوگ داروها *</label>
                <select name="item_id" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white">
                    <?php foreach ($medicines as $m): ?>
                        <option value="<?= $m['id'] ?>">
                            <?= htmlspecialchars($m['name']) ?> (<?= number_format((float)$m['price']) ?> تومان)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sm:col-span-2 hidden" id="product_select_container">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">انتخاب از کاتالوگ محصولات *</label>
                <select name="item_id_product" class="w-full h-11 px-3 rounded-xl border border-slate-300 text-xs bg-white" disabled>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['name']) ?> (<?= number_format((float)$p['price']) ?> تومان)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">قیمت اختصاصی مرکز (تومان)</label>
                <input type="number" name="custom_price" placeholder="خالی = قیمت مرجع" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">تعداد موجودی در مرکز *</label>
                <input type="number" name="stock" required value="20" class="w-full h-11 px-3.5 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="flex items-end justify-end gap-2">
                <button type="button" onclick="document.getElementById('add-item-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                    انصراف
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-700 text-white shadow-sm transition-all">
                    ثبت در انبار
                </button>
            </div>
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">نام قلم</th>
                        <th class="p-4">دسته‌بندی</th>
                        <th class="p-4">تعداد موجودی</th>
                        <th class="p-4">قیمت مرکز</th>
                        <th class="p-4">وضعیت</th>
                        <th class="p-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                هنوز هیچ قلم دارویی یا محصولی در این مرکز ثبت نشده است.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $row): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-4 font-black text-slate-900 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 text-teal-600">
                                    <span class="material-symbols-outlined text-lg">medication</span>
                                </div>
                                <span><?= htmlspecialchars($row['item_name']) ?></span>
                            </td>
                            <td class="p-4 text-slate-500 font-medium">
                                <?= htmlspecialchars($row['item_category'] ?? 'دارو') ?>
                            </td>
                            <td class="p-4 font-bold text-slate-900 font-mono">
                                <?= number_format((int)($row['stock'] ?? 0)) ?> عدد
                            </td>
                            <td class="p-4 font-black text-emerald-700">
                                <?= number_format((float)$row['effective_price']) ?> تومان
                            </td>
                            <td class="p-4">
                                <?php if (!empty($row['is_in_stock'])): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                        موجود در مرکز
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">
                                        ناموجود
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Toggle Stock Form -->
                                    <form method="POST" action="inventory.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_stock">
                                        <input type="hidden" name="inventory_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold border border-slate-200 hover:bg-slate-100 text-slate-600 transition-colors">
                                            <?= !empty($row['is_in_stock']) ? 'ثبت ناموجود' : 'ثبت موجود' ?>
                                        </button>
                                    </form>

                                    <!-- Delete Item Form -->
                                    <form method="POST" action="inventory.php" onsubmit="return confirm('آیا از حذف این قلم مطمئن هستید؟')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="inventory_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 transition-colors" title="حذف">
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

<script>
function toggleItemType(type) {
    const medCont = document.getElementById('medicine_select_container');
    const prdCont = document.getElementById('product_select_container');
    const medSel = medCont.querySelector('select');
    const prdSel = prdCont.querySelector('select');

    if (type === 'medicine') {
        medCont.classList.remove('hidden');
        prdCont.classList.add('hidden');
        medSel.disabled = false;
        prdSel.disabled = true;
        medSel.name = 'item_id';
    } else {
        medCont.classList.add('hidden');
        prdCont.classList.remove('hidden');
        medSel.disabled = true;
        prdSel.disabled = false;
        prdSel.name = 'item_id';
    }
}
</script>

<?php require_once 'includes/organization_footer.php'; ?>
