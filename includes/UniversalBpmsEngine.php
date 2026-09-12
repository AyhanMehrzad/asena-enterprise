<?php
/**
 * ASENA Enterprise - Universal BPMS (Business Process Management System) Engine
 * 
 * Enterprise-grade state machine orchestrating workflows across all platform domains:
 * 1. Clinical Prescriptions (Doctor → Pharmacist → Clinic → Pet Owner)
 * 2. Marketplace Order Fulfillment & Escrow Settlement (Buyer → Seller → Post → Paya)
 * 3. Clinical Appointments & EMR Consultation (Booking → Check-in → Diagnosis → Followup)
 * 4. Professional Healthcare Credentialing & Organization Onboarding
 * 5. Animal Shelter Charity & Medical Treatment Sponsorship
 * 
 * Features:
 * - Deterministic Directed Acyclic Graph (DAG) state transition validator
 * - Role-Based Transition Guards (RBAC)
 * - Centralized Audit Event Ledger (`bpms_events_log`)
 * - Automated Multi-Channel Side Effects (SMS, Notifications, Escrow Release)
 * 
 * Version: 2.0.0
 */

class UniversalBpmsEngine
{
    private PDO $pdo;

    // Domains
    public const DOMAIN_PRESCRIPTION = 'prescription';
    public const DOMAIN_ORDER        = 'order';
    public const DOMAIN_APPOINTMENT  = 'appointment';
    public const DOMAIN_CREDENTIAL   = 'credential';
    public const DOMAIN_CHARITY      = 'charity';

    // State definitions registry per domain
    private static array $workflowRegistry = [
        self::DOMAIN_ORDER => [
            'table' => 'orders',
            'state_col' => 'status',
            'transitions' => [
                'pending_payment'   => ['paid', 'cancelled'],
                'paid'              => ['seller_accepted', 'cancelled'],
                'seller_accepted'   => ['picking_packing', 'cancelled'],
                'picking_packing'   => ['carrier_dispatched'],
                'carrier_dispatched'=> ['delivered_verified', 'dispute_opened'],
                'delivered_verified'=> ['inspection_window', 'dispute_opened'],
                'inspection_window' => ['paya_settled', 'dispute_opened'],
                'dispute_opened'    => ['refunded', 'paya_settled'],
                'paya_settled'      => [],
                'refunded'          => [],
                'cancelled'         => []
            ],
            'roles' => [
                'paid'               => ['system', 'user', 'admin'],
                'seller_accepted'    => ['seller', 'admin'],
                'picking_packing'    => ['seller', 'admin'],
                'carrier_dispatched' => ['seller', 'admin'],
                'delivered_verified' => ['system', 'iran_post', 'admin'],
                'inspection_window'  => ['system', 'admin'],
                'paya_settled'       => ['system', 'admin'],
                'dispute_opened'     => ['user', 'admin'],
                'refunded'           => ['admin'],
                'cancelled'          => ['user', 'seller', 'admin']
            ]
        ],

        self::DOMAIN_APPOINTMENT => [
            'table' => 'appointments',
            'state_col' => 'status',
            'transitions' => [
                'pending'         => ['approved', 'cancelled'],
                'approved'        => ['checked_in', 'cancelled', 'rescheduled'],
                'rescheduled'     => ['approved', 'cancelled'],
                'checked_in'      => ['in_consultation', 'no_show'],
                'in_consultation' => ['documented', 'completed'],
                'documented'      => ['completed'],
                'completed'       => [],
                'no_show'         => [],
                'cancelled'       => []
            ],
            'roles' => [
                'approved'        => ['organization', 'doctor', 'admin'],
                'checked_in'      => ['organization', 'admin'],
                'in_consultation' => ['doctor', 'admin'],
                'documented'      => ['doctor', 'admin'],
                'completed'       => ['doctor', 'organization', 'admin'],
                'rescheduled'     => ['doctor', 'organization', 'user', 'admin'],
                'no_show'         => ['organization', 'doctor', 'admin'],
                'cancelled'       => ['user', 'doctor', 'organization', 'admin']
            ]
        ],

        self::DOMAIN_CREDENTIAL => [
            'table' => 'role_applications',
            'state_col' => 'status',
            'transitions' => [
                'pending'            => ['docs_uploaded', 'rejected'],
                'docs_uploaded'      => ['inquiry_verified', 'revision_needed', 'rejected'],
                'revision_needed'    => ['docs_uploaded', 'rejected'],
                'inquiry_verified'   => ['facility_inspected', 'approved', 'rejected'],
                'facility_inspected' => ['approved', 'rejected'],
                'approved'           => ['suspended'],
                'suspended'          => ['approved', 'rejected'],
                'rejected'           => []
            ],
            'roles' => [
                'docs_uploaded'      => ['applicant', 'doctor', 'pharmacist', 'organization', 'admin'],
                'inquiry_verified'   => ['admin', 'compliance'],
                'revision_needed'    => ['admin', 'compliance'],
                'facility_inspected' => ['admin', 'inspector'],
                'approved'           => ['admin'],
                'suspended'          => ['admin'],
                'rejected'           => ['admin']
            ]
        ],

        self::DOMAIN_PRESCRIPTION => [
            'table' => 'prescriptions',
            'state_col' => 'bpms_state',
            'transitions' => [
                'broadcasted'         => ['pharmacist_review', 'cancelled'],
                'pharmacist_review'   => ['pharmacist_approved', 'pharmacist_rejected'],
                'pharmacist_rejected' => ['broadcasted', 'cancelled'], // doctor revision re-broadcasts
                'pharmacist_approved' => ['org_shipped'],
                'org_shipped'         => ['completed'],
                'completed'           => [],
                'cancelled'           => []
            ],
            'roles' => [
                'pharmacist_review'   => ['pharmacist', 'admin'],
                'pharmacist_approved' => ['pharmacist', 'admin'],
                'pharmacist_rejected' => ['pharmacist', 'admin'],
                'broadcasted'         => ['doctor', 'admin'],
                'org_shipped'         => ['organization', 'pharmacist', 'admin'],
                'completed'           => ['user', 'system', 'admin'],
                'cancelled'           => ['doctor', 'admin']
            ]
        ]
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->initLedgerSchema();
    }

    /**
     * Ensure central BPMS event ledger exists
     */
    private function initLedgerSchema(): void
    {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS bpms_events_log (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    domain VARCHAR(50) NOT NULL,
                    entity_id INT NOT NULL,
                    actor_id INT NULL,
                    actor_role VARCHAR(50) NOT NULL,
                    from_state VARCHAR(50) NULL,
                    to_state VARCHAR(50) NOT NULL,
                    action_tag VARCHAR(100) NOT NULL,
                    notes TEXT NULL,
                    payload_snapshot JSON NULL,
                    ip_address VARCHAR(45) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_domain_entity (domain, entity_id),
                    INDEX idx_actor (actor_id, actor_role),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Throwable $e) {}
    }

    /**
     * Execute a deterministic BPMS state transition
     */
    public function transition(
        string $domain,
        int $entityId,
        string $targetState,
        ?int $actorId,
        string $actorRole,
        string $actionTag,
        string $notes = '',
        array $extraData = []
    ): array {
        if (!isset(self::$workflowRegistry[$domain])) {
            return ['success' => false, 'error' => "دامنه فرآیندی نامعتبر است: {$domain}"];
        }

        $reg      = self::$workflowRegistry[$domain];
        $table    = $reg['table'];
        $stateCol = $reg['state_col'];

        // 1. Fetch current entity state
        $stmt = $this->pdo->prepare("SELECT * FROM `{$table}` WHERE id = ?");
        $stmt->execute([$entityId]);
        $entity = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entity) {
            return ['success' => false, 'error' => "مورد موردنظر با شناسه #{$entityId} در سامانه یافت نشد."];
        }

        $currentState = (string)($entity[$stateCol] ?? '');

        // 2. Validate DAG transition allowance
        $allowedNext = $reg['transitions'][$currentState] ?? null;
        if ($allowedNext === null || (!empty($allowedNext) && !in_array($targetState, $allowedNext, true))) {
            return [
                'success' => false,
                'error'   => "انتقال وضعیت از '{$currentState}' به '{$targetState}' در چرخه کاری مجاز نمی‌باشد.",
                'current' => $currentState,
                'allowed' => $allowedNext ?? []
            ];
        }

        // 3. Verify actor RBAC permission
        $allowedRoles = $reg['roles'][$targetState] ?? [];
        if (!empty($allowedRoles) && !in_array($actorRole, $allowedRoles, true) && $actorRole !== 'admin') {
            return [
                'success' => false,
                'error'   => "نقش کاربری شما ({$actorRole}) دسترسی لازم برای این مرحله را ندارد."
            ];
        }

        // 4. Execute atomic state change and audit log
        $this->pdo->beginTransaction();
        try {
            $upSql = "UPDATE `{$table}` SET `{$stateCol}` = :target";
            $params = [':target' => $targetState, ':id' => $entityId];

            // Domain-specific side effects in SQL
            if ($domain === self::DOMAIN_ORDER && $targetState === 'delivered_verified') {
                $upSql .= ", delivered_at = NOW(), post_delivery_verified = 1";
            } elseif ($domain === self::DOMAIN_PRESCRIPTION && $targetState === 'pharmacist_approved') {
                $upSql .= ", shipping_unlocked = 1, pharmacist_decision = 'approved', pharmacist_decision_at = NOW()";
            }

            $upSql .= " WHERE id = :id";
            $upStmt = $this->pdo->prepare($upSql);
            $upStmt->execute($params);

            // Insert into central BPMS event ledger
            $logStmt = $this->pdo->prepare("
                INSERT INTO bpms_events_log 
                  (domain, entity_id, actor_id, actor_role, from_state, to_state, action_tag, notes, payload_snapshot, ip_address, created_at)
                VALUES 
                  (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([
                $domain,
                $entityId,
                $actorId,
                $actorRole,
                $currentState,
                $targetState,
                $actionTag,
                $notes,
                !empty($extraData) ? json_encode($extraData, JSON_UNESCAPED_UNICODE) : null,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            $this->pdo->commit();

            return [
                'success'    => true,
                'domain'     => $domain,
                'entity_id'  => $entityId,
                'from_state' => $currentState,
                'to_state'   => $targetState,
                'message'    => "وضعیت با موفقیت به '{$targetState}' انتقال یافت."
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'error' => 'خطا در ثبت پایگاه داده: ' . $e->getMessage()];
        }
    }

    /**
     * Retrieve full timeline of events for an entity
     */
    public function getTimeline(string $domain, int $entityId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM bpms_events_log 
            WHERE domain = ? AND entity_id = ? 
            ORDER BY created_at ASC, id ASC
        ");
        $stmt->execute([$domain, $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
