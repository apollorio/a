<?php

/**
 * Login Handler
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login\Auth;

// Prevent direct access.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Login Handler class
 */
class LoginHandler
{


	/**
	 * Constructor
	 */
	public function __construct()
	{
		// AJAX login handlers — JS sends action:'apollo_login' to admin-ajax.php
		add_action('wp_ajax_nopriv_apollo_login', array($this, 'handle_ajax_login'));
		add_action('wp_ajax_apollo_login', array($this, 'handle_ajax_login'));

		add_action('wp_ajax_nopriv_apollo_auth_nonce', array($this, 'handle_refresh_nonce'));
		add_action('wp_ajax_apollo_auth_nonce', array($this, 'handle_refresh_nonce'));

		add_filter('authenticate', array($this, 'check_lockout'), 30, 3);
		add_filter('login_errors', array($this, 'custom_login_errors'), 10, 1);
		add_action('wp_login_failed', array($this, 'on_login_failed'));
		add_action('wp_login', array($this, 'on_login_success'), 10, 2);
	}

	/**
	 * Return a fresh auth nonce (for stale cached pages).
	 *
	 * @return void
	 */
	public function handle_refresh_nonce(): void
	{
		wp_send_json_success(
			array(
				'nonce' => wp_create_nonce('apollo_auth_nonce'),
			)
		);
	}

	/**
	 * Handle AJAX login request from /acesso form
	 *
	 * @return void
	 */
	public function handle_ajax_login(): void
	{
		\Apollo\Login\apollo_login_session_log(
			'LoginHandler::handle_ajax_login',
			'ajax_login_entry',
			array(
				'has_log' => isset($_POST['log']) && $_POST['log'] !== '',
				'has_pwd' => isset($_POST['pwd']) && $_POST['pwd'] !== '',
				'doing_ajax' => defined('DOING_AJAX') && DOING_AJAX,
			),
			'H5'
		);

		// Verify nonce (wp_verify_nonce — no referer check; works behind CDN/cache).
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'apollo_auth_nonce')) {
			\Apollo\Login\apollo_login_session_log(
				'LoginHandler::handle_ajax_login',
				'nonce_failed',
				array(
					'has_nonce' => '' !== $nonce,
				),
				'H5'
			);
			wp_send_json_error(
				array(
					'message' => __('Verificação de segurança falhou. Recarregue a página.', 'apollo-login'),
					'code'    => 'nonce_failed',
					'nonce'   => wp_create_nonce('apollo_auth_nonce'),
				),
				403
			);
		}

		$log = isset($_POST['apollo_log'])
			? \Apollo\Login\apollo_login_sanitize_username_param(wp_unslash($_POST['apollo_log']))
			: (isset($_POST['log']) ? sanitize_text_field(wp_unslash($_POST['log'])) : '');
		$pwd = isset($_POST['apollo_pwd'])
			? \Apollo\Login\apollo_login_sanitize_password_param(wp_unslash($_POST['apollo_pwd']))
			: (isset($_POST['pwd']) ? wp_unslash($_POST['pwd']) : '');
		$remember = ! empty($_POST['rememberme']);

		if (empty($log) || empty($pwd)) {
			wp_send_json_error(
				array(
					'message' => __('Preencha todos os campos.', 'apollo-login'),
					'code'    => 'missing_fields',
				),
				400
			);
		}

		// Same flow as apolloDJ POST /app/auth — wp_authenticate first, extended resolver on failure.
		$authenticated = \Apollo\Login\apollo_login_authenticate_credentials($log, $pwd);

		if (is_wp_error($authenticated)) {
			$error_code = $authenticated->get_error_code();

			if ('apollo_locked_out' === $error_code) {
				$remaining = 0;
				$resolved  = \Apollo\Login\apollo_login_resolve_user_identifier($log);
				if ($resolved instanceof \WP_User) {
					$remaining = \Apollo\Login\apollo_lockout_remaining((int) $resolved->ID);
				}
				wp_send_json_error(
					array(
						'message'  => $authenticated->get_error_message(),
						'code'     => 'rate_limited',
						'lockout'  => true,
						'duration' => $remaining,
					),
					429
				);
			}

			$attempts = $this->get_ip_attempts();

			wp_send_json_error(
				array(
					'message'  => __('Credenciais incorretas. Tente novamente.', 'apollo-login'),
					'code'     => 'invalid_credentials',
					'attempts' => $attempts,
					'max'      => APOLLO_LOGIN_MAX_ATTEMPTS,
					'warning'  => $attempts >= (APOLLO_LOGIN_MAX_ATTEMPTS - 1),
				),
				401
			);
		}

		// Block login if email not yet verified (admins + local dev bypass via helper).
		if (\Apollo\Login\apollo_login_must_verify_email_before_login((int) $authenticated->ID)) {
			wp_send_json_error(
				array(
					'message'  => __('E-mail não confirmado. Verifique sua caixa de entrada.', 'apollo-login'),
					'code'     => 'email_not_verified',
					'redirect' => home_url('/verificar-email/?resend=1'),
				),
				403
			);
		}

		// Success — set auth cookie
		wp_set_current_user($authenticated->ID);
		wp_set_auth_cookie($authenticated->ID, $remember);

		// Ensure integrations listening to native login flow are always notified.
		do_action('wp_login', $authenticated->user_login, $authenticated);

		$redirect = apply_filters('apollo_login_redirect', home_url('/feed'), $authenticated);

		\Apollo\Login\apollo_login_session_log(
			'LoginHandler::handle_ajax_login',
			'ajax_login_success',
			array(
				'user_id'  => $authenticated->ID,
				'redirect' => $redirect,
			),
			'H5'
		);

		wp_send_json_success(
			array(
				'message'  => __('Acesso autorizado. Redirecionando...', 'apollo-login'),
				'redirect' => $redirect,
				'user_id'  => $authenticated->ID,
			)
		);
	}

	/**
	 * Get failed login attempts count for current IP
	 *
	 * @return int
	 */
	private function get_ip_attempts(): int
	{
		$ip            = $this->get_client_ip();
		$transient_key = 'apollo_login_attempts_' . md5($ip);
		return (int) get_transient($transient_key);
	}

	/**
	 * Get client IP address
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
			if (! empty($_SERVER[$header])) {
				$ip = explode(',', sanitize_text_field($_SERVER[$header]));
				return trim($ip[0]);
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Custom login error messages
	 *
	 * @param string $error Error message.
	 * @return string
	 */
	public function custom_login_errors(string $error): string
	{
		// Check for specific error codes
		if (strpos($error, 'locked_out') !== false) {
			return __('Conta temporariamente bloqueada devido a múltiplas tentativas falhadas. Tente novamente em alguns minutos.', 'apollo-login');
		}

		if (strpos($error, 'Credenciais incorretas') !== false) {
			return __('Usuário ou senha incorretos. Verifique se digitou corretamente.', 'apollo-login');
		}

		// Return original error if not customized
		return $error;
	}
	public function check_lockout($user, string $username, string $password)
	{
		// Skip if already an error or no username
		if (is_wp_error($user) || empty($username)) {
			return $user;
		}

		// Get user by username or email (case-insensitive for username)
		$user_obj = get_user_by('login', $username);
		if (! $user_obj) {
			$user_obj = get_user_by('email', $username);
		}

		// If still not found, try case-insensitive username search
		if (! $user_obj) {
			$user_obj = get_user_by('login', strtolower($username));
		}
		if (! $user_obj) {
			$user_obj = get_user_by('login', strtoupper($username));
		}

		if (! $user_obj) {
			return $user;
		}

		// Check lockout status.
		if (\Apollo\Login\apollo_is_locked_out($user_obj->ID)) {
			$remaining = \Apollo\Login\apollo_lockout_remaining($user_obj->ID);

			return new \WP_Error(
				'apollo_locked_out',
				sprintf(
					/* translators: %d: seconds remaining. */
					__('Conta bloqueada por segurança. Tente novamente em %d segundos.', 'apollo-login'),
					$remaining
				)
			);
		}

		return $user;
	}

	/**
	 * Handle failed login
	 *
	 * @param string $username Username or email.
	 * @return void
	 */
	public function on_login_failed(string $username): void
	{
		if (! empty($GLOBALS['apollo_login_auth_retry'])) {
			return;
		}

		\Apollo\Login\apollo_log_login_attempt($username, false);

		$user = get_user_by('login', $username);
		if (! $user) {
			$user = get_user_by('email', $username);
		}

		if ($user instanceof \WP_User) {
			do_action('apollo/login/failed_attempt', (int) $user->ID, (string) $this->get_client_ip());
		}
	}

	/**
	 * Handle successful login
	 *
	 * @param string   $username Username.
	 * @param \WP_User $user     User object.
	 * @return void
	 */
	public function on_login_success(string $username, \WP_User $user): void
	{
		// Log successful login
		\Apollo\Login\apollo_log_login_attempt($username, true);

		// Clear lockout
		delete_user_meta($user->ID, '_apollo_lockout_until');
		delete_user_meta($user->ID, '_apollo_login_attempts');

		// Update last login time
		update_user_meta($user->ID, '_apollo_last_login', current_time('mysql'));

		do_action('apollo/login/logged_in', $user->ID, $username);
	}
}
