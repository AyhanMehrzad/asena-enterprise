<?php
/**
 * ASENA Enterprise - Universal Live Search Endpoint
 * Searches across Pet Shop Products, Pharmacy Medicines, Clinics/Hospitals, and Doctors.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/Feature.php';

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q, 'UTF-8') < 2) {
    echo json_encode(['status' => 'success', 'results' => [], 'total' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

$param = '%' . $q . '%';
$results = [
    'products' => [],
    'pharmacy' => [],
    'organizations' => [],
    'doctors' => []
];

try {
    // 1. Pet Shop Products
    if (Feature::has('petshop_catalog')) {
        $stmt = $pdo->prepare("
            SELECT id, name, category, price, discount_price, image_url 
            FROM products 
            WHERE name LIKE ? OR category LIKE ? OR brand LIKE ? 
            ORDER BY id DESC 
            LIMIT 4
        ");
        $stmt->execute([$param, $param, $param]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $results['products'][] = [
                'id' => (int)$r['id'],
                'title' => $r['name'],
                'category' => $r['category'] ?? 'پت‌شاپ',
                'price' => (int)($r['discount_price'] ?: $r['price']),
                'old_price' => $r['discount_price'] ? (int)$r['price'] : null,
                'image' => $r['image_url'] ?: 'assets/images/logo.png',
                'url' => 'product_details.php?id=' . (int)$r['id'] . '&type=product',
                'type' => 'product',
                'type_label' => 'کالای پت‌شاپ'
            ];
        }
    }

    // 2. Pharmacy Medicines
    if (Feature::has('pharmacy_catalog')) {
        $stmt = $pdo->prepare("
            SELECT id, name, category, target_animal, price, discount_price, image_url, requires_prescription, is_cold_chain 
            FROM pharmacy_medicines 
            WHERE name LIKE ? OR category LIKE ? OR target_animal LIKE ? 
            ORDER BY id DESC 
            LIMIT 4
        ");
        $stmt->execute([$param, $param, $param]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $results['pharmacy'][] = [
                'id' => (int)$r['id'],
                'title' => $r['name'],
                'category' => $r['category'] ?? 'دارو و مکمل',
                'animal' => $r['target_animal'] ?? 'all',
                'price' => (int)($r['discount_price'] ?: $r['price']),
                'old_price' => $r['discount_price'] ? (int)$r['price'] : null,
                'image' => $r['image_url'] ?: 'assets/images/logo.png',
                'url' => 'product_details.php?id=' . (int)$r['id'] . '&type=pharmacy',
                'requires_prescription' => (bool)$r['requires_prescription'],
                'is_cold_chain' => (bool)$r['is_cold_chain'],
                'type' => 'pharmacy',
                'type_label' => 'داروخانه تخصصی'
            ];
        }
    }

    // 3. Clinics & Hospitals (Organizations)
    if (Feature::has('clinic_booking')) {
        $stmt = $pdo->prepare("
            SELECT id, name, slug, type, city, address, rating, review_count, is_24_7, logo_url 
            FROM organizations 
            WHERE status = 'approved' AND (name LIKE ? OR city LIKE ? OR address LIKE ?) 
            ORDER BY is_24_7 DESC, rating DESC 
            LIMIT 3
        ");
        $stmt->execute([$param, $param, $param]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $results['organizations'][] = [
                'id' => (int)$r['id'],
                'title' => $r['name'],
                'city' => $r['city'],
                'rating' => (float)$r['rating'],
                'review_count' => (int)$r['review_count'],
                'is_24_7' => (bool)$r['is_24_7'],
                'image' => $r['logo_url'] ?: 'assets/images/logo.png',
                'url' => !empty($r['slug']) ? 'organization_profile.php?slug=' . urlencode($r['slug']) : 'organization_profile.php?id=' . (int)$r['id'],
                'type' => 'organization',
                'type_label' => $r['is_24_7'] ? 'بیمارستان ۲۴/۷' : 'مرکز درمانی'
            ];
        }
    }

    // 4. Doctors / Specialists
    if (Feature::has('clinic_booking')) {
        $stmt = $pdo->prepare("
            SELECT id, name, specialty, rating, review_count, image_url 
            FROM doctors 
            WHERE name LIKE ? OR specialty LIKE ? 
            ORDER BY rating DESC 
            LIMIT 3
        ");
        $stmt->execute([$param, $param]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $results['doctors'][] = [
                'id' => (int)$r['id'],
                'title' => $r['name'],
                'specialty' => $r['specialty'],
                'rating' => (float)$r['rating'],
                'review_count' => (int)$r['review_count'],
                'image' => $r['image_url'] ?: 'assets/images/vet-hero.png',
                'url' => 'booking.php?doctor_id=' . (int)$r['id'],
                'type' => 'doctor',
                'type_label' => 'پزشک متخصص'
            ];
        }
    }

    $totalCount = count($results['products']) + count($results['pharmacy']) + count($results['organizations']) + count($results['doctors']);

    echo json_encode([
        'status' => 'success',
        'query' => $q,
        'total' => $totalCount,
        'results' => $results
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
