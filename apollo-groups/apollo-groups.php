<?php
/**
 * Plugin Name: Apollo Groups
 * Plugin URI: https://apollo.rio.br
 * Description: Comunas (public communities) — adapted from BuddyPress bp-groups. Public-only, flat, no hierarchy.
 * Version: 1.2.0
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * Text Domain: apollo-groups
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package Apollo\Groups
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_GROUPS_VERSION', '1.2.0' );
define( 'APOLLO_GROUPS_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_GROUPS_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_GROUPS_FILE', __FILE__ );

spl_autoload_register(
	function ( $class ) {
		$prefix = 'Apollo\\Groups\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = APOLLO_GROUPS_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Apollo\\Groups\\Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Apollo\\Groups\\Deactivation', 'deactivate' ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
			return;
		}
		require_once APOLLO_GROUPS_PATH . 'includes/functions.php';
		\Apollo\Groups\Plugin::instance();
	},
	15
);
