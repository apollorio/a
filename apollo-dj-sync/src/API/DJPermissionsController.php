<?php

/**
 * REST controller: DJ session logging (desktop bridge).
 *
 * Routes owned by this plugin:
 *   POST /apollo/v1/dj/session — session log write-back (requires app token + optional JWT/HMAC in strict mode)
 *
 * GET /dj/config and GET /dj/permissions are registered in apollo-login AppAuthController (D1).
 *
 * Diamond Rule 5: every write has permission_callback. Never __return_true.
 * Diamond Rule 7: authenticates via X-Apollo-App-Token resolved through apollo-login token meta.
 */

namespace Apollo\DJSync\API;

if (! defined('ABSPATH')) {
    exit;
}

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class DJPermissionsController extends WP_REST_Controller
{

    protected $namespace = 'apollo/v1';
    protected $rest_base = 'dj';

    public function register_routes(): void
    {
        // Removed /dj/permissions and /dj/config — they are registered by apollo-login.
        // This plugin owns only session logging.

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/session',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'log_session'],
                    'permission_callback' => [$this, 'app_token_permission'],
                    'args'                => $this->_session_args(),
                ],
            ]
        );
    }

    // ------------------------------------------------------------------
    // Permission helpers
    // ------------------------------------------------------------------

    /**
     * Resolve X-Apollo-App-Token to a user_id via apollo-login's stored hash.
     * Uses wp_hash() to match apollo-login's storage method.
     * Returns true if valid, WP_Error otherwise.
     */
    public function app_token_permission(WP_REST_Request $request): bool|WP_Error
    {
        $token = $request->get_header('x_apollo_app_token');

        if (empty($token) || ! preg_match('/^[a-zA-Z0-9]{64}$/', $token)) {
            return new WP_Error('dj_missing_token', __('Valid app token required.', 'apollo-dj-sync'), ['status' => 401]);
        }

        $user_id = $this->_resolve_token($token);

        if (! $user_id) {
            return new WP_Error('dj_invalid_token', __('Token not recognised or expired.', 'apollo-dj-sync'), ['status' => 403]);
        }

        $jwt_validation = $this->validate_jwt_and_hmac($request, $user_id);
        if (is_wp_error($jwt_validation)) {
            return $jwt_validation;
        }

        // Stash resolved user_id so callbacks can retrieve it without a second lookup.
        $request->set_param('_resolved_user_id', $user_id);

        return true;
    }

    // ------------------------------------------------------------------
    // Callbacks
    // ------------------------------------------------------------------

    /**
     * POST /apollo/v1/dj/session
     * Session log write-back from apolloDJ.exe — stored via apollo-core activity log.
     */
    public function log_session(WP_REST_Request $request): WP_REST_Response
    {
        $user_id = (int) $request->get_param('_resolved_user_id');

        $payload = [
            'user_id'    => $user_id,
            'app'        => 'apollodj',
            'version'    => sanitize_text_field((string) $request->get_param('version')),
            'action'     => sanitize_text_field((string) $request->get_param('action')),
            'track_hash' => sanitize_text_field((string) $request->get_param('track_hash')),
            'bpm'        => (float) $request->get_param('bpm'),
            'confidence' => (int) $request->get_param('confidence'),
            'cue_count'  => (int) $request->get_param('cue_count'),
            'os'         => sanitize_text_field((string) $request->get_param('os')),
            'logged_at'  => gmdate('c'),
        ];

        /**
         * Fire Apollo hook so apollo-core / apollo-statistics can store the entry.
         * Diamond Rule 6: approved hook naming.
         */
        do_action('apollo/dj/session_logged', $payload);

        $this->increment_daily_usage($user_id);

        return rest_ensure_response(['logged' => true]);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * Resolve a raw 64-char token to a WP user_id.
     * apollo-login stores wp_hash() of the token as user meta _apollo_app_token_{app_id}.
     * We hash the incoming token the same way and do a meta query — no plaintext secrets in DB.
     */
    private function _resolve_token(string $token): int
    {
        $hash     = wp_hash($token);
        $meta_key = '_apollo_app_token_' . APOLLO_DJ_SYNC_APP_ID;

        // Fast path — mirrors apollo-login AppAuthController::resolve_user_by_token().
        $cached_uid = get_transient('apollo_apptk_' . md5($hash));
        if ($cached_uid) {
            $uid    = (int) $cached_uid;
            $stored = get_user_meta($uid, $meta_key, true);
            if ($stored && hash_equals((string) $stored, $hash)) {
                $expiry = (int) get_user_meta($uid, $meta_key . '_expiry', true);
                if ($expiry > time()) {
                    return $uid;
                }
            }
            delete_transient('apollo_apptk_' . md5($hash));
        }

        $users = get_users([
            'meta_key'   => $meta_key,
            'meta_value' => $hash,
            'number'     => 1,
            'fields'     => 'ids',
        ]);

        if (empty($users)) {
            return 0;
        }

        $user_id = (int) $users[0];
        $expiry  = (int) get_user_meta($user_id, $meta_key . '_expiry', true);

        if ($expiry <= time()) {
            delete_user_meta($user_id, $meta_key);
            delete_user_meta($user_id, $meta_key . '_expiry');
            return 0;
        }

        set_transient('apollo_apptk_' . md5($hash), $user_id, DAY_IN_SECONDS);

        return $user_id;
    }

    /**
     * Atomically increment daily session usage meta (_apollo_dj_usage_{Y-m-d}).
     */
    private function increment_daily_usage(int $user_id): void
    {
        global $wpdb;

        $today_key = '_apollo_dj_usage_' . gmdate('Y-m-d');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $meta_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s LIMIT 1",
                $user_id,
                $today_key
            )
        );

        if ($meta_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->usermeta} SET meta_value = CAST(meta_value AS UNSIGNED) + 1 WHERE umeta_id = %d",
                    (int) $meta_id
                )
            );
            return;
        }

        add_user_meta($user_id, $today_key, 1, true);
    }

    /**
     * Schema for POST /dj/session args.
     *
     * @return array<string, array>
     */
    private function _session_args(): array
    {
        return [
            'version'    => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'required' => true],
            'action'     => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'required' => true],
            'track_hash' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => ''],
            'bpm'        => ['type' => 'number', 'default' => 0.0],
            'confidence' => ['type' => 'integer', 'default' => 0],
            'cue_count'  => ['type' => 'integer', 'default' => 0],
            'os'         => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => ''],
        ];
    }

    /**
     * Validate optional Bearer JWT + optional HMAC signature.
     * In strict mode this is mandatory and fail-closed.
     */
    private function validate_jwt_and_hmac(WP_REST_Request $request, int $resolved_user_id): bool|WP_Error
    {
        $strict_mode = $this->is_auth_strict_mode();
        $auth_header = (string) $request->get_header('authorization');

        if (! preg_match('/^\s*Bearer\s+(.+)\s*$/i', $auth_header, $matches)) {
            if ($strict_mode) {
                return new WP_Error('dj_missing_jwt', __('Bearer JWT required in strict mode.', 'apollo-dj-sync'), ['status' => 401]);
            }
            return true;
        }

        $jwt = trim((string) $matches[1]);
        if ('' === $jwt) {
            return new WP_Error('dj_invalid_jwt', __('Invalid JWT.', 'apollo-dj-sync'), ['status' => 401]);
        }

        $jwt_secret = $this->read_runtime_secret('APOLLO_DJ_JWT_SECRET', 'APOLLODJ_JWT_SECRET');
        if (strlen($jwt_secret) < 32) {
            return new WP_Error('dj_jwt_secret_missing', __('JWT secret not configured.', 'apollo-dj-sync'), ['status' => 500]);
        }

        $claims = $this->jwt_decode_hs256($jwt, $jwt_secret);
        if (! is_array($claims)) {
            return new WP_Error('dj_invalid_jwt', __('JWT validation failed.', 'apollo-dj-sync'), ['status' => 403]);
        }

        $claim_user_id = isset($claims['sub']) ? (int) $claims['sub'] : 0;
        $claim_app_id  = isset($claims['app_id']) ? sanitize_key((string) $claims['app_id']) : '';
        if ($claim_user_id <= 0 || $claim_user_id !== $resolved_user_id || APOLLO_DJ_SYNC_APP_ID !== $claim_app_id) {
            return new WP_Error('dj_jwt_claim_mismatch', __('JWT claims do not match token user/app.', 'apollo-dj-sync'), ['status' => 403]);
        }

        $hmac_secret = $this->read_runtime_secret('APOLLO_DJ_HMAC_SECRET', 'APOLLODJ_HMAC_SECRET');
        $sig_header  = trim((string) $request->get_header('x_apollo_jwt_signature'));
        $ts_header   = trim((string) $request->get_header('x_apollo_jwt_timestamp'));
        $must_check_hmac = $strict_mode || '' !== $sig_header || '' !== $ts_header || '' !== $hmac_secret;

        if (! $must_check_hmac) {
            return true;
        }

        if (strlen($hmac_secret) < 32) {
            return new WP_Error('dj_hmac_secret_missing', __('HMAC secret not configured.', 'apollo-dj-sync'), ['status' => 500]);
        }

        if ('' === $sig_header || '' === $ts_header || ! ctype_digit($ts_header)) {
            return new WP_Error('dj_missing_hmac', __('JWT signature headers required.', 'apollo-dj-sync'), ['status' => 401]);
        }

        $timestamp = (int) $ts_header;
        if (abs(time() - $timestamp) > 300) {
            return new WP_Error('dj_hmac_expired', __('JWT signature timestamp expired.', 'apollo-dj-sync'), ['status' => 401]);
        }

        $expected = hash_hmac('sha256', $ts_header . '.' . $jwt, $hmac_secret);
        if (! hash_equals($expected, $sig_header)) {
            return new WP_Error('dj_hmac_invalid', __('JWT HMAC verification failed.', 'apollo-dj-sync'), ['status' => 403]);
        }

        return true;
    }

    private function is_auth_strict_mode(): bool
    {
        if (defined('APOLLO_DJ_AUTH_STRICT_MODE')) {
            return (bool) constant('APOLLO_DJ_AUTH_STRICT_MODE');
        }

        $env = getenv('APOLLODJ_AUTH_STRICT');
        if (false !== $env && '' !== trim((string) $env)) {
            $value = filter_var($env, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (null !== $value) {
                return $value;
            }
        }

        return wp_get_environment_type() === 'production';
    }

    private function read_runtime_secret(string $constant_name, string $env_name): string
    {
        if (defined($constant_name)) {
            $value = (string) constant($constant_name);
            if ('' !== trim($value)) {
                return trim($value);
            }
        }

        $env = getenv($env_name);
        if (false === $env) {
            return '';
        }

        return trim((string) $env);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function jwt_decode_hs256(string $jwt, string $secret): ?array
    {
        $parts = explode('.', $jwt);
        if (3 !== count($parts)) {
            return null;
        }

        list($head_b64, $body_b64, $sig_b64) = $parts;

        $header_json = $this->base64url_decode($head_b64);
        $payload_json = $this->base64url_decode($body_b64);
        $sig_raw = $this->base64url_decode($sig_b64);
        if ('' === $header_json || '' === $payload_json || '' === $sig_raw) {
            return null;
        }

        $header = json_decode($header_json, true);
        $claims = json_decode($payload_json, true);
        if (! is_array($header) || ! is_array($claims)) {
            return null;
        }

        if (($header['alg'] ?? '') !== 'HS256' || ($header['typ'] ?? '') !== 'JWT') {
            return null;
        }

        $expected_sig = hash_hmac('sha256', $head_b64 . '.' . $body_b64, $secret, true);
        if (! hash_equals($expected_sig, $sig_raw)) {
            return null;
        }

        $exp = isset($claims['exp']) ? (int) $claims['exp'] : 0;
        if ($exp <= 0 || (time() - 30) >= $exp) {
            return null;
        }

        return $claims;
    }

    private function base64url_decode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return false === $decoded ? '' : $decoded;
    }
}
