<?php

/**
 * Mapa — screen layout (PHASE 006).
 *
 * Host markup ported verbatim from view.mapa.js's HTML_SHELL (map canvas,
 * nav-pill filter toggle, filter-bloom, FAB, card stage, bottom sheet) —
 * the mockup itself builds this shell via JS since Leaflet needs a live DOM
 * node; rendering it server-side here just removes that one indirection.
 * Real behaviour (Leaflet, clustering, sheet physics, rail carousel) lives
 * in assets/js/mapa-explorer.js, fed by real data from
 * apollo_mapa_get_places() — no simulated.data.js, no map-places.json.
 *
 * @package Apollo\Templates
 */

if (! defined('ABSPATH')) {
    exit;
}

$mapa_places = function_exists('apollo_mapa_get_places') ? apollo_mapa_get_places() : array();

// cdn.jsdelivr.net, not unpkg.com — unpkg isn't in the CSP allowlist.
wp_enqueue_style('apollo-leaflet', 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
wp_enqueue_script('apollo-leaflet', 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
wp_enqueue_script(
    'apollo-mapa-explorer',
    APOLLO_TEMPLATES_URL . 'assets/js/mapa-explorer.js',
    array('apollo-leaflet'),
    APOLLO_TEMPLATES_VERSION,
    true
);
wp_localize_script(
    'apollo-mapa-explorer',
    'APOLLO_MAPA_PLACES',
    $mapa_places
);
?>
<div class="mapa-app" id="mapaApp">
    <div id="map"></div>
    <div class="nav-pill" id="navPill">
        <button class="nav-filter-btn" id="filterToggle" aria-label="<?php esc_attr_e('Filtros', 'apollo-templates'); ?>"><i class="ri-stack-line"></i></button>
    </div>
    <div class="filter-bloom" id="filterBloom"></div>
    <div id="bloomScrim"></div>
    <div class="map-fab-wrap" id="fabWrap" style="bottom:104px;">
        <button class="map-fab" id="focusBtn" aria-label="<?php esc_attr_e('Localização', 'apollo-templates'); ?>"><i class="ri-focus-3-line"></i></button>
    </div>
    <div class="card-stage" id="cardStage" style="bottom:-600px;"></div>
    <div id="mapScrim"></div>
    <div class="map-sheet" id="mapSheet">
        <div class="sheet-drag" id="sheetDrag"><div class="sheet-handle"></div></div>
        <div class="sheet-inner" id="sheetInner">
            <div class="sheet-hd">
                <div class="sheet-hd-row">
                    <div class="sheet-title" id="sheetTitle"><?php esc_html_e('Perto de você', 'apollo-templates'); ?></div>
                    <div class="sheet-count" id="sheetCount"></div>
                </div>
                <div class="sheet-sub"><span class="loc-dot"></span><span id="sheetSub">Rio de Janeiro</span></div>
            </div>
            <div class="place-rail" id="placeRail"></div>
            <div class="rail-controls" id="railControls" style="display:none;">
                <button class="rail-btn" id="railPrev" aria-label="<?php esc_attr_e('Anterior', 'apollo-templates'); ?>"><i class="ri-arrow-left-s-line"></i></button>
                <button class="rail-btn" id="railNext" aria-label="<?php esc_attr_e('Próximo', 'apollo-templates'); ?>"><i class="ri-arrow-right-s-line"></i></button>
            </div>
            <div class="place-sep" id="placeSep" style="display:none;"></div>
            <div class="place-list" id="placeList" style="display:none;"></div>
        </div>
    </div>
</div>
