<?php
/**
 * apollo_wa_* option access. Secrets never autoload, never echoed in full.
 *
 * Not in inc/class-plugin.php's loader — required directly by the first
 * stub that needs it (inc/Client/class-client.php) so this run does not
 * touch the orchestrator.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Options {

	/** Keys that hold secrets — always stored with autoload false. */
	private const SECRET_KEYS = array(
		'apollo_wa_api_key',
		'apollo_wa_hmac_key',
	);

	/** Defaults mirror waha-registry.json → options.keys. */
	private const DEFAULTS = array(
		'apollo_wa_base_url'         => 'http://127.0.0.1:3000',
		'apollo_wa_api_key'          => '',
		'apollo_wa_hmac_key'         => '',
		'apollo_wa_session'          => 'apollo',
		'apollo_wa_group_id'         => '',
		'apollo_wa_engine'           => 'GOWS',
		'apollo_wa_mirror_group'     => '1',
		'apollo_wa_bot_dm'           => '1',
		'apollo_wa_group_anchor'     => '1',
		'apollo_wa_send_gap_seconds' => '10',
		'apollo_wa_sends_paused'     => '0',
		'apollo_wa_last_ping'        => '',
	);

	public static function get( string $key, $fallback = '' ) {
		$default = array_key_exists( $key, self::DEFAULTS ) ? self::DEFAULTS[ $key ] : $fallback;
		return get_option( $key, $default );
	}

	public static function set( string $key, $value ): bool {
		return update_option( $key, $value, self::is_secret( $key ) ? false : true );
	}

	public static function is_secret( string $key ): bool {
		return in_array( $key, self::SECRET_KEYS, true );
	}

	/**
	 * First 2 + last 2 chars only, for display context. Never the full secret.
	 */
	public static function mask( string $value ): string {
		$len = strlen( $value );
		if ( 0 === $len ) {
			return '';
		}
		if ( $len <= 6 ) {
			return str_repeat( '•', $len );
		}
		return substr( $value, 0, 2 ) . str_repeat( '•', $len - 4 ) . substr( $value, -2 );
	}
}
