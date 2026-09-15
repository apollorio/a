<?php
/**
 * POST /apollo/v1/wa/webhook
 * Pipeline: docs/WEBHOOK-SECURITY.md — fail closed.
 * Never approves the join queue.
 *
 * Steps 1-9 of registry webhook.pipeline[] run in verify(), in SSOT order.
 * Step 10 (the event switch) runs in handle(). Step 7 is split: the dedupe
 * LOOKUP happens at position 7 inside verify() but does not return there, so
 * steps 8 and 9 still evaluate in order; handle() emits the 200-empty.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Webhook {

	private static ?self $instance = null;

	/** Step 9 — 256 KB. */
	private const MAX_BODY = 262144;

	/** Step 6 — 300 s, expressed in ms because WAHA sends unix MILLISECONDS. */
	private const TS_WINDOW_MS = 300000;

	/** Step 7 — registry webhook.transient_prefix + 10 minute TTL. */
	private const TRANSIENT_PREFIX = 'apollo_wa_hook_';
	private const DEDUPE_TTL       = 600;

	/**
	 * Per-request state carried from verify() to handle().
	 *
	 * Keyed by spl_object_id( $request ): WP_REST_Server::dispatch() hands the
	 * SAME WP_REST_Request instance to permission_callback and callback. Not
	 * $request->set_param() — that would inject a fake field into the very
	 * payload step 10 reads. Not a static bool — rest_do_request() can dispatch
	 * more than once per PHP process.
	 *
	 * @var array<int,array>
	 */
	private array $state = array();

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	public function register_route(): void {
		register_rest_route(
			'apollo/v1',
			'/wa/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'verify' ),
			)
		);
	}

	/**
	 * Pipeline steps 1-9. Single-exit discipline: exactly one `return true`, at
	 * the bottom. Every other exit is deny(). Never returns a bare false — that
	 * would yield 403 for a logged-in caller instead of the specified 401.
	 *
	 * @return true|WP_Error
	 */
	public function verify( WP_REST_Request $request ) {
		try {
			// 1. https in production; LocalWP lab allowed.
			if ( ! $this->transport_ok() ) {
				return $this->deny();
			}

			// 2. raw body, read once. Steps 5, 8 and 9 all use THESE bytes — hashing
			// one body and handling another is the classic signature bypass.
			$raw = $this->raw_body( $request );

			// 3. signature header required. Refuse before hashing if the secret is
			// unusable: hash_hmac with an empty key still yields a digest anyone can
			// compute. A missing secret is an outage, never an open door.
			$secret = (string) Apollo_Waha_Options::get( 'apollo_wa_hmac_key', '' );
			if ( strlen( $secret ) < 32 ) {
				return $this->deny();
			}

			$signature = $request->get_header( 'x-webhook-hmac' );
			if ( ! is_string( $signature ) ) {
				return $this->deny();
			}
			$signature = strtolower( trim( $signature ) );
			// Shape-check first: also rejects a duplicated header, which WP joins with ", ".
			if ( 1 !== preg_match( '/^[0-9a-f]{128}$/', $signature ) ) {
				return $this->deny();
			}

			// 4. algorithm allowlist. Conformance check only — the client never selects
			// the algorithm; sha512 is a hardcoded literal at step 5.
			$algorithm = $request->get_header( 'x-webhook-hmac-algorithm' );
			if ( ! is_string( $algorithm ) || 'sha512' !== strtolower( trim( $algorithm ) ) ) {
				return $this->deny();
			}

			// 5. the authentication decision. hash_equals only — never == or ===.
			if ( ! hash_equals( hash_hmac( 'sha512', $raw, $secret ), $signature ) ) {
				return $this->deny();
			}

			// 6. timestamp window. time() is UTC — current_time() would shift the
			// window by the site's timezone offset. abs() so a future clock fails too.
			$timestamp = $request->get_header( 'x-webhook-timestamp' );
			if ( ! is_string( $timestamp ) || ! ctype_digit( trim( $timestamp ) ) ) {
				return $this->deny();
			}
			$now_ms = (int) round( microtime( true ) * 1000 );
			if ( abs( $now_ms - (int) trim( $timestamp ) ) > self::TS_WINDOW_MS ) {
				return $this->deny();
			}

			// 7. dedupe LOOKUP only — deliberately does not return, so steps 8 and 9
			// still run in SSOT order: a replayed foreign-session body still 401s and a
			// replayed oversized body still 413s. handle() emits the 200-empty.
			$id     = $this->request_id( $request, $raw, trim( $timestamp ) );
			$replay = ( false !== get_transient( self::TRANSIENT_PREFIX . $id ) );

			// 8. session must match the option. JSON parse is legal only now, after the
			// HMAC passed. WAHA puts session at the envelope top level.
			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) ) {
				return $this->deny();
			}
			if ( (string) ( $data['session'] ?? '' ) !== (string) Apollo_Waha_Options::get( 'apollo_wa_session', 'apollo' ) ) {
				return $this->deny();
			}

			// 9. body size ceiling.
			if ( strlen( $raw ) > self::MAX_BODY ) {
				return new WP_Error(
					'apollo_wa_webhook_too_large',
					__( 'Payload too large.', 'apollo-waha' ),
					array( 'status' => 413 )
				);
			}

			$this->state[ spl_object_id( $request ) ] = array(
				'data'   => $data,
				'id'     => $id,
				'replay' => $replay,
			);

			return true;
		} catch ( \Throwable $e ) {
			// A TypeError on a malformed header must become a clean 401, never a 500
			// with a stack trace. Never log $raw, the signature, or the key.
			return $this->deny();
		}
	}

	/**
	 * Pipeline step 10. Never reads or writes queue status.
	 */
	public function handle( WP_REST_Request $request ) {
		$key = spl_object_id( $request );

		// No state row means verify() did not pass this request. Fail closed.
		if ( ! isset( $this->state[ $key ] ) ) {
			return new WP_REST_Response( null, 401 );
		}

		$state = $this->state[ $key ];
		unset( $this->state[ $key ] );

		if ( $state['replay'] ) {
			return new WP_REST_Response( null, 200 );
		}

		$transient = self::TRANSIENT_PREFIX . $state['id'];

		// Mark BEFORE dispatch to close the TOCTOU double-process window, and delete on throw so a WAHA retry after a 500 is still processed instead of being swallowed as a replay (registry run_log.open_decision — threat-agent synthesis).
		set_transient( $transient, 1, self::DEDUPE_TTL );

		try {
			$this->dispatch( $state['data'] );
		} catch ( \Throwable $e ) {
			delete_transient( $transient );
			return new WP_REST_Response( array( 'ok' => false ), 500 );
		}

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Event switch. No eval, no raw SQL, no queue mutation.
	 */
	private function dispatch( array $data ): void {
		$event = (string) ( $data['event'] ?? '' );

		switch ( $event ) {
			case 'session.status':
				// Contract goes live end-to-end: Apollo_Waha_Session::hooks() already
				// binds on_status() to this action. The strip does NOT move yet —
				// on_status() is still an empty stub. Wiring it is a class-session.php
				// edit, which is out of scope for this run.
				do_action( 'apollo/whatsapp/session_status', $data );
				break;

			case 'message':
			case 'message.any':
				$this->store_debug_payload( $data );
				do_action( 'apollo/whatsapp/message_in', $data );
				break;
		}

		// Queue status is never touched here. The webhook never approves a join.
	}

	/**
	 * Last inbound message payload, for debugging only.
	 *
	 * Message payloads carry personal data (phone numbers, message text), so this
	 * is gated behind WP_DEBUG, truncated, and written with autoload false. It
	 * bypasses Apollo_Waha_Options deliberately: that class forces autoload false
	 * only for the two declared secrets, and this run may not edit it.
	 */
	private function store_debug_payload( array $data ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		$encoded = (string) wp_json_encode( $data );
		update_option( 'apollo_wa_last_payload', substr( $encoded, 0, 8192 ), false );
	}

	/**
	 * Idempotency key. The client-supplied Request-Id is shape-validated and then
	 * ALWAYS hashed: a request header must never get to choose an option name, and
	 * the transient key must stay well inside the option_name budget.
	 *
	 * With no usable header, fall back to the body digest plus the timestamp — two
	 * legitimately identical envelopes sent at different moments must stay
	 * distinguishable rather than being swallowed as replays.
	 */
	private function request_id( WP_REST_Request $request, string $raw, string $timestamp ): string {
		$header = $request->get_header( 'x-webhook-request-id' );
		$header = is_string( $header ) ? trim( $header ) : '';

		$source = ( 1 === preg_match( '/^[A-Za-z0-9._:-]{1,64}$/', $header ) )
			? $header
			: hash( 'sha256', $raw . '|' . $timestamp );

		return hash( 'sha256', $source );
	}

	/**
	 * Step 1. Uses site configuration, not request headers: X-Forwarded-Proto is
	 * caller-controlled and must never be the thing that opens the door.
	 */
	private function transport_ok(): bool {
		if ( is_ssl() ) {
			return true;
		}

		// docs/WEBHOOK-SECURITY.md: "HTTPS in production. LocalWP lab allowed."
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		if ( in_array( $environment, array( 'local', 'development' ), true ) ) {
			return true;
		}

		// Behind a TLS-terminating proxy is_ssl() is false on a genuinely https site.
		return 'https' === strtolower( (string) wp_parse_url( home_url(), PHP_URL_SCHEME ) );
	}

	/**
	 * WP_REST_Server already consumed php://input into the request body, so that is
	 * the authoritative copy; the stream is not reliably re-readable on every SAPI.
	 */
	private function raw_body( WP_REST_Request $request ): string {
		$raw = (string) $request->get_body();
		if ( '' === $raw ) {
			$raw = (string) file_get_contents( 'php://input' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return $raw;
	}

	/**
	 * One identical denial for every auth failure — a 401 must never hint which
	 * check failed, nor that a signature was close.
	 */
	private function deny(): WP_Error {
		return new WP_Error(
			'apollo_wa_webhook_denied',
			__( 'Unauthorized.', 'apollo-waha' ),
			array( 'status' => 401 )
		);
	}
}
