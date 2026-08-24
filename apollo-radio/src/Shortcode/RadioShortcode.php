<?php

namespace Apollo\Radio\Shortcode;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * [apollo_radio] shortcode.
 *
 * Usage:
 *   [apollo_radio]                — fullscreen player (intro + preloader + player)
 *   [apollo_radio mode="widget"]  — compact bottom-bar widget
 */
class RadioShortcode {

    public function register(): void {
        add_shortcode( 'apollo_radio', [ $this, 'render' ] );
    }

    /**
     * @param array|string $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render( $atts ): string {
        $atts = shortcode_atts( [
            'mode' => 'full',
        ], $atts, 'apollo_radio' );

        // Enqueue assets.
        wp_enqueue_style( 'apollo-radio' );
        wp_enqueue_script( 'apollo-radio-engine' );

        // Load Apollo CDN if constant available, else hardcode.
        $cdn_url = defined( 'APOLLO_CDN_URL' )
            ? \APOLLO_CDN_URL . 'core.min.js'
            : 'https://cdn.apollo.rio.br/v1.0.0/core.min.js';

        wp_enqueue_script( 'apollo-cdn', $cdn_url, [], null, false );

        ob_start();

        $mode = sanitize_key( $atts['mode'] );

        if ( $mode === 'widget' ) {
            include APOLLO_RADIO_PATH . 'templates/radio-widget.php';
        } else {
            include APOLLO_RADIO_PATH . 'templates/radio-full.php';
        }

        return ob_get_clean();
    }
}
