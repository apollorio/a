<?php
/**
 * Apollo Color Input — Global HDR Color Picker Web Component
 *
 * Registers the <color-input> custom element from the Apollo CDN.
 * Supports oklch, oklab, p3, rec2020, hsl, hex and all modern CSS color spaces.
 *
 * Usage (PHP):
 *   apollo_enqueue_color_input();
 *   echo apollo_color_input('oklch(75% 75% 180)', 'oklch');
 *
 * Usage (HTML, after enqueueing):
 *   <color-input value="#CE5937" colorspace="hex"></color-input>
 *
 * @package Apollo\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Constants ──────────────────────────────────────────────────────────────

define( 'APOLLO_COLOR_INPUT_VERSION', 'v0.4.0' );
define( 'APOLLO_COLOR_INPUT_HANDLE',  'apollo-color-input' );

// ─── Script registration ─────────────────────────────────────────────────────

/**
 * Register the <color-input> module script (idempotent).
 *
 * Called automatically on 'init'. Individual plugins/pages MUST call
 * apollo_enqueue_color_input() to actually load it.
 */
function apollo_register_color_input(): void {
    if ( wp_script_is( APOLLO_COLOR_INPUT_HANDLE, 'registered' ) ) {
        return;
    }

    $cdn  = defined( 'APOLLO_CDN_URL' ) ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';
    $src  = $cdn . 'js/hdr-color-input.' . APOLLO_COLOR_INPUT_VERSION . '.js';

    wp_register_script(
        APOLLO_COLOR_INPUT_HANDLE,
        $src,
        [], // no deps — it's a self-contained ES module
        null, // version managed by filename
        [ 'in_footer' => true, 'strategy' => 'defer' ]
    );

    // WordPress 6.3+ exposes wp_script_add_data('type','module') but
    // the script_loader_tag filter works across all supported WP versions.
    add_filter( 'script_loader_tag', 'apollo_color_input_module_tag', 10, 3 );
}
add_action( 'init', 'apollo_register_color_input', 5 );

/**
 * Inject type="module" on the script tag so it loads as an ES module.
 *
 * @param string $tag    Full <script> HTML.
 * @param string $handle Script handle.
 * @param string $src    Script src URL.
 * @return string
 */
function apollo_color_input_module_tag( string $tag, string $handle, string $src ): string {
    if ( APOLLO_COLOR_INPUT_HANDLE !== $handle ) {
        return $tag;
    }
    // Replace standard script tag with type="module" version.
    return '<script type="module" src="' . esc_url( $src ) . '"></script>' . "\n";
}

// ─── Public API ───────────────────────────────────────────────────────────────

/**
 * Enqueue the color-input web component for the current page.
 *
 * Call this inside any wp_enqueue_scripts, admin_enqueue_scripts, or
 * enqueue_block_assets callback before outputting a <color-input> element.
 */
function apollo_enqueue_color_input(): void {
    apollo_register_color_input();
    wp_enqueue_script( APOLLO_COLOR_INPUT_HANDLE );
}

/**
 * Render a <color-input> element string (also enqueues the script).
 *
 * @param string $value      Initial color value. Any valid CSS color string.
 * @param string $colorspace Starting color space: oklch | oklab | hsl | hex | srgb | display-p3 …
 * @param array  $attrs      Additional HTML attributes as [ 'data-name' => 'value' ].
 * @return string            Safe HTML string (NOT escaped — must be echoed directly).
 */
function apollo_color_input(
    string $value      = 'oklch(75% 75% 180)',
    string $colorspace = 'oklch',
    array  $attrs      = []
): string {
    apollo_enqueue_color_input();

    $extra = '';
    foreach ( $attrs as $k => $v ) {
        $extra .= ' ' . esc_attr( (string) $k ) . '="' . esc_attr( (string) $v ) . '"';
    }

    return sprintf(
        '<color-input value="%s" colorspace="%s"%s></color-input>',
        esc_attr( $value ),
        esc_attr( $colorspace ),
        $extra
    );
}

// ─── Auto-enqueue filter ─────────────────────────────────────────────────────

/**
 * Any Apollo plugin can opt-in to automatic enqueueing by returning true from:
 *   add_filter('apollo/color_input/enqueue_admin', '__return_true');
 *
 * Or for specific screens:
 *   add_filter('apollo/color_input/enqueue_admin', function($v){ return is_admin() && $my_screen; });
 */
add_action( 'admin_enqueue_scripts', function (): void {
    if ( apply_filters( 'apollo/color_input/enqueue_admin', false ) ) {
        apollo_enqueue_color_input();
    }
} );

add_action( 'wp_enqueue_scripts', function (): void {
    if ( apply_filters( 'apollo/color_input/enqueue_frontend', false ) ) {
        apollo_enqueue_color_input();
    }
} );
