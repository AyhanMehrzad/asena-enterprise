<?php
/**
 * ASENA Enterprise - Data Security & Cryptography Service
 * Provides:
 *  1. Cryptographic HMAC Server-Side Password Pepper (renders dumped DB password cracking impossible).
 *  2. Authenticated Field-Level Encryption (AES-256-GCM) for PII (National ID, Sheba, Card, Address).
 *  3. Deterministic HMAC Blind Indexing for encrypted column lookups.
 *  4. Sensitive data masking helpers for UI.
 * 
 * Version: 1.0.0
 */

require_once __DIR__ . '/Env.php';

class DataSecurityService {

    private static ?self $instance = null;
    private string $pepper;
    private string $encryptionKey;
    private string $blindIndexKey;

    public function __construct(?string $pepper = null, ?string $encryptionKey = null, ?string $blindIndexKey = null) {
        $this->pepper = $pepper ?? (string)Env::get('APP_SECURITY_PEPPER', 'asena_def_pepper_8f1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f');
        
        $rawKey = $encryptionKey ?? (string)Env::get('APP_DATA_ENCRYPTION_KEY', 'asena_def_aes_key_9a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1a0b9c8d7e6f');
        // Derive exact 256-bit binary key via SHA-256 HKDF/hash
        $this->encryptionKey = hash('sha256', $rawKey, true);

        $rawBlind = $blindIndexKey ?? (string)Env::get('APP_BLIND_INDEX_KEY', 'asena_def_blind_key_1234567890abcdef1234567890abcdef1234567890abcdef');
        $this->blindIndexKey = hash('sha256', $rawBlind, true);
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ─── 1. PEPPERED PASSWORD HASHING ─────────────────────────────────────────

    /**
     * Hash password using secret server pepper + BCrypt/Argon2
     * Even if database is dumped, hashes cannot be cracked without APP_SECURITY_PEPPER.
     */
    public function hashPassword(string $plainPassword): string {
        $peppered = hash_hmac('sha256', $plainPassword, $this->pepper);
        return password_hash($peppered, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * Verify password against peppered hash or legacy unpeppered hash.
     * Sets $needsRehash = true if password was verified using legacy format or needs algorithm upgrade.
     */
    public function verifyPassword(string $plainPassword, string $hash, ?bool &$needsRehash = null): bool {
        $needsRehash = false;

        if (empty($hash) || empty($plainPassword)) {
            return false;
        }

        // 1. Try peppered verification
        $peppered = hash_hmac('sha256', $plainPassword, $this->pepper);
        if (password_verify($peppered, $hash)) {
            $needsRehash = password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 12]);
            return true;
        }

        // 2. Fallback to legacy unpeppered verification (for accounts created before pepper)
        if (password_verify($plainPassword, $hash)) {
            // Must rehash to upgrade account to peppered scheme!
            $needsRehash = true;
            return true;
        }

        return false;
    }

    // ─── 2. AES-256-GCM FIELD-LEVEL ENCRYPTION ─────────────────────────────────

    /**
     * Encrypt sensitive data (National ID, Bank Sheba, Card, Address) using AES-256-GCM.
     * Output format: enc:v1:<base64(iv . tag . ciphertext)>
     */
    public function encrypt(?string $plaintext): ?string {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        // If already encrypted, do not re-encrypt
        if (str_starts_with($plaintext, 'enc:v1:')) {
            return $plaintext;
        }

        // 96-bit (12 bytes) IV recommended for GCM
        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // 128-bit authentication tag
        );

        if ($ciphertext === false) {
            throw new RuntimeException('خطای رمزنگاری داده‌های حساس.');
        }

        return 'enc:v1:' . base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt sensitive data.
     * Transparently returns plaintext if input was not encrypted (legacy data compatibility).
     */
    public function decrypt(?string $data): ?string {
        if ($data === null || $data === '') {
            return $data;
        }

        // If not encrypted, return as-is (graceful backward compatibility)
        if (!str_starts_with($data, 'enc:v1:')) {
            return $data;
        }

        $raw = base64_decode(substr($data, 7), true);
        if ($raw === false || strlen($raw) < 28) { // 12 (iv) + 16 (tag) = 28 bytes min
            return null;
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            // Authentication tag failed: data was tampered with or key is invalid
            error_log('[DataSecurityService] AES-256-GCM authentication failed. Tampered ciphertext or wrong key.');
            return null;
        }

        return $plaintext;
    }

    // ─── 3. DETERMINISTIC BLIND INDEXING ──────────────────────────────────────

    /**
     * Generate deterministic HMAC blind index for querying encrypted fields without decrypting table.
     * Example: SELECT * FROM users WHERE national_id_blind_index = ?
     */
    public function blindIndex(?string $value): ?string {
        if ($value === null || $value === '') {
            return null;
        }
        $normalized = mb_strtolower(trim($value));
        return hash_hmac('sha256', $normalized, $this->blindIndexKey);
    }

    // ─── 4. SENSITIVE DATA MASKING HELPERS ────────────────────────────────────

    public function maskNationalId(?string $id): string {
        $plain = $this->decrypt($id);
        if (empty($plain)) return '';
        $clean = preg_replace('/\D/', '', $plain);
        if (strlen($clean) === 10) {
            return substr($clean, 0, 3) . '****' . substr($clean, 7, 3);
        }
        return '***' . substr($clean, -4);
    }

    public function maskSheba(?string $sheba): string {
        $plain = $this->decrypt($sheba);
        if (empty($plain)) return '';
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', $plain));
        if (strlen($clean) >= 26) {
            return substr($clean, 0, 4) . ' **** **** **** **** ' . substr($clean, -4);
        }
        return substr($clean, 0, 4) . ' **** ' . substr($clean, -4);
    }

    public function maskCard(?string $card): string {
        $plain = $this->decrypt($card);
        if (empty($plain)) return '';
        $clean = preg_replace('/\D/', '', $plain);
        if (strlen($clean) === 16) {
            return substr($clean, 0, 4) . '-****-****-' . substr($clean, -4);
        }
        return '****-****-****-' . substr($clean, -4);
    }

    public function maskPhone(?string $phone): string {
        $plain = $this->decrypt($phone);
        if (empty($plain)) return '';
        $clean = preg_replace('/\D/', '', $plain);
        if (strlen($clean) >= 11) {
            return substr($clean, 0, 4) . '***' . substr($clean, -4);
        }
        return $plain;
    }
}
