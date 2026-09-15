<?php
/**
 * POST /apollo/v1/wa/webhook
 * Pipeline: docs/WEBHOOK-SECURITY.md — fail closed.
 * Never approves the join queue.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Webhook {

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	public function register_route(): void {
		register_rest_route(
			'apollo/v1',
			'/wa/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'verify' ),
			)
		);
	}

	public function verify( WP_REST_Request $request ) {
		return false;
	}

	public function handle( WP_REST_Request $request ) {
		return new WP_REST_Response( array( 'ok' => false ), 401 );
	}
}
