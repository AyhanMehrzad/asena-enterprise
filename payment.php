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

    // Incorporate applied promo code if standard cart checkout
    require_once __DIR__ . '/includes/App.php';
    $appliedPromo = $_SESSION['applied_promo'] ?? null;
    $promoDiscount = 0;
    $promoCode = null;
    $promoId = null;

    if ($appliedPromo && !empty($appliedPromo['code']) && $checkout_type !== 'autoship') {
        $promoValidation = App::promo()->validatePromo($appliedPromo['code'], (int)$_SESSION['user_id'], $subtotal);
        if ($promoValidation['valid']) {
            $promoDiscount = (int)$promoValidation['discount_amount'];
            $promoCode = $promoValidation['code'];
            $promoId = (int)$promoValidation['promo_id'];
        } else {
            unset($_SESSION['applied_promo']);
            $appliedPromo = null;
        }
    }

    $taxable_subtotal = max(0, $subtotal - $promoDiscount);

    // Carrier & National Logistics Calculation
    $selected_carrier = $_SESSION['selected_carrier'] ?? 'pishtaz';
    if (!in_array($selected_carrier, ['pishtaz', 'tipax'])) {
        $selected_carrier = 'pishtaz';
    }

    $total_cart_weight_grams = 0;
    foreach ($pending_items as $pi) {
        $total_cart_weight_grams += 450 * (int)$pi['qty'];
    }
    if ($total_cart_weight_grams <= 0) $total_cart_weight_grams = 450;

    $extra_weight_fee = 0;
    if ($total_cart_weight_grams > 1000) {
        $extra_kg = ceil(($total_cart_weight_grams - 1000) / 1000.0);
        $extra_weight_fee = (int)($extra_kg * 12000);
    }

    $free_shipping_enabled   = (get_setting($pdo, 'free_shipping_enabled', '1') === '1');
    $free_shipping_threshold = (int)get_setting($pdo, 'free_shipping_threshold_toman', 600000);
    $standard_shipping_cost  = (int)get_setting($pdo, 'standard_shipping_cost_toman', 49000);

    $pishtaz_total_cost = $standard_shipping_cost + $extra_weight_fee;
    $tipax_total_cost   = max($standard_shipping_cost + 19000, 68000) + $extra_weight_fee;

    if ($checkout_type === 'autoship') {
        $shipping_cost = 0; // Always free for Autoship subscribers
        $carrier_label = 'ارسال خودکار دوره‌ای (رایگان)';
    } else {
        $is_free_eligible = ($free_shipping_enabled && $taxable_subtotal >= $free_shipping_threshold);
        if ($is_free_eligible) {
            if ($selected_carrier === 'tipax') {
                $shipping_cost = max(0, $tipax_total_cost - $pishtaz_total_cost);
                $carrier_label = 'تیپاکس اکسپرس (با تخفیف سقف خرید)';
            } else {
                $shipping_cost = 0;
                $carrier_label = 'پست پیشتاز سراسری (ارسال رایگان)';
            }
        } else {
            $shipping_cost = ($selected_carrier === 'tipax') ? $tipax_total_cost : $pishtaz_total_cost;
            $carrier_label = ($selected_carrier === 'tipax') ? 'تیپاکس اکسپرس' : 'پست پیشتاز سراسری';
        }
    }

    // 10% Statutory VAT computed on BOTH (Product Subtotal + Shipping Cost)
    $tax_rate_pct = (float)get_setting($pdo, 'tax_rate_percent', 10.0);
    $tax_base     = $taxable_subtotal + $shipping_cost;
    $tax_amount   = (int)round($tax_base * ($tax_rate_pct / 100.0));

    $final_total  = $tax_base + $tax_amount;

    $duration_months = (int)($_GET['duration'] ?? 3);
    if (!in_array($duration_months, [3, 6, 12])) $duration_months = 3;
    $payment_model = ($_GET['model'] ?? 'monthly') === 'upfront' ? 'upfront' : 'monthly';

    if ($checkout_type === 'autoship' && $payment_model === 'upfront') {
        $payable_today = round(($final_total * $duration_months) * 0.95);
        $order_desc = "خرید یک‌جا اشتراک {$duration_months} ماهه تحویل خودکار آسنا (" . count($pending_items) . " قلم با ارسال رایگان و مالیات ارزش افزوده)";
    } elseif ($checkout_type === 'autoship') {
        $payable_today = $final_total;
        $order_desc = "پرداخت نوبت ۱ از اشتراک {$duration_months} ماهه تحویل خودکار آسنا (" . count($pending_items) . " قلم با ارسال رایگان و مالیات ارزش افزوده)";
    } else {
        $payable_today = $final_total;
        $promoDesc = $promoCode ? " [کد تخفیف: {$promoCode}]" : "";
        $shippingDesc = ($shipping_cost === 0) ? " [ارسال رایگان]" : " [کرایه حمل: " . number_format($shipping_cost) . " ت ({$carrier_label})]";
        $order_desc = "خرید از فروشگاه آسنا — " . count($pending_items) . " محصول{$shippingDesc}{$promoDesc} [مالیات کل: " . number_format($tax_amount) . " ت]";
    }
}

// ── Multi-Driver Payment Service Routing ──────────────────────────────────────
require_once __DIR__ . '/includes/PaymentService.php';
$paymentService = new PaymentService($pdo);

$orderType = $isBooking ? 'booking' : ($isSmsPackage ? 'sms_package' : 'cart');
$orderMetadata = [
    'email' => $currentUser['email'] ?? '',
    'mobile' => (string)($currentUser['phone'] ?? ''),
    'checkout_type' => $checkout_type ?? 'standard',
    'tax_amount' => $tax_amount ?? 0,
    'tax_rate_pct' => $tax_rate_pct ?? 10.0,
    'shipping_cost' => $shipping_cost ?? 0,
    'carrier_name' => $carrier_label ?? 'شرکت ملی پست',
    'dispatch_sla' => 'حداکثر ۲۴ ساعت کاری',
    'free_shipping_threshold' => $free_shipping_threshold ?? 600000,
    'promo_code' => $promoCode ?? null,
    'discount_amount' => $promoDiscount ?? 0
];

$result = $paymentService->requestPayment(
    (int)$_SESSION['user_id'],
    $payable_today,
    $order_desc,
    $orderType,
    null,
    $orderMetadata
);

if (!$result['success']) {
    $fallbackRedirect = $isBooking ? 'booking.php' : 'cart.php';
    $_SESSION['profile_error'] = 'خطا در ایجاد تراکنش پرداخت: ' . ($result['error'] ?? $result['message'] ?? 'خطای نامشخص');
    header("Location: {$fallbackRedirect}");
    exit;
}

// ── Store authority in pending order ──────────────────────────────────────────
if ($isBooking || $isSmsPackage) {
    $_SESSION['pending_order']['authority'] = $result['authority'];
    $_SESSION['pending_order']['user_id']   = (int)$_SESSION['user_id'];
} else {
    $_SESSION['pending_order'] = [
        'type'            => 'cart',
        'checkout_type'   => $checkout_type,
        'user_id'         => (int)$_SESSION['user_id'],
        'items'           => $pending_items,
        'subtotal'        => $subtotal,
        'discount_amount' => $promoDiscount,
        'promo_code'      => $promoCode,
        'promo_id'        => $promoId,
        'tax_amount'      => $tax_amount,
        'shipping_cost'   => $shipping_cost,
        'carrier_name'    => $carrier_label,
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
