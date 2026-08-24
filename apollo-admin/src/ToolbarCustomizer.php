<?php

/**
 * Apollo Admin — Toolbar Customizer.
 *
 * Captures the WordPress admin bar/toolbar, allows reordering and hiding items.
 * Adapted from UiPress Lite ToolBar class.
 *
 * @package Apollo\Admin
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Toolbar Customizer.
 *
 * @since 1.1.0
 */
final class ToolbarCustomizer {


	/** @var ToolbarCustomizer|null */
	private static ?ToolbarCustomizer $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return ToolbarCustomizer
	 */
	public static function get_instance(): ToolbarCustomizer {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		add_action( 'wp_before_admin_bar_render', array( $this, 'capture_and_apply' ), PHP_INT_MAX );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Capture the toolbar and apply customizations.
	 *
	 * Adapted from UiPress ToolBar::capture_wp_toolbar().
	 */
	public function capture_and_apply(): void {
		global $wp_admin_bar;

		if ( ! $wp_admin_bar ) {
			return;
		}

		$items = $wp_admin_bar->get_nodes();
		if ( ! $items ) {
			$items = array();
		}

		// Get settings.
		$settings = Settings::get_instance();
		$config   = $settings->for_plugin( 'toolbar' );

		// Apply hidden items.
		if ( ! empty( $config['hidden'] ) && is_array( $config['hidden'] ) ) {
			foreach ( $config['hidden'] as $node_id ) {
				$wp_admin_bar->remove_node( sanitize_key( $node_id ) );
			}
		}

		// Build structured toolbar for caching.
		$categories = array();
		foreach ( $items as $id => $item ) {
			if ( empty( $item->parent ) ) {
				$node              = array(
					'id'      => $item->id,
					'title'   => wp_strip_all_tags( $item->title ?? '' ),
					'href'    => $item->href ?? '',
					'submenu' => $this->get_submenu_items( $item->id, $items ),
				);
				$categories[ $id ] = $node;
			}
		}

		// Remove default WP items we never need in Apollo context.
		$remove_defaults = array( 'menu-toggle', 'wp-logo' );
		foreach ( $remove_defaults as $rm ) {
			unset( $categories[ $rm ] );
		}

		// Cache for REST.
		set_transient( 'apollo_admin_toolbar_cache', $categories, 12 * HOUR_IN_SECONDS );
	}

	/**
	 * Get sub-menu items for a toolbar node.
	 *
	 * Adapted from UiPress ToolBar::getToolBarSubMenuItems().
	 *
	 * @param string $parent_id Parent node ID.
	 * @param array  $items     All toolbar items.
	 * @return array
	 */
	private function get_submenu_items( string $parent_id, array $items ): array {
		$children = array();
		foreach ( $items as $item ) {
			if ( $item->parent === $parent_id ) {
				$children[] = array(
					'id'    => $item->id,
					'title' => wp_strip_all_tags( $item->title ?? '' ),
					'href'  => $item->href ?? '',
				);
			}
		}
		return $children;
	}

	/**
	 * Register REST endpoints.
	 */
	public function register_rest_routes(): void {
		$namespace = APOLLO_ADMIN_REST_NAMESPACE;

		// GET toolbar structure.
		register_rest_route(
			$namespace,
			'/toolbar',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_toolbar' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// POST save toolbar customizations.
		register_rest_route(
			$namespace,
			'/toolbar',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_save_toolbar' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * REST callback — get toolbar structure.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_toolbar(): \WP_REST_Response {
		$cached   = get_transient( 'apollo_admin_toolbar_cache' );
		$settings = Settings::get_instance()->for_plugin( 'toolbar' );

		return new \WP_REST_Response(
			array(
				'toolbar' => $cached ?: array(),
				'hidden'  => $settings['hidden'] ?? array(),
			),
			200
		);
	}

	/**
	 * REST callback — save toolbar customizations.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function rest_save_toolbar( \WP_REST_Request $request ): \WP_REST_Response {
		$body   = $request->get_json_params();
		$hidden = array();

		if ( isset( $body['hidden'] ) && is_array( $body['hidden'] ) ) {
			$hidden = array_map( 'sanitize_key', $body['hidden'] );
		}

		Settings::get_instance()->set( 'toolbar', 'hidden', $hidden );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Toolbar personalizada salva.', 'apollo-admin' ),
			),
			200
		);
	}
}
