<?php
/**
 * Plugin Name: Apollo Elementor Pro
 * Plugin URI:  https://apollo.rio.br
 * Description: Native Elementor widgets, dynamic tags, and controls for the Apollo ecosystem. Reads CPTs, taxonomies, and meta from apollo-core MASTER_REGISTRY.
 * Version:     1.0.1
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Author:      Apollo Rio
 * License:     GPL-2.0-or-later
 * Text Domain: apollo-elementor-pro
 * Domain Path: /languages
 *
 * @package Apollo\Elementor
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_ELEMENTOR_VERSION', '1.0.1' );
define( 'APOLLO_ELEMENTOR_DIR',     plugin_dir_path( __FILE__ ) );
define( 'APOLLO_ELEMENTOR_URL',     plugin_dir_url( __FILE__ ) );
define( 'APOLLO_ELEMENTOR_FILE',    __FILE__ );

// PSR-4 autoloader for Apollo\Elementor\ namespace.
spl_autoload_register( static function ( string $class ): void {
	$prefix = 'Apollo\\Elementor\\';
	if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
		return;
	}
	$relative = str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, strlen( $prefix ) ) );
	$file     = APOLLO_ELEMENTOR_DIR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';
	if ( is_file( $file ) ) {
		require_once $file;
	}
} );

// Gate: require Elementor to be loaded first.
add_action( 'plugins_loaded', static function (): void {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', static function (): void {
			echo '<div class="notice notice-warning"><p><strong>Apollo Elementor Pro</strong>: ';
			esc_html_e( 'Elementor must be installed and active.', 'apollo-elementor-pro' );
			echo '</p></div>';
		} );
		return;
	}

	// Gate: require apollo-core.
	if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
		add_action( 'admin_notices', static function (): void {
			echo '<div class="notice notice-warning"><p><strong>Apollo Elementor Pro</strong>: ';
			esc_html_e( 'Apollo Core plugin must be active.', 'apollo-elementor-pro' );
			echo '</p></div>';
		} );
		return;
	}

	\Apollo\Elementor\Plugin::get_instance()->init();
}, 20 );

register_uninstall_hook( __FILE__, [ \Apollo\Elementor\Uninstaller::class, 'run' ] );
