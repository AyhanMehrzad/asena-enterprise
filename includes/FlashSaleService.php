<?php
/**
 * ASENA Enterprise - Flash Sale & Incredible Offers Engine
 * Benchmarked against Digikala.com "پیشنهاد شگفت‌انگیز"
 */

class FlashSaleService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            $this->pdo = $pdo;
        }
    }

    /**
     * Retrieve all active flash sales with product details and remaining quota.
     */
    public function getActiveFlashSales(int $limit = 6): array
    {
        $stmt = $this->pdo->prepare("
            SELECT fs.*,
                   COALESCE(pm.name, p.name) as product_name,
                   COALESCE(pm.price, p.price) as regular_price,
                   COALESCE(pm.image_url, p.image_url) as image_url,
                   COALESCE(pm.category, p.category) as category,
                   COALESCE(pm.target_animal, p.target_animal) as target_animal,
                   TIMESTAMPDIFF(SECOND, NOW(), fs.ends_at) as seconds_remaining
            FROM flash_sales fs
            LEFT JOIN pharmacy_medicines pm ON fs.product_id = pm.id AND fs.is_pharmacy = 1
            LEFT JOIN products p ON fs.product_id = p.id AND fs.is_pharmacy = 0
            WHERE fs.is_active = 1
              AND fs.starts_at <= NOW()
              AND fs.ends_at > NOW()
              AND fs.claimed_count < fs.stock_quota
            ORDER BY fs.ends_at ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate discount percentage and stock progress
        foreach ($deals as &$d) {
            $reg = (int)$d['regular_price'];
            $special = (int)$d['special_price'];
            $d['discount_pct'] = ($reg > 0) ? (int)round((($reg - $special) / $reg) * 100) : 0;
            $quota = max(1, (int)$d['stock_quota']);
            $claimed = (int)$d['claimed_count'];
            $d['progress_percent'] = min(100, (int)round(($claimed / $quota) * 100));
            $d['remaining_stock'] = max(0, $quota - $claimed);
        }

        return $deals;
    }

    /**
     * Retrieve the primary active flash sale deal for hero/banner promotion.
     */
    public function getActiveFlashSale(): ?array
    {
        $deals = $this->getActiveFlashSales(1);
        if (empty($deals)) {
            return null;
        }

        $deal = $deals[0];
        $deal['title'] = $deal['title'] ?? ($deal['product_name'] ? 'تخفیف ویژه: ' . $deal['product_name'] : 'پیشنهاد شگفت‌انگیز آسنا');
        $deal['flash_price'] = (int)($deal['special_price'] ?? 0);
        $deal['original_price'] = (int)($deal['regular_price'] ?? 0);
        $deal['claimed_percent'] = (int)($deal['progress_percent'] ?? 0);

        return $deal;
    }

    /**
     * Claim / decrement a flash sale item upon purchase.
     */
    public function recordClaim(int $flashSaleId, int $qty = 1): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE flash_sales 
            SET claimed_count = claimed_count + ? 
            WHERE id = ? AND (claimed_count + ?) <= stock_quota
        ");
        return $stmt->execute([$qty, $flashSaleId, $qty]);
    }
}
