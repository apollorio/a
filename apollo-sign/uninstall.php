<?php

/**
 * Apollo Sign — Uninstall
 *
 * Removes all tables, signed files, certificates, and options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// ── Drop tables ──
$tables = array(
	$wpdb->prefix . 'apollo_signature_audit',
	$wpdb->prefix . 'apollo_signatures',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// ── Remove storage directory ──
$upload_dir = wp_upload_dir();
$sign_dir   = $upload_dir['basedir'] . '/apollo-sign';

if ( is_dir( $sign_dir ) ) {
	$it    = new RecursiveDirectoryIterator( $sign_dir, RecursiveDirectoryIterator::SKIP_DOTS );
	$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );

	foreach ( $files as $file ) {
		if ( $file->isDir() ) {
			rmdir( $file->getRealPath() );
		} else {
			unlink( $file->getRealPath() );
		}
	}

	rmdir( $sign_dir );
}

// ── Delete options ──
delete_option( 'apollo_sign_db_version' );
delete_option( 'apollo_sign_version' );

// ── Flush rewrite rules (remove /assinar/{hash}) ──
flush_rewrite_rules();
