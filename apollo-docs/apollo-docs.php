<?php
/**
 * Plugin Name:       Apollo Docs
 * Plugin URI:        https://apollo.rio.br
 * Description:       Document lifecycle management — authoring, versioning, folders, PDF generation, storage, download control.
 * Version:           1.0.1
 * Requires PHP:      8.1
 * Requires at least: 6.4
 * Author:            Apollo Platform
 * Author URI:        https://apollo.rio.br
 * License:           GPL-2.0-or-later
 * Text Domain:       apollo-docs
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── Constants ─────────────────────────────────────────────── */
define( 'APOLLO_DOCS_VERSION', '1.0.1' );
define( 'APOLLO_DOCS_DB_VERSION', 1 );
define( 'APOLLO_DOCS_FILE', __FILE__ );
define( 'APOLLO_DOCS_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_DOCS_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_DOCS_BASENAME', plugin_basename( __FILE__ ) );

/* ── PSR-4 Autoloader ──────────────────────────────────────── */
spl_autoload_register(
	function ( string $class ): void {
		$prefix   = 'Apollo\\Docs\\';
		$base_dir = APOLLO_DOCS_DIR . 'src/';
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

/* ── Activation / Deactivation ─────────────────────────────── */
register_activation_hook(
	__FILE__,
	function (): void {
		Apollo\Docs\Database::install();
		Apollo\Docs\Storage::init_directories();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function (): void {
		flush_rewrite_rules();
	}
);

/* ── Bootstrap ─────────────────────────────────────────────── */
add_action(
	'plugins_loaded',
	function (): void {
		/* Dependency check */
		if ( ! defined( 'APOLLO_CORE_VERSION' ) ) {
			add_action(
				'admin_notices',
				function (): void {
					echo '<div class="notice notice-error"><p><strong>Apollo Docs</strong> requer o plugin <code>apollo-core</code> ativo.</p></div>';
				}
			);
			return;
		}

		/* Check OpenSSL (needed for checksum / future signing bridge) */
		if ( ! extension_loaded( 'openssl' ) ) {
			add_action(
				'admin_notices',
				function (): void {
					echo '<div class="notice notice-warning"><p><strong>Apollo Docs</strong>: extensão <code>openssl</code> não detectada. Algumas funcionalidades serão limitadas.</p></div>';
				}
			);
		}

		Apollo\Docs\Plugin::get_instance()->init();
	},
	15
);
