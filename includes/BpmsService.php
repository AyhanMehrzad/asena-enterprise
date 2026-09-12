<?php
/**
 * ASENA Enterprise - BPMS (Business Process Management System) Service
 * 
 * Manages the clinical prescription lifecycle and state machine for the
 * Doctor → Pharmacist → Organization → User triad workflow.
 * 
 * State Machine:
 *  broadcasted → pharmacist_review → pharmacist_approved → org_shipped → completed
 *                                  ↘ pharmacist_rejected → (doctor revision) → broadcasted
 */

class BpmsService
{
    private PDO $pdo;

    public const STATE_BROADCASTED        = 'broadcasted';
    public const STATE_PHARMACIST_REVIEW  = 'pharmacist_review';
    public const STATE_PHARMACIST_APPROVED = 'pharmacist_approved';
    public const STATE_PHARMACIST_REJECTED = 'pharmacist_rejected';
    public const STATE_ORG_SHIPPED        = 'org_shipped';
    public const STATE_COMPLETED          = 'completed';

    public const DECISION_NONE            = 'none';
    public const DECISION_APPROVED        = 'approved';
    public const DECISION_REJECTED        = 'rejected';
    public const DECISION_REVISION        = 'revision_requested';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Auto-heal schema if license_number is missing in doctors table
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM doctors LIKE 'license_number'")->fetchAll();
            if (empty($cols)) {
                $this->pdo->exec("ALTER TABLE doctors ADD COLUMN license_number VARCHAR(100) NULL DEFAULT NULL AFTER phone");
            }
        } catch (Throwable $e) {}
    }

    /**
     * DOCTOR ACTION: Submit a new prescription and broadcast to all 3 parties.
     * Creates or updates a prescription record in 'broadcasted' state.
     * Returns the prescription ID.
     */
    public function doctorSubmitPrescription(array $data): int
    {
        $userId         = (int)($data['user_id'] ?? 0);
        $doctorId       = (int)($data['doctor_id'] ?? 0);
        $petId          = (int)($data['pet_id'] ?? 0);
        $orgId          = !empty($data['organization_id']) ? (int)$data['organization_id'] : null;
        $pharmacyUserId = !empty($data['pharmacy_user_id']) ? (int)$data['pharmacy_user_id'] : null;
        $diagnosis      = trim($data['diagnosis'] ?? '');
        $examinationReport = trim($data['doctor_examination_report'] ?? '');
        $itemsJson      = is_array($data['items']) ? json_encode($data['items'], JSON_UNESCAPED_UNICODE) : ($data['items_json'] ?? '[]');
        $rxFileUrl      = trim($data['rx_file_url'] ?? '');
        $vetName        = trim($data['vet_name'] ?? '');
        $vetPhone       = trim($data['vet_phone'] ?? '');
        $vetLicense     = trim($data['vet_license_number'] ?? '');
        $clinicName     = trim($data['clinic_name'] ?? '');

        // Find pharmacy_id if pharmacy_user_id is given
        $pharmacyId = null;
        if ($pharmacyUserId) {
            $ps = $this->pdo->prepare("SELECT id FROM pharmacy_stores WHERE user_id = ?");
            $ps->execute([$pharmacyUserId]);
            $pharmacyId = $ps->fetchColumn() ?: null;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO prescriptions 
              (user_id, doctor_id, pet_id, organization_id, pharmacy_id, 
               diagnosis, doctor_examination_report, items_json, rx_file_url,
               vet_name, vet_phone, vet_license_number, clinic_name,
               status, dispensing_status, bpms_state, pharmacist_decision,
               shipping_unlocked, created_at)
            VALUES 
              (?, ?, ?, ?, ?,
               ?, ?, ?, ?,
               ?, ?, ?, ?,
               'pending', 'pending_review', ?, 'none',
               0, NOW())
        ");
        $stmt->execute([
            $userId, $doctorId, $petId ?: null, $orgId, $pharmacyId,
            $diagnosis, $examinationReport, $itemsJson, $rxFileUrl,
            $vetName, $vetPhone, $vetLicense, $clinicName,
            self::STATE_BROADCASTED,
        ]);
        $prescriptionId = (int)$this->pdo->lastInsertId();

        // Log the BPMS event
        $this->logEvent(
            $prescriptionId,
            $doctorId,
            'doctor',
            null,
            self::STATE_BROADCASTED,
            'prescription_submitted',
            "نسخه الکترونیک صادر شد و به سه طرف (سرپرست، داروخانه، کلینیک) برودکست گردید."
        );

        return $prescriptionId;
    }

    /**
     * PHARMACIST ACTION: Mark prescription as under review.
     */
    public function pharmacistStartReview(int $prescriptionId, int $pharmacistUserId): bool
    {
        $rx = $this->getPrescription($prescriptionId);
        if (!$rx || $rx['bpms_state'] !== self::STATE_BROADCASTED) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE prescriptions 
            SET bpms_state = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        $ok = $stmt->execute([self::STATE_PHARMACIST_REVIEW, $pharmacistUserId, $prescriptionId]);

        if ($ok) {
            $this->logEvent(
                $prescriptionId, $pharmacistUserId, 'pharmacist',
                self::STATE_BROADCASTED, self::STATE_PHARMACIST_REVIEW,
                'pharmacist_review_started',
                'داروساز شروع به بررسی گزارش معاینه پزشک و اقلام دارویی نمود.'
            );
        }
        return $ok;
    }

    /**
     * PHARMACIST ACTION: Approve prescription → unlock org shipping.
     */
    public function pharmacistApprove(int $prescriptionId, int $pharmacistUserId, string $notes = ''): bool
    {
        $rx = $this->getPrescription($prescriptionId);
        if (!$rx) return false;

        // Allow approval from review or directly from broadcasted
        $validStates = [self::STATE_BROADCASTED, self::STATE_PHARMACIST_REVIEW];
        if (!in_array($rx['bpms_state'], $validStates)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE prescriptions 
            SET bpms_state = ?,
                pharmacist_decision = 'approved',
                pharmacist_decision_at = NOW(),
                shipping_unlocked = 1,
                dispensing_status = 'preparing',
                status = 'approved',
                pharmacist_notes = ?,
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE id = ?
        ");
        $ok = $stmt->execute([
            self::STATE_PHARMACIST_APPROVED,
            $notes ?: 'نسخه پس از بررسی کامل گزارش بالینی پزشک، تأیید شد.',
            $pharmacistUserId,
            $prescriptionId,
        ]);

        if ($ok) {
            $this->logEvent(
                $prescriptionId, $pharmacistUserId, 'pharmacist',
                $rx['bpms_state'], self::STATE_PHARMACIST_APPROVED,
                'pharmacist_accept_receipt',
                "✅ نسخه تأیید شد. قفل فرایندی ارسال مرسوله کلینیک باز شد. " . ($notes ? "یادداشت: $notes" : '')
            );
        }
        return $ok;
    }

    /**
     * PHARMACIST ACTION: Reject prescription → notify doctor.
     */
    public function pharmacistReject(int $prescriptionId, int $pharmacistUserId, string $reason): bool
    {
        $rx = $this->getPrescription($prescriptionId);
        if (!$rx) return false;

        $validStates = [self::STATE_BROADCASTED, self::STATE_PHARMACIST_REVIEW];
        if (!in_array($rx['bpms_state'], $validStates)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE prescriptions 
            SET bpms_state = ?,
                pharmacist_decision = 'rejected',
                pharmacist_decision_at = NOW(),
                shipping_unlocked = 0,
                pharmacist_notes = ?,
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE id = ?
        ");
        $ok = $stmt->execute([
            self::STATE_PHARMACIST_REJECTED,
            $reason,
            $pharmacistUserId,
            $prescriptionId,
        ]);

        if ($ok) {
            $this->logEvent(
                $prescriptionId, $pharmacistUserId, 'pharmacist',
                $rx['bpms_state'], self::STATE_PHARMACIST_REJECTED,
                'pharmacist_reject_receipt',
                "❌ نسخه توسط داروساز رد شد. دلیل: $reason. ارسال کلینیک قفل باقی می‌ماند."
            );
        }
        return $ok;
    }

    /**
     * ORGANIZATION ACTION: Check if shipping is unlocked.
     * Returns true only if pharmacist has approved.
     */
    public function isShippingUnlocked(int $prescriptionId): bool
    {
        $stmt = $this->pdo->prepare("SELECT shipping_unlocked, bpms_state FROM prescriptions WHERE id = ?");
        $stmt->execute([$prescriptionId]);
        $rx = $stmt->fetch(PDO::FETCH_ASSOC);
        return $rx && (int)$rx['shipping_unlocked'] === 1;
    }

    /**
     * ORGANIZATION ACTION: Mark as shipped and record tracking code.
     */
    public function orgMarkShipped(int $prescriptionId, int $orgUserId, string $trackingCode, string $carrier = 'پست / پستکس'): bool
    {
        if (!$this->isShippingUnlocked($prescriptionId)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            UPDATE prescriptions 
            SET bpms_state = ?,
                shipping_tracking_code = ?,
                shipped_at = NOW(),
                dispensing_status = 'dispensed'
            WHERE id = ?
        ");
        $ok = $stmt->execute([self::STATE_ORG_SHIPPED, $trackingCode, $prescriptionId]);

        if ($ok) {
            $this->logEvent(
                $prescriptionId, $orgUserId, 'organization',
                self::STATE_PHARMACIST_APPROVED, self::STATE_ORG_SHIPPED,
                'org_dispatched',
                "📦 کلینیک مرسوله را با کد رهگیری {$trackingCode} از طریق {$carrier} ارسال نمود."
            );
        }
        return $ok;
    }

    /**
     * Get a prescription row by ID.
     */
    public function getPrescription(int $prescriptionId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prescriptions WHERE id = ?");
        $stmt->execute([$prescriptionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get BPMS audit log for a prescription.
     */
    public function getBpmsLog(int $prescriptionId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, u.name as actor_name
            FROM prescription_bpms_logs l
            LEFT JOIN users u ON l.actor_id = u.id
            WHERE l.prescription_id = ?
            ORDER BY l.created_at ASC
        ");
        $stmt->execute([$prescriptionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get prescriptions with full context for pharmacist review panel.
     */
    public function getPrescriptionsForPharmacist(?int $pharmacyId = null, int $limit = 60): array
    {
        $where = $pharmacyId ? "AND p.pharmacy_id = {$pharmacyId}" : "";
        $stmt = $this->pdo->query("
            SELECT p.*,
                   u.name as user_name, u.phone as user_phone,
                   COALESCE(NULLIF(d.name, ''), NULLIF(p.vet_name, ''), '') as doctor_name,
                   d.specialty as doctor_specialty,
                   COALESCE(NULLIF(p.vet_license_number, ''), NULLIF(d.license_number, ''), '') as doctor_license,
                   pet.pet_name, pet.species, pet.breed, pet.birth_date, pet.allergies, pet.chronic_conditions, pet.weight_kg,
                   org.name as org_name
            FROM prescriptions p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN doctors d ON p.doctor_id = d.id
            LEFT JOIN pet_health_records pet ON p.pet_id = pet.id
            LEFT JOIN organizations org ON p.organization_id = org.id
            WHERE 1=1 {$where}
            ORDER BY FIELD(p.bpms_state, 'broadcasted', 'pharmacist_review', 'pharmacist_rejected', 'pharmacist_approved', 'org_shipped', 'completed'), p.created_at DESC
            LIMIT {$limit}
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get prescriptions for a specific doctor's patients.
     */
    public function getPrescriptionsForDoctor(int $doctorId, int $limit = 80): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*,
                   u.name as user_name, u.phone as user_phone,
                   pet.pet_name, pet.species, pet.breed,
                   org.name as org_name
            FROM prescriptions p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN pet_health_records pet ON p.pet_id = pet.id
            LEFT JOIN organizations org ON p.organization_id = org.id
            WHERE p.doctor_id = ?
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$doctorId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search patients (users) seen by a doctor based on name/phone/microchip.
     */
    public function searchDoctorPatients(int $doctorId, string $query): array
    {
        $q = '%' . $query . '%';
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT u.id as user_id, u.name as user_name, u.phone,
                   pet.id as pet_id, pet.pet_name, pet.species, pet.breed, pet.microchip_id,
                   pet.birth_date, pet.weight_kg, pet.allergies, pet.chronic_conditions
            FROM appointments a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN pet_health_records pet ON (pet.user_id = u.id)
            WHERE a.doctor_id = ?
              AND (u.name LIKE ? OR u.phone LIKE ? OR pet.pet_name LIKE ? OR pet.microchip_id LIKE ? OR pet.breed LIKE ?)
            ORDER BY a.appointment_date DESC
            LIMIT 30
        ");
        $stmt->execute([$doctorId, $q, $q, $q, $q, $q]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get BPMS state label in Persian.
     */
    public static function getStateLabelFa(string $state): string
    {
        return match($state) {
            self::STATE_BROADCASTED          => 'برودکست شده (در انتظار بررسی داروساز)',
            self::STATE_PHARMACIST_REVIEW    => 'در دست بررسی داروساز',
            self::STATE_PHARMACIST_APPROVED  => '✅ تأیید داروساز - آماده ارسال کلینیک',
            self::STATE_PHARMACIST_REJECTED  => '❌ رد داروساز - نیازمند بازبینی پزشک',
            self::STATE_ORG_SHIPPED          => '📦 ارسال شد توسط کلینیک',
            self::STATE_COMPLETED            => '🎉 تحویل داده شد',
            default                          => $state,
        };
    }

    /**
     * Get BPMS state badge CSS class.
     */
    public static function getStateBadgeClass(string $state): string
    {
        return match($state) {
            self::STATE_BROADCASTED          => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            self::STATE_PHARMACIST_REVIEW    => 'bg-blue-100 text-blue-800 border-blue-200',
            self::STATE_PHARMACIST_APPROVED  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STATE_PHARMACIST_REJECTED  => 'bg-rose-100 text-rose-800 border-rose-200',
            self::STATE_ORG_SHIPPED          => 'bg-sky-100 text-sky-800 border-sky-200',
            self::STATE_COMPLETED            => 'bg-violet-100 text-violet-800 border-violet-200',
            default                          => 'bg-slate-100 text-slate-800 border-slate-200',
        };
    }

    /**
     * Internal: Log a BPMS state transition event.
     */
    private function logEvent(
        int $prescriptionId,
        ?int $actorId,
        string $actorRole,
        ?string $previousState,
        ?string $newState,
        string $actionName,
        string $comments = ''
    ): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO prescription_bpms_logs 
                  (prescription_id, actor_id, actor_role, previous_state, new_state, action_name, comments, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$prescriptionId, $actorId, $actorRole, $previousState, $newState, $actionName, $comments]);
        } catch (Throwable $e) {
            // Logging failures must never break the main flow
        }
    }
}
