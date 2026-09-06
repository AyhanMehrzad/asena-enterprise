<?php
/**
 * ASENA Enterprise - Organization & Clinic Service
 * Manages healthcare facilities, multi-physician rosters, and facility inventory.
 * Version: 1.0.0
 */

class OrganizationService {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
    }

    /**
     * Query organizations with rich filtering (search, city, type, 24/7)
     */
    public function getOrganizations(array $filters = []): array {
        $where = ["status = 'approved'"];
        $params = [];

        if (!empty($filters['q'])) {
            $q = '%' . trim($filters['q']) . '%';
            $where[] = "(name LIKE ? OR description LIKE ? OR address LIKE ?)";
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($filters['city'])) {
            $where[] = "city = ?";
            $params[] = trim($filters['city']);
        }

        if (!empty($filters['type'])) {
            $where[] = "type = ?";
            $params[] = trim($filters['type']);
        }

        if (!empty($filters['is_24_7'])) {
            $where[] = "is_24_7 = 1";
        }

        $sql = "SELECT * FROM organizations WHERE " . implode(' AND ', $where) . " ORDER BY is_24_7 DESC, rating DESC, id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get organization by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE id = ?");
        $stmt->execute([$id]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        return $org ?: null;
    }

    /**
     * Get organization by slug
     */
    public function getBySlug(string $slug): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM organizations WHERE slug = ?");
        $stmt->execute([$slug]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
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
                   COALESCE(m.name, p.name) as item_name,
                   COALESCE(m.category, p.category) as item_category,
                   COALESCE(m.image_url, p.image_url) as item_image,
                   COALESCE(oi.custom_price, m.price, p.price) as effective_price
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
