<?php
/**
 * Orchestrator. Registers modules. No WAHA HTTP here.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {}

	public function boot(): void {
		$this->load();
		Apollo_Waha_Session::instance()->hooks();
		Apollo_Waha_Webhook::instance()->hooks();
		Apollo_Waha_Queue::instance()->hooks();
		Apollo_Waha_Pane::instance()->hooks();
		Apollo_Waha_Flows::instance()->hooks();
		Apollo_Waha_Public::instance()->hooks();
		if ( is_admin() ) {
			Apollo_Waha_Admin::instance()->hooks();
		}
	}

	private function load(): void {
		$files = array(
			// MUST stay first: every other class calls Apollo_Waha_Options at runtime.
			'inc/class-options.php',
			'inc/Client/class-client.php',
			'inc/Session/class-session.php',
			'inc/Webhook/class-webhook.php',
			'inc/Phone/class-phone.php',
			'inc/Queue/class-queue.php',
			'inc/Pane/class-pane.php',
			'inc/Flow/class-flows.php',
			'admin/class-admin.php',
			'public/class-public.php',
		);
		foreach ( $files as $rel ) {
			require_once APOLLO_WAHA_DIR . $rel;
		}
	}
}
