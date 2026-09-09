<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$pet_id = (int)($_POST['pet_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if ($pet_id <= 0) {
        $_SESSION['profile_error'] = 'شناسه حیوان خانگی نامعتبر است.';
        header("Location: ../profile.php#pets-section");
        exit;
    }

    // Verify ownership
    $chkStmt = $pdo->prepare("SELECT id, pending_doctor_proposal, medical_history FROM user_pets WHERE id = ? AND user_id = ?");
    $chkStmt->execute([$pet_id, $user_id]);
    $pet = $chkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        $_SESSION['profile_error'] = 'حیوان خانگی یافت نشد یا دسترسی مجاز نیست.';
        header("Location: ../profile.php#pets-section");
        exit;
    }

    if ($action === 'accept_proposal') {
        if (empty($pet['pending_doctor_proposal'])) {
            $_SESSION['profile_error'] = 'پیشنهاد تغییراتی برای این حیوان ثبت نشده است.';
            header("Location: ../profile.php#pets-section");
            exit;
        }

        $proposal = json_decode($pet['pending_doctor_proposal'], true);
        if (!$proposal) {
            $_SESSION['profile_error'] = 'ساختار اطلاعات بالینی نامعتبر است.';
            header("Location: ../profile.php#pets-section");
            exit;
        }

        $newWeight = isset($proposal['new_weight']) && $proposal['new_weight'] !== '' ? (float)$proposal['new_weight'] : null;
        $newAllergies = trim($proposal['new_allergies'] ?? '');
        $doctorId = !empty($proposal['doctor_id']) ? (int)$proposal['doctor_id'] : null;
        $docName = $proposal['doctor_name'] ?? 'دامپزشک معالج';
        $proposedAt = $proposal['proposed_at'] ?? date('Y-m-d H:i');
        $diag = trim($proposal['diagnosis'] ?? '');
        $rx = trim($proposal['prescription'] ?? '');

        // Append to medical history journal
        $newHistoryEntry = "• [معاینه مورخ {$proposedAt} توسط {$docName}]: تشخیص: {$diag} | دستور دارویی: {$rx}";
        $existingHistory = trim($pet['medical_history'] ?? '');
        $updatedHistory = !empty($existingHistory) ? ($existingHistory . "\n" . $newHistoryEntry) : $newHistoryEntry;

        $upStmt = $pdo->prepare("
            UPDATE user_pets SET
                weight_kg = COALESCE(?, weight_kg),
                allergies = CASE WHEN ? != '' THEN ? ELSE allergies END,
                medical_history = ?,
                last_doctor_id = COALESCE(?, last_doctor_id),
                clinical_verified_at = NOW(),
                pending_doctor_proposal = NULL
            WHERE id = ? AND user_id = ?
        ");

        if ($upStmt->execute([$newWeight, $newAllergies, $newAllergies, $updatedHistory, $doctorId, $pet_id, $user_id])) {
            $_SESSION['profile_success'] = "تشخیص و تغییرات بالینی ثبت‌شده توسط {$docName} با موفقیت در پرونده سلامت پت تایید و اعمال گردید.";
        } else {
            $_SESSION['profile_error'] = 'خطا در ثبت تغییرات پرونده.';
        }
    } elseif ($action === 'reject_proposal') {
        $clearStmt = $pdo->prepare("UPDATE user_pets SET pending_doctor_proposal = NULL WHERE id = ? AND user_id = ?");
        if ($clearStmt->execute([$pet_id, $user_id])) {
            $_SESSION['profile_success'] = 'پیشنهاد تغییرات بالینی پزشک رد شد و پرونده بدون تغییر باقی ماند.';
        } else {
            $_SESSION['profile_error'] = 'خطا در رد پیشنهاد.';
        }
    }
}

header("Location: ../profile.php#pets-section");
exit;
