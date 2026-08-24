<?php
/**
 * Apollo Adverts — Uninstall
 *
 * Runs on plugin deletion.
 * Removes: options, post meta, taxonomy terms, pages, transients, cron hooks.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/* ── Options ───────────────────────────────────────────── */

delete_option( 'apollo_adverts_settings' );
delete_option( 'apollo_adverts_version' );
delete_option( 'apollo_adverts_archive_page_id' );
delete_option( 'apollo_adverts_submit_page_id' );

/* ── Meta ──────────────────────────────────────────────── */

global $wpdb;

$meta_keys = array(
	'_classified_price',
	'_classified_currency',
	'_classified_negotiable',
	'_classified_condition',
	'_classified_loc',
	'_classified_contact_phone',
	'_classified_contact_whatsapp',
	'_classified_expires_at',
	'_classified_featured',
	'_classified_views',
	'_classified_expiring_notified',
);

foreach ( $meta_keys as $key ) {
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $key ) );
}

/* ── Taxonomy terms ────────────────────────────────────── */

$taxonomies = array( 'classified_domain', 'classified_intent' );

foreach ( $taxonomies as $tax ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $tax,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, $tax );
		}
	}
}

/* ── Pages created by plugin ───────────────────────────── */

$page_ids = array(
	get_option( 'apollo_adverts_archive_page_id' ),
	get_option( 'apollo_adverts_submit_page_id' ),
);
foreach ( array_filter( $page_ids ) as $pid ) {
	wp_delete_post( $pid, true );
}

/* ── Transients ────────────────────────────────────────── */

$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_apollo_adverts_%' OR option_name LIKE '_transient_timeout_apollo_adverts_%'"
);

/* ── Cron hooks ────────────────────────────────────────── */

$crons = array(
	'apollo_classifieds_check_expired',
	'apollo_classifieds_notify_expiring',
	'apollo_classifieds_gc_temp',
);
foreach ( $crons as $hook ) {
	wp_clear_scheduled_hook( $hook );
}

/* ── Flush rewrite ─────────────────────────────────────── */

flush_rewrite_rules();
