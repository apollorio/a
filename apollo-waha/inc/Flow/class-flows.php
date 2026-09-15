<?php
/**
 * Keyword router. Group answers only on anchors, reply in DM.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Flows {

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {
		add_action( 'apollo/whatsapp/message_in', array( $this, 'route' ), 10, 1 );
	}

	public function route( $payload ): void {}
}
