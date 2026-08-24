<?php
/**
 * Uninstall Apollo Users
 *
 * @package Apollo\Users
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete options
delete_option( 'apollo_users_version' );
delete_option( 'apollo_users_settings' );

// Only delete data if option is set
$delete_data = get_option( 'apollo_users_delete_data_on_uninstall', false );

if ( $delete_data ) {
	// Drop custom tables
	$tables = array(
		$wpdb->prefix . 'apollo_user_fields',
		$wpdb->prefix . 'apollo_profile_views',
		$wpdb->prefix . 'apollo_matchmaking',
	);

	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore
	}

	// Delete user meta
	$meta_keys = array(
		'_apollo_social_name',
		'_apollo_bio',
		'_apollo_phone',
		'_apollo_website',
		'_apollo_privacy_profile',
		'_apollo_privacy_email',
		'custom_avatar',
		'avatar_thumb',
		'cover_image',
	);

	foreach ( $meta_keys as $key ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
				$key
			)
		);
	}
}

// Clear any cached data
wp_cache_flush();
