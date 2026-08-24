<?php

/**
 * Shortcode: [apollo_maps]
 *
 * @package Apollo\Maps
 */

declare(strict_types=1);

namespace Apollo\Maps\Shortcodes;

if (! defined('ABSPATH')) {
    exit;
}

class MapShortcode
{

    public function __construct()
    {
        add_shortcode('apollo_maps', array($this, 'render_map'));
    }

    /**
     * Render [apollo_maps]
     */
    public function render_map($atts): string
    {
        $atts = shortcode_atts(
            array(
                'center'       => '',
                'zoom'         => get_option('apollo_maps_default_zoom', 12),
                'height'       => '520px',
                'per_page'     => 60,
                'upcoming'     => 1,
                'show_events'  => 1,
                'show_locs'    => 1,
            ),
            $atts,
            'apollo_maps'
        );

        $center_lat = (float) get_option('apollo_maps_center_lat', -22.9502);
        $center_lng = (float) get_option('apollo_maps_center_lng', -43.1903);

        if (! empty($atts['center'])) {
            $parts = array_map('trim', explode(',', (string) $atts['center']));
            if (count($parts) === 2) {
                $center_lat = (float) $parts[0];
                $center_lng = (float) $parts[1];
            }
        }

        $zoom       = (int) $atts['zoom'];
        $per_page   = max(1, min(150, (int) $atts['per_page']));
        $upcoming   = (int) $atts['upcoming'] ? 1 : 0;
        $show_events = (int) $atts['show_events'] ? 1 : 0;
        $show_locs   = (int) $atts['show_locs'] ? 1 : 0;

        $height = is_string($atts['height']) ? trim($atts['height']) : '520px';
        if (! preg_match('/^\d+(px|vh|%)?$/', $height)) {
            $height = '520px';
        }

        $map_id = 'apollo-maps-' . wp_unique_id();

        // Assets
        if (wp_script_is('apollo-cdn', 'registered')) {
            wp_enqueue_script('apollo-cdn');
        }
        wp_enqueue_style('apollo-maps');
        wp_enqueue_script('apollo-maps');
        wp_enqueue_style('leaflet');
        wp_enqueue_script('leaflet');

        return sprintf(
            '<div class="apollo-maps-wrap">' .
                '<div id="%1$s" class="apollo-maps-canvas" role="region" aria-label="Mapa Apollo" ' .
                '	style="--apollo-maps-height:%2$s;" ' .
                '	data-center-lat="%3$s" data-center-lng="%4$s" data-zoom="%5$d" ' .
                '	data-per-page="%6$d" data-upcoming="%7$d" data-show-events="%8$d" data-show-locs="%9$d">' .
                '</div>' .
                '<div class="apollo-map-legend" aria-hidden="true">' .
                '<span class="pill event">Eventos</span>' .
                '<span class="pill loc">Loc</span>' .
                '</div>' .
                '</div>',
            esc_attr($map_id),
            esc_attr($height),
            esc_attr((string) $center_lat),
            esc_attr((string) $center_lng),
            $zoom,
            $per_page,
            $upcoming,
            $show_events,
            $show_locs
        );
    }
}
