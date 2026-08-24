<?php

/**
 * Uninstall — Clean removal of all Apollo Sheets data
 *
 * Runs when the plugin is deleted (not just deactivated).
 *
 * @package Apollo\Sheets
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════
// 1. Delete all sheet posts (CPT: apollo_sheet)
// ═══════════════════════════════════════════════════════════════

$sheets = get_posts(
	array(
		'post_type'      => 'apollo_sheet',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $sheets as $post_id ) {
	wp_delete_post( $post_id, true );
}

// ═══════════════════════════════════════════════════════════════
// 2. Delete options
// ═══════════════════════════════════════════════════════════════

delete_option( 'apollo_sheets_tables' );
delete_option( 'apollo_sheets_settings' );
delete_option( 'apollo_sheets_version' );
delete_option( 'apollo_sheets_db_version' );

// ═══════════════════════════════════════════════════════════════
// 3. Delete transient caches
// ═══════════════════════════════════════════════════════════════

global $wpdb;
$wpdb->query(
	"DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_apollo_sheet_%'
        OR option_name LIKE '_transient_timeout_apollo_sheet_%'"
);

// ═══════════════════════════════════════════════════════════════
// 4. Remove custom capabilities
// ═══════════════════════════════════════════════════════════════

$caps = array(
	'apollo_sheets_list',
	'apollo_sheets_add',
	'apollo_sheets_edit',
	'apollo_sheets_copy',
	'apollo_sheets_delete',
	'apollo_sheets_import',
	'apollo_sheets_export',
	'apollo_sheets_options',
);

foreach ( array( 'administrator', 'editor' ) as $role_name ) {
	$role = get_role( $role_name );
	if ( $role ) {
		foreach ( $caps as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}

// ═══════════════════════════════════════════════════════════════
// 5. Delete orphaned post meta
// ═══════════════════════════════════════════════════════════════

$wpdb->query(
	"DELETE FROM {$wpdb->postmeta}
     WHERE meta_key IN ('_apollo_sheet_options', '_apollo_sheet_visibility', '_apollo_sheet_id')
       AND post_id NOT IN (SELECT ID FROM {$wpdb->posts})"
);
