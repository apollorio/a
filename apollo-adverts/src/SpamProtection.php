<?php

/**
 * Apollo Adverts — Spam Protection.
 *
 * Anti-spam measures for classified submissions: honeypot, time-trap,
 * and data collection for analysis.
 * Adapted from WPAdverts snippets spam-farmer, honeypot, timetrap.
 *
 * @package Apollo\Adverts
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Adverts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Spam Protection for classifieds.
 *
 * @since 1.1.0
 */
class SpamProtection {


	/**
	 * Minimum seconds before form can be submitted (time trap).
	 */
	private const MIN_SUBMIT_TIME = 5;

	/**
	 * Honeypot field name (hidden field bots fill in).
	 */
	private const HONEYPOT_FIELD = 'apollo_website_url';

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// Add honeypot and time-trap to forms.
		add_action( 'apollo/classifieds/form/after_fields', array( $this, 'render_hidden_fields' ) );

		// Validate on submission.
		add_filter( 'apollo/classifieds/validate_submission', array( $this, 'validate_spam' ), 5, 2 );

		// REST API validation.
		add_filter( 'apollo/classifieds/pre_create', array( $this, 'validate_spam_rest' ), 5, 2 );

		// Log suspicious submissions.
		add_action( 'apollo/classifieds/spam_detected', array( $this, 'log_spam' ), 10, 2 );
	}

	/**
	 * Render hidden honeypot and time-trap fields.
	 *
	 * Adapted from WPAdverts honeypot/timetrap snippets.
	 */
	public function render_hidden_fields(): void {
		$token = $this->generate_time_token();
		?>
		<!-- Spam protection — do not fill -->
		<div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
			<label for="<?php echo esc_attr( self::HONEYPOT_FIELD ); ?>"><?php esc_html_e( 'Website', 'apollo-adverts' ); ?></label>
			<input type="text"
				name="<?php echo esc_attr( self::HONEYPOT_FIELD ); ?>"
				id="<?php echo esc_attr( self::HONEYPOT_FIELD ); ?>"
				value=""
				tabindex="-1"
				autocomplete="off" />
		</div>
		<input type="hidden"
			name="apollo_ts"
			value="<?php echo esc_attr( $token ); ?>" />
		<?php
	}

	/**
	 * Validate spam protection fields.
	 *
	 * @param array $errors Current validation errors.
	 * @param int   $post_id Post ID.
	 * @return array
	 */
	public function validate_spam( array $errors, int $post_id ): array {
		// 1. Honeypot check — if the hidden field has a value, it's a bot.
		if ( ! empty( $_POST[ self::HONEYPOT_FIELD ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$errors[] = array(
				'field'   => 'spam',
				'message' => __( 'Detecção de spam ativada.', 'apollo-adverts' ),
			);

			/**
			 * Fires when spam is detected.
			 *
			 * @param int    $post_id Post ID.
			 * @param string $method  Detection method.
			 */
			do_action( 'apollo/classifieds/spam_detected', $post_id, 'honeypot' );
			return $errors;
		}

		// 2. Time-trap check — form submitted too fast.
		if ( isset( $_POST['apollo_ts'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$submitted_time = $this->decode_time_token( sanitize_text_field( wp_unslash( $_POST['apollo_ts'] ) ) );
			if ( $submitted_time && ( time() - $submitted_time ) < self::MIN_SUBMIT_TIME ) {
				$errors[] = array(
					'field'   => 'spam',
					'message' => __( 'O formulário foi enviado rápido demais. Tente novamente.', 'apollo-adverts' ),
				);

				do_action( 'apollo/classifieds/spam_detected', $post_id, 'timetrap' );
			}
		}

		return $errors;
	}

	/**
	 * Validate spam on REST API submissions.
	 *
	 * @param bool             $can_create Whether creation is allowed.
	 * @param \WP_REST_Request $request    REST request.
	 * @return bool|\WP_Error
	 */
	public function validate_spam_rest( bool $can_create, \WP_REST_Request $request ) {
		if ( ! $can_create ) {
			return $can_create;
		}

		// Check honeypot in request body.
		$honeypot = $request->get_param( self::HONEYPOT_FIELD );
		if ( ! empty( $honeypot ) ) {
			do_action( 'apollo/classifieds/spam_detected', 0, 'honeypot_rest' );
			return new \WP_Error(
				'apollo_adverts_spam',
				__( 'Detecção de spam ativada.', 'apollo-adverts' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Log spam detection.
	 *
	 * Adapted from WPAdverts WPAdverts_Spam_Farmer.
	 *
	 * @param int    $post_id Post ID (0 if pre-creation).
	 * @param string $method  Detection method.
	 */
	public function log_spam( int $post_id, string $method ): void {
		$data = array(
			'time'       => current_time( 'mysql' ),
			'method'     => $method,
			'post_id'    => $post_id,
			'ip'         => $this->get_client_ip(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] )
				? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
				: '',
			'user_id'    => get_current_user_id(),
			'referer'    => wp_get_referer() ?: '',
		);

		// Store in option (ring buffer of last 100 entries).
		$log   = get_option( 'apollo_adverts_spam_log', array() );
		$log[] = $data;

		// Keep only last 100 entries.
		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}

		update_option( 'apollo_adverts_spam_log', $log, false );

		// Also log to error_log for debugging.
		if ( function_exists( 'apollo_adverts_log' ) ) {
			apollo_adverts_log( 'spam_detected', $data );
		}
	}

	/**
	 * Generate a time-based token.
	 *
	 * @return string
	 */
	private function generate_time_token(): string {
		return base64_encode( (string) time() . '|' . wp_generate_password( 8, false ) );
	}

	/**
	 * Decode a time token.
	 *
	 * @param string $token Encoded token.
	 * @return int|null Timestamp or null on failure.
	 */
	private function decode_time_token( string $token ): ?int {
		$decoded = base64_decode( $token, true );
		if ( ! $decoded ) {
			return null;
		}

		$parts = explode( '|', $decoded, 2 );
		if ( count( $parts ) < 2 ) {
			return null;
		}

		$timestamp = (int) $parts[0];
		if ( $timestamp < 1000000000 ) {
			return null;
		}

		return $timestamp;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs.
				if ( str_contains( $ip, ',' ) ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}