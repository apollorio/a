<?php
/**
 * Funções geo do Apollo Local
 *
 * Helpers para coordenadas, distância Haversine e bounding box.
 *
 * @package Apollo\Local
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna coordenadas de um local como array ou null se ausentes.
 *
 * @param int $local_id Post ID do local.
 * @return array{lat:float, lng:float}|null Coordenadas ou null.
 */
function apollo_local_get_coords( int $local_id ): ?array {
	$lat = get_post_meta( $local_id, '_local_lat', true );
	$lng = get_post_meta( $local_id, '_local_lng', true );

	if ( empty( $lat ) || empty( $lng ) ) {
		return null;
	}

	return array(
		'lat' => (float) $lat,
		'lng' => (float) $lng,
	);
}

/**
 * Calcula distância entre dois pontos GPS usando Haversine (delegação).
 *
 * @param float $lat1 Latitude ponto A.
 * @param float $lng1 Longitude ponto A.
 * @param float $lat2 Latitude ponto B.
 * @param float $lng2 Longitude ponto B.
 * @return float Distância em km.
 */
function apollo_local_distance( float $lat1, float $lng1, float $lat2, float $lng2 ): float {
	return \Apollo\Local\Geo\HaversineCalculator::km( $lat1, $lng1, $lat2, $lng2 );
}

/**
 * Retorna URL do mapa estático Google Maps para as coordenadas.
 *
 * @param float $lat  Latitude.
 * @param float $lng  Longitude.
 * @return string URL do Google Maps direções.
 */
function apollo_local_route_url( float $lat, float $lng ): string {
	return esc_url_raw(
		sprintf( 'https://www.google.com/maps/dir/?api=1&destination=%s,%s', $lat, $lng )
	);
}

/**
 * Retorna URL para abrir ponto no OSM.
 *
 * @param float $lat  Latitude.
 * @param float $lng  Longitude.
 * @return string URL do OpenStreetMap.
 */
function apollo_local_osm_url( float $lat, float $lng ): string {
	return esc_url_raw(
		sprintf( 'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=16/%s/%s', $lat, $lng, $lat, $lng )
	);
}
