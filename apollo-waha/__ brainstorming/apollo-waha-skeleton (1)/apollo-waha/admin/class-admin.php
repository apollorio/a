<?php
/**
 * Screens 1–5 under apollo-admin. Views only render.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Admin {

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'menu' ), 25 );
	}

	public function menu(): void {}

	public function render_settings(): void {
		include APOLLO_WAHA_DIR . 'admin/views/settings.php';
	}

	public function render_queue(): void {
		include APOLLO_WAHA_DIR . 'admin/views/queue.php';
	}

	public function render_pane(): void {
		include APOLLO_WAHA_DIR . 'admin/views/pane.php';
	}

	public function render_flows(): void {
		include APOLLO_WAHA_DIR . 'admin/views/flows.php';
	}
}
