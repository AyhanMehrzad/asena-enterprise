<?php
/**
 * ASENA Enterprise - Secure Pet Meal Plan Chart Viewer
 * Renders clinical diet plans and meal schedules from pet medical documents.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$fileParam = trim((string)($_GET['file'] ?? ''));
$serial = trim((string)($_GET['serial'] ?? ''));
$docId = (int)($_GET['doc_id'] ?? 0);

$filePath = null;

// 1. Resolve by doc_id if provided
if ($docId > 0 && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT file_path FROM pet_documents WHERE id = ? LIMIT 1");
        $stmt->execute([$docId]);
        $filePath = $stmt->fetchColumn();
    } catch (Exception $e) {}
}

// 2. Resolve by file parameter
if (empty($filePath) && !empty($fileParam)) {
    // Sanitize: allow only basenames or safe uploads/meal_plans paths
    $cleanBasename = basename($fileParam);
    $targetPath = __DIR__ . '/uploads/meal_plans/' . $cleanBasename;
    if (file_exists($targetPath)) {
        $filePath = $targetPath;
    }
}

// 3. Resolve by serial
if (empty($filePath) && !empty($serial)) {
    $cleanSerial = preg_replace('/[^A-Za-z0-9_-]/', '', $serial);
    $matches = glob(__DIR__ . '/uploads/meal_plans/meal_plan_*' . strtolower($cleanSerial) . '*.html');
    if (!empty($matches)) {
        $filePath = $matches[0];
    }
}

// If file resolved, render
if (!empty($filePath) && file_exists($filePath)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($filePath);
    exit;
}

// If not found, show clean notice with link back
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سند برنامه غذایی یافت نشد | آسنا</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; background: #f8fafc; text-align: center; padding: 60px 20px; color: #1e293b; }
        .box { max-width: 500px; margin: 0 auto; background: #ffffff; padding: 40px; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        h2 { color: #002d72; font-weight: 900; margin-bottom: 12px; }
        p { font-size: 13px; color: #64748b; margin-bottom: 24px; line-height: 1.6; }
        a { display: inline-block; background: #059669; color: #ffffff; padding: 10px 24px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 13px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>سند رژیم غذایی یافت نشد</h2>
        <p>فایل مورد نظر یافت نشد یا ممکن است هنوز از طریق محاسبه‌گر هوشمند تغذیه صادر نشده باشد.</p>
        <a href="calculator.php">بازگشت به محاسبه‌گر تغذیه بالینی</a>
    </div>
</body>
</html>
