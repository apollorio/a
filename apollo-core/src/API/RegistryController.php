<?php
/**
 * Apollo Core - Registry REST API Controller
 *
 * Provides REST endpoints for CPT/Taxonomy/Meta registry info.
 *
 * @package Apollo\Core\API
 * @since 6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry Controller
 */
class RegistryController {

	/**
	 * REST namespace
	 */
	const NAMESPACE = 'apollo/v1';

	/**
	 * Constructor - register routes
	 */
	public function __construct() {
		$this->register_routes();
	}

	/**
	 * Register REST routes
	 */
	public function register_routes(): void {
		// GET /apollo/v1/registry - Get registry overview
		register_rest_route(
			self::NAMESPACE,
			'/registry',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_registry' ),
					'permission_callback' => array( $this, 'check_admin' ),
				),
			)
		);

		// GET /apollo/v1/registry/cpts - Get CPT registry
		register_rest_route(
			self::NAMESPACE,
			'/registry/cpts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_cpts' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// GET /apollo/v1/registry/taxonomies - Get taxonomy registry
		register_rest_route(
			self::NAMESPACE,
			'/registry/taxonomies',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_taxonomies' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// GET /apollo/v1/registry/status - Get registration status (public health check)
		register_rest_route(
			self::NAMESPACE,
			'/registry/status',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// GET /apollo/v1/registry/tables - Get database table status
		register_rest_route(
			self::NAMESPACE,
			'/registry/tables',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tables' ),
					'permission_callback' => array( $this, 'check_admin' ),
				),
			)
		);

		// POST /apollo/v1/registry/inventory-sync — mescla plugins + MU-plugins no apollo-registry.json
		register_rest_route(
			self::NAMESPACE,
			'/registry/inventory-sync',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'post_inventory_sync' ),
					'permission_callback' => array( $this, 'check_admin' ),
				),
			)
		);
	}

	/**
	 * Check if user is admin
	 */
	public function check_admin(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /registry - Full registry overview
	 */
	public function get_registry( \WP_REST_Request $request ): \WP_REST_Response {
		$cpt_registry  = \Apollo\Core\CPTRegistry::get_instance();
		$tax_registry  = \Apollo\Core\TaxonomyRegistry::get_instance();
		$meta_registry = \Apollo\Core\MetaRegistry::get_instance();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'version'    => APOLLO_CORE_VERSION,
					'cpts'       => array(
						'definitions' => $cpt_registry->get_definitions(),
						'registered'  => $cpt_registry->get_registered(),
					),
					'taxonomies' => array(
						'definitions' => $tax_registry->get_definitions(),
						'registered'  => $tax_registry->get_registered(),
					),
					'meta'       => array(
						'post_meta' => array_keys( $meta_registry->get_post_meta_definitions() ),
						'user_meta' => array_keys( $meta_registry->get_user_meta_definitions() ),
					),
				),
			),
			200
		);
	}

	/**
	 * GET /registry/cpts - List CPTs
	 */
	public function get_cpts( \WP_REST_Request $request ): \WP_REST_Response {
		$registry = \Apollo\Core\CPTRegistry::get_instance();

		$cpts = array();
		foreach ( $registry->get_definitions() as $slug => $def ) {
			$cpts[ $slug ] = array(
				'slug'      => $slug,
				'owner'     => $def['owner'],
				'rewrite'   => $def['rewrite'],
				'archive'   => $def['archive'],
				'rest_base' => $def['rest_base'],
				'public'    => $def['public'],
				'labels'    => array(
					'name'          => $def['labels']['name'],
					'singular_name' => $def['labels']['singular_name'],
				),
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $cpts,
				'total'   => count( $cpts ),
			),
			200
		);
	}

	/**
	 * GET /registry/taxonomies - List taxonomies
	 */
	public function get_taxonomies( \WP_REST_Request $request ): \WP_REST_Response {
		$registry = \Apollo\Core\TaxonomyRegistry::get_instance();

		$taxonomies = array();
		foreach ( $registry->get_definitions() as $slug => $def ) {
			$taxonomies[ $slug ] = array(
				'slug'         => $slug,
				'owner'        => $def['owner'],
				'rewrite'      => $def['rewrite'],
				'object_types' => $def['object_types'],
				'hierarchical' => $def['hierarchical'],
				'labels'       => array(
					'name'          => $def['labels']['name'],
					'singular_name' => $def['labels']['singular_name'],
				),
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $taxonomies,
				'total'   => count( $taxonomies ),
			),
			200
		);
	}

	/**
	 * GET /registry/status - Registration status
	 */
	public function get_status( \WP_REST_Request $request ): \WP_REST_Response {
		$cpt_registry = \Apollo\Core\CPTRegistry::get_instance();
		$tax_registry = \Apollo\Core\TaxonomyRegistry::get_instance();

		$cpt_status = $cpt_registry->get_registered();
		$tax_status = $tax_registry->get_registered();

		// Count fallbacks
		$cpt_fallbacks = array_filter( $cpt_status, fn( $s ) => $s['fallback'] ?? false );
		$tax_fallbacks = array_filter( $tax_status, fn( $s ) => $s['fallback'] ?? false );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'cpts'       => array(
						'total'     => count( $cpt_status ),
						'fallbacks' => count( $cpt_fallbacks ),
						'details'   => $cpt_status,
					),
					'taxonomies' => array(
						'total'     => count( $tax_status ),
						'fallbacks' => count( $tax_fallbacks ),
						'details'   => $tax_status,
					),
				),
			),
			200
		);
	}

	/**
	 * GET /registry/tables - Database table status
	 */
	public function get_tables( \WP_REST_Request $request ): \WP_REST_Response {
		$builder = new \Apollo\Core\DatabaseBuilder();
		$status  = $builder->get_table_status();

		$total_rows = array_sum( array_column( $status, 'rows' ) );
		$existing   = count( array_filter( $status, fn( $s ) => $s['exists'] ) );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'tables'     => $status,
					'total'      => count( $status ),
					'existing'   => $existing,
					'total_rows' => $total_rows,
				),
			),
			200
		);
	}

	/**
	 * POST /registry/inventory-sync — body JSON opcional { "persist": true|false }.
	 */
	public function post_inventory_sync( \WP_REST_Request $request ): \WP_REST_Response {
		$persist = true;
		$params  = $request->get_json_params();
		if ( is_array( $params ) && array_key_exists( 'persist', $params ) ) {
			$persist = rest_sanitize_boolean( $params['persist'] );
		}

		$result = \Apollo\Core\PluginInventorySync::sync_registry_file( $persist );

		$runtime = array();
		if ( isset( $result['merged'][ \Apollo\Core\PluginInventorySync::RUNTIME_PACKAGES_KEY ] ) && is_array( $result['merged'][ \Apollo\Core\PluginInventorySync::RUNTIME_PACKAGES_KEY ] ) ) {
			$runtime = $result['merged'][ \Apollo\Core\PluginInventorySync::RUNTIME_PACKAGES_KEY ];
		}

		$data = array(
			'success' => $result['success'],
			'path'    => $result['path'],
			'message' => $result['message'],
			'persist' => $persist,
			'counts'  => isset( $runtime['counts'] ) && is_array( $runtime['counts'] ) ? $runtime['counts'] : array(),
			'generated_iso' => isset( $runtime['generated_iso'] ) ? (string) $runtime['generated_iso'] : '',
		);

		$code = $result['success'] ? 200 : 500;

		return new \WP_REST_Response(
			array(
				'success' => (bool) $result['success'],
				'data'    => $data,
			),
			$code
		);
	}
}
