<?php

/**
 * New Home — Map Explorer Section
 *
 * Leaflet.js map with CARTO Positron Light tiles.
 * Pulsing orange markers sourced ONLY from event loc coordinates.
 * Meta flow: event → _event_loc_id → loc post → _local_lat / _local_lng
 * FORBIDDEN: do not use "venue" or "location" — use "loc"
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// ── Collect event loc coordinates for map markers ──────────────────────────
// Source: events CPT → _event_loc_id → loc post → _local_lat / _local_lng
// Fallback simulated RJ data shown only when no event has geo coords.
$map_events = array();

$days_pt = array(
    'Mon' => 'Seg',
    'Tue' => 'Ter',
    'Wed' => 'Qua',
    'Thu' => 'Qui',
    'Fri' => 'Sex',
    'Sat' => 'Sáb',
    'Sun' => 'Dom',
);

$map_query = new WP_Query(array(
    'post_type'      => 'event',
    'posts_per_page' => 20,
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
));

if ($map_query->have_posts()) {
    while ($map_query->have_posts()) {
        $map_query->the_post();
        $event_id = get_the_ID();
        $loc_id   = (int) get_post_meta($event_id, '_event_loc_id', true);

        if (! $loc_id) {
            continue;
        }

        $lat = get_post_meta($loc_id, '_local_lat', true);
        $lng = get_post_meta($loc_id, '_local_lng', true);

        if (! $lat || ! $lng) {
            continue;
        }

        $start_date = get_post_meta($event_id, '_event_start_date', true);
        $start_time = get_post_meta($event_id, '_event_start_time', true);
        $tag        = '';

        if ($start_date) {
            $day_en = gmdate('D', strtotime($start_date));
            $tag    = isset($days_pt[$day_en]) ? $days_pt[$day_en] : $day_en;
            $tag   .= $start_time ? ' ' . esc_html($start_time) : '';
        }

        $map_events[] = array(
            'lat'   => (float) $lat,
            'lng'   => (float) $lng,
            'title' => get_the_title(),
            'tag'   => $tag,
        );
    }
    wp_reset_postdata();
}

// Fallback simulated RJ data — only shown when no event has geo coords
if (empty($map_events)) {
    $map_events = array(
        array('lat' => -22.9133, 'lng' => -43.1787, 'title' => 'Baile da Lapa',        'tag' => 'Dom 21h'),
        array('lat' => -22.9711, 'lng' => -43.1822, 'title' => 'Jazz Night Copa',       'tag' => 'Sex 23h'),
        array('lat' => -22.9863, 'lng' => -43.2001, 'title' => 'Sunset Session',        'tag' => 'Sáb 17h'),
        array('lat' => -22.9796, 'lng' => -43.2211, 'title' => 'Club Underground',      'tag' => 'Sex 00h'),
        array('lat' => -22.9248, 'lng' => -43.1765, 'title' => 'Arte & Baile · StaTe', 'tag' => 'Sáb 20h'),
        array('lat' => -23.0086, 'lng' => -43.3223, 'title' => 'Pool Rave Barra',       'tag' => 'Dom 15h'),
        array('lat' => -22.9463, 'lng' => -43.1875, 'title' => 'Festival Indie Bota',   'tag' => 'Sex 19h'),
        array('lat' => -22.9226, 'lng' => -43.1765, 'title' => 'Samba da Glória',       'tag' => 'Sáb 18h'),
        array('lat' => -22.9682, 'lng' => -43.1747, 'title' => 'Flamengo Open Air',     'tag' => 'Dom 16h'),
        array('lat' => -22.9411, 'lng' => -43.2293, 'title' => 'Gávea Night Market',    'tag' => 'Sex 20h'),
    );
}
?>

<section class="section" id="map" aria-labelledby="map-title">
    <div class="container">

        <div class="nh-section-head ai" style="margin-bottom:24px;">
            <h2 id="map-title">Mapa</h2>
        </div>

        <div class="nh-map-wrap">
            <div id="nhMap"
                class="nh-map-canvas" style="width:100%;height:100%;min-height:380px;" data-lat="-22.9502"
                data-lng="-43.1903"
                data-zoom="12"
                role="region"
                aria-label="<?php esc_attr_e('Mapa de eventos no Rio de Janeiro', 'apollo-templates'); ?>">
            </div>
            <!-- Whole map is a tap target → opens the full map site in a new tab. -->
            <a class="nh-map-open" href="https://mapa.apollo.rio.br/" target="_blank" rel="noopener"
                aria-label="<?php esc_attr_e('Abrir mapa completo', 'apollo-templates'); ?>">
                <span class="nh-map-open-chip"><i class="ri-fullscreen-line"></i> <?php esc_html_e('Abrir mapa', 'apollo-templates'); ?></span>
            </a>
        </div>

    </div>
</section>

<!-- Leaflet JS + simple init. cdn.jsdelivr.net, not unpkg.com (unpkg isn't
     CSP-allowlisted — see 2026-08-01 audit note in render-single.php). -->
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    (function() {
        'use strict';

        var EVENTS = <?php echo wp_json_encode($map_events); ?>;

        function waitForLeaflet(cb, n) {
            n = n || 0;
            if (typeof L !== 'undefined') {
                cb();
            } else if (n < 80) {
                setTimeout(function() {
                    waitForLeaflet(cb, n + 1);
                }, 50);
            }
        }

        waitForLeaflet(function() {
            var el = document.getElementById('nhMap');
            if (!el) return;

            var map = L.map(el, {
                center: [-22.9502, -43.1903],
                zoom: 12,
                zoomControl: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                touchZoom: false,
                boxZoom: false,
                keyboard: false,
                dragging: true
            });

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                subdomains: 'abcd',
                maxZoom: 20
            }).addTo(map);

            function makePulseIcon() {
                return L.divIcon({
                    className: '',
                    html: '<div class="nh-map-pulse-wrap">' +
                        '<div class="nh-map-pulse-ring nh-pulse-a"></div>' +
                        '<div class="nh-map-pulse-ring nh-pulse-b"></div>' +
                        '</div>',
                    iconSize: [80, 80],
                    iconAnchor: [40, 40]
                });
            }

            EVENTS.forEach(function(ev) {
                L.marker([ev.lat, ev.lng], {
                    icon: makePulseIcon()
                }).addTo(map);
            });

            window.ApolloMapHome = map;
        });

        /* CARTO tile enforcer — prevents OSM tile fallback */
        var obs = new MutationObserver(function(muts) {
            muts.forEach(function(m) {
                m.addedNodes.forEach(function(n) {
                    if (n.tagName === 'IMG' && n.src && n.src.indexOf('openstreetmap') !== -1) {
                        n.src = n.src.replace(/tile\.openstreetmap\.org/, 'a.basemaps.cartocdn.com/light_all');
                    }
                });
            });
        });
        var mc = document.getElementById('nhMap');
        if (mc) obs.observe(mc, {
            childList: true,
            subtree: true
        });
    })();
</script>