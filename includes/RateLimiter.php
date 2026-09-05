<?php
/**
 * ASENA Enterprise - High-Performance Sliding Window Rate Limiter
 * Version: 1.0.0
 */

class RateLimiter {
    private ?PDO $pdo;
    private string $storageDir;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
        $this->storageDir = sys_get_temp_dir() . '/asena_ratelimit';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
        $this->initSchema();
    }

    private function initSchema(): void {
        if ($this->pdo) {
            try {
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS security_rate_limits (
                        id BIGINT AUTO_INCREMENT PRIMARY KEY,
                        rate_key VARCHAR(128) NOT NULL,
                        timestamp INT UNSIGNED NOT NULL,
                        INDEX idx_rate_key_time (rate_key, timestamp)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
            } catch (Exception $e) {
                // Silently fallback to file-based if DB cannot create table
            }
        }
    }

    /**
     * Check if an action is permitted under the rate limit
     * @param string $key Unique identifier (e.g., "sms_otp:192.168.1.1" or "login:09146676978")
     * @param int $maxAttempts Maximum allowed hits in window
     * @param int $decaySeconds Window length in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
     */
    public function check(string $key, int $maxAttempts = 5, int $decaySeconds = 60): array {
        $now = time();
        $cutoff = $now - $decaySeconds;

        if ($this->pdo) {
            try {
                // 1. Purge entries older than decay window
                $del = $this->pdo->prepare("DELETE FROM security_rate_limits WHERE rate_key = ? AND timestamp < ?");
                $del->execute([$key, $cutoff]);

                // 2. Count current hits
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM security_rate_limits WHERE rate_key = ? AND timestamp >= ?");
                $stmt->execute([$key, $cutoff]);
                $currentHits = (int)$stmt->fetchColumn();

                if ($currentHits >= $maxAttempts) {
                    // Find oldest hit to compute retry-after
                    $oldestStmt = $this->pdo->prepare("SELECT MIN(timestamp) FROM security_rate_limits WHERE rate_key = ? AND timestamp >= ?");
                    $oldestStmt->execute([$key, $cutoff]);
                    $oldest = (int)$oldestStmt->fetchColumn();
                    $retryAfter = max(1, ($oldest + $decaySeconds) - $now);

                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'retry_after' => $retryAfter,
                        'error' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً {$retryAfter} ثانیه دیگر تلاش کنید."
                    ];
                }

                return [
                    'allowed' => true,
                    'remaining' => $maxAttempts - $currentHits,
                    'retry_after' => 0
                ];
            } catch (Exception $e) {
                // Fallback to file-based
            }
        }

        // File-based fallback
        return $this->checkFileBased($key, $maxAttempts, $decaySeconds, $now, $cutoff);
    }

    /**
     * Record a hit for the key
     */
    public function hit(string $key): void {
        $now = time();
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("INSERT INTO security_rate_limits (rate_key, timestamp) VALUES (?, ?)");
                $stmt->execute([$key, $now]);
                return;
            } catch (Exception $e) {}
        }

        // File fallback hit
        $file = $this->storageDir . '/' . md5($key) . '.log';
        @file_put_contents($file, $now . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Reset / clear rate limit for a key (e.g., upon successful login)
     */
    public function clear(string $key): void {
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM security_rate_limits WHERE rate_key = ?");
                $stmt->execute([$key]);
            } catch (Exception $e) {}
        }
        $file = $this->storageDir . '/' . md5($key) . '.log';
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    private function checkFileBased(string $key, int $maxAttempts, int $decaySeconds, int $now, int $cutoff): array {
        $file = $this->storageDir . '/' . md5($key) . '.log';
        if (!file_exists($file)) {
            return ['allowed' => true, 'remaining' => $maxAttempts, 'retry_after' => 0];
        }

        $timestamps = array_filter(array_map('intval', explode("\n", trim((string)@file_get_contents($file)))));
        $activeTimestamps = array_filter($timestamps, fn($t) => $t >= $cutoff);

        if (count($activeTimestamps) >= $maxAttempts) {
            $oldest = min($activeTimestamps);
            $retryAfter = max(1, ($oldest + $decaySeconds) - $now);
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $retryAfter,
                'error' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً {$retryAfter} ثانیه دیگر تلاش کنید."
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $maxAttempts - count($activeTimestamps),
            'retry_after' => 0
        ];
    }
}
