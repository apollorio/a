<?php
/**
 * CreateEndpoint — POST /apollo/v1/local  (apenas admin)
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

final class CreateEndpoint {

	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$title = sanitize_text_field( (string) $request->get_param( 'title' ) );

		if ( ! $title ) {
			return new \WP_REST_Response( array( 'code' => 'missing_title', 'message' => 'Título é obrigatório.' ), 400 );
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_type'    => APOLLO_LOCAL_CPT,
				// Same moderation contract as the DJ quick-add: publishers go live
				// (renders on the event page immediately); others land in 'pending'.
				'post_status'  => current_user_can( 'publish_posts' ) ? 'publish' : 'pending',
				'post_author'  => get_current_user_id(),
				'post_content' => wp_kses_post( (string) $request->get_param( 'content' ) ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return new \WP_REST_Response( array( 'code' => 'create_failed', 'message' => $post_id->get_error_message() ), 500 );
		}

		LocalSchema::save_meta( $post_id, $request );
		LocalSchema::save_taxonomies( $post_id, $request );
		Geocoder::maybe_geocode( $post_id );

		do_action( 'apollo/loc/created', $post_id, $request );

		return new \WP_REST_Response( LocalSchema::prepare( get_post( $post_id ) ), 201 );
	}
}
