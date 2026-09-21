<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// POST only — blocks GET-based cart manipulation via URL/image tags
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ../cart.php');
    exit;
}

csrf_verify();

$action     = $_POST['action'] ?? '';
$raw_id     = trim((string)($_POST['product_id'] ?? ''));
$source     = trim($_POST['item_source'] ?? $_POST['source'] ?? '');
$type       = $_POST['type'] ?? 'standard'; // 'standard' or 'autoship'
$frequency  = $_POST['frequency'] ?? '1_month'; // '2_weeks', '1_month', '2_months', '3_months'

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['cart_types'])) {
    $_SESSION['cart_types'] = [];
}
if (!isset($_SESSION['cart_frequency'])) {
    $_SESSION['cart_frequency'] = [];
}
if (!isset($_SESSION['cart_sources'])) {
    $_SESSION['cart_sources'] = [];
}

// Normalize cart key and source
$product_id = 0;
if (str_starts_with($raw_id, 'med_')) {
    $source = 'pharmacy';
    $product_id = (int)substr($raw_id, 4);
    $cart_key = $raw_id;
} elseif (str_starts_with($raw_id, 'prod_')) {
    $source = 'product';
    $product_id = (int)substr($raw_id, 5);
    $cart_key = $raw_id;
} else {
    $product_id = (int)$raw_id;
    if ($source === 'pharmacy') {
        $cart_key = 'med_' . $product_id;
    } elseif ($source === 'product') {
        $cart_key = 'prod_' . $product_id;
    } else {
        if (isset($_SESSION['cart']['med_' . $product_id])) {
            $cart_key = 'med_' . $product_id;
            $source = 'pharmacy';
        } elseif (isset($_SESSION['cart']['prod_' . $product_id])) {
            $cart_key = 'prod_' . $product_id;
            $source = 'product';
        } elseif (isset($_SESSION['cart'][$product_id])) {
            $cart_key = $product_id;
            $source = $_SESSION['cart_sources'][$product_id] ?? 'product';
        } else {
            $source = 'product';
            $cart_key = 'prod_' . $product_id;
        }
    }
}

$action_status = "success";
$action_message = "کالا با موفقیت به سبد خرید اضافه شد";

if ($product_id > 0) {
    // Helper to resolve product with preference to designated source
    $prodLookup = function(PDO $p, int $id, string $preferredSource = 'product'): ?array {
        if ($preferredSource === 'pharmacy') {
            $st = $p->prepare("SELECT id, name, price, is_autoship, stock, 'pharmacy' as src FROM pharmacy_medicines WHERE id = ?");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        }
        $st = $p->prepare("SELECT id, name, price, is_autoship, stock, 'product' as src FROM products WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;

        if ($preferredSource !== 'pharmacy') {
            $st = $p->prepare("SELECT id, name, price, is_autoship, stock, 'pharmacy' as src FROM pharmacy_medicines WHERE id = ?");
            $st->execute([$id]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        return null;
    };

    switch ($action) {
        case 'add':
            $product_row = $prodLookup($pdo, $product_id, $source);
            if ($product_row && (int)$product_row['stock'] > 0) {
                $currQty = $_SESSION['cart'][$cart_key] ?? ($_SESSION['cart'][$product_id] ?? 0);
                $maxAllowed = min((int)$product_row['stock'], 50);
                if ($currQty < $maxAllowed) {
                    $_SESSION['cart'][$cart_key] = $currQty + 1;
                    $_SESSION['cart_sources'][$cart_key] = $source;
                    if ($product_id > 0 && isset($_SESSION['cart'][$product_id]) && $cart_key !== $product_id) {
                        unset($_SESSION['cart'][$product_id]);
                    }
                    
                    if ($type === 'autoship' && !empty($product_row['is_autoship'])) {
                        $_SESSION['cart_types'][$cart_key] = 'autoship';
                        if (empty($_SESSION['cart_frequency'][$cart_key])) {
                            $_SESSION['cart_frequency'][$cart_key] = !empty($frequency) ? $frequency : '1_month';
                        }
                    } elseif (!isset($_SESSION['cart_types'][$cart_key])) {
                        $_SESSION['cart_types'][$cart_key] = 'standard';
                    }
                } else {
                    $action_status = 'error';
                    $action_message = 'سقف مجاز سفارش برای این کالا تکمیل شده است (حداکثر ' . $maxAllowed . ' عدد).';
                }
            } else {
                $action_status = 'error';
                $action_message = ($product_row && (int)$product_row['stock'] <= 0) 
                    ? 'موجودی این کالا به اتمام رسیده است.' 
                    : 'کالای مورد نظر در دسترس نیست.';
            }
            break;

        case 'toggle_type':
            $targetKey = isset($_SESSION['cart'][$cart_key]) ? $cart_key : (isset($_SESSION['cart'][$product_id]) ? $product_id : $cart_key);
            $current_type = $_SESSION['cart_types'][$targetKey] ?? 'standard';
            if ($current_type === 'autoship') {
                $_SESSION['cart_types'][$targetKey] = 'standard';
            } else {
                $product_row = $prodLookup($pdo, $product_id, $source);
                if ($product_row && !empty($product_row['is_autoship'])) {
                    $_SESSION['cart_types'][$targetKey] = 'autoship';
                    if (empty($_SESSION['cart_frequency'][$targetKey])) {
                        $_SESSION['cart_frequency'][$targetKey] = '1_month';
                    }
                }
            }
            break;

        case 'set_frequency':
            $targetKey = isset($_SESSION['cart'][$cart_key]) ? $cart_key : (isset($_SESSION['cart'][$product_id]) ? $product_id : $cart_key);
            if (in_array($frequency, ['2_weeks', '1_month', '2_months', '3_months'])) {
                $_SESSION['cart_frequency'][$targetKey] = $frequency;
                $_SESSION['cart_types'][$targetKey] = 'autoship';
            }
            break;

        case 'increase':
            $targetKey = isset($_SESSION['cart'][$cart_key]) ? $cart_key : (isset($_SESSION['cart'][$product_id]) ? $product_id : null);
            if ($targetKey !== null) {
                $product_row = $prodLookup($pdo, $product_id, $source);
                $maxAllowed = ($product_row && isset($product_row['stock'])) ? min((int)$product_row['stock'], 50) : 50;
                if ($_SESSION['cart'][$targetKey] < $maxAllowed) {
                    $_SESSION['cart'][$targetKey]++;
                } else {
                    $action_status = 'error';
                    $action_message = 'سقف مجاز سفارش برای این کالا تکمیل شده است.';
                }
            }
            break;

        case 'decrease':
            $targetKey = isset($_SESSION['cart'][$cart_key]) ? $cart_key : (isset($_SESSION['cart'][$product_id]) ? $product_id : null);
            if ($targetKey !== null) {
                if ($_SESSION['cart'][$targetKey] > 1) {
                    $_SESSION['cart'][$targetKey]--;
                } else {
                    unset($_SESSION['cart'][$targetKey], $_SESSION['cart_types'][$targetKey], $_SESSION['cart_frequency'][$targetKey], $_SESSION['cart_sources'][$targetKey]);
                    if (is_string($targetKey) && $product_id > 0) {
                        unset($_SESSION['cart'][$product_id], $_SESSION['cart_types'][$product_id], $_SESSION['cart_frequency'][$product_id], $_SESSION['cart_sources'][$product_id]);
                    }
                }
            }
            break;

        case 'remove':
            $targetKey = isset($_SESSION['cart'][$cart_key]) ? $cart_key : (isset($_SESSION['cart'][$product_id]) ? $product_id : $cart_key);
            unset($_SESSION['cart'][$targetKey], $_SESSION['cart_types'][$targetKey], $_SESSION['cart_frequency'][$targetKey], $_SESSION['cart_sources'][$targetKey]);
            if (is_string($targetKey) && $product_id > 0) {
                unset($_SESSION['cart'][$product_id], $_SESSION['cart_types'][$product_id], $_SESSION['cart_frequency'][$product_id], $_SESSION['cart_sources'][$product_id]);
            }
            break;
    }
} elseif ($action === 'set_carrier') {
    $carrier = trim($_POST['carrier'] ?? 'pishtaz');
    if (in_array($carrier, ['pishtaz', 'tipax'])) {
        $_SESSION['selected_carrier'] = $carrier;
        $action_status = "success";
        $action_message = "روش ارسال با موفقیت به‌روزرسانی شد.";
    }
}

// Determine active tab to persist
$active_tab = $_POST['active_tab'] ?? '';
if (empty($active_tab)) {
    $checkKey = $cart_key ?? $product_id;
    if ($action === 'set_frequency' || $type === 'autoship' || ($_SESSION['cart_types'][$checkKey] ?? '') === 'autoship') {
        $active_tab = 'autoship';
    } else {
        $active_tab = 'standard';
    }
}
$_SESSION['active_cart_tab'] = $active_tab;

if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
    header('Content-Type: application/json');
    $standard_count = 0;
    $autoship_count = 0;
    foreach ($_SESSION['cart'] as $p_id => $qty) {
        if (($_SESSION['cart_types'][$p_id] ?? 'standard') === 'autoship') {
            $autoship_count += $qty;
        } else {
            $standard_count += $qty;
        }
    }
    $respKey = $cart_key ?? $product_id;
    echo json_encode([
        'status' => $action_status, 
        'message' => $action_message,
        'cart_count' => array_sum($_SESSION['cart'] ?? []),
        'standard_count' => $standard_count,
        'autoship_count' => $autoship_count,
        'item_type' => $_SESSION['cart_types'][$respKey] ?? 'standard',
        'item_frequency' => $_SESSION['cart_frequency'][$respKey] ?? '1_month',
        'active_tab' => $active_tab
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Redirect back to cart with preserved active tab
header('Location: ../cart.php?tab=' . urlencode($active_tab));
exit;
