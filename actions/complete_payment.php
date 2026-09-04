<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gateway.php';

// ── Gate 1: Must arrive via GET callback from ZarinPal ───────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: ../cart.php');
    exit;
}

$authority = trim($_GET['Authority'] ?? $_GET['authority'] ?? '');
$status    = strtoupper(trim($_GET['Status'] ?? $_GET['status'] ?? ''));

// ── Gate 2: Gateway reported failure ──────────────────────────────────────────
if ($status !== 'OK' || empty($authority)) {
    unset($_SESSION['pending_order']);
    $_SESSION['profile_error'] = 'پرداخت لغو شد یا با خطا مواجه گردید.';
    header('Location: ../profile.php');
    exit;
}

// ── Gate 3: Authority must match session — prevents authority injection ────────
$pending = $_SESSION['pending_order'] ?? null;
$is_booking = ($pending['type'] ?? '') === 'booking';
$is_subscription = ($pending['type'] ?? '') === 'subscription';

if (!$pending
    || ($pending['authority'] ?? '') !== $authority
    || empty($pending['total_amount'])
    || (!$is_booking && !$is_subscription && empty($pending['items']))
) {
    unset($_SESSION['pending_order']);
    $_SESSION['profile_error'] = 'اطلاعات سفارش نامعتبر یا منقضی شده است.';
    header('Location: ../cart.php');
    exit;
}

// ── Gate 4: Server-to-server verification with ZarinPal ──────────────────────
$gateway    = new ZarinPalGateway();
$verified   = $gateway->verifyPayment((int)$pending['total_amount'], $authority);

if (!$verified['success']) {
    unset($_SESSION['pending_order']);
    $_SESSION['profile_error'] = 'تأییدیه پرداخت از درگاه دریافت نشد: ' . $verified['error'];
    header('Location: ../cart.php');
    exit;
}

$ref_id = $verified['ref_id'];

// ── Verified — commit atomically ──────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    $user_id      = (int)$_SESSION['user_id'];
    $total_amount = (int)$pending['total_amount'];
    $items        = $pending['items'] ?? [];

    if ($is_subscription) {
        $months = intval($pending['plan_id'] ?? 1);
        if ($months < 1) $months = 1;
        $sub_freq = $pending['frequency'] ?? '1_month';
        $freq_days = get_autoship_frequency_days($sub_freq);

        $subStmt = $pdo->prepare(
            "INSERT INTO user_subscriptions (user_id, plan_name, amount, status, next_delivery_date, duration_months, payment_model, delivery_frequency)
             VALUES (?, ?, ?, 'active', DATE_ADD(CURRENT_DATE, INTERVAL ? DAY), ?, 'monthly', ?)"
        );
        $subStmt->execute([$user_id, $pending['plan_name'], $total_amount, $freq_days, $months, $sub_freq]);
        $sub_id = $pdo->lastInsertId();

        $delStmt = $pdo->prepare("INSERT INTO subscription_deliveries (subscription_id, delivery_month, scheduled_date, status, payment_status) VALUES (?, ?, ?, ?, ?)");
        
        for ($i = 1; $i <= $months; $i++) {
            $days = ($i - 1) * $freq_days;
            $scheduled = date('Y-m-d', strtotime("+$days days"));
            $del_status = ($i === 1) ? 'processing' : 'pending';
            $delStmt->execute([$sub_id, $i, $scheduled, $del_status, 'paid']);
        }
        
        $order_id = $sub_id; // For the success message below
    } else {
        // 1. Create order with real amount and ref_id
    $orderStmt = $pdo->prepare(
        "INSERT INTO orders (user_id, total_amount, status, gateway_ref_id)
         VALUES (?, ?, 'processing', ?)"
    );
    // If gateway_ref_id column doesn't exist yet, fall back gracefully
    try {
        $orderStmt->execute([$user_id, $total_amount, $ref_id]);
    } catch (PDOException $colErr) {
        $orderStmt = $pdo->prepare(
            "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'processing')"
        );
        $orderStmt->execute([$user_id, $total_amount]);
    }
    $order_id = $pdo->lastInsertId();

    // 2. Insert order_items and decrement stock
    if (!$is_booking && !$is_subscription) {
        $itemStmt  = $pdo->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, product_name_snapshot)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['qty'];

            // Dynamically locate item in products or pharmacy_medicines
            $invTable = 'products';
            $checkStmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            $checkStmt->execute([$pid]);
            $current_stock = $checkStmt->fetchColumn();

            if ($current_stock === false) {
                $invTable = 'pharmacy_medicines';
                $checkStmt = $pdo->prepare("SELECT stock FROM pharmacy_medicines WHERE id = ? FOR UPDATE");
                $checkStmt->execute([$pid]);
                $current_stock = $checkStmt->fetchColumn();
            }

            if ($current_stock === false || (int)$current_stock < $qty) {
                throw new RuntimeException(
                    "محصول «{$item['product_name_snapshot']}» موجودی کافی ندارد (موجود: " . (int)$current_stock . ")."
                );
            }

            $itemStmt->execute([
                $order_id, $pid, $qty,
                (int)$item['unit_price'],
                $item['product_name_snapshot'],
            ]);
            
            $stockStmt = $pdo->prepare("UPDATE {$invTable} SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $stockStmt->execute([$qty, $pid, $qty]);

            // If item was purchased via Autoship, auto-create recurring subscription schedule
            if (!empty($item['is_autoship'])) {
                try {
                    $dur_months = (int)($pending['duration_months'] ?? 3);
                    if (!in_array($dur_months, [3, 6, 12])) $dur_months = 3;
                    $pay_model = ($pending['payment_model'] ?? 'monthly') === 'upfront' ? 'upfront' : 'monthly';
                    $freq = $item['frequency'] ?? '1_month';
                    $item_freq_days = get_autoship_frequency_days($freq);

                    $plan_label = "اشتراک {$dur_months} ماهه: " . $item['product_name_snapshot'];
                    $sub_amt = (int)$item['unit_price'] * $qty;

                    $autoSubStmt = $pdo->prepare(
                        "INSERT INTO user_subscriptions (user_id, plan_name, amount, status, next_delivery_date, duration_months, payment_model, delivery_frequency)
                         VALUES (?, ?, ?, 'active', DATE_ADD(CURRENT_DATE, INTERVAL ? DAY), ?, ?, ?)"
                    );
                    $autoSubStmt->execute([$user_id, $plan_label, $sub_amt, $item_freq_days, $dur_months, $pay_model, $freq]);
                    $new_sub_id = $pdo->lastInsertId();

                    $autoDelStmt = $pdo->prepare(
                        "INSERT INTO subscription_deliveries (subscription_id, delivery_month, scheduled_date, status, payment_status)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    for ($m = 1; $m <= $dur_months; $m++) {
                        $sched_date = date('Y-m-d', strtotime("+" . (($m - 1) * $item_freq_days) . " days"));
                        if ($m === 1) {
                            $del_status = 'processing';
                            $del_pay_status = 'paid';
                        } else {
                            $del_status = 'pending';
                            $del_pay_status = ($pay_model === 'upfront') ? 'paid' : 'pending';
                        }
                        $autoDelStmt->execute([$new_sub_id, $m, $sched_date, $del_status, $del_pay_status]);
                    }
                } catch (Exception $subEx) {
                    error_log("Autoship scheduling error: " . $subEx->getMessage());
                }
            }
        }
    }
} // End of else block for regular orders

    // 3. Loyalty points, booking approval & SMS Notifications
    require_once __DIR__ . '/../includes/SmsService.php';
    $sms = new SmsService();

    // Fetch user phone
    $u_stmt = $pdo->prepare("SELECT phone, name FROM users WHERE id = ?");
    $u_stmt->execute([$user_id]);
    $u = $u_stmt->fetch(PDO::FETCH_ASSOC);
    $userPhone = $u['phone'] ?? '';

    if ($is_booking && !empty($pending['booking_id'])) {
        $pdo->prepare("UPDATE appointments SET status = 'approved' WHERE id = ?")
            ->execute([$pending['booking_id']]);
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 20 WHERE id = ?")
            ->execute([$user_id]);
            
        // 1. Fetch appointment & doctor details
        $doc_stmt = $pdo->prepare("
            SELECT d.name as doctor_name, d.phone as doctor_phone, a.pet_name, a.pet_type, a.appointment_date, a.appointment_time, u_doc.phone as doc_user_phone
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            LEFT JOIN users u_doc ON d.user_id = u_doc.id
            WHERE a.id = ?
        ");
        $doc_stmt->execute([$pending['booking_id']]);
        $apptDoc = $doc_stmt->fetch(PDO::FETCH_ASSOC);

        if ($apptDoc) {
            // Send Booking Confirmation SMS to User
            if (!empty($userPhone)) {
                $sms->sendBookingConfirmation($userPhone, $apptDoc['appointment_date'], $apptDoc['appointment_time']);
            }

            // 2. Send SMS to Doctor if doctor notifications enabled
            $doctorSmsEnabled = get_setting($pdo, 'doctor_sms_on_booking', '1');
            $docPhone = !empty($apptDoc['doctor_phone']) ? $apptDoc['doctor_phone'] : ($apptDoc['doc_user_phone'] ?? '');
            if ($doctorSmsEnabled === '1' && !empty($docPhone)) {
                $petDisplay = !empty($apptDoc['pet_name']) ? $apptDoc['pet_name'] : (!empty($apptDoc['pet_type']) ? $apptDoc['pet_type'] : 'پت بیمار');
                $sms->sendDoctorNewAppointmentAlert($docPhone, $apptDoc['doctor_name'], $petDisplay, $apptDoc['appointment_date'], $apptDoc['appointment_time']);
            }

            // 3. Send SMS to Admin if admin booking notifications enabled
            $adminBookingSmsEnabled = get_setting($pdo, 'admin_sms_on_booking', '1');
            if ($adminBookingSmsEnabled === '1') {
                $adminPhones = get_setting($pdo, 'admin_notification_phones', '09146676978');
                if (!empty($adminPhones)) {
                    $textAdmin = "مدیر گرامی، نوبت جدید برای دکتر {$apptDoc['doctor_name']} در تاریخ {$apptDoc['appointment_date']} ساعت {$apptDoc['appointment_time']} در سامانه آسنا ثبت شد.\nasena.company";
                    $phoneList = preg_split('/[,\s;]+/', $adminPhones);
                    foreach ($phoneList as $ap) {
                        $ap = SmsService::normalizePhone($ap);
                        if (!empty($ap)) $sms->sendDirectSms($ap, $textAdmin);
                    }
                }
            }
        }
    } else {
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 50 WHERE id = ?")
            ->execute([$user_id]);

        if ($is_subscription) {
            // Subscription Confirmation SMS to User
            if (!empty($userPhone)) {
                $sms->sendSubscriptionSent($userPhone, $pending['plan_name'] ?? 'ماهانه');
            }
        } else {
            // Regular Order Confirmation SMS to Buyer
            if (!empty($userPhone) && !empty($order_id)) {
                $sms->sendShippingUpdate($userPhone, $order_id);
            }

            // Send Admin New Order Notification
            $adminOrderSmsEnabled = get_setting($pdo, 'admin_sms_on_order', '1');
            if ($adminOrderSmsEnabled === '1' && !empty($order_id)) {
                $adminPhones = get_setting($pdo, 'admin_notification_phones', '09146676978');
                $orderTotalAmount = $total_amount ?? ($pending['final_amount'] ?? ($pending['amount'] ?? 0));
                $sms->sendAdminNewOrderAlert($adminPhones, $order_id, $orderTotalAmount);
            }
        }
    }

    $pdo->commit();

    // 4. Clean up session
    unset($_SESSION['cart'], $_SESSION['pending_order']);
    
    if ($is_subscription) {
        $_SESSION['profile_success'] =
            "پرداخت موفق! اشتراک «{$pending['plan_name']}» با موفقیت فعال شد. کد رهگیری: {$ref_id}";
    } else {
        $_SESSION['profile_success'] =
            "پرداخت موفق! سفارش #PC-{$order_id} ثبت شد. کد رهگیری: {$ref_id}";
    }
    header('Location: ../profile.php');
    exit;

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    unset($_SESSION['pending_order']);
    $_SESSION['profile_error'] = $e->getMessage();
    header('Location: ../cart.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Payment commit error [{$ref_id}]: " . $e->getMessage());
    $_SESSION['profile_error'] =
        'خطای سیستمی در ثبت سفارش. مبلغ کسر شده با کد رهگیری ' . $ref_id . ' قابل استرداد است.';
    header('Location: ../cart.php');
    exit;
}
