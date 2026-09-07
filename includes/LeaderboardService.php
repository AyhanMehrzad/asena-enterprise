<?php
/**
 * ASENA Enterprise - Leaderboard & Dynamic Top-5 Badging Engine
 * Computes dynamic real-time rankings for Organizations, Doctors/Specialists, and Sellers.
 * Badges are dynamically granted to Top 5 performers and automatically revoked upon performance degradation.
 */

class LeaderboardService {
    private PDO $pdo;
    private static ?array $top5OrgCache = null;
    private static ?array $top5DocCache = null;
    private static ?array $top5SellerCache = null;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
    }

    /**
     * Get Top 5 Organization IDs dynamically
     */
    public function getTop5OrganizationIds(): array {
        if (self::$top5OrgCache !== null) {
            return self::$top5OrgCache;
        }

        // Bayesian score formula: (rating * 0.6) + (LOG10(review_count + 1) * 1.5) + (is_24_7 * 0.3)
        $sql = "
            SELECT id,
                   (IFNULL(rating, 5.0) * 0.6) + (LOG10(IFNULL(review_count, 0) + 1) * 1.5) + (IFNULL(is_24_7, 0) * 0.3) AS perf_score
            FROM organizations
            WHERE status = 'approved'
            ORDER BY perf_score DESC, rating DESC, review_count DESC, id ASC
            LIMIT 5
        ";
        try {
            $stmt = $this->pdo->query($sql);
            self::$top5OrgCache = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Throwable $e) {
            self::$top5OrgCache = [];
        }

        return self::$top5OrgCache;
    }

    /**
     * Check if a specific organization is currently in Top 5
     */
    public function isTopOrganization(int $orgId): bool {
        return in_array($orgId, $this->getTop5OrganizationIds(), true);
    }

    /**
     * Get Top 5 Doctor/Specialist IDs dynamically
     */
    public function getTop5DoctorIds(): array {
        if (self::$top5DocCache !== null) {
            return self::$top5DocCache;
        }

        $sql = "
            SELECT d.id,
                   (IFNULL(d.rating, 5.0) * 0.6) + (LOG10(IFNULL(d.review_count, 0) + 1) * 1.5) + 
                   (LOG10(IFNULL((SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id AND a.status IN ('approved', 'completed')), 0) + 1) * 0.8) AS perf_score
            FROM doctors d
            ORDER BY perf_score DESC, d.rating DESC, d.review_count DESC, d.id ASC
            LIMIT 5
        ";
        try {
            $stmt = $this->pdo->query($sql);
            self::$top5DocCache = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Throwable $e) {
            self::$top5DocCache = [];
        }

        return self::$top5DocCache;
    }

    /**
     * Check if doctor/specialist is in Top 5
     */
    public function isTopDoctor(int $doctorId): bool {
        return in_array($doctorId, $this->getTop5DoctorIds(), true);
    }

    /**
     * Get Top 5 Seller IDs dynamically
     */
    public function getTop5SellerIds(): array {
        if (self::$top5SellerCache !== null) {
            return self::$top5SellerCache;
        }

        $sql = "
            SELECT u.id,
                   (IFNULL(w.balance_settled_lifetime, 0) * 0.00001) + (IFNULL(w.balance_available_for_payout, 0) * 0.000005) +
                   ((SELECT COUNT(*) FROM products p WHERE p.seller_id = u.id) * 0.5) AS perf_score
            FROM users u
            LEFT JOIN seller_wallets w ON u.id = w.seller_id
            WHERE u.role = 'seller'
            ORDER BY perf_score DESC, u.id ASC
            LIMIT 5
        ";
        try {
            $stmt = $this->pdo->query($sql);
            self::$top5SellerCache = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Throwable $e) {
            self::$top5SellerCache = [];
        }

        return self::$top5SellerCache;
    }

    /**
     * Check if seller is in Top 5
     */
    public function isTopSeller(int $sellerId): bool {
        return in_array($sellerId, $this->getTop5SellerIds(), true);
    }

    /**
     * Get dynamic leaderboard for Admin Showcase with rich filtering & sorting
     *
     * @param string $category 'organizations' | 'doctors' | 'sellers'
     * @param array $filters ['sort' => 'best'|'rating'|'reviews'|'created_desc'|'created_asc'|'activity', 'top_only' => bool, 'q' => string, 'city' => string]
     * @return array
     */
    public function getLeaderboard(string $category, array $filters = []): array {
        $sort = $filters['sort'] ?? 'best';
        $topOnly = !empty($filters['top_only']);
        $search = trim($filters['q'] ?? '');
        $city = trim($filters['city'] ?? '');

        switch ($category) {
            case 'organizations':
                return $this->queryOrganizationsLeaderboard($sort, $topOnly, $search, $city);

            case 'doctors':
                return $this->queryDoctorsLeaderboard($sort, $topOnly, $search);

            case 'sellers':
                return $this->querySellersLeaderboard($sort, $topOnly, $search);

            default:
                return [];
        }
    }

    /**
     * Query organizations leaderboard
     */
    private function queryOrganizationsLeaderboard(string $sort, bool $topOnly, string $search, string $city): array {
        $where = ["o.status = 'approved'"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(o.name LIKE ? OR o.manager_name LIKE ? OR o.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($city)) {
            $where[] = "o.city = ?";
            $params[] = $city;
        }

        $top5Ids = $this->getTop5OrganizationIds();
        if ($topOnly) {
            if (empty($top5Ids)) return [];
            $placeholders = implode(',', array_fill(0, count($top5Ids), '?'));
            $where[] = "o.id IN ($placeholders)";
            $params = array_merge($params, $top5Ids);
        }

        // Sorting mapping
        $orderBy = match ($sort) {
            'rating'       => "o.rating DESC, o.review_count DESC",
            'reviews'      => "o.review_count DESC, o.rating DESC",
            'created_desc' => "o.created_at DESC, o.id DESC",
            'created_asc'  => "o.created_at ASC, o.id ASC",
            'activity'     => "appointments_count DESC, o.rating DESC",
            default        => "perf_score DESC, o.rating DESC, o.review_count DESC"
        };

        $sql = "
            SELECT o.*,
                   (IFNULL(o.rating, 5.0) * 0.6) + (LOG10(IFNULL(o.review_count, 0) + 1) * 1.5) + (IFNULL(o.is_24_7, 0) * 0.3) AS perf_score,
                   (SELECT COUNT(*) FROM appointments a WHERE a.organization_id = o.id) AS appointments_count,
                   (SELECT COUNT(*) FROM organization_doctors od WHERE od.organization_id = o.id) AS doctors_count
            FROM organizations o
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$orderBy}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Assign ranks and top-5 flags
        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
            $row['is_top_5'] = in_array((int)$row['id'], $top5Ids, true);
            $row['badge_label'] = $row['is_top_5'] ? '🏆 ۵ مرکز برتر کشور' : null;
        }

        return $rows;
    }

    /**
     * Query doctors / specialists leaderboard
     */
    private function queryDoctorsLeaderboard(string $sort, bool $topOnly, string $search): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(d.name LIKE ? OR d.specialty LIKE ? OR d.clinic_name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $top5Ids = $this->getTop5DoctorIds();
        if ($topOnly) {
            if (empty($top5Ids)) return [];
            $placeholders = implode(',', array_fill(0, count($top5Ids), '?'));
            $where[] = "d.id IN ($placeholders)";
            $params = array_merge($params, $top5Ids);
        }

        $orderBy = match ($sort) {
            'rating'       => "d.rating DESC, d.review_count DESC",
            'reviews'      => "d.review_count DESC, d.rating DESC",
            'created_desc' => "d.id DESC",
            'created_asc'  => "d.id ASC",
            'activity'     => "appointments_count DESC, d.rating DESC",
            default        => "perf_score DESC, d.rating DESC, d.review_count DESC"
        };

        $sql = "
            SELECT d.*,
                   (IFNULL(d.rating, 5.0) * 0.6) + (LOG10(IFNULL(d.review_count, 0) + 1) * 1.5) + 
                   (LOG10(IFNULL((SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id AND a.status IN ('approved', 'completed')), 0) + 1) * 0.8) AS perf_score,
                   (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id) AS appointments_count,
                   (SELECT o.name FROM organizations o WHERE o.id = d.organization_id LIMIT 1) AS organization_name
            FROM doctors d
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$orderBy}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
            $row['is_top_5'] = in_array((int)$row['id'], $top5Ids, true);
            $typeLabel = ($row['provider_type'] ?? '') === 'groomer' ? 'گرومر' : 'پزشک';
            $row['badge_label'] = $row['is_top_5'] ? "🩺 {$typeLabel} برگزیده سامانه" : null;
        }

        return $rows;
    }

    /**
     * Query sellers leaderboard
     */
    private function querySellersLeaderboard(string $sort, bool $topOnly, string $search): array {
        $where = ["u.role = 'seller'"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(u.name LIKE ? OR u.phone LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $top5Ids = $this->getTop5SellerIds();
        if ($topOnly) {
            if (empty($top5Ids)) return [];
            $placeholders = implode(',', array_fill(0, count($top5Ids), '?'));
            $where[] = "u.id IN ($placeholders)";
            $params = array_merge($params, $top5Ids);
        }

        $orderBy = match ($sort) {
            'rating'       => "products_count DESC, total_sales_volume DESC",
            'reviews'      => "products_count DESC, u.id DESC",
            'created_desc' => "u.created_at DESC, u.id DESC",
            'created_asc'  => "u.created_at ASC, u.id ASC",
            'activity'     => "total_sales_volume DESC, products_count DESC",
            default        => "perf_score DESC, total_sales_volume DESC"
        };

        $sql = "
            SELECT u.id, u.name, u.phone, u.created_at,
                   IFNULL(w.bank_name, 'ثبت نشده') AS bank_name,
                   IFNULL(w.balance_available_for_payout, 0) AS balance_available,
                   IFNULL(w.balance_settled_lifetime, 0) AS total_sales_volume,
                   (SELECT COUNT(*) FROM products p WHERE p.seller_id = u.id) AS products_count,
                   (IFNULL(w.balance_settled_lifetime, 0) * 0.00001) + (IFNULL(w.balance_available_for_payout, 0) * 0.000005) +
                   ((SELECT COUNT(*) FROM products p WHERE p.seller_id = u.id) * 0.5) AS perf_score
            FROM users u
            LEFT JOIN seller_wallets w ON u.id = w.seller_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$orderBy}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
            $row['is_top_5'] = in_array((int)$row['id'], $top5Ids, true);
            $row['badge_label'] = $row['is_top_5'] ? '🛍️ فروشنده برتر پلتفرم' : null;
        }

        return $rows;
    }

    /**
     * Get summary metrics for admin dashboard cards
     */
    public function getSummaryStats(): array {
        $stats = [
            'top_orgs_count'    => count($this->getTop5OrganizationIds()),
            'top_docs_count'    => count($this->getTop5DoctorIds()),
            'top_sellers_count' => count($this->getTop5SellerIds()),
            'best_org_name'     => 'نامشخص',
            'best_doc_name'     => 'نامشخص',
            'best_seller_name'  => 'نامشخص',
        ];

        try {
            $topOrgs = $this->getTop5OrganizationIds();
            if (!empty($topOrgs[0])) {
                $st = $this->pdo->prepare("SELECT name FROM organizations WHERE id = ?");
                $st->execute([$topOrgs[0]]);
                $stats['best_org_name'] = $st->fetchColumn() ?: 'نامشخص';
            }

            $topDocs = $this->getTop5DoctorIds();
            if (!empty($topDocs[0])) {
                $st = $this->pdo->prepare("SELECT name FROM doctors WHERE id = ?");
                $st->execute([$topDocs[0]]);
                $stats['best_doc_name'] = $st->fetchColumn() ?: 'نامشخص';
            }

            $topSellers = $this->getTop5SellerIds();
            if (!empty($topSellers[0])) {
                $st = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
                $st->execute([$topSellers[0]]);
                $stats['best_seller_name'] = $st->fetchColumn() ?: 'نامشخص';
            }
        } catch (Throwable $e) {}

        return $stats;
    }
}
