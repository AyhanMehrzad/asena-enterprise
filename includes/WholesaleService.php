<?php
/**
 * ASENA Enterprise - B2B Wholesale & RFQ Procurement Service
 * Benchmarked against Alibaba.com B2B Pricing, MOQ & RFQ Tenders
 */

class WholesaleService
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
     * Retrieve active volume pricing tiers for a product.
     */
    public function getPriceTiers(int $productId, bool $isPharmacy = false): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM product_price_tiers 
            WHERE product_id = ? AND is_pharmacy = ?
            ORDER BY min_qty ASC
        ");
        $stmt->execute([$productId, $isPharmacy ? 1 : 0]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alibaba Feature: Calculate dynamic unit price based on purchase volume tiers.
     */
    public function getTieredUnitPrice(int $productId, bool $isPharmacy, int $qty, int $basePrice): array
    {
        $tiers = $this->getPriceTiers($productId, $isPharmacy);
        if (empty($tiers)) {
            return [
                'unit_price'       => $basePrice,
                'discount_percent' => 0.0,
                'is_tiered'        => false,
                'tier_matched'     => null
            ];
        }

        $matchedTier = null;
        foreach ($tiers as $tier) {
            $min = (int)$tier['min_qty'];
            $max = !empty($tier['max_qty']) ? (int)$tier['max_qty'] : PHP_INT_MAX;

            if ($qty >= $min && $qty <= $max) {
                $matchedTier = $tier;
            }
        }

        if ($matchedTier) {
            return [
                'unit_price'       => (int)$matchedTier['unit_price'],
                'discount_percent' => (float)$matchedTier['discount_percent'],
                'is_tiered'        => true,
                'tier_matched'     => $matchedTier
            ];
        }

        return [
            'unit_price'       => $basePrice,
            'discount_percent' => 0.0,
            'is_tiered'        => false,
            'tier_matched'     => null
        ];
    }

    /**
     * Enforce Minimum Order Quantity (MOQ) for wholesale or bulk products.
     */
    public function checkMoq(int $productId, bool $isPharmacy, int $requestedQty): array
    {
        $table = $isPharmacy ? 'pharmacy_medicines' : 'products';
        $stmt = $this->pdo->prepare("SELECT name, moq, is_b2b_only FROM {$table} WHERE id = ?");
        $stmt->execute([$productId]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            return ['allowed' => true, 'moq' => 1];
        }

        $moq = max(1, (int)($prod['moq'] ?? 1));
        if ($requestedQty < $moq) {
            return [
                'allowed' => false,
                'moq'     => $moq,
                'message' => "حداقل تعداد سفارش برای «{$prod['name']}» برابر با {$moq} عدد می‌باشد."
            ];
        }

        return ['allowed' => true, 'moq' => $moq];
    }

    /**
     * Submit a Clinic Wholesale Tender / RFQ (Request for Quotation).
     */
    public function submitRfq(int $userId, array $clinicData, array $items): int
    {
        $this->pdo->beginTransaction();
        try {
            $proformaNum = 'RFQ-' . date('Ymd') . '-' . rand(1000, 9999);
            
            $rfqStmt = $this->pdo->prepare("
                INSERT INTO b2b_rfqs 
                    (user_id, clinic_name, vet_license_num, contact_person, contact_phone, target_delivery_date, admin_notes, proforma_invoice_num, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'submitted')
            ");
            $rfqStmt->execute([
                $userId,
                trim($clinicData['clinic_name'] ?? 'کلینیک دامپزشکی'),
                trim($clinicData['vet_license_num'] ?? ''),
                trim($clinicData['contact_person'] ?? ''),
                trim($clinicData['contact_phone'] ?? ''),
                !empty($clinicData['target_delivery_date']) ? $clinicData['target_delivery_date'] : null,
                trim($clinicData['notes'] ?? ''),
                $proformaNum
            ]);
            $rfqId = (int)$this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare("
                INSERT INTO b2b_rfq_items 
                    (rfq_id, product_id, is_pharmacy, product_title, requested_quantity, target_unit_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($items as $it) {
                $itemStmt->execute([
                    $rfqId,
                    !empty($it['product_id']) ? (int)$it['product_id'] : null,
                    !empty($it['is_pharmacy']) ? 1 : 0,
                    trim($it['title'] ?? 'کالای سفارشی'),
                    max(1, (int)($it['quantity'] ?? 1)),
                    !empty($it['target_price']) ? (int)$it['target_price'] : null
                ]);
            }

            $this->pdo->commit();
            return $rfqId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("RFQ submission error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve list of RFQs with optional status filter.
     */
    public function getRfqs(?string $status = null): array
    {
        $sql = "
            SELECT r.*, u.name as submitter_name, u.phone as submitter_phone,
                   COUNT(i.id) as item_count, SUM(i.requested_quantity) as total_units
            FROM b2b_rfqs r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN b2b_rfq_items i ON r.id = i.rfq_id
        ";
        if ($status !== null) {
            $sql .= " WHERE r.status = " . $this->pdo->quote($status);
        }
        $sql .= " GROUP BY r.id ORDER BY r.created_at DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve single RFQ with all tender line items.
     */
    public function getRfqDetails(int $rfqId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, u.name as submitter_name, u.phone as submitter_phone, u.address as submitter_address
            FROM b2b_rfqs r
            JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$rfqId]);
        $rfq = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rfq) return null;

        $itemStmt = $this->pdo->prepare("SELECT * FROM b2b_rfq_items WHERE rfq_id = ? ORDER BY id ASC");
        $itemStmt->execute([$rfqId]);
        $rfq['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        return $rfq;
    }

    /**
     * Admin action: Issue formal price quote with expiration date and automated SMS alert.
     */
    public function issueQuote(int $rfqId, int $totalAmount, string $validUntil, ?string $adminNotes, array $itemPrices = []): bool
    {
        $this->pdo->beginTransaction();
        try {
            // Update items
            $itemUp = $this->pdo->prepare("UPDATE b2b_rfq_items SET quoted_unit_price = ? WHERE id = ? AND rfq_id = ?");
            foreach ($itemPrices as $itemId => $price) {
                $itemUp->execute([(int)$price, (int)$itemId, $rfqId]);
            }

            // Update RFQ header
            $upStmt = $this->pdo->prepare("
                UPDATE b2b_rfqs SET
                    status = 'quoted',
                    quoted_total_amount = ?,
                    quote_valid_until = ?,
                    admin_notes = ?
                WHERE id = ?
            ");
            $upStmt->execute([$totalAmount, $validUntil, $adminNotes, $rfqId]);

            $this->pdo->commit();

            // Send notification SMS
            $rfq = $this->getRfqDetails($rfqId);
            if ($rfq && !empty($rfq['contact_phone'])) {
                require_once __DIR__ . '/SmsService.php';
                $sms = new SmsService();
                $formattedAmount = number_format($totalAmount);
                $text = "آسنا: کلینیک {$rfq['clinic_name']} گرامی، استعلام قیمت عمده شما (#{$rfq['proforma_invoice_num']}) به مبلغ {$formattedAmount} تومان صادر شد. اعتبار تا: {$validUntil}";
                $sms->send($rfq['contact_phone'], $text);
            }

            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Issue quote error: " . $e->getMessage());
            return false;
        }
    }
}
