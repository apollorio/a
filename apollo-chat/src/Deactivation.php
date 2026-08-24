<?php
declare(strict_types=1);
namespace Apollo\Chat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivation {
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'apollo_chat_cleanup' );
		flush_rewrite_rules();
	}
}
