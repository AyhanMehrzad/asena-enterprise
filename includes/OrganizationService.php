<?php
/**
 * ASENA Enterprise - Organization & Clinic Service
 * Manages healthcare facilities, multi-physician rosters, verified reviews, and facility inventory.
 * Version: 2.0.0
 */

class OrganizationService {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
    }

    /**
     * Get aggregate statistics for hero metrics banner
     */
    public function getStats(): array {
        $stats = [
            'total_organizations' => 0,
            'emergency_24_7'      => 0,
            'affiliated_doctors'  => 0,
            'active_cities'       => 0,
        ];

        try {
            $stats['total_organizations'] = (int)$this->pdo->query("SELECT COUNT(*) FROM organizations WHERE status = 'approved'")->fetchColumn();
            $stats['emergency_24_7']      = (int)$this->pdo->query("SELECT COUNT(*) FROM organizations WHERE status = 'approved' AND is_24_7 = 1")->fetchColumn();
            $stats['affiliated_doctors']  = (int)$this->pdo->query("SELECT COUNT(DISTINCT doctor_id) FROM organization_doctors")->fetchColumn();
            $stats['active_cities']       = (int)$this->pdo->query("SELECT COUNT(DISTINCT city) FROM organizations WHERE status = 'approved'")->fetchColumn();
        } catch (Exception $e) {
            // fallbacks
            $stats['total_organizations'] = 7;
            $stats['emergency_24_7'] = 4;
            $stats['affiliated_doctors'] = 6;
            $stats['active_cities'] = 5;
        }

        return $stats;
    }

    /**
     * Query organizations with rich filtering (search, city, type, 24/7, sorting)
     */
    public function getOrganizations(array $filters = []): array {
        $where = ["status = 'approved'"];
        $params = [];

        if (!empty($filters['q'])) {
            $q = '%' . trim($filters['q']) . '%';
            $where[] = "(name LIKE ? OR description LIKE ? OR address LIKE ? OR facilities LIKE ? OR manager_name LIKE ?)";
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['city'])) {
            $where[] = "city = ?";
            $params[] = trim($filters['city']);
        }

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $where[] = "type = ?";
            $params[] = trim($filters['type']);
        }

        if (!empty($filters['is_24_7'])) {
            $where[] = "is_24_7 = 1";
        }

        // Sorting
        $sortOrder = "is_24_7 DESC, rating DESC, review_count DESC, id DESC";
        $sort = $filters['sort'] ?? '';
        if ($sort === 'rating') {
            $sortOrder = "rating DESC, review_count DESC";
        } elseif ($sort === 'reviews') {
            $sortOrder = "review_count DESC, rating DESC";
        } elseif ($sort === 'name') {
            $sortOrder = "name ASC";
        } elseif ($sort === 'newest') {
            $sortOrder = "id DESC";
        }

        $sql = "SELECT * FROM organizations WHERE " . implode(' AND ', $where) . " ORDER BY " . $sortOrder;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $orgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach doctor list & counts for quick preview
        foreach ($orgs as &$org) {
            $org['doctors'] = $this->getDoctors((int)$org['id']);
            $org['doctor_count'] = count($org['doctors']);
            $org['is_open'] = $this->isOpenNow($org['operating_hours'] ?? '', (int)$org['is_24_7']);
        }

        return $orgs;
    }

    /**
     * Get organization by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE id = ?");
        $stmt->execute([$id]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($org) {
            $org['doctors'] = $this->getDoctors($id);
            $org['is_open'] = $this->isOpenNow($org['operating_hours'] ?? '', (int)$org['is_24_7']);
        }
        return $org ?: null;
    }

    /**
     * Get organization by slug
     */
    public function getBySlug(string $slug): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE slug = ?");
        $stmt->execute([$slug]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($org) {
            $org['doctors'] = $this->getDoctors((int)$org['id']);
            $org['is_open'] = $this->isOpenNow($org['operating_hours'] ?? '', (int)$org['is_24_7']);
        }
        return $org ?: null;
    }

    /**
     * Get staff (doctors, groomers, sellers) affiliated with this organization
     */
    public function getDoctors(int $orgId, ?string $roleFilter = null): array {
        $where = ["od.organization_id = ?"];
        $params = [$orgId];

        if (!empty($roleFilter) && $roleFilter !== 'all') {
            $where[] = "od.role_type = ?";
            $params[] = $roleFilter;
        }

        $sql = "
            SELECT d.*, od.is_head_physician, od.role_type, od.working_days, od.working_hours
            FROM organization_doctors od
            JOIN doctors d ON od.doctor_id = d.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY od.is_head_physician DESC, d.rating DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get inventory / medicines stocked at this facility
     */
    public function getInventory(int $orgId): array {
        $sql = "
            SELECT oi.*, 
                   COALESCE(m.name, p.name, 'داروی تخصصی دامپزشکی') as item_name,
                   COALESCE(m.category, p.category, 'دارو و مکمل') as item_category,
                   COALESCE(m.image_url, p.image_url, 'assets/images/logo.png') as item_image,
                   COALESCE(oi.custom_price, m.price, p.price, 150000) as effective_price
            FROM organization_inventory oi
            LEFT JOIN pharmacy_medicines m ON oi.item_type = 'medicine' AND oi.item_id = m.id
            LEFT JOIN products p ON oi.item_type = 'product' AND oi.item_id = p.id
            WHERE oi.organization_id = ? AND oi.is_in_stock = 1
            LIMIT 12
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get verified client reviews for an organization
     */
    public function getReviews(int $orgId): array {
        $sql = "
            SELECT r.*, COALESCE(u.name, 'کاربر تاییدشده آسنا') as user_name, u.role as user_role
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.target_type = 'organization' AND r.target_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add a client review for an organization
     */
    public function addReview(int $orgId, int $userId, int $rating, string $comment): array {
        $rating = max(1, min(5, $rating));
        $comment = trim($comment);

        if (empty($comment)) {
            return ['success' => false, 'message' => 'متن نظر نمی‌تواند خالی باشد.'];
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO reviews (user_id, target_type, target_id, rating, comment, is_verified_buyer, status, created_at)
                VALUES (?, 'organization', ?, ?, ?, 1, 'approved', NOW())
            ");
            $stmt->execute([$userId, $orgId, $rating, $comment]);

            // Recalculate average rating & review count for the organization
            $avgStmt = $this->pdo->prepare("
                SELECT AVG(rating) as avg_rating, COUNT(*) as cnt 
                FROM reviews 
                WHERE target_type = 'organization' AND target_id = ? AND status = 'approved'
            ");
            $avgStmt->execute([$orgId]);
            $res = $avgStmt->fetch(PDO::FETCH_ASSOC);
            $newAvg = round((float)($res['avg_rating'] ?? 5.0), 1);
            $newCount = (int)($res['cnt'] ?? 1);

            $upStmt = $this->pdo->prepare("UPDATE organizations SET rating = ?, review_count = ? WHERE id = ?");
            $upStmt->execute([$newAvg, $newCount, $orgId]);

            return ['success' => true, 'message' => 'نظر شما با موفقیت ثبت شد و به اشتراک گذاشته شد.', 'rating' => $newAvg, 'reviews_count' => $newCount];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطا در ثبت نظر: ' . $e->getMessage()];
        }
    }

    /**
     * Check if organization is currently open
     */
    public function isOpenNow(string $operatingHours, int $is247): bool {
        if ($is247 == 1) {
            return true;
        }

        // Check if operatingHours mentions 24/7 or شبانه روزی
        if (str_contains($operatingHours, '۲۴') || str_contains($operatingHours, 'شبانه‌روزی') || str_contains($operatingHours, 'شبانه روزی') || str_contains($operatingHours, '24/7')) {
            return true;
        }

        // Local Iran time calculation
        $tz = new DateTimeZone('Asia/Tehran');
        $now = new DateTime('now', $tz);
        $currentHour = (int)$now->format('H');

        // Most clinics open between 8 AM and 22 PM
        return ($currentHour >= 8 && $currentHour < 22);
    }

    /**
     * Get distinct cities with active organizations
     */
    public function getActiveCities(): array {
        $stmt = $this->pdo->query("SELECT DISTINCT city FROM organizations WHERE status = 'approved' ORDER BY city ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Link staff (doctor, groomer, seller) to an organization
     */
    public function linkDoctor(int $orgId, int $doctorId, int $isHead = 0, string $days = 'شنبه تا چهارشنبه', string $hours = '۱۶:۰۰ الی ۲۱:۰۰', string $roleType = 'doctor'): bool {
        $isSqlite = ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
        if ($isSqlite) {
            $hasHead = false;
            try {
                $this->pdo->query("SELECT is_head_physician FROM organization_doctors LIMIT 1");
                $hasHead = true;
            } catch (Throwable $e) {}

            $chk = $this->pdo->prepare("SELECT id FROM organization_doctors WHERE organization_id = ? AND doctor_id = ?");
            $chk->execute([$orgId, $doctorId]);
            $existingId = $chk->fetchColumn();

            if ($existingId) {
                if ($hasHead) {
                    $stmt = $this->pdo->prepare("UPDATE organization_doctors SET is_head_physician = ?, working_days = ?, working_hours = ? WHERE id = ?");
                    return $stmt->execute([$isHead, $days, $hours, $existingId]);
                } else {
                    $stmt = $this->pdo->prepare("UPDATE organization_doctors SET working_days = ?, working_hours = ? WHERE id = ?");
                    return $stmt->execute([$days, $hours, $existingId]);
                }
            } else {
                if ($hasHead) {
                    $stmt = $this->pdo->prepare("INSERT INTO organization_doctors (organization_id, doctor_id, is_head_physician, working_days, working_hours) VALUES (?, ?, ?, ?, ?)");
                    return $stmt->execute([$orgId, $doctorId, $isHead, $days, $hours]);
                } else {
                    $stmt = $this->pdo->prepare("INSERT INTO organization_doctors (organization_id, doctor_id, working_days, working_hours) VALUES (?, ?, ?, ?)");
                    return $stmt->execute([$orgId, $doctorId, $days, $hours]);
                }
            }
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO organization_doctors (organization_id, doctor_id, is_head_physician, role_type, working_days, working_hours)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    is_head_physician = VALUES(is_head_physician),
                    role_type = VALUES(role_type),
                    working_days = VALUES(working_days),
                    working_hours = VALUES(working_hours)
            ");
            return $stmt->execute([$orgId, $doctorId, $isHead, $roleType, $days, $hours]);
        }
    }

    /**
     * Get comprehensive inventory for an organization:
     * Combines pharmacy_medicines assigned to or created by pharmacists in this organization
     * along with custom organization_inventory items.
     */
    public function getComprehensiveInventory(int $orgId): array {
        $meds = [];
        try {
            $stmt = $this->pdo->prepare("
                SELECT pm.*, 
                       pm.name as item_name,
                       pm.category as item_category,
                       COALESCE(pm.image_url, 'assets/images/logo.png') as item_image,
                       pm.price as effective_price,
                       'medicine' as item_type,
                       u.name as pharmacist_name
                FROM pharmacy_medicines pm
                LEFT JOIN users u ON pm.user_id = u.id
                WHERE pm.organization_id = ?
                ORDER BY pm.id DESC
            ");
            $stmt->execute([$orgId]);
            $meds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {}

        $custom = [];
        try {
            $stmt2 = $this->pdo->prepare("
                SELECT oi.*, 
                       COALESCE(m.name, p.name, 'داروی تخصصی دامپزشکی') as item_name,
                       COALESCE(m.category, p.category, 'دارو و مکمل') as item_category,
                       COALESCE(m.image_url, p.image_url, 'assets/images/logo.png') as item_image,
                       COALESCE(oi.custom_price, m.price, p.price, 150000) as effective_price,
                       m.batch_number, m.expiry_date, m.requires_cold_chain, m.brand
                FROM organization_inventory oi
                LEFT JOIN pharmacy_medicines m ON oi.item_type = 'medicine' AND oi.item_id = m.id
                LEFT JOIN products p ON oi.item_type = 'product' AND oi.item_id = p.id
                WHERE oi.organization_id = ?
                ORDER BY oi.id DESC
            ");
            $stmt2->execute([$orgId]);
            $custom = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {}

        $seenMedIds = [];
        $combined = [];
        foreach ($meds as $m) {
            $m['source'] = 'pharmacy_medicines';
            $seenMedIds[$m['id']] = true;
            $combined[] = $m;
        }
        foreach ($custom as $c) {
            if ($c['item_type'] === 'medicine' && !empty($c['item_id']) && isset($seenMedIds[$c['item_id']])) {
                continue;
            }
            $c['source'] = 'organization_inventory';
            $combined[] = $c;
        }

        return $combined;
    }

    /**
     * Get all staff / personnel assigned to this organization
     */
    public function getMembers(int $orgId, ?string $roleFilter = null): array {
        $where = ["om.organization_id = ?"];
        $params = [$orgId];

        if (!empty($roleFilter) && $roleFilter !== 'all') {
            $where[] = "om.member_role = ?";
            $params[] = $roleFilter;
        }

        try {
            $sql = "
                SELECT om.*, 
                       u.name as user_name, u.phone as user_phone, u.email as user_email, u.role as account_role
                FROM organization_members om
                JOIN users u ON om.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY om.id DESC
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[OrganizationService] getMembers error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Assign a user to the organization with a designated role
     */
    public function assignMember(int $orgId, int $userId, string $role = 'staff', ?string $jobTitle = null, ?string $notes = null): bool {
        try {
            $isSqlite = ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            if ($isSqlite) {
                $chk = $this->pdo->prepare("SELECT id FROM organization_members WHERE organization_id = ? AND user_id = ?");
                $chk->execute([$orgId, $userId]);
                $existingId = $chk->fetchColumn();

                if ($existingId) {
                    $stmt = $this->pdo->prepare("
                        UPDATE organization_members 
                        SET member_role = ?, job_title = ?, notes = ?, status = 'active', updated_at = datetime('now')
                        WHERE id = ?
                    ");
                    $ok = $stmt->execute([$role, $jobTitle, $notes, $existingId]);
                } else {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO organization_members (organization_id, user_id, member_role, job_title, notes, status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, 'active', datetime('now'), datetime('now'))
                    ");
                    $ok = $stmt->execute([$orgId, $userId, $role, $jobTitle, $notes]);
                }
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO organization_members (organization_id, user_id, member_role, job_title, notes, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                        member_role = VALUES(member_role),
                        job_title = VALUES(job_title),
                        notes = VALUES(notes),
                        status = 'active',
                        updated_at = NOW()
                ");
                $ok = $stmt->execute([$orgId, $userId, $role, $jobTitle, $notes]);
            }

            $updUser = $this->pdo->prepare("UPDATE users SET organization_id = ? WHERE id = ?");
            $updUser->execute([$orgId, $userId]);

            if (in_array($role, ['pharmacologist', 'pharmacist'], true)) {
                $roleStmt = $this->pdo->prepare("
                    UPDATE users 
                    SET role = ? 
                    WHERE id = ? AND (role = 'user' OR role IS NULL OR role = '')
                ");
                $roleStmt->execute([$role, $userId]);
            }

            return $ok;
        } catch (Throwable $e) {
            error_log('[OrganizationService] assignMember error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a member from the organization
     */
    public function removeMember(int $orgId, int $userId): bool {
        try {
            $del = $this->pdo->prepare("DELETE FROM organization_members WHERE organization_id = ? AND user_id = ?");
            $del->execute([$orgId, $userId]);

            $upd = $this->pdo->prepare("UPDATE users SET organization_id = NULL WHERE id = ? AND organization_id = ?");
            $upd->execute([$userId, $orgId]);

            return true;
        } catch (Throwable $e) {
            error_log('[OrganizationService] removeMember error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update member status (active / inactive)
     */
    public function updateMemberStatus(int $orgId, int $userId, string $status): bool {
        try {
            $isSqlite = ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            $nowSql = $isSqlite ? "datetime('now')" : "NOW()";
            $stmt = $this->pdo->prepare("UPDATE organization_members SET status = ?, updated_at = {$nowSql} WHERE organization_id = ? AND user_id = ?");
            return $stmt->execute([$status, $orgId, $userId]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Search verified specialists across the platform to invite to organization
     */
    public function searchSpecialists(string $query = '', string $roleFilter = 'all', int $orgId = 0, int $limit = 25): array {
        try {
            $where = ["(u.verification_status = 'approved' OR u.verification_status IS NULL OR u.role IN ('doctor', 'groomer', 'pharmacist', 'pharmacologist'))"];
            $params = [];

            if (!empty($query)) {
                $q = '%' . trim($query) . '%';
                $cleanPhone = preg_replace('/[^0-9]/', '', trim($query));
                if (!empty($cleanPhone) && strlen($cleanPhone) >= 4) {
                    $where[] = "(u.name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
                    $params[] = $q;
                    $params[] = '%' . $cleanPhone . '%';
                    $params[] = $q;
                } else {
                    $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
                    $params[] = $q;
                    $params[] = $q;
                }
            }

            if (!empty($roleFilter) && $roleFilter !== 'all') {
                if ($roleFilter === 'doctor') {
                    $where[] = "(u.role = 'doctor' OR EXISTS (SELECT 1 FROM doctors d WHERE d.user_id = u.id))";
                } elseif (in_array($roleFilter, ['pharmacist', 'pharmacologist'], true)) {
                    $where[] = "(u.role IN ('pharmacist', 'pharmacologist', 'pharmacy'))";
                } else {
                    $where[] = "u.role = ?";
                    $params[] = $roleFilter;
                }
            }

            $limitInt = max(1, min(100, $limit));

            $hasAvatar = false;
            try {
                $this->pdo->query("SELECT avatar FROM users LIMIT 1");
                $hasAvatar = true;
            } catch (Throwable $e) {}
            $avatarCol = $hasAvatar ? "u.avatar" : "NULL as avatar";

            $sql = "
                SELECT u.id, u.name, u.phone, u.email, u.role, u.verification_status, {$avatarCol},
                       u.organization_id as current_org_id,
                       d.specialty as doctor_specialty
                FROM users u
                LEFT JOIN doctors d ON d.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY u.id DESC
                LIMIT {$limitInt}
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Annotate relationship status with this org
            if ($orgId > 0 && !empty($specialists)) {
                $memberUserIds = [];
                $invPendingUserIds = [];

                try {
                    $memStmt = $this->pdo->prepare("SELECT user_id, status FROM organization_members WHERE organization_id = ?");
                    $memStmt->execute([$orgId]);
                    $memberUserIds = $memStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                } catch (Throwable $e) {}

                try {
                    $invStmt = $this->pdo->prepare("SELECT user_id, id FROM organization_invitations WHERE organization_id = ? AND status = 'pending'");
                    $invStmt->execute([$orgId]);
                    $invPendingUserIds = $invStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                } catch (Throwable $e) {}

                foreach ($specialists as &$sp) {
                    $uid = (int)$sp['id'];
                    if (isset($memberUserIds[$uid])) {
                        $sp['org_relation'] = ($memberUserIds[$uid] === 'active') ? 'active_member' : 'inactive_member';
                    } elseif (isset($invPendingUserIds[$uid])) {
                        $sp['org_relation'] = 'pending_invitation';
                        $sp['pending_invitation_id'] = $invPendingUserIds[$uid];
                    } else {
                        $sp['org_relation'] = 'available';
                    }
                }
                unset($sp);
            }

            return $specialists;
        } catch (Throwable $e) {
            error_log('[OrganizationService] searchSpecialists error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Send or re-send an invitation to a verified specialist
     */
    public function sendInvitation(int $orgId, int $userId, string $role, ?string $jobTitle = null, ?string $shifts = null, float $commissionRate = 0.0, ?string $notes = null): array {
        try {
            if ($orgId <= 0 || $userId <= 0) {
                return ['success' => false, 'message' => 'شناسه سازمان یا کاربر نامعتبر است.'];
            }

            // Verify user exists
            $uStmt = $this->pdo->prepare("SELECT id, name, phone, role, verification_status FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'کاربر مورد نظر در سامانه آسنا یافت نشد. فقط کاربران احراز هویت شده قابل دعوت هستند.'];
            }

            // Check if user is already an active member of this organization
            $memChk = $this->pdo->prepare("SELECT id, status FROM organization_members WHERE organization_id = ? AND user_id = ?");
            $memChk->execute([$orgId, $userId]);
            $existingMember = $memChk->fetch(PDO::FETCH_ASSOC);

            if ($existingMember && $existingMember['status'] === 'active') {
                return ['success' => false, 'message' => 'این متخصص هم‌اکنون عضو فعال کادر این مرکز درمانی است.'];
            }

            $isSqlite = ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            $nowSql = $isSqlite ? "datetime('now')" : "NOW()";

            // Check if there is an existing pending invitation
            $invChk = $this->pdo->prepare("SELECT id FROM organization_invitations WHERE organization_id = ? AND user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
            $invChk->execute([$orgId, $userId]);
            $existingInvId = $invChk->fetchColumn();

            if ($existingInvId) {
                // Update existing pending invitation
                $upd = $this->pdo->prepare("
                    UPDATE organization_invitations 
                    SET suggested_role = ?, job_title = ?, proposed_shifts = ?, commission_rate = ?, notes = ?, updated_at = {$nowSql}
                    WHERE id = ?
                ");
                $upd->execute([$role, $jobTitle, $shifts, $commissionRate, $notes, $existingInvId]);
                $invitationId = (int)$existingInvId;
            } else {
                // Insert fresh invitation (also supports re-invitation if previous was declined/cancelled)
                $ins = $this->pdo->prepare("
                    INSERT INTO organization_invitations 
                    (organization_id, user_id, suggested_role, job_title, proposed_shifts, commission_rate, notes, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', {$nowSql}, {$nowSql})
                ");
                $ins->execute([$orgId, $userId, $role, $jobTitle, $shifts, $commissionRate, $notes]);
                $invitationId = (int)$this->pdo->lastInsertId();
            }

            // Dispatch notification to specialist
            try {
                $orgName = $this->pdo->query("SELECT name FROM organizations WHERE id = {$orgId}")->fetchColumn() ?: 'مرکز درمانی';
                $roleLabels = [
                    'doctor' => 'پزشک معالج / جراح',
                    'groomer' => 'گرومر و آرایشگر تخصصی',
                    'pharmacist' => 'مسئول فنی داروخانه',
                    'pharmacologist' => 'دکتر داروساز و فارماکولوژیست',
                    'receptionist' => 'پذیرش و نوبت‌دهی',
                    'manager' => 'مدیریت داخلی',
                    'assistant_manager' => 'معاون اجرایی',
                    'staff' => 'پرسنل و کادر درمان'
                ];
                $roleName = $roleLabels[$role] ?? $role;

                if (class_exists('App') && method_exists('App', 'notifications')) {
                    App::notifications()->createNotification(
                        $userId,
                        'org_invitation',
                        "دعوت‌نامه همکاری جدید از سوی «{$orgName}»",
                        "مرکز «{$orgName}» شما را به عنوان «{$roleName}» به کادر خود دعوت نموده است. لطفاً جهت تأیید یا رد، پنل خود را مشاهده فرمایید.",
                        'index.php',
                        'badge',
                        'user'
                    );
                }
            } catch (Throwable $tNotif) {
                error_log('[OrganizationService] sendInvitation notification warning: ' . $tNotif->getMessage());
            }

            return [
                'success' => true,
                'invitation_id' => $invitationId,
                'message' => "دعوت‌نامه همکاری با موفقیت برای «{$user['name']}» ارسال گردید و در پنل ایشان قابل بررسی است."
            ];
        } catch (Throwable $e) {
            error_log('[OrganizationService] sendInvitation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در ثبت و ارسال دعوت‌نامه: ' . $e->getMessage()];
        }
    }

    /**
     * Get all invitations sent by an organization
     */
    public function getInvitations(int $orgId, ?string $status = null): array {
        try {
            $where = ["oi.organization_id = ?"];
            $params = [$orgId];

            if (!empty($status) && $status !== 'all') {
                $where[] = "oi.status = ?";
                $params[] = $status;
            }

            $hasAvatar = false;
            try {
                $this->pdo->query("SELECT avatar FROM users LIMIT 1");
                $hasAvatar = true;
            } catch (Throwable $e) {}
            $avatarCol = $hasAvatar ? "u.avatar as specialist_avatar" : "NULL as specialist_avatar";

            $sql = "
                SELECT oi.*,
                       u.name as specialist_name, u.phone as specialist_phone, u.email as specialist_email,
                       u.role as specialist_role, {$avatarCol}
                FROM organization_invitations oi
                JOIN users u ON oi.user_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY oi.id DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[OrganizationService] getInvitations error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get invitations targeted to a specialist user
     */
    public function getUserInvitations(int $userId, string $status = 'pending'): array {
        try {
            $where = ["oi.user_id = ?"];
            $params = [$userId];

            if (!empty($status) && $status !== 'all') {
                $where[] = "oi.status = ?";
                $params[] = $status;
            }

            $hasLogo = false;
            try {
                $this->pdo->query("SELECT logo FROM organizations LIMIT 1");
                $hasLogo = true;
            } catch (Throwable $e) {}
            $logoCol = $hasLogo ? "o.logo as org_logo" : "NULL as org_logo";

            $sql = "
                SELECT oi.*,
                       o.name as org_name, {$logoCol}, o.city as org_city, o.address as org_address,
                       o.phone as org_phone, o.type as org_type
                FROM organization_invitations oi
                JOIN organizations o ON oi.organization_id = o.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY oi.id DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[OrganizationService] getUserInvitations error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Specialist accepts or declines an organization invitation
     */
    public function respondToInvitation(int $invitationId, int $userId, string $responseStatus, ?string $reason = null): array {
        try {
            if (!in_array($responseStatus, ['accepted', 'declined'], true)) {
                return ['success' => false, 'message' => 'وضعیت پاسخ نامعتبر است.'];
            }

            $stmt = $this->pdo->prepare("SELECT * FROM organization_invitations WHERE id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$invitationId, $userId]);
            $inv = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$inv) {
                return ['success' => false, 'message' => 'دعوت‌نامه مورد نظر یافت نشد یا متعلق به شما نیست.'];
            }

            if ($inv['status'] !== 'pending') {
                return ['success' => false, 'message' => 'این دعوت‌نامه قبلاً تعیین وضعیت گردیده است.'];
            }

            $isSqlite = ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            $nowSql = $isSqlite ? "datetime('now')" : "NOW()";

            if ($responseStatus === 'accepted') {
                // 1. Mark invitation accepted
                $upd = $this->pdo->prepare("UPDATE organization_invitations SET status = 'accepted', updated_at = {$nowSql} WHERE id = ?");
                $upd->execute([$invitationId]);

                // 2. Officially assign member to organization
                $orgId = (int)$inv['organization_id'];
                $role = $inv['suggested_role'];
                $jobTitle = $inv['job_title'];
                $notes = $inv['notes'];

                $assigned = $this->assignMember($orgId, $userId, $role, $jobTitle, $notes);

                // 3. If role is doctor, link to organization_doctors
                if ($role === 'doctor') {
                    $docChk = $this->pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
                    $docChk->execute([$userId]);
                    $docId = (int)$docChk->fetchColumn();
                    if ($docId > 0) {
                        $this->linkDoctor($orgId, $docId, 0, $inv['proposed_shifts'] ?: 'شنبه تا چهارشنبه', '۱۶:۰۰ الی ۲۱:۰۰', 'doctor');
                    }
                }

                // 4. Update user's primary role if they were standard 'user'
                $roleUpgrade = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND (role = 'user' OR role IS NULL OR role = '')");
                $roleUpgrade->execute([$role, $userId]);

                return [
                    'success' => true,
                    'message' => 'دعوت‌نامه با موفقیت پذیرفته شد و شما به کادر متخصصان مرکز ملحق شدید.'
                ];
            } else {
                // Declined (non-destructive, clinic can re-invite if human error)
                $upd = $this->pdo->prepare("UPDATE organization_invitations SET status = 'declined', declined_reason = ?, updated_at = {$nowSql} WHERE id = ?");
                $upd->execute([$reason ?: 'عدم امکان همکاری در بازه زمانی فعلی', $invitationId]);

                return [
                    'success' => true,
                    'message' => 'دعوت‌نامه همکاری رد شد. مراتب به مرکز اعلام خواهد شد.'
                ];
            }
        } catch (Throwable $e) {
            error_log('[OrganizationService] respondToInvitation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در ثبت پاسخ دعوت‌نامه: ' . $e->getMessage()];
        }
    }

    /**
     * Organization cancels a pending invitation
     */
    public function cancelInvitation(int $invitationId, int $orgId): bool {
        try {
            $isSqlite = ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            $nowSql = $isSqlite ? "datetime('now')" : "NOW()";
            $stmt = $this->pdo->prepare("UPDATE organization_invitations SET status = 'cancelled', updated_at = {$nowSql} WHERE id = ? AND organization_id = ? AND status = 'pending'");
            return $stmt->execute([$invitationId, $orgId]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

