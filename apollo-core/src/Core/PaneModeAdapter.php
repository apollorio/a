<?php
/**
 * Apollo Pane Mode Adapter
 *
 * Manages the "pane engine mode" toggle in apollo-core.
 * When active:
 *  - Ensures apollo-pane-engine plugin is activated
 *  - Intercepts CPT single templates and serves them inside the pane shell
 *  - Provides the option CRUD used by PaneModeController
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

namespace Apollo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaneModeAdapter {

	/** wp_options key */
	const OPTION_KEY = 'apollo_pane_mode';

	/** Plugin file relative to WP plugins dir */
	const PLUGIN_FILE = 'apollo-pane-engine/apollo-pane-engine.php';

	/**
	 * Boot: hooks template intercept + ensures pane-engine is active.
	 * Called from apollo_core_bootstrap() — safe at plugins_loaded.
	 */
	public static function init(): void {
		if ( ! self::is_active() ) {
			return;
		}

		// Auto-activate pane-engine if mode is on but plugin is off.
		// This happens in the SAME request only when called by PaneModeController::enable().
		// For normal page loads we just fire the template hook (plugin already active by then).
		add_action( 'plugins_loaded', array( static::class, 'ensure_plugin_active' ), 20 );

		// Intercept CPT singles — priority 5 fires before theme templates.
		add_action( 'template_redirect', array( static::class, 'intercept_cpt_template' ), 5 );
	}

	/* ── Option helpers ──────────────────────────────────── */

	public static function is_active(): bool {
		return (bool) get_option( self::OPTION_KEY, false );
	}

	public static function enable(): void {
		update_option( self::OPTION_KEY, true );
		self::ensure_plugin_active();
		delete_transient( 'apollo_pane_mode_cache' );
	}

	public static function disable(): void {
		update_option( self::OPTION_KEY, false );
		delete_transient( 'apollo_pane_mode_cache' );
	}

	/* ── Plugin auto-activation ──────────────────────────── */

	public static function ensure_plugin_active(): void {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( self::PLUGIN_FILE ) ) {
			$result = activate_plugin( self::PLUGIN_FILE );
			if ( is_wp_error( $result ) ) {
				// Log silently — admin will see plugin is not active
				error_log( '[Apollo PaneMode] Failed to auto-activate apollo-pane-engine: ' . $result->get_error_message() );
			}
		}
	}

	/* ── CPT template intercept ──────────────────────────── */

	/**
	 * If the current request is a CPT single registered in the pane map,
	 * serve the pane shell with the CPT content pre-loaded in #casa-root.
	 */
	public static function intercept_cpt_template(): void {
		// Pane-engine plugin must be loaded (its template + section-renderer).
		if ( ! defined( 'APOLLO_PANE_ENGINE_PATH' ) ) {
			return;
		}

		/**
		 * Map post_type → [url_slug, back_route].
		 * Other plugins add entries via this filter.
		 *
		 * @filter apollo/pane/cpt_map
		 */
		$cpt_map = apply_filters(
			'apollo/pane/cpt_map',
			array(
				'event' => array( 'url_slug' => 'evento', 'back_route' => 'gigs' ),
				'dj'    => array( 'url_slug' => 'dj',     'back_route' => 'sounds' ),
				'local' => array( 'url_slug' => 'local',  'back_route' => 'spots' ),
			)
		);

		$matched      = null;
		$queried_id   = 0;

		foreach ( $cpt_map as $post_type => $config ) {
			if ( is_singular( $post_type ) ) {
				$matched    = $config;
				$queried_id = absint( get_queried_object_id() );
				break;
			}
		}

		if ( ! $matched || ! $queried_id ) {
			return;
		}

		// Set query vars for page-casa.php to read.
		set_query_var( 'apollo_pane_preload_section', $matched['url_slug'] );
		set_query_var( 'apollo_pane_preload_id',      $queried_id );
		set_query_var( 'apollo_pane_back_route',      $matched['back_route'] );

		status_header( 200 );

		$tpl = APOLLO_PANE_ENGINE_PATH . 'templates/page-casa.php';
		if ( file_exists( $tpl ) ) {
			include $tpl;
			exit;
		}
	}

	/* ── Status data ─────────────────────────────────────── */

	public static function get_status(): array {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return array(
			'mode_active'    => self::is_active(),
			'plugin_active'  => is_plugin_active( self::PLUGIN_FILE ),
			'plugin_file'    => self::PLUGIN_FILE,
			'option_key'     => self::OPTION_KEY,
		);
	}
}
