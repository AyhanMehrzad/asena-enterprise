<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gateway.php';

// ── Gate 1: Must arrive via GET callback from ZarinPal ───────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: ../cart.php');
    exit;
}

$authority = trim($_GET['Authority'] ?? $_GET['authority'] ?? '');
$status    = strtoupper(trim($_GET['Status'] ?? $_GET['status'] ?? ''));

// Resilient parsing if tx parameter was passed or merged in query string
if (empty($authority) && !empty($_GET['tx'])) {
    $rawTx = trim($_GET['tx']);
    if (str_contains($rawTx, '?')) {
        $parts = explode('?', $rawTx, 2);
        $authority = $parts[0];
        if (empty($status) && str_contains($parts[1], 'Status=')) {
            parse_str($parts[1], $extraParams);
            if (!empty($extraParams['Status'])) {
                $status = strtoupper(trim($extraParams['Status']));
            }
        }
    } else {
        $authority = $rawTx;
    }
}

// ── Gate 2: Gateway reported failure ──────────────────────────────────────────
if ($status !== 'OK' || empty($authority)) {
    unset($_SESSION['pending_order']);
    $_SESSION['profile_error'] = 'پرداخت لغو شد یا با خطا مواجه گردید.';
    header('Location: ../profile.php');
    exit;
}

// ── Gate 3: Authority must match session — prevents authority injection ────────
$pending = $_SESSION['pending_order'] ?? null;

$is_booking = (($pending['type'] ?? '') === 'booking');
$is_subscription = (($pending['type'] ?? '') === 'subscription');
$is_sms_package = (($pending['type'] ?? '') === 'sms_package');
$is_meal_plan = (($pending['type'] ?? '') === 'meal_plan');

if (!$pending
    || empty($pending['authority'])
    || !hash_equals((string)$pending['authority'], (string)$authority)
    || empty($pending['total_amount'])
    || (!$is_booking && !$is_subscription && !$is_sms_package && !$is_meal_plan && empty($pending['items']))
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

    $user_id = (int)($_SESSION['user_id'] ?? $pending['user_id'] ?? 0);
    if ($user_id > 0 && empty($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $user_id;
    }
    if ($user_id <= 0) {
        throw new RuntimeException('نشست کاربری شما معتبر نیست. لطفاً مجدداً وارد حساب کاربری شوید.');
    }
    $total_amount = (int)$pending['total_amount'];
    $items        = $pending['items'] ?? [];

    if ($is_sms_package) {
        $credits = (int)($pending['credits'] ?? 0);
        $pkgName = $pending['package_name'] ?? 'بسته پیامک';
        
        $pdo->prepare("INSERT INTO seller_wallets (seller_id, sms_credits) VALUES (?, ?) ON DUPLICATE KEY UPDATE sms_credits = sms_credits + ?")
            ->execute([$user_id, $credits, $credits]);
            
        $pdo->prepare("INSERT INTO sms_package_purchases (user_id, package_name, credits, price, payment_method, payment_ref, status) VALUES (?, ?, ?, ?, 'gateway', ?, 'completed')")
            ->execute([$user_id, $pkgName, $credits, $total_amount, $ref_id]);
            
        $order_id = 0;
    } elseif ($is_subscription) {
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
    } elseif ($is_booking) {
        $order_id = 0; // Clinical appointment booking handled specifically below
    } elseif ($is_meal_plan) {
        require_once __DIR__ . '/../includes/MealPlanGenerator.php';

        $mealData = (array)($pending['meal_plan_data'] ?? []);
        $petName = trim((string)($mealData['pet_name'] ?? 'حیوان خانگی من'));
        $species = in_array($mealData['species'] ?? '', ['dog', 'cat']) ? $mealData['species'] : 'dog';
        $race = trim((string)($mealData['race'] ?? 'مشخص نشده'));
        $weightKg = (float)($mealData['weight_kg'] ?? 0);
        $idealWeightKg = (float)($mealData['ideal_weight_kg'] ?? $weightKg);
        $bcsScore = (int)($mealData['bcs_score'] ?? 5);
        $dailyCalories = (int)($mealData['daily_calories'] ?? 0);
        $kibbleGrams = (int)($mealData['kibble_grams'] ?? 0);
        $waterMl = (int)($mealData['water_ml'] ?? 0);
        $activity = trim((string)($mealData['activity'] ?? 'neutered'));
        $stage = trim((string)($mealData['stage'] ?? 'adult'));
        $treatCalories = (int)($mealData['treat_calories'] ?? (int)round($dailyCalories * 0.10));
        $aiAnalysisObj = $mealData['ai_analysis_obj'] ?? null;
        if (empty($aiAnalysisObj) && !empty($mealData['ai_analysis'])) {
            $decodedAi = json_decode((string)$mealData['ai_analysis'], true);
            if (is_array($decodedAi)) $aiAnalysisObj = $decodedAi;
        }

        $reportSerial = 'ASENA-NUT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        // Generate Standalone Meal Plan HTML Document File
        $mealPlansDir = __DIR__ . '/../uploads/meal_plans';
        if (!is_dir($mealPlansDir)) {
            @mkdir($mealPlansDir, 0755, true);
        }

        $cleanSerialPart = strtolower(str_replace(['ASENA-', 'NUT-', 'DIET-'], '', $reportSerial));
        $fileName = 'meal_plan_' . $cleanSerialPart . '_' . time() . '.html';
        $relativeFilePath = 'uploads/meal_plans/' . $fileName;
        $fullFilePath = $mealPlansDir . '/' . $fileName;

        $htmlContent = MealPlanGenerator::generate([
            'serial' => $reportSerial,
            'pet_name' => $petName,
            'species' => $species,
            'race' => $race,
            'weight_kg' => $weightKg,
            'ideal_weight_kg' => $idealWeightKg,
            'bcs_score' => $bcsScore,
            'stage' => $stage,
            'activity' => $activity,
            'daily_calories' => $dailyCalories,
            'kibble_grams' => $kibbleGrams,
            'water_ml' => $waterMl,
            'treat_calories' => $treatCalories,
            'ai_analysis' => $aiAnalysisObj,
            'created_at' => date('Y/m/d - H:i')
        ]);

        @file_put_contents($fullFilePath, $htmlContent);

        // Find or create pet
        $petId = 0;
        $chkStmt = $pdo->prepare("SELECT id FROM user_pets WHERE user_id = ? AND (name = ? OR name LIKE ?) LIMIT 1");
        $chkStmt->execute([$user_id, $petName, "%$petName%"]);
        $petId = (int)$chkStmt->fetchColumn();

        if ($petId <= 0) {
            try {
                $insPet = $pdo->prepare("INSERT INTO user_pets (user_id, name, type, race, weight_kg) VALUES (?, ?, ?, ?, ?)");
                $insPet->execute([$user_id, $petName, $species, $race, $weightKg]);
            } catch (Exception $ePet) {
                $insPet = $pdo->prepare("INSERT INTO user_pets (user_id, name, type, weight_kg) VALUES (?, ?, ?, ?)");
                $insPet->execute([$user_id, $petName, $species, $weightKg]);
            }
            $petId = (int)$pdo->lastInsertId();
        }

        // Insert document into pet_documents
        // Save clinical meal plan into pet_documents
        $docTitle = 'جدول و برنامه غذایی بالینی (' . $reportSerial . ')';
        $reportSummary = "کارنامه و رژیم غذایی بالینی پت ({$reportSerial}) با پرداخت آنلاین معتبر به شماره ارجاع {$ref_id}";
        try {
            $insDoc = $pdo->prepare("
                INSERT INTO pet_documents (pet_id, user_id, title, file_name, file_path)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insDoc->execute([$petId, $user_id, $docTitle, $fileName, $relativeFilePath]);
        } catch (Exception $eDoc) {
            try {
                $insDoc = $pdo->prepare("
                    INSERT INTO pet_documents (user_id, pet_id, title, document_type, notes, file_path, uploaded_at)
                    VALUES (?, ?, ?, 'nutrition_assessment', ?, ?, NOW())
                ");
                $insDoc->execute([$user_id, $petId, $docTitle, $reportSummary, $relativeFilePath]);
            } catch (Exception $eDoc2) {
                try {
                    $insDoc = $pdo->prepare("
                        INSERT INTO pet_documents (pet_id, user_id, title, file_path)
                        VALUES (?, ?, ?, ?)
                    ");
                    $insDoc->execute([$petId, $user_id, $docTitle, $relativeFilePath]);
                } catch (Exception $eDoc3) {}
            }
        }

        // Update payment transaction status
        try {
            $pdo->prepare("UPDATE payment_transactions SET status = 'paid', tracking_code = ? WHERE authority_or_ref = ?")
                ->execute([$ref_id, $authority]);
        } catch (Exception $eTx) {}

        $order_id = 0;
        $mealPlanRedirectUrl = '../view_meal_plan.php?file=' . urlencode($fileName) . '&paid=1';
    } else {
        // Fetch snapshot of buyer address
        $userAddrStmt = $pdo->prepare("SELECT city, address, postal_code FROM users WHERE id = ?");
        $userAddrStmt->execute([$user_id]);
        $uAddr = $userAddrStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $fullShippingAddress = trim(($uAddr['city'] ?? '') . '، ' . ($uAddr['address'] ?? ''));
        if (!empty($uAddr['postal_code'])) {
            $fullShippingAddress .= ' (کد پستی: ' . $uAddr['postal_code'] . ')';
        }

        // 1. Create order with dynamic schema introspection to guarantee 100% fail-safe insertion
        $discountAmount = (int)($pending['discount_amount'] ?? 0);
        $taxAmount      = (int)($pending['tax_amount'] ?? 0);
        $shippingCost   = (int)($pending['shipping_cost'] ?? 0);
        $carrierName    = !empty($pending['carrier_name']) ? $pending['carrier_name'] : 'شرکت ملی پست (پیشتاز)';
        $promoCode      = !empty($pending['promo_code']) ? $pending['promo_code'] : null;
        $promoId        = (int)($pending['promo_id'] ?? 0);

        $fullShippingAddressWithSla = $fullShippingAddress . " [حامل: {$carrierName} | مهلت ارسال: ۲۴h کاری]";

        $orderCols = [];
        try {
            $colQuery = $pdo->query("SHOW COLUMNS FROM `orders`");
            if ($colQuery) {
                $orderCols = $colQuery->fetchAll(PDO::FETCH_COLUMN);
            }
        } catch (Throwable $t) {}

        $orderData = [
            'user_id'          => $user_id,
            'total_amount'     => $total_amount,
            'discount_amount'  => $discountAmount,
            'tax_amount'       => $taxAmount,
            'shipping_cost'    => $shippingCost,
            'carrier_name'     => $carrierName,
            'promo_code'       => $promoCode,
            'status'           => 'processing',
            'gateway_ref_id'   => $ref_id,
            'shipping_address' => $fullShippingAddressWithSla,
            'tracking_code'    => $ref_id
        ];

        if (!empty($orderCols)) {
            $insertFields = [];
            $insertPlaceholders = [];
            $insertValues = [];
            foreach ($orderData as $col => $val) {
                if (in_array($col, $orderCols)) {
                    $insertFields[] = "`{$col}`";
                    $insertPlaceholders[] = "?";
                    $insertValues[] = $val;
                }
            }
            $orderStmt = $pdo->prepare("INSERT INTO `orders` (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")");
            $orderStmt->execute($insertValues);
        } else {
            $orderStmt = $pdo->prepare("INSERT INTO `orders` (user_id, total_amount, status) VALUES (?, ?, 'processing')");
            $orderStmt->execute([$user_id, $total_amount]);
        }
        $order_id = $pdo->lastInsertId();

        // 1.5 Record promo code usage ledger entry
        if ($promoId > 0 && $discountAmount > 0) {
            try {
                require_once __DIR__ . '/../includes/App.php';
                App::promo()->recordUsage($promoId, $user_id, (int)$order_id, $discountAmount);
            } catch (Throwable $pe) {
                error_log("Promo redemption ledger warning: " . $pe->getMessage());
            }
        }

    // 2. Insert order_items and decrement stock
    if (!$is_booking && !$is_subscription) {
        $itemStmt = $pdo->prepare(
            "INSERT INTO order_items (order_id, product_id, item_source, quantity, price_at_purchase, product_name_snapshot)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $pid = (int)$item['product_id'];
            $qty = (int)$item['qty'];
            $itemSource = ($item['item_source'] ?? '') === 'pharmacy' ? 'pharmacy' : 'product';

            // Dynamically locate item in designated table
            $invTable = ($itemSource === 'pharmacy') ? 'pharmacy_medicines' : 'products';
            $checkStmt = $pdo->prepare("SELECT stock FROM {$invTable} WHERE id = ? FOR UPDATE");
            $checkStmt->execute([$pid]);
            $current_stock = $checkStmt->fetchColumn();

            if ($current_stock === false && $itemSource === 'product') {
                // Resilient fallback check across pharmacy
                $invTable = 'pharmacy_medicines';
                $itemSource = 'pharmacy';
                $checkStmt = $pdo->prepare("SELECT stock FROM pharmacy_medicines WHERE id = ? FOR UPDATE");
                $checkStmt->execute([$pid]);
                $current_stock = $checkStmt->fetchColumn();
            }

            // Segregated Autoship Reservation Pool Check
            $reserved_pool = 0;
            try {
                $resStmt = $pdo->prepare("SELECT COALESCE(reserved_stock, 0) FROM {$invTable} WHERE id = ?");
                $resStmt->execute([$pid]);
                $reserved_pool = (int)$resStmt->fetchColumn();
            } catch (Throwable $eRes) {}

            $available_for_retail = max(0, (int)$current_stock - $reserved_pool);
            $is_autoship_item = !empty($item['is_autoship']);
            $effective_available = $is_autoship_item ? (int)$current_stock : $available_for_retail;

            if ($current_stock === false || $effective_available < $qty) {
                throw new RuntimeException(
                    "محصول «{$item['product_name_snapshot']}» موجودی آزاد کافی ندارد (موجودی آزاد: " . $effective_available . ")."
                );
            }

            try {
                $itemStmt->execute([
                    $order_id, $pid, $itemSource, $qty,
                    (int)$item['unit_price'],
                    $item['product_name_snapshot'],
                ]);
            } catch (PDOException $eCol) {
                // Fallback for schema transition
                $fallbackItemStmt = $pdo->prepare(
                    "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, product_name_snapshot)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $fallbackItemStmt->execute([
                    $order_id, $pid, $qty,
                    (int)$item['unit_price'],
                    $item['product_name_snapshot'],
                ]);
            }
            
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

                    // Allocate remaining recurring deliveries to reserved_stock pool
                    if ($dur_months > 1) {
                        $remaining_committed_qty = ($dur_months - 1) * $qty;
                        try {
                            $pdo->prepare("UPDATE {$invTable} SET reserved_stock = reserved_stock + ? WHERE id = ?")
                                ->execute([$remaining_committed_qty, $pid]);
                        } catch (Throwable $ePool) {}
                    }
                } catch (Exception $subEx) {
                    error_log("Autoship scheduling error: " . $subEx->getMessage());
                }
            }
        }
    }
} // End of else block for regular orders

    // 2.5 Escrow Deposit: Hold seller net revenue in corporate escrow pending 7-day post delivery
    if (!$is_booking && !$is_subscription && !empty($order_id)) {
        try {
            require_once __DIR__ . '/../includes/App.php';
            App::escrow()->depositOrderToEscrow((int)$order_id);
        } catch (Throwable $escrowEx) {
            error_log("Escrow Deposit Warning: " . $escrowEx->getMessage());
        }
    }

    // 2.6 Update payment transaction record to paid
    try {
        $pdo->prepare("UPDATE payment_transactions SET status = 'paid', tracking_code = ?, order_id = ? WHERE authority_or_ref = ?")
            ->execute([$ref_id, (int)$order_id, $authority]);
    } catch (Throwable $eTx) {}

    // 3. Loyalty points, booking approval & SMS Notifications
    require_once __DIR__ . '/../includes/SmsService.php';
    $sms = new SmsService();

    // Fetch user phone
    $u_stmt = $pdo->prepare("SELECT phone, name FROM users WHERE id = ?");
    $u_stmt->execute([$user_id]);
    $u = $u_stmt->fetch(PDO::FETCH_ASSOC);
    $userPhone = $u['phone'] ?? '';

    if ($is_booking && !empty($pending['booking_id'])) {
        $bookingId = (int)$pending['booking_id'];
        $pdo->prepare("UPDATE appointments SET status = 'approved', settlement_status = 'held_in_escrow', tracking_code = ? WHERE id = ?")
            ->execute([$ref_id, $bookingId]);
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 20 WHERE id = ?")
            ->execute([$user_id]);
            
        // 1. Fetch appointment & doctor details
        $doc_stmt = $pdo->prepare("
            SELECT a.net_amount, a.organization_id, d.user_id as doctor_user_id, d.name as doctor_name, d.phone as doctor_phone, a.pet_name, a.pet_type, a.appointment_date, a.appointment_time, u_doc.phone as doc_user_phone
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            LEFT JOIN users u_doc ON d.user_id = u_doc.id
            WHERE a.id = ?
        ");
        $doc_stmt->execute([$bookingId]);
        $apptDoc = $doc_stmt->fetch(PDO::FETCH_ASSOC);

        if ($apptDoc) {
            $beneficiaryOrgOrDoc = !empty($apptDoc['organization_id']) ? (int)$apptDoc['organization_id'] : (int)($apptDoc['doctor_user_id'] ?: 1);
            $netShare = (int)($apptDoc['net_amount'] ?: ($pending['net_amount'] ?? 0));
            if ($netShare > 0) {
                // Credit pending escrow to wallet
                $pdo->prepare("INSERT INTO seller_wallets (seller_id, balance_pending_escrow) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance_pending_escrow = balance_pending_escrow + ?")
                    ->execute([$beneficiaryOrgOrDoc, $netShare, $netShare]);
            }
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

            // 4. Send SMS to Organization / Clinic Manager if linked to a clinic
            if (!empty($apptDoc['organization_id'])) {
                try {
                    $orgStmt = $pdo->prepare("SELECT name, phone, emergency_phone FROM organizations WHERE id = ?");
                    $orgStmt->execute([(int)$apptDoc['organization_id']]);
                    $orgRow = $orgStmt->fetch(PDO::FETCH_ASSOC);
                    $clinicPhone = !empty($orgRow['phone']) ? $orgRow['phone'] : ($orgRow['emergency_phone'] ?? '');
                    if (!empty($clinicPhone)) {
                        $sms->sendClinicNewAppointmentAlert($clinicPhone, $orgRow['name'] ?? 'کلینیک', $apptDoc['doctor_name'], $apptDoc['appointment_date'], $apptDoc['appointment_time']);
                    }
                } catch (Throwable $orgEx) {
                    error_log("Clinic new booking SMS alert error: " . $orgEx->getMessage());
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

            // Notify Marketplace Seller(s) with items in this order (Pattern 535286 / Direct SMS)
            if (!empty($order_id)) {
                try {
                    $sellerStmt = $pdo->prepare("
                        SELECT DISTINCT u.phone, u.name 
                        FROM order_items oi
                        JOIN users u ON oi.seller_id = u.id
                        WHERE oi.order_id = ? AND u.phone IS NOT NULL AND u.phone != ''
                    ");
                    $sellerStmt->execute([$order_id]);
                    $sellers = $sellerStmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($sellers as $sRow) {
                        $sms->sendSellerNewOrderAlert($sRow['phone'], $order_id);
                    }
                } catch (Throwable $sEx) {
                    error_log("Seller new order SMS alert error: " . $sEx->getMessage());
                }
            }

            // In-app Notification for User
            try {
                require_once __DIR__ . '/../includes/App.php';
                if (!empty($order_id)) {
                    App::notifications()->notifyOrderPlaced((int)$user_id, (int)$order_id, (int)$total_amount);
                } elseif ($is_booking && !empty($bookingId)) {
                    $docName = !empty($apptDoc['doctor_name']) ? $apptDoc['doctor_name'] : 'دامپزشک معالج';
                    $apptDate = !empty($apptDoc['appointment_date']) ? $apptDoc['appointment_date'] : '';
                    $apptTime = !empty($apptDoc['appointment_time']) ? $apptDoc['appointment_time'] : '';
                    App::notifications()->notifyAppointmentBooked((int)$user_id, (int)$bookingId, $docName, $apptDate, $apptTime);
                }
            } catch (Throwable $notifEx) {
                error_log("In-app notification creation error: " . $notifEx->getMessage());
            }
        }
    }

    $pdo->commit();

    // 4. Clean up session
    unset($_SESSION['cart'], $_SESSION['cart_types'], $_SESSION['cart_frequency'], $_SESSION['active_cart_tab'], $_SESSION['pending_order'], $_SESSION['applied_promo']);
    
    if ($is_meal_plan) {
        $_SESSION['profile_success'] = "پرداخت موفق! جدول برنامه غذایی بالینی با موفقیت صادر و در پرونده سلامت ذخیره گردید. کد رهگیری: {$ref_id}";
        header('Location: ' . ($mealPlanRedirectUrl ?? '../view_meal_plan.php?paid=1'));
        exit;
    } elseif ($is_sms_package) {
        $_SESSION['profile_success'] = "پرداخت موفق! {$pending['package_name']} با موفقیت به حساب شما افزوده شد. کد رهگیری: {$ref_id}";
        header('Location: ../partner_interactions.php');
        exit;
    } elseif ($is_subscription) {
        $_SESSION['profile_success'] =
            "پرداخت موفق! اشتراک «{$pending['plan_name']}» با موفقیت فعال شد. کد رهگیری: {$ref_id}";
        header('Location: ../profile.php');
        exit;
    } elseif ($is_booking) {
        $_SESSION['profile_success'] =
            "پرداخت موفق! نوبت ویزیت تخصصی شما در سامانه آسنا با موفقیت تایید شد. کد رهگیری: {$ref_id}";
        header('Location: ../profile.php');
        exit;
    } else {
        $_SESSION['profile_success'] =
            "پرداخت موفق! سفارش #PC-{$order_id} ثبت شد. کد رهگیری زرین‌پال: {$ref_id}";
        header("Location: ../order_receipt.php?order_id={$order_id}");
        exit;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Payment PDOException commit error [{$ref_id}]: " . $e->getMessage());

    if (!empty($ref_id)) {
        try {
            $logStmt = $pdo->prepare("INSERT INTO payment_discrepancy_logs 
                (user_id, gateway_ref_id, authority, amount, pending_order_json, error_message, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending_investigation', NOW())");
            $logStmt->execute([
                $user_id ?? 0,
                (string)$ref_id,
                (string)($authority ?? ''),
                (int)($final_amount ?? 0),
                json_encode($pending ?? [], JSON_UNESCAPED_UNICODE),
                $e->getMessage()
            ]);

            $ticketStmt = $pdo->prepare("INSERT INTO tickets (user_id, mode, status, created_at, updated_at) VALUES (?, 'admin', 'open', NOW(), NOW())");
            $ticketStmt->execute([$user_id ?? 0]);
            $ticketId = (int)$pdo->lastInsertId();
            if ($ticketId > 0) {
                $ticketMsg = "🚨 خطای پایگاه داده پس از کسر وجه بانکی:\n"
                    . "کد رهگیری زرین‌پال: {$ref_id}\n"
                    . "مبلغ: " . number_format($final_amount ?? 0) . " تومان\n"
                    . "خطا: " . $e->getMessage();
                $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'admin', ?, NOW())")
                    ->execute([$ticketId, $ticketMsg]);
            }
        } catch (Throwable $logEx) {
            error_log("Failed to log payment discrepancy: " . $logEx->getMessage());
        }
    }

    $_SESSION['profile_error'] =
        'خطای سیستمی پایگاه داده در ثبت سفارش (' . htmlspecialchars($e->getMessage()) . '). مبلغ کسر شده با کد رهگیری ' . ($ref_id ?: 'درگاه') . ' در سامانه مالی جهت پیگیری ثبت شد.';
    header('Location: ../cart.php');
    exit;

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    unset($_SESSION['pending_order']);
    error_log("Payment RuntimeException [ref: {$ref_id}]: " . $e->getMessage());
    
    // If bank debited customer ($ref_id exists), log discrepancy and create priority support ticket
    if (!empty($ref_id)) {
        try {
            $logStmt = $pdo->prepare("INSERT INTO payment_discrepancy_logs 
                (user_id, gateway_ref_id, authority, amount, pending_order_json, error_message, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending_investigation', NOW())");
            $logStmt->execute([
                $user_id ?? 0,
                (string)$ref_id,
                (string)($authority ?? ''),
                (int)($final_amount ?? 0),
                json_encode($pending ?? [], JSON_UNESCAPED_UNICODE),
                $e->getMessage()
            ]);

            $ticketStmt = $pdo->prepare("INSERT INTO tickets (user_id, mode, status, created_at, updated_at) VALUES (?, 'admin', 'open', NOW(), NOW())");
            $ticketStmt->execute([$user_id ?? 0]);
            $ticketId = (int)$pdo->lastInsertId();
            if ($ticketId > 0) {
                $ticketMsg = "🚨 هشدار مغایرت مالی (کسر از حساب بدون ثبت نهایی سفارش):\n"
                    . "کد رهگیری زرین‌پال: {$ref_id}\n"
                    . "مبلغ: " . number_format($final_amount ?? 0) . " تومان\n"
                    . "شناسه یکتا Authority: " . ($authority ?? '-') . "\n"
                    . "علت عدم ثبت سفارش: " . $e->getMessage() . "\n"
                    . "این تیکت به صورت خودکار توسط سامانه ایجاد شده و در صف اولویت پیگیری مالی و استرداد وجه قرار دارد.";
                $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message, created_at) VALUES (?, 'admin', ?, NOW())")
                    ->execute([$ticketId, $ticketMsg]);
            }
        } catch (Throwable $logEx) {
            error_log("Failed to log payment discrepancy: " . $logEx->getMessage());
        }

        $_SESSION['profile_error'] = "تراکنش بانکی شما با کد پیگیری {$ref_id} با موفقیت تایید شده بود اما به علت خطای سیستمی (" . htmlspecialchars($e->getMessage()) . ") سفارش ثبت نشد. یک تیکت پیگیری برای پشتیبانی مالی ثبت گردید و موضوع در اسرع وقت بررسی خواهد شد.";
    } else {
        $_SESSION['profile_error'] = $e->getMessage();
    }
    header('Location: ../cart.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Payment unexpected error [{$ref_id}]: " . $e->getMessage());

    if (!empty($ref_id)) {
        try {
            $logStmt = $pdo->prepare("INSERT INTO payment_discrepancy_logs 
                (user_id, gateway_ref_id, authority, amount, pending_order_json, error_message, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending_investigation', NOW())");
            $logStmt->execute([
                $user_id ?? 0,
                (string)$ref_id,
                (string)($authority ?? ''),
                (int)($final_amount ?? 0),
                json_encode($pending ?? [], JSON_UNESCAPED_UNICODE),
                $e->getMessage()
            ]);
        } catch (Throwable $logEx) {}
    }

    $_SESSION['profile_error'] =
        'خطای غیرمنتظره در ثبت نهایی سفارش: ' . htmlspecialchars($e->getMessage());
    header('Location: ../cart.php');
    exit;
}
