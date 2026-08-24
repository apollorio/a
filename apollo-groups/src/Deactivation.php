<?php
declare(strict_types=1);
namespace Apollo\Groups;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Deactivation {
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
