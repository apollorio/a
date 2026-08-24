<?php
/**
 * Uninstall Apollo Admin
 *
 * Removes all plugin data when the plugin is deleted via WP admin.
 *
 * @package Apollo\Admin
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete the single serialized option
delete_option( 'apollo_admin_settings' );

// Delete transients
delete_transient( 'apollo_admin_registry_cache' );

// Clear any cached data
wp_cache_flush();
