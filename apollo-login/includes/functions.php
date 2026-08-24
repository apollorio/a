<?php

/**
 * Helper Functions
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin instance
 *
 * @return Core\Plugin
 */
function apollo_login(): Core\Plugin
{
    return Core\Plugin::get_instance();
}

/**
 * Canonical front-end login URL (uses APOLLO_LOGIN_CUSTOM_LOGIN_SLUG, default /acesso/).
 */
function apollo_login_canonical_login_url(): string
{
    return trailingslashit(home_url('/' . trim(APOLLO_LOGIN_CUSTOM_LOGIN_SLUG, '/')));
}

/**
 * Canonical register URL (APOLLO_LOGIN_CUSTOM_REGISTER_SLUG, default /registre/).
 */
function apollo_login_register_url(): string
{
    return trailingslashit(home_url('/' . trim(APOLLO_LOGIN_CUSTOM_REGISTER_SLUG, '/')));
}

/**
 * Login URL with query flag that opens the password-recovery overlay (matches login template + overlay JS).
 */
function apollo_login_password_recovery_url(): string
{
    return add_query_arg('quero', 'recuperar-chave', apollo_login_canonical_login_url());
}

/**
 * Path segment after the site (home) path — matches virtual slugs for subdirectory installs.
 *
 * Example: home in /client/ and request /client/registre/ → "registre".
 */
function apollo_login_normalized_request_path(): string
{
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $raw_path    = parse_url($request_uri, PHP_URL_PATH);
    if (! is_string($raw_path) || $raw_path === '') {
        $raw_path = '';
    }
    $path = trim($raw_path, '/');

    $home_path = parse_url(home_url('/'), PHP_URL_PATH);
    if (! is_string($home_path) || $home_path === '') {
        $home_path = '';
    }
    $home_path = trim($home_path, '/');

    if ($home_path !== '' && $path !== '') {
        if ($path === $home_path) {
            $path = '';
        } else {
            $prefix = $home_path . '/';
            if (str_starts_with($path, $prefix)) {
                $path = trim(substr($path, strlen($prefix)), '/');
            }
        }
    }

    return $path;
}

/**
 * Virtual slug => apollo_login_page value (shared by parse_request, wp, template_redirect).
 *
 * @return array<string, string>
 */
function apollo_login_virtual_page_map(): array
{
    $map = array(
        'registre'        => 'register',
        'reset'           => 'reset',
        'verificar-email' => 'verify-email',
        'sair'            => 'logout',
    );

    foreach (APOLLO_LOGIN_LOGIN_SLUG_ALIASES as $login_slug) {
        $login_slug                    = sanitize_key((string) $login_slug);
        $map[ $login_slug ]            = 'login';
        $map[ $login_slug . '/login' ] = 'login';
    }

    return $map;
}

/**
 * Check if user has completed quiz
 *
 * @param int $user_id User ID.
 * @return bool
 */
function apollo_quiz_completed(int $user_id): bool
{
    $score = get_user_meta($user_id, '_apollo_quiz_score', true);
    return ! empty($score) && $score > 0;
}

/**
 * Check if user is locked out
 *
 * @param int $user_id User ID.
 * @return bool
 */
function apollo_is_locked_out(int $user_id): bool
{
    $lockout_until = get_user_meta($user_id, '_apollo_lockout_until', true);

    if (empty($lockout_until)) {
        return false;
    }

    return time() < (int) $lockout_until;
}

/**
 * Get lockout remaining time in seconds
 *
 * @param int $user_id User ID.
 * @return int
 */
function apollo_lockout_remaining(int $user_id): int
{
    $lockout_until = get_user_meta($user_id, '_apollo_lockout_until', true);

    if (empty($lockout_until)) {
        return 0;
    }

    $remaining = (int) $lockout_until - time();
    return max(0, $remaining);
}

/**
 * Log login attempt
 *
 * @param string $username Username or email.
 * @param bool   $success  Success status.
 * @return void
 */
function apollo_log_login_attempt(string $username, bool $success): void
{
    global $wpdb;

    $table = $wpdb->prefix . APOLLO_LOGIN_TABLE_LOGIN_ATTEMPTS;

    $wpdb->insert(
        $table,
        array(
            'username'     => sanitize_text_field($username),
            'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '',
            'success'      => $success ? 1 : 0,
            'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'attempted_at' => current_time('mysql'),
        ),
        array('%s', '%s', '%d', '%s', '%s')
    );
}

/**
 * Get failed login attempts count
 *
 * @param string $identifier Username, email, or IP.
 * @return int
 */
function apollo_get_failed_attempts(string $identifier): int
{
    global $wpdb;

    $table = $wpdb->prefix . APOLLO_LOGIN_TABLE_LOGIN_ATTEMPTS;

    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
			WHERE (username = %s OR ip_address = %s)
			AND success = 0
			AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $identifier,
            $identifier
        )
    );

    return (int) $count;
}

/**
 * Validate CPF (Brazilian document)
 *
 * @param string $cpf CPF number.
 * @return bool
 */
function apollo_validate_cpf(string $cpf): bool
{
    // Remove non-numeric characters
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    // Check if has 11 digits
    if (strlen($cpf) !== 11) {
        return false;
    }

    // Check for known invalid CPFs
    $invalid = array(
        '00000000000',
        '11111111111',
        '22222222222',
        '33333333333',
        '44444444444',
        '55555555555',
        '66666666666',
        '77777777777',
        '88888888888',
        '99999999999',
    );

    if (in_array($cpf, $invalid, true)) {
        return false;
    }

    // Validate check digits
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }

    return true;
}

/**
 * Validate passport number format (registration strict mode).
 *
 * Alphanumeric, 6–20 chars, at least one letter and one digit.
 *
 * @param string $passport Passport number.
 * @return bool
 */
function apollo_validate_passport(string $passport): bool
{
    $passport = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $passport));

    if (strlen($passport) < 6 || strlen($passport) > 20) {
        return false;
    }

    if (! preg_match('/^[A-Z0-9]+$/', $passport)) {
        return false;
    }

    return (bool) preg_match('/[A-Z]/', $passport) && (bool) preg_match('/[0-9]/', $passport);
}

/**
 * Party personality options collected at registration.
 *
 * @return array<string, string> slug => label
 */
function apollo_login_party_roles(): array
{
    return array(
        'smoking_gossip' => __('Always smoking area, gossips and fun', 'apollo-login'),
        'dancing_floor'  => __('Dancing on spaced areas', 'apollo-login'),
        'front_row'      => __('Sure on Front as always', 'apollo-login'),
    );
}

/**
 * Zodiac slug labels (PT).
 *
 * @return array<string, string>
 */
function apollo_login_zodiac_labels(): array
{
    return array(
        'aries'       => __('Áries', 'apollo-login'),
        'taurus'      => __('Touro', 'apollo-login'),
        'gemini'      => __('Gêmeos', 'apollo-login'),
        'cancer'      => __('Câncer', 'apollo-login'),
        'leo'         => __('Leão', 'apollo-login'),
        'virgo'       => __('Virgem', 'apollo-login'),
        'libra'       => __('Libra', 'apollo-login'),
        'scorpio'     => __('Escorpião', 'apollo-login'),
        'sagittarius' => __('Sagitário', 'apollo-login'),
        'capricorn'   => __('Capricórnio', 'apollo-login'),
        'aquarius'    => __('Aquário', 'apollo-login'),
        'pisces'      => __('Peixes', 'apollo-login'),
    );
}

/**
 * Human-readable zodiac label from slug.
 */
function apollo_login_zodiac_label(string $slug): string
{
    $labels = apollo_login_zodiac_labels();

    return $labels[ $slug ] ?? $slug;
}

/**
 * Western zodiac slug from Y-m-d date.
 */
function apollo_login_zodiac_from_date(string $ymd): ?string
{
    $parts = explode('-', $ymd);
    if (count($parts) !== 3) {
        return null;
    }

    $month = (int) $parts[1];
    $day   = (int) $parts[2];

    if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
        return null;
    }

    $ranges = array(
        array( 'capricorn', 1, 1, 1, 19 ),
        array( 'aquarius', 1, 20, 2, 18 ),
        array( 'pisces', 2, 19, 3, 20 ),
        array( 'aries', 3, 21, 4, 19 ),
        array( 'taurus', 4, 20, 5, 20 ),
        array( 'gemini', 5, 21, 6, 20 ),
        array( 'cancer', 6, 21, 7, 22 ),
        array( 'leo', 7, 23, 8, 22 ),
        array( 'virgo', 8, 23, 9, 22 ),
        array( 'libra', 9, 23, 10, 22 ),
        array( 'scorpio', 10, 23, 11, 21 ),
        array( 'sagittarius', 11, 22, 12, 21 ),
        array( 'capricorn', 12, 22, 12, 31 ),
    );

    foreach ($ranges as $range) {
        list($sign, $start_m, $start_d, $end_m, $end_d) = $range;
        if ($month === $start_m && $day >= $start_d) {
            return $sign;
        }
        if ($month === $end_m && $day <= $end_d) {
            return $sign;
        }
        if ($start_m < $end_m && $month > $start_m && $month < $end_m) {
            return $sign;
        }
    }

    return null;
}

/**
 * Validate birth date components for registration (18+).
 *
 * @return array{valid: bool, date: string, zodiac: string, error: string}
 */
function apollo_login_validate_birth_date(int $day, int $month, int $year): array
{
    $empty = array(
        'valid'  => false,
        'date'   => '',
        'zodiac' => '',
        'error'  => '',
    );

    if ($day <= 0 || $month <= 0 || $year <= 0) {
        $empty['error'] = __('Informe dia, mês e ano de nascimento.', 'apollo-login');

        return $empty;
    }

    if (! checkdate($month, $day, $year)) {
        $empty['error'] = __('Data de nascimento inválida.', 'apollo-login');

        return $empty;
    }

    $birth = sprintf('%04d-%02d-%02d', $year, $month, $day);
    $today = new \DateTimeImmutable('today', wp_timezone());
    $born  = \DateTimeImmutable::createFromFormat('Y-m-d', $birth, wp_timezone());

    if (! $born) {
        $empty['error'] = __('Data de nascimento inválida.', 'apollo-login');

        return $empty;
    }

    if ($born > $today) {
        $empty['error'] = __('Data de nascimento não pode ser no futuro.', 'apollo-login');

        return $empty;
    }

    $min_born = $today->sub(new \DateInterval('P' . APOLLO_LOGIN_MIN_REGISTRATION_AGE . 'Y'));
    if ($born > $min_born) {
        $empty['error'] = sprintf(
            /* translators: %d: minimum age */
            __('É necessário ter pelo menos %d anos para se registrar.', 'apollo-login'),
            APOLLO_LOGIN_MIN_REGISTRATION_AGE
        );

        return $empty;
    }

    $zodiac = apollo_login_zodiac_from_date($birth);
    if (null === $zodiac) {
        $empty['error'] = __('Não foi possível calcular o signo.', 'apollo-login');

        return $empty;
    }

    return array(
        'valid'  => true,
        'date'   => $birth,
        'zodiac' => $zodiac,
        'error'  => '',
    );
}

/**
 * Parse birth date from registration request input.
 *
 * @param array<string, mixed> $input Request payload.
 * @return array{valid: bool, date: string, zodiac: string, error: string}
 */
function apollo_login_parse_birth_date_from_input(array $input): array
{
    if (! empty($input['birth_date'])) {
        $raw = sanitize_text_field((string) $input['birth_date']);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return apollo_login_validate_birth_date((int) $m[3], (int) $m[2], (int) $m[1]);
        }
    }

    $day   = isset($input['bday_day']) ? absint($input['bday_day']) : 0;
    $month = isset($input['bday_month']) ? absint($input['bday_month']) : 0;
    $year  = isset($input['bday_year']) ? absint($input['bday_year']) : 0;

    return apollo_login_validate_birth_date($day, $month, $year);
}

/**
 * Generate email verification token with 24-hour TTL
 *
 * Creates a cryptographically secure 32-char token, stores it in user meta
 * alongside an expiry timestamp (24h from now). Replaces any existing token.
 *
 * @param int $user_id User ID.
 * @return string The generated token.
 */
function apollo_generate_verification_token(int $user_id): string
{
    $token = wp_generate_password(32, false);
    update_user_meta($user_id, '_apollo_verification_token', $token);
    update_user_meta($user_id, '_apollo_verification_token_expiry', time() + DAY_IN_SECONDS);
    return $token;
}

/**
 * Verify email verification token
 *
 * Validates the token against stored value AND checks TTL expiry.
 * On success: marks user as verified, deletes token + expiry.
 * On failure: returns false (token invalid, expired, or missing).
 *
 * @param int    $user_id User ID.
 * @param string $token   Token to verify.
 * @return bool  True on successful verification.
 */
function apollo_verify_email_token(int $user_id, string $token): bool
{
    $saved_token = get_user_meta($user_id, '_apollo_verification_token', true);

    if (empty($saved_token) || ! hash_equals($saved_token, $token)) {
        return false;
    }

    // Check expiry (24h TTL)
    $expiry = (int) get_user_meta($user_id, '_apollo_verification_token_expiry', true);
    if ($expiry > 0 && time() > $expiry) {
        // Token expired — clean up
        delete_user_meta($user_id, '_apollo_verification_token');
        delete_user_meta($user_id, '_apollo_verification_token_expiry');
        return false;
    }

    // Mark as verified (badge unchanged — admin/PMPro assigns profile badge).
    update_user_meta($user_id, '_apollo_user_verified', true);
    if (defined('APOLLO_META_EMAIL_VERIFIED')) {
        update_user_meta($user_id, APOLLO_META_EMAIL_VERIFIED, true);
    }
    delete_user_meta($user_id, '_apollo_verification_token');
    delete_user_meta($user_id, '_apollo_verification_token_expiry');

    /**
     * Fires after a user successfully verifies their email.
     *
     * @since 1.0.0
     * @param int $user_id Verified user ID.
     */
    do_action('apollo/login/email_verified', $user_id);

    return true;
}

/**
 * Whether the current request already delivered a verification email.
 *
 * @return bool
 */
function apollo_login_verification_email_was_sent(): bool
{
    return ! empty($GLOBALS['apollo_verification_email_sent']);
}

/**
 * Mark verification email as sent for the current request.
 *
 * @return void
 */
function apollo_login_mark_verification_email_sent(): void
{
    $GLOBALS['apollo_verification_email_sent'] = true;
}

/**
 * Whether login must be blocked until email is verified.
 *
 * BRUTAL POLICY (1.0.41+): any row in {$wpdb->users} with a valid password
 * may log in via /acesso — email gate disabled. Verification remains available
 * as a soft prompt elsewhere; it no longer blocks wp_authenticate success.
 *
 * @param int $user_id Authenticated user ID.
 */
function apollo_login_must_verify_email_before_login(int $user_id): bool
{
    unset($user_id);
    return false;
}

/**
 * Dispatch registration verification email (apollo-email template + wp_mail fallback).
 *
 * Hook chain on `apollo/login/verification_email`:
 * - apollo-email @10 — templated send via apollo_send_email()
 * - RegisterHandler @99 — plain wp_mail() if nothing succeeded yet
 *
 * @param int    $user_id    User ID.
 * @param string $verify_url Full verification URL with token.
 * @return bool True when an provider accepted the message for delivery.
 */
function apollo_login_send_verification_email(int $user_id, string $verify_url): bool
{
    $GLOBALS['apollo_verification_email_sent'] = false;

    do_action('apollo/login/verification_email', $user_id, $verify_url);

    $sent = apollo_login_verification_email_was_sent();

    if (defined('WP_DEBUG') && WP_DEBUG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log(
            sprintf(
                '[apollo-login] verification_email user_id=%d sent=%s apollo-email=%s',
                $user_id,
                $sent ? 'true' : 'false',
                class_exists('Apollo\\Email\\Plugin') ? 'loaded' : 'missing'
            )
        );
    }

    return $sent;
}

/**
 * Whether the current request already delivered a password-reset email.
 *
 * @return bool
 */
function apollo_login_password_reset_email_was_sent(): bool
{
    return ! empty($GLOBALS['apollo_password_reset_email_sent']);
}

/**
 * Mark password-reset email as sent for the current request.
 *
 * @return void
 */
function apollo_login_mark_password_reset_email_sent(): void
{
    $GLOBALS['apollo_password_reset_email_sent'] = true;
}

/**
 * Validate a password-reset token from the email link.
 *
 * @param int    $user_id User ID from the reset URL.
 * @param string $token   Plain token from the reset URL.
 * @return string One of: valid, invalid, expired.
 */
function apollo_login_validate_password_reset_token(int $user_id, string $token): string
{
    if ($user_id <= 0 || $token === '') {
        return 'invalid';
    }

    $stored_hash = get_user_meta($user_id, APOLLO_META_PASSWORD_RESET_TOKEN, true);
    $expires     = (int) get_user_meta($user_id, APOLLO_META_PASSWORD_RESET_EXPIRES, true);

    if (! $stored_hash) {
        return 'invalid';
    }

    if ($expires > 0 && time() > $expires) {
        return 'expired';
    }

    if (! hash_equals((string) $stored_hash, wp_hash($token))) {
        return 'invalid';
    }

    return 'valid';
}

/**
 * Whether live SMTP is enabled (vs Mailpit capture on local).
 *
 * @return bool
 */
function apollo_login_smtp_is_live(): bool
{
    return defined('APOLLO_SMTP_LIVE') && APOLLO_SMTP_LIVE;
}

/**
 * Dispatch password-reset email (apollo-email template + wp_mail fallback).
 *
 * Hook chain on `apollo/login/password_reset_requested`:
 * - apollo-email @10 — templated send via apollo_send_email()
 * - PasswordReset @99 — plain wp_mail() if nothing succeeded yet
 *
 * @param int    $user_id   User ID.
 * @param string $reset_url Full password reset URL with token.
 * @return bool True when a provider accepted the message for delivery.
 */
function apollo_login_send_password_reset_email(int $user_id, string $reset_url): bool
{
    $GLOBALS['apollo_password_reset_email_sent'] = false;

    do_action('apollo/login/password_reset_requested', $user_id, $reset_url);

    $sent = apollo_login_password_reset_email_was_sent();

    if (defined('WP_DEBUG') && WP_DEBUG) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log(
            sprintf(
                '[apollo-login] password_reset_email user_id=%d sent=%s apollo-email=%s',
                $user_id,
                $sent ? 'true' : 'false',
                class_exists('Apollo\\Email\\Plugin') ? 'loaded' : 'missing'
            )
        );
    }

    return $sent;
}

/**
 * Generate a random token
 *
 * @param int $length Token length.
 * @return string
 */
function apollo_login_generate_token(int $length = 64): string
{
    return bin2hex(random_bytes(max(1, intval($length / 2))));
}

/**
 * Send a login-related email via apollo-email or plain wp_mail() fallback.
 *
 * Fires `apollo/login/send_email` so apollo-email can intercept and template
 * the message. When apollo-email is not active the function falls back to a
 * plain-text wp_mail() call so the feature still works without the mailer.
 *
 * @param string $to      Recipient email address.
 * @param string $subject Email subject.
 * @param string $message Plain-text body (used as fallback).
 * @param string $type    Email type slug (e.g. 'password_reset', 'verification').
 * @param array  $data    Extra data passed to apollo-email for templating.
 * @return bool           Whether the email was accepted for delivery.
 */
function apollo_login_send_email(
    string $to,
    string $subject,
    string $message,
    string $type = 'transactional',
    array $data = array()
): bool {
    /**
     * Let apollo-email intercept and send a templated version.
     * Listeners should set the 'intercepted' key to true when they handle it.
     *
     * @param array{to: string, subject: string, message: string, type: string, data: array, intercepted: bool} $payload
     */
    $payload = apply_filters(
        'apollo/login/send_email', // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        array(
            'to'          => $to,
            'subject'     => $subject,
            'message'     => $message,
            'type'        => $type,
            'data'        => $data,
            'intercepted' => false,
        )
    );

    if (! empty($payload['intercepted'])) {
        return true;
    }

    // Plain-text fallback when apollo-email is not active.
    return wp_mail(
        $to,
        $subject,
        $message,
        array('Content-Type: text/plain; charset=UTF-8')
    );
}

/**
 * Session debug log for registration pipeline (WP_DEBUG only).
 *
 * @param string               $location     File:line or gate name.
 * @param string               $message      Short description.
 * @param array<string, mixed> $data         Safe payload (no passwords).
 * @param string               $hypothesisId Hypothesis id from debug plan.
 */
/**
 * Debug session bc295a — login route + auth pipeline (NDJSON).
 *
 * @param string               $location     File:line or gate name.
 * @param string               $message      Short description.
 * @param array<string, mixed> $data         Safe payload (no passwords).
 * @param string               $hypothesisId Hypothesis id from debug plan.
 */
function apollo_login_session_log(string $location, string $message, array $data = array(), string $hypothesisId = ''): void
{
    // Intentionally empty — removed temporary debug instrumentation.
}

function apollo_login_debug_log(string $location, string $message, array $data = array(), string $hypothesisId = ''): void
{
    // Intentionally empty — removed temporary debug instrumentation.
}

/**
 * Whether Apollo runs in local/dev mode (relaxed email gates).
 */
function apollo_login_is_local_dev(): bool
{
    return (defined('APOLLO_DEV_MODE') && APOLLO_DEV_MODE)
        || (function_exists('wp_get_environment_type') && 'local' === wp_get_environment_type());
}

/**
 * REST/AJAX password sanitizer — never use sanitize_text_field (corrupts % and #).
 *
 * @param mixed $value Raw request value.
 */
function apollo_login_sanitize_password_param(mixed $value): string
{
    if (! is_scalar($value)) {
        return '';
    }

    return (string) wp_unslash($value);
}

/**
 * Login identifier sanitizer — trim + unslash; preserve dots and @handles.
 *
 * @param mixed $value Raw request value.
 */
function apollo_login_sanitize_username_param(mixed $value): string
{
    if (! is_scalar($value)) {
        return '';
    }

    return trim((string) wp_unslash($value));
}

/**
 * Resolve a user from email or username (User Registration lost-password pattern).
 */
function apollo_login_resolve_user_by_email_or_login(string $login): ?\WP_User
{
    $login = trim($login);
    if ($login === '') {
        return null;
    }

    if (is_email($login)) {
        $user = get_user_by('email', $login);
        if ($user instanceof \WP_User) {
            return $user;
        }
    }

    $user = get_user_by('login', sanitize_user($login, true));
    if ($user instanceof \WP_User) {
        return $user;
    }

    if (! is_email($login)) {
        return null;
    }

    return null;
}

/**
 * Case-insensitive WordPress user_login lookup.
 */
function apollo_login_get_user_by_login_ci(string $login): ?\WP_User
{
    $login = trim($login);
    if ($login === '') {
        return null;
    }

    $user = get_user_by('login', $login);
    if ($user instanceof \WP_User) {
        return $user;
    }

    global $wpdb;
    $user_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} WHERE LOWER(user_login) = LOWER(%s) LIMIT 1",
            $login
        )
    );

    if ($user_id) {
        $user = get_user_by('id', (int) $user_id);
        if ($user instanceof \WP_User) {
            return $user;
        }
    }

    return null;
}

/**
 * Resolve user by extended identifiers (CPF, passport, phone, @handle).
 * Used only after wp_authenticate fails with the raw identifier.
 */
function apollo_login_resolve_user_identifier(string $identifier): ?\WP_User
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }

    if (is_email($identifier)) {
        $user = get_user_by('email', $identifier);
        if ($user instanceof \WP_User) {
            return $user;
        }
    }

    $clean = ltrim($identifier, '@');
    $user  = apollo_login_get_user_by_login_ci($clean);
    if ($user instanceof \WP_User) {
        return $user;
    }

    $cpf_clean = preg_replace('/\D/', '', $identifier);
    if (strlen($cpf_clean) === 11) {
        $users = get_users(
            array(
                'meta_key'   => '_apollo_cpf',
                'meta_value' => $cpf_clean,
                'number'     => 1,
            )
        );
        if (! empty($users)) {
            return $users[0];
        }
    }

    // Strict passport format only — avoids collisions with short usernames like "roots".
    if (apollo_validate_passport($identifier)) {
        $passport_upper = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $identifier));
        $users          = get_users(
            array(
                'meta_key'    => '_apollo_passport',
                'meta_value'  => $passport_upper,
                'number'      => 1,
                'count_total' => false,
            )
        );
        if (! empty($users)) {
            return $users[0];
        }
    }

    $normalized_phone = function_exists('apollo_telegram_normalize_phone')
        ? apollo_telegram_normalize_phone($identifier)
        : null;
    if ($normalized_phone) {
        $users = get_users(
            array(
                'meta_key'   => APOLLO_META_PHONE,
                'meta_value' => $normalized_phone,
                'number'     => 1,
            )
        );
        if (! empty($users)) {
            return $users[0];
        }
    }

    return null;
}

/**
 * Authenticate credentials — mirrors AppAuthController (/app/auth) with extended identifier fallback.
 *
 * @return \WP_User|\WP_Error
 */
function apollo_login_authenticate_credentials(string $identifier, string $password)
{
    $identifier = trim((string) wp_unslash($identifier));
    $password   = (string) wp_unslash($password);
    if ($identifier === '' || $password === '') {
        return new \WP_Error('empty_credentials', __('Credenciais incompletas.', 'apollo-login'));
    }

    // Bypass Loginizer IP/user lock during Apollo auth so every wp_users row
    // can authenticate with a valid password (Loginizer still hardens wp-login.php).
    $loginizer_priority = has_filter('authenticate', 'loginizer_wp_authenticate');
    if (false !== $loginizer_priority) {
        remove_filter('authenticate', 'loginizer_wp_authenticate', (int) $loginizer_priority);
    }

    try {
        // Step 1: WordPress core auth (same as apolloDJ POST /app/auth).
        $user = wp_authenticate($identifier, $password);
        if ($user instanceof \WP_User) {
            return $user;
        }

        if (! is_wp_error($user)) {
            return new \WP_Error('invalid_credentials', __('Credenciais incorretas.', 'apollo-login'));
        }

        $primary_error = $user;
        if ('apollo_locked_out' === $primary_error->get_error_code()) {
            return $primary_error;
        }

        // Step 2: CPF, passport, phone, @handle — retry with canonical user_login.
        $resolved = apollo_login_resolve_user_identifier($identifier);
        if (! $resolved instanceof \WP_User) {
            return $primary_error;
        }

        $needle = ltrim($identifier, '@');

        // Case-only mismatch: verify password without a second wp_authenticate (avoids double lockout).
        if (strcasecmp($resolved->user_login, $needle) === 0) {
            if ($resolved->user_login !== $needle && wp_check_password($password, $resolved->user_pass, $resolved->ID)) {
                if (apollo_is_locked_out($resolved->ID)) {
                    $remaining = apollo_lockout_remaining($resolved->ID);

                    return new \WP_Error(
                        'apollo_locked_out',
                        sprintf(
                            /* translators: %d: seconds remaining. */
                            __('Conta bloqueada por segurança. Tente novamente em %d segundos.', 'apollo-login'),
                            $remaining
                        )
                    );
                }

                return $resolved;
            }

            return $primary_error;
        }

        $GLOBALS['apollo_login_auth_retry'] = true;
        $user                               = wp_authenticate($resolved->user_login, $password);
        $GLOBALS['apollo_login_auth_retry'] = false;

        if ($user instanceof \WP_User) {
            return $user;
        }

        if (is_wp_error($user) && 'apollo_locked_out' === $user->get_error_code()) {
            return $user;
        }

        return $primary_error;
    } finally {
        if (false !== $loginizer_priority) {
            add_filter('authenticate', 'loginizer_wp_authenticate', (int) $loginizer_priority, 3);
        }
    }
}

/**
 * Explain why registration blocked an existing email (admin / unverified / verified).
 *
 * @return array{code: string, message: string, action_url: string}
 */
function apollo_login_email_registration_conflict(int $existing_id): array
{
    $login_url = apollo_login_canonical_login_url();
    $verify_url = home_url('/verificar-email/?resend=1');

    if ($existing_id <= 0) {
        return array(
            'code'        => 'email_taken',
            'message'     => __('Este e-mail já está registrado. Faça login em /acesso/.', 'apollo-login'),
            'action_url'  => $login_url,
        );
    }

    if (user_can($existing_id, 'manage_options')) {
        return array(
            'code'       => 'email_taken_admin',
            'message'    => __('Este e-mail pertence à conta administrativa do WordPress (instalação do site), não a um cadastro Apollo. Use /acesso/ para entrar ou recuperar senha.', 'apollo-login'),
            'action_url' => $login_url,
        );
    }

    if (! get_user_meta($existing_id, APOLLO_META_EMAIL_VERIFIED, true)) {
        return array(
            'code'       => 'email_taken_unverified',
            'message'    => __('Este e-mail já iniciou cadastro mas o link de confirmação ainda não foi validado. Acesse /verificar-email/ para reenviar.', 'apollo-login'),
            'action_url' => $verify_url,
        );
    }

    return array(
        'code'       => 'email_taken_verified',
        'message'    => __('Este e-mail já possui conta confirmada. Faça login em /acesso/.', 'apollo-login'),
        'action_url' => $login_url,
    );
}
