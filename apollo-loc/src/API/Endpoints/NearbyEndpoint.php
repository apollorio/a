<?php
/**
 * NearbyEndpoint — GET /apollo/v1/local/proximos
 *
 * Busca locais dentro de um raio (km) via Haversine SQL.
 *
 * @package Apollo\Local\API\Endpoints
 */

declare(strict_types=1);

namespace Apollo\Local\API\Endpoints;

use Apollo\Local\API\Schema\LocalSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NearbyEndpoint {

	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$lat      = (float) $request->get_param( 'lat' );
		$lng      = (float) $request->get_param( 'lng' );
		$radius   = max( 0.1, (float) ( $request->get_param( 'radius' ) ?: 5 ) );
		$per_page = min( 100, absint( $request->get_param( 'per_page' ) ?: 20 ) );

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID,
				        ( 6371 * acos(
				            cos( radians(%f) ) *
				            cos( radians( CAST(pm_lat.meta_value AS DECIMAL(10,7)) ) ) *
				            cos( radians( CAST(pm_lng.meta_value AS DECIMAL(10,7)) ) - radians(%f) ) +
				            sin( radians(%f) ) *
				            sin( radians( CAST(pm_lat.meta_value AS DECIMAL(10,7)) ) )
				        )) AS distance
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm_lat ON pm_lat.post_id = p.ID AND pm_lat.meta_key = '_local_lat'
				 INNER JOIN {$wpdb->postmeta} pm_lng ON pm_lng.post_id = p.ID AND pm_lng.meta_key = '_local_lng'
				 WHERE p.post_type = %s
				   AND p.post_status = 'publish'
				   AND pm_lat.meta_value != ''
				   AND pm_lng.meta_value != ''
				 HAVING distance <= %f
				 ORDER BY distance ASC
				 LIMIT %d",
				$lat,
				$lng,
				$lat,
				APOLLO_LOCAL_CPT,
				$radius,
				$per_page
			)
		);
		// phpcs:enable

		$items = array();

		foreach ( $results as $row ) {
			$post = get_post( (int) $row->ID );
			if ( $post ) {
				$item             = LocalSchema::prepare( $post );
				$item['distance'] = round( (float) $row->distance, 2 );
				$items[]          = $item;
			}
		}

		return new \WP_REST_Response( $items, 200 );
	}
}
