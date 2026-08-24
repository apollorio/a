<?php
/**
 * DeleteEndpoint — DELETE /apollo/v1/local/{id}  (apenas admin)
 *
 * @package Apollo\Local\API\Endpoints
 */

declare(strict_types=1);

namespace Apollo\Local\API\Endpoints;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DeleteEndpoint {

	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = absint( $request->get_param( 'id' ) );
		$post = get_post( $id );

		if ( ! $post || APOLLO_LOCAL_CPT !== $post->post_type ) {
			return new \WP_REST_Response( array( 'code' => 'not_found', 'message' => 'Local não encontrado.' ), 404 );
		}

		do_action( 'apollo/loc/before_delete', $id );

		wp_delete_post( $id, true );

		return new \WP_REST_Response( array( 'deleted' => true, 'id' => $id ), 200 );
	}
}
