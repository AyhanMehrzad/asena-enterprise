<?php
/**
 * ASENA Enterprise - Security Audit & Threat Intelligence Service
 * Version: 1.0.0
 */

class SecurityAuditService {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
    }

    /**
     * Record a security audit event
     */
    public function logEvent(string $type, string $severity, ?int $userId, string $summary, array $context = []): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Unknown', 0, 250);
        $requestUri = substr($_SERVER['REQUEST_URI'] ?? '', 0, 250);
        $requestMethod = substr($_SERVER['REQUEST_METHOD'] ?? 'GET', 0, 10);

        if (!empty($context)) {
            $summary .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_audit_logs 
                (event_type, severity, user_id, ip_address, user_agent, request_uri, request_method, payload_summary, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([
                $type, $severity, $userId, $ip, $userAgent, $requestUri, $requestMethod, $summary
            ]);
        } catch (Throwable $e) {
            error_log("SecurityAuditService Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if an IP address is currently banned
     */
    public function isIpBanned(?string $ip = null): bool {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM security_banned_ips 
                WHERE ip_address = ? AND banned_until > NOW() 
                LIMIT 1
            ");
            $stmt->execute([$ip]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Ban an IP address for specified duration
     */
    public function banIp(string $ip, int $durationMinutes, string $reason, ?int $adminId = null): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO security_banned_ips (ip_address, reason, banned_until, banned_by, created_at)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    reason = VALUES(reason),
                    banned_until = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                    banned_by = VALUES(banned_by)
            ");
            $ok = $stmt->execute([$ip, $reason, $durationMinutes, $adminId, $durationMinutes]);
            if ($ok) {
                $this->logEvent('ip_banned', 'warning', $adminId, "IP {$ip} banned for {$durationMinutes} minutes. Reason: {$reason}");
            }
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Unban an IP address
     */
    public function unbanIp(string $ip): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM security_banned_ips WHERE ip_address = ?");
            $ok = $stmt->execute([$ip]);
            if ($ok) {
                $this->logEvent('ip_unbanned', 'info', null, "IP {$ip} was unbanned.");
            }
            return $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get recent audit events
     */
    public function getRecentEvents(int $limit = 100, ?string $severity = null): array {
        try {
            $sql = "SELECT * FROM security_audit_logs";
            $params = [];
            if (!empty($severity) && $severity !== 'all') {
                $sql .= " WHERE severity = ?";
                $params[] = $severity;
            }
            $sql .= " ORDER BY id DESC LIMIT " . (int)$limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get list of currently banned IPs
     */
    public function getBannedIps(): array {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM security_banned_ips 
                WHERE banned_until > NOW() 
                ORDER BY id DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Compute threat and incident metrics for admin console
     */
    public function getSecurityMetrics(): array {
        try {
            $total24h = (int)$this->pdo->query("
                SELECT COUNT(*) FROM security_audit_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")->fetchColumn();

            $critical24h = (int)$this->pdo->query("
                SELECT COUNT(*) FROM security_audit_logs 
                WHERE severity IN ('critical', 'emergency') AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")->fetchColumn();

            $wafBlocks24h = (int)$this->pdo->query("
                SELECT COUNT(*) FROM security_audit_logs 
                WHERE event_type = 'waf_blocked' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")->fetchColumn();

            $activeBans = (int)$this->pdo->query("
                SELECT COUNT(*) FROM security_banned_ips 
                WHERE banned_until > NOW()
            ")->fetchColumn();

            return [
                'total_events_24h' => $total24h,
                'critical_events_24h' => $critical24h,
                'waf_blocks_24h' => $wafBlocks24h,
                'active_bans' => $activeBans
            ];
        } catch (Throwable $e) {
            return [
                'total_events_24h' => 0,
                'critical_events_24h' => 0,
                'waf_blocks_24h' => 0,
                'active_bans' => 0
            ];
        }
    }
}
