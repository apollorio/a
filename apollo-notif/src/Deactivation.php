<?php
declare(strict_types=1);
namespace Apollo\Notif;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivation {
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'apollo_notif_cleanup' );
		wp_clear_scheduled_hook( 'apollo_notif_digest_dispatch' );
		delete_transient( 'apollo_notif_cache' );
		flush_rewrite_rules();
	}
}
