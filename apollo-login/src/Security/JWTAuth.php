<?php

/**
 * JWT RS256 Authentication for REST API
 *
 * Issues and validates JSON Web Tokens using RS256 (RSA 2048-bit)
 * for stateless REST API authentication.
 *
 * Configuration (wp-config.php):
 *   define('APOLLO_JWT_PRIVATE_KEY', '-----BEGIN RSA PRIVATE KEY-----\n...');
 *   define('APOLLO_JWT_PUBLIC_KEY',  '-----BEGIN PUBLIC KEY-----\n...');
 *   // Optional: token lifetime in seconds (default: 3600 = 1 hour)
 *   define('APOLLO_JWT_EXPIRY', 3600);
 *
 * Generate keypair:
 *   openssl genrsa -out private.pem 2048
 *   openssl rsa -in private.pem -pubout -out public.pem
 *
 * REST Endpoints (apollo/v1):
 *   POST /auth/token         — Issue JWT + refresh token
 *   POST /auth/token/refresh  — Exchange refresh for new JWT
 *   POST /auth/token/revoke   — Revoke refresh token
 *
 * @package Apollo\Login\Security
 */

declare(strict_types=1);

namespace Apollo\Login\Security;

if (! defined('ABSPATH')) {
    exit;
}

class JWTAuth
{
    /**
     * JWT algorithm
     */
    private const ALG = 'RS256';

    /**
     * Refresh token table (without $wpdb->prefix)
     */
    private const REFRESH_TABLE = 'apollo_jwt_refresh';

    /**
     * Token lifetime in seconds (default 1 hour)
     */
    private int $expiry;

    /**
     * Refresh token lifetime (30 days)
     */
    private const REFRESH_EXPIRY = 2592000;

    private static ?self $instance = null;

    /**
     * True after maybe_create_table() has run once this request (admin_init + rest_api_init both hook it).
     */
    private static bool $refresh_table_bootstrap_done = false;

    private function __construct()
    {
        $this->expiry = defined('APOLLO_JWT_EXPIRY') ? (int) APOLLO_JWT_EXPIRY : 3600;
    }

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize JWT auth system
     */
    public static function init(): void
    {
        $self = self::get_instance();

        // REST API middleware — authenticate JWT on every request
        // Priority 3: must run BEFORE HMACVerifier (priority 5) so user is set
        add_filter('rest_pre_dispatch', array($self, 'authenticate_request'), 3, 3);

        // Register auth endpoints
        add_action('rest_api_init', array($self, 'register_routes'));

        // Ensure refresh token table exists
        add_action('admin_init', array($self, 'maybe_create_table'));
        add_action('rest_api_init', array($self, 'maybe_create_table'));

        // Schedule daily cleanup of expired refresh tokens
        if (! wp_next_scheduled('apollo_jwt_purge_expired')) {
            wp_schedule_event(time(), 'daily', 'apollo_jwt_purge_expired');
        }
        add_action('apollo_jwt_purge_expired', array($self, 'purge_expired_tokens'));
    }

    /**
     * Check if JWT is properly configured
     */
    public function is_configured(): bool
    {
        return defined('APOLLO_JWT_PRIVATE_KEY') && defined('APOLLO_JWT_PUBLIC_KEY');
    }

    // ─────────────────────────────────────────────────────────────────
    // REST Routes
    // ─────────────────────────────────────────────────────────────────

    /**
     * Register REST API routes for token management
     */
    public function register_routes(): void
    {
        $namespace = defined('APOLLO_REST_NAMESPACE') ? APOLLO_REST_NAMESPACE : 'apollo/v1';

        register_rest_route($namespace, '/auth/token', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_token_request'),
            'permission_callback' => '__return_true', // Public by necessity — this route mints the credential. IP lockout enforced in handle_token_request(), sharing login()'s bucket. Added 2026-08-25.
            'args'                => array(
                'email'    => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
                'password' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => '\\Apollo\\Login\\apollo_login_sanitize_password_param',
                ),
            ),
        ));

        register_rest_route($namespace, '/auth/token/refresh', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_refresh_request'),
            'permission_callback' => '__return_true', // UNPROTECTED — no nonce, no rate limit. The refresh token is 256-bit random so it is not guessable; the exposure is unthrottled DB load, and unlimited re-minting if a token ever leaks. plan-003 S-1.
            'args'                => array(
                'refresh_token' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route($namespace, '/auth/token/revoke', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_revoke_request'),
            'permission_callback' => array($this, 'require_auth'),
            'args'                => array(
                'refresh_token' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // Token Operations
    // ─────────────────────────────────────────────────────────────────

    /**
     * Issue a JWT access token + refresh token
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_token_request(\WP_REST_Request $request)
    {
        if (! $this->is_configured()) {
            return new \WP_Error('jwt_not_configured', 'JWT authentication is not configured.', array('status' => 500));
        }

        // ── Lockout. Added 2026-08-25.
        //
        //    This route is the same operation as POST /auth/login — one bare
        //    wp_authenticate() against a caller-supplied email and password — but it
        //    shipped without any of login()'s protection, and a correct guess here mints
        //    a 1-hour JWT *and* a 30-day refresh token. That is a portable credential,
        //    not a session cookie, so this endpoint was the cheaper of the two to attack.
        //
        //    It shares login()'s bucket on purpose. A separate counter would just hand an
        //    attacker a second budget: exhaust /auth/login, switch to /auth/token, start
        //    again from zero. One concept, one declaration — the counter is the concept.
        //    Mirrors AuthController::login() (AuthController.php:355-381).
        //
        //    Every RateLimiter call is guarded by class_exists(). If the class is ever
        //    unavailable the route behaves exactly as it did before this comment existed:
        //    the failure mode is the old behaviour, never a locked-out site.
        //    Firewall is the one owner of "which IP is hitting us" — RateLimiter itself
        //    reads it from there (RateLimiter.php:88, :133). It is guarded here too, so a
        //    missing class degrades to "no lockout", never to a fatal inside an auth route.
        $limiter      = __NAMESPACE__ . '\\RateLimiter';
        $firewall     = __NAMESPACE__ . '\\Firewall';
        $client_ip    = class_exists($firewall) ? Firewall::get_client_ip() : '0.0.0.0';
        $rate_bucket  = 'apollo_login_attempts_' . md5($client_ip);
        $max_attempts = (int) (defined('APOLLO_LOGIN_MAX_ATTEMPTS') ? APOLLO_LOGIN_MAX_ATTEMPTS : 3);
        $lockout      = (int) (defined('APOLLO_LOGIN_LOCKOUT_DURATION') ? APOLLO_LOGIN_LOCKOUT_DURATION : 900);
        $has_limiter  = class_exists($limiter);

        if ($has_limiter && RateLimiter::get_counter($rate_bucket) >= $max_attempts) {
            return new \WP_Error(
                'rate_limited',
                __('Muitas tentativas. Aguarde antes de tentar novamente.', 'apollo-login'),
                array('status' => 429)
            );
        }

        $email    = $request->get_param('email');
        $password = $request->get_param('password');

        // Authenticate user
        $user = wp_authenticate($email, $password);
        if (is_wp_error($user)) {
            if ($has_limiter) {
                RateLimiter::increment_counter($rate_bucket, $lockout);
            }

            return new \WP_Error('invalid_credentials', 'E-mail ou senha inválidos.', array('status' => 401));
        }

        if ($has_limiter) {
            RateLimiter::clear_counter($rate_bucket);
        }

        return $this->issue_tokens($user);
    }

    /**
     * Exchange refresh token for new JWT
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_refresh_request(\WP_REST_Request $request)
    {
        if (! $this->is_configured()) {
            return new \WP_Error('jwt_not_configured', 'JWT authentication is not configured.', array('status' => 500));
        }

        $refresh_token = $request->get_param('refresh_token');
        $user_id = $this->validate_refresh_token($refresh_token);

        if (! $user_id) {
            return new \WP_Error('invalid_refresh_token', 'Token de atualização inválido ou expirado.', array('status' => 401));
        }

        // Revoke old refresh token (rotation)
        $this->revoke_refresh_token($refresh_token);

        $user = get_user_by('id', $user_id);
        if (! $user) {
            return new \WP_Error('user_not_found', 'Usuário não encontrado.', array('status' => 404));
        }

        return $this->issue_tokens($user);
    }

    /**
     * Revoke a refresh token
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_revoke_request(\WP_REST_Request $request): \WP_REST_Response
    {
        $refresh_token = $request->get_param('refresh_token');

        // Verify token belongs to current user before revoking
        $token_user_id = $this->validate_refresh_token($refresh_token);
        if ($token_user_id && $token_user_id !== get_current_user_id()) {
            return new \WP_REST_Response(array('error' => 'Não autorizado.'), 403);
        }

        $this->revoke_refresh_token($refresh_token);

        return new \WP_REST_Response(array('revoked' => true), 200);
    }

    /**
     * Issue JWT + refresh token for a user
     *
     * @param \WP_User $user
     * @return \WP_REST_Response
     */
    private function issue_tokens(\WP_User $user): \WP_REST_Response
    {
        $now = time();
        $jti = bin2hex(random_bytes(16));

        $payload = array(
            'iss'  => home_url(),
            'sub'  => $user->ID,
            'iat'  => $now,
            'exp'  => $now + $this->expiry,
            'jti'  => $jti,
            'role' => $user->roles[0] ?? 'subscriber',
        );

        $jwt = $this->encode($payload);
        if (! $jwt) {
            return new \WP_REST_Response(array('error' => 'Falha ao gerar token.'), 500);
        }

        $refresh = $this->create_refresh_token($user->ID);

        /**
         * Fires when JWT tokens are issued
         *
         * @param int    $user_id The user ID.
         * @param string $jti     The JWT unique identifier.
         */
        do_action('apollo/login/jwt_issued', $user->ID, $jti);

        return new \WP_REST_Response(array(
            'access_token'  => $jwt,
            'token_type'    => 'Bearer',
            'expires_in'    => $this->expiry,
            'refresh_token' => $refresh,
            'user_id'       => $user->ID,
        ), 200);
    }

    // ─────────────────────────────────────────────────────────────────
    // JWT Encode / Decode (adapted from Firebase\JWT\JWT)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Encode a payload into a JWT string using RS256
     *
     * @param array $payload The JWT claims.
     * @return string|false The JWT string or false on failure.
     */
    public function encode(array $payload): string|false
    {
        $private_key = openssl_pkey_get_private(constant('APOLLO_JWT_PRIVATE_KEY'));
        if (! $private_key) {
            return false;
        }

        $header = array('typ' => 'JWT', 'alg' => self::ALG);

        $segments   = array();
        $segments[] = self::base64url_encode((string) wp_json_encode($header));
        $segments[] = self::base64url_encode((string) wp_json_encode($payload));

        $signing_input = implode('.', $segments);
        $signature     = '';
        $success       = openssl_sign($signing_input, $signature, $private_key, OPENSSL_ALGO_SHA256);

        if (! $success) {
            return false;
        }

        $segments[] = self::base64url_encode($signature);
        return implode('.', $segments);
    }

    /**
     * Decode and verify a JWT string using the public key
     *
     * @param string $jwt The JWT string.
     * @return object|false The decoded payload or false on failure.
     */
    public function decode(string $jwt): object|false
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        list($head_b64, $body_b64, $sig_b64) = $parts;

        // Decode header
        $header = json_decode(self::base64url_decode($head_b64));
        if (! $header || ! isset($header->alg) || $header->alg !== self::ALG) {
            return false;
        }

        // Verify signature
        $public_key = openssl_pkey_get_public(constant('APOLLO_JWT_PUBLIC_KEY'));
        if (! $public_key) {
            return false;
        }

        $signature = self::base64url_decode($sig_b64);
        $valid = openssl_verify("{$head_b64}.{$body_b64}", $signature, $public_key, OPENSSL_ALGO_SHA256);
        if ($valid !== 1) {
            return false;
        }

        // Decode payload
        $payload = json_decode(self::base64url_decode($body_b64));
        if (! $payload || ! isset($payload->sub, $payload->exp)) {
            return false;
        }

        // Check expiration (with 30s leeway)
        if ((time() - 30) >= $payload->exp) {
            return false;
        }

        // Check issuer
        if (isset($payload->iss) && $payload->iss !== home_url()) {
            return false;
        }

        return $payload;
    }

    // ─────────────────────────────────────────────────────────────────
    // REST Middleware
    // ─────────────────────────────────────────────────────────────────

    /**
     * Authenticate REST requests via Bearer token
     *
     * @param mixed            $result  Pre-dispatch result.
     * @param \WP_REST_Server  $server  REST server.
     * @param \WP_REST_Request $request The request.
     * @return mixed
     */
    public function authenticate_request(mixed $result, \WP_REST_Server $server, \WP_REST_Request $request): mixed
    {
        // Don't override if already authenticated (cookie auth, nonce, etc.)
        if (get_current_user_id() > 0) {
            return $result;
        }

        // Only process Apollo namespace routes
        $route = $request->get_route();
        if (! str_starts_with($route, '/apollo/')) {
            return $result;
        }

        $auth_header = $request->get_header('Authorization');
        if (empty($auth_header) || ! str_starts_with($auth_header, 'Bearer ')) {
            return $result;
        }

        $token = substr($auth_header, 7);
        if (empty($token) || ! $this->is_configured()) {
            return $result;
        }

        $payload = $this->decode($token);
        if (! $payload || ! isset($payload->sub)) {
            return $result;
        }

        // Set the authenticated user
        wp_set_current_user((int) $payload->sub);

        /**
         * Fires when a user is authenticated via JWT
         *
         * @param int    $user_id The authenticated user ID.
         * @param object $payload The JWT payload.
         */
        do_action('apollo/login/jwt_authenticated', (int) $payload->sub, $payload);

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────
    // Refresh Tokens
    // ─────────────────────────────────────────────────────────────────

    /**
     * Create a refresh token for a user
     *
     * @param int $user_id
     * @return string The opaque refresh token.
     */
    private function create_refresh_token(int $user_id): string
    {
        global $wpdb;

        // Ensure table exists before INSERT — prevents race on first request
        $this->maybe_create_table();

        $token   = bin2hex(random_bytes(32));
        $hashed  = hash('sha256', $token);
        $expires = gmdate('Y-m-d H:i:s', time() + self::REFRESH_EXPIRY);
        $table   = $wpdb->prefix . self::REFRESH_TABLE;

        $wpdb->insert(
            $table,
            array(
                'user_id'    => $user_id,
                'token_hash' => $hashed,
                'expires_at' => $expires,
                'created_at' => current_time('mysql', true),
            ),
            array('%d', '%s', '%s', '%s')
        );

        return $token;
    }

    /**
     * Validate a refresh token and return user ID
     *
     * @param string $token The raw refresh token.
     * @return int|false User ID or false if invalid.
     */
    private function validate_refresh_token(string $token): int|false
    {
        global $wpdb;

        $hashed = hash('sha256', $token);
        $table  = $wpdb->prefix . self::REFRESH_TABLE;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_id, expires_at FROM {$table} WHERE token_hash = %s LIMIT 1",
                $hashed
            )
        );

        if (! $row) {
            return false;
        }

        // Check expiry
        if (strtotime($row->expires_at) < time()) {
            $this->revoke_refresh_token($token);
            return false;
        }

        return (int) $row->user_id;
    }

    /**
     * Revoke a refresh token
     */
    private function revoke_refresh_token(string $token): void
    {
        global $wpdb;

        $hashed = hash('sha256', $token);
        $table  = $wpdb->prefix . self::REFRESH_TABLE;

        $wpdb->delete($table, array('token_hash' => $hashed), array('%s'));
    }

    /**
     * Create refresh token table if not exists
     */
    public function maybe_create_table(): void
    {
        if (self::$refresh_table_bootstrap_done) {
            return;
        }

        global $wpdb;

        $table   = $wpdb->prefix . self::REFRESH_TABLE;
        $charset = $wpdb->get_charset_collate();

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
            self::$refresh_table_bootstrap_done = true;

            return;
        }

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY token_hash (token_hash),
            KEY user_id (user_id),
            KEY expires_at (expires_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        self::$refresh_table_bootstrap_done = true;
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Permission callback: require authenticated user
     */
    public function require_auth(): bool
    {
        return is_user_logged_in();
    }

    /**
     * Purge expired refresh tokens (called via WP-Cron daily)
     */
    public function purge_expired_tokens(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::REFRESH_TABLE;

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return;
        }

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE expires_at < %s",
                gmdate('Y-m-d H:i:s')
            )
        );
    }

    private static function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64url_decode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
