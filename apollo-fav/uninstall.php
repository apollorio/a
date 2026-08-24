<?php

/**
 * Apollo Fav — Uninstall
 *
 * Fired when the plugin is deleted via the WordPress admin.
 * Drops the `apollo_favs` table and removes all plugin options/user-meta.
 *
 * @package Apollo\Fav
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// ── 1. Drop custom table ───────────────────────────────────────────────────
$table = $wpdb->prefix . 'apollo_favs';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );

// ── 2. Remove plugin options ───────────────────────────────────────────────
delete_option('apollo_fav_db_version');
delete_option('apollo_fav_settings');

// ── 3. Remove user meta (legacy + current keys) ───────────────────────────
$user_meta_keys = array(
    '_user_fav_events',
    '_apollo_fav_items',
    // Legacy keys
    '_user_interested_events',
    'apollo_favorites',
);

foreach ($user_meta_keys as $key) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->delete($wpdb->usermeta, array('meta_key' => $key), array('%s'));
}

// ── 4. Remove post meta (legacy + current keys) ────────────────────────────
$post_meta_keys = array(
    '_event_fav_users',
    '_apollo_fav_users',
    // Legacy keys
    '_event_interested_users',
    '_apollo_favorited_users',
);

foreach ($post_meta_keys as $key) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->delete($wpdb->postmeta, array('meta_key' => $key), array('%s'));
}
