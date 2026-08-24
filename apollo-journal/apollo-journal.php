<?php
/**
 * Plugin Name: Apollo Journal
 * Plugin URI: https://apollo.rio.br/plugins/apollo-journal
 * Description: Magazine & editorial platform — news grid, NREP auto-codes, custom taxonomies, ColorMag-adapted layouts. Mobile-first.
 * Version: 1.0.1
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-journal
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Journal
 */

declare(strict_types=1);

namespace Apollo\Journal;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_JOURNAL_VERSION', '1.0.1' );
define( 'APOLLO_JOURNAL_FILE', __FILE__ );
define( 'APOLLO_JOURNAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_JOURNAL_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_JOURNAL_BASENAME', plugin_basename( __FILE__ ) );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check if apollo-core is active (REQUIRED for ALL Apollo plugins).
 *
 * @return void
 */
function apollo_journal_check_dependencies(): void {
	$active_plugins = get_option( 'active_plugins', array() );

	if ( ! in_array( 'apollo-core/apollo-core.php', $active_plugins, true ) ) {
		add_action(
			'admin_notices',
			function (): void {
				echo '<div class="notice notice-error"><p><strong>Apollo Journal:</strong> Requires Apollo Core plugin to be active.</p></div>';
			}
		);
		deactivate_plugins( APOLLO_JOURNAL_BASENAME );
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_journal_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER (PSR-4)
// ═══════════════════════════════════════════════════════════════════════════

if ( file_exists( APOLLO_JOURNAL_DIR . 'vendor/autoload.php' ) ) {
	require_once APOLLO_JOURNAL_DIR . 'vendor/autoload.php';
}

spl_autoload_register(
	function ( string $class ): void {
		$prefix   = 'Apollo\\Journal\\';
		$base_dir = APOLLO_JOURNAL_DIR . 'src/';
		$len      = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
		}

		$relative = substr( $class, $len );
		$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// ═══════════════════════════════════════════════════════════════════════════
// INCLUDES
// ═══════════════════════════════════════════════════════════════════════════

require_once APOLLO_JOURNAL_DIR . 'includes/constants.php';
require_once APOLLO_JOURNAL_DIR . 'includes/functions.php';

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Boot plugin after apollo-core is loaded.
 *
 * @return void
 */
function apollo_journal_init(): void {
	if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
		return;
	}

	Plugin::get_instance()->init();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_journal_init', 15 );

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
	__FILE__,
	function (): void {
		if ( ! is_plugin_active( 'apollo-core/apollo-core.php' ) ) {
			wp_die(
				esc_html__( 'Apollo Journal requires Apollo Core to be active.', 'apollo-journal' ),
				'Plugin Dependency',
				array( 'back_link' => true )
			);
		}

		Activation::activate();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function (): void {
		Deactivation::deactivate();
		flush_rewrite_rules();
	}
);
