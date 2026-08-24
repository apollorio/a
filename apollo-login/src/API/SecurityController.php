<?php

/**
 * Security REST Controller
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

// Prevent direct access.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Security Controller class
 */
class SecurityController extends WP_REST_Controller
{

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = APOLLO_LOGIN_REST_NAMESPACE;

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes(): void
	{
		// GET /security/rewrites (admin only)
		register_rest_route(
			$this->namespace,
			'/security/rewrites',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_rewrites'),
				'permission_callback' => array($this, 'admin_only'),
			)
		);

		// GET /security/attempts (admin only)
		register_rest_route(
			$this->namespace,
			'/security/attempts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_attempts'),
				'permission_callback' => array($this, 'admin_only'),
				'args'                => array(
					'limit' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 50,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Admin only permission callback
	 *
	 * @return bool
	 */
	public function admin_only(): bool
	{
		return current_user_can('manage_options');
	}

	/**
	 * Get URL rewrites
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_rewrites(WP_REST_Request $request): WP_REST_Response
	{
		global $wpdb;

		$table = $wpdb->prefix . \APOLLO_LOGIN_TABLE_URL_REWRITES;

		$rewrites = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE active = %d ORDER BY created_at DESC",
				1
			)
		);

		return new WP_REST_Response(
			array(
				'rewrites' => $rewrites ?: array(),
			),
			200
		);
	}

	/**
	 * Get login attempts
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_attempts(WP_REST_Request $request): WP_REST_Response
	{
		global $wpdb;

		$limit = $request->get_param('limit');
		$table = $wpdb->prefix . \APOLLO_LOGIN_TABLE_LOGIN_ATTEMPTS;

		$attempts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY attempted_at DESC LIMIT %d",
				$limit
			)
		);

		return new WP_REST_Response(
			array(
				'attempts' => $attempts ?: array(),
			),
			200
		);
	}
}
