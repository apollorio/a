<?php

/**
 * Apollo Dashboard — Uninstall
 *
 * Fired when the plugin is deleted via the WordPress admin.
 * Removes all plugin options and user meta.
 *
 * @package Apollo\Dashboard
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// ── Remove plugin options ──────────────────────────────────────────────────
$options = array(
    'apollo_dashboard_version',
    'apollo_dashboard_settings',
    'apollo_dashboard_pages',
);

foreach ($options as $option) {
    delete_option($option);
}

// ── Remove user meta (dashboard preferences) ──────────────────────────────
global $wpdb;

$user_meta_keys = array(
    '_apollo_dashboard_preferences',
    '_apollo_dashboard_widgets',
    '_apollo_dashboard_layout',
);

foreach ($user_meta_keys as $key) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->delete($wpdb->usermeta, array('meta_key' => $key), array('%s'));
}
