<?php
/**
 * Plugin Name: Apollo WOW Reactions
 * Plugin URI: https://apollo.rio.br
 * Description: Emoji-based WOW reaction system replacing likes — custom reactions on posts/depoimentos with charts.
 * Version: 1.0.1
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * Text Domain: apollo-wow
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package Apollo\Wow
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_WOW_VERSION', '1.0.1' );
define( 'APOLLO_WOW_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_WOW_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_WOW_FILE', __FILE__ );

spl_autoload_register(
	function ( $class ) {
		$prefix = 'Apollo\\Wow\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = APOLLO_WOW_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'Apollo\\Wow\\Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Apollo\\Wow\\Deactivation', 'deactivate' ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'APOLLO_CORE_BOOTSTRAPPED' ) ) {
			return;
		}
		require_once APOLLO_WOW_PATH . 'includes/functions.php';
		\Apollo\Wow\Plugin::instance();
	},
	15
);
