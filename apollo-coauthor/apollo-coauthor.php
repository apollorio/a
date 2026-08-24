<?php
/**
 * ================================================================================
 * Plugin Name: Apollo CoAuthor
 * Plugin URI:  https://apollo.rio.br
 * Description: Co-authorship system for all Apollo CPTs — events, DJs, classifieds, docs, locals & groups. Taxonomy-based multi-author management with admin metabox, REST API, and deep WP_Query integration. Adapted from Co-Authors Plus patterns.
 * Version:     1.0.2
 * Author:      Apollo::Rio
 * Author URI:  https://apollo.rio.br
 * Text Domain: apollo-coauthor
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License:     Proprietary
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 * ================================================================================
 */

declare(strict_types=1);

namespace Apollo\CoAuthor;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
═══════════════════════════════════════════════════════════════════════════════
 * 1. CONSTANTS
 * ═══════════════════════════════════════════════════════════════════════════════ */

define( 'APOLLO_COAUTHOR_VERSION', '1.0.2' ); /* 1.0.2 — POST_TYPES said 'loc'; the CPT slug is 'local', so venues never got the coauthor box. */
define( 'APOLLO_COAUTHOR_FILE', __FILE__ );
define( 'APOLLO_COAUTHOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_COAUTHOR_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_COAUTHOR_BASENAME', plugin_basename( __FILE__ ) );

/*
═══════════════════════════════════════════════════════════════════════════════
 * 2. DEPENDENCY CHECK — priority 5
 * ═══════════════════════════════════════════════════════════════════════════════ */

/**
 * Verify apollo-core is active; deactivate self otherwise.
 *
 * @since 1.0.0
 */
function apollo_coauthor_check_dependencies(): void {
	if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
		add_action(
			'admin_notices',
			static function (): void {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'Apollo CoAuthor requires Apollo Core to be installed and active.', 'apollo-coauthor' );
				echo '</p></div>';
			}
		);
		deactivate_plugins( APOLLO_COAUTHOR_BASENAME );
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_coauthor_check_dependencies', 5 );

/*
═══════════════════════════════════════════════════════════════════════════════
 * 3. AUTOLOADER
 * ═══════════════════════════════════════════════════════════════════════════════ */

if ( file_exists( APOLLO_COAUTHOR_DIR . 'vendor/autoload.php' ) ) {
	require_once APOLLO_COAUTHOR_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix   = 'Apollo\\CoAuthor\\';
			$base_dir = APOLLO_COAUTHOR_DIR . 'src/';

			$len = strlen( $prefix );
			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
			}

			$relative = substr( $class, $len );
			$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

/*
═══════════════════════════════════════════════════════════════════════════════
 * 4. INCLUDES
 * ═══════════════════════════════════════════════════════════════════════════════ */

require_once APOLLO_COAUTHOR_DIR . 'includes/constants.php';
require_once APOLLO_COAUTHOR_DIR . 'includes/functions.php';

/*
═══════════════════════════════════════════════════════════════════════════════
 * 5. INITIALIZATION — priority 15
 * ═══════════════════════════════════════════════════════════════════════════════ */

/**
 * Boot the plugin after all dependencies are loaded.
 *
 * @since 1.0.0
 */
function apollo_coauthor_init(): void {
	if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
		return;
	}

	Plugin::get_instance()->init();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_coauthor_init', 15 );

/*
═══════════════════════════════════════════════════════════════════════════════
 * 6. ACTIVATION / DEACTIVATION
 * ═══════════════════════════════════════════════════════════════════════════════ */

register_activation_hook(
	__FILE__,
	static function (): void {
		Activation::activate();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		Deactivation::deactivate();
		flush_rewrite_rules();
	}
);
