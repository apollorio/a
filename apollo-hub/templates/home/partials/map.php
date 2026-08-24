<?php
/**
 * Partial: Map — Leaflet.js explorer with pulsing markers.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section class="section" id="map" aria-labelledby="map-title">
    <div class="container">
        <div class="nh-section-head ai">
            <h2 id="map-title">Explorer</h2>
        </div>
        <div class="nh-map-wrap">
            <div id="nhMap"
                 class="nh-map-canvas"
                 data-lat="-22.9502"
                 data-lng="-43.1903"
                 data-zoom="12"
                 role="region"
                 aria-label="Mapa de eventos no Rio de Janeiro">
            </div>
        </div>
    </div>
</section>
