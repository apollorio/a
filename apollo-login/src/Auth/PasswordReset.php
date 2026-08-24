<?php

/**
 * Password Reset Handler
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
 * Password Reset class
 */
class PasswordReset
{



	/**
	 * Constructor
	 */
	public function __construct()
	{
		// AJAX handlers for password reset
		add_action('wp_ajax_nopriv_apollo_forgot_password', array($this, 'handle_forgot_password'));
		add_action('wp_ajax_apollo_forgot_password', array($this, 'handle_forgot_password'));
		add_action('wp_ajax_nopriv_apollo_reset_confirm', array($this, 'handle_reset_confirm'));
		add_action('wp_ajax_apollo_reset_confirm', array($this, 'handle_reset_confirm'));

		// Fallback reset email — wp_mail only when apollo-email did not send @10.
		add_action('apollo/login/password_reset_requested', array($this, 'send_password_reset_fallback'), 99, 2);
	}

	/**
	 * Plain-text fallback when apollo-email template send failed or plugin inactive.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $reset_url Full password reset URL with token.
	 * @return void
	 */
	public function send_password_reset_fallback(int $user_id, string $reset_url): void
	{
		if (\Apollo\Login\apollo_login_password_reset_email_was_sent()) {
			return;
		}

		$user = get_userdata($user_id);
		if (! $user) {
			return;
		}

		$to      = sanitize_email($user->user_email);
		$name    = sanitize_text_field($user->display_name ?: $user->user_login);
		$subject = __('Sua nova chave de acesso — Apollo Rio', 'apollo-login');

		/* translators: 1: user display name, 2: password reset URL */
		$body = sprintf(
			__(
				"Olá %1\$s,\n\nRedefina sua senha clicando no link abaixo:\n\n%2\$s\n\nO link expira em 1 hora.\n\nApollo",
				'apollo-login'
			),
			$name,
			esc_url_raw($reset_url)
		);

		$headers = array('Content-Type: text/plain; charset=UTF-8');

		if (wp_mail($to, $subject, $body, $headers)) {
			\Apollo\Login\apollo_login_mark_password_reset_email_sent();
		}
	}

	/**
	 * Handle forgot password AJAX request
	 *
	 * @return void
	 */
	public function handle_forgot_password(): void
	{
		/*
		 * apollo-email recover path (AJAX):
		 *   POST admin-ajax.php  action=apollo_forgot_password
		 *   → PasswordReset::handle_forgot_password()
		 *   → apollo_login_send_password_reset_email()
		 *   → do_action( 'apollo/login/password_reset_requested' )
		 *   → apollo-email Plugin::onPasswordResetRequested @10 (apollo_send_email password-reset)
		 *   → PasswordReset::send_password_reset_fallback @99 (wp_mail if template failed)
		 */
		// Verify nonce
		if (
			! isset($_POST['apollo_forgot_password_nonce']) ||
			! wp_verify_nonce($_POST['apollo_forgot_password_nonce'], 'apollo_forgot_password_action')
		) {
			wp_send_json_error(__('Verificação de segurança falhou.', 'apollo-login'), 403);
		}

		// Form field is name="user_email" (id forgot_email); accept legacy forgot_email too.
		$raw_email = '';
		if (isset($_POST['user_email'])) {
			$raw_email = wp_unslash($_POST['user_email']);
		} elseif (isset($_POST['forgot_email'])) {
			$raw_email = wp_unslash($_POST['forgot_email']);
		}
		$login_input = sanitize_text_field((string) $raw_email);

		if ($login_input === '') {
			wp_send_json_error(__('Informe seu e-mail ou nome de usuário.', 'apollo-login'), 400);
		}

		// UR pattern: accept email or username in the same field.
		$user = \Apollo\Login\apollo_login_resolve_user_by_email_or_login($login_input);
		$email_sent = false;
		$reset_url  = '';
		$user_found = false;

		if ($user) {
			$user_found = true;

			/** This filter is documented in wp-includes/user.php. */
			$allow = apply_filters('allow_password_reset', true, $user->ID);

			if ($allow && ! is_wp_error($allow)) {
				$token   = \Apollo\Login\apollo_login_generate_token(32);
				$expires = time() + 3600; // 1 hour

				// Store hashed token
				update_user_meta($user->ID, APOLLO_META_PASSWORD_RESET_TOKEN, wp_hash($token));
				update_user_meta($user->ID, APOLLO_META_PASSWORD_RESET_EXPIRES, $expires);

				$reset_url = add_query_arg(
					array(
						'token'   => $token,
						'user_id' => $user->ID,
					),
					home_url('/' . APOLLO_LOGIN_PAGE_RESET . '/')
				);

				/**
				 * Fires when a password reset is requested — triggers apollo-email.
				 *
				 * @since 1.0.0
				 * @param int    $user_id   User ID.
				 * @param string $reset_url Password reset URL.
				 */
				$email_sent = \Apollo\Login\apollo_login_send_password_reset_email($user->ID, $reset_url);

				if (! $email_sent && defined('WP_DEBUG') && WP_DEBUG) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log(sprintf('[apollo-login] password_reset email failed for user #%d — both apollo-email and wp_mail fallback returned false.', $user->ID));
				}
			}
		}

		$success_data = array(
			'message'    => __('E-mail enviado. Se existir em nossa base, você receberá instruções de recuperação em instantes.', 'apollo-login'),
			'email_sent' => $email_sent,
		);

		if ($user_found && ! $email_sent && defined('WP_DEBUG') && WP_DEBUG) {
			$success_data['delivery_failed'] = true;
		}

		if ($email_sent && $reset_url && \Apollo\Login\apollo_login_is_local_dev()) {
			if (! \Apollo\Login\apollo_login_smtp_is_live()) {
				$success_data['dev_reset_url']   = $reset_url;
				$success_data['local_mail_hint'] = __('Ambiente local: o e-mail vai para o Mailpit do Local (não para Gmail). Use o link abaixo ou abra Mailpit no app Local.', 'apollo-login');
			} else {
				$success_data['local_mail_hint'] = __('Verifique sua caixa de entrada e a pasta de spam.', 'apollo-login');
			}
		}

		wp_send_json_success($success_data);
	}

	/**
	 * Handle password reset confirmation
	 *
	 * @return void
	 */
	public function handle_reset_confirm(): void
	{
		// Verify nonce
		if (
			! isset($_POST['nonce']) ||
			! wp_verify_nonce($_POST['nonce'], 'apollo_reset_confirm_action')
		) {
			wp_send_json_error(
				array(
					'message' => __('Verificação de segurança falhou.', 'apollo-login'),
				),
				403
			);
		}

		$token        = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
		$user_id      = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
		$new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

		if (empty($token) || ! $user_id || empty($new_password)) {
			wp_send_json_error(
				array(
					'message' => __('Dados incompletos.', 'apollo-login'),
				),
				400
			);
		}

		if (strlen($new_password) < 8) {
			wp_send_json_error(
				array(
					'message' => __('A senha deve ter pelo menos 8 caracteres.', 'apollo-login'),
				),
				400
			);
		}

		$stored_hash = get_user_meta($user_id, APOLLO_META_PASSWORD_RESET_TOKEN, true);
		$expires     = (int) get_user_meta($user_id, APOLLO_META_PASSWORD_RESET_EXPIRES, true);

		if (! $stored_hash || ! hash_equals((string) $stored_hash, wp_hash($token))) {
			wp_send_json_error(
				array(
					'message' => __('Token inválido ou expirado.', 'apollo-login'),
				),
				400
			);
		}

		if (time() > $expires) {
			delete_user_meta($user_id, APOLLO_META_PASSWORD_RESET_TOKEN);
			delete_user_meta($user_id, APOLLO_META_PASSWORD_RESET_EXPIRES);
			wp_send_json_error(
				array(
					'message' => __('Token expirado. Solicite um novo link de recuperação.', 'apollo-login'),
				),
				400
			);
		}

		// Reset password
		wp_set_password($new_password, $user_id);

		// Clean up tokens
		delete_user_meta($user_id, APOLLO_META_PASSWORD_RESET_TOKEN);
		delete_user_meta($user_id, APOLLO_META_PASSWORD_RESET_EXPIRES);

		wp_send_json_success(
			array(
				'message' => __('Senha alterada com sucesso! Você já pode fazer login.', 'apollo-login'),
			)
		);
	}
}
