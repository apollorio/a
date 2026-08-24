<?php
/**
 * Plugin Name: Apollo Elementor
 * Plugin URI:  https://apollo.rio.br
 * Description: Server-rendered Elementor widgets with cache-first data helpers for the Apollo ecosystem. Premium singles, planners, charts, and gestor dashboards.
 * Version:     1.0.1
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Author:      Apollo Rio
 * License:     GPL-2.0-or-later
 * Text Domain: apollo-elementor
 * Domain Path: /languages
 *
 * @package Apollo\ElementorAE
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_AE_VERSION', '1.0.1' );
define( 'APOLLO_AE_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_AE_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_AE_FILE', __FILE__ );

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'Apollo\\ElementorAE\\';
	if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
		return;
	}
	$relative = str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, strlen( $prefix ) ) );
	$file     = APOLLO_AE_DIR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';
	if ( is_file( $file ) ) {
		require_once $file;
	}
} );

require_once APOLLO_AE_DIR . 'src/helpers.php';

add_action( 'plugins_loaded', static function (): void {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', static function (): void {
			echo '<div class="notice notice-warning"><p><strong>Apollo Elementor</strong>: ';
			esc_html_e( 'Elementor must be installed and active.', 'apollo-elementor' );
			echo '</p></div>';
		} );
		return;
	}

	\Apollo\ElementorAE\Plugin::get_instance()->init();
}, 20 );

register_uninstall_hook( __FILE__, [ \Apollo\ElementorAE\Plugin::class, 'uninstall' ] );
