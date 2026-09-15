<?php
/**
 * BR 12/13 candidates. Landline must not gain a 9.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Phone {

	public static function candidates( string $raw ): array {
		return array();
	}

	public static function resolve_jid( string $raw ): array {
		return array(
			'jid' => '',
			'lid' => '',
		);
	}
}
