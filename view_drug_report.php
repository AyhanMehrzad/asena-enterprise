<?php
/**
 * ASENA Enterprise - Secure Pet Drug Interaction Report Viewer
 * Renders clinical drug interaction dossiers from pet medical documents.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

$fileParam = trim((string)($_GET['file'] ?? ''));
$serial = trim((string)($_GET['serial'] ?? ''));
$docId = (int)($_GET['doc_id'] ?? 0);

$filePath = null;

// 1. Resolve by file parameter first (fast & offline-resilient)
if (!empty($fileParam)) {
    $cleanBasename = basename($fileParam);
    $targetPath = __DIR__ . '/uploads/documents/' . $cleanBasename;
    if (file_exists($targetPath)) {
        $filePath = $targetPath;
    }
}

// 2. Resolve by doc_id if provided
if (empty($filePath) && $docId > 0) {
    try {
        @include_once __DIR__ . '/includes/db.php';
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT file_path FROM pet_documents WHERE id = ? LIMIT 1");
            $stmt->execute([$docId]);
            $dbPath = $stmt->fetchColumn();
            if ($dbPath) {
                if (file_exists($dbPath)) {
                    $filePath = $dbPath;
                } elseif (file_exists(__DIR__ . '/' . $dbPath)) {
                    $filePath = __DIR__ . '/' . $dbPath;
                }
            }
        }
    } catch (Exception $e) {}
}

// 3. Resolve by serial
if (empty($filePath) && !empty($serial)) {
    $cleanSerial = preg_replace('/[^A-Za-z0-9_-]/', '', $serial);
    $matches = glob(__DIR__ . '/uploads/documents/drug_report_*' . strtolower($cleanSerial) . '*.html');
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
    <title>سند تداخلات دارویی یافت نشد | آسنا</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; background: #f8fafc; text-align: center; padding: 60px 20px; color: #1e293b; direction: rtl; }
        .box { max-width: 500px; margin: 0 auto; background: #ffffff; padding: 40px; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        h2 { color: #002d72; font-weight: 900; margin-bottom: 12px; }
        p { font-size: 13px; color: #64748b; margin-bottom: 24px; line-height: 1.6; }
        a { display: inline-block; background: #002d72; color: #ffffff; padding: 10px 24px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 13px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>سند ارزیابی دارویی یافت نشد</h2>
        <p>فایل مورد نظر یافت نشد یا ممکن است هنوز از طریق سامانه پایش تداخلات دارویی صادر نشده باشد.</p>
        <a href="interactions.php">ورود به پایشگر تداخلات دارویی</a>
    </div>
</body>
</html>
