<?php
/**
 * ListEndpoint — GET /apollo/v1/local
 *
 * Lista paginada de locais com filtro por tipo, área e busca textual.
 *
 * @package Apollo\Local\API\Endpoints
 */

declare(strict_types=1);

namespace Apollo\Local\API\Endpoints;

use Apollo\Local\API\Schema\LocalSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ListEndpoint {

	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$args = array(
			'post_type'      => APOLLO_LOCAL_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ) ?: 20,
			'paged'          => $request->get_param( 'page' ) ?: 1,
			'orderby'        => $request->get_param( 'orderby' ) ?: 'title',
			'order'          => strtoupper( (string) ( $request->get_param( 'order' ) ?: 'ASC' ) ),
		);

		$tax_query = array();

		if ( $type = sanitize_text_field( (string) $request->get_param( 'type' ) ) ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_LOCAL_TAX_TYPE,
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $type ) ),
			);
		}

		if ( $area = sanitize_text_field( (string) $request->get_param( 'area' ) ) ) {
			$tax_query[] = array(
				'taxonomy' => APOLLO_LOCAL_TAX_AREA,
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $area ) ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		if ( $search = sanitize_text_field( (string) $request->get_param( 'search' ) ) ) {
			$args['s'] = $search;
		}

		$query = new \WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = LocalSchema::prepare( $post );
		}

		$response = new \WP_REST_Response( $items, 200 );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}
}
