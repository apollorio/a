<?php
/**
 * Plugin Name: Apollo Radio
 * Plugin URI:  https://apollo.rio.br
 * Description: Ultra-pro Web Audio radio engine with BPM-aware beat-aligned crossfade mixing via WAM (Web Audio Mixer). SoundCloud streams resolved through Cloudflare Worker proxy. Independent standalone plugin, integrates seamlessly with Apollo ecosystem when available.
 * Version:     1.0.1
 * Author:      Apollo Rio
 * Author URI:  https://apollo.rio.br
 * License:     GPL-2.0-or-later
 * Text Domain: apollo-radio
 * Requires PHP: 8.1
 * Requires at least: 6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Constants ───────────────────────────────────────────────────────────── */
define( 'APOLLO_RADIO_VERSION', '1.0.1' );
define( 'APOLLO_RADIO_FILE',    __FILE__ );
define( 'APOLLO_RADIO_PATH',    plugin_dir_path( __FILE__ ) );
define( 'APOLLO_RADIO_URL',     plugin_dir_url( __FILE__ ) );

/* ── PSR-4 Autoloader: Apollo\Radio\ → src/ ──────────────────────────────── */
spl_autoload_register( function ( string $class ) {
    $prefix   = 'Apollo\\Radio\\';
    $base_dir = APOLLO_RADIO_PATH . 'src/';
    $len      = strlen( $prefix );

    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative = substr( $class, $len );
    $file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/* ── Bootstrap ───────────────────────────────────────────────────────────── */
add_action( 'plugins_loaded', function () {
    Apollo\Radio\Plugin::instance()->init();
}, 15 );

/* ── Activation / Deactivation ───────────────────────────────────────────── */
register_activation_hook( __FILE__, [ Apollo\Radio\Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Apollo\Radio\Plugin::class, 'deactivate' ] );

/* ── Apollo ecosystem integration (optional) ─────────────────────────────── */
add_action( 'apollo/core/initialized', function ( $info ) {
    do_action( 'apollo/radio/initialized', APOLLO_RADIO_VERSION );
}, 10, 1 );
