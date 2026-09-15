<?php
/**
 * WAHA HTTP. Every request sends X-Api-Key.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Client {

	public function get( string $path, array $query = array() ): array {
		return $this->request( 'GET', $path, $query, null );
	}

	public function post( string $path, array $body = array() ): array {
		return $this->request( 'POST', $path, array(), $body );
	}

	public function put( string $path, array $body = array() ): array {
		return $this->request( 'PUT', $path, array(), $body );
	}

	private function request( string $method, string $path, array $query, ?array $body ): array {
		// TODO authorized write: wp_remote_request + X-Api-Key + timeout.
		return array(
			'ok'     => false,
			'status' => 0,
			'body'   => array(),
		);
	}
}
