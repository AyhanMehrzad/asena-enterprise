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
    $_SESSION['profile_error'] = 'تراکنش یافت نشد.';
    header('Location: cart.php');
    exit;
}

// Convert Tomans to USDT (approx rate or setting)
$tomanPerUsdt = (int)get_setting($pdo, 'crypto_usdt_toman_rate', 65000);
$usdtAmount   = round($tx['amount'] / $tomanPerUsdt, 2);
$walletAddress = get_setting($pdo, 'crypto_usdt_trc20_wallet', 'TYDskj3920sdfkJSHdf98234JHskfjh2');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_txid'])) {
    $txHash = trim($_POST['tx_hash'] ?? '');
    if (strlen($txHash) < 16) {
        $message = 'هش تراکنش (TxHash) وارد شده معتبر نیست.';
        $messageType = 'error';
    } else {
        $paymentService = new PaymentService($pdo);
        $res = $paymentService->submitCardReceipt($authority, $txHash, 'USDT', null);
        if ($res['success']) {
            $message = 'هش تراکنش با موفقیت ثبت شد و در حال استعلام بلاک‌چین است.';
            $messageType = 'success';
        } else {
            $message = $res['message'];
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت با رمزارز تتر (USDT) — آسنا</title>
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
    <link rel="stylesheet" href="assets/vendor/@fortawesome/fontawesome-free/css/all.min.css">
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-center items-center p-4 font-sans">
    <div class="max-w-xl w-full bg-slate-800 border border-slate-700 rounded-3xl shadow-2xl p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-700 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-600/20 text-emerald-400 flex items-center justify-center text-2xl border border-emerald-500/30">
                    <i class="fa-brands fa-ethereum"></i>
                </div>
                <div>
                    <h1 class="text-lg font-black text-white">پرداخت با تتر (USDT TRC20)</h1>
                    <p class="text-xs text-slate-400">۱۰۰٪ بدون نظارت مالیاتی و غیرمتمرکز</p>
                </div>
            </div>
            <span class="text-xs font-mono font-bold text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                ≈ <?= number_format($usdtAmount, 2) ?> USDT
            </span>
        </div>

        <?php if ($message): ?>
            <div class="p-4 rounded-xl text-xs font-bold <?= $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/10 text-rose-300 border border-rose-500/30' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-slate-900/80 rounded-2xl p-4 border border-slate-700/80 space-y-2 text-center">
            <span class="text-xs text-slate-400">آدرس کیف پول تتر TRC20:</span>
            <div class="font-mono text-xs text-emerald-300 break-all select-all bg-slate-950 p-3 rounded-xl border border-slate-800">
                <?= htmlspecialchars($walletAddress) ?>
            </div>
        </div>

        <form action="crypto_payment.php?tx=<?= urlencode($authority) ?>" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">هش تراکنش (TxHash / Transaction ID):</label>
                <input type="text" name="tx_hash" required placeholder="شناسه تراکنش بعد از انتقال در کیف پول" 
                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-xs font-mono text-white focus:outline-none focus:border-emerald-500">
            </div>
            <button type="submit" name="submit_txid" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition-all">
                ثبت شناسه تراکنش رمزارز
            </button>
        </form>

        <div class="text-center pt-2">
            <a href="cart.php" class="text-xs text-slate-400 hover:text-white">انصراف و بازگشت</a>
        </div>
    </div>
</body>
</html>
