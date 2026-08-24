<?php
/**
 * Removes all apollo-dj-sync data when the plugin is deleted.
 * Meta keys registered by DJMetaRegistry + global options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Global options.
delete_option( 'apollo_dj_maintenance_mode' );
delete_option( 'apollo_dj_min_version' );
delete_option( 'apollo_dj_global_message' );

// User meta — delete in bulk.
global $wpdb;

$meta_keys = [
    '_apollo_dj_allowed_tabs',
    '_apollo_dj_quantize_level',
    '_apollo_dj_ultra_mode',
    '_apollo_dj_daily_limit',
    '_apollo_dj_premium_until',
    '_apollo_dj_can_export',
    '_apollo_dj_can_harmonic',
    '_apollo_dj_min_accuracy',
];

foreach ( $meta_keys as $key ) {
    $wpdb->delete( $wpdb->usermeta, [ 'meta_key' => $key ], [ '%s' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

// Daily usage counters — wildcard delete.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
        $wpdb->esc_like( '_apollo_dj_usage_' ) . '%'
    )
);
