<?php
/**
 * ASENA Enterprise - Recommendation & Co-Purchase Engine
 * Benchmarked against Amazon.com "Frequently Bought Together" & Chewy Smart Suggestions
 */

class RecommendationService
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
     * Amazon Feature: "Frequently Bought Together" (خرید مکرر با هم)
     * Finds items commonly co-ordered with $productId in past orders, or falls back to intelligent taxonomy matching.
     */
    public function getFrequentlyBoughtTogether(int $productId, bool $isPharmacy = false, int $limit = 2): array
    {
        $bundleItems = [];

        // 1. Data-mining on historical order_items: items appearing in the same order
        $stmt = $this->pdo->prepare("
            SELECT oi2.product_id, COUNT(*) as co_occurrences
            FROM order_items oi1
            JOIN order_items oi2 ON oi1.order_id = oi2.order_id AND oi1.product_id != oi2.product_id
            WHERE oi1.product_id = ?
            GROUP BY oi2.product_id
            ORDER BY co_occurrences DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $productId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $topIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($topIds)) {
            $inClause = implode(',', array_map('intval', $topIds));
            $pStmt = $this->pdo->query("
                SELECT id, name, price, discount_price, image_url, 'products' as source_table 
                FROM products WHERE id IN ({$inClause})
                UNION
                SELECT id, name, price, discount_price, image_url, 'pharmacy_medicines' as source_table 
                FROM pharmacy_medicines WHERE id IN ({$inClause})
            ");
            $bundleItems = $pStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 2. If historical orders yielded fewer than $limit, backfill with complementary pet products
        if (count($bundleItems) < $limit) {
            $needed = $limit - count($bundleItems);
            $sourceTable = $isPharmacy ? 'pharmacy_medicines' : 'products';
            
            // Get source item animal & category
            $srcStmt = $this->pdo->prepare("SELECT target_animal, category FROM {$sourceTable} WHERE id = ?");
            $srcStmt->execute([$productId]);
            $src = $srcStmt->fetch(PDO::FETCH_ASSOC);
            $animal = $src['target_animal'] ?? 'all';

            $excludeIds = array_merge([$productId], array_column($bundleItems, 'id'));
            $exClause = implode(',', array_map('intval', $excludeIds));

            $backfillStmt = $this->pdo->prepare("
                SELECT id, name, price, discount_price, image_url, '{$sourceTable}' as source_table
                FROM {$sourceTable}
                WHERE id NOT IN ({$exClause})
                  AND (target_animal = ? OR target_animal = 'all')
                ORDER BY stock DESC, baseline_rating DESC
                LIMIT ?
            ");
            $backfillStmt->bindValue(1, $animal, PDO::PARAM_STR);
            $backfillStmt->bindValue(2, $needed, PDO::PARAM_INT);
            $backfillStmt->execute();
            $backfill = $backfillStmt->fetchAll(PDO::FETCH_ASSOC);

            $bundleItems = array_merge($bundleItems, $backfill);
        }

        return $bundleItems;
    }

    /**
     * Compute bundle pricing with guaranteed bundle savings (5% extra discount).
     */
    public static function calculateBundle(array $mainProduct, array $addonProducts, float $bundleDiscountPercent = 5.0): array
    {
        $allItems = array_merge([$mainProduct], $addonProducts);
        $regularSum = 0;

        foreach ($allItems as $item) {
            $price = !empty($item['discount_price']) ? (int)$item['discount_price'] : (int)$item['price'];
            $regularSum += $price;
        }

        $discountFactor = 1.0 - ($bundleDiscountPercent / 100.0);
        $bundleTotal = (int)round($regularSum * $discountFactor);
        $savings = $regularSum - $bundleTotal;

        return [
            'items'         => $allItems,
            'regular_total' => $regularSum,
            'bundle_total'  => $bundleTotal,
            'savings'       => $savings,
            'discount_pct'  => $bundleDiscountPercent
        ];
    }
}
