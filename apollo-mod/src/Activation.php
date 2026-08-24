<?php
declare(strict_types=1);
namespace Apollo\Mod;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activation {
	public static function activate(): void {
		update_option( 'apollo_mod_version', APOLLO_MOD_VERSION );
		// mod_reports + mod_actions tables already created by apollo-core DatabaseBuilder
	}
}
