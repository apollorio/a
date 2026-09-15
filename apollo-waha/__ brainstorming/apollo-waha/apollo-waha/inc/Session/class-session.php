<?php
/**
 * Session strip. WORKING gate. Does not load/unload plugins.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Session {

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {
		add_action( 'apollo/whatsapp/session_status', array( $this, 'on_status' ), 10, 1 );
	}

	public function is_working(): bool {
		return false;
	}

	public function on_status( $payload ): void {}
}
