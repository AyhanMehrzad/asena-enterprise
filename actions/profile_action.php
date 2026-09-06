<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if ($action === 'add_pet') {
        $name = trim($_POST['pet_name'] ?? '');
        $type = trim($_POST['pet_type'] ?? '');
        $race = trim($_POST['pet_race'] ?? '');
        
        if (!empty($name) && !empty($type)) {
            $stmt = $pdo->prepare("INSERT INTO user_pets (user_id, name, type, race) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $name, $type, $race])) {
                $_SESSION['profile_success'] = "حیوان خانگی جدید با موفقیت اضافه شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در ثبت حیوان خانگی.";
            }
        } else {
            $_SESSION['profile_error'] = "نام و نوع حیوان الزامی است.";
        }
    } elseif ($action === 'upload_document') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        $title  = trim($_POST['doc_title'] ?? '');

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        if ($pet_id > 0 && !empty($title) && isset($_FILES['document'])) {
            $validation = validate_upload($_FILES['document'], $allowed_mimes, 5 * 1024 * 1024);

            if (!$validation['ok']) {
                $_SESSION['profile_error'] = $validation['error'];
            } else {
                $ext          = match($validation['mime']) {
                    'image/jpeg'       => 'jpg',
                    'image/png'        => 'png',
                    'image/webp'       => 'webp',
                    'application/pdf'  => 'pdf',
                    default            => 'bin',
                };
                $uploadDir  = '../uploads/documents/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;
                $target_file = $uploadDir . $newFileName;
                $db_path     = 'uploads/documents/' . $newFileName;

                if (move_uploaded_file($_FILES['document']['tmp_name'], $target_file)) {
                    $stmt = $pdo->prepare("INSERT INTO pet_documents (pet_id, user_id, title, file_name, file_path) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$pet_id, $user_id, $title, $newFileName, $db_path]);
                    $_SESSION['profile_success'] = 'سند با موفقیت آپلود شد.';
                } else {
                    $_SESSION['profile_error'] = 'خطا در انتقال فایل.';
                }
            }
        } else {
            $_SESSION['profile_error'] = 'لطفاً تمام فیلدها را پر کنید و فایلی انتخاب نمایید.';
        }
    } elseif ($action === 'edit_pet') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        $name = trim($_POST['pet_name'] ?? '');
        $type = trim($_POST['pet_type'] ?? '');
        $race = trim($_POST['pet_race'] ?? '');
        
        if ($pet_id > 0 && !empty($name) && !empty($type)) {
            // Ensure the pet belongs to the user
            $stmt = $pdo->prepare("UPDATE user_pets SET name = ?, type = ?, race = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$name, $type, $race, $pet_id, $user_id])) {
                $_SESSION['profile_success'] = "مشخصات حیوان خانگی با موفقیت بروزرسانی شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در بروزرسانی حیوان خانگی.";
            }
        } else {
            $_SESSION['profile_error'] = "نام و نوع حیوان الزامی است.";
        }
    } elseif ($action === 'delete_pet') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        if ($pet_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM user_pets WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$pet_id, $user_id])) {
                $_SESSION['profile_success'] = "حیوان خانگی با موفقیت حذف شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در حذف حیوان خانگی.";
            }
        }
    } elseif ($action === 'cancel_subscription') {
        $sub_id = (int)($_POST['subscription_id'] ?? 0);
        if ($sub_id > 0) {
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$sub_id, $user_id])) {
                $_SESSION['profile_success'] = "اشتراک با موفقیت لغو شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در لغو اشتراک.";
            }
        }
    } elseif ($action === 'cancel_order') {
        $order_id = (int)($_POST['order_id'] ?? 0);
        if ($order_id > 0) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending_payment', 'processing')");
                $stmt->execute([$order_id, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    // Restore stock
                    $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $items->execute([$order_id]);
                    $restoreStmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                    foreach ($items->fetchAll() as $item) {
                        $restoreStmt->execute([$item['quantity'], $item['product_id']]);
                    }
                    
                    // Deduct loyalty points
                    $pdo->prepare("UPDATE users SET loyalty_points = GREATEST(0, loyalty_points - 50) WHERE id = ?")->execute([$user_id]);
                    
                    $pdo->commit();
                    $_SESSION['profile_success'] = "سفارش شما با موفقیت لغو شد.";
                } else {
                    $pdo->rollBack();
                    $_SESSION['profile_error'] = "امکان لغو این سفارش وجود ندارد (ممکن است ارسال شده باشد).";
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['profile_error'] = "خطای سیستمی در لغو سفارش.";
            }
        }
    } elseif ($action === 'cancel_appointment') {
        $apt_id = (int)($_POST['appointment_id'] ?? 0);
        if ($apt_id > 0) {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'approved', 'در انتظار')");
            $stmt->execute([$apt_id, $user_id]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['profile_success'] = "نوبت با موفقیت لغو شد.";
            } else {
                $_SESSION['profile_error'] = "امکان لغو این نوبت وجود ندارد.";
            }
        }
    } elseif ($action === 'update_bank_details') {
        require_once __DIR__ . '/../includes/App.php';
        $escrow = App::escrow();
        $res = $escrow->updateBankDetails($user_id, [
            'bank_name' => $_POST['bank_name'] ?? '',
            'bank_account_holder' => $_POST['bank_account_holder'] ?? '',
            'bank_sheba' => $_POST['bank_sheba'] ?? '',
            'bank_card_number' => $_POST['bank_card_number'] ?? ''
        ]);
        if (!empty($res['success'])) {
            $_SESSION['profile_success'] = $res['message'];
        } else {
            $_SESSION['profile_error'] = $res['message'];
        }
    } elseif ($action === 'seller_dispatch_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $trackingCode = trim($_POST['post_tracking_code'] ?? '');
        $carrier = trim($_POST['carrier_name'] ?? 'شرکت ملی پست / سامانه پستکس');

        if ($orderId > 0 && !empty($trackingCode)) {
            $up = $pdo->prepare("
                UPDATE orders 
                SET status = 'shipped', post_tracking_code = ?, carrier_name = ?
                WHERE id = ?
            ");
            if ($up->execute([$trackingCode, $carrier, $orderId])) {
                $pdo->prepare("UPDATE seller_escrow_ledger SET status = 'in_inspection' WHERE order_id = ? AND status = 'pending_delivery'")->execute([$orderId]);
                $_SESSION['profile_success'] = "سفارش #PC-{$orderId} با کد رهگیری {$trackingCode} به عنوان ارسال شده ثبت گردید.";
            } else {
                $_SESSION['profile_error'] = "خطا در ثبت ارسال سفارش.";
            }
        } else {
            $_SESSION['profile_error'] = "لطفاً کد رهگیری پستی را وارد نمایید.";
        }
    } elseif ($action === 'seller_add_product') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'سایر ملزومات');
        $price = (int)($_POST['price'] ?? 0);
        $discountPrice = (int)($_POST['discount_price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 10);
        $description = trim($_POST['description'] ?? '');
        $brand = trim($_POST['brand'] ?? 'تأمین‌کننده رسمی');
        $imageUrl = trim($_POST['image_url'] ?? 'assets/images/toy-mouse.jpg');

        if (!empty($name) && $price > 0) {
            $ins = $pdo->prepare("
                INSERT INTO products (seller_id, name, category, price, discount_price, stock, description, brand, image_url, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            if ($ins->execute([$user_id, $name, $category, $price, $discountPrice, $stock, $description, $brand, $imageUrl])) {
                $_SESSION['profile_success'] = "کالای «{$name}» با موفقیت در کاتالوگ فروشگاه شما ثبت شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در افزودن کالا به کاتالوگ.";
            }
        } else {
            $_SESSION['profile_error'] = "عنوان کالا و قیمت فروش الزامی است.";
        }
    } elseif ($action === 'seller_update_product') {
        $prodId = (int)($_POST['product_id'] ?? 0);
        $price = (int)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);

        if ($prodId > 0 && $price > 0) {
            $up = $pdo->prepare("UPDATE products SET price = ?, stock = ? WHERE id = ?");
            if ($up->execute([$price, $stock, $prodId])) {
                $_SESSION['profile_success'] = "قیمت و موجودی کالا به‌روزرسانی شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در بروزرسانی محصول.";
            }
        }
    } elseif ($action === 'seller_delete_product') {
        $prodId = (int)($_POST['product_id'] ?? 0);
        if ($prodId > 0) {
            $del = $pdo->prepare("DELETE FROM products WHERE id = ? AND (seller_id = ? OR ? = 'admin')");
            if ($del->execute([$prodId, $user_id, $user['role'] ?? ''])) {
                $_SESSION['profile_success'] = "کالا با موفقیت از کاتالوگ شما حذف شد.";
            } else {
                $_SESSION['profile_error'] = "خطا در حذف کالا.";
            }
        }
    } elseif ($action === 'seller_update_identity') {
        $storeName = trim($_POST['store_name'] ?? '');
        $nationalId = trim($_POST['national_id'] ?? '');
        $city = trim($_POST['city'] ?? '');
        if (!empty($storeName)) {
            $upUser = $pdo->prepare("UPDATE users SET name = ?, national_id = ?, city = ? WHERE id = ?");
            $upUser->execute([$storeName, $nationalId, $city, $user_id]);
            $_SESSION['profile_success'] = "اطلاعات هویتی و نام فروشگاه شما با موفقیت ذخیره گردید.";
        }
    }
}

// Redirect back with view=seller if user is seller
if (isset($_POST['is_seller_action']) || (isset($user['role']) && $user['role'] === 'seller')) {
    header("Location: ../profile.php?view=seller");
    exit;
}

header("Location: ../profile.php");
exit;
?>
