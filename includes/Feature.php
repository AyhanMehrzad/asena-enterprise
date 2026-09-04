<?php
/**
 * ASENA Enterprise - Feature Flag & Tier Manager
 * 
 * Determines enabled capabilities based on the active edition configured in .env or config.
 */

class Feature
{
    private static ?array $config = null;
    private static ?string $currentTier = null;
    private static ?array $enabledFeatures = null;

    /**
     * Initialize feature configuration.
     */
    private static function init(): void
    {
        if (self::$config !== null) {
            return;
        }

        $configFile = dirname(__DIR__) . '/config/tiers.php';
        if (file_exists($configFile)) {
            self::$config = require $configFile;
        } else {
            self::$config = ['tiers' => [], 'default_tier' => 'enterprise'];
        }

        // Tier detection: Environment variable > .env file > default
        $tier = getenv('ASENA_TIER') ?: (isset($_ENV['ASENA_TIER']) ? $_ENV['ASENA_TIER'] : null);
        
        if (!$tier) {
            $envFile = dirname(__DIR__) . '/.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, '=') !== false) {
                        [$k, $v] = explode('=', $line, 2);
                        if (trim($k) === 'ASENA_TIER') {
                            $tier = trim($v, " \t\n\r\0\x0B\"'");
                            break;
                        }
                    }
                }
            }
        }

        self::$currentTier = $tier ?: (self::$config['default_tier'] ?? 'enterprise');

        // Resolve active features
        $tierData = self::$config['tiers'][self::$currentTier] ?? self::$config['tiers']['enterprise'] ?? [];
        self::$enabledFeatures = $tierData['features'] ?? [];
    }

    /**
     * Check if a feature is enabled in the current tier.
     *
     * @param string $feature e.g. 'autoship', 'clinic_booking', 'pharmacy_catalog'
     * @return bool
     */
    public static function has(string $feature): bool
    {
        self::init();
        return in_array($feature, self::$enabledFeatures, true);
    }

    /**
     * Alias for has()
     */
    public static function enabled(string $feature): bool
    {
        return self::has($feature);
    }

    /**
     * Get current active tier code (e.g. 'basic', 'standard', 'premium', 'pharmacy', 'enterprise').
     */
    public static function currentTier(): string
    {
        self::init();
        return self::$currentTier;
    }

    /**
     * Get human-readable name of current tier.
     */
    public static function tierName(): string
    {
        self::init();
        return self::$config['tiers'][self::$currentTier]['name'] ?? ucfirst(self::$currentTier);
    }

    /**
     * Get all defined tiers.
     */
    public static function allTiers(): array
    {
        self::init();
        return self::$config['tiers'] ?? [];
    }

    /**
     * Temporarily set tier (useful for CLI packaging and previewing).
     */
    public static function setTier(string $tier): void
    {
        self::init();
        if (isset(self::$config['tiers'][$tier])) {
            self::$currentTier = $tier;
            self::$enabledFeatures = self::$config['tiers'][$tier]['features'] ?? [];
        }
    }
}
