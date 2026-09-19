<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/PaymentService.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$authority = trim($_GET['tx'] ?? '');
if (empty($authority)) {
    $_SESSION['profile_error'] = 'شناسه تراکنش نامعتبر است.';
    header('Location: cart.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.*, u.name as user_name, u.phone as user_phone 
    FROM payment_transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.authority_or_ref = ? AND t.user_id = ?
    LIMIT 1
");
$stmt->execute([$authority, $_SESSION['user_id']]);
$tx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tx) {
    $_SESSION['profile_error'] = 'تراکنش یافت نشد یا متعلق به کاربر دیگری است.';
    header('Location: cart.php');
    exit;
}

// Fetch card gateway settings
$cardNum   = get_setting($pdo, 'card_gateway_number', '6037997512345678');
$cardName  = get_setting($pdo, 'card_gateway_holder', 'آسنا — حساب متمرکز امانی');
$cardBank  = get_setting($pdo, 'card_gateway_bank', 'بانک ملی ایران');
$cardShaba = get_setting($pdo, 'card_gateway_shaba', 'IR120170000000123456789012');

// Check if already submitted
$subStmt = $pdo->prepare("SELECT * FROM card_receipt_submissions WHERE payment_transaction_id = ? ORDER BY id DESC LIMIT 1");
$subStmt->execute([$tx['id']]);
$submission = $subStmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$messageType = '';

// Handle receipt form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_receipt'])) {
    $trackingCode = trim($_POST['tracking_code'] ?? '');
    $cardLast4    = trim($_POST['card_last4'] ?? '');
    
    $imageUrl = null;
    if (!empty($_FILES['receipt_image']['name']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($_FILES['receipt_image']['tmp_name']);
        if (in_array($fileType, $allowed)) {
            $ext = pathinfo($_FILES['receipt_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'receipt_' . $tx['id'] . '_' . time() . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/receipts';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $targetPath = $uploadDir . '/' . $fileName;
            if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $targetPath)) {
                $imageUrl = 'uploads/receipts/' . $fileName;
            }
        }
    }

    $paymentService = new PaymentService($pdo);
    $result = $paymentService->submitCardReceipt($authority, $trackingCode, $cardLast4, $imageUrl);

    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
        // Refresh transaction & submission
        $stmt->execute([$authority, $_SESSION['user_id']]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);
        $subStmt->execute([$tx['id']]);
        $submission = $subStmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = $result['message'];
        $messageType = 'error';
    }
}

$page_title = 'درگاه پرداخت کارت به کارت آسنا (بدون مالیات)';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
    <link rel="stylesheet" href="assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
    <style>
        @keyframes pulseBorder {
            0%, 100% { border-color: rgba(59, 130, 246, 0.4); }
            50% { border-color: rgba(59, 130, 246, 1); }
        }
        .pulse-card { animation: pulseBorder 3s infinite; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-center items-center p-4 font-sans antialiased selection:bg-blue-600 selection:text-white">

    <div class="max-w-xl w-full bg-slate-800/90 backdrop-blur-xl border border-slate-700/80 rounded-3xl shadow-2xl p-6 sm:p-8 relative overflow-hidden">
        <!-- Top Background Glow -->
        <div class="absolute -top-24 -left-24 w-56 h-56 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-56 h-56 bg-emerald-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-700/60 pb-5 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i class="fa-solid fa-credit-card text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-black text-white">درگاه کارت به کارت امن آسنا</h1>
                    <p class="text-xs text-slate-400 mt-0.5">واریز مستقیم به حساب متمرکز امانی (بدون کد مالیاتی)</p>
                </div>
            </div>
            <div class="text-left">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    شناسه: <?= htmlspecialchars(substr($tx['authority_or_ref'], -8)) ?>
                </span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="p-4 rounded-2xl mb-6 text-sm font-medium flex items-center gap-3 <?= $messageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border border-rose-500/30 text-rose-300' ?>">
                <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-triangle-exclamation text-rose-400' ?> text-lg"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($tx['status'] === 'paid'): ?>
            <!-- Already Paid State -->
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-6 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto text-3xl">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <h2 class="text-xl font-black text-emerald-300">پرداخت شما تأیید گردید!</h2>
                <p class="text-sm text-slate-300 leading-relaxed">
                    مبلغ <strong class="text-white font-mono"><?= number_format($tx['amount']) ?> تومان</strong> در سامانه ثبت شد و سفارش شما هم‌اکنون در مرحله آماده‌سازی و ارسال قرار دارد.
                </p>
                <div class="pt-2">
                    <a href="profile.php" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm transition-all shadow-lg shadow-emerald-600/30">
                        <i class="fa-solid fa-user-gear"></i>
                        مشاهده سفارش در پنل کاربری
                    </a>
                </div>
            </div>

        <?php elseif ($submission && $submission['status'] === 'pending'): ?>
            <!-- Submitted & Pending Review State -->
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-2xl p-6 text-center space-y-4">
                <div class="w-14 h-14 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-hourglass-half fa-spin"></i>
                </div>
                <h2 class="text-lg font-black text-amber-300">رسید واریز شما دریافت شد</h2>
                <p class="text-xs text-slate-300 leading-relaxed">
                    شماره پیگیری ثبت‌شده: <span class="font-mono font-bold text-white bg-slate-900/60 px-2 py-0.5 rounded"><?= htmlspecialchars($submission['bank_tracking_code']) ?></span><br>
                    تیم حسابداری یا وب‌سرویس پیامکی در حال تطبیق واریزی هستند. پس از تایید، پیامک فعال‌سازی بلافاصله ارسال خواهد شد.
                </p>
                <div class="pt-2 flex justify-center gap-3">
                    <a href="card_payment.php?tx=<?= urlencode($authority) ?>" class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-xs font-bold text-slate-200 transition-all">
                        <i class="fa-solid fa-rotate-right ml-1"></i>
                        بروزرسانی وضعیت
                    </a>
                    <a href="profile.php" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-xs font-bold text-white transition-all">
                        بازگشت به حساب کاربری
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Instructions & Card Details -->
            <div class="space-y-5">
                <!-- Amount Banner with 10% Tax itemization -->
                <div class="bg-gradient-to-r from-blue-900/40 via-indigo-900/40 to-slate-900/40 border border-blue-500/30 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-400 block">مبلغ نهایی قابل واریز (با احتساب ۱۰٪ ارزش افزوده):</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-2xl font-black text-white font-mono"><?= number_format($tx['amount']) ?></span>
                            <span class="text-xs font-bold text-blue-400">تومان</span>
                        </div>
                    </div>
                    <div class="text-left bg-slate-900/80 px-3 py-1.5 rounded-xl border border-slate-700/60">
                        <span class="text-[10px] text-slate-400 block">معادل ریال:</span>
                        <span class="text-xs font-mono font-bold text-slate-200"><?= number_format($tx['amount'] * 10) ?> ریال</span>
                    </div>
                </div>

                <!-- Digital Bank Card Display -->
                <div class="relative bg-gradient-to-br from-indigo-800 via-blue-800 to-slate-800 border-2 border-blue-500/50 rounded-2xl p-5 shadow-xl text-white pulse-card overflow-hidden">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <span class="text-[10px] font-bold tracking-wider text-blue-200 uppercase block"><?= htmlspecialchars($cardBank) ?></span>
                            <span class="text-xs font-bold text-slate-100"><?= htmlspecialchars($cardName) ?></span>
                        </div>
                        <i class="fa-solid fa-sim-card text-2xl text-amber-300 opacity-80"></i>
                    </div>

                    <!-- 16-Digit Card Number with Copy Action -->
                    <div class="space-y-1">
                        <span class="text-[10px] text-blue-200">شماره کارت جهت انتقال:</span>
                        <div class="flex items-center justify-between bg-slate-950/40 backdrop-blur rounded-xl px-3.5 py-2.5 border border-white/10">
                            <span id="cardNumber" class="font-mono text-lg sm:text-xl font-black tracking-widest text-amber-200">
                                <?= htmlspecialchars(implode(' ', str_split($cardNum, 4))) ?>
                            </span>
                            <button type="button" onclick="copyCardNumber()" class="px-2.5 py-1 rounded-lg bg-blue-500/20 hover:bg-blue-500/40 text-blue-300 text-xs font-bold transition-all flex items-center gap-1.5 border border-blue-400/30">
                                <i class="fa-regular fa-copy"></i>
                                <span id="copyBtnText">کپی</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-white/10 flex justify-between items-center text-[10px] text-blue-200 font-mono">
                        <span>شماره شبا: <?= htmlspecialchars($cardShaba) ?></span>
                        <span class="bg-blue-500/30 px-2 py-0.5 rounded text-white font-sans">امانی آسنا</span>
                    </div>
                </div>

                <!-- Payment Form -->
                <form action="card_payment.php?tx=<?= urlencode($authority) ?>" method="POST" enctype="multipart/form-data" class="bg-slate-900/60 border border-slate-700/60 rounded-2xl p-5 space-y-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-blue-400"></i>
                        ثبت اطلاعات فیش یا شماره پیگیری
                    </h3>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">
                            شماره پیگیری / شماره ارجاع بانکی (الزامی):
                        </label>
                        <input type="text" name="tracking_code" required placeholder="مثلاً: 987452 یا 1234567890" 
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">
                                ۴ رقم آخر کارت شما (اختیاری):
                            </label>
                            <input type="text" name="card_last4" maxlength="4" placeholder="مثلاً: 4521" 
                                class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">
                                تصویر فیش واریز (اختیاری):
                            </label>
                            <input type="file" name="receipt_image" accept="image/*" 
                                class="w-full text-xs text-slate-400 file:ml-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-blue-400 hover:file:bg-slate-700 cursor-pointer">
                        </div>
                    </div>

                    <button type="submit" name="submit_receipt" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-black text-sm shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i>
                        ثبت و ارسال اطلاعات واریزی
                    </button>
                </form>

                <!-- Help note -->
                <p class="text-[11px] text-slate-400 leading-relaxed text-center">
                    <i class="fa-solid fa-shield-halved text-blue-400 ml-1"></i>
                    واریز شما مستقیماً وارد حساب امانی شرکت شده و تا زمان تحویل سفارش و تایید رضایت شما محفوظ است.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyCardNumber() {
            const raw = '<?= htmlspecialchars($cardNum) ?>';
            navigator.clipboard.writeText(raw).then(() => {
                const btn = document.getElementById('copyBtnText');
                btn.textContent = 'کپی شد!';
                setTimeout(() => { btn.textContent = 'کپی'; }, 2000);
            });
        }
    </script>
</body>
</html>
