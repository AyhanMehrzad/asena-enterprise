<?php
/**
 * ASENA Enterprise - Automated Google Web Search Indexing Service
 * Uses Google Indexing API v3 with Service Account JWT authentication.
 */

class GoogleIndexingService {
    private static ?string $cachedToken = null;
    private static int $tokenExpiresAt = 0;

    /**
     * Get or refresh Google OAuth2 Access Token using Service Account Key.
     */
    public static function getAccessToken(): ?string {
        if (self::$cachedToken && time() < (self::$tokenExpiresAt - 60)) {
            return self::$cachedToken;
        }

        $keyPaths = [
            __DIR__ . '/../config/google-indexing-key.json',
            dirname(__DIR__) . '/config/google-indexing-key.json',
            '/opt/lampp/htdocs/asena/asena-enterprise/config/google-indexing-key.json',
            '/opt/lampp/htdocs/asena/asena.company/config/google-indexing-key.json'
        ];

        $keyFile = null;
        foreach ($keyPaths as $p) {
            if (file_exists($p)) {
                $keyFile = $p;
                break;
            }
        }

        if (!$keyFile) {
            error_log("[GoogleIndexingService] Key file not found.");
            return null;
        }

        $keyData = json_decode(file_get_contents($keyFile), true);
        if (!$keyData || empty($keyData['client_email']) || empty($keyData['private_key'])) {
            error_log("[GoogleIndexingService] Invalid key file format.");
            return null;
        }

        $now = time();
        $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::base64UrlEncode(json_encode([
            'iss' => $keyData['client_email'],
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]));

        $payloadToSign = "$header.$claims";
        $signature = '';
        if (!openssl_sign($payloadToSign, $signature, $keyData['private_key'], OPENSSL_ALGO_SHA256)) {
            error_log("[GoogleIndexingService] Failed to sign JWT assertion.");
            return null;
        }

        $assertion = $payloadToSign . '.' . self::base64UrlEncode($signature);

        $response = self::executeHttp('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion
        ]);

        if (!$response || empty($response['access_token'])) {
            error_log("[GoogleIndexingService] Token request failed: " . json_encode($response));
            return null;
        }

        self::$cachedToken = $response['access_token'];
        self::$tokenExpiresAt = $now + ($response['expires_in'] ?? 3600);

        return self::$cachedToken;
    }

    /**
     * Notify Google of URL creation or update.
     *
     * @param string $url Full canonical URL (e.g., https://asena.company/shop.php)
     * @param string $type 'URL_UPDATED' or 'URL_DELETED'
     * @return array Result array with status and response payload
     */
    public static function publish(string $url, string $type = 'URL_UPDATED'): array {
        $token = self::getAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Unable to authenticate with Google Indexing API.'];
        }

        $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
        $body = [
            'url' => $url,
            'type' => $type
        ];

        $response = self::executeHttp($endpoint, json_encode($body), [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);

        $success = isset($response['urlNotificationMetadata']);
        self::logIndexAction($url, $type, $success, $response);

        return [
            'success' => $success,
            'url' => $url,
            'response' => $response
        ];
    }

    /**
     * Publish multiple URLs in sequence.
     */
    public static function publishBatch(array $urls, string $type = 'URL_UPDATED', int $delayMs = 200): array {
        $results = [];
        foreach ($urls as $url) {
            $results[$url] = self::publish($url, $type);
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }
        return $results;
    }

    /**
     * Helper to perform HTTP request with graceful proxy support if direct fails.
     */
    private static function executeHttp(string $url, $postData, array $headers = []): ?array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Try direct first
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // If network issue, retry via local proxy if available
        if ($res === false || $httpCode === 0) {
            curl_setopt($ch, CURLOPT_PROXY, 'http://127.0.0.1:10808');
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        curl_close($ch);

        return $res ? json_decode($res, true) : null;
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function logIndexAction(string $url, string $type, bool $success, ?array $response): void {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/indexing.log';
        $entry = sprintf(
            "[%s] [%s] %s -> %s | Response: %s\n",
            date('Y-m-d H:i:s'),
            $type,
            $url,
            $success ? 'SUCCESS' : 'FAILED',
            json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
