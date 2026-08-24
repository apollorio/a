<?php
declare(strict_types=1);
namespace Apollo\Social;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivation {
	public static function deactivate(): void {
		delete_transient( 'apollo_social_cache' );
		flush_rewrite_rules();
	}
}
