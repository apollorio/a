<?php
/**
 * Front: join button + my-requests. No secrets in DOM.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Public {

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {
		add_shortcode( 'apollo_wa_join', array( $this, 'shortcode_join' ) );
	}

	public function shortcode_join(): string {
		ob_start();
		include APOLLO_WAHA_DIR . 'public/views/join-button.php';
		return (string) ob_get_clean();
	}
}
