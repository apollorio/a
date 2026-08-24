<?php
/**
 * Plugin Name: Apollo Notifications
 * Plugin URI: https://apollo.rio.br
 * Description: In-app notifications, preferences, badge counts — adapted from BNFW engine patterns.
 * Version: 1.0.1
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * Text Domain: apollo-notif
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package Apollo\Notif
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants
define( 'APOLLO_NOTIF_VERSION', '1.0.1' );
define( 'APOLLO_NOTIF_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_NOTIF_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_NOTIF_FILE', __FILE__ );

// Autoload
spl_autoload_register(
	function ( $class ) {
		$prefix = 'Apollo\\Notif\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = APOLLO_NOTIF_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

// Activation / Deactivation
register_activation_hook( __FILE__, array( 'Apollo\\Notif\\Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Apollo\\Notif\\Deactivation', 'deactivate' ) );

// Bootstrap
add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
			return;
		}
		require_once APOLLO_NOTIF_PATH . 'includes/functions.php';
		\Apollo\Notif\Plugin::instance();
	},
	15
);
