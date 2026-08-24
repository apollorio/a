<?php
/**
 * UpdateEndpoint — PUT/PATCH /apollo/v1/local/{id}  (apenas admin)
 *
 * @package Apollo\Local\API\Endpoints
 */

declare(strict_types=1);

namespace Apollo\Local\API\Endpoints;

use Apollo\Local\API\Schema\LocalSchema;
use Apollo\Local\Geocoder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UpdateEndpoint {

	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = absint( $request->get_param( 'id' ) );
		$post = get_post( $id );

		if ( ! $post || APOLLO_LOCAL_CPT !== $post->post_type ) {
			return new \WP_REST_Response( array( 'code' => 'not_found', 'message' => 'Local não encontrado.' ), 404 );
		}

		$update = array( 'ID' => $id );

		if ( $title = $request->get_param( 'title' ) ) {
			$update['post_title'] = sanitize_text_field( $title );
		}
		if ( $request->has_param( 'content' ) ) {
			$update['post_content'] = wp_kses_post( (string) $request->get_param( 'content' ) );
		}

		wp_update_post( $update );
		LocalSchema::save_meta( $id, $request );
		LocalSchema::save_taxonomies( $id, $request );
		Geocoder::maybe_geocode( $id );

		do_action( 'apollo/loc/updated', $id, $request );

		return new \WP_REST_Response( LocalSchema::prepare( get_post( $id ) ), 200 );
	}
}
