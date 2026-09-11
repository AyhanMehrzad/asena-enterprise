<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/gateway.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userStmt = $pdo->prepare("SELECT email, phone, city, address FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);

$pendingOrder = $_SESSION['pending_order'] ?? null;
$isBooking = (($pendingOrder['type'] ?? '') === 'booking');
$isSmsPackage = (($pendingOrder['type'] ?? '') === 'sms_package');

if ($isBooking) {
    // ── Direct Appointment Booking Payment ────────────────────────────────────
    $payable_today = (int)($pendingOrder['total_amount'] ?? 0);
    $bookingId = (int)($pendingOrder['booking_id'] ?? 0);
    if ($payable_today <= 0 || $bookingId <= 0) {
        $_SESSION['booking_error'] = 'اطلاعات نوبت جهت پرداخت نامعتبر است.';
        header('Location: booking.php');
        exit;
    }
    $order_desc = "پرداخت هزینه نوبت ویزیت تخصصی در سامانه آسنا (#APT-{$bookingId})";
} elseif ($isSmsPackage) {
    // ── Direct SMS Package Purchase ───────────────────────────────────────────
    $payable_today = (int)($pendingOrder['total_amount'] ?? 0);
    $pkgName = $pendingOrder['package_name'] ?? 'بسته پیامک آسنا';
    if ($payable_today <= 0) {
        $_SESSION['profile_error'] = 'اطلاعات بسته پیامک نامعتبر است.';
        header('Location: index.php');
        exit;
    }
    $order_desc = "خرید {$pkgName} در پرتال آسنا";
} else {
    // ── Standard & Autoship Cart Checkout ─────────────────────────────────────
    if (empty(trim((string)$currentUser['city'])) || empty(trim((string)$currentUser['address']))) {
        $_SESSION['profile_error'] = "لطفاً پیش از خرید، آدرس منزل و شهر خود را در پروفایل تکمیل کنید تا امکان ارسال مرسولات فراهم باشد.";
        header('Location: profile_settings.php');
        exit;
    }

    $cart_items = $_SESSION['cart'] ?? [];
    if (empty($cart_items)) {
        header('Location: cart.php');
        exit;
    }

    $cart_types       = $_SESSION['cart_types'] ?? [];
    $cart_frequencies = $_SESSION['cart_frequency'] ?? [];
    $checkout_type    = $_GET['type'] ?? 'all'; // 'autoship', 'standard', or 'all'

    // Calculate real totals from DB
    $ids = array_keys($cart_items);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Check in products first
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $db_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If some items were from pharmacy_medicines
    $found_ids = array_column($db_products, 'id');
    $remaining_ids = array_diff($ids, $found_ids);
    if (!empty($remaining_ids)) {
        $rem_placeholders = implode(',', array_fill(0, count($remaining_ids), '?'));
        $stmtMeds = $pdo->prepare("SELECT * FROM pharmacy_medicines WHERE id IN ($rem_placeholders)");
        $stmtMeds->execute(array_values($remaining_ids));
        $db_products = array_merge($db_products, $stmtMeds->fetchAll(PDO::FETCH_ASSOC));
    }

    $total_price    = 0;
    $total_discount = 0;
    $pending_items  = [];

    foreach ($db_products as $prod) {
        $p_id            = $prod['id'];
        $item_type       = $cart_types[$p_id] ?? 'standard';
        
        // Filter if specific checkout requested
        if ($checkout_type === 'autoship' && $item_type !== 'autoship') continue;
        if ($checkout_type === 'standard' && $item_type === 'autoship') continue;

        $qty             = (int)($cart_items[$p_id] ?? 0);
        $price           = (int)$prod['price'];
        
        if ($item_type === 'autoship') {
            $auto_pct = !empty($prod['autoship_discount']) ? (int)$prod['autoship_discount'] : 15;
            $effective_price = round($price * (1 - ($auto_pct / 100)));
        } else {
            $effective_price = $prod['discount_price'] ? (int)$prod['discount_price'] : $price;
        }
        
        $total_price    += $price * $qty;
        $total_discount += ($price - $effective_price) * $qty;

        $pending_items[] = [
            'product_id'            => $prod['id'],
            'product_name_snapshot' => $prod['name'],
            'qty'                   => $qty,
            'unit_price'            => $effective_price,
            'is_autoship'           => ($item_type === 'autoship') ? 1 : 0,
            'frequency'             => $cart_frequencies[$p_id] ?? '1_month'
        ];
    }

    $subtotal = $total_price - $total_discount;
    if ($subtotal <= 0 || empty($pending_items)) {
        header('Location: cart.php');
        exit;
    }

    // Add 9% VAT
    $tax_rate_pct = (float)get_setting($pdo, 'tax_rate_percent', 9);
    $tax_amount   = (int)round($subtotal * ($tax_rate_pct / 100.0));
    $final_total  = $subtotal + $tax_amount;

    $duration_months = (int)($_GET['duration'] ?? 3);
    if (!in_array($duration_months, [3, 6, 12])) $duration_months = 3;
    $payment_model = ($_GET['model'] ?? 'monthly') === 'upfront' ? 'upfront' : 'monthly';

    if ($checkout_type === 'autoship' && $payment_model === 'upfront') {
        $payable_today = round(($final_total * $duration_months) * 0.95);
        $order_desc = "خرید یک‌جا اشتراک {$duration_months} ماهه تحویل خودکار آسنا (" . count($pending_items) . " قلم با احتساب مالیات ارزش افزوده)";
    } elseif ($checkout_type === 'autoship') {
        $payable_today = $final_total;
        $order_desc = "پرداخت نوبت ۱ از اشتراک {$duration_months} ماهه تحویل خودکار آسنا (" . count($pending_items) . " قلم با احتساب مالیات ارزش افزوده)";
    } else {
        $payable_today = $final_total;
        $order_desc = "خرید از فروشگاه آسنا — " . count($pending_items) . " محصول (با احتساب مالیات ارزش افزوده)";
    }
}

// ── Request payment authority from ZarinPal / Mock Gateway ────────────────────
$gateway      = new ZarinPalGateway();
$callback_url = get_app_base_url() . '/actions/complete_payment.php';

$metadata = [];
if (!empty($currentUser['email'])) {
    $metadata['email'] = $currentUser['email'];
}
if (!empty($currentUser['phone'])) {
    $metadata['mobile'] = (string)$currentUser['phone'];
}

$result = $gateway->requestPayment(
    $payable_today,
    $order_desc,
    $callback_url,
    $metadata
);

if (!$result['success']) {
    $fallbackRedirect = $isBooking ? 'booking.php' : 'cart.php';
    $_SESSION['profile_error'] = 'خطا در اتصال به درگاه پرداخت: ' . $result['error'];
    header("Location: {$fallbackRedirect}");
    exit;
}

// ── Store authority in pending order ──────────────────────────────────────────
if ($isBooking || $isSmsPackage) {
    $_SESSION['pending_order']['authority'] = $result['authority'];
} else {
    $_SESSION['pending_order'] = [
        'type'            => 'cart',
        'checkout_type'   => $checkout_type,
        'items'           => $pending_items,
        'subtotal'        => $subtotal,
        'tax_amount'      => $tax_amount,
        'total_amount'    => $payable_today,
        'per_delivery'    => $final_total,
        'duration_months' => $duration_months,
        'payment_model'   => $payment_model,
        'authority'       => $result['authority'],
        'created_at'      => time(),
    ];
}

// Redirect user to payment gateway
header('Location: ' . $result['payment_url']);
exit;
