<?php
/**
 * ASENA Enterprise - Environment & Configuration Loader
 */

class Env {
    private static bool $loaded = false;

    public static function load(): void {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $searchPaths = [
            __DIR__ . '/../.env',
            __DIR__ . '/../../.env',
            __DIR__ . '/.env',
            dirname(__DIR__, 2) . '/.env'
        ];

        $envFile = null;
        foreach ($searchPaths as $p) {
            if (file_exists($p) && is_readable($p)) {
                $envFile = $p;
                break;
            }
        }

        // If no .env exists, copy from .env.example
        if (!$envFile && file_exists(__DIR__ . '/../.env.example')) {
            @copy(__DIR__ . '/../.env.example', __DIR__ . '/../.env');
            if (file_exists(__DIR__ . '/../.env')) {
                $envFile = __DIR__ . '/../.env';
            }
        }

        if ($envFile) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val, " \t\n\r\0\x0B\"'");

                    if (getenv($key) === false) {
                        putenv("{$key}={$val}");
                    }
                    if (!isset($_ENV[$key])) {
                        $_ENV[$key] = $val;
                    }
                    if (!isset($_SERVER[$key])) {
                        $_SERVER[$key] = $val;
                    }
                }
            }
        }

        // Define DB constants from .env if not yet defined
        if (!defined('DB_HOST')) {
            define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
        }
        if (!defined('DB_NAME')) {
            define('DB_NAME', getenv('DB_NAME') ?: 'asena_premium');
        }
        if (!defined('DB_USER')) {
            define('DB_USER', getenv('DB_USER') ?: 'root');
        }
        if (!defined('DB_PASS')) {
            define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
        }
    }

    public static function get(string $key, $default = null) {
        self::load();
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
        return $val !== null ? $val : $default;
    }
}

// Auto-load on include
Env::load();
