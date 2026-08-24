<?php
/**
 * Apollo Core - Central CPT Registry
 *
 * MASTER REGISTRY for ALL Custom Post Types in Apollo ecosystem.
 * Registers CPTs as FALLBACK if owner plugin is not active.
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT Registry - Singleton Pattern
 *
 * Philosophy: apollo-core owns the DEFINITIONS.
 * If apollo-event is active, it registers 'event' CPT.
 * If NOT active, apollo-core registers as fallback bridge.
 */
final class CPTRegistry {

	/**
	 * Instance
	 *
	 * @var CPTRegistry|null
	 */
	private static ?CPTRegistry $instance = null;

	/**
	 * Registered CPTs tracking
	 *
	 * @var array
	 */
	private array $registered = array();

	/**
	 * CPT Definitions from Registry
	 *
	 * @var array
	 */
	private array $definitions = array();

	/**
	 * Get instance (Singleton)
	 */
	public static function get_instance(): CPTRegistry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->load_definitions();
	}

	/**
	 * Initialize - Hook into WordPress
	 */
	public static function init(): void {
		$instance = self::get_instance();

		// Register CPTs on init with priority 5 (before other plugins)
		add_action( 'init', array( $instance, 'register_fallback_cpts' ), 5 );

		// Fire action for other plugins to register their CPTs
		add_action( 'init', array( $instance, 'fire_cpt_registered_action' ), 6 );
	}

	/**
	 * Load CPT definitions from config/cpts.php
	 *
	 * @since 6.1.0 — Migrated from hardcoded array to config file.
	 */
	private function load_definitions(): void {
		$this->definitions = \Apollo\Core\Config\ConfigLoader::load( 'cpts' );
	}

	/**
	 * Check if owner plugin is active
	 */
	private function is_owner_active( string $owner ): bool {
		$plugin_file = $owner . '/' . $owner . '.php';
		return is_plugin_active( $plugin_file );
	}

	/**
	 * Check if CPT already exists
	 */
	public function cpt_exists( string $slug ): bool {
		return post_type_exists( $slug );
	}

	/**
	 * Register fallback CPTs for inactive owner plugins
	 */
	public function register_fallback_cpts(): void {
		foreach ( $this->definitions as $slug => $def ) {
			// Skip if already registered by owner plugin
			if ( $this->cpt_exists( $slug ) ) {
				$this->registered[ $slug ] = array(
					'registered_by' => 'owner',
					'owner'         => $def['owner'],
				);
				continue;
			}

			// Register as fallback
			$this->register_cpt( $slug, $def );

			$this->registered[ $slug ] = array(
				'registered_by' => 'apollo-core',
				'owner'         => $def['owner'],
				'fallback'      => true,
			);

			// Log
			apollo_log_audit(
				'cpt:fallback_registered',
				'apollo-core',
				null,
				array(
					'cpt'   => $slug,
					'owner' => $def['owner'],
				)
			);
		}
	}

	/**
	 * Register a single CPT
	 */
	private function register_cpt( string $slug, array $def ): void {
		$args = array(
			'labels'                => $def['labels'],
			'public'                => $def['public'],
			'publicly_queryable'    => $def['public'],
			'show_ui'               => true,
			'show_in_menu'          => true,
			'query_var'             => true,
			'rewrite'               => $def['rewrite'] ? array(
				'slug'       => $def['rewrite'],
				'with_front' => false,
			) : false,
			'capability_type'       => 'post',
			'has_archive'           => $def['has_archive'] ? $def['archive'] : false,
			'hierarchical'          => false,
			'menu_position'         => null,
			'menu_icon'             => $def['menu_icon'] ?? 'dashicons-admin-post',
			'supports'              => $def['supports'],
			'show_in_rest'          => true,
			'rest_base'             => $def['rest_base'],
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		);

		register_post_type( $slug, $args );
	}

	/**
	 * Fire action after all CPTs registered
	 */
	public function fire_cpt_registered_action(): void {
		/**
		 * Action: apollo/cpts/registered
		 *
		 * Fired after all Apollo CPTs are registered.
		 * Other plugins can hook here to add meta boxes, etc.
		 *
		 * @param array $registered List of registered CPTs with details
		 */
		do_action( \Apollo\Core\Config\ApolloHook::CPTS_REGISTERED, $this->registered );
	}

	/**
	 * Get all CPT definitions
	 */
	public function get_definitions(): array {
		return $this->definitions;
	}

	/**
	 * Get registered CPTs status
	 */
	public function get_registered(): array {
		return $this->registered;
	}

	/**
	 * Get single CPT definition
	 */
	public function get_definition( string $slug ): ?array {
		return $this->definitions[ $slug ] ?? null;
	}

	/**
	 * Check if CPT is registered as fallback
	 */
	public function is_fallback( string $slug ): bool {
		return isset( $this->registered[ $slug ]['fallback'] ) && $this->registered[ $slug ]['fallback'];
	}
}
