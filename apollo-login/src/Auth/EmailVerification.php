<?php

/**
 * Email Verification Handler
 *
 * Processes email verification tokens from the /verificar-email virtual page.
 * Works alongside the REST endpoint in AuthController for AJAX-based verification.
 *
 * Flow:
 * 1. User clicks verification link → /verificar-email/?user=X&token=Y
 * 2. parse_request maps to apollo_login_page=verify-email
 * 3. This class intercepts on template_redirect and verifies server-side
 * 4. On success → redirect to /acesso/?verified=success
 * 5. On failure → template renders with error message via global flag
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login\Auth;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Verification class
 */
class EmailVerification {


	/**
	 * Verification result for template consumption.
	 *
	 * @var array{status: string, message: string}|null
	 */
	public static ?array $verification_result = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'process_verification' ), 5 );

		// AJAX handler: resend verification email by email address
		add_action( 'wp_ajax_nopriv_apollo_resend_verification', array( $this, 'ajax_resend_verification' ) );
		add_action( 'wp_ajax_apollo_resend_verification', array( $this, 'ajax_resend_verification' ) );
	}

	/**
	 * Process email verification on the virtual page.
	 *
	 * Virtual pages use query_var, NOT is_page(), hence we only check
	 * get_query_var('apollo_login_page').
	 *
	 * @return void
	 */
	public function process_verification(): void {
		$page = get_query_var( 'apollo_login_page', '' );

		if ( 'verify-email' !== $page ) {
			return;
		}

		$user_id = absint( $_GET['user'] ?? 0 );
		$token   = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		// No token/user in URL → show the resend form (template handles this)
		if ( ! $user_id || empty( $token ) ) {
			return;
		}

		// Rate-limit: max 5 verification attempts per IP per hour
		$ip            = $this->get_client_ip();
		$transient_key = 'apollo_verify_attempts_' . md5( $ip );
		$attempts      = (int) get_transient( $transient_key );

		if ( $attempts >= 5 ) {
			self::$verification_result = array(
				'status'  => 'error',
				'message' => __( 'Muitas tentativas. Aguarde alguns minutos.', 'apollo-login' ),
			);
			return;
		}

		// Increment attempt counter (1-hour window)
		set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );

		// Check user exists
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			self::$verification_result = array(
				'status'  => 'error',
				'message' => __( 'Usuário não encontrado.', 'apollo-login' ),
			);
			return;
		}

		// Already verified?
		if ( get_user_meta( $user_id, '_apollo_email_verified', true ) ) {
			wp_safe_redirect( add_query_arg( 'verified', 'already', \Apollo\Login\apollo_login_canonical_login_url() ) );
			exit;
		}

		// Check token expiry first (to give a specific message)
		$expiry = (int) get_user_meta( $user_id, '_apollo_verification_token_expiry', true );
		if ( $expiry > 0 && time() > $expiry ) {
			self::$verification_result = array(
				'status'  => 'expired',
				'message' => __( 'Link expirado. Solicite um novo e-mail de verificação.', 'apollo-login' ),
			);
			// Clean up expired token
			delete_user_meta( $user_id, '_apollo_verification_token' );
			delete_user_meta( $user_id, '_apollo_verification_token_expiry' );
			return;
		}

		// Verify token (uses hash_equals + TTL check + marks verified)
		if ( \Apollo\Login\apollo_verify_email_token( $user_id, $token ) ) {
			// Success — redirect to login with success flag
			wp_safe_redirect( add_query_arg( 'verified', 'success', \Apollo\Login\apollo_login_canonical_login_url() ) );
			exit;
		}

		// Token invalid
		self::$verification_result = array(
			'status'  => 'error',
			'message' => __( 'Token de verificação inválido.', 'apollo-login' ),
		);
	}

	/**
	 * AJAX: Resend verification email by email address.
	 *
	 * Allows unauthenticated users to request a new verification email
	 * by providing their email address. Rate-limited to 3 per hour per IP.
	 *
	 * @return void
	 */
	public function ajax_resend_verification(): void {
		if ( ! check_ajax_referer( 'apollo_resend_verification', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Sessão expirada. Recarregue a página.', 'apollo-login' ) ), 403 );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'E-mail inválido.', 'apollo-login' ) ), 400 );
		}

		// Rate-limit: 3 resends per IP per hour
		$ip            = $this->get_client_ip();
		$transient_key = 'apollo_resend_verify_ip_' . md5( $ip );
		$attempts      = (int) get_transient( $transient_key );

		if ( $attempts >= 3 ) {
			wp_send_json_error( array( 'message' => __( 'Limite de reenvios atingido. Aguarde 1 hora.', 'apollo-login' ) ), 429 );
		}

		set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );

		// Find user by email
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			// Don't reveal if email exists or not
			wp_send_json_success( array( 'message' => __( 'Se este e-mail estiver cadastrado, enviaremos a verificação.', 'apollo-login' ) ) );
		}

		// Already verified?
		if ( get_user_meta( $user->ID, '_apollo_email_verified', true ) ) {
			wp_send_json_success( array( 'message' => __( 'Este e-mail já está verificado. Faça login normalmente.', 'apollo-login' ) ) );
		}

		// Generate new token (24h TTL)
		$token      = \Apollo\Login\apollo_generate_verification_token( $user->ID );
		$verify_url = add_query_arg(
			array(
				'user'  => $user->ID,
				'token' => $token,
			),
			home_url( '/verificar-email/' )
		);

		// Fire hook for apollo-email (+ wp_mail fallback @99).
		$sent = \Apollo\Login\apollo_login_send_verification_email( $user->ID, $verify_url );

		if ( ! $sent ) {
			wp_send_json_error(
				array( 'message' => __( 'Não foi possível enviar o e-mail agora. Tente novamente em instantes.', 'apollo-login' ) ),
				500
			);
		}

		wp_send_json_success( array( 'message' => __( 'E-mail de verificação reenviado!', 'apollo-login' ) ) );
	}

	/**
	 * Get client IP address (supports proxies/CDN).
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = explode( ',', sanitize_text_field( $_SERVER[ $header ] ) );
				return trim( $ip[0] );
			}
		}

		return '0.0.0.0';
	}
}
