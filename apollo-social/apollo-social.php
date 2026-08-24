<?php

/**
 * Plugin Name: Apollo Social
 * Plugin URI: https://apollo.rio.br
 * Description: Activity streams, social feed, auto-connections — adapted from BuddyPress activity patterns. No likes, no followers hierarchy.
 * Version: 1.0.1
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * Text Domain: apollo-social
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package Apollo\Social
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_SOCIAL_VERSION', '1.0.1' );
define( 'APOLLO_SOCIAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_SOCIAL_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_SOCIAL_FILE', __FILE__ );

spl_autoload_register(
	function ( $class ) {
		$prefix = 'Apollo\\Social\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = APOLLO_SOCIAL_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Apollo\\Social\\Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Apollo\\Social\\Deactivation', 'deactivate' ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
			return;
		}
		require_once APOLLO_SOCIAL_PATH . 'includes/functions.php';
		\Apollo\Social\Plugin::instance();
	},
	15
);
