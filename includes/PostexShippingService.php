<?php
/**
 * ASENA Enterprise - PostexShippingService
 *
 * Comprehensive Logistics & National Parcel Shipping Service for Iranian E-Commerce.
 * Integrates with Postex (پستکس - پلتفرم جامع خدمات پستی و لجستیک):
 *  - Real-time Carrier Shipping Rate Quotes (Iran Post Pishtaz, Chapar, Tipax, Postbar)
 *  - Automated Parcel Booking, Barcode Generation & Waybill Issuance
 *  - Live Parcel Tracking Events & Delivery Verification
 *  - Digital Postal Label Retrieval (PDF/Printable Stickers)
 *  - Wallet Balance & Account Verification
 *
 * Version: 1.0.0
 */

class PostexShippingService
{
    private ?PDO $db;
    private string $apiKey;
    private string $apiUrl;
    private bool $isSandbox;
    private int $defaultFromCity;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db;
        $this->apiKey = getenv('POSTEX_API_KEY') ?: 'postex_live_21de47511fpzXYeen3IGz0hKHGIOqtJAOk7lbf83';
        $this->apiUrl = rtrim(getenv('POSTEX_API_URL') ?: 'https://api.postex.ir', '/');
        $this->isSandbox = (getenv('POSTEX_SANDBOX') === 'true' || empty($this->apiKey));
        $this->defaultFromCity = (int)(getenv('POSTEX_DEFAULT_FROM_CITY') ?: 1); // 1 = Tehran
    }

    /**
     * Verify API connectivity and get current merchant account information
     */
    public function getAccountInfo(): array
    {
        if ($this->isSandbox) {
            return [
                'success' => true,
                'sandbox' => true,
                'first_name' => 'توسعه‌دهنده',
                'last_name' => 'آسنا',
                'mobile_no' => '09120000000',
                'is_verified' => true
            ];
        }

        $res = $this->request('GET', '/api/v1/user/whoami');
        if ($res['http_code'] === 200 && !empty($res['data'])) {
            return [
                'success' => true,
                'sandbox' => false,
                'username' => $res['data']['username'] ?? '',
                'first_name' => $res['data']['first_name'] ?? '',
                'last_name' => $res['data']['last_name'] ?? '',
                'mobile_no' => $res['data']['mobile_no'] ?? '',
                'national_code' => $res['data']['national_code'] ?? '',
                'is_active' => $res['data']['is_active'] ?? false,
                'is_verified' => $res['data']['is_verified'] ?? false
            ];
        }

        return [
            'success' => false,
            'message' => $res['data']['message'] ?? 'خطا در ارتباط با سرویس پستکس',
            'http_code' => $res['http_code']
        ];
    }

    /**
     * Retrieve merchant Postex wallet balance
     */
    public function getWalletBalance(): array
    {
        if ($this->isSandbox) {
            return [
                'success' => true,
                'balance_rial' => 100000000,
                'balance_toman' => 10000000,
                'frozen_rial' => 0
            ];
        }

        $res = $this->request('GET', '/api/v1/wallet/balance');
        if ($res['http_code'] === 200 && isset($res['data']['amount'])) {
            $amountRial = (int)$res['data']['amount'];
            return [
                'success' => true,
                'balance_rial' => $amountRial,
                'balance_toman' => (int)($amountRial / 10),
                'frozen_rial' => (int)($res['data']['frozenAmount'] ?? 0),
                'user_id' => $res['data']['userId'] ?? ''
            ];
        }

        return [
            'success' => false,
            'message' => $res['data']['message'] ?? 'خطا در دریافت موجودی کیف پول پستکس'
        ];
    }

    /**
     * Retrieve all available courier shipping methods
     */
    public function getAvailableShippingMethods(): array
    {
        $res = $this->request('GET', '/api/v1/common/shipping-methods');
        if ($res['http_code'] === 200 && !empty($res['data']['data'])) {
            return [
                'success' => true,
                'methods' => $res['data']['data']
            ];
        }

        return [
            'success' => false,
            'methods' => []
        ];
    }

    /**
     * Retrieve list of Iranian provinces from Postex locality service
     */
    public function getProvinces(): array
    {
        $res = $this->request('GET', '/api/v1/locality/provinces');
        if ($res['http_code'] === 200 && is_array($res['data'])) {
            return $res['data'];
        }
        return [];
    }

    /**
     * Retrieve list of cities in a province
     */
    public function getCities(int $provinceCode): array
    {
        $res = $this->request('GET', "/api/v1/locality/cities/{$provinceCode}");
        if ($res['http_code'] === 200 && is_array($res['data'])) {
            return $res['data'];
        }
        return [];
    }

    /**
     * Calculate live shipping quote for parcel checkout
     *
     * @param int $toCityCode Destination city code in Postex system
     * @param int $weightGrams Total parcel weight in grams (e.g. 500 = 0.5kg)
     * @param int $valueRials Declared item value in Rials for insurance
     * @param string $courierCode e.g. 'IR_POST', 'CHAPAR', 'POSTBAR'
     * @param string $serviceType e.g. 'EXPRESS', 'PRIORITY', 'CHAPAR'
     * @param int|null $fromCityCode Optional origin city (defaults to store warehouse, Tehran = 1)
     * @return array Calculated pricing and SLA details
     */
    public function calculateShippingQuote(
        int $toCityCode,
        int $weightGrams = 500,
        int $valueRials = 1000000,
        string $courierCode = 'IR_POST',
        string $serviceType = 'EXPRESS',
        ?int $fromCityCode = null,
        array $dimensions = []
    ): array {
        $fromCity = $fromCityCode ?: $this->defaultFromCity;
        $length = $dimensions['length'] ?? 20;
        $width = $dimensions['width'] ?? 15;
        $height = $dimensions['height'] ?? 10;
        $boxTypeId = $dimensions['box_type_id'] ?? 1;

        if ($this->isSandbox) {
            $simulatedToman = 45000 + (int)(ceil($weightGrams / 1000) * 12000);
            return [
                'success' => true,
                'sandbox' => true,
                'courier_code' => $courierCode,
                'service_type' => $serviceType,
                'courier_name' => ($courierCode === 'IR_POST') ? 'شرکت ملی پست' : 'چاپار اکسپرس',
                'service_name' => ($serviceType === 'EXPRESS') ? 'پست پیشتاز' : 'ارسال سریع',
                'price_rial' => $simulatedToman * 10,
                'price_toman' => $simulatedToman,
                'formatted_price' => number_format($simulatedToman) . ' تومان',
                'sla_days' => '۲ الی ۳ روز کاری',
                'logo' => 'assets/images/shipping/ir_post.png'
            ];
        }

        $payload = [
            'collection_type' => 'courier_drop_off',
            'from_city_code' => $fromCity,
            'courier' => [
                'courier_code' => $courierCode,
                'service_type' => $serviceType
            ],
            'value_added_service' => [
                'request_label' => false,
                'request_packaging' => false,
                'request_sms_notification' => true
            ],
            'parcels' => [
                [
                    'custom_parcel_id' => 'ASENA-' . time(),
                    'to_city_code' => $toCityCode,
                    'payment_type' => 'SENDER',
                    'parcel_properties' => [
                        'length' => (int)$length,
                        'width' => (int)$width,
                        'height' => (int)$height,
                        'total_weight' => max(100, (int)$weightGrams),
                        'total_value' => max(100000, (int)$valueRials),
                        'box_type_id' => (int)$boxTypeId
                    ]
                ]
            ]
        ];

        $res = $this->request('POST', '/api/v1/shipping/quotes', $payload);

        if ($res['http_code'] === 200 && !empty($res['data']['shipping_prices'][0]['service_price'][0])) {
            $service = $res['data']['shipping_prices'][0]['service_price'][0];
            $totalRials = (int)($service['totalPrice'] ?? $res['data']['total_cost'] ?? 0);
            $totalTomans = (int)round($totalRials / 10);

            return [
                'success' => true,
                'sandbox' => false,
                'courier_code' => $service['courierCode'] ?? $courierCode,
                'service_type' => $service['serviceType'] ?? $serviceType,
                'courier_name' => $service['courierName'] ?? 'شرکت ملی پست',
                'service_name' => $service['serviceName'] ?? 'پست پیشتاز',
                'price_rial' => $totalRials,
                'price_toman' => $totalTomans,
                'vat_rial' => (int)($service['vat'] ?? 0),
                'formatted_price' => number_format($totalTomans) . ' تومان',
                'sla_days' => $service['slaDays'] ?? '۲۴ الی ۴۸ ساعت کاری',
                'sla_hours' => $service['slaHours'] ?? 48,
                'logo' => $service['courierLogo'] ?? ''
            ];
        }

        return [
            'success' => false,
            'message' => $res['data']['message'] ?? 'خطا در محاسبه کرایه پستی پستکس',
            'price_toman' => 45000, // Safe fallback
            'formatted_price' => '۴۵,۰۰۰ تومان (پیش‌فرض)'
        ];
    }

    /**
     * Register and book a parcel shipment via Postex
     * Generates official parcel tracking code and postal barcode
     */
    public function createParcel(array $orderData): array
    {
        if ($this->isSandbox) {
            $dummyBarcode = '10000' . date('ymd') . rand(1000000000, 9999999999);
            return [
                'success' => true,
                'sandbox' => true,
                'parcel_no' => 'PTX-' . rand(100000, 999999),
                'tracking_code' => $dummyBarcode,
                'barcode' => $dummyBarcode,
                'status' => 'registered',
                'message' => 'مرسوله با موفقیت در حالت سندباکس ثبت شد.'
            ];
        }

        $res = $this->request('POST', '/api/v1/parcels', $orderData);
        if ($res['http_code'] === 200 || $res['http_code'] === 201) {
            return [
                'success' => true,
                'sandbox' => false,
                'data' => $res['data']
            ];
        }

        return [
            'success' => false,
            'message' => $res['data']['message'] ?? 'ثبت سفارش در سامانه پستکس با خطا مواجه شد.',
            'details' => $res['data'] ?? []
        ];
    }

    /**
     * Retrieve printable shipping label URL or base64 stream
     */
    public function getParcelLabel(string $parcelNo): array
    {
        $res = $this->request('GET', "/api/v1/parcels/{$parcelNo}/label");
        return [
            'success' => ($res['http_code'] === 200),
            'label_url' => "{$this->apiUrl}/api/v1/parcels/{$parcelNo}/label",
            'data' => $res['data'] ?? null
        ];
    }

    /**
     * Track live courier events for a parcel by Postex parcel number
     */
    public function trackParcel(string $parcelNo): array
    {
        $res = $this->request('GET', "/api/v1/tracking/events/{$parcelNo}");
        if ($res['http_code'] === 200) {
            return [
                'success' => true,
                'events' => $res['data'] ?? []
            ];
        }

        return [
            'success' => false,
            'events' => []
        ];
    }

    /**
     * Track parcel by courier code and tracking barcode (e.g. IR_POST, CHAPAR, POSTBAR)
     */
    public function trackCourierBarcode(string $trackingCode, string $courier = 'IR_POST'): array
    {
        $cleanCode = trim($trackingCode);
        $res = $this->request('GET', "/api/v1/tracking/events/{$courier}/" . urlencode($cleanCode));
        if ($res['http_code'] === 200 && is_array($res['data'])) {
            return [
                'success' => true,
                'events' => $res['data']
            ];
        }

        return [
            'success' => false,
            'events' => []
        ];
    }

    /**
     * Check if parcel barcode has been delivered to recipient via Postex
     */
    public function isDelivered(string $trackingCode, string $courier = 'IR_POST'): array
    {
        $track = $this->trackCourierBarcode($trackingCode, $courier);
        if (!$track['success'] || empty($track['events'])) {
            return ['is_delivered' => false, 'delivered_at' => null];
        }

        foreach ($track['events'] as $evt) {
            $desc = $evt['description'] ?? '';
            if (
                str_contains($desc, 'توزیع شد') ||
                str_contains($desc, 'تحویل به گیرنده') ||
                str_contains($desc, 'تحویل گیرنده شد') ||
                str_contains($desc, 'توزیع موفق') ||
                str_contains($desc, 'تحویل گردید')
            ) {
                $dateStr = $evt['event_date'] ?? date('Y-m-d');
                $timeStr = $evt['event_time'] ?? date('H:i:s');
                $deliveredAt = date('Y-m-d H:i:s', strtotime("{$dateStr} {$timeStr}"));

                return [
                    'is_delivered' => true,
                    'delivered_at' => $deliveredAt,
                    'description' => $desc
                ];
            }
        }

        return ['is_delivered' => false, 'delivered_at' => null];
    }

    /**
     * Automated Synchronization: Check in-transit shipments via Postex API,
     * confirm customer delivery, and unlock the 7-day guarantee escrow inspection window.
     */
    public function syncInTransitParcels(): array
    {
        if (!$this->db) {
            return [
                'checked_count' => 0,
                'delivered_count' => 0,
                'in_transit_count' => 0,
                'updated_orders' => []
            ];
        }

        $stmt = $this->db->prepare("
            SELECT id, user_id, post_tracking_code, total_amount, created_at 
            FROM orders 
            WHERE post_tracking_code IS NOT NULL 
              AND post_delivery_verified = 0 
              AND status IN ('shipped', 'processing')
            LIMIT 50
        ");
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $checkedCount = count($orders);
        $deliveredCount = 0;
        $updatedOrders = [];

        foreach ($orders as $order) {
            $trackingCode = $order['post_tracking_code'];
            $deliveryCheck = $this->isDelivered($trackingCode);

            // If sandbox or verified delivered:
            if ($deliveryCheck['is_delivered']) {
                $deliveredAt = $deliveryCheck['delivered_at'] ?: date('Y-m-d H:i:s');
                $payoutEligibleAt = date('Y-m-d H:i:s', strtotime($deliveredAt . ' +7 days'));

                // Update order status to delivered with active inspection window
                $updateOrder = $this->db->prepare("
                    UPDATE orders 
                    SET status = 'delivered',
                        delivered_at = :delivered_at,
                        post_delivery_verified = 1,
                        escrow_status = 'delivered_in_inspection'
                    WHERE id = :order_id
                ");
                $updateOrder->execute([
                    'delivered_at' => $deliveredAt,
                    'order_id' => $order['id']
                ]);

                // Update escrow ledger records for this order
                $updateLedger = $this->db->prepare("
                    UPDATE seller_escrow_ledger 
                    SET delivered_at = :delivered_at,
                        payout_eligible_at = :payout_eligible_at
                    WHERE order_id = :order_id AND status = 'held_in_escrow'
                ");
                $updateLedger->execute([
                    'delivered_at' => $deliveredAt,
                    'payout_eligible_at' => $payoutEligibleAt,
                    'order_id' => $order['id']
                ]);

                $deliveredCount++;
                $updatedOrders[] = [
                    'order_id' => $order['id'],
                    'post_tracking_code' => $trackingCode,
                    'delivered_at' => $deliveredAt,
                    'payout_eligible_at' => $payoutEligibleAt
                ];
            }
        }

        return [
            'checked_count' => $checkedCount,
            'delivered_count' => $deliveredCount,
            'in_transit_count' => max(0, $checkedCount - $deliveredCount),
            'updated_orders' => $updatedOrders
        ];
    }

    /**
     * Automated Periodic Sync Runner: Checks in-transit parcels if interval has elapsed.
     * Uses a lightweight timestamp check to ensure zero overhead on page loads.
     */
    public function autoSyncIfDue(int $intervalSeconds = 900): array
    {
        $flagFile = sys_get_temp_dir() . '/asena_last_postex_sync.txt';
        $now = time();
        if (file_exists($flagFile)) {
            $lastSync = (int)@file_get_contents($flagFile);
            if (($now - $lastSync) < $intervalSeconds) {
                return [
                    'executed' => false,
                    'reason' => 'Throttled (interval not elapsed)',
                    'seconds_remaining' => $intervalSeconds - ($now - $lastSync)
                ];
            }
        }

        @file_put_contents($flagFile, (string)$now);
        $res = $this->syncInTransitParcels();
        $res['executed'] = true;
        return $res;
    }


    /**
     * Low-level HTTP executor using cURL with Postex authentication
     */
    private function request(string $method, string $endpoint, ?array $body = null): array
    {
        $url = $this->apiUrl . $endpoint;
        $ch = curl_init($url);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-API-KEY: ' . $this->apiKey,
            'User-Agent: ASENA-Enterprise-Logistics/1.0'
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $decoded = null;
        if ($response) {
            $decoded = json_decode($response, true);
        }

        return [
            'http_code' => $httpCode,
            'data' => $decoded,
            'raw' => $response,
            'error' => $curlError
        ];
    }
}
