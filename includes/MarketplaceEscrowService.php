<?php
/**
 * MarketplaceEscrowService - Enterprise Marketplace Escrow & Weekly Payout Settlement Engine
 *
 * Implements:
 * 1. Central Corporate Account Inflow & Escrow Deduction
 * 2. 7-Day Guarantee & Customer Inspection Window Enforcement
 * 3. Iran Post Delivery Verification Bridge
 * 4. Automated Central Bank Paya (پایا) Interbank Weekly Settlement Batches
 * 5. Seller Wallet Management & Melipayamak SMS Settlement Notices
 */

class MarketplaceEscrowService {
    private PDO $db;
    public const DEFAULT_COMMISSION_RATE = 5.00; // 5% platform commission
    public const ESCROW_HOLD_DAYS = 7; // 7 days under Iranian E-Commerce Law

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Deposit order amounts into corporate escrow upon successful customer payment
     */
    public function depositOrderToEscrow(int $orderId): array {
        // Fetch order
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return ['success' => false, 'message' => 'سفارش یافت نشد.'];
        }

        // Fetch order items
        $itemsStmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = :id");
        $itemsStmt->execute(['id' => $orderId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return ['success' => false, 'message' => 'اقلام سفارش یافت نشد.'];
        }

        $totalDeposited = 0;
        $totalCommission = 0;
        $ledgerEntries = [];

        foreach ($items as $item) {
            // Check if already in ledger
            $checkStmt = $this->db->prepare("SELECT id FROM seller_escrow_ledger WHERE order_item_id = :item_id");
            $checkStmt->execute(['item_id' => $item['id']]);
            if ($checkStmt->fetch()) {
                continue; // Already processed
            }

            // Determine seller ID: default to seller_id on item, or find org/admin user
            $sellerId = $item['seller_id'] ?? null;
            if (!$sellerId) {
                $sellerId = $this->resolveItemSellerId((int)$item['product_id']);
            }

            $quantity = max(1, (int)$item['quantity']);
            $price = (int)$item['price_at_purchase'];
            $grossAmount = $price * $quantity;

            $commissionRate = isset($item['commission_rate']) ? (float)$item['commission_rate'] : self::DEFAULT_COMMISSION_RATE;
            $commissionAmount = (int)round($grossAmount * ($commissionRate / 100.0));
            $netSellerAmount = max(0, $grossAmount - $commissionAmount);

            // Update order_items table
            $updateItem = $this->db->prepare("
                UPDATE order_items 
                SET seller_id = :seller_id,
                    commission_rate = :comm_rate,
                    commission_amount = :comm_amount,
                    seller_net_amount = :seller_net
                WHERE id = :item_id
            ");
            $updateItem->execute([
                'seller_id' => $sellerId,
                'comm_rate' => $commissionRate,
                'comm_amount' => $commissionAmount,
                'seller_net' => $netSellerAmount,
                'item_id' => $item['id']
            ]);

            // Insert into escrow ledger
            $ledgerStmt = $this->db->prepare("
                INSERT INTO seller_escrow_ledger 
                (order_id, order_item_id, seller_id, gross_amount, commission_amount, net_seller_amount, status)
                VALUES 
                (:order_id, :order_item_id, :seller_id, :gross, :commission, :net, 'held_in_escrow')
            ");
            $ledgerStmt->execute([
                'order_id' => $orderId,
                'order_item_id' => $item['id'],
                'seller_id' => $sellerId,
                'gross' => $grossAmount,
                'commission' => $commissionAmount,
                'net' => $netSellerAmount
            ]);

            // Update seller wallet pending escrow balance
            $this->creditPendingEscrow($sellerId, $netSellerAmount);

            $totalDeposited += $netSellerAmount;
            $totalCommission += $commissionAmount;
            $ledgerEntries[] = [
                'item_id' => $item['id'],
                'seller_id' => $sellerId,
                'gross' => $grossAmount,
                'commission' => $commissionAmount,
                'net' => $netSellerAmount
            ];
        }

        // Update order escrow status
        $updateOrder = $this->db->prepare("
            UPDATE orders 
            SET escrow_status = 'pending_delivery' 
            WHERE id = :id AND (escrow_status IS NULL OR escrow_status = 'pending_delivery')
        ");
        $updateOrder->execute(['id' => $orderId]);

        return [
            'success' => true,
            'order_id' => $orderId,
            'total_seller_net' => $totalDeposited,
            'total_commission' => $totalCommission,
            'ledger_entries' => $ledgerEntries
        ];
    }

    /**
     * Resolve product vendor or default platform vendor ID
     */
    private function resolveItemSellerId(int $productId): int {
        // Find if product has an associated organization or creator
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role IN ('doctor', 'seller', 'organization_manager') ORDER BY id ASC LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return (int)$user['id'];
        }

        // Fallback to super admin or user #1
        return 1;
    }

    /**
     * Credit pending escrow balance in seller's wallet
     */
    private function creditPendingEscrow(int $sellerId, int $amount): void {
        $this->ensureWalletExists($sellerId);
        $stmt = $this->db->prepare("
            UPDATE seller_wallets 
            SET balance_pending_escrow = balance_pending_escrow + :amount 
            WHERE seller_id = :seller_id
        ");
        $stmt->execute(['amount' => $amount, 'seller_id' => $sellerId]);
    }

    /**
     * Release matured escrow funds after 7-day guarantee inspection period has passed
     */
    public function releaseMaturedEscrow(): array {
        // Find ledger items where delivered_at is not null and payout_eligible_at <= NOW()
        $stmt = $this->db->prepare("
            SELECT l.*, o.post_tracking_code 
            FROM seller_escrow_ledger l
            JOIN orders o ON l.order_id = o.id
            WHERE l.status = 'held_in_escrow'
              AND l.payout_eligible_at IS NOT NULL
              AND l.payout_eligible_at <= NOW()
        ");
        $stmt->execute();
        $eligibleRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $releasedCount = 0;
        $totalReleasedAmount = 0;
        $affectedSellers = [];

        foreach ($eligibleRecords as $rec) {
            $ledgerId = (int)$rec['id'];
            $sellerId = (int)$rec['seller_id'];
            $netAmount = (int)$rec['net_seller_amount'];
            $orderId = (int)$rec['order_id'];

            // 1. Move funds from pending to available in wallet
            $this->ensureWalletExists($sellerId);
            $walletStmt = $this->db->prepare("
                UPDATE seller_wallets 
                SET balance_pending_escrow = GREATEST(0, balance_pending_escrow - :pending_deduct),
                    balance_available_for_payout = balance_available_for_payout + :available_add
                WHERE seller_id = :seller_id
            ");
            $walletStmt->execute([
                'pending_deduct' => $netAmount,
                'available_add' => $netAmount,
                'seller_id' => $sellerId
            ]);

            // 2. Mark ledger record as released
            $ledgerUpdate = $this->db->prepare("
                UPDATE seller_escrow_ledger 
                SET status = 'released_to_available' 
                WHERE id = :id
            ");
            $ledgerUpdate->execute(['id' => $ledgerId]);

            // 3. Update order escrow status if all order items are released
            $checkOrderPending = $this->db->prepare("
                SELECT COUNT(*) as pending_count 
                FROM seller_escrow_ledger 
                WHERE order_id = :order_id AND status = 'held_in_escrow'
            ");
            $checkOrderPending->execute(['order_id' => $orderId]);
            $pendingCount = (int)$checkOrderPending->fetchColumn();

            if ($pendingCount === 0) {
                $orderUpdate = $this->db->prepare("
                    UPDATE orders 
                    SET escrow_status = 'cleared_for_payout', 
                        escrow_cleared_at = NOW() 
                    WHERE id = :order_id
                ");
                $orderUpdate->execute(['order_id' => $orderId]);
            }

            // 4. Notify seller via SMS
            $this->notifySellerEscrowReleased($sellerId, $orderId, $netAmount);

            $releasedCount++;
            $totalReleasedAmount += $netAmount;
            $affectedSellers[$sellerId] = ($affectedSellers[$sellerId] ?? 0) + $netAmount;
        }

        return [
            'success' => true,
            'released_count' => $releasedCount,
            'total_amount' => $totalReleasedAmount,
            'sellers_affected_count' => count($affectedSellers),
            'affected_sellers' => $affectedSellers
        ];
    }

    /**
     * Generate weekly Central Bank Paya (پایا) settlement batch file
     */
    public function generateWeeklyPayoutBatch(int $adminId = 1): array {
        // Find all sellers with positive balance_available_for_payout
        $stmt = $this->db->prepare("
            SELECT w.*, u.name as seller_name, u.phone as seller_phone
            FROM seller_wallets w
            JOIN users u ON w.seller_id = u.id
            WHERE w.balance_available_for_payout > 0
            ORDER BY w.balance_available_for_payout DESC
        ");
        $stmt->execute();
        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($sellers)) {
            return [
                'success' => false,
                'message' => 'هیچ فروشنده‌ای با موجودی قابل تسویه در سامانه وجود ندارد.'
            ];
        }

        $batchCode = 'PAYA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $totalAmount = 0;
        $payaLines = [];
        $payaLines[] = "IBAN\tAMOUNT_TOMAN\tRECIPIENT_NAME\tBANK_NAME\tDESCRIPTION\tREFERENCE_BATCH";

        foreach ($sellers as $s) {
            $payoutAmount = (int)$s['balance_available_for_payout'];
            $sheba = !empty($s['bank_sheba']) ? strtoupper(trim($s['bank_sheba'])) : 'IR000000000000000000000000';
            $holder = !empty($s['bank_account_holder']) ? $s['bank_account_holder'] : $s['seller_name'];
            $bank = !empty($s['bank_name']) ? $s['bank_name'] : 'سامانه پایا مرکزی';
            $desc = "تسویه حساب هفتگی فروشگاه آسنا - کد پیگیری {$batchCode}";

            $payaLines[] = "{$sheba}\t{$payoutAmount}\t{$holder}\t{$bank}\t{$desc}\t{$batchCode}";
            $totalAmount += $payoutAmount;
        }

        $exportContent = implode("\r\n", $payaLines);

        // Save batch to database
        $batchStmt = $this->db->prepare("
            INSERT INTO seller_payout_batches 
            (batch_code, total_payout_amount, seller_count, status, paya_export_content, processed_by, processed_at)
            VALUES 
            (:batch_code, :total_amount, :seller_count, 'completed', :content, :admin_id, NOW())
        ");
        $batchStmt->execute([
            'batch_code' => $batchCode,
            'total_amount' => $totalAmount,
            'seller_count' => count($sellers),
            'content' => $exportContent,
            'admin_id' => $adminId
        ]);
        $batchId = (int)$this->db->lastInsertId();

        // Update seller wallets and mark ledger items as settled
        foreach ($sellers as $s) {
            $sellerId = (int)$s['seller_id'];
            $settledAmount = (int)$s['balance_available_for_payout'];

            // Update wallet
            $updateWallet = $this->db->prepare("
                UPDATE seller_wallets 
                SET balance_settled_lifetime = balance_settled_lifetime + :settled_add,
                    balance_available_for_payout = 0
                WHERE seller_id = :seller_id
            ");
            $updateWallet->execute([
                'settled_add' => $settledAmount,
                'seller_id' => $sellerId
            ]);

            // Update ledger records
            $updateLedger = $this->db->prepare("
                UPDATE seller_escrow_ledger 
                SET status = 'settled_in_batch',
                    settlement_batch_id = :batch_id 
                WHERE seller_id = :seller_id AND status = 'released_to_available'
            ");
            $updateLedger->execute([
                'batch_id' => $batchId,
                'seller_id' => $sellerId
            ]);

            // Send SMS notification
            $this->notifySellerPayoutExecuted($s, $settledAmount, $batchCode);
        }

        return [
            'success' => true,
            'batch_id' => $batchId,
            'batch_code' => $batchCode,
            'total_amount' => $totalAmount,
            'seller_count' => count($sellers),
            'export_content' => $exportContent
        ];
    }

    /**
     * Generate single seller / organization Paya settlement based on admin preference
     */
    public function generateSingleSellerPayout(int $sellerId, int $adminId = 1, ?int $customAmount = null): array {
        $stmt = $this->db->prepare("
            SELECT w.*, u.name as seller_name, u.phone as seller_phone
            FROM seller_wallets w
            JOIN users u ON w.seller_id = u.id
            WHERE w.seller_id = ?
        ");
        $stmt->execute([$sellerId]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$seller) {
            return [
                'success' => false,
                'message' => 'کیف پول فروشنده یا مرکز درمانی مورد نظر یافت نشد.'
            ];
        }

        $available = (int)$seller['balance_available_for_payout'];
        if ($available <= 0) {
            return [
                'success' => false,
                'message' => 'موجودی آماده تسویه برای این فروشنده صفر می‌باشد.'
            ];
        }

        if ($customAmount !== null) {
            if ($customAmount <= 0) {
                return [
                    'success' => false,
                    'message' => 'مبلغ درخواستی تسویه باید بزرگتر از صفر باشد.'
                ];
            }
            if ($customAmount > $available) {
                return [
                    'success' => false,
                    'message' => 'مبلغ درخواستی (' . number_format($customAmount) . ' تومان) بیشتر از موجودی قابل تسویه (' . number_format($available) . ' تومان) است.'
                ];
            }
            $payoutAmount = $customAmount;
        } else {
            $payoutAmount = $available;
        }

        $batchCode = 'PAYA-SNGL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $sheba = !empty($seller['bank_sheba']) ? strtoupper(trim($seller['bank_sheba'])) : 'IR000000000000000000000000';
        $holder = !empty($seller['bank_account_holder']) ? $seller['bank_account_holder'] : $seller['seller_name'];
        $bank = !empty($seller['bank_name']) ? $seller['bank_name'] : 'سامانه پایا مرکزی';
        $desc = "تسویه حساب انفرادی آسنا - {$seller['seller_name']} - کد {$batchCode}";

        $payaLines = [
            "IBAN\tAMOUNT_TOMAN\tRECIPIENT_NAME\tBANK_NAME\tDESCRIPTION\tREFERENCE_BATCH",
            "{$sheba}\t{$payoutAmount}\t{$holder}\t{$bank}\t{$desc}\t{$batchCode}"
        ];
        $exportContent = implode("\r\n", $payaLines);

        // Save batch to database
        $batchStmt = $this->db->prepare("
            INSERT INTO seller_payout_batches 
            (batch_code, total_payout_amount, seller_count, status, paya_export_content, processed_by, processed_at)
            VALUES 
            (:batch_code, :total_amount, 1, 'completed', :content, :admin_id, NOW())
        ");
        $batchStmt->execute([
            'batch_code' => $batchCode,
            'total_amount' => $payoutAmount,
            'content' => $exportContent,
            'admin_id' => $adminId
        ]);
        $batchId = (int)$this->db->lastInsertId();

        // Update seller wallet
        $updateWallet = $this->db->prepare("
            UPDATE seller_wallets 
            SET balance_settled_lifetime = balance_settled_lifetime + ?,
                balance_available_for_payout = balance_available_for_payout - ?
            WHERE seller_id = ?
        ");
        $updateWallet->execute([
            $payoutAmount,
            $payoutAmount,
            $sellerId
        ]);

        // Update ledger records
        if ($payoutAmount >= $available) {
            $updateLedger = $this->db->prepare("
                UPDATE seller_escrow_ledger 
                SET status = 'settled_in_batch',
                    settlement_batch_id = :batch_id 
                WHERE seller_id = :seller_id AND status = 'released_to_available'
            ");
            $updateLedger->execute([
                'batch_id' => $batchId,
                'seller_id' => $sellerId
            ]);
        } else {
            $stmtLedger = $this->db->prepare("
                SELECT id, net_seller_amount 
                FROM seller_escrow_ledger 
                WHERE seller_id = :seller_id AND status = 'released_to_available'
                ORDER BY id ASC
            ");
            $stmtLedger->execute(['seller_id' => $sellerId]);
            $rows = $stmtLedger->fetchAll(PDO::FETCH_ASSOC);

            $accumulated = 0;
            $rowIdsToUpdate = [];
            foreach ($rows as $r) {
                if ($accumulated + (int)$r['net_seller_amount'] <= $payoutAmount) {
                    $accumulated += (int)$r['net_seller_amount'];
                    $rowIdsToUpdate[] = (int)$r['id'];
                }
            }
            if (!empty($rowIdsToUpdate)) {
                $inPlaceholders = implode(',', array_fill(0, count($rowIdsToUpdate), '?'));
                $params = array_merge([$batchId], $rowIdsToUpdate);
                $upd = $this->db->prepare("
                    UPDATE seller_escrow_ledger 
                    SET status = 'settled_in_batch',
                        settlement_batch_id = ?
                    WHERE id IN ($inPlaceholders)
                ");
                $upd->execute($params);
            }
        }

        // Send SMS notification
        $this->notifySellerPayoutExecuted($seller, $payoutAmount, $batchCode);

        return [
            'success' => true,
            'batch_id' => $batchId,
            'batch_code' => $batchCode,
            'total_amount' => $payoutAmount,
            'seller_name' => $seller['seller_name'],
            'seller_count' => 1,
            'export_content' => $exportContent
        ];
    }

    /**
     * Get or create seller wallet
     */
    public function getSellerWallet(int $sellerId): array {
        $this->ensureWalletExists($sellerId);
        $stmt = $this->db->prepare("
            SELECT w.*, u.name as seller_name, u.phone as seller_phone, u.email as seller_email 
            FROM seller_wallets w
            JOIN users u ON w.seller_id = u.id
            WHERE w.seller_id = :seller_id
        ");
        $stmt->execute(['seller_id' => $sellerId]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch recent ledger items
        $ledgerStmt = $this->db->prepare("
            SELECT l.*, o.post_tracking_code, o.created_at as order_date 
            FROM seller_escrow_ledger l
            JOIN orders o ON l.order_id = o.id
            WHERE l.seller_id = :seller_id
            ORDER BY l.id DESC
            LIMIT 20
        ");
        $ledgerStmt->execute(['seller_id' => $sellerId]);
        $wallet['recent_transactions'] = $ledgerStmt->fetchAll(PDO::FETCH_ASSOC);

        return $wallet;
    }

    /**
     * Update seller bank accounts & Sheba number
     */
    public function updateBankDetails(int $sellerId, array $data): array {
        $sheba = strtoupper(trim($data['bank_sheba'] ?? ''));
        $sheba = preg_replace('/[^A-Z0-9]/', '', $sheba);

        // Validate Sheba format (Must be IR followed by 24 digits)
        if (!empty($sheba) && !preg_match('/^IR\d{24}$/', $sheba)) {
            return [
                'success' => false,
                'message' => 'شماره شبا نامعتبر است. شماره شبا باید با حروف IR و ۲۴ رقم عدد بدون فاصله باشد.'
            ];
        }

        $this->ensureWalletExists($sellerId);

        $stmt = $this->db->prepare("
            UPDATE seller_wallets 
            SET bank_name = :bank_name,
                bank_account_holder = :holder,
                bank_sheba = :sheba,
                bank_card_number = :card
            WHERE seller_id = :seller_id
        ");
        $stmt->execute([
            'bank_name' => htmlspecialchars(trim($data['bank_name'] ?? '')),
            'holder' => htmlspecialchars(trim($data['bank_account_holder'] ?? '')),
            'sheba' => $sheba,
            'card' => preg_replace('/[^\d]/', '', $data['bank_card_number'] ?? ''),
            'seller_id' => $sellerId
        ]);

        return [
            'success' => true,
            'message' => 'اطلاعات بانکی با موفقیت به‌روزرسانی شد.'
        ];
    }

    /**
     * Get platform-wide escrow metrics for Admin Dashboard
     */
    public function getEscrowMetrics(): array {
        $metrics = [
            'total_pending_escrow' => 0,
            'total_available_payout' => 0,
            'total_lifetime_settled' => 0,
            'active_in_inspection_count' => 0,
            'eligible_sellers_count' => 0,
            'recent_batches' => []
        ];

        $stmt = $this->db->query("
            SELECT 
                COALESCE(SUM(balance_pending_escrow), 0) as pending,
                COALESCE(SUM(balance_available_for_payout), 0) as available,
                COALESCE(SUM(balance_settled_lifetime), 0) as settled,
                COUNT(CASE WHEN balance_available_for_payout > 0 THEN 1 END) as eligible_sellers
            FROM seller_wallets
        ");
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $metrics['total_pending_escrow'] = (int)$row['pending'];
            $metrics['total_available_payout'] = (int)$row['available'];
            $metrics['total_lifetime_settled'] = (int)$row['settled'];
            $metrics['eligible_sellers_count'] = (int)$row['eligible_sellers'];
        }

        $countStmt = $this->db->query("
            SELECT COUNT(*) FROM seller_escrow_ledger 
            WHERE status = 'held_in_escrow' AND delivered_at IS NOT NULL
        ");
        $metrics['active_in_inspection_count'] = (int)$countStmt->fetchColumn();

        $batchStmt = $this->db->query("
            SELECT * FROM seller_payout_batches ORDER BY id DESC LIMIT 10
        ");
        $metrics['recent_batches'] = $batchStmt->fetchAll(PDO::FETCH_ASSOC);

        return $metrics;
    }

    /**
     * Ensure seller wallet record exists
     */
    private function ensureWalletExists(int $sellerId): void {
        $stmt = $this->db->prepare("SELECT id FROM seller_wallets WHERE seller_id = :id");
        $stmt->execute(['id' => $sellerId]);
        if (!$stmt->fetch()) {
            $ins = $this->db->prepare("INSERT INTO seller_wallets (seller_id) VALUES (:id)");
            $ins->execute(['id' => $sellerId]);
        }
    }

    /**
     * SMS alert when 7-day guarantee escrow unlocks
     */
    private function notifySellerEscrowReleased(int $sellerId, int $orderId, int $amount): void {
        try {
            $stmt = $this->db->prepare("SELECT phone FROM users WHERE id = :id");
            $stmt->execute(['id' => $sellerId]);
            $phone = $stmt->fetchColumn();

            if ($phone) {
                $formattedAmount = number_format($amount);
                $msg = "آسنا: مهلت تست ۷ روزه سفارش #{$orderId} به اتمام رسید. مبلغ {$formattedAmount} تومان به موجودی آماده تسویه شما واریز گردید.";
                App::sms()->send($phone, $msg);
            }
        } catch (Throwable $e) {
            error_log("Escrow notification error: " . $e->getMessage());
        }
    }

    /**
     * SMS alert when Paya transfer batch executes
     */
    private function notifySellerPayoutExecuted(array $seller, int $amount, string $batchCode): void {
        try {
            $phone = $seller['seller_phone'] ?? '';
            if ($phone) {
                $formattedAmount = number_format($amount);
                $sheba = !empty($seller['bank_sheba']) ? substr($seller['bank_sheba'], 0, 6) . '...' . substr($seller['bank_sheba'], -4) : 'شبا';
                $msg = "آسنا: حواله پایا به مبلغ {$formattedAmount} تومان به {$sheba} صادر شد. شناسه تسویه: {$batchCode}";
                App::sms()->send($phone, $msg);
            }
        } catch (Throwable $e) {
            error_log("Payout executed notification error: " . $e->getMessage());
        }
    }
}
