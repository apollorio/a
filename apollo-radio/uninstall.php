<?php
/**
 * Apollo Radio — Uninstall handler.
 *
 * Cleans up all plugin options on uninstall.
 *
 * @package Apollo\Radio
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove all plugin options.
delete_option( 'apollo_radio_sc_client_id' );
delete_option( 'apollo_radio_sc_proxy_url' );
delete_option( 'apollo_radio_json_cdn' );
delete_option( 'apollo_radio_json_fallback' );

// Clear transient cache.
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        '_transient_apollo_radio_%',
        '_transient_timeout_apollo_radio_%'
    )
);
