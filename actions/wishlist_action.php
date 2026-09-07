<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if (!$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه محصول نامعتبر است.']);
    exit;
}

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = $is_logged_in ? (int)$_SESSION['user_id'] : null;

try {
    if ($is_logged_in) {
        // Check if already in DB wishlist
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        if ($stmt->rowCount() > 0) {
            // Remove from wishlist
            $delete = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $delete->execute([$user_id, $product_id]);
            $action = 'removed';
            $in_wishlist = false;
        } else {
            // Add to wishlist
            $insert = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $insert->execute([$user_id, $product_id]);
            $action = 'added';
            $in_wishlist = true;
        }

        // Count total items for logged in user
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $cStmt->execute([$user_id]);
        $total_count = (int)$cStmt->fetchColumn();
    } else {
        // Guest user wishlist (session based)
        if (!isset($_SESSION['guest_wishlist']) || !is_array($_SESSION['guest_wishlist'])) {
            $_SESSION['guest_wishlist'] = [];
        }
        
        $key = array_search($product_id, $_SESSION['guest_wishlist']);
        if ($key !== false) {
            unset($_SESSION['guest_wishlist'][$key]);
            $_SESSION['guest_wishlist'] = array_values($_SESSION['guest_wishlist']);
            $action = 'removed';
            $in_wishlist = false;
        } else {
            $_SESSION['guest_wishlist'][] = $product_id;
            $action = 'added';
            $in_wishlist = true;
        }
        $total_count = count($_SESSION['guest_wishlist']);
    }

    echo json_encode([
        'status' => 'success',
        'action' => $action,
        'in_wishlist' => $in_wishlist,
        'count' => $total_count,
        'message' => $in_wishlist ? 'به لیست علاقه‌مندی‌ها اضافه شد ❤️' : 'از لیست علاقه‌مندی‌ها حذف شد'
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطای ارتباط با پایگاه داده رخ داد.'], JSON_UNESCAPED_UNICODE);
}
?>
