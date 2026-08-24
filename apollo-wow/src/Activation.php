<?php
declare(strict_types=1);
namespace Apollo\Wow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activation {
	public static function activate(): void {
		update_option( 'apollo_wow_version', APOLLO_WOW_VERSION );
		// wow_reactions table is already created by apollo-core DatabaseBuilder
	}
}
