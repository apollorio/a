<?php
/**
 * Plugin Name: Apollo — Depoimentos
 * Plugin URI:  https://apollo.rio.br
 * Description: WordPress comments relabeled as "Depoimentos" — testimonial-style cards with avatar, badge, groups.
 * Version:     1.0.1
 * Author:      Apollo
 * Author URI:  https://apollo.rio.br
 * Text Domain: apollo-comment
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.4
 *
 * @package Apollo\Comment
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─── Constants ─────────────────────────────────────────────── */
define( 'APOLLO_COMMENT_VERSION', '1.0.1' );
define( 'APOLLO_COMMENT_FILE', __FILE__ );
define( 'APOLLO_COMMENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_COMMENT_URL', plugin_dir_url( __FILE__ ) );

/* ─── PSR-4 Autoloader: Apollo\Comment\ → src/ ─────────────── */
spl_autoload_register(
	function ( string $class ) {
		$prefix   = 'Apollo\\Comment\\';
		$base_dir = APOLLO_COMMENT_PATH . 'src/';
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

/* ─── Bootstrap ─────────────────────────────────────────────── */
add_action(
	'plugins_loaded',
	function () {
		// Require apollo-core
		if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p><strong>Apollo Depoimentos</strong> requer o plugin <code>apollo-core</code>.</p></div>';
				}
			);
			return;
		}

		// Initialize
		\Apollo\Comment\Plugin::instance();
	},
	20
);
