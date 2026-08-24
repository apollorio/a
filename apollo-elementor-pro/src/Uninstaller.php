<?php
/**
 * Uninstaller static helper referenced by uninstall.php.
 *
 * @package Apollo\Elementor
 */

declare(strict_types=1);

namespace Apollo\Elementor;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) && ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Uninstaller {
	public static function run(): void {
		if ( get_option( 'apollo_delete_data_on_uninstall' ) ) {
			delete_option( 'apollo_elementor_settings' );
		}
	}
}
