<?php

/**
 * Uninstall Apollo Maps
 *
 * Removes all plugin data when the plugin is deleted.
 * This file is called by WordPress when the plugin is deleted.
 *
 * @package Apollo\Maps
 */

// If uninstall not called from WordPress, exit
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'apollo_maps_%' ) );

// Delete user meta
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", '_apollo_maps_%' ) );

// Delete post meta (if applicable)
// $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_apollo_maps_%'" );

// Drop custom tables (if applicable)
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}apollo_maps_example" );

// Clear any cached data
wp_cache_flush();
