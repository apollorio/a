<?php
/**
 * Uninstall — Apollo Hub
 *
 * Executado apenas quando o usuário APAGA o plugin via admin WP.
 * Remove: posts do CPT hub, postmeta, opções do plugin.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// ── Remove todos os posts do CPT 'hub' e seus metadados ──────────────────────

$hub_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
		'hub'
	)
);

if ( $hub_ids ) {
	$ids = array_map( 'absint', $hub_ids );
	$ids = array_filter( $ids );

	if ( $ids ) {
		// Postmeta dos hubs — build IN list from absint IDs only (no raw user input).
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($placeholders)", ...$ids ) );
	}

	// Posts
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->posts} WHERE post_type = %s",
			'hub'
		)
	);
}

// ── Remove opções do plugin ───────────────────────────────────────────────────

delete_option( 'apollo_hub_version' );
delete_option( 'apollo_hub_flush_rewrite' );

// ── Remove transients de cache ────────────────────────────────────────────────

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options}
     WHERE option_name LIKE %s
        OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_apollo_hub_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_apollo_hub_' ) . '%'
	)
);

// ── Limpa rewrite rules ───────────────────────────────────────────────────────

flush_rewrite_rules();
