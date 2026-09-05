<?php
/**
 * ASENA Enterprise - B2B RFQ (Request for Quotation) Action Handler
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/WholesaleService.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد نامعتبر است.']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'لطفاً ابتدا وارد حساب کاربری خود شوید.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$clinicName = trim($_POST['clinic_name'] ?? '');
$vetLicense = trim($_POST['vet_license_num'] ?? '');
$contactPerson = trim($_POST['contact_person'] ?? '');
$contactPhone = trim($_POST['contact_phone'] ?? '');
$targetDate = !empty($_POST['target_delivery_date']) ? $_POST['target_delivery_date'] : null;
$notes = trim($_POST['notes'] ?? '');

if (empty($clinicName) || empty($contactPerson) || empty($contactPhone)) {
    echo json_encode(['success' => false, 'message' => 'لطفاً نام کلینیک، نام رابط و شماره تماس را تکمیل فرمایید.']);
    exit;
}

// Items payload
$rawItems = $_POST['items'] ?? [];
if (empty($rawItems) || !is_array($rawItems)) {
    echo json_encode(['success' => false, 'message' => 'حداقل یک ردیف کالا برای استعلام باید مشخص گردد.']);
    exit;
}

$items = [];
foreach ($rawItems as $it) {
    $title = trim($it['title'] ?? '');
    $qty = (int)($it['quantity'] ?? 1);
    if (!empty($title) && $qty > 0) {
        $items[] = [
            'title'        => $title,
            'quantity'     => $qty,
            'product_id'   => !empty($it['product_id']) ? (int)$it['product_id'] : null,
            'is_pharmacy'  => !empty($it['is_pharmacy']) ? 1 : 0,
            'target_price' => !empty($it['target_price']) ? (int)$it['target_price'] : null,
        ];
    }
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'اطلاعات کالاهای درخواستی معتبر نمی‌باشد.']);
    exit;
}

try {
    $wholesale = new WholesaleService($pdo);
    $rfqId = $wholesale->submitRfq($userId, [
        'clinic_name'          => $clinicName,
        'vet_license_num'      => $vetLicense,
        'contact_person'       => $contactPerson,
        'contact_phone'        => $contactPhone,
        'target_delivery_date' => $targetDate,
        'notes'                => $notes,
    ], $items);

    echo json_encode([
        'success' => true,
        'message' => 'درخواست استعلام قیمت عمده (RFQ) با موفقیت ثبت شد و ظرف ۲۴ ساعت بررسی خواهد شد.',
        'rfq_id'  => $rfqId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در ثبت استعلام: ' . $e->getMessage()]);
}
