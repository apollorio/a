<?php
/**
 * WAHA HTTP. Every request sends X-Api-Key.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Client {

	private const TIMEOUT = 20;

	public function get( string $path, array $query = array() ): array {
		return $this->request( 'GET', $path, $query, null );
	}

	public function post( string $path, array $body = array() ): array {
		return $this->request( 'POST', $path, array(), $body );
	}

	public function put( string $path, array $body = array() ): array {
		return $this->request( 'PUT', $path, array(), $body );
	}

	/**
	 * @return array{ok:bool,status:int,body:array}
	 */
	private function request( string $method, string $path, array $query, ?array $body ): array {
		$base = (string) Apollo_Waha_Options::get( 'apollo_wa_base_url', 'http://127.0.0.1:3000' );
		$key  = (string) Apollo_Waha_Options::get( 'apollo_wa_api_key', '' );

		$url = rtrim( $base, '/' ) . '/' . ltrim( $path, '/' );
		if ( $query ) {
			$url = add_query_arg( $query, $url );
		}

		$args = array(
			'method'  => $method,
			'timeout' => self::TIMEOUT,
			'headers' => array(
				'X-Api-Key' => $key,
				'Accept'    => 'application/json',
			),
		);

		if ( null !== $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = (string) wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		// wp_remote_request() WP_Error objects never carry request headers —
		// nothing below this line can leak the key, on success or failure.
		if ( is_wp_error( $response ) ) {
			return array(
				'ok'     => false,
				'status' => 0,
				'body'   => array(),
			);
		}

		$status  = (int) wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return array(
			'ok'     => $status >= 200 && $status < 300,
			'status' => $status,
			'body'   => is_array( $decoded ) ? $decoded : array(),
		);
	}
}
