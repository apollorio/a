<?php

/**
 * HomePage — Virtual /home route with Blank Canvas
 *
 * Registers rewrite rule for /home, intercepts request,
 * loads ultra-modular template with zero theme interference.
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HomePage {

    private const QUERY_VAR = 'apollo_home_page';
    private const SLUG      = 'home';

    public function __construct() {
        add_action( 'init', array( $this, 'register_rewrite' ), 5 );
        add_filter( 'query_vars', array( $this, 'add_query_var' ) );
        add_action( 'template_redirect', array( $this, 'render' ) );
    }

    /**
     * Rewrite: /home → index.php?apollo_home_page=1
     */
    public function register_rewrite(): void {
        add_rewrite_rule(
            '^' . self::SLUG . '/?$',
            'index.php?' . self::QUERY_VAR . '=1',
            'top'
        );
    }

    /**
     * @param array<string> $vars
     * @return array<string>
     */
    public function add_query_var( array $vars ): array {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    /**
     * Intercept and render Blank Canvas home page.
     */
    public function render(): void {
        if ( ! get_query_var( self::QUERY_VAR ) ) {
            return;
        }

        $this->enqueue_assets();

        // Blank Canvas — zero theme, direct output
        require APOLLO_HUB_DIR . 'templates/home/home-page.php';
        exit;
    }

    /**
     * Enqueue all modular CSS/JS for home page.
     */
    private function enqueue_assets(): void {
        $css_dir = APOLLO_HUB_URL . 'assets/css/home/';
        $js_dir  = APOLLO_HUB_URL . 'assets/js/home/';
        $ver     = APOLLO_HUB_VERSION;

        // CSS modules — order matters
        $css_modules = array(
            'tokens',
            'base',
            'fab',
            'radio',
            'hero',
            'marquee',
            'tracks',
            'events',
            'classifieds',
            'crash',
            'map',
            'footer',
            'gsap-reveal',
            'responsive',
        );

        foreach ( $css_modules as $mod ) {
            wp_enqueue_style(
                'apollo-home-' . $mod,
                $css_dir . $mod . '.css',
                array(),
                $ver
            );
        }

        // Leaflet from apollo-maps (already registered via wp_register_script)
        wp_enqueue_style( 'leaflet' );
        wp_enqueue_script( 'leaflet' );

        // Localize apollo-maps config for the home map if not already available
        if ( ! wp_script_is( 'apollo-maps', 'enqueued' ) ) {
            wp_localize_script( 'leaflet', 'apolloMapsConfig', array(
                'restUrl' => esc_url_raw( rest_url( 'apollo/v1' ) ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
            ) );
        }

        // JS modules — loaded defer
        $js_modules = array(
            'fab'   => array(),
            'radio' => array(),
            'gsap'  => array(),
            'month' => array(),
            'map'   => array( 'leaflet' ),
        );

        foreach ( $js_modules as $mod => $deps ) {
            wp_enqueue_script(
                'apollo-home-' . $mod,
                $js_dir . $mod . '.js',
                $deps,
                $ver,
                true
            );
        }
    }
}