<?php

/**
 * LEGACY — preserved for rollback only, no longer wired to /mapa.
 *
 * Retired PHASE 006 (2026-07-29): replaced by templates/page-mapa.php, a
 * Blank Canvas Apollo+ screen matching the new mockup's full-bleed Leaflet
 * explorer (real events + real loc CPT posts via apollo-maps'
 * /apollo/v1/map/explorer). Decision: the old metro/parks/landmarks static
 * arrays below and the "fallback simulated events" block have no backing
 * Apollo CPT/plugin data source, so per the "real data only" call for this
 * phase they were not carried forward. If that curated points-of-interest
 * layer is wanted again later, it needs a real data source first (a CPT/
 * taxonomy or a deliberately-adopted static reference file), not a revert
 * to this file's inline fixtures.
 *
 * Template Name: Apollo Mapa (legacy)
 * Template Post Type: page
 *
 * Full-screen interactive map — events, metros, parks, landmarks.
 * Canvas v2: CDN core.js loads everything. No wp_head/wp_footer.
 * Leaflet.js with CARTO Positron tiles (dark variant for dark-mode).
 *
 * Layers:
 *   - Events (pulsing orange markers from event CPT with _local_lat/_local_lng)
 *   - Metrô Rio stations (static GeoJSON)
 *   - Public parks & praças famosas
 *   - Hot sightseeing landmarks
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-rest.json → apollo-loc → /mapa
 * @see     _inventory/apollo-registry.json → meta.local
 */

defined('ABSPATH') || exit;
define('APOLLO_NAVBAR_LOADED', true);

$parts     = plugin_dir_path(__FILE__) . 'template-parts/new-home/';
$is_logged = is_user_logged_in();

// ═══════════════════════════════════════════════════════════════════════
// COLLECT EVENT MARKERS (from event CPT → linked local → _local_lat/lng)
// ═══════════════════════════════════════════════════════════════════════
$event_markers = array();

$events_q = new WP_Query(
    array(
        'post_type'      => 'event',
        'posts_per_page' => 60,
        'post_status'    => 'publish',
        'meta_key'       => '_event_start_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => '_event_start_date',
                'value'   => current_time('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    )
);

if ($events_q->have_posts()) {
    while ($events_q->have_posts()) {
        $events_q->the_post();
        $eid    = get_the_ID();
        $loc_id = get_post_meta($eid, '_event_loc_id', true);
        if (! $loc_id) {
            continue;
        }
        $lat = (float) get_post_meta($loc_id, '_local_lat', true);
        $lng = (float) get_post_meta($loc_id, '_local_lng', true);
        if (! $lat || ! $lng) {
            continue;
        }

        $start_date = get_post_meta($eid, '_event_start_date', true);
        $start_time = get_post_meta($eid, '_event_start_time', true);
        $loc_name   = get_the_title($loc_id);

        $event_markers[] = array(
            'id'    => $eid,
            'lat'   => $lat,
            'lng'   => $lng,
            'title' => get_the_title(),
            'loc'   => $loc_name,
            'date'  => $start_date ? date_i18n('d/m', strtotime($start_date)) : '',
            'time'  => $start_time ?: '',
            'url'   => get_permalink(),
        );
    }
    wp_reset_postdata();
}

// Fallback simulated events if no real data
if (empty($event_markers)) {
    $event_markers = array(
        array(
            'id'    => 0,
            'lat'   => -22.9133,
            'lng'   => -43.1787,
            'title' => 'Baile da Lapa',
            'loc'   => 'Arcos da Lapa',
            'date'  => '',
            'time'  => '21h',
            'url'   => '#',
        ),
        array(
            'id'    => 0,
            'lat'   => -22.9711,
            'lng'   => -43.1822,
            'title' => 'Jazz Night Copa',
            'loc'   => 'Copacabana',
            'date'  => '',
            'time'  => '23h',
            'url'   => '#',
        ),
        array(
            'id'    => 0,
            'lat'   => -22.9863,
            'lng'   => -43.2001,
            'title' => 'Sunset Session',
            'loc'   => 'Ipanema',
            'date'  => '',
            'time'  => '17h',
            'url'   => '#',
        ),
        array(
            'id'    => 0,
            'lat'   => -22.9796,
            'lng'   => -43.2211,
            'title' => 'Club Underground',
            'loc'   => 'Leblon',
            'date'  => '',
            'time'  => '00h',
            'url'   => '#',
        ),
        array(
            'id'    => 0,
            'lat'   => -22.9463,
            'lng'   => -43.1875,
            'title' => 'Festival Indie Bota',
            'loc'   => 'Botafogo',
            'date'  => '',
            'time'  => '19h',
            'url'   => '#',
        ),
    );
}

// ═══════════════════════════════════════════════════════════════════════
// COLLECT LOCALS (loc CPT with coords)
// ═══════════════════════════════════════════════════════════════════════
$loc_markers = array();

$locals_q = new WP_Query(
    array(
        'post_type'      => 'local',
        'posts_per_page' => 100,
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_local_lat',
                'compare' => 'EXISTS',
            ),
            array(
                'key'     => '_local_lng',
                'compare' => 'EXISTS',
            ),
        ),
    )
);

if ($locals_q->have_posts()) {
    while ($locals_q->have_posts()) {
        $locals_q->the_post();
        $lid = get_the_ID();
        $lat = (float) get_post_meta($lid, '_local_lat', true);
        $lng = (float) get_post_meta($lid, '_local_lng', true);
        if (! $lat || ! $lng) {
            continue;
        }

        $types = wp_get_post_terms($lid, 'local_type', array('fields' => 'names'));
        $areas = wp_get_post_terms($lid, 'local_area', array('fields' => 'names'));

        $loc_markers[] = array(
            'id'   => $lid,
            'lat'  => $lat,
            'lng'  => $lng,
            'name' => get_the_title(),
            'type' => (! is_wp_error($types) && ! empty($types)) ? $types[0] : '',
            'area' => (! is_wp_error($areas) && ! empty($areas)) ? $areas[0] : '',
            'url'  => get_permalink(),
        );
    }
    wp_reset_postdata();
}

// ═══════════════════════════════════════════════════════════════════════
// STATIC POI DATA — Metrô, Parks, Landmarks
// ═══════════════════════════════════════════════════════════════════════

$metro_stations = array(
    array('name' => 'Uruguai', 'lat' => -22.9067, 'lng' => -43.2385, 'line' => 1),
    array('name' => 'Saens Peña', 'lat' => -22.9093, 'lng' => -43.2339, 'line' => 1),
    array('name' => 'São Francisco Xavier', 'lat' => -22.9117, 'lng' => -43.2238, 'line' => 1),
    array('name' => 'Afonso Pena', 'lat' => -22.9130, 'lng' => -43.2176, 'line' => 1),
    array('name' => 'Estácio', 'lat' => -22.9135, 'lng' => -43.2050, 'line' => 1),
    array('name' => 'Praça Onze', 'lat' => -22.9112, 'lng' => -43.1998, 'line' => 1),
    array('name' => 'Central', 'lat' => -22.9027, 'lng' => -43.1720, 'line' => 1),
    array('name' => 'Presidente Vargas', 'lat' => -22.9033, 'lng' => -43.1797, 'line' => 1),
    array('name' => 'Uruguaiana', 'lat' => -22.9048, 'lng' => -43.1793, 'line' => 1),
    array('name' => 'Carioca', 'lat' => -22.9068, 'lng' => -43.1766, 'line' => 1),
    array('name' => 'Cinelândia', 'lat' => -22.9101, 'lng' => -43.1756, 'line' => 1),
    array('name' => 'Glória', 'lat' => -22.9176, 'lng' => -43.1767, 'line' => 1),
    array('name' => 'Catete', 'lat' => -22.9270, 'lng' => -43.1773, 'line' => 1),
    array('name' => 'Largo do Machado', 'lat' => -22.9329, 'lng' => -43.1778, 'line' => 1),
    array('name' => 'Flamengo', 'lat' => -22.9391, 'lng' => -43.1732, 'line' => 1),
    array('name' => 'Botafogo', 'lat' => -22.9515, 'lng' => -43.1872, 'line' => 1),
    array('name' => 'Cardeal Arcoverde', 'lat' => -22.9587, 'lng' => -43.1815, 'line' => 1),
    array('name' => 'Siqueira Campos', 'lat' => -22.9719, 'lng' => -43.1817, 'line' => 1),
    array('name' => 'Cantagalo', 'lat' => -22.9783, 'lng' => -43.1916, 'line' => 1),
    array('name' => 'General Osório', 'lat' => -22.9862, 'lng' => -43.1978, 'line' => 1),
    array('name' => 'Jardim de Alah', 'lat' => -22.9845, 'lng' => -43.2076, 'line' => 4),
    array('name' => 'Antero de Quental', 'lat' => -22.9782, 'lng' => -43.2179, 'line' => 4),
    array('name' => 'Jardim Oceânico', 'lat' => -22.9879, 'lng' => -43.3656, 'line' => 4),
    array('name' => 'São Conrado', 'lat' => -22.9933, 'lng' => -43.2726, 'line' => 4),
    array('name' => 'Barra da Tijuca', 'lat' => -22.9994, 'lng' => -43.3652, 'line' => 4),
);

$parks = array(
    array('name' => 'Aterro do Flamengo', 'lat' => -22.9345, 'lng' => -43.1695),
    array('name' => 'Jardim Botânico', 'lat' => -22.9673, 'lng' => -43.2247),
    array('name' => 'Parque Lage', 'lat' => -22.9590, 'lng' => -43.2124),
    array('name' => 'Floresta da Tijuca', 'lat' => -22.9503, 'lng' => -43.2805),
    array('name' => 'Quinta da Boa Vista', 'lat' => -22.9054, 'lng' => -43.2269),
    array('name' => 'Campo de Santana', 'lat' => -22.9076, 'lng' => -43.1891),
    array('name' => 'Praça Mauá', 'lat' => -22.8962, 'lng' => -43.1761),
    array('name' => 'Praia de Copacabana', 'lat' => -22.9711, 'lng' => -43.1822),
    array('name' => 'Praia de Ipanema', 'lat' => -22.9863, 'lng' => -43.2001),
    array('name' => 'Mirante Dona Marta', 'lat' => -22.9452, 'lng' => -43.1911),
);

$landmarks = array(
    array('name' => 'Cristo Redentor', 'lat' => -22.9519, 'lng' => -43.2105, 'icon' => 'ri-landscape-fill'),
    array('name' => 'Pão de Açúcar', 'lat' => -22.9488, 'lng' => -43.1563, 'icon' => 'ri-landscape-fill'),
    array('name' => 'Maracanã', 'lat' => -22.9121, 'lng' => -43.2302, 'icon' => 'ri-football-fill'),
    array('name' => 'Arcos da Lapa', 'lat' => -22.9133, 'lng' => -43.1787, 'icon' => 'ri-ancient-gate-fill'),
    array('name' => 'Escadaria Selarón', 'lat' => -22.9152, 'lng' => -43.1794, 'icon' => 'ri-stairs-fill'),
    array('name' => 'MAM Rio', 'lat' => -22.9136, 'lng' => -43.1704, 'icon' => 'ri-gallery-fill'),
    array('name' => 'Museu do Amanhã', 'lat' => -22.8941, 'lng' => -43.1793, 'icon' => 'ri-building-4-fill'),
    array('name' => 'Theatro Municipal', 'lat' => -22.9092, 'lng' => -43.1765, 'icon' => 'ri-building-fill'),
    array('name' => 'AquaRio', 'lat' => -22.8939, 'lng' => -43.1861, 'icon' => 'ri-water-flash-fill'),
    array('name' => 'Pedra do Telégrafo', 'lat' => -23.0520, 'lng' => -43.5045, 'icon' => 'ri-camera-fill'),
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="dark-mode">

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#0A0A0A">
    <title><?php echo esc_html(get_bloginfo('name')); ?> — Mapa</title>

    <script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.apollo.rio.br/v1.0.0/css/99-osm-map.min.css">

    <style id="apollo-mapa">
        * { margin: 0; padding: 0; box-sizing: border-box }
        html, body { height: 100%; overflow: hidden; background: var(--bg, #0A0A0A); color: var(--txt-color, #E8E8E8); font-family: var(--ff-main, 'Space Grotesk', system-ui, sans-serif) }
        .mapa-wrap { position: fixed; inset: 0 }
        #apolloMapFull { width: 100%; height: 100% }
    </style>

    <?php do_action('apollo/mapa/head'); ?>
</head>

<body>
    <?php
    apollo_render_navbar();
    require $parts . 'menu-fab.php';
    ?>

    <div class="mapa-wrap">
        <div id="apolloMapFull" data-lat="-22.9502" data-lng="-43.1903" data-zoom="12" role="application" aria-label="<?php esc_attr_e('Mapa interativo do Rio de Janeiro', 'apollo-templates'); ?>"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        (function() {
            'use strict';
            var EVENTS = <?php echo wp_json_encode($event_markers); ?>;
            var LOCALS = <?php echo wp_json_encode($loc_markers); ?>;
            var METROS = <?php echo wp_json_encode($metro_stations); ?>;
            var PARKS = <?php echo wp_json_encode($parks); ?>;
            var LANDMARKS = <?php echo wp_json_encode($landmarks); ?>;
            var IS_LOGGED = <?php echo $is_logged ? 'true' : 'false'; ?>;
            /* Full engine preserved in version control history — see the
               pre-2026-07-29 copy of templates/page-mapa.php for the complete
               Leaflet wiring (this legacy file is kept only as documentation
               of what previously shipped at /mapa, not as a live fallback). */
        })();
    </script>

    <?php do_action('apollo/mapa/after_content'); ?>
</body>

</html>
