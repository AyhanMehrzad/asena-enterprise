<?php
/**
 * actions/promo_action.php - AJAX Handler for applying and removing promo codes
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/App.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر یا منقضی شده است. لطفاً صفحه را تازه‌سازی کنید.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = trim($_POST['action'] ?? '');
$userId = (int)($_SESSION['user_id'] ?? 0);

// Helper function to calculate real subtotal from cart
function calculateCartSubtotal(PDO $pdo): int {
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) return 0;

    $cartTypes = $_SESSION['cart_types'] ?? [];
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $foundIds = array_column($products, 'id');
    $remainingIds = array_diff($ids, $foundIds);
    if (!empty($remainingIds)) {
        $remPlaceholders = implode(',', array_fill(0, count($remainingIds), '?'));
        $stmtMeds = $pdo->prepare("SELECT * FROM pharmacy_medicines WHERE id IN ($remPlaceholders)");
        $stmtMeds->execute(array_values($remainingIds));
        $products = array_merge($products, $stmtMeds->fetchAll(PDO::FETCH_ASSOC));
    }

    $subtotal = 0;
    foreach ($products as $prod) {
        $pid = $prod['id'];
        $qty = (int)($cart[$pid] ?? 0);
        $itemType = $cartTypes[$pid] ?? 'standard';
        $price = (int)$prod['price'];

        if ($itemType === 'autoship') {
            $autoPct = !empty($prod['autoship_discount']) ? (int)$prod['autoship_discount'] : 15;
            $effectivePrice = round($price * (1 - ($autoPct / 100)));
        } else {
            $effectivePrice = $prod['discount_price'] ? (int)$prod['discount_price'] : $price;
        }
        $subtotal += ($effectivePrice * $qty);
    }

    return $subtotal;
}

$subtotal = calculateCartSubtotal($pdo);
if ($subtotal <= 0 && isset($_POST['subtotal']) && (int)$_POST['subtotal'] > 0) {
    // Fallback for appointment or custom direct checkout if cart is empty
    $subtotal = (int)$_POST['subtotal'];
}

$promoService = App::promo();
$taxRatePct = (float)get_setting($pdo, 'tax_rate_percent', 10.0);

if ($action === 'apply') {
    $code = trim($_POST['code'] ?? '');
    $validation = $promoService->validatePromo($code, $userId, $subtotal);

    if (!$validation['valid']) {
        echo json_encode([
            'success' => false,
            'message' => $validation['message']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Store in session
    $_SESSION['applied_promo'] = $validation;

    $totals = $promoService->calculateTotals($subtotal, $validation, $taxRatePct);

    echo json_encode([
        'success' => true,
        'message' => $validation['message'],
        'promo' => [
            'code' => $validation['code'],
            'title' => $validation['title'],
            'discount_amount' => $validation['discount_amount'],
            'discount_formatted' => number_format($validation['discount_amount']) . ' تومان'
        ],
        'totals' => [
            'subtotal' => $totals['subtotal'],
            'subtotal_formatted' => number_format($totals['subtotal']) . ' تومان',
            'discount_amount' => $totals['discount_amount'],
            'discount_formatted' => number_format($totals['discount_amount']) . ' تومان',
            'taxable_subtotal' => $totals['taxable_subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'tax_formatted' => number_format($totals['tax_amount']) . ' تومان',
            'final_total' => $totals['final_total'],
            'final_total_formatted' => number_format($totals['final_total']) . ' تومان'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;

} elseif ($action === 'remove') {
    unset($_SESSION['applied_promo']);
    $totals = $promoService->calculateTotals($subtotal, null, $taxRatePct);

    echo json_encode([
        'success' => true,
        'message' => 'کد تخفیف حذف گردید.',
        'totals' => [
            'subtotal' => $totals['subtotal'],
            'subtotal_formatted' => number_format($totals['subtotal']) . ' تومان',
            'discount_amount' => 0,
            'discount_formatted' => '۰ تومان',
            'taxable_subtotal' => $totals['taxable_subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'tax_formatted' => number_format($totals['tax_amount']) . ' تومان',
            'final_total' => $totals['final_total'],
            'final_total_formatted' => number_format($totals['final_total']) . ' تومان'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;

} else {
    echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر است.'], JSON_UNESCAPED_UNICODE);
    exit;
}
