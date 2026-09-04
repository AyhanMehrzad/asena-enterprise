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
$product_id = (int)($_POST['product_id'] ?? 0);
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

if ($product_id > 0) {
    // Helper to resolve product from either products or pharmacy_medicines
    $prodLookup = function(PDO $p, int $id): ?array {
        $st = $p->prepare("SELECT id, is_autoship, stock, 'product' as src FROM products WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
        $st = $p->prepare("SELECT id, is_autoship, stock, 'pharmacy' as src FROM pharmacy_medicines WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    };

    switch ($action) {
        case 'add':
            // Verify product exists and is in stock before adding
            $product_row = $prodLookup($pdo, $product_id);
            if ($product_row && (int)$product_row['stock'] > 0) {
                $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
                
                // Only place in autoship tab if explicitly requested as autoship
                if ($type === 'autoship' && !empty($product_row['is_autoship'])) {
                    $_SESSION['cart_types'][$product_id] = 'autoship';
                    if (empty($_SESSION['cart_frequency'][$product_id])) {
                        $_SESSION['cart_frequency'][$product_id] = !empty($frequency) ? $frequency : '1_month';
                    }
                } elseif (!isset($_SESSION['cart_types'][$product_id])) {
                    $_SESSION['cart_types'][$product_id] = 'standard';
                }
            }
            break;

        case 'toggle_type':
            $current_type = $_SESSION['cart_types'][$product_id] ?? 'standard';
            if ($current_type === 'autoship') {
                $_SESSION['cart_types'][$product_id] = 'standard';
            } else {
                $product_row = $prodLookup($pdo, $product_id);
                if ($product_row && !empty($product_row['is_autoship'])) {
                    $_SESSION['cart_types'][$product_id] = 'autoship';
                    if (empty($_SESSION['cart_frequency'][$product_id])) {
                        $_SESSION['cart_frequency'][$product_id] = '1_month';
                    }
                }
            }
            break;

        case 'set_frequency':
            if (in_array($frequency, ['2_weeks', '1_month', '2_months', '3_months'])) {
                $_SESSION['cart_frequency'][$product_id] = $frequency;
                $_SESSION['cart_types'][$product_id] = 'autoship';
            }
            break;

        case 'increase':
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]++;
            }
            break;

        case 'decrease':
            if (isset($_SESSION['cart'][$product_id])) {
                if ($_SESSION['cart'][$product_id] > 1) {
                    $_SESSION['cart'][$product_id]--;
                } else {
                    unset($_SESSION['cart'][$product_id]);
                    unset($_SESSION['cart_types'][$product_id]);
                    unset($_SESSION['cart_frequency'][$product_id]);
                }
            }
            break;

        case 'remove':
            unset($_SESSION['cart'][$product_id]);
            unset($_SESSION['cart_types'][$product_id]);
            unset($_SESSION['cart_frequency'][$product_id]);
            break;
    }
}

// Determine active tab to persist
$active_tab = $_POST['active_tab'] ?? '';
if (empty($active_tab)) {
    if ($action === 'set_frequency' || $type === 'autoship' || ($_SESSION['cart_types'][$product_id] ?? '') === 'autoship') {
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
    echo json_encode([
        'status' => 'success', 
        'cart_count' => array_sum($_SESSION['cart']),
        'standard_count' => $standard_count,
        'autoship_count' => $autoship_count,
        'item_type' => $_SESSION['cart_types'][$product_id] ?? 'standard',
        'item_frequency' => $_SESSION['cart_frequency'][$product_id] ?? '1_month',
        'active_tab' => $active_tab
    ]);
    exit;
}

// Redirect back to cart with preserved active tab
header('Location: ../cart.php?tab=' . urlencode($active_tab));
exit;
