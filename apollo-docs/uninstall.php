<?php
/**
 * Apollo Docs — Uninstall
 * Removes all plugin data when deleted via WordPress admin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/* ── Remove custom tables ── */
$doc_tables = array(
	$wpdb->prefix . 'apollo_doc_versions',
	$wpdb->prefix . 'apollo_doc_downloads',
);
foreach ( $doc_tables as $doc_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $doc_table ) );
}

/* ── Remove all doc posts ── */
$docs = get_posts(
	array(
		'post_type'      => 'doc',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $docs as $doc_id ) {
	/* Remove attached files */
	$file_id = get_post_meta( $doc_id, '_doc_file_id', true );
	if ( $file_id ) {
		wp_delete_attachment( $file_id, true );
	}
	wp_delete_post( $doc_id, true );
}

/* ── Remove taxonomies terms ── */
$taxonomies = array( 'doc_folder', 'doc_type' );
foreach ( $taxonomies as $tax ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $tax,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $tid ) {
			wp_delete_term( $tid, $tax );
		}
	}
}

/* ── Remove storage directory ── */
$upload_dir = wp_upload_dir();
$docs_dir   = trailingslashit( $upload_dir['basedir'] ) . 'apollo-docs';

if ( is_dir( $docs_dir ) ) {
	$it    = new RecursiveDirectoryIterator( $docs_dir, RecursiveDirectoryIterator::SKIP_DOTS );
	$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );

	foreach ( $files as $file ) {
		if ( $file->isDir() ) {
			rmdir( $file->getPathname() );
		} else {
			unlink( $file->getPathname() );
		}
	}
	rmdir( $docs_dir );
}

/* ── Remove options ── */
delete_option( 'apollo_docs_db_version' );

/* ── Flush rewrite rules ── */
flush_rewrite_rules();
