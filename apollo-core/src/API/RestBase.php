<?php
/**
 * REST API Base Controller
 *
 * Base class for all Apollo REST controllers
 *
 * @package Apollo\Core\API
 * @since 6.0.0
 */

namespace Apollo\Core\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class RestBase extends \WP_REST_Controller {

	/**
	 * REST namespace
	 */
	protected $namespace = 'apollo/v1';

	public function __construct() {
		// parent::__construct(); // Not needed
	}

	public function is_logged_in() {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You must be logged in.', 'apollo-core' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	public function is_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission.', 'apollo-core' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	protected function prepare_response( $data, int $status = 200 ): \WP_REST_Response {
		$response = new \WP_REST_Response( $data, $status );
		$response->header( 'X-Apollo-Version', defined( 'APOLLO_VERSION' ) ? APOLLO_VERSION : '6.0.0' );
		return $response;
	}

	protected function prepare_error( string $code, string $message, int $status = 400 ): \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
