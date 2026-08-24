<?php
/**
 * Plugin Name: Apollo Moderation
 * Plugin URI: https://apollo.rio.br
 * Description: Content moderation queue, flagging, user reports, audit log — adapted from BuddyPress bp-moderation patterns.
 * Version: 1.0.1
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * Text Domain: apollo-mod
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package Apollo\Mod
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_MOD_VERSION', '1.0.1' );
define( 'APOLLO_MOD_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_MOD_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_MOD_FILE', __FILE__ );

spl_autoload_register(
	function ( $class ) {
		$prefix = 'Apollo\\Mod\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = APOLLO_MOD_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Apollo\\Mod\\Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Apollo\\Mod\\Deactivation', 'deactivate' ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
			return;
		}
		require_once APOLLO_MOD_PATH . 'includes/functions.php';
		\Apollo\Mod\Plugin::instance();
	},
	15
);
