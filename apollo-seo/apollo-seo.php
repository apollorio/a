<?php

/**
 * Plugin Name: Apollo SEO
 * Plugin URI:  https://apollo.rio.br
 * Description: Premium SEO engine — meta titles, descriptions, Open Graph, Twitter Cards, Schema.org JSON-LD, XML sitemaps, canonical URLs, robots directives, webmaster verification. Optimized for ALL Apollo CPTs, taxonomies, virtual pages, and blank canvas templates.
 * Version:     1.0.3
 * Author:      Apollo::Rio
 * Author URI:  https://apollo.rio.br
 * Text Domain: apollo-seo
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License:     GPL-2.0-or-later
 *
 * @package Apollo\SEO
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─── Constants ─────────────────────────────────────────────────── */
define( 'APOLLO_SEO_VERSION', '1.0.3' );
define( 'APOLLO_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_SEO_FILE', __FILE__ );
define( 'APOLLO_SEO_OPTION', 'apollo_seo_settings' );
define( 'APOLLO_SEO_POST_META', '_apollo_seo' );
define( 'APOLLO_SEO_TERM_META', '_apollo_seo_term' );

/* ─── PSR-4 Autoloader ──────────────────────────────────────────── */
spl_autoload_register(
	function ( string $class ): void {
		$prefix = 'Apollo\\SEO\\';
		$len    = strlen( $prefix );
		if ( strncmp( $class, $prefix, $len ) !== 0 ) {
			return;
		}
		$relative = substr( $class, $len );
		$file     = APOLLO_SEO_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/* ─── Activation / Deactivation ─────────────────────────────────── */
register_activation_hook(
	__FILE__,
	function (): void {
		$defaults = \Apollo\SEO\Settings::defaults();
		if ( ! get_option( APOLLO_SEO_OPTION ) ) {
			add_option( APOLLO_SEO_OPTION, $defaults, '', 'no' );
		}
		flush_rewrite_rules( true );
	}
);

register_deactivation_hook(
	__FILE__,
	function (): void {
		flush_rewrite_rules( true );
	}
);

/* ─── Bootstrap ─────────────────────────────────────────────────── */
add_action(
	'plugins_loaded',
	function (): void {
		\Apollo\SEO\Plugin::instance();
	},
	12
);
