<?php
/**
 * Operator pane. Composer → sendText. fromMe does not run flows.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Pane {

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {}

	public function send( string $jid, string $text, array $args = array() ): bool {
		return false;
	}
}
