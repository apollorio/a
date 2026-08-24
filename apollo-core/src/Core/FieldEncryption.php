<?php

/**
 * AES-256-GCM Field Encryption for PII
 *
 * Transparently encrypts/decrypts sensitive user meta fields at rest.
 * Uses libsodium (preferred) or OpenSSL fallback.
 *
 * Configuration (wp-config.php):
 *   define('APOLLO_ENCRYPTION_KEY', '<32-byte-base64-key>');
 *   // Optional: key rotation — first key encrypts, all keys try decrypt
 *   define('APOLLO_ENCRYPTION_KEYS', '<key1>,<key2-legacy>');
 *
 * Generate a key: php -r "echo base64_encode(random_bytes(32));"
 *
 * @package Apollo\Core
 */

declare(strict_types=1);

namespace Apollo\Core;

if (! defined('ABSPATH')) {
    exit;
}

class FieldEncryption
{
    /**
     * Meta keys that must be encrypted at rest.
     * From apollo-registry.json PII fields.
     */
    private const ENCRYPTED_META_KEYS = array(
        '_apollo_phone',
        '_apollo_birth_date',
        '_apollo_instagram',
        '_apollo_verification_token',
        '_apollo_password_reset_token',
        '_classified_contact_phone',
        '_classified_contact_whatsapp',
        '_supplier_cnpj',
        '_supplier_contact_email',
        '_doc_cpf',
        '_apollo_push_subscriptions',
        '_apollo_chat_blocked_users',
    );

    private const IV_LENGTH  = 12;
    private const TAG_LENGTH = 16;

    /**
     * Encryption keys — first is current, rest are legacy for decryption
     *
     * @var string[]
     */
    private array $keys = array();

    /**
     * Whether libsodium AES-256-GCM is available
     */
    private bool $use_sodium = false;

    private static ?self $instance = null;

    private function __construct()
    {
        $this->load_keys();
        $this->use_sodium = function_exists('sodium_crypto_aead_aes256gcm_is_available')
            && sodium_crypto_aead_aes256gcm_is_available();
    }

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize WordPress meta filters for transparent encryption
     */
    public static function init(): void
    {
        $self = self::get_instance();

        // Intercept user meta writes
        add_filter('update_user_metadata', array($self, 'encrypt_on_write'), 10, 5);
        add_filter('add_user_metadata', array($self, 'encrypt_on_write'), 10, 5);

        // Intercept user meta reads
        add_filter('get_user_metadata', array($self, 'decrypt_on_read'), 10, 4);

        // Also handle post meta for classified/supplier PII
        add_filter('update_post_metadata', array($self, 'encrypt_post_on_write'), 10, 5);
        add_filter('add_post_metadata', array($self, 'encrypt_post_on_write'), 10, 5);
        add_filter('get_post_metadata', array($self, 'decrypt_post_on_read'), 10, 4);

        // WP-CLI
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('apollo encrypt-fields', array($self, 'cli_migrate'));
        }
    }

    /**
     * Load encryption keys from wp-config.php constants
     */
    private function load_keys(): void
    {
        // Multi-key rotation support
        if (defined('APOLLO_ENCRYPTION_KEYS')) {
            $raw = constant('APOLLO_ENCRYPTION_KEYS');
            foreach (explode(',', $raw) as $b64_key) {
                $decoded = base64_decode(trim($b64_key), true);
                if ($decoded !== false && strlen($decoded) === 32) {
                    $this->keys[] = $decoded;
                }
            }
        }

        // Single key fallback
        if (empty($this->keys) && defined('APOLLO_ENCRYPTION_KEY')) {
            $decoded = base64_decode(constant('APOLLO_ENCRYPTION_KEY'), true);
            if ($decoded !== false && strlen($decoded) === 32) {
                $this->keys[] = $decoded;
            }
        }
    }

    /**
     * Check if encryption is properly configured
     */
    public function is_configured(): bool
    {
        return ! empty($this->keys);
    }

    /**
     * Check if a meta key should be encrypted
     */
    private function should_encrypt(string $meta_key): bool
    {
        return in_array($meta_key, self::ENCRYPTED_META_KEYS, true);
    }

    /**
     * Encrypt plaintext using AES-256-GCM
     *
     * @param string $plaintext The data to encrypt.
     * @param string $aad       Additional authenticated data (meta key name).
     * @return string Base64-encoded ciphertext: [IV 12B][ciphertext][tag 16B]
     */
    public function encrypt(string $plaintext, string $aad = ''): string
    {
        if (empty($this->keys)) {
            return $plaintext;
        }

        // Don't double-encrypt
        if ($this->is_encrypted($plaintext)) {
            return $plaintext;
        }

        $iv  = random_bytes(self::IV_LENGTH);
        $key = $this->keys[0]; // Current key
        $tag = '';

        if ($this->use_sodium) {
            $ciphertext = sodium_crypto_aead_aes256gcm_encrypt(
                $plaintext,
                $aad,
                $iv,
                $key
            );
            // sodium appends tag to ciphertext
            // Prefix with 'S' byte to mark sodium format
            $packed = 'S' . $iv . $ciphertext;
        } else {
            $ciphertext = openssl_encrypt(
                $plaintext,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                $aad
            );
            if ($ciphertext === false) {
                return $plaintext;
            }
            $packed = $iv . $ciphertext . $tag;
        }

        // Prefix with marker for detection
        return '$APOLLO_ENC$' . base64_encode($packed);
    }

    /**
     * Decrypt AES-256-GCM ciphertext
     *
     * @param string $ciphertext Base64-encoded encrypted data with $APOLLO_ENC$ prefix.
     * @param string $aad        Additional authenticated data (meta key name).
     * @return string Decrypted plaintext, or original string if not encrypted/decryption fails.
     */
    public function decrypt(string $ciphertext, string $aad = ''): string
    {
        if (empty($this->keys) || ! $this->is_encrypted($ciphertext)) {
            return $ciphertext;
        }

        $raw = base64_decode(substr($ciphertext, strlen('$APOLLO_ENC$')), true);
        if ($raw === false || strlen($raw) < self::IV_LENGTH + 2) {
            return $ciphertext;
        }

        // Detect format: 'S' prefix = sodium, 'O' or legacy (no prefix) = OpenSSL
        $format = 'openssl';
        if ($raw[0] === 'S') {
            $format = 'sodium';
            $raw = substr($raw, 1); // strip format byte
        }

        $iv = substr($raw, 0, self::IV_LENGTH);
        $encrypted = substr($raw, self::IV_LENGTH);

        // Try all keys (current + legacy) for decryption
        foreach ($this->keys as $key) {
            $result = $this->try_decrypt($encrypted, $iv, $key, $aad, $format);
            if ($result !== false) {
                return $result;
            }
        }

        return $ciphertext;
    }

    /**
     * Attempt decryption with a single key
     */
    private function try_decrypt(string $encrypted, string $iv, string $key, string $aad, string $format = 'openssl'): string|false
    {
        if ($format === 'sodium' && function_exists('sodium_crypto_aead_aes256gcm_decrypt')) {
            try {
                $plaintext = sodium_crypto_aead_aes256gcm_decrypt(
                    $encrypted,
                    $aad,
                    $iv,
                    $key
                );
                return $plaintext !== false ? $plaintext : false;
            } catch (\SodiumException $e) {
                return false;
            }
        }

        // OpenSSL: last 16 bytes are tag
        if (strlen($encrypted) < self::TAG_LENGTH) {
            return false;
        }

        $tag = substr($encrypted, -self::TAG_LENGTH);
        $data = substr($encrypted, 0, -self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $data,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        return $plaintext !== false ? $plaintext : false;
    }

    /**
     * Check if a value appears to be encrypted
     */
    public function is_encrypted(string $value): bool
    {
        return str_starts_with($value, '$APOLLO_ENC$');
    }

    // ─────────────────────────────────────────────────────────────────
    // WordPress Meta Hooks
    // ─────────────────────────────────────────────────────────────────

    /**
     * Encrypt user meta before writing to database
     *
     * @param null|bool $check     Whether to short-circuit.
     * @param int       $object_id User ID.
     * @param string    $meta_key  Meta key.
     * @param mixed     $meta_value Meta value.
     * @param mixed     $prev_value Previous value (update only).
     * @return null|bool
     */
    public function encrypt_on_write($check, int $object_id, string $meta_key, mixed $meta_value, mixed $prev_value = ''): null|bool
    {
        if (! $this->should_encrypt($meta_key) || ! $this->is_configured()) {
            return $check;
        }

        if (! is_string($meta_value) || $meta_value === '') {
            return $check;
        }

        // Encrypt the value
        $encrypted = $this->encrypt($meta_value, $meta_key);
        if ($encrypted === $meta_value) {
            return $check; // Encryption failed or already encrypted
        }

        // Temporarily remove our filter to avoid infinite loop
        remove_filter('update_user_metadata', array($this, 'encrypt_on_write'), 10);
        remove_filter('add_user_metadata', array($this, 'encrypt_on_write'), 10);

        update_user_meta($object_id, $meta_key, $encrypted);

        add_filter('update_user_metadata', array($this, 'encrypt_on_write'), 10, 5);
        add_filter('add_user_metadata', array($this, 'encrypt_on_write'), 10, 5);

        return true; // Short-circuit the original write
    }

    /**
     * Decrypt user meta after reading from database
     *
     * @param mixed  $check     Current value.
     * @param int    $object_id User ID.
     * @param string $meta_key  Meta key.
     * @param bool   $single    Whether to return single value.
     * @return mixed
     */
    public function decrypt_on_read(mixed $check, int $object_id, string $meta_key, bool $single): mixed
    {
        if (! $this->should_encrypt($meta_key) || ! $this->is_configured()) {
            return $check;
        }

        // Must bypass our filter to get the raw value
        remove_filter('get_user_metadata', array($this, 'decrypt_on_read'), 10);
        $raw = get_user_meta($object_id, $meta_key, true);
        add_filter('get_user_metadata', array($this, 'decrypt_on_read'), 10, 4);

        if (! is_string($raw) || $raw === '') {
            return $check;
        }

        $decrypted = $this->decrypt($raw, $meta_key);
        return $single ? $decrypted : array($decrypted);
    }

    /**
     * Encrypt post meta before writing (for classified/supplier PII)
     */
    public function encrypt_post_on_write($check, int $object_id, string $meta_key, mixed $meta_value, mixed $prev_value = ''): null|bool
    {
        if (! $this->should_encrypt($meta_key) || ! $this->is_configured()) {
            return $check;
        }

        if (! is_string($meta_value) || $meta_value === '') {
            return $check;
        }

        $encrypted = $this->encrypt($meta_value, $meta_key);
        if ($encrypted === $meta_value) {
            return $check;
        }

        remove_filter('update_post_metadata', array($this, 'encrypt_post_on_write'), 10);
        remove_filter('add_post_metadata', array($this, 'encrypt_post_on_write'), 10);

        update_post_meta($object_id, $meta_key, $encrypted);

        add_filter('update_post_metadata', array($this, 'encrypt_post_on_write'), 10, 5);
        add_filter('add_post_metadata', array($this, 'encrypt_post_on_write'), 10, 5);

        return true;
    }

    /**
     * Decrypt post meta after reading
     */
    public function decrypt_post_on_read(mixed $check, int $object_id, string $meta_key, bool $single): mixed
    {
        if (! $this->should_encrypt($meta_key) || ! $this->is_configured()) {
            return $check;
        }

        remove_filter('get_post_metadata', array($this, 'decrypt_post_on_read'), 10);
        $raw = get_post_meta($object_id, $meta_key, true);
        add_filter('get_post_metadata', array($this, 'decrypt_post_on_read'), 10, 4);

        if (! is_string($raw) || $raw === '') {
            return $check;
        }

        $decrypted = $this->decrypt($raw, $meta_key);
        return $single ? $decrypted : array($decrypted);
    }

    // ─────────────────────────────────────────────────────────────────
    // WP-CLI Migration
    // ─────────────────────────────────────────────────────────────────

    /**
     * CLI: wp apollo encrypt-fields [--dry-run]
     *
     * Scans user/post meta for plaintext PII and encrypts in-place.
     */
    public function cli_migrate(array $args, array $assoc_args): void
    {
        if (! $this->is_configured()) {
            \WP_CLI::error('Encryption not configured. Set APOLLO_ENCRYPTION_KEY in wp-config.php');
            return;
        }

        $dry_run = isset($assoc_args['dry-run']);
        $encrypted_count = 0;
        $skipped_count   = 0;

        \WP_CLI::log(($dry_run ? '[DRY RUN] ' : '') . 'Scanning user meta for plaintext PII...');

        global $wpdb;

        foreach (self::ENCRYPTED_META_KEYS as $meta_key) {
            // User meta
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT umeta_id, user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value != '' AND meta_value NOT LIKE %s",
                    $meta_key,
                    '$APOLLO_ENC$%'
                )
            );

            foreach ($rows as $row) {
                if ($dry_run) {
                    \WP_CLI::log("  Would encrypt user #{$row->user_id} → {$meta_key}");
                    $encrypted_count++;
                    continue;
                }

                $encrypted = $this->encrypt($row->meta_value, $meta_key);
                if ($encrypted !== $row->meta_value) {
                    $wpdb->update(
                        $wpdb->usermeta,
                        array('meta_value' => $encrypted),
                        array('umeta_id' => $row->umeta_id),
                        array('%s'),
                        array('%d')
                    );
                    $encrypted_count++;
                } else {
                    $skipped_count++;
                }
            }

            // Post meta (classifieds, suppliers)
            $post_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != '' AND meta_value NOT LIKE %s",
                    $meta_key,
                    '$APOLLO_ENC$%'
                )
            );

            foreach ($post_rows as $row) {
                if ($dry_run) {
                    \WP_CLI::log("  Would encrypt post #{$row->post_id} → {$meta_key}");
                    $encrypted_count++;
                    continue;
                }

                $encrypted = $this->encrypt($row->meta_value, $meta_key);
                if ($encrypted !== $row->meta_value) {
                    $wpdb->update(
                        $wpdb->postmeta,
                        array('meta_value' => $encrypted),
                        array('meta_id' => $row->meta_id),
                        array('%s'),
                        array('%d')
                    );
                    $encrypted_count++;
                } else {
                    $skipped_count++;
                }
            }
        }

        \WP_CLI::success("{$encrypted_count} fields encrypted, {$skipped_count} skipped.");
    }
}
