<?php
/**
 * ASENA Payment Gateway — Legacy Bridge & Direct Official Gateway Adapter
 * All payments are routed directly to official Shaparak Gateways (Zibal / ZarinPal).
 * Mock / Sample payment simulators are permanently removed.
 */

require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/ZibalGateway.php';
Env::load();

if (!defined('ZARINPAL_MERCHANT_ID')) {
    define('ZARINPAL_MERCHANT_ID', getenv('ZARINPAL_MERCHANT_ID') ?: '');
}

class ZarinPalGateway
{
    private string $merchant_id;
    private string $request_url;
    private string $verify_url;
    private string $payment_base;
    private ZibalGateway $zibal;

    public function __construct()
    {
        $this->merchant_id = (string)ZARINPAL_MERCHANT_ID;
        $base = 'payment.zarinpal.com';
        $this->request_url  = "https://{$base}/pg/v4/payment/request.json";
        $this->verify_url   = "https://{$base}/pg/v4/payment/verify.json";
        $this->payment_base = "https://{$base}/pg/StartPay/";
        $this->zibal        = new ZibalGateway();
    }

    /**
     * Request payment token. If ZarinPal is not fully configured, seamlessly delegates to official Zibal IPG.
     */
    public function requestPayment(
        int    $amount_tomans,
        string $description,
        string $callback_url,
        array  $metadata = []
    ): array {
        // If ZarinPal merchant is not configured or empty, immediately use real Zibal IPG
        if (empty($this->merchant_id) || $this->merchant_id === '00000000-0000-0000-0000-000000000000') {
            return $this->zibal->requestPayment($amount_tomans, $description, $callback_url, $metadata);
        }

        // Real Production ZarinPal
        $payload = [
            'merchant_id'  => $this->merchant_id,
            'amount'       => $amount_tomans * 10, // Tomans → Rials
            'description'  => mb_substr($description, 0, 255),
            'callback_url' => $callback_url,
        ];

        if (!empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->post($this->request_url, $payload);

        if (($response['data']['code'] ?? null) === 100) {
            $authority = $response['data']['authority'];
            return [
                'success'     => true,
                'authority'   => $authority,
                'payment_url' => $this->payment_base . $authority,
            ];
        }

        // If ZarinPal fails, fallback directly to official real Zibal IPG
        $zibalRes = $this->zibal->requestPayment($amount_tomans, $description, $callback_url, $metadata);
        if (!empty($zibalRes['success'])) {
            return $zibalRes;
        }

        return [
            'success' => false,
            'error'   => $response['errors']['message']
                         ?? ($zibalRes['error'] ?? "کد خطا: " . ($response['data']['code'] ?? 'نامشخص')),
        ];
    }

    /**
     * Verify a returned payment authority.
     */
    public function verifyPayment(int $amount_tomans, string $authority): array
    {
        // If authority is numeric or trackId format, verify via Zibal
        if (is_numeric($authority) || strlen($authority) < 15) {
            return $this->zibal->verifyPayment($authority);
        }

        // Real Production Verification via ZarinPal
        $payload = [
            'merchant_id' => $this->merchant_id,
            'amount'      => $amount_tomans * 10,
            'authority'   => $authority,
        ];

        $response = $this->post($this->verify_url, $payload);
        $code     = $response['data']['code'] ?? null;

        if ($code === 100 || $code === 101) {
            return [
                'success'          => true,
                'ref_id'           => (string)($response['data']['ref_id'] ?? ''),
                'already_verified' => ($code === 101),
            ];
        }

        // Fallback check against Zibal
        $zibalVerify = $this->zibal->verifyPayment($authority);
        if (!empty($zibalVerify['success'])) {
            return $zibalVerify;
        }

        return [
            'success' => false,
            'error'   => $response['errors']['message']
                         ?? "پرداخت تأیید نشد (کد: {$code}).",
        ];
    }

    /** Internal HTTP POST with JSON payload. */
    private function post(string $url, array $data): array
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $ch   = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($json),
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);
        $error  = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || !$result) {
            error_log("ZarinPal cURL error: {$error}");
            return ['errors' => ['message' => "خطا در اتصال به درگاه پرداخت."]];
        }

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("ZarinPal invalid JSON (HTTP {$status}): {$result}");
            return ['errors' => ['message' => "پاسخ نامعتبر از درگاه."]];
        }

        return $decoded;
    }
}
