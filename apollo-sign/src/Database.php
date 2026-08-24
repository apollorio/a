<?php
namespace Apollo\Sign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database installer — tables: apollo_signatures, apollo_signature_audit.
 */
final class Database {

	public static function install(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/* ── apollo_signatures ── */
		$t1   = $wpdb->prefix . 'apollo_signatures';
		$sql1 = "CREATE TABLE {$t1} (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            doc_id          BIGINT UNSIGNED NOT NULL DEFAULT 0,
            signer_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
            signer_name     VARCHAR(255)    NOT NULL DEFAULT '',
            signer_cpf      VARCHAR(14)     NOT NULL DEFAULT '',
            signer_email    VARCHAR(255)    NOT NULL DEFAULT '',
            certificate_cn  VARCHAR(255)    NOT NULL DEFAULT '',
            certificate_issuer VARCHAR(255) NOT NULL DEFAULT '',
            certificate_serial VARCHAR(128) NOT NULL DEFAULT '',
            certificate_valid_from DATETIME NULL,
            certificate_valid_to   DATETIME NULL,
            hash            VARCHAR(64)     NOT NULL DEFAULT '',
            content_hash    VARCHAR(64)     NOT NULL DEFAULT '',
            signature_data  LONGTEXT        NOT NULL,
            algorithm       VARCHAR(32)     NOT NULL DEFAULT 'sha256WithRSAEncryption',
            status          VARCHAR(20)     NOT NULL DEFAULT 'pending',
            ip_address      VARCHAR(45)     NOT NULL DEFAULT '',
            user_agent      VARCHAR(512)    NOT NULL DEFAULT '',
            sig_x           DECIMAL(5,4)    NOT NULL DEFAULT 0.6500,
            sig_y           DECIMAL(5,4)    NOT NULL DEFAULT 0.8500,
            sig_w           DECIMAL(5,4)    NOT NULL DEFAULT 0.2800,
            sig_h           DECIMAL(5,4)    NOT NULL DEFAULT 0.0600,
            sig_page        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            placement_mode  VARCHAR(20)     NOT NULL DEFAULT 'auto_footer',
            signature_image_path VARCHAR(512) NOT NULL DEFAULT '',
            stamp_path      VARCHAR(512)    NOT NULL DEFAULT '',
            signed_at       DATETIME        NULL,
            created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_hash (hash),
            KEY idx_doc (doc_id),
            KEY idx_signer (signer_id),
            KEY idx_status (status),
            KEY idx_cpf (signer_cpf)
        ) {$charset};";

		/* ── apollo_signature_audit ── */
		$t2   = $wpdb->prefix . 'apollo_signature_audit';
		$sql2 = "CREATE TABLE {$t2} (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            signature_id  BIGINT UNSIGNED NOT NULL,
            action        VARCHAR(50)     NOT NULL DEFAULT '',
            actor_id      BIGINT UNSIGNED NOT NULL DEFAULT 0,
            actor_name    VARCHAR(255)    NOT NULL DEFAULT '',
            actor_ip      VARCHAR(45)     NOT NULL DEFAULT '',
            details       TEXT            NOT NULL,
            created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_sig (signature_id),
            KEY idx_action (action),
            KEY idx_actor (actor_id)
        ) {$charset};";

		dbDelta( $sql1 );
		dbDelta( $sql2 );
	}

	/**
	 * Run upgrade migrations for existing installs.
	 * Called on `plugins_loaded` when DB_VERSION bumped.
	 */
	public static function upgrade( int $installed_version ): void {
		global $wpdb;

		/* v3 → v4: add content_hash column */
		if ( $installed_version < 4 ) {
			$table = $wpdb->prefix . 'apollo_signatures';
			$col   = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'content_hash'" );
			if ( empty( $col ) ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN content_hash VARCHAR(64) NOT NULL DEFAULT '' AFTER hash" );
			}
		}
	}

	public static function uninstall(): void {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}apollo_signatures" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}apollo_signature_audit" );
	}
}
