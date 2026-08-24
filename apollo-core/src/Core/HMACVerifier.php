<?php

/**
 * HMAC Request Signing Verifier
 *
 * Verifies HMAC-SHA256 signatures on POST/PUT/DELETE REST API requests
 * to prevent payload tampering.
 *
 * Client must send:
 *   X-Apollo-Signature: sha256={base64_signature}
 *   X-Apollo-Timestamp: {unix_timestamp}
 *
 * Signature is computed as:
 *   HMAC-SHA256(METHOD + \n + PATH + \n + TIMESTAMP + \n + SHA256(BODY), user_secret)
 *
 * @package Apollo\Core
 */

declare(strict_types=1);

namespace Apollo\Core;

if (! defined('ABSPATH')) {
    exit;
}

class HMACVerifier
{
    /**
     * Maximum age of a signed request in seconds
     */
    private const TIMESTAMP_WINDOW = 300; // 5 minutes

    /**
     * Header names
     */
    private const HEADER_SIGNATURE = 'X-Apollo-Signature';
    private const HEADER_TIMESTAMP = 'X-Apollo-Timestamp';

    /**
     * REST route prefixes that require HMAC on mutations
     */
    private const PROTECTED_PREFIXES = array(
        '/apollo/v1/auth/',
        '/apollo/v1/chat/',
        '/apollo/v1/signatures/',
        '/apollo/v1/users/',
        '/apollo/v1/social/',
        '/apollo/v1/groups/',
        '/apollo/v1/events/',
        '/apollo/v1/fav/',
        '/apollo/v1/wow/',
        '/apollo/v1/comment/',
        '/apollo/v1/classifieds/',
        '/apollo/v1/notifications/',
    );

    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize HMAC verification on REST mutations
     */
    public static function init(): void
    {
        $self = self::get_instance();
        add_filter('rest_pre_dispatch', array($self, 'verify_request'), 5, 3);
    }

    /**
     * Check if HMAC verification is configured
     */
    public function is_configured(): bool
    {
        return defined('APOLLO_HMAC_MASTER_KEY') && ! empty(constant('APOLLO_HMAC_MASTER_KEY'));
    }

    /**
     * Verify HMAC signature on mutation requests
     *
     * @param mixed            $result  Pre-dispatch result.
     * @param \WP_REST_Server  $server  REST server.
     * @param \WP_REST_Request $request The request.
     * @return mixed|\WP_Error
     */
    public function verify_request(mixed $result, \WP_REST_Server $server, \WP_REST_Request $request): mixed
    {
        if (! $this->is_configured()) {
            return $result;
        }

        // Only verify mutations (POST, PUT, PATCH, DELETE)
        $method = strtoupper($request->get_method());
        if (in_array($method, array('GET', 'HEAD', 'OPTIONS'), true)) {
            return $result;
        }

        // Only protect Apollo routes
        $route = $request->get_route();
        if (! $this->is_protected_route($route)) {
            return $result;
        }

        // Skip for token issuance (user doesn't have HMAC secret yet)
        if ($route === '/apollo/v1/auth/token') {
            return $result;
        }

        // Require authenticated user
        $user_id = get_current_user_id();
        if ($user_id === 0) {
            return $result; // JWTAuth or cookie auth will handle this
        }

        // Get signature header
        $signature_header = $request->get_header('x_apollo_signature');
        if (empty($signature_header)) {
            return new \WP_Error(
                'hmac_missing',
                'Assinatura HMAC obrigatória para esta operação.',
                array('status' => 401)
            );
        }

        // Get timestamp header
        $timestamp = $request->get_header('x_apollo_timestamp');
        if (empty($timestamp)) {
            return new \WP_Error(
                'hmac_timestamp_missing',
                'Timestamp obrigatório para assinatura HMAC.',
                array('status' => 401)
            );
        }

        // Validate timestamp window
        $ts = (int) $timestamp;
        $now = time();
        if (abs($now - $ts) > self::TIMESTAMP_WINDOW) {
            return new \WP_Error(
                'hmac_timestamp_expired',
                'Assinatura HMAC expirada.',
                array('status' => 401)
            );
        }

        // Extract signature value (sha256=BASE64_SIG)
        if (! str_starts_with($signature_header, 'sha256=')) {
            return new \WP_Error(
                'hmac_invalid_format',
                'Formato de assinatura HMAC inválido.',
                array('status' => 401)
            );
        }
        $provided_sig = substr($signature_header, 7);

        // Compute expected signature
        $body      = $request->get_body();
        $body_hash = hash('sha256', $body);
        $message   = implode("\n", array($method, $route, $timestamp, $body_hash));

        $user_secret  = $this->derive_user_secret($user_id);
        $expected_sig = base64_encode(hash_hmac('sha256', $message, $user_secret, true));

        // Timing-safe comparison
        if (! hash_equals($expected_sig, $provided_sig)) {
            return new \WP_Error(
                'hmac_invalid',
                'Assinatura HMAC inválida.',
                array('status' => 401)
            );
        }

        return $result;
    }

    /**
     * Derive a per-user HMAC secret from the master key
     *
     * @param int $user_id
     * @return string The derived secret (raw bytes).
     */
    public function derive_user_secret(int $user_id): string
    {
        return hash_hmac('sha256', (string) $user_id, constant('APOLLO_HMAC_MASTER_KEY'), true);
    }

    /**
     * Get the base64-encoded user secret for client-side use
     * (to be delivered securely via JWT token response)
     *
     * @param int $user_id
     * @return string Base64-encoded secret.
     */
    public function get_user_secret_b64(int $user_id): string
    {
        return base64_encode($this->derive_user_secret($user_id));
    }

    /**
     * Check if a route is within the protected prefixes
     */
    private function is_protected_route(string $route): bool
    {
        foreach (self::PROTECTED_PREFIXES as $prefix) {
            if (str_starts_with($route, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate signature for client-side (utility for testing)
     *
     * @param string $method    HTTP method.
     * @param string $path      Request path.
     * @param string $body      Request body.
     * @param int    $user_id   User ID.
     * @param int    $timestamp Unix timestamp.
     * @return array{signature: string, timestamp: int}
     */
    public function sign(string $method, string $path, string $body, int $user_id, int $timestamp = 0): array
    {
        if ($timestamp === 0) {
            $timestamp = time();
        }

        $body_hash    = hash('sha256', $body);
        $message      = implode("\n", array(strtoupper($method), $path, (string) $timestamp, $body_hash));
        $user_secret  = $this->derive_user_secret($user_id);
        $signature    = base64_encode(hash_hmac('sha256', $message, $user_secret, true));

        return array(
            'signature' => "sha256={$signature}",
            'timestamp' => $timestamp,
        );
    }
}
