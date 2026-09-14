<?php
/**
 * server_ip.php — Diagnostic tool to discover outbound public server IP
 * Used to whitelist server IP in Melipayamak Panel > Settings > Allowed IPs
 */
header('Content-Type: text/html; charset=utf-8');

$ip = 'نامشخص';
$sources = [
    'https://api.ipify.org',
    'https://ifconfig.me/ip',
    'https://icanhazip.com'
];

foreach ($sources as $src) {
    $ch = curl_init($src);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200 && !empty(trim((string)$res))) {
        $ip = trim($res);
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آی‌پی سرور هاستینگ | جهت ثبت در ملی‌پیامک</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; background: #f8fafc; padding: 40px 16px; display: flex; justify-content: center; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; max-width: 600px; width: 100%; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .ip-box { background: #eff6ff; border: 2px dashed #3b82f6; padding: 18px; border-radius: 12px; font-size: 24px; font-weight: 900; text-align: center; color: #1d4ed8; letter-spacing: 1px; margin: 20px 0; direction: ltr; }
        .guide { background: #fefce8; border: 1px solid #fef08a; padding: 16px; border-radius: 10px; font-size: 13px; color: #854d0e; line-height: 1.8; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="font-size: 20px; font-weight: 800; color: #0f172a;">🌐 آی‌پی عمومی خروجی سرور هاست شما</h2>
        <p style="font-size: 13px; color: #64748b; margin-top: 6px;">این آی‌پی همان آدرسی است که سرور شما هنگام اتصال به وب‌سرویس ملی‌پیامک ارسال می‌کند:</p>

        <div class="ip-box" id="ipBox"><?= htmlspecialchars($ip) ?></div>

        <div class="guide">
            <strong>راهنمای رفع خطای ۱۰۸- (مسدودی موقت فایروال ملی‌پیامک):</strong>
            <ol style="margin-right: 20px; margin-top: 8px;">
                <li>وارد <a href="https://payamak-panel.com" target="_blank" style="color: #2563eb; font-weight: bold;">پنل کاربری ملی‌پیامک</a> شوید.</li>
                <li>از منوی کناری به بخش <strong>تنظیمات > IP های مجاز وب سرویس</strong> مراجعه فرمایید.</li>
                <li>آی‌پی بالا (<code style="background: white; padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($ip) ?></code>) را کپی کرده و در آن بخش ثبت کنید.</li>
                <li>با این کار، سرور شما در لیست سفید قرار گرفته و فایروال ملی‌پیامک هرگز آن را مسدود نخواهد کرد.</li>
            </ol>
        </div>

        <div style="text-align: center;">
            <a href="test_sms.php" class="btn">📱 رفتن به کنسول تست پیامک</a>
        </div>
    </div>
</body>
</html>
