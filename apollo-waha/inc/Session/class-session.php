<?php
/**
 * Session strip. WORKING gate. Does not load/unload plugins.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Session {

	private static ?self $instance = null;

	/** Ping is cached this many seconds — keeps admin screen loads from hammering WAHA. */
	private const PING_TTL = 30;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {
		add_action( 'apollo/whatsapp/session_status', array( $this, 'on_status' ), 10, 1 );
	}

	/**
	 * GET /api/sessions/{session} through the Client.
	 *
	 * @return array{ok:bool,status:int,body:array} raw Client response.
	 */
	public function ping( bool $force = false ): array {
		$session   = (string) Apollo_Waha_Options::get( 'apollo_wa_session', 'apollo' );
		$cache_key = 'apollo_wa_session_ping_' . md5( $session );

		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$client = new Apollo_Waha_Client();
		$result = $client->get( '/api/sessions/' . rawurlencode( $session ) );

		set_transient( $cache_key, $result, self::PING_TTL );
		Apollo_Waha_Options::set( 'apollo_wa_last_ping', (string) time() );

		return $result;
	}

	/**
	 * The global gate. Queue, pane, and every other WAHA-writing feature
	 * must check this before doing anything — false means "session down,
	 * nothing goes out".
	 */
	public function is_working( bool $force = false ): bool {
		$result = $this->ping( $force );
		return $result['ok'] && 'WORKING' === $this->status_label( false, $result );
	}

	/**
	 * One of WORKING | SCAN_QR_CODE | STARTING | FAILED | STOPPED | DOWN.
	 *
	 * @param array $reuse Optional already-fetched ping() result, to skip a second call.
	 */
	public function status_label( bool $force = false, array $reuse = array() ): string {
		$result = $reuse ?: $this->ping( $force );
		$status = strtoupper( (string) ( $result['body']['status'] ?? '' ) );
		return $status ?: 'DOWN';
	}

	public function on_status( $payload ): void {}
}
