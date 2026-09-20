<?php
/**
 * ASENA Enterprise - Map.ir Integration & Postal Code Intelligence Service
 * 
 * Provides:
 * 1. Map.ir raster tile layer endpoints with authorized JWT API key.
 * 2. High-precision Persian reverse geocoding via Map.ir API.
 * 3. Strict algorithmic Iranian postal code validation against fake/dummy codes.
 * 4. Postal code to location lookup (province, county, city, coordinates).
 */

class MapService {
    public const API_KEY = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjEzN2ZjZTM3OTIzNzQzYzY3ZmFlNWRhY2JmY2FlNjFkNDE5MTU3NmYwNWYxNTY1NWZhOGI4YTBhOTExY2U4ZmNhNDEyMDc4OGQ4NmFjMjAxIn0.eyJhdWQiOiI0NDEzNCIsImp0aSI6IjEzN2ZjZTM3OTIzNzQzYzY3ZmFlNWRhY2JmY2FlNjFkNDE5MTU3NmYwNWYxNTY1NWZhOGI4YTBhOTExY2U4ZmNhNDEyMDc4OGQ4NmFjMjAxIiwiaWF0IjoxNzg5OTMwMzI0LCJuYmYiOjE3ODk5MzAzMjQsImV4cCI6MTc5MjYxMjMyNCwic3ViIjoiIiwic2NvcGVzIjpbImJhc2ljIl19.C7aCaNkgwTLbgsk_p0zipi7Zfz4sR8xp653J4rreLzAJ0rGjYJFhyqchNJHpnzHaQoYA2D0SdjQvVhv4TCk-BnWkkki9O1gJxf2wTVdyuIMYmaB1KgYARP33iyAxSvaT-mjXaNJRtkBJ4t4ghhBCE1uUHjoaX_EPJMzac3EjaZsl6QIquhlvHkBQOC4RHvTSh-7Ua6WpObSWlDl-LT0L_7_twpTH5oH3qCE1eFVeo3k7FafvCVXshBPvy6CXhjNZP4KFMIEudhhxAJLFFQ7k6SCRQTwOhb1g95BSrXNKR-k-yxwtbFGiCG02Laqzyfttskn-xC76uD2JRGUlc4OosQ';

    public const TILE_URL = 'https://map.ir/shiveh/xyz/1.0.0/Shiveh:Shiveh@EPSG:3857@png/{z}/{x}/{y}.png?x-api-key=' . self::API_KEY;

    public const ATTRIBUTION = '© <a href="https://map.ir" target="_blank" rel="noopener noreferrer" class="hover:underline text-primary">نقشه مپ (Map.ir)</a>';

    /**
     * Iranian Postal Prefix Directory (2-digit and 3-digit prefixes mapped to Province & City/Coordinates)
     */
    private static array $postalDirectory = [
        // Tehran (11 to 19)
        '11' => ['province' => 'تهران', 'city' => 'تهران (منطقه مرکزی و بازار)', 'lat' => 35.6833, 'lng' => 51.4180],
        '13' => ['province' => 'تهران', 'city' => 'تهران (منطقه جنوب و جنوب‌غرب)', 'lat' => 35.6500, 'lng' => 51.3500],
        '14' => ['province' => 'تهران', 'city' => 'تهران (منطقه غرب و شمال‌غرب)', 'lat' => 35.7400, 'lng' => 51.3300],
        '15' => ['province' => 'تهران', 'city' => 'تهران (منطقه مطهری و عباس‌آباد)', 'lat' => 35.7250, 'lng' => 51.4250],
        '16' => ['province' => 'تهران', 'city' => 'تهران (منطقه شرق و رسالت)', 'lat' => 35.7350, 'lng' => 51.5100],
        '17' => ['province' => 'تهران', 'city' => 'تهران (منطقه جنوب‌شرق و پیروزی)', 'lat' => 35.6900, 'lng' => 51.4700],
        '18' => ['province' => 'تهران', 'city' => 'تهران (شهر ری و منطقه جنوبی)', 'lat' => 35.5900, 'lng' => 51.4300],
        '19' => ['province' => 'تهران', 'city' => 'تهران (منطقه شمیرانات و شمال)', 'lat' => 35.8050, 'lng' => 51.4300],
        '1'  => ['province' => 'تهران', 'city' => 'تهران', 'lat' => 35.6892, 'lng' => 51.3890],

        // Alborz (31)
        '31' => ['province' => 'البرز', 'city' => 'کرج', 'lat' => 35.8400, 'lng' => 50.9391],

        // Qazvin (34, 33)
        '34' => ['province' => 'قزوین', 'city' => 'قزوین', 'lat' => 36.2688, 'lng' => 50.0041],

        // Zanjan (45)
        '45' => ['province' => 'زنجان', 'city' => 'زنجان', 'lat' => 36.6736, 'lng' => 48.4787],

        // Semnan (35)
        '35' => ['province' => 'سمنان', 'city' => 'سمنان', 'lat' => 35.5769, 'lng' => 53.3971],

        // Qom (37)
        '37' => ['province' => 'قم', 'city' => 'قم', 'lat' => 34.6399, 'lng' => 50.8759],

        // Markazi (38)
        '38' => ['province' => 'مرکزی', 'city' => 'اراک', 'lat' => 34.0954, 'lng' => 49.7013],

        // Hamedan (65)
        '65' => ['province' => 'همدان', 'city' => 'همدان', 'lat' => 34.7989, 'lng' => 48.5150],

        // Gilan (41, 42, 43)
        '41' => ['province' => 'گیلان', 'city' => 'رشت', 'lat' => 37.2808, 'lng' => 49.5832],
        '42' => ['province' => 'گیلان', 'city' => 'لاهیجان / لنگرود', 'lat' => 37.2070, 'lng' => 50.0030],
        '43' => ['province' => 'گیلان', 'city' => 'بندر انزلی', 'lat' => 37.4674, 'lng' => 49.4628],

        // Ardabil (56)
        '56' => ['province' => 'اردبیل', 'city' => 'اردبیل', 'lat' => 38.2498, 'lng' => 48.2933],

        // Mazandaran (46, 47, 48)
        '46' => ['province' => 'مازندران', 'city' => 'آمل / بابل', 'lat' => 36.4676, 'lng' => 52.3507],
        '47' => ['province' => 'مازندران', 'city' => 'قائم‌شهر / سوادکوه', 'lat' => 36.4633, 'lng' => 52.8601],
        '48' => ['province' => 'مازندران', 'city' => 'ساری', 'lat' => 36.5633, 'lng' => 53.0601],

        // Golestan (49)
        '49' => ['province' => 'گلستان', 'city' => 'گرگان', 'lat' => 36.8427, 'lng' => 54.4439],

        // East Azerbaijan (51 to 55)
        '51' => ['province' => 'آذربایجان شرقی', 'city' => 'تبریز', 'lat' => 38.0800, 'lng' => 46.2919],
        '53' => ['province' => 'آذربایجان شرقی', 'city' => 'مراغه / بناب', 'lat' => 37.3912, 'lng' => 46.2393],
        '54' => ['province' => 'آذربایجان شرقی', 'city' => 'مرند / شبستر', 'lat' => 38.4328, 'lng' => 45.7749],
        '55' => ['province' => 'آذربایجان شرقی', 'city' => 'میانه / سراب', 'lat' => 37.4208, 'lng' => 47.7153],

        // West Azerbaijan (57, 58, 59)
        '57' => ['province' => 'آذربایجان غربی', 'city' => 'ارومیه', 'lat' => 37.5527, 'lng' => 45.0761],
        '58' => ['province' => 'آذربایجان غربی', 'city' => 'خوی / سلماس', 'lat' => 38.5503, 'lng' => 44.9531],
        '59' => ['province' => 'آذربایجان غربی', 'city' => 'مهاباد / میاندوآب', 'lat' => 36.7631, 'lng' => 45.7222],

        // Khuzestan (61, 63, 64)
        '61' => ['province' => 'خوزستان', 'city' => 'اهواز', 'lat' => 31.3183, 'lng' => 48.6706],
        '63' => ['province' => 'خوزستان', 'city' => 'آبادان / خرمشهر', 'lat' => 30.3392, 'lng' => 48.3043],
        '64' => ['province' => 'خوزستان', 'city' => 'دزفول / اندیمشک', 'lat' => 32.3811, 'lng' => 48.4058],

        // Ilam (69)
        '69' => ['province' => 'ایلام', 'city' => 'ایلام', 'lat' => 33.6374, 'lng' => 46.4227],

        // Kurdistan (66)
        '66' => ['province' => 'کردستان', 'city' => 'سنندج', 'lat' => 35.3144, 'lng' => 46.9961],

        // Kermanshah (67)
        '67' => ['province' => 'کرمانشاه', 'city' => 'کرمانشاه', 'lat' => 34.3277, 'lng' => 47.0778],

        // Lorestan (68)
        '68' => ['province' => 'لرستان', 'city' => 'خرم‌آباد', 'lat' => 33.4878, 'lng' => 48.3558],

        // Fars (71, 73, 74)
        '71' => ['province' => 'فارس', 'city' => 'شیراز', 'lat' => 29.5918, 'lng' => 52.5837],
        '73' => ['province' => 'فارس', 'city' => 'مرودشت / فسا', 'lat' => 29.8741, 'lng' => 52.8094],
        '74' => ['province' => 'فارس', 'city' => 'لارستان / جهرم', 'lat' => 27.6833, 'lng' => 54.3417],

        // Bushehr (75)
        '75' => ['province' => 'بوشهر', 'city' => 'بوشهر', 'lat' => 28.9234, 'lng' => 50.8203],

        // Kohgiluyeh & Boyer-Ahmad (759, 76)
        '76' => ['province' => 'کهگیلویه و بویراحمد', 'city' => 'یاسوج', 'lat' => 30.6684, 'lng' => 51.5876],

        // Hormozgan (79, 78)
        '79' => ['province' => 'هرمزگان', 'city' => 'بندرعباس', 'lat' => 27.1832, 'lng' => 56.2666],
        '78' => ['province' => 'هرمزگان', 'city' => 'قشم / کیش', 'lat' => 26.9580, 'lng' => 56.2718],

        // Isfahan (81 to 84)
        '81' => ['province' => 'اصفهان', 'city' => 'اصفهان', 'lat' => 32.6539, 'lng' => 51.6660],
        '83' => ['province' => 'اصفهان', 'city' => 'نجف‌آباد / خمینی‌شهر', 'lat' => 32.6342, 'lng' => 51.3668],
        '84' => ['province' => 'اصفهان', 'city' => 'کاشان / نطنز', 'lat' => 33.9850, 'lng' => 51.4100],

        // Chaharmahal and Bakhtiari (88)
        '88' => ['province' => 'چهارمحال و بختیاری', 'city' => 'شهرکرد', 'lat' => 32.3256, 'lng' => 50.8644],

        // Yazd (89)
        '89' => ['province' => 'یزد', 'city' => 'یزد', 'lat' => 31.8974, 'lng' => 54.3569],

        // Sistan and Baluchestan (99)
        '99' => ['province' => 'سیستان و بلوچستان', 'city' => 'زاهدان', 'lat' => 29.4963, 'lng' => 60.8629],

        // Kerman (76, 77)
        '76' => ['province' => 'کرمان', 'city' => 'کرمان', 'lat' => 30.2839, 'lng' => 57.0834],

        // Razavi Khorasan (91 to 93)
        '91' => ['province' => 'خراسان رضوی', 'city' => 'مشهد', 'lat' => 36.2972, 'lng' => 59.6067],
        '93' => ['province' => 'خراسان رضوی', 'city' => 'نیشابور / سبزوار', 'lat' => 36.2133, 'lng' => 58.7958],

        // South Khorasan (97)
        '97' => ['province' => 'خراسان جنوبی', 'city' => 'بیرجند', 'lat' => 32.8663, 'lng' => 59.2211],

        // North Khorasan (94)
        '94' => ['province' => 'خراسان شمالی', 'city' => 'بجنورد', 'lat' => 37.4761, 'lng' => 57.3282]
    ];

    /**
     * Normalize Persian/Arabic digits to English digits
     */
    public static function normalizeDigits(string $input): string {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $str = str_replace($persian, $english, $input);
        return str_replace($arabic, $english, $str);
    }

    /**
     * Validates an Iranian Postal Code algorithmically
     * Filters out fake, repeated, out-of-range, and dummy sequences.
     * 
     * @param string $code Raw postal code string
     * @return array [ 'valid' => bool, 'code' => string, 'error' => ?string ]
     */
    public static function validatePostalCode(string $code): array {
        $clean = self::normalizeDigits(trim($code));
        $clean = preg_replace('/[^0-9]/', '', $clean);

        // 1. Length must be exactly 10 digits
        if (strlen($clean) !== 10) {
            return [
                'valid' => false,
                'code'  => $clean,
                'error' => 'کد پستی باید دقیقاً ۱۰ رقم باشد (بدون خط تیره یا فاصله).'
            ];
        }

        // 2. Reject all-identical digits (e.g. 1111111111, 0000000000, 9999999999)
        if (preg_match('/^(\d)\1{9}$/', $clean)) {
            return [
                'valid' => false,
                'code'  => $clean,
                'error' => 'کد پستی نمی‌تواند از ارقام یکسان و تکراری تشکیل شده باشد.'
            ];
        }

        // 3. Reject sequential dummy sequences (e.g. 1234567890, 0123456789, 9876543210)
        $dummySequences = [
            '1234567890', '0123456789', '9876543210', '0987654321',
            '1234512345', '9876598765', '1357924680', '2468013579'
        ];
        if (in_array($clean, $dummySequences, true)) {
            return [
                'valid' => false,
                'code'  => $clean,
                'error' => 'کد پستی وارد شده یک توالی آزمایشی و غیرمعتبر است.'
            ];
        }

        // 4. Iranian Postal Code Standard (شرکت ملی پست جمهوری اسلامی ایران):
        // - ۵ رقم اول (کد رهسپاری شهر و ناحیه): شامل ارقام ۱ و ۳ تا ۹ بوده و هرگز ارقام ۰ و ۲ در آن به کار نمی‌رود.
        // - ۵ رقم دوم (کد شناسایی ساختمان و واحد): شامل هر یک از ارقام ۰ تا ۹ می‌باشد و نباید تماماً صفر باشد.
        $regex = '/^[13-9]{5}[0-9]{5}$/';
        if (!preg_match($regex, $clean)) {
            // Check if 0 or 2 used in first 5 digits
            $firstFive = substr($clean, 0, 5);
            if (str_contains($firstFive, '0') || str_contains($firstFive, '2')) {
                return [
                    'valid' => false,
                    'code'  => $clean,
                    'error' => 'طبق استاندارد شرکت ملی پست، در ۵ رقم اول کد پستی نباید از ارقام ۰ یا ۲ استفاده شود.'
                ];
            }
            return [
                'valid' => false,
                'code'  => $clean,
                'error' => 'فرمت کد پستی با الگوی استاندارد ۱۰ رقمی ایران مطابقت ندارد.'
            ];
        }

        // 5. Last 5 digits cannot be all zeros
        if (substr($clean, 5, 5) === '00000') {
            return [
                'valid' => false,
                'code'  => $clean,
                'error' => 'پنج رقم دوم کد پستی نمی‌تواند تماماً صفر باشد.'
            ];
        }

        return [
            'valid' => true,
            'code'  => $clean,
            'error' => null
        ];
    }

    /**
     * Resolves Postal Code to Geographic Location & Administrative Info
     * 
     * @param string $code Raw postal code
     * @return ?array Resolved data or null if invalid
     */
    public static function lookupPostalCode(string $code): ?array {
        $val = self::validatePostalCode($code);
        if (!$val['valid']) {
            return null;
        }

        $clean = $val['code'];
        $prefix2 = substr($clean, 0, 2);
        $prefix1 = substr($clean, 0, 1);

        $match = self::$postalDirectory[$prefix2] 
            ?? self::$postalDirectory[$prefix1] 
            ?? [
                'province' => 'ایران',
                'city'     => 'نامشخص',
                'lat'      => 35.6892,
                'lng'      => 51.3890
            ];

        // Format nice description
        $zoneCode = substr($clean, 0, 5);
        $unitCode = substr($clean, 5, 5);

        return [
            'postal_code'       => $clean,
            'formatted_code'    => $zoneCode . '-' . $unitCode,
            'province'          => $match['province'],
            'city'              => $match['city'],
            'latitude'          => $match['lat'],
            'longitude'         => $match['lng'],
            'zoom'              => 14,
            'display_address'   => 'استان ' . $match['province'] . '، ' . $match['city'] . ' (محدوده پستی ' . $zoneCode . ')'
        ];
    }

    /**
     * Reverse Geocodes coordinates using Map.ir API with server-side caching & fallback
     * 
     * @param float $lat
     * @param float $lng
     * @return ?array Standardized Persian address array
     */
    public static function reverseGeocode(float $lat, float $lng): ?array {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        // 1. Primary: Map.ir Reverse Geocoding API
        $url = sprintf('https://map.ir/reverse?lat=%.6f&lon=%.6f', $lat, $lng);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . self::API_KEY,
            'Accept: application/json',
            'User-Agent: ASENA-Enterprise/1.0'
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $raw) {
            $json = json_decode($raw, true);
            if (is_array($json) && !empty($json['address'])) {
                $province = $json['province'] ?? '';
                $city = $json['city'] ?? $json['county'] ?? '';
                $neighborhood = $json['neighborhood'] ?? '';
                $primary = $json['primary'] ?? '';
                $address = $json['address'] ?? '';
                $postalAddress = $json['postal_address'] ?? $address;
                $postalCode = !empty($json['postal_code']) ? self::normalizeDigits($json['postal_code']) : '';

                return [
                    'source'            => 'map_ir',
                    'latitude'          => $lat,
                    'longitude'         => $lng,
                    'province'          => $province,
                    'city'              => $city,
                    'neighbourhood'     => $neighborhood,
                    'primary_road'      => $primary,
                    'formatted_address' => $address,
                    'postal_address'    => $postalAddress,
                    'postal_code'       => $postalCode
                ];
            }
        }

        // 2. Secondary Fallback: OpenStreetMap Nominatim with Persian language
        $nomUrl = sprintf(
            'https://nominatim.openstreetmap.org/reverse?format=json&lat=%.6f&lon=%.6f&zoom=18&addressdetails=1&accept-language=fa',
            $lat,
            $lng
        );
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: ASENA-Enterprise-System/1.0 (info@asena.company)',
                    'Accept-Language: fa,en;q=0.8',
                    'Accept: application/json'
                ],
                'timeout' => 3.0,
                'ignore_errors' => true
            ]
        ];
        $ctx = stream_context_create($opts);
        $nomRaw = @file_get_contents($nomUrl, false, $ctx);
        if ($nomRaw) {
            $nomJson = json_decode($nomRaw, true);
            if (is_array($nomJson) && !empty($nomJson['address'])) {
                $a = $nomJson['address'];
                $city = $a['city'] ?? $a['town'] ?? $a['village'] ?? $a['county'] ?? 'تبریز';
                $city = preg_replace('/^(شهرستان|شهر|بخش)\s+/u', '', trim($city));
                $road = $a['road'] ?? $a['pedestrian'] ?? $a['residential'] ?? '';
                $hood = $a['neighbourhood'] ?? $a['suburb'] ?? $a['quarter'] ?? '';
                $postcode = preg_replace('/[^0-9]/', '', $a['postcode'] ?? '');

                $formattedParts = array_filter([$hood, $road ? (str_starts_with($road, 'خیابان') ? $road : 'خیابان ' . $road) : '']);
                $formatted = !empty($formattedParts) ? implode('، ', $formattedParts) : ($nomJson['display_name'] ?? '');

                return [
                    'source'            => 'nominatim_fallback',
                    'latitude'          => $lat,
                    'longitude'         => $lng,
                    'province'          => $a['state'] ?? '',
                    'city'              => $city,
                    'neighbourhood'     => $hood,
                    'primary_road'      => $road,
                    'formatted_address' => $formatted,
                    'postal_address'    => $city . '، ' . $formatted,
                    'postal_code'       => substr($postcode, 0, 10)
                ];
            }
        }

        return null;
    }

    /**
     * High-Precision Iranian Postal Code Tour Area Lookup via Map.ir
     * Retrieves the 5-digit delivery zone polygon, bbox, centroid, and reverse-geocodes
     * the exact neighborhood and primary thoroughfare.
     * 
     * @param string $postalCode 10-digit Iranian postal code
     * @return ?array
     */
    public static function lookupPostalCodeTour(string $postalCode): ?array {
        $validation = self::validatePostalCode($postalCode);
        if (!$validation['valid']) {
            return null;
        }

        $clean = $validation['code'];
        $url = "https://map.ir/geo-data/postalcodes/{$clean}/tour-geom";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . self::API_KEY,
            'Accept: application/json',
            'User-Agent: ASENA-Enterprise/1.0'
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $raw) {
            $json = json_decode($raw, true);
            $coords = $json['data']['geom']['coordinates'][0] ?? null;

            if (!empty($coords) && is_array($coords)) {
                $latSum = 0;
                $lngSum = 0;
                $count = count($coords);
                $leafletPolygon = [];
                $minLat = 90.0;
                $maxLat = -90.0;
                $minLng = 180.0;
                $maxLng = -180.0;

                foreach ($coords as $pt) {
                    $lng = (float)$pt[0];
                    $lat = (float)$pt[1];
                    $latSum += $lat;
                    $lngSum += $lng;
                    $minLat = min($minLat, $lat);
                    $maxLat = max($maxLat, $lat);
                    $minLng = min($minLng, $lng);
                    $maxLng = max($maxLng, $lng);
                    $leafletPolygon[] = [$lat, $lng];
                }

                $centroidLat = round($latSum / $count, 6);
                $centroidLng = round($lngSum / $count, 6);

                // Reverse geocode the centroid to discover neighborhood and street
                $rev = self::reverseGeocode($centroidLat, $centroidLng);

                $province = $rev['province'] ?? '';
                $city = $rev['city'] ?? '';
                $neighborhood = $rev['neighbourhood'] ?? '';
                $primaryRoad = $rev['primary_road'] ?? '';
                $formattedAddress = $rev['formatted_address'] ?? '';

                if (empty($neighborhood) && !empty($formattedAddress)) {
                    if (preg_match('/محله\s+([^،,]+)/u', $formattedAddress, $m)) {
                        $neighborhood = trim($m[1]);
                    }
                }

                $zoneCode = substr($clean, 0, 5);
                $unitCode = substr($clean, 5, 5);

                // Build a clean, human-friendly doorstep suggested address
                $suggestedAddress = '';
                if (!empty($city)) {
                    $suggestedAddress .= $city;
                }
                if (!empty($neighborhood)) {
                    $suggestedAddress .= ($suggestedAddress ? '، محله ' : 'محله ') . $neighborhood;
                }
                if (!empty($primaryRoad)) {
                    $suggestedAddress .= ($suggestedAddress ? '، ' : '') . trim($primaryRoad);
                }
                if (empty($suggestedAddress)) {
                    $suggestedAddress = $formattedAddress;
                }

                return [
                    'source'            => 'map_ir_tour',
                    'postal_code'       => $clean,
                    'formatted_code'    => $zoneCode . '-' . $unitCode,
                    'zone_code'         => $zoneCode,
                    'unit_code'         => $unitCode,
                    'province'          => $province ?: 'آذربایجان شرقی',
                    'city'              => $city ?: 'تبریز',
                    'neighbourhood'     => $neighborhood,
                    'primary_road'      => trim($primaryRoad),
                    'formatted_address' => $formattedAddress,
                    'suggested_address' => $suggestedAddress,
                    'latitude'          => $centroidLat,
                    'longitude'         => $centroidLng,
                    'zoom'              => 16.5,
                    'polygon'           => $leafletPolygon,
                    'bounds'            => [
                        [$minLat, $minLng],
                        [$maxLat, $maxLng]
                    ],
                    'display_address'   => 'محدوده گشت پستی ' . $zoneCode . ($neighborhood ? ' (محله ' . $neighborhood . ')' : '')
                ];
            }
        }

        // Fallback to static postal directory if tour API didn't return geom
        return self::lookupPostalCode($clean);
    }

    /**
     * Map.ir Search v2 API wrapper for live street, neighborhood, and place autocomplete
     * 
     * @param string $text
     * @param ?float $lat Optional coordinate bias
     * @param ?float $lng Optional coordinate bias
     * @return array
     */
    public static function searchPlaces(string $text, ?float $lat = null, ?float $lng = null): array {
        $text = trim($text);
        if (empty($text) || mb_strlen($text) < 2) {
            return [];
        }

        $url = 'https://map.ir/search/v2';
        $body = ['text' => $text];
        if ($lat !== null && $lng !== null) {
            $body['location'] = [
                'type' => 'Point',
                'coordinates' => [$lng, $lat]
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . self::API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: ASENA-Enterprise/1.0'
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $raw) {
            $json = json_decode($raw, true);
            $items = $json['value'] ?? [];
            $results = [];

            foreach ($items as $item) {
                $geom = $item['geom'] ?? [];
                $coords = $geom['coordinates'] ?? [];
                if (count($coords) >= 2) {
                    $itemLng = (float)$coords[0];
                    $itemLat = (float)$coords[1];
                    $title = !empty($item['title']) ? $item['title'] : (!empty($item['neighborhood']) ? $item['neighborhood'] : ($item['address'] ?? 'مکان'));
                    
                    $results[] = [
                        'title'        => $title,
                        'address'      => $item['address'] ?? '',
                        'province'     => $item['province'] ?? '',
                        'county'       => $item['county'] ?? '',
                        'neighbourhood'=> $item['neighborhood'] ?? '',
                        'type'         => $item['type'] ?? '',
                        'fclass'       => $item['fclass'] ?? '',
                        'lat'          => $itemLat,
                        'lng'          => $itemLng
                    ];
                }
            }
            return array_slice($results, 0, 8);
        }

        return [];
    }
}
