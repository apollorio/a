<?php

/**
 * Apollo SEO — Uninstall
 *
 * Fired when the plugin is deleted via the WordPress admin.
 * Removes all plugin options, post meta, and term meta.
 *
 * @package Apollo\SEO
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// ── Remove plugin options ──────────────────────────────────────────────────
delete_option('apollo_seo_settings');
delete_option('apollo_seo_version');
delete_option('apollo_seo_sitemap_version');

// ── Remove post meta ───────────────────────────────────────────────────────
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_apollo_seo'), array('%s'));

// ── Remove term meta ───────────────────────────────────────────────────────
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->delete($wpdb->termmeta, array('meta_key' => '_apollo_seo_term'), array('%s'));
