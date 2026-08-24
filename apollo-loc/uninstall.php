<?php

/**
 * Apollo Loc — Uninstall
 *
 * Fired when the plugin is deleted via the WordPress admin.
 * Removes all plugin options and cleans up rewrite rules.
 *
 * @package Apollo\Local
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// ── Remove plugin options ──────────────────────────────────────────────────
$options = array(
    'apollo_local_version',
    'apollo_local_settings',
    'apollo_local_db_version',
    'apollo_local_flush',
);

foreach ($options as $option) {
    delete_option($option);
}

// ── Remove post meta ───────────────────────────────────────────────────────
global $wpdb;

$post_meta_keys = array(
    '_loc_lat',
    '_loc_lng',
    '_loc_address',
    '_loc_city',
    '_loc_state',
    '_loc_country',
    '_loc_zip',
    '_loc_phone',
    '_loc_website',
    '_loc_instagram',
    '_loc_capacity',
    '_loc_type',
    '_loc_verified',
    '_loc_geocoded',
);

foreach ($post_meta_keys as $key) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->delete($wpdb->postmeta, array('meta_key' => $key), array('%s'));
}

// ── Flush rewrite rules ────────────────────────────────────────────────────
flush_rewrite_rules();
