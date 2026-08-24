<?php

/**
 * Mapa — /mapa  ·  PHASE 006
 *
 * Blank Canvas Apollo+ screen, same mount-point shape as phases 001-005:
 * opens the shared shell, renders the Mapa layout, closes. Real full-bleed
 * Leaflet explorer over apollo-maps' own data (events with loc coordinates
 * + standalone loc CPT posts), replacing the previous event-locations-only
 * "Canvas v2" template.
 *
 * Retired (2026-07-29): the prior version of this file — CARTO dark tiles,
 * hardcoded metro/parks/landmarks arrays, a "fallback simulated events"
 * block, no .ax-aside shell — is preserved for reference at
 * templates/_legacy/page-mapa.monolith.php. Per the phase decision, its
 * static points-of-interest layer was NOT carried forward: no Apollo CPT
 * backs parks/metro/beaches/museums/airports/stadiums, so this screen ships
 * with real data only (events + locs) rather than invented or copied
 * fixture content.
 *
 * Wiring is unchanged: apollo-templates.php's is_page('mapa') check still
 * points at this same file path, so no rewrite/menu change is needed for
 * the swap to take effect.
 *
 * @package Apollo\Templates
 * @since   6.1.0
 * @see     templates/template-parts/mapa/{styles,layout}.php
 * @see     includes/mapa-data.php
 */

if (! defined('ABSPATH')) {
    exit;
}

$mapa_parts = plugin_dir_path(__FILE__) . 'template-parts/mapa/';

ob_start();
require $mapa_parts . 'styles.php';
$mapa_head = ob_get_clean();

if (! function_exists('apollo_plus_open')) {
    wp_die(esc_html__('Apollo Templates: shell API indisponível.', 'apollo-templates'));
}

apollo_plus_open(
    array(
        'title'      => get_bloginfo('name') . ' — Mapa',
        'extra_head' => $mapa_head,
        'screen'     => 'mapa',
    )
);

require $mapa_parts . 'layout.php';

apollo_plus_close();
