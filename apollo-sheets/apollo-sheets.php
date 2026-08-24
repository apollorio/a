<?php

/**
 * Plugin Name: Apollo Sheets
 * Plugin URI: https://apollo.rio.br/plugins/apollo-sheets
 * Description: Data tables & spreadsheets — bulk data display, import/export CSV/JSON/HTML, frontend DataTables, admin editor, dashboard widgets, shortcodes.
 * Version: 1.0.1
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-sheets
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_SHEETS_VERSION', '1.0.1' );
define( 'APOLLO_SHEETS_DB_VERSION', 1 );
define( 'APOLLO_SHEETS_FILE', __FILE__ );
define( 'APOLLO_SHEETS_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_SHEETS_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_SHEETS_BASENAME', plugin_basename( __FILE__ ) );
define( 'APOLLO_SHEETS_CPT', 'apollo_sheet' );

// ═══════════════════════════════════════════════════════════════════════════
// DEPENDENCY CHECK
// ═══════════════════════════════════════════════════════════════════════════

function apollo_sheets_check_dependencies(): void {
	$active_plugins = get_option( 'active_plugins', array() );

	if ( ! in_array( 'apollo-core/apollo-core.php', $active_plugins, true ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p><strong>Apollo Sheets:</strong> Requires Apollo Core plugin to be active.</p></div>';
			}
		);
		deactivate_plugins( APOLLO_SHEETS_BASENAME );
		return;
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_sheets_check_dependencies', 5 );

// ═══════════════════════════════════════════════════════════════════════════
// AUTOLOADER (PSR-4)
// ═══════════════════════════════════════════════════════════════════════════

spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\Sheets\\';
		$base_dir = APOLLO_SHEETS_DIR . 'src/';

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

require_once APOLLO_SHEETS_DIR . 'includes/constants.php';
require_once APOLLO_SHEETS_DIR . 'includes/functions.php';

// ═══════════════════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════════════════

function apollo_sheets_init(): void {
	if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
		return;
	}

	$plugin = Plugin::get_instance();
	$plugin->init();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\apollo_sheets_init', 15 );

// ═══════════════════════════════════════════════════════════════════════════
// ACTIVATION / DEACTIVATION
// ═══════════════════════════════════════════════════════════════════════════

register_activation_hook(
	__FILE__,
	function () {
		if ( ! is_plugin_active( 'apollo-core/apollo-core.php' ) ) {
			wp_die( 'Apollo Sheets requires Apollo Core to be active.' );
		}
		Activation::activate();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		Deactivation::deactivate();
		flush_rewrite_rules();
	}
);
