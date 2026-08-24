<?php
/**
 * Apollo Pane Mode REST Controller
 *
 * Endpoints:
 *   GET  /apollo/v1/pane-mode/status  — public booleans only (no internal paths/keys)
 *   POST /apollo/v1/pane-mode/enable  — admin: enable mode + auto-activate plugin
 *   POST /apollo/v1/pane-mode/disable — admin: disable mode
 *
 * @package Apollo\Core\API
 * @since   6.1.0
 */

namespace Apollo\Core\API;

use Apollo\Core\PaneModeAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaneModeController extends RestBase {

	public function __construct() {
		parent::__construct();
		$this->register_routes();
	}

	public function register_routes(): void {
		// Status — public booleans only (frontend needs mode flag without leaking internals).
		register_rest_route(
			$this->namespace,
			'/pane-mode/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => '__return_true',
			)
		);

		// Enable — admin only.
		register_rest_route(
			$this->namespace,
			'/pane-mode/enable',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'enable_mode' ),
				'permission_callback' => array( $this, 'check_admin' ),
			)
		);

		// Disable — admin only.
		register_rest_route(
			$this->namespace,
			'/pane-mode/disable',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'disable_mode' ),
				'permission_callback' => array( $this, 'check_admin' ),
			)
		);
	}

	/* ── Permission callbacks ─────────────────────────────── */

	public function check_admin(): bool|\WP_Error {
		return current_user_can( 'manage_options' )
			? true
			: $this->prepare_error( 'rest_forbidden', 'Insufficient permissions.', 403 );
	}

	/* ── Callbacks ────────────────────────────────────────── */

	public function get_status( \WP_REST_Request $request ): \WP_REST_Response {
		// P1 hardening: public consumers only need booleans — never expose plugin path or option keys.
		$full = PaneModeAdapter::get_status();

		return $this->prepare_response(
			array(
				'mode_active'   => ! empty( $full['mode_active'] ),
				'plugin_active' => ! empty( $full['plugin_active'] ),
			)
		);
	}

	public function enable_mode( \WP_REST_Request $request ): \WP_REST_Response {
		PaneModeAdapter::enable();

		return $this->prepare_response(
			array_merge(
				PaneModeAdapter::get_status(),
				array( 'message' => 'Pane mode enabled. apollo-pane-engine activated.' )
			)
		);
	}

	public function disable_mode( \WP_REST_Request $request ): \WP_REST_Response {
		PaneModeAdapter::disable();

		return $this->prepare_response(
			array_merge(
				PaneModeAdapter::get_status(),
				array( 'message' => 'Pane mode disabled.' )
			)
		);
	}
}
