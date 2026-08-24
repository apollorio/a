<?php
namespace Apollo\Docs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database table installer for apollo-docs.
 *
 * Tables:
 *  - apollo_doc_versions   → version history per document
 *  - apollo_doc_downloads  → download audit log
 */
final class Database {

	public static function install(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/* ── Document Version History ─────────────────────── */
		$sql_versions = "CREATE TABLE {$prefix}apollo_doc_versions (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            doc_id      BIGINT UNSIGNED NOT NULL,
            version     VARCHAR(20)     NOT NULL DEFAULT '1.0',
            file_id     BIGINT UNSIGNED DEFAULT NULL,
            content     LONGTEXT        DEFAULT NULL,
            checksum    VARCHAR(64)     DEFAULT NULL,
            author_id   BIGINT UNSIGNED NOT NULL,
            changelog   TEXT            DEFAULT NULL,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_doc     (doc_id),
            KEY idx_author  (author_id),
            KEY idx_version (doc_id, version)
        ) {$charset};";

		/* ── Download Audit Log ───────────────────────────── */
		$sql_downloads = "CREATE TABLE {$prefix}apollo_doc_downloads (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            doc_id      BIGINT UNSIGNED NOT NULL,
            user_id     BIGINT UNSIGNED NOT NULL,
            ip          VARCHAR(45)     DEFAULT NULL,
            user_agent  VARCHAR(255)    DEFAULT NULL,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_doc  (doc_id),
            KEY idx_user (user_id)
        ) {$charset};";

		dbDelta( $sql_versions );
		dbDelta( $sql_downloads );

		update_option( 'apollo_docs_db_version', APOLLO_DOCS_DB_VERSION );
	}

	/**
	 * Full cleanup on uninstall.
	 */
	public static function uninstall(): void {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'apollo_doc_versions',
			$wpdb->prefix . 'apollo_doc_downloads',
		);

		foreach ( $tables as $table ) {
			// %i = identifier (WP 6.2+); table names are prefix+constant only.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
		}

		delete_option( 'apollo_docs_db_version' );
	}
}
