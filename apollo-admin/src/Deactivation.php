<?php
/**
 * Plugin Deactivation Handler
 *
 * @package Apollo\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivation {

	/**
	 * Run deactivation tasks
	 */
	public static function deactivate(): void {
		delete_transient( 'apollo_admin_registry_cache' );
		// Settings are preserved — only uninstall.php removes data
	}
}
