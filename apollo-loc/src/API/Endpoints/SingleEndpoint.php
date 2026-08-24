<?php
/**
 * SingleEndpoint — GET /apollo/v1/local/{id}
 *
 * @package Apollo\Local\API\Endpoints
 */

declare(strict_types=1);

namespace Apollo\Local\API\Endpoints;

use Apollo\Local\API\Schema\LocalSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SingleEndpoint {

	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = absint( $request->get_param( 'id' ) );
		$post = get_post( $id );

		if ( ! $post || APOLLO_LOCAL_CPT !== $post->post_type || 'publish' !== $post->post_status ) {
			return new \WP_REST_Response( array( 'code' => 'not_found', 'message' => 'Local não encontrado.' ), 404 );
		}

		return new \WP_REST_Response( LocalSchema::prepare( $post ), 200 );
	}
}
