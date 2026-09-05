<?php
/**
 * ASENA Enterprise - Role Verification Service
 * Handles professional credential uploads, verification state machine, and SMS alerts.
 * Version: 1.0.0
 */

require_once __DIR__ . '/SecurityMiddleware.php';
require_once __DIR__ . '/SmsService.php';

class RoleVerificationService {
    private PDO $pdo;
    private SmsService $sms;
    private string $uploadDir;

    public function __construct(?PDO $pdo = null, ?SmsService $sms = null) {
        $this->pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
        $this->sms = $sms ?? new SmsService();
        $this->uploadDir = dirname(__DIR__) . '/uploads/credentials';
        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Submit a professional credential application
     */
    public function submitApplication(int $userId, array $data, array $files): array {
        $appliedRole = $data['applied_role'] ?? '';
        $fullName    = trim($data['full_name'] ?? '');
        $phone       = trim($data['phone'] ?? '');
        $licenseNum  = trim($data['license_number'] ?? '');
        $specialty   = trim($data['specialty'] ?? '');
        $orgName     = trim($data['organization_name'] ?? '');
        $orgType     = trim($data['organization_type'] ?? '');
        $website     = trim($data['website'] ?? '');
        $instagram   = trim($data['instagram'] ?? '');
        $city        = trim($data['city'] ?? 'تهران');
        $address     = trim($data['address'] ?? '');

        if (!in_array($appliedRole, ['doctor', 'pharmacist', 'organization', 'supplier'])) {
            return ['success' => false, 'error' => 'نقش انتخاب شده نامعتبر است.'];
        }

        if (empty($fullName) || empty($phone)) {
            return ['success' => false, 'error' => 'نام کامل و شماره تلفن الزامی است.'];
        }

        // Handle Degree Document Upload
        $degreePath = null;
        if (isset($files['degree_document']) && !empty($files['degree_document']['tmp_name'])) {
            $val = SecurityMiddleware::validateUploadedFile($files['degree_document']);
            if (!$val['valid']) {
                return ['success' => false, 'error' => 'فایل مدرک تحصیلی: ' . $val['error']];
            }
            $target = $this->uploadDir . '/' . $val['safe_filename'];
            if (move_uploaded_file($files['degree_document']['tmp_name'], $target)) {
                $degreePath = 'uploads/credentials/' . $val['safe_filename'];
            }
        }

        // Handle License Document Upload
        $licensePath = null;
        if (isset($files['license_document']) && !empty($files['license_document']['tmp_name'])) {
            $val = SecurityMiddleware::validateUploadedFile($files['license_document']);
            if (!$val['valid']) {
                return ['success' => false, 'error' => 'فایل پروانه فعالیت: ' . $val['error']];
            }
            $target = $this->uploadDir . '/' . $val['safe_filename'];
            if (move_uploaded_file($files['license_document']['tmp_name'], $target)) {
                $licensePath = 'uploads/credentials/' . $val['safe_filename'];
            }
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO role_applications (
                    user_id, applied_role, full_name, phone, license_number, specialty,
                    degree_document_url, license_document_url, organization_name, organization_type,
                    website, instagram, city, address, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $userId, $appliedRole, $fullName, $phone, $licenseNum, $specialty,
                $degreePath, $licensePath, $orgName, $orgType,
                $website, $instagram, $city, $address
            ]);
            $appId = (int)$this->pdo->lastInsertId();

            // Update user pending state
            $upUser = $this->pdo->prepare("
                UPDATE users 
                SET pending_role = ?, verification_status = 'pending' 
                WHERE id = ?
            ");
            $upUser->execute([$appliedRole, $userId]);

            $this->pdo->commit();

            // Send confirmation SMS
            $smsMsg = "آسنا: درخواست عضویت تخصصی شما دریافت گردید و در دست بررسی کارشناسان قرار گرفت. نتیجه ظرف ۲۴ ساعت آینده به اطلاع شما خواهد رسید.";
            $this->sms->send($phone, $smsMsg);

            return [
                'success' => true,
                'application_id' => $appId,
                'message' => 'درخواست شما با موفقیت ثبت شد و در صف بررسی کارشناسان قرار گرفت.'
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'error' => 'خطا در ثبت درخواست: ' . $e->getMessage()];
        }
    }

    /**
     * Get list of applications for Admin Verification Board
     */
    public function getApplications(?string $role = null, string $status = 'pending'): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($status) && $status !== 'all') {
            $where[] = "ra.status = ?";
            $params[] = $status;
        }

        if (!empty($role)) {
            $where[] = "ra.applied_role = ?";
            $params[] = $role;
        }

        $sql = "
            SELECT ra.*, u.name as account_name, u.email as account_email
            FROM role_applications ra
            JOIN users u ON ra.user_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ra.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get pending count badge
     */
    public function getPendingCount(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM role_applications WHERE status = 'pending'")->fetchColumn();
    }

    /**
     * 1-Click Approve Application: Promotes user role and initializes entity profiles
     */
    public function approveApplication(int $appId, int $reviewerId, string $adminNotes = ''): bool {
        $stmt = $this->pdo->prepare("SELECT * FROM role_applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Update Application status
            $upApp = $this->pdo->prepare("
                UPDATE role_applications 
                SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), admin_notes = ? 
                WHERE id = ?
            ");
            $upApp->execute([$reviewerId, $adminNotes, $appId]);

            // 2. Promote User Role
            $role = $app['applied_role'];
            $upUser = $this->pdo->prepare("
                UPDATE users 
                SET role = ?, pending_role = NULL, verification_status = 'approved', 
                    vet_council_number = ?, is_verified_vet = 1 
                WHERE id = ?
            ");
            $upUser->execute([$role, $app['license_number'] ?? '', $app['user_id']]);

            // 3. Initialize Doctor Record if applicable
            if ($role === 'doctor') {
                $chkDoc = $this->pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
                $chkDoc->execute([$app['user_id']]);
                if (!$chkDoc->fetchColumn()) {
                    $insDoc = $this->pdo->prepare("
                        INSERT INTO doctors (name, specialty, phone, user_id, rating, review_count, created_at)
                        VALUES (?, ?, ?, ?, 5.0, 0, NOW())
                    ");
                    $insDoc->execute([
                        $app['full_name'],
                        $app['specialty'] ?: 'دامپزشک عمومی',
                        $app['phone'],
                        $app['user_id']
                    ]);
                }
            }

            // 4. Initialize Organization Record if applicable
            if ($role === 'organization') {
                $chkOrg = $this->pdo->prepare("SELECT id FROM organizations WHERE user_id = ?");
                $chkOrg->execute([$app['user_id']]);
                if (!$chkOrg->fetchColumn()) {
                    $slug = 'org-' . time() . '-' . rand(100, 999);
                    $insOrg = $this->pdo->prepare("
                        INSERT INTO organizations (
                            user_id, name, slug, type, license_number, manager_name,
                            phone, website, instagram, city, address, status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW())
                    ");
                    $insOrg->execute([
                        $app['user_id'],
                        $app['organization_name'] ?: $app['full_name'],
                        $slug,
                        $app['organization_type'] ?: 'clinic',
                        $app['license_number'],
                        $app['full_name'],
                        $app['phone'],
                        $app['website'],
                        $app['instagram'],
                        $app['city'] ?: 'تهران',
                        $app['address'] ?: 'تهران'
                    ]);
                }
            }

            $this->pdo->commit();

            // 5. Send Congratulations SMS
            $roleFa = match($role) {
                'doctor' => 'پزشک دامپزشک',
                'pharmacist' => 'داروساز تخصصی',
                'organization' => 'مرکز درمانی / کلینیک',
                'supplier' => 'تأمین‌کننده عمده',
                default => 'حرفه‌ای'
            };
            $smsMsg = "آسنا: مدارک تخصصی شما تایید شد! سطح حساب شما به [{$roleFa}] ارتقا یافت. اکنون می‌توانید از پنل اختصاصی خود استفاده نمایید.";
            $this->sms->send($app['phone'], $smsMsg);

            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * 1-Click Reject Application with explicit reason
     */
    public function rejectApplication(int $appId, int $reviewerId, string $reason): bool {
        $stmt = $this->pdo->prepare("SELECT * FROM role_applications WHERE id = ?");
        $stmt->execute([$appId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            $upApp = $this->pdo->prepare("
                UPDATE role_applications 
                SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? 
                WHERE id = ?
            ");
            $upApp->execute([$reviewerId, $reason, $appId]);

            $upUser = $this->pdo->prepare("
                UPDATE users 
                SET verification_status = 'rejected' 
                WHERE id = ?
            ");
            $upUser->execute([$app['user_id']]);

            $this->pdo->commit();

            // Send rejection SMS with corrective instructions
            $smsMsg = "آسنا: مدارک ارسالی شما مورد تایید قرار نگرفت. علت: {$reason}. لطفاً جهت بارگذاری مجدد به حساب کاربری خود مراجعه کنید.";
            $this->sms->send($app['phone'], $smsMsg);

            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }
}
