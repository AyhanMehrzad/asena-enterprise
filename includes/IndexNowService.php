<?php
/**
 * ASENA Enterprise - IndexNow Protocol Service
 * Instantly notifies Microsoft Bing, Yandex, Seznam, Naver & AI Search engines of URL changes.
 */

class IndexNowService {
    private const KEY = '4a8f921e5c0840b59f3d9b62a71d87e2';
    private const HOST = 'asena.company';
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    /**
     * Submit a list of URLs to the IndexNow protocol.
     *
     * @param array $urls List of full canonical URLs
     * @return array Status and HTTP code
     */
    public static function submit(array $urls): array {
        if (empty($urls)) {
            return ['success' => false, 'error' => 'No URLs provided.'];
        }

        $urls = array_values(array_unique($urls));
        $payload = [
            'host' => self::HOST,
            'key' => self::KEY,
            'keyLocation' => 'https://' . self::HOST . '/' . self::KEY . '.txt',
            'urlList' => $urls
        ];

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8'
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Fallback through proxy if network issue
        if ($response === false || $httpCode === 0) {
            curl_setopt($ch, CURLOPT_PROXY, 'http://127.0.0.1:10808');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        curl_close($ch);

        $success = in_array($httpCode, [200, 202]);
        self::logIndexNow($urls, $success, $httpCode, $response);

        return [
            'success' => $success,
            'http_code' => $httpCode,
            'count' => count($urls),
            'response' => $response
        ];
    }

    /**
     * Submit a single URL.
     */
    public static function submitUrl(string $url): array {
        return self::submit([$url]);
    }

    private static function logIndexNow(array $urls, bool $success, int $httpCode, $response): void {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/indexnow.log';
        $entry = sprintf(
            "[%s] IndexNow %d URLs -> HTTP %d (%s) | Res: %s\n",
            date('Y-m-d H:i:s'),
            count($urls),
            $httpCode,
            $success ? 'SUCCESS' : 'FAILED',
            substr(trim((string)$response), 0, 200)
        );
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
