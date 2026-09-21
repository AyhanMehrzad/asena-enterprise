<?php
/**
 * PromoCodeService - ASENA Enterprise Multi-Vendor Marketplace Promotion Engine
 *
 * Implements:
 * 1. Platform-Funded Margin Absorption (100% provider payout preservation)
 * 2. Statutory 10% Post-Discount VAT Taxation
 * 3. Anti-Abuse Atomic Redemptions Tracking
 * 4. Percentage Ceiling Caps & Minimum Subtotal Thresholds
 * 5. First-Order & Per-User Redemption Limitations
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

class PromoCodeService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? $GLOBALS['pdo'];
    }

    /**
     * Validate promo code against all business rules and calculate discount
     */
    public function validatePromo(string $code, int $userId, int $subtotal): array {
        $cleanCode = strtoupper(trim($code));

        if (empty($cleanCode)) {
            return ['valid' => false, 'message' => 'لطفاً کد تخفیف را وارد نمایید.'];
        }

        if ($subtotal <= 0) {
            return ['valid' => false, 'message' => 'سبد خرید شما خالی است یا مبلغ نامعتبر است.'];
        }

        $stmt = $this->db->prepare("SELECT * FROM promo_codes WHERE code = ? LIMIT 1");
        $stmt->execute([$cleanCode]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$promo) {
            return ['valid' => false, 'message' => 'کد تخفیف وارد شده معتبر نمی‌باشد.'];
        }

        if (empty($promo['is_active'])) {
            return ['valid' => false, 'message' => 'این کد تخفیف در حال حاضر غیرفعال است.'];
        }

        $now = date('Y-m-d H:i:s');
        if (!empty($promo['starts_at']) && $promo['starts_at'] > $now) {
            return ['valid' => false, 'message' => 'زمان استفاده از این کد تخفیف هنوز فرا نرسیده است.'];
        }

        if (!empty($promo['expires_at']) && $promo['expires_at'] < $now) {
            return ['valid' => false, 'message' => 'مهلت استفاده از این کد تخفیف به پایان رسیده است.'];
        }

        $minOrder = (int)($promo['min_order_amount'] ?? 0);
        if ($minOrder > 0 && $subtotal < $minOrder) {
            return [
                'valid' => false,
                'message' => 'حداقل مبلغ سفارش برای استفاده از این کد ' . number_format($minOrder) . ' تومان می‌باشد.'
            ];
        }

        // Global usage limit
        if (!empty($promo['usage_limit_total'])) {
            $totalUsesStmt = $this->db->prepare("SELECT COUNT(*) FROM promo_code_usages WHERE promo_code_id = ?");
            $totalUsesStmt->execute([$promo['id']]);
            $totalUses = (int)$totalUsesStmt->fetchColumn();

            if ($totalUses >= (int)$promo['usage_limit_total']) {
                return ['valid' => false, 'message' => 'ظرفیت استفاده از این کد تخفیف تکمیل شده است.'];
            }
        }

        // First-order-only restriction
        if (!empty($promo['first_order_only']) && $userId > 0) {
            $orderCountStmt = $this->db->prepare("
                SELECT COUNT(*) FROM orders 
                WHERE user_id = ? AND status IN ('processing', 'shipped', 'delivered')
            ");
            $orderCountStmt->execute([$userId]);
            $priorOrders = (int)$orderCountStmt->fetchColumn();

            if ($priorOrders > 0) {
                return ['valid' => false, 'message' => 'این کد تخفیف فقط برای اولین خرید کاربران جدید معتبر است.'];
            }
        }

        // Per-user usage limit
        if ($userId > 0) {
            $userLimit = (int)($promo['usage_limit_per_user'] ?? 1);
            $userUsesStmt = $this->db->prepare("
                SELECT COUNT(*) FROM promo_code_usages 
                WHERE promo_code_id = ? AND user_id = ?
            ");
            $userUsesStmt->execute([$promo['id'], $userId]);
            $userUses = (int)$userUsesStmt->fetchColumn();

            if ($userUses >= $userLimit) {
                return ['valid' => false, 'message' => 'شما قبلاً از این کد تخفیف استفاده کرده‌اید.'];
            }
        }

        // Calculate discount
        $discountType = $promo['discount_type'] ?? 'percentage';
        $discountValue = (int)$promo['discount_value'];
        $maxCap = !empty($promo['max_discount_amount']) ? (int)$promo['max_discount_amount'] : null;

        if ($discountType === 'percentage') {
            $calculatedDiscount = (int)round($subtotal * ($discountValue / 100.0));
            if ($maxCap !== null && $maxCap > 0 && $calculatedDiscount > $maxCap) {
                $discountAmount = $maxCap;
            } else {
                $discountAmount = $calculatedDiscount;
            }
        } else {
            $discountAmount = min($discountValue, $subtotal);
        }

        // Guard: discount can never exceed subtotal
        $discountAmount = max(0, min($discountAmount, $subtotal));

        return [
            'valid' => true,
            'promo_id' => (int)$promo['id'],
            'code' => $promo['code'],
            'title' => $promo['title'],
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'max_discount_amount' => $maxCap,
            'discount_amount' => $discountAmount,
            'message' => 'کد تخفیف با موفقیت اعمال گردید.'
        ];
    }

    /**
     * Compute checkout totals following statutory post-discount 10% VAT
     */
    public function calculateTotals(int $subtotal, ?array $appliedPromo, ?float $taxRatePct = null): array {
        if ($taxRatePct === null) {
            $taxRatePct = (float)get_setting($this->db, 'tax_rate_percent', 10.0);
        }

        $discountAmount = (int)($appliedPromo['discount_amount'] ?? 0);
        $taxableSubtotal = max(0, $subtotal - $discountAmount);
        $taxAmount = (int)round($taxableSubtotal * ($taxRatePct / 100.0));
        $finalTotal = $taxableSubtotal + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'promo_code' => $appliedPromo['code'] ?? null,
            'promo_title' => $appliedPromo['title'] ?? null,
            'taxable_subtotal' => $taxableSubtotal,
            'tax_rate_pct' => $taxRatePct,
            'tax_amount' => $taxAmount,
            'final_total' => $finalTotal
        ];
    }

    /**
     * Atomically log promo code redemption against an order
     */
    public function recordUsage(int $promoId, int $userId, int $orderId, int $discountAmount): bool {
        if ($promoId <= 0 || $userId <= 0 || $discountAmount <= 0) {
            return false;
        }

        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare("
                INSERT INTO promo_code_usages 
                (promo_code_id, user_id, order_id, discount_amount, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$promoId, $userId, $orderId, $discountAmount, $now]);
        } catch (Throwable $e) {
            error_log("PromoCodeService recordUsage error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Admin: Retrieve all promotional codes with redemption metrics
     */
    public function getAllPromoCodes(): array {
        try {
            $query = "
                SELECT p.*, 
                       COUNT(u.id) as total_redemptions,
                       COALESCE(SUM(u.discount_amount), 0) as total_discount_granted
                FROM promo_codes p
                LEFT JOIN promo_code_usages u ON p.id = u.promo_code_id
                GROUP BY p.id
                ORDER BY p.created_at DESC
            ";
            $res = $this->db->query($query);
            return $res ? $res->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log("PromoCodeService getAllPromoCodes error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Admin: Retrieve promo code audit logs / usages
     */
    public function getPromoAuditLogs(int $limit = 25): array {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, 
                       p.code as promo_code,
                       usr.name as user_name,
                       usr.phone as user_phone
                FROM promo_code_usages u
                JOIN promo_codes p ON u.promo_code_id = p.id
                LEFT JOIN users usr ON u.user_id = usr.id
                ORDER BY u.created_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("PromoCodeService getPromoAuditLogs error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Admin: Create or update a promo code
     */
    public function savePromoCode(array $data): array {
        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $code = strtoupper(trim($data['code'] ?? ''));
        $title = trim($data['title'] ?? 'کد تخفیف آسنا');
        $discountType = ($data['discount_type'] ?? 'percentage') === 'fixed_amount' ? 'fixed_amount' : 'percentage';
        $discountValue = max(1, (int)($data['discount_value'] ?? 10));
        $maxCap = !empty($data['max_discount_amount']) ? max(1, (int)$data['max_discount_amount']) : null;
        $minOrder = max(0, (int)($data['min_order_amount'] ?? 0));
        $usageLimitTotal = !empty($data['usage_limit_total']) ? max(1, (int)$data['usage_limit_total']) : null;
        $usageLimitUser = max(1, (int)($data['usage_limit_per_user'] ?? 1));
        $firstOrderOnly = !empty($data['first_order_only']) ? 1 : 0;
        $startsAt = !empty($data['starts_at']) ? $data['starts_at'] : null;
        $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if (empty($code)) {
            return ['success' => false, 'message' => 'عنوان کد تخفیف الزامی است.'];
        }

        try {
            $now = date('Y-m-d H:i:s');
            if ($id) {
                $stmt = $this->db->prepare("
                    UPDATE promo_codes SET
                        code = ?, title = ?, discount_type = ?, discount_value = ?,
                        max_discount_amount = ?, min_order_amount = ?, usage_limit_total = ?,
                        usage_limit_per_user = ?, first_order_only = ?, starts_at = ?,
                        expires_at = ?, is_active = ?, updated_at = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $code, $title, $discountType, $discountValue,
                    $maxCap, $minOrder, $usageLimitTotal,
                    $usageLimitUser, $firstOrderOnly, $startsAt,
                    $expiresAt, $isActive, $now, $id
                ]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO promo_codes (
                        code, title, discount_type, discount_value,
                        max_discount_amount, min_order_amount, usage_limit_total,
                        usage_limit_per_user, first_order_only, starts_at,
                        expires_at, is_active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $code, $title, $discountType, $discountValue,
                    $maxCap, $minOrder, $usageLimitTotal,
                    $usageLimitUser, $firstOrderOnly, $startsAt,
                    $expiresAt, $isActive, $now, $now
                ]);
                $id = (int)$this->db->lastInsertId();
            }

            return ['success' => true, 'id' => $id, 'message' => 'کد تخفیف با موفقیت ثبت گردید.'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'این کد تخفیف قبلاً ثبت شده است.'];
            }
            return ['success' => false, 'message' => 'خطا در ثبت کد تخفیف: ' . $e->getMessage()];
        }
    }

    /**
     * Admin: Toggle promo code active status
     */
    public function togglePromoStatus(int $id, int $isActive): bool {
        $stmt = $this->db->prepare("UPDATE promo_codes SET is_active = ? WHERE id = ?");
        return $stmt->execute([$isActive ? 1 : 0, $id]);
    }

    /**
     * Admin: Retrieve a single promo code by ID
     */
    public function getPromoCodeById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM promo_codes WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Admin: Immediately end / terminate an active promo code
     */
    public function endPromoCode(int $id): bool {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE promo_codes SET is_active = 0, expires_at = ? WHERE id = ?");
        return $stmt->execute([$now, $id]);
    }

    /**
     * Admin: Delete or safely expire promo code
     */
    public function deletePromoCode(int $id): array {
        $usagesStmt = $this->db->prepare("SELECT COUNT(*) FROM promo_code_usages WHERE promo_code_id = ?");
        $usagesStmt->execute([$id]);
        if ((int)$usagesStmt->fetchColumn() > 0) {
            $this->endPromoCode($id);
            return ['success' => true, 'message' => 'این کد دارای تراکنش ثبت‌شده است، بنابراین به جای حذف فیزیکی، فوراً پایان یافت و غیرفعال شد.'];
        }
        $stmt = $this->db->prepare("DELETE FROM promo_codes WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => 'کد تخفیف با موفقیت حذف گردید.'];
    }

    /**
     * Admin: Broadcast promotional notification (In-app + Optional SMS) to users
     */
    public function broadcastPromoNotification(int $promoId, string $title, string $message, string $audience = 'all', bool $sendSms = false): array {
        $promo = $this->getPromoCodeById($promoId);
        if (!$promo) {
            return ['success' => false, 'message' => 'کد تخفیف یافت نشد.'];
        }

        require_once __DIR__ . '/PushNotificationService.php';
        require_once __DIR__ . '/SmsService.php';
        $sms = new SmsService();
        $push = new PushNotificationService($this->db, $sms);

        $linkUrl = 'shop.php?coupon=' . urlencode($promo['code']);
        return $push->broadcastCampaign(
            $title,
            $message,
            'purchase_offer',
            $audience,
            $linkUrl,
            null,
            $sendSms
        );
    }
}
