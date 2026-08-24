<?php

namespace Apollo\Radio;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Apollo\Radio\Admin\Settings;
use Apollo\Radio\API\RadioController;
use Apollo\Radio\Shortcode\RadioShortcode;

/**
 * Plugin singleton — bootstrap all components.
 */
final class Plugin {

    private static ?Plugin $instance = null;
    private bool $initialized = false;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        if ( $this->initialized ) {
            return;
        }
        $this->initialized = true;

        // Admin settings page.
        if ( is_admin() ) {
            ( new Settings() )->register();
        }

        // REST API endpoints.
        add_action( 'rest_api_init', [ new RadioController(), 'register_routes' ] );

        // Shortcode.
        ( new RadioShortcode() )->register();

        // Frontend assets hook.
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
    }

    /**
     * Register (not enqueue) JS + CSS so shortcode can enqueue on demand.
     */
    public function register_assets(): void {
        wp_register_style(
            'apollo-radio',
            APOLLO_RADIO_URL . 'assets/css/radio.css',
            [],
            APOLLO_RADIO_VERSION
        );

        wp_register_script(
            'apollo-radio-engine',
            APOLLO_RADIO_URL . 'assets/js/radio-engine.js',
            [],
            APOLLO_RADIO_VERSION,
            [ 'in_footer' => true, 'strategy' => 'defer' ]
        );

        $proxy_url = get_option( 'apollo_radio_sc_proxy_url', 'https://apradio.pages.dev/sc-proxy' );
        $cdn_url   = get_option( 'apollo_radio_json_cdn', 'https://assets.apollo.rio.br/radio/json/' );
        $fallback  = get_option( 'apollo_radio_json_fallback', '0' );

        wp_localize_script( 'apollo-radio-engine', 'apolloRadioConfig', [
            'proxyUrl'    => esc_url( $proxy_url ),
            'cdnUrl'      => esc_url( trailingslashit( $cdn_url ) ),
            'restUrl'     => esc_url_raw( rest_url( 'apollo/v1/radio/' ) ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'useFallback' => $fallback === '1',
            'version'     => APOLLO_RADIO_VERSION,
        ] );
    }

    /* ── Activation ──────────────────────────────────────────────────────── */
    public static function activate(): void {
        // Set default options if not present.
        if ( false === get_option( 'apollo_radio_sc_proxy_url' ) ) {
            update_option( 'apollo_radio_sc_proxy_url', 'https://apradio.pages.dev/sc-proxy' );
        }
        if ( false === get_option( 'apollo_radio_json_cdn' ) ) {
            update_option( 'apollo_radio_json_cdn', 'https://assets.apollo.rio.br/radio/json/' );
        }
        if ( false === get_option( 'apollo_radio_json_fallback' ) ) {
            update_option( 'apollo_radio_json_fallback', '0' );
        }
        flush_rewrite_rules();
    }

    /* ── Deactivation ────────────────────────────────────────────────────── */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
