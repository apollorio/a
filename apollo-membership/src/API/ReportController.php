<?php

/**
 * REST API: Report / Contact Controller
 *
 * Endpoints:
 *   POST /report — submit a report/contact form (sends email to oi@apollo.rio.br)
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

namespace Apollo\Membership\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class ReportController {


	private string $namespace;

	/** @var string Target email for reports */
	private const REPORT_EMAIL = 'oi@apollo.rio.br';

	/** @var string Google Forms endpoint for Sheets backup */
	private const GFORM_URL = 'https://docs.google.com/forms/u/0/d/e/1FAIpQLSflexLf5HLHVdCUCUex1LXxKOhQROMGpfDPx7B85tLvohiczA/formResponse';

	/** @var array Google Forms field mapping */
	private const GFORM_FIELDS = array(
		'name'    => 'entry.294491473',
		'email'   => 'entry.317870723',
		'subject' => 'entry.195576492',
		'message' => 'entry.1724857942',
	);

	public function __construct() {
		$this->namespace = defined( 'APOLLO_MEMBERSHIP_REST_NAMESPACE' )
			? APOLLO_MEMBERSHIP_REST_NAMESPACE
			: 'apollo/v1';
	}

	/**
	 * Register REST routes
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/report',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'submit_report' ),
				'permission_callback' => '__return_true', // Public endpoint
				'args'                => array(
					'name'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							return ! empty( trim( $value ) );
						},
					),
					'email'   => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
						'validate_callback' => function ( $value ) {
							return is_email( $value );
						},
					),
					'subject' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'message' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
						'validate_callback' => function ( $value ) {
							return ! empty( trim( $value ) );
						},
					),
				),
			)
		);
	}

	/**
	 * Handle report submission
	 */
	public function submit_report( WP_REST_Request $request ): WP_REST_Response {
		$name    = $request->get_param( 'name' );
		$email   = $request->get_param( 'email' );
		$subject = $request->get_param( 'subject' );
		$message = $request->get_param( 'message' );

		// Map subject codes to labels
		$subject_labels = array(
			'1' => 'Sobre Parceria',
			'2' => 'Problema ou Denúncia',
			'3' => 'Suporte Técnico',
			'4' => 'Elogio ou Crítica',
			'5' => 'Conteúdo impróprio',
			'6' => 'Spam ou fraude',
			'7' => 'Perfil falso',
		);

		$subject_label = $subject_labels[ $subject ] ?? sanitize_text_field( $subject );

		// Optional post_id for content reports
		$post_id = absint( $request->get_param( 'post_id' ) ?? 0 );

		// Build email body
		$body  = "Nova mensagem via Apollo Report Form\n\n";
		$body .= "Nome: {$name}\n";
		$body .= "Email: {$email}\n";
		$body .= "Assunto: {$subject_label}\n";
		$body .= "Mensagem:\n{$message}\n\n";
		$body .= "---\n";
		$body .= 'Enviado em: ' . wp_date( 'd/m/Y H:i:s' ) . "\n";
		$body .= 'IP: ' . sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) . "\n";

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$body        .= "Usuário logado: {$current_user->display_name} (ID: {$current_user->ID})\n";
		}

		if ( $post_id > 0 ) {
			$body .= "Post reportado: #{$post_id} — " . get_the_title( $post_id ) . "\n";
			$body .= 'URL: ' . get_permalink( $post_id ) . "\n";
		}

		// Email headers
		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			"Reply-To: {$name} <{$email}>",
			'From: Apollo Report <noreply@apollo.rio.br>',
		);

		// Use apollo_send_email — single authorised dispatch path
		$email_subject = "[Apollo Report] {$subject_label} — {$name}";

		if ( ! function_exists( 'apollo_send_email' ) ) {
			error_log( '[Apollo Membership] ReportController: apollo_send_email not available — report email NOT sent.' );
			$sent = false;
		} else {
			$sent = apollo_send_email(
				self::REPORT_EMAIL,
				$email_subject,
				'report',
				array(
					'message' => $body,
					'name'    => $name,
					'email'   => $email,
					'subject' => $subject_label,
				)
			);
		}

		if ( $sent ) {
			// ── Google Sheets backup (fire-and-forget) ──
			$this->submit_to_google_sheets( $name, $email, $subject_label, $message, $post_id );

			// Log the report
			if ( function_exists( 'apollo_membership_log' ) ) {
				apollo_membership_log(
					get_current_user_id(),
					'report',
					'contact_form_submitted',
					'report',
					0,
					"Report from {$name} ({$email}): {$subject_label}"
				);
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Mensagem enviada com sucesso.',
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => 'Erro ao enviar mensagem. Tente novamente.',
			),
			500
		);
	}

	/**
	 * Backup submission to Google Sheets via Google Forms (fire-and-forget).
	 *
	 * @param string $name
	 * @param string $email
	 * @param string $subject
	 * @param string $message
	 * @param int    $post_id  Optional post ID for content reports
	 */
	private function submit_to_google_sheets( string $name, string $email, string $subject, string $message, int $post_id = 0 ): void {
		$full_message = $message;
		if ( $post_id > 0 ) {
			$full_message .= " [Post #{$post_id}]";
		}

		$body = array(
			self::GFORM_FIELDS['name']    => $name,
			self::GFORM_FIELDS['email']   => $email,
			self::GFORM_FIELDS['subject'] => $subject,
			self::GFORM_FIELDS['message'] => $full_message,
		);

		wp_remote_post(
			self::GFORM_URL,
			array(
				'body'      => $body,
				'timeout'   => 5,
				'blocking'  => false,  // Non-blocking — don't wait for response
				'sslverify' => false,
			)
		);
	}
}
