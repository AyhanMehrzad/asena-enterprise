<?php
/**
 * ASENA Enterprise - Iranian Multi-Carrier Shipping Calculator
 * Benchmarked against Digikala.com Logistics Engine (Iran Post, Tipax, AloPeyk, Cold Chain)
 */

class ShippingCalculator
{
    private PDO $pdo;
    private string $originProvince = 'تهران'; // Center of distribution (or 'آذربایجان شرقی')

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            $this->pdo = $pdo;
        }
    }

    /**
     * Calculate delivery cost for a specific carrier.
     */
    public function calculate(string $carrierCode, string $destProvince, int $totalWeightGrams, bool $requiresColdChain = false): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM shipping_rates WHERE carrier_code = ? AND is_active = 1");
        $stmt->execute([$carrierCode]);
        $rate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rate) {
            // Default fallback
            return [
                'carrier_code'          => $carrierCode,
                'carrier_name_fa'       => 'ارسال استاندارد آسنا',
                'cost'                  => 45000,
                'formatted_cost'        => '۴۵,۰۰۰ تومان',
                'estimated_delivery_fa' => '۲ تا ۳ روز کاری',
                'requires_cold_chain'   => $requiresColdChain
            ];
        }

        $baseCost = (int)$rate['base_cost'];
        $baseWeight = (int)$rate['base_weight_grams'];
        $extraKgCost = (int)$rate['extra_kg_cost'];
        $interProvincialSurcharge = (int)$rate['inter_provincial_surcharge'];
        $coldChainSurcharge = (int)$rate['cold_chain_surcharge'];

        $totalCost = $baseCost;

        // Extra weight cost
        if ($totalWeightGrams > $baseWeight) {
            $extraWeightGrams = $totalWeightGrams - $baseWeight;
            $extraKgs = ceil($extraWeightGrams / 1000.0);
            $totalCost += ($extraKgs * $extraKgCost);
        }

        // Inter-provincial surcharge (if destination is different from origin)
        $isInterProvincial = (!empty($destProvince) && trim($destProvince) !== $this->originProvince);
        if ($isInterProvincial) {
            $totalCost += $interProvincialSurcharge;
        }

        // Cold-chain surcharge (insulated thermal box + ice packs)
        if ($requiresColdChain) {
            $totalCost += max(40000, $coldChainSurcharge);
        }

        $estMin = $rate['estimated_days_min'];
        $estMax = $rate['estimated_days_max'];
        $estFa = ($estMin === $estMax) ? "تحویل ظرف {$estMin} روز کاری" : "تحویل ظرف {$estMin} الی {$estMax} روز کاری";

        return [
            'carrier_code'          => $carrierCode,
            'carrier_name_fa'       => $rate['carrier_name_fa'],
            'cost'                  => (int)$totalCost,
            'formatted_cost'        => number_format($totalCost) . ' تومان',
            'estimated_delivery_fa' => $estFa,
            'is_inter_provincial'   => $isInterProvincial,
            'requires_cold_chain'   => $requiresColdChain
        ];
    }

    /**
     * Retrieve all eligible shipping methods for checkout comparison.
     */
    public function getAllOptions(string $destProvince, int $totalWeightGrams, bool $requiresColdChain = false): array
    {
        $stmt = $this->pdo->query("SELECT carrier_code FROM shipping_rates WHERE is_active = 1 ORDER BY base_cost ASC");
        $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $options = [];
        foreach ($codes as $code) {
            // If cold chain is strictly required, promote or limit to cold_chain_express or tipax
            if ($requiresColdChain && $code === 'pishtaz') {
                // Post pishtaz does not support cold chain biologicals
                continue;
            }
            $options[] = $this->calculate($code, $destProvince, $totalWeightGrams, $requiresColdChain);
        }

        return $options;
    }
}
