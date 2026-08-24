<?php

/**
 * Main Plugin Class (Singleton)
 *
 * @package Apollo\Maps
 */

declare(strict_types=1);

namespace Apollo\Maps;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

use Apollo\Maps\API\ExplorerController;
use Apollo\Maps\Shortcodes\MapShortcode;

/**
 * Main Plugin class
 */
final class Plugin
{

    /**
     * Plugin instance
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Get plugin instance (Singleton)
     */
    public static function get_instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct()
    {
        // Singleton - use get_instance()
    }

    /**
     * Initialize plugin
     */
    public function init(): void
    {
        add_action('init', [$this, 'register_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        $this->init_components();
    }

    /**
     * Initialize plugin components
     */
    private function init_components(): void
    {
        new MapShortcode();
    }

    /**
     * Register plugin assets
     */
    public function register_assets(): void
    {
        // CDN core (ensures GSAP/RemixIcon/css vars)
        if (defined('APOLLO_CDN_URL')) {
            wp_register_script('apollo-cdn', apollo_cdn_core_js_url(), array(), null, false);
        }

        // Leaflet (OSM) — served from cdn.jsdelivr.net, not unpkg.com: the
        // blank-canvas CSP's script-src/style-src allowlist cdn.jsdelivr.net
        // but not unpkg, so every unpkg-sourced Leaflet tag was silently
        // blocked by the browser (see 2026-08-01 CSP audit note in
        // apollo-events/includes/render-single.php).
        wp_register_style(
            'leaflet',
            'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        wp_register_script(
            'leaflet',
            'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        // Apollo Maps assets
        wp_register_style(
            'apollo-maps',
            APOLLO_MAPS_URL . 'assets/css/apollo-maps.css',
            array('leaflet'),
            APOLLO_MAPS_VERSION
        );

        wp_register_script(
            'apollo-maps',
            APOLLO_MAPS_URL . 'assets/js/apollo-maps.js',
            array('leaflet'),
            APOLLO_MAPS_VERSION,
            true
        );

        wp_localize_script(
            'apollo-maps',
            'apolloMapsConfig',
            array(
                'restUrl'       => esc_url_raw(rest_url(APOLLO_MAPS_REST_NAMESPACE)),
                'nonce'         => wp_create_nonce('wp_rest'),
                'defaultCenter' => array(-22.9502, -43.1903),
                'defaultZoom'   => 12,
                'tiles'         => array(
                    'url'     => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
                    'options' => array(
                        'subdomains'  => 'abcd',
                        'maxZoom'     => 20,
                        'attribution' => '&copy; OpenStreetMap, &copy; CARTO',
                    ),
                ),
            )
        );
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes(): void
    {
        $controller = new ExplorerController();
        $controller->register_routes();
    }
}
