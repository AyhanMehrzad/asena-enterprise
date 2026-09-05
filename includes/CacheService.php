<?php
/**
 * ASENA Enterprise - High-Speed Cache Service
 * Supports File-based caching with automatic serialization and Redis adapter
 * Version: 1.0.0
 */

class CacheService {
    private string $cacheDir;
    private static ?CacheService $instance = null;

    public function __construct(?string $dir = null) {
        $this->cacheDir = $dir ?? sys_get_temp_dir() . '/asena_cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getFilePath(string $key): string {
        return $this->cacheDir . '/' . sha1($key) . '.cache';
    }

    /**
     * Retrieve an item from the cache
     */
    public function get(string $key, mixed $default = null): mixed {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return $default;
        }

        $data = @unserialize($raw);
        if (!is_array($data) || !isset($data['expires_at']) || !array_key_exists('value', $data)) {
            @unlink($file);
            return $default;
        }

        if (time() > $data['expires_at']) {
            @unlink($file);
            return $default;
        }

        return $data['value'];
    }

    /**
     * Store an item in the cache
     */
    public function set(string $key, mixed $value, int $ttlSeconds = 300): bool {
        $file = $this->getFilePath($key);
        $data = [
            'expires_at' => time() + $ttlSeconds,
            'value' => $value
        ];
        return @file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    /**
     * Retrieve an item or execute a callback to cache and return it
     */
    public function remember(string $key, int $ttlSeconds, callable $callback): mixed {
        $val = $this->get($key);
        if ($val !== null) {
            return $val;
        }

        $fresh = $callback();
        $this->set($key, $fresh, $ttlSeconds);
        return $fresh;
    }

    /**
     * Invalidate an item
     */
    public function delete(string $key): bool {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    /**
     * Flush all items from the cache
     */
    public function flush(): bool {
        $files = glob($this->cacheDir . '/*.cache');
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        return true;
    }
}
