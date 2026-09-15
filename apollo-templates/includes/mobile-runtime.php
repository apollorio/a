<?php

/**
 * Mobile premium runtime — wires blank-canvas Apollo / Apollo+.
 *
 * Emits on apollo/canvas/before_close (every blank canvas via document-head)
 * and stays idempotent if apollo/plus/before_close also fires.
 *
 * Disable: add_filter( 'apollo/mobile/enabled', '__return_false' );
 * Refine:  add_filter( 'apollo/mobile/cells', … );
 * Manual:  do_action( 'apollo/mobile/emit' );
 *
 * @package Apollo\Templates
 * @since   1.6.0
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once APOLLO_TEMPLATES_DIR . 'templates/template-parts/mobile/_manifest.php';

/**
 * Whether the mobile premium stack should emit on this request.
 */
function apollo_mobile_enabled(): bool
{
    $on = true;
    if (is_admin() && ! wp_doing_ajax()) {
        $on = false;
    }
    return (bool) apply_filters('apollo/mobile/enabled', $on);
}

/**
 * Print the mobile cell stack once per document.
 */
function apollo_mobile_emit(): void
{
    static $done = false;
    if ($done || ! apollo_mobile_enabled()) {
        return;
    }
    $done = true;
    apollo_mobile_render_cells();
}

add_action('apollo/canvas/before_close', 'apollo_mobile_emit', 20);
add_action('apollo/plus/before_close', 'apollo_mobile_emit', 20);
add_action('apollo/mobile/emit', 'apollo_mobile_emit', 10);
