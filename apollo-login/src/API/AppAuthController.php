<?php

/**
 * App Token Auth REST Controller
 *
 * Token-based authentication for desktop apps (apolloDJ, etc.).
 * Issues long-lived opaque tokens stored hashed in user_meta.
 * Client sends token via X-Apollo-App-Token header.
 *
 * Ecosystem: routes live under {@see APOLLO_LOGIN_REST_NAMESPACE} (apollo/v1). GET /dj/config is intentionally
 * public read-only boot config. GET /dj/permissions + POST /app/* require app_id + token rules as coded.
 * One active opaque token per app_id — a new POST /app/auth replaces the previous token (last device wins).
 * Strict JWT/HMAC for session payloads: {@see self::is_auth_strict_mode()} (production defaults strict true — divergence D6 vs desktop doc; WP is authoritative).
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_User;
use Apollo\Login\Security\Lockout;
use Apollo\Login\Security\RateLimiter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * App Auth Controller — token auth for desktop apps.
 */
class AppAuthController extends WP_REST_Controller
{

    /**
     * REST namespace.
     *
     * @var string
     */
    protected $namespace = APOLLO_LOGIN_REST_NAMESPACE;

    /**
     * Whitelisted app identifiers.
     *
     * @var string[]
     */
    private const ALLOWED_APPS = array('apollodj');

    /**
     * Token TTL — 30 days in seconds.
     *
     * @var int
     */
    private const TOKEN_TTL = 2592000;
    /**
     * Short-lived JWT TTL for signed session payloads (seconds).
     *
     * @var int
     */
    private const SESSION_JWT_TTL = 900;

    /**
     * Minimum secret length for HS256 session JWT signing key.
     *
     * @var int
     */
    private const SESSION_JWT_SECRET_MIN_LENGTH = 32;

    /**
     * Minimum secret length for session_jwt HMAC (can sit below JWT min; still cryptographically adequate if random).
     *
     * @var int
     */
    private const SESSION_HMAC_SECRET_MIN_LENGTH = 24;

    /**
     * Register routes.
     *
     * @return void
     */
    public function register_routes(): void
    {
        // POST /app/auth — login and get token.
        register_rest_route(
            $this->namespace,
            '/app/auth',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'authenticate'),
                'permission_callback' => static function (WP_REST_Request $request): bool|\WP_Error {
                    $app_id = sanitize_key((string) $request->get_param('app_id'));
                    if (! in_array($app_id, self::ALLOWED_APPS, true)) {
                        return new WP_Error('invalid_app', __('Aplicativo não reconhecido.', 'apollo-login'), array('status' => 403));
                    }
                    return true;
                },
                'args'                => array(
                    'username' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => '\\Apollo\\Login\\apollo_login_sanitize_username_param',
                    ),
                    'password' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => '\\Apollo\\Login\\apollo_login_sanitize_password_param',
                    ),
                    'app_id'   => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // GET /app/verify — validate token from header.
        register_rest_route(
            $this->namespace,
            '/app/verify',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'verify'),
                'permission_callback' => static function (WP_REST_Request $request): bool|\WP_Error {
                    $app_id = sanitize_key((string) $request->get_param('app_id'));
                    if (! in_array($app_id, self::ALLOWED_APPS, true)) {
                        return new WP_Error('invalid_app', __('Aplicativo não reconhecido.', 'apollo-login'), array('status' => 403));
                    }
                    $token = sanitize_text_field((string) $request->get_header('X-Apollo-App-Token'));
                    if (empty($token)) {
                        return new WP_Error('missing_token', __('Token de autenticação não fornecido.', 'apollo-login'), array('status' => 401));
                    }
                    if (! preg_match('/^[a-zA-Z0-9]{64}$/', $token)) {
                        return new WP_Error('invalid_token_format', __('Formato de token inválido.', 'apollo-login'), array('status' => 400));
                    }
                    return true;
                },
                'args'                => array(
                    'app_id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // POST /app/revoke — delete token (logout).
        register_rest_route(
            $this->namespace,
            '/app/revoke',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'revoke'),
                'permission_callback' => static function (WP_REST_Request $request): bool|\WP_Error {
                    $app_id = sanitize_key((string) $request->get_param('app_id'));
                    if (! in_array($app_id, self::ALLOWED_APPS, true)) {
                        return new WP_Error('invalid_app', __('Aplicativo não reconhecido.', 'apollo-login'), array('status' => 403));
                    }
                    $token = sanitize_text_field((string) $request->get_header('X-Apollo-App-Token'));
                    if (empty($token)) {
                        return new WP_Error('missing_token', __('Token de autenticação não fornecido.', 'apollo-login'), array('status' => 401));
                    }
                    if (! preg_match('/^[a-zA-Z0-9]{64}$/', $token)) {
                        return new WP_Error('invalid_token_format', __('Formato de token inválido.', 'apollo-login'), array('status' => 400));
                    }
                    return true;
                },
                'args'                => array(
                    'app_id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // GET /dj/config — public global config (apolloDJ boot check, no auth needed).
        register_rest_route(
            $this->namespace,
            '/dj/config',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'dj_config'),
                'permission_callback' => '__return_true',
            )
        );

        // GET /dj/permissions — per-user feature gates (valid app token required).
        register_rest_route(
            $this->namespace,
            '/dj/permissions',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'dj_permissions'),
                'permission_callback' => static function (WP_REST_Request $request): bool|\WP_Error {
                    $app_id = sanitize_key((string) $request->get_param('app_id'));
                    if (! in_array($app_id, self::ALLOWED_APPS, true)) {
                        return new WP_Error('invalid_app', __('Aplicativo não reconhecido.', 'apollo-login'), array('status' => 403));
                    }
                    $token = sanitize_text_field((string) $request->get_header('X-Apollo-App-Token'));
                    if (empty($token)) {
                        return new WP_Error('missing_token', __('Token de autenticação não fornecido.', 'apollo-login'), array('status' => 401));
                    }
                    if (! preg_match('/^[a-zA-Z0-9]{64}$/', $token)) {
                        return new WP_Error('invalid_token_format', __('Formato de token inválido.', 'apollo-login'), array('status' => 400));
                    }
                    return true;
                },
                'args'                => array(
                    'app_id' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );
    }

    /**
     * POST /app/auth — authenticate user and issue app token.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function authenticate(WP_REST_Request $request)
    {
        $username = \Apollo\Login\apollo_login_sanitize_username_param($request->get_param('username'));
        $password = \Apollo\Login\apollo_login_sanitize_password_param($request->get_param('password'));
        $app_id   = $request->get_param('app_id');

        if (! in_array($app_id, self::ALLOWED_APPS, true)) {
            return new WP_Error(
                'invalid_app',
                __('Aplicativo não reconhecido.', 'apollo-login'),
                array('status' => 403)
            );
        }

        // Rate limiting — atomic counter (audit: transient read-modify-write race).
        $ip            = $this->get_client_ip();
        $rate_bucket   = 'apollo_app_auth_' . md5($ip);
        $attempts      = RateLimiter::get_counter($rate_bucket);

        if ($attempts >= APOLLO_LOGIN_MAX_ATTEMPTS) {
            return new WP_Error(
                'rate_limited',
                __('Muitas tentativas. Aguarde antes de tentar novamente.', 'apollo-login'),
                array('status' => 429)
            );
        }

        $user = \Apollo\Login\apollo_login_authenticate_credentials($username, $password);

        if (is_wp_error($user)) {
            if ('apollo_locked_out' === $user->get_error_code()) {
                return new WP_Error(
                    'rate_limited',
                    $user->get_error_message(),
                    array('status' => 429)
                );
            }

            RateLimiter::increment_counter($rate_bucket, APOLLO_LOGIN_LOCKOUT_DURATION);

            return new WP_Error(
                'login_failed',
                __('Credenciais incorretas.', 'apollo-login'),
                array('status' => 401)
            );
        }

        if (! $this->is_user_role_allowed($user)) {
            return new WP_Error(
                'role_not_allowed',
                __('Seu papel não tem acesso ao apolloDJ.exe.', 'apollo-login'),
                array('status' => 403)
            );
        }

        // Success — clear rate limit and reset lockout tiers.
        RateLimiter::clear_counter($rate_bucket);
        Lockout::reset_tier($user->ID);
        $rate_limiter = new RateLimiter();
        $rate_limiter->reset_ip_tier($ip);

        // Generate opaque token.
        $raw_token       = wp_generate_password(64, false, false);
        $meta_key        = '_apollo_app_token_' . $app_id;
        $meta_key_expiry = $meta_key . '_expiry';

        update_user_meta($user->ID, $meta_key, wp_hash($raw_token));
        update_user_meta($user->ID, $meta_key_expiry, time() + self::TOKEN_TTL);

        // Store device fingerprint if provided.
        $device_id = sanitize_text_field($request->get_header('X-Apollo-Device-Id') ?? '');
        if ($device_id) {
            update_user_meta($user->ID, '_apollo_app_device_' . $app_id, $device_id);
        }

        // Store granting IP for audit trail.
        update_user_meta($user->ID, '_apollo_app_token_' . $app_id . '_ip', $ip);

        // Cache hash→user for fast verify lookup (24h).
        set_transient('apollo_apptk_' . md5(wp_hash($raw_token)), $user->ID, DAY_IN_SECONDS);

        $dj_access = $this->build_dj_access_payload($user);
        $session_bundle = $this->build_session_auth_bundle($user, $app_id, $dj_access, $device_id);
        if (is_wp_error($session_bundle)) {
            return $session_bundle;
        }

        $response = array(
            'success'           => true,
            'token'             => $raw_token,
            'user'              => array(
                'id'           => $user->ID,
                'username'     => $user->user_login,
                'display_name' => $user->display_name,
            ),
            'membership_active' => $dj_access['membership_active'],
            'memberships'       => $dj_access['memberships'],
            'role'              => $dj_access['role'],
            'role_allowed'      => $dj_access['role_allowed'],
            'nicotine_access'   => $dj_access['nicotine_access'],
            'allowed_tabs'      => $dj_access['allowed_tabs'],
        );

        if (! empty($session_bundle)) {
            $response = array_merge($response, $session_bundle);
        }

        return new WP_REST_Response(
            $response,
            200
        );
    }

    /**
     * GET /app/verify — validate app token from header.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function verify(WP_REST_Request $request)
    {
        $app_id = $request->get_param('app_id');

        if (! in_array($app_id, self::ALLOWED_APPS, true)) {
            return new WP_Error(
                'invalid_app',
                __('Aplicativo não reconhecido.', 'apollo-login'),
                array('status' => 403)
            );
        }

        $raw_token = $request->get_header('X-Apollo-App-Token');

        if (empty($raw_token)) {
            return new WP_Error(
                'missing_token',
                __('Token de autenticação não fornecido.', 'apollo-login'),
                array('status' => 401)
            );
        }

        $raw_token = sanitize_text_field($raw_token);

        // Validate token format (64 alphanumeric chars).
        if (! preg_match('/^[a-zA-Z0-9]{64}$/', $raw_token)) {
            return new WP_Error(
                'invalid_token_format',
                __('Formato de token inválido.', 'apollo-login'),
                array('status' => 400)
            );
        }

        $user      = $this->resolve_user_by_token($raw_token, $app_id);

        if (! $user) {
            return new WP_Error(
                'invalid_token',
                __('Token inválido ou expirado.', 'apollo-login'),
                array('status' => 401)
            );
        }

        if (! $this->is_user_role_allowed($user)) {
            return new WP_Error(
                'role_not_allowed',
                __('Seu papel não tem acesso ao apolloDJ.exe.', 'apollo-login'),
                array('status' => 403)
            );
        }

        // Device fingerprint — bind on first sight; reject mismatch when both sides present.
        $device_id   = sanitize_text_field($request->get_header('X-Apollo-Device-Id') ?? '');
        $device_meta = '_apollo_app_device_' . $app_id;
        $stored_device = (string) get_user_meta($user->ID, $device_meta, true);

        if ($device_id && $stored_device && ! hash_equals($stored_device, $device_id)) {
            return new WP_Error(
                'device_mismatch',
                __('Dispositivo não reconhecido. Faça login novamente.', 'apollo-login'),
                array('status' => 401)
            );
        }

        if ($device_id && '' === $stored_device) {
            update_user_meta($user->ID, $device_meta, $device_id);
        }

        // Sliding window — renew expiry on successful verify.
        $meta_key_expiry = '_apollo_app_token_' . $app_id . '_expiry';
        update_user_meta($user->ID, $meta_key_expiry, time() + self::TOKEN_TTL);

        // Audit trail: last verify timestamp.
        update_user_meta($user->ID, '_apollo_app_token_' . $app_id . '_last_verify', time());

        $dj_access = $this->build_dj_access_payload($user);
        $session_bundle = $this->build_session_auth_bundle($user, $app_id, $dj_access, $device_id);
        if (is_wp_error($session_bundle)) {
            return $session_bundle;
        }

        $response = array(
            'valid'             => true,
            'user'              => array(
                'id'           => $user->ID,
                'username'     => $user->user_login,
                'display_name' => $user->display_name,
            ),
            'membership_active' => $dj_access['membership_active'],
            'memberships'       => $dj_access['memberships'],
            'role'              => $dj_access['role'],
            'role_allowed'      => $dj_access['role_allowed'],
            'nicotine_access'   => $dj_access['nicotine_access'],
            'allowed_tabs'      => $dj_access['allowed_tabs'],
        );

        if (! empty($session_bundle)) {
            $response = array_merge($response, $session_bundle);
        }

        return new WP_REST_Response(
            $response,
            200
        );
    }

    /**
     * POST /app/revoke — delete app token (logout).
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function revoke(WP_REST_Request $request)
    {
        $app_id = $request->get_param('app_id');

        if (! in_array($app_id, self::ALLOWED_APPS, true)) {
            return new WP_Error(
                'invalid_app',
                __('Aplicativo não reconhecido.', 'apollo-login'),
                array('status' => 403)
            );
        }

        $raw_token = $request->get_header('X-Apollo-App-Token');

        if (empty($raw_token)) {
            return new WP_Error(
                'missing_token',
                __('Token não fornecido.', 'apollo-login'),
                array('status' => 401)
            );
        }

        $raw_token = sanitize_text_field($raw_token);
        $user      = $this->resolve_user_by_token($raw_token, $app_id);

        if ($user) {
            $meta_key = '_apollo_app_token_' . $app_id;
            delete_user_meta($user->ID, $meta_key);
            delete_user_meta($user->ID, $meta_key . '_expiry');
            delete_user_meta($user->ID, $meta_key . '_ip');
            delete_user_meta($user->ID, $meta_key . '_last_verify');
            delete_user_meta($user->ID, '_apollo_app_device_' . $app_id);
            delete_transient('apollo_apptk_' . md5(wp_hash($raw_token)));
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __('Token revogado.', 'apollo-login'),
            ),
            200
        );
    }

    /**
     * GET /dj/config — public global configuration for apolloDJ.exe.
     * Called on boot before login — no authentication required.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function dj_config(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response(
            array(
                'maintenance_mode' => (bool) get_option('apollo_dj_maintenance_mode', false),
                'min_app_version'  => (string) get_option('apollo_dj_min_version', '1.0.0'),
                'global_message'   => (string) get_option('apollo_dj_global_message', ''),
            ),
            200
        );
    }

    /**
     * GET /dj/permissions — per-user feature gates for apolloDJ.exe.
     * Requires X-Apollo-App-Token header (validated by permission_callback).
     *
     * Response matches apollo_permissions.py permissions_dict contract:
     * allowed_tabs, quantize_level, ultra_mode, daily_limit, premium_until,
     * can_export, can_harmonic, min_accuracy, nicotine_access, membership_active
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function dj_permissions(WP_REST_Request $request)
    {
        $app_id    = sanitize_key((string) $request->get_param('app_id'));
        $raw_token = sanitize_text_field((string) $request->get_header('X-Apollo-App-Token'));

        if (! in_array($app_id, self::ALLOWED_APPS, true)) {
            return new WP_Error(
                'invalid_app',
                __('Aplicativo não reconhecido.', 'apollo-login'),
                array('status' => 403)
            );
        }

        $user = $this->resolve_user_by_token($raw_token, $app_id);
        if (! $user) {
            return new WP_Error(
                'invalid_token',
                __('Token inválido ou expirado.', 'apollo-login'),
                array('status' => 401)
            );
        }

        $dj = $this->build_dj_access_payload($user);

        return new WP_REST_Response(
            array(
                'allowed_tabs'      => $dj['allowed_tabs'],
                'quantize_level'    => $dj['quantize_level'],
                'ultra_mode'        => $dj['ultra_mode'],
                'daily_limit'       => $dj['daily_limit'],
                'premium_until'     => $dj['premium_until'],
                'can_export'        => $dj['can_export'],
                'can_harmonic'      => $dj['can_harmonic'],
                'min_accuracy'      => $dj['min_accuracy'],
                'nicotine_access'   => $dj['nicotine_access'],
                'membership_active' => $dj['membership_active'],
            ),
            200
        );
    }

    /**
     * Resolve WP_User from raw app token.
     *
     * Uses transient cache first, falls back to meta query.
     *
     * @param string $raw_token Raw token from header.
     * @param string $app_id    Application identifier.
     * @return WP_User|null
     */
    private function resolve_user_by_token(string $raw_token, string $app_id): ?WP_User
    {
        $hashed_token = wp_hash($raw_token);
        $meta_key     = '_apollo_app_token_' . $app_id;

        // Fast path: transient cache.
        $cached_uid = get_transient('apollo_apptk_' . md5($hashed_token));
        if ($cached_uid) {
            $stored = get_user_meta((int) $cached_uid, $meta_key, true);
            if ($stored && hash_equals($stored, $hashed_token)) {
                $expiry = (int) get_user_meta((int) $cached_uid, $meta_key . '_expiry', true);
                if ($expiry > time()) {
                    return get_user_by('id', (int) $cached_uid) ?: null;
                }
            }
            delete_transient('apollo_apptk_' . md5($hashed_token));
        }

        // Slow path: meta query.
        $users = get_users(
            array(
                'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value' => $hashed_token, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                'number'     => 1,
            )
        );

        if (empty($users)) {
            return null;
        }

        $user   = $users[0];
        $expiry = (int) get_user_meta($user->ID, $meta_key . '_expiry', true);

        if ($expiry <= time()) {
            delete_user_meta($user->ID, $meta_key);
            delete_user_meta($user->ID, $meta_key . '_expiry');
            return null;
        }

        // Populate cache for next time.
        set_transient('apollo_apptk_' . md5($hashed_token), $user->ID, DAY_IN_SECONDS);

        return $user;
    }

    /**
     * Build apolloDJ access payload for app clients.
     *
     * @param WP_User $user User object.
     * @return array<string, mixed>
     */
    private function build_dj_access_payload(WP_User $user): array
    {
        $memberships       = $this->get_user_memberships($user);
        $access_slugs      = function_exists('apollo_membership_get_user_access_slugs')
            ? apollo_membership_get_user_access_slugs($user->ID)
            : array_values(array_intersect($memberships, array('app-apollodj', 'amigz', 'greatdjs', 'amigxs')));
        $membership_active = in_array('app-apollodj', $access_slugs, true);
        $allowed_nicotine  = $this->get_allowed_nicotine_memberships();
        $nicotine_access   = (bool) array_intersect($access_slugs, $allowed_nicotine);
        $default_tabs      = $nicotine_access ? array('biblioteca', 'transferencias') : array();

        return array(
            'membership_active' => $membership_active,
            'memberships'       => $memberships,
            'role'              => $user->roles[0] ?? '',
            'role_allowed'      => $this->is_user_role_allowed($user),
            'nicotine_access'   => $nicotine_access,
            'allowed_tabs'      => $this->get_dj_user_meta_tabs($user->ID, $default_tabs),
            'quantize_level'    => $this->get_dj_user_meta_string(
                $user->ID,
                '_apollo_dj_quantize_level',
                (string) get_option('apollo_dj_quantize_level', '1/16')
            ),
            'ultra_mode'        => $this->get_dj_user_meta_bool(
                $user->ID,
                '_apollo_dj_ultra_mode',
                $membership_active
            ),
            'daily_limit'       => $this->get_dj_user_meta_int(
                $user->ID,
                '_apollo_dj_daily_limit',
                (int) get_option('apollo_dj_daily_limit', 0)
            ),
            'premium_until'     => $this->get_dj_user_meta_string(
                $user->ID,
                '_apollo_dj_premium_until',
                ''
            ),
            'can_export'        => $this->get_dj_user_meta_bool(
                $user->ID,
                '_apollo_dj_can_export',
                $membership_active
            ),
            'can_harmonic'      => $this->get_dj_user_meta_bool(
                $user->ID,
                '_apollo_dj_can_harmonic',
                $membership_active
            ),
            'min_accuracy'      => $this->get_dj_user_meta_int(
                $user->ID,
                '_apollo_dj_min_accuracy',
                (int) get_option('apollo_dj_min_accuracy', 85)
            ),
        );
    }

    /**
     * Read registered DJ user meta with site/global fallback when unset.
     *
     * @param int    $user_id  User ID.
     * @param string $meta_key Meta key (_apollo_dj_*).
     * @param bool   $default  Fallback when meta not set.
     * @return bool
     */
    private function get_dj_user_meta_bool(int $user_id, string $meta_key, bool $default): bool
    {
        if (! metadata_exists('user', $user_id, $meta_key)) {
            return $default;
        }

        return (bool) get_user_meta($user_id, $meta_key, true);
    }

    /**
     * @param int    $user_id  User ID.
     * @param string $meta_key Meta key.
     * @param int    $default  Fallback when meta not set.
     * @return int
     */
    private function get_dj_user_meta_int(int $user_id, string $meta_key, int $default): int
    {
        if (! metadata_exists('user', $user_id, $meta_key)) {
            return $default;
        }

        return (int) get_user_meta($user_id, $meta_key, true);
    }

    /**
     * @param int    $user_id  User ID.
     * @param string $meta_key Meta key.
     * @param string $default  Fallback when meta not set.
     * @return string
     */
    private function get_dj_user_meta_string(int $user_id, string $meta_key, string $default): string
    {
        if (! metadata_exists('user', $user_id, $meta_key)) {
            return $default;
        }

        return sanitize_text_field((string) get_user_meta($user_id, $meta_key, true));
    }

    /**
     * @param int               $user_id  User ID.
     * @param array<int,string> $fallback Default tabs when meta unset.
     * @return array<int,string>
     */
    private function get_dj_user_meta_tabs(int $user_id, array $fallback): array
    {
        if (! metadata_exists('user', $user_id, '_apollo_dj_allowed_tabs')) {
            return $fallback;
        }

        $val = get_user_meta($user_id, '_apollo_dj_allowed_tabs', true);
        if (! is_array($val) || empty($val)) {
            return $fallback;
        }

        return array_values(array_filter(array_map('sanitize_key', $val)));
    }

    /**
     * Return normalized membership list for a user.
     *
     * @param WP_User $user User object.
     * @return array<int, string>
     */
    private function get_user_memberships(WP_User $user): array
    {
        if (function_exists('apollo_membership_get_user_slugs')) {
            return apollo_membership_get_user_slugs($user->ID);
        }

        $raw = get_user_meta($user->ID, '_apollo_membership', true);

        if (is_array($raw)) {
            return array_values(array_filter(array_map('sanitize_key', $raw)));
        }

        $raw = ! empty($raw) ? sanitize_text_field((string) $raw) : '';
        return $raw ? array(sanitize_key($raw)) : array();
    }

    /**
     * Get memberships configured for nicotine+ tabs.
     *
     * @return array<int, string>
     */
    private function get_allowed_nicotine_memberships(): array
    {
        $configured = get_option('apollo_dj_allowed_memberships_nicotine', array('amigz', 'greatdjs', 'amigxs'));
        $configured = is_array($configured) ? $configured : array();
        $configured = array_values(array_unique(array_map('sanitize_key', $configured)));

        if (empty($configured)) {
            return array('amigz', 'greatdjs');
        }

        return $configured;
    }

    /**
     * Return role list allowed for apolloDJ login.
     *
     * @return array<int, string>
     */
    private function get_allowed_login_roles(): array
    {
        $configured = get_option('apollo_dj_allowed_roles_login', array());
        $configured = is_array($configured) ? $configured : array();
        $configured = array_values(array_unique(array_map('sanitize_key', $configured)));

        return array_filter($configured);
    }

    /**
     * Check if user has at least one role allowed to access apolloDJ.
     *
     * @param WP_User $user User object.
     * @return bool
     */
    private function is_user_role_allowed(WP_User $user): bool
    {
        $allowed_roles = $this->get_allowed_login_roles();
        if (empty($allowed_roles)) {
            return true;
        }

        return (bool) array_intersect($user->roles, $allowed_roles);
    }

    /**
     * Get user badge data for response.
     *
     * @param WP_User $user User object.
     * @return array{type:string,label:string,icon:string,color:string}
     */
    private function get_user_badge_data(WP_User $user): array
    {
        if (function_exists('apollo_get_user_badge_data')) {
            return apollo_get_user_badge_data($user->ID);
        }

        // Fallback if apollo-membership not active.
        $badge = get_user_meta($user->ID, '_apollo_membership', true);
        $badge = ! empty($badge) ? sanitize_text_field($badge) : 'nao-verificado';

        return array(
            'type'    => $badge,
            'label'   => $badge,
            'icon'    => '',
            'color'   => '',
            'ri_icon' => '',
        );
    }

    /**
     * Build signed session auth fields consumed by apolloDJ.exe.
     *
     * Returns empty array in compatibility mode when secrets are missing.
     * Returns WP_Error in strict mode when secrets are missing/weak.
     *
     * @param WP_User $user Authenticated user.
     * @param string  $app_id Application identifier.
     * @param array<string, mixed> $dj_access Access payload.
     * @param string  $device_id Optional client fingerprint.
     * @return array<string, mixed>|WP_Error
     */
    private function build_session_auth_bundle(WP_User $user, string $app_id, array $dj_access, string $device_id = '')
    {
        $strict_mode = $this->is_auth_strict_mode();
        $jwt_secret  = $this->read_runtime_secret('APOLLO_DJ_JWT_SECRET', 'APOLLODJ_JWT_SECRET');

        if (strlen($jwt_secret) < self::SESSION_JWT_SECRET_MIN_LENGTH) {
            if ($strict_mode) {
                return new WP_Error(
                    'jwt_secret_missing',
                    __('JWT secret is missing or too weak for strict mode.', 'apollo-login'),
                    array('status' => 500)
                );
            }
            return array();
        }

        $now = time();
        $exp = $now + self::SESSION_JWT_TTL;

        $claims = array(
            'iss'              => home_url(),
            'aud'              => 'apolloDJ-exe',
            'sub'              => (int) $user->ID,
            'app_id'           => $app_id,
            'iat'              => $now,
            'exp'              => $exp,
            'role'             => $dj_access['role'] ?? '',
            'nicotine_access'  => ! empty($dj_access['nicotine_access']),
            'allowed_tabs'     => isset($dj_access['allowed_tabs']) && is_array($dj_access['allowed_tabs']) ? array_values($dj_access['allowed_tabs']) : array(),
            'device_hash'      => $device_id ? hash('sha256', $device_id) : '',
        );

        $jwt = $this->jwt_encode_hs256($claims, $jwt_secret);
        if ('' === $jwt) {
            if ($strict_mode) {
                return new WP_Error(
                    'jwt_issue_failed',
                    __('Unable to issue JWT in strict mode.', 'apollo-login'),
                    array('status' => 500)
                );
            }
            return array();
        }

        $bundle = array(
            'session_jwt' => $jwt,
            'session_iat' => $now,
            'session_exp' => $exp,
        );

        $hmac_secret = $this->read_runtime_secret('APOLLO_DJ_HMAC_SECRET', 'APOLLODJ_HMAC_SECRET');
        if (strlen($hmac_secret) >= self::SESSION_HMAC_SECRET_MIN_LENGTH) {
            $bundle['session_sig'] = hash_hmac('sha256', $jwt, $hmac_secret);
        } elseif ($strict_mode) {
            return new WP_Error(
                'hmac_secret_missing',
                __('HMAC secret is missing or too weak for strict mode.', 'apollo-login'),
                array('status' => 500)
            );
        }

        return $bundle;
    }

    /**
     * Strict auth mode toggle:
     * - explicit constant/env wins
     * - defaults to strict=true in production.
     */
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

    /**
     * Read secret from constant or environment variable.
     */
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
     * Minimal JWT HS256 encoder (no external dependency).
     *
     * @param array<string, mixed> $claims
     */
    private function jwt_encode_hs256(array $claims, string $secret): string
    {
        $header = array(
            'typ' => 'JWT',
            'alg' => 'HS256',
        );

        $header_json = wp_json_encode($header);
        $claims_json = wp_json_encode($claims);
        if (false === $header_json || false === $claims_json) {
            return '';
        }

        $head = $this->base64url_encode($header_json);
        $body = $this->base64url_encode($claims_json);
        $signing_input = $head . '.' . $body;
        $signature = hash_hmac('sha256', $signing_input, $secret, true);

        return $signing_input . '.' . $this->base64url_encode($signature);
    }

    private function base64url_encode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    /**
     * Get client IP — mirrors AuthController::get_client_ip().
     *
     * @return string
     */
    private function get_client_ip(): string
    {
        $headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ($headers as $header) {
            if (! empty($_SERVER[$header])) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
                $ip = explode(',', sanitize_text_field(wp_unslash($_SERVER[$header])));
                return trim($ip[0]);
            }
        }

        return '0.0.0.0';
    }
}
