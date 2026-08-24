<?php
/**
 * Plugin Name: Apollo Dashboard
 * Plugin URI: https://apollo.rio.br/plugins/apollo-dashboard
 * Description: Dashboard: User dashboard settings, template definitions, widgets
 * Version: 1.1.0
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-dashboard
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Dashboard
 */

declare(strict_types=1);

namespace Apollo\Dashboard;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_DASHBOARD_VERSION', '1.1.0' );
define( 'APOLLO_DASHBOARD_FILE', __FILE__ );
define( 'APOLLO_DASHBOARD_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_DASHBOARD_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_DASHBOARD_BASENAME', plugin_basename( __FILE__ ) );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check if apollo-core and apollo-users are active (REQUIRED)
 */
function apollo_dashboard_check_dependencies(): void {
	$active_plugins = get_option( 'active_plugins', array() );

	// Check apollo-core
	if ( ! in_array( 'apollo-core/apollo-core.php', $active_plugins, true ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p><strong>Apollo Dashboard:</strong> Requires Apollo Core plugin to be active.</p></div>';
			}
		);
		deactivate_plugins( APOLLO_DASHBOARD_BASENAME );
		return;
	}

	// Check apollo-users (registry defines dependency)
	if ( ! in_array( 'apollo-users/apollo-users.php', $active_plugins, true ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p><strong>Apollo Dashboard:</strong> Requires Apollo Users plugin to be active.</p></div>';
			}
		);
		deactivate_plugins( APOLLO_DASHBOARD_BASENAME );
		return;
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_dashboard_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER
// ═══════════════════════════════════════════════════════════════════════════

// Composer autoloader
if ( file_exists( APOLLO_DASHBOARD_DIR . 'vendor/autoload.php' ) ) {
	require_once APOLLO_DASHBOARD_DIR . 'vendor/autoload.php';
}

// Manual PSR-4 autoloader fallback
spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\Dashboard\\';
		$base_dir = APOLLO_DASHBOARD_DIR . 'src/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// ═══════════════════════════════════════════════════════════════════════════
// INCLUDES
// ═══════════════════════════════════════════════════════════════════════════

if ( file_exists( APOLLO_DASHBOARD_DIR . 'includes/constants.php' ) ) {
	require_once APOLLO_DASHBOARD_DIR . 'includes/constants.php';
}
if ( file_exists( APOLLO_DASHBOARD_DIR . 'includes/functions.php' ) ) {
	require_once APOLLO_DASHBOARD_DIR . 'includes/functions.php';
}

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Initialize plugin after apollo-core loads
 */
function apollo_dashboard_init(): void {
	// Verify apollo-core is loaded
	if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
		return;
	}

	// Initialize main plugin class
	$plugin = Plugin::get_instance();
	$plugin->init();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_dashboard_init', 15 );

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
	__FILE__,
	function () {
		// Verify dependencies
		if ( ! is_plugin_active( 'apollo-core/apollo-core.php' ) ) {
			wp_die( 'Apollo Dashboard requires Apollo Core to be active.' );
		}

		// Run activation tasks
		if ( class_exists( __NAMESPACE__ . '\\Activation' ) ) {
			Activation::activate();
		}

		// Flush rewrite rules
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		if ( class_exists( __NAMESPACE__ . '\\Deactivation' ) ) {
			Deactivation::deactivate();
		}
		flush_rewrite_rules();
	}
);
