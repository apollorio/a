<?php
/**
 * NearbySearch — busca locais próximos usando SQL Haversine
 *
 * Usa bounding box para pré-filtrar no SQL e depois aplica
 * Haversine exato em PHP para precisão.
 *
 * @package Apollo\Local\Geo
 */

declare(strict_types=1);

namespace Apollo\Local\Geo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NearbySearch {

	/**
	 * Busca locais dentro de um raio e retorna com distância.
	 *
	 * @param float $lat      Latitude central.
	 * @param float $lng      Longitude central.
	 * @param float $radius   Raio em km (default 5).
	 * @param int   $per_page Máximo de resultados (max 100).
	 * @return array<int, array{post_id:int, distance:float}> Ordenados por distância ASC.
	 */
	public static function find( float $lat, float $lng, float $radius = 5.0, int $per_page = 20 ): array {
		global $wpdb;

		$per_page = min( $per_page, 100 );
		$box      = HaversineCalculator::bounding_box( $lat, $lng, $radius );

		// Haversine SQL — pré-filtra por bounding box para eficiência
		$sql = $wpdb->prepare(
			"SELECT p.ID,
				pm_lat.meta_value AS loc_lat,
				pm_lng.meta_value AS loc_lng,
				( %f * acos(
					LEAST( 1.0, GREATEST( -1.0,
						cos(radians(%f)) * cos(radians(CAST(pm_lat.meta_value AS DECIMAL(10,6)))) *
						cos(radians(CAST(pm_lng.meta_value AS DECIMAL(10,6))) - radians(%f)) +
						sin(radians(%f)) * sin(radians(CAST(pm_lat.meta_value AS DECIMAL(10,6))))
					))
				)) AS distance
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_lat ON pm_lat.post_id = p.ID AND pm_lat.meta_key = '_local_lat'
			INNER JOIN {$wpdb->postmeta} pm_lng ON pm_lng.post_id = p.ID AND pm_lng.meta_key = '_local_lng'
			WHERE p.post_type   = %s
			  AND p.post_status = 'publish'
			  AND CAST(pm_lat.meta_value AS DECIMAL(10,6)) BETWEEN %f AND %f
			  AND CAST(pm_lng.meta_value AS DECIMAL(10,6)) BETWEEN %f AND %f
			HAVING distance <= %f
			ORDER BY distance ASC
			LIMIT %d",
			6371,               // Earth radius km
			$lat,               // cos(radians(center_lat))
			$lng,               // radians(loc_lng) - radians(center_lng)
			$lat,               // sin(radians(center_lat))
			APOLLO_LOCAL_CPT,   // post_type filter
			$box['lat_min'],
			$box['lat_max'],
			$box['lng_min'],
			$box['lng_max'],
			$radius,
			$per_page
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query uses $wpdb->prepare above
		$rows = $wpdb->get_results( $sql );

		if ( empty( $rows ) ) {
			return array();
		}

		$results = array();
		foreach ( $rows as $row ) {
			// Refina com Haversine PHP para precisão máxima
			$exact_distance = HaversineCalculator::km(
				$lat,
				$lng,
				(float) $row->loc_lat,
				(float) $row->loc_lng
			);

			if ( $exact_distance <= $radius ) {
				$results[] = array(
					'post_id'  => (int) $row->ID,
					'distance' => $exact_distance,
				);
			}
		}

		// Já ordenado por SQL, mas reordena após refinamento PHP
		usort( $results, fn( $a, $b ) => $a['distance'] <=> $b['distance'] );

		return $results;
	}
}
