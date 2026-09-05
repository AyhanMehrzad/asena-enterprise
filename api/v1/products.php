<?php
/**
 * ASENA Enterprise - Product Catalog REST API
 * Endpoint: GET /api/v1/products.php?q=...&category=...&flash=1
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/App.php';

App::boot();

$db = App::db();
$cache = App::cache();

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$cacheKey = "api_products_" . md5("{$q}_{$category}_{$limit}_{$page}");

$result = $cache->remember($cacheKey, 60, function() use ($db, $q, $category, $limit, $offset) {
    $where = ["1=1"];
    $params = [];

    if ($q !== '') {
        $where[] = "(name LIKE ? OR description LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    if ($category !== '') {
        $where[] = "category = ?";
        $params[] = $category;
    }

    $whereSql = implode(' AND ', $where);

    // Total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Data
    $stmt = $db->prepare("SELECT id, name, category, price, stock, image_url, description FROM products WHERE $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'total' => $total,
        'products' => $products
    ];
});

echo json_encode([
    'success' => true,
    'page' => $page,
    'limit' => $limit,
    'total' => $result['total'],
    'data' => $result['products']
], JSON_UNESCAPED_UNICODE);
