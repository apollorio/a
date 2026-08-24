<?php
/**
 * Apollo Core - Uninstall Script
 *
 * This file is executed when the plugin is DELETED (not deactivated).
 *
 * IMPORTANT: By default, ALL DATA IS PRESERVED.
 * Data is only deleted if admin explicitly enabled the option in settings.
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the plugin to access classes
require_once __DIR__ . '/apollo-core.php';

// Execute uninstall (checks admin preference internally)
\Apollo\Core\UninstallHandler::uninstall();
