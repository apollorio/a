<?php
/**
 * Uninstall Apollo Journal
 *
 * Removes all plugin data when the plugin is DELETED from WordPress.
 * This is NOT called on deactivation — only on deletion.
 *
 * @package Apollo\Journal
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Delete plugin options.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'aj_%' OR option_name LIKE 'apollo_journal_%'" );

// Delete NREP post meta.
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_nrep_code', '_nrep_year', '_nrep_seq')" );

// Note: Custom taxonomy terms (music, culture, rio, formato) are NOT deleted
// because they may be in use by other content. Clean manually if needed.

wp_cache_flush();
