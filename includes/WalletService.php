<?php
/**
 * ASENA Enterprise - WalletService
 *
 * Closed-Loop In-App Wallet Architecture
 * - Strict In-App Refund Policy (Never refunded to external bank accounts/Sheba; refunded 100% to wallet)
 * - Atomic balance mutations with ledger logging
 * - Zibal IPG Top-up Integration
 * - Purchase deduction & escrow interoperability
 *
 * Version: 1.0.0
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/ZibalGateway.php';

class WalletService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
    }

    /**
     * Get current wallet balance of user in Tomans
     */
    public function getBalance(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int)$val : 0;
    }

    /**
     * Check if user has sufficient funds for purchase
     */
    public function canPay(int $userId, int $amountToman): bool
    {
        if ($amountToman <= 0) {
            return true;
        }
        return $this->getBalance($userId) >= $amountToman;
    }

    /**
     * Retrieve recent wallet transactions
     */
    public function getTransactions(int $userId, int $limit = 50): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM wallet_transactions 
                WHERE user_id = ? 
                ORDER BY id DESC 
                LIMIT " . (int)$limit . "
            ");
            $stmt->execute([$userId]);
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($res)) {
                return $res;
            }
        } catch (Throwable $e) {}

        // Fallback to wallet_id via user_wallets
        try {
            $wStmt = $this->pdo->prepare("SELECT id FROM user_wallets WHERE user_id = ?");
            $wStmt->execute([$userId]);
            $wId = $wStmt->fetchColumn();
            if ($wId) {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM wallet_transactions 
                    WHERE wallet_id = ? 
                    ORDER BY id DESC 
                    LIMIT " . (int)$limit . "
                ");
                $stmt->execute([$wId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {}

        return [];
    }

    /**
     * Internal helper to insert transaction log safely across schema variants
     */
    private function logTransaction(
        int $userId,
        string $type,
        int $amountToman,
        int $balanceBefore,
        int $balanceAfter,
        string $referenceModule,
        ?string $referenceId,
        string $description
    ): void {
        $walletId = 0;
        try {
            $stmtW = $this->pdo->prepare("SELECT id FROM user_wallets WHERE user_id = ? LIMIT 1");
            $stmtW->execute([$userId]);
            $walletId = (int)$stmtW->fetchColumn();
            if (!$walletId) {
                $insW = $this->pdo->prepare("INSERT INTO user_wallets (user_id, balance, currency, created_at) VALUES (?, ?, 'IRT', ?)");
                $insW->execute([$userId, $balanceAfter, date('Y-m-d H:i:s')]);
                $walletId = (int)$this->pdo->lastInsertId();
            } else {
                $this->pdo->prepare("UPDATE user_wallets SET balance = ? WHERE id = ?")->execute([$balanceAfter, $walletId]);
            }
        } catch (Throwable $ignore) {}

        static $cols = null;
        if ($cols === null) {
            $cols = [];
            try {
                $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                if ($driver === 'sqlite') {
                    $q = $this->pdo->query("PRAGMA table_info(wallet_transactions)");
                    $cols = array_column($q->fetchAll(PDO::FETCH_ASSOC), 'name');
                } else {
                    $q = $this->pdo->query("SHOW COLUMNS FROM wallet_transactions");
                    $cols = array_column($q->fetchAll(PDO::FETCH_ASSOC), 'Field');
                }
            } catch (Throwable $e) {}
        }

        $fields = [];
        $placeholders = [];
        $params = [];

        if (in_array('user_id', $cols)) { $fields[] = 'user_id'; $placeholders[] = '?'; $params[] = $userId; }
        if (in_array('wallet_id', $cols)) { $fields[] = 'wallet_id'; $placeholders[] = '?'; $params[] = $walletId ?: $userId; }
        if (in_array('transaction_type', $cols)) { $fields[] = 'transaction_type'; $placeholders[] = '?'; $params[] = $type; }
        if (in_array('type', $cols)) { $fields[] = 'type'; $placeholders[] = '?'; $params[] = $type; }
        if (in_array('amount', $cols)) { $fields[] = 'amount'; $placeholders[] = '?'; $params[] = $amountToman; }
        if (in_array('balance_before', $cols)) { $fields[] = 'balance_before'; $placeholders[] = '?'; $params[] = $balanceBefore; }
        if (in_array('balance_after', $cols)) { $fields[] = 'balance_after'; $placeholders[] = '?'; $params[] = $balanceAfter; }
        if (in_array('reference_module', $cols)) { $fields[] = 'reference_module'; $placeholders[] = '?'; $params[] = $referenceModule; }
        if (in_array('reference_id', $cols)) { $fields[] = 'reference_id'; $placeholders[] = '?'; $params[] = $referenceId; }
        if (in_array('description', $cols)) { $fields[] = 'description'; $placeholders[] = '?'; $params[] = $description; }
        if (in_array('created_at', $cols)) { $fields[] = 'created_at'; $placeholders[] = '?'; $params[] = date('Y-m-d H:i:s'); }

        if (!empty($fields)) {
            $sql = "INSERT INTO wallet_transactions (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $this->pdo->prepare($sql)->execute($params);
        }
    }

    /**
     * Deposit funds into wallet (via topup, bonus, admin credit)
     */
    public function deposit(
        int $userId,
        int $amountToman,
        string $referenceModule,
        ?string $referenceId,
        string $description
    ): array {
        if ($amountToman <= 0) {
            return ['success' => false, 'message' => 'مبلغ افزایش موجودی نامعتبر است.'];
        }

        $isTransStartedLocally = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $isTransStartedLocally = true;
        }

        try {
            $userStmt = $this->pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $currentBalance = $userStmt->fetchColumn();

            if ($currentBalance === false) {
                if ($isTransStartedLocally) $this->pdo->rollBack();
                return ['success' => false, 'message' => 'کاربر یافت نشد.'];
            }

            $balanceBefore = (int)$currentBalance;
            $balanceAfter = $balanceBefore + $amountToman;

            $updStmt = $this->pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $updStmt->execute([$balanceAfter, $userId]);

            $this->logTransaction(
                $userId,
                'deposit',
                $amountToman,
                $balanceBefore,
                $balanceAfter,
                $referenceModule,
                $referenceId,
                $description
            );

            if ($isTransStartedLocally) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'message' => 'موجودی کیف‌پول با موفقیت افزایش یافت.',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'amount' => $amountToman
            ];
        } catch (Throwable $e) {
            if ($isTransStartedLocally && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'خطا در افزایش موجودی کیف‌پول: ' . $e->getMessage()];
        }
    }

    /**
     * Deduct funds from wallet for order/booking/subscription payment
     */
    public function deduct(
        int $userId,
        int $amountToman,
        string $referenceModule,
        ?string $referenceId,
        string $description
    ): array {
        if ($amountToman <= 0) {
            return ['success' => false, 'message' => 'مبلغ کسر نامعتبر است.'];
        }

        $isTransStartedLocally = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $isTransStartedLocally = true;
        }

        try {
            $userStmt = $this->pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $currentBalance = $userStmt->fetchColumn();

            if ($currentBalance === false) {
                if ($isTransStartedLocally) $this->pdo->rollBack();
                return ['success' => false, 'message' => 'کاربر یافت نشد.'];
            }

            $balanceBefore = (int)$currentBalance;
            if ($balanceBefore < $amountToman) {
                if ($isTransStartedLocally) $this->pdo->rollBack();
                return [
                    'success' => false, 
                    'message' => 'موجودی کیف‌پول کافی نیست. لطفاً کیف‌پول خود را شارژ فرمایید.',
                    'current_balance' => $balanceBefore,
                    'required_amount' => $amountToman
                ];
            }

            $balanceAfter = $balanceBefore - $amountToman;

            $updStmt = $this->pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $updStmt->execute([$balanceAfter, $userId]);

            $this->logTransaction(
                $userId,
                'purchase',
                $amountToman,
                $balanceBefore,
                $balanceAfter,
                $referenceModule,
                $referenceId,
                $description
            );

            if ($isTransStartedLocally) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'message' => 'کسر از کیف‌پول با موفقیت انجام شد.',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'amount' => $amountToman
            ];
        } catch (Throwable $e) {
            if ($isTransStartedLocally && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'خطا در کسر از کیف‌پول: ' . $e->getMessage()];
        }
    }

    /**
     * ASENA Golden Refund Rule:
     * 100% of the cancelled service/order amount is instantly returned to user's In-App Wallet.
     * Never returned to external Sheba/bank accounts.
     */
    public function refund(
        int $userId,
        int $amountToman,
        string $referenceModule,
        ?string $referenceId,
        string $description
    ): array {
        if ($amountToman <= 0) {
            return ['success' => true, 'message' => 'مبلغ استرداد صفر است (سفارش رایگان یا پرداخت نشده).'];
        }

        $isTransStartedLocally = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $isTransStartedLocally = true;
        }

        try {
            $userStmt = $this->pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $currentBalance = $userStmt->fetchColumn();

            if ($currentBalance === false) {
                if ($isTransStartedLocally) $this->pdo->rollBack();
                return ['success' => false, 'message' => 'کاربر یافت نشد.'];
            }

            $balanceBefore = (int)$currentBalance;
            $balanceAfter = $balanceBefore + $amountToman;

            $updStmt = $this->pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $updStmt->execute([$balanceAfter, $userId]);

            $this->logTransaction(
                $userId,
                'refund',
                $amountToman,
                $balanceBefore,
                $balanceAfter,
                $referenceModule,
                $referenceId,
                $description
            );

            if ($isTransStartedLocally) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'message' => "مبلغ " . number_format($amountToman) . " تومان بلافاصله به کیف‌پول شما واریز گردید.",
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'amount' => $amountToman
            ];
        } catch (Throwable $e) {
            if ($isTransStartedLocally && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'خطا در ثبت استرداد وجه به کیف‌پول: ' . $e->getMessage()];
        }
    }

    /**
     * Start Zibal payment to charge in-app wallet
     */
    public function initiateTopup(int $userId, int $amountToman, string $callbackUrl): array
    {
        if ($amountToman < 1000) {
            return ['success' => false, 'message' => 'حداقل مبلغ شارژ کیف‌پول ۱,۰۰۰ تومان می‌باشد.'];
        }

        $gateway = new ZibalGateway(null, null, $this->pdo);
        $desc = "شارژ کیف‌پول درون‌برنامه‌ای آسنا کاربر کد {$userId}";

        $res = $gateway->requestPayment($amountToman, $desc, $callbackUrl, [
            'user_id' => $userId,
            'type' => 'wallet_topup'
        ]);

        if (!empty($res['success']) && !empty($res['trackId'])) {
            return [
                'success' => true,
                'trackId' => $res['trackId'],
                'paymentUrl' => $res['paymentUrl'] ?? ('https://gateway.zibal.ir/start/' . $res['trackId'])
            ];
        }

        return [
            'success' => false,
            'message' => $res['error'] ?? 'خطا در برقراری ارتباط با درگاه پرداخت زیبال.'
        ];
    }

    /**
     * Verify Zibal payment callback for wallet topup
     */
    public function verifyTopup(int $userId, string $trackId): array
    {
        $gateway = new ZibalGateway(null, null, $this->pdo);
        $res = $gateway->verifyPayment($trackId);

        if (!empty($res['success'])) {
            $amountToman = (int)($res['amountTomans'] ?? ($res['amount'] / 10));
            $refNumber = (string)($res['refNumber'] ?? $trackId);

            // Check if already credited (idempotency check)
            $chk = $this->pdo->prepare("
                SELECT id FROM wallet_transactions 
                WHERE reference_module = 'zibal_topup' AND reference_id = ?
            ");
            $chk->execute([$trackId]);
            if ($chk->fetch()) {
                return [
                    'success' => true,
                    'already_processed' => true,
                    'message' => 'این تراکنش قبلاً تأیید و به کیف‌پول واریز شده است.',
                    'amount' => $amountToman,
                    'refNumber' => $refNumber
                ];
            }

            // Credit wallet
            $depositRes = $this->deposit(
                $userId,
                $amountToman,
                'zibal_topup',
                $trackId,
                "شارژ اینترنتی کیف‌پول از درگاه زیبال (کد پیگیری: {$refNumber})"
            );

            if ($depositRes['success']) {
                return [
                    'success' => true,
                    'amount' => $amountToman,
                    'refNumber' => $refNumber,
                    'new_balance' => $depositRes['balance_after'],
                    'message' => "شارژ کیف‌پول به مبلغ " . number_format($amountToman) . " تومان با موفقیت انجام شد."
                ];
            }

            return $depositRes;
        }

        return [
            'success' => false,
            'message' => $res['error'] ?? 'پرداخت درگاه زیبال تأیید نشد یا توسط کاربر لغو گردید.'
        ];
    }
}
