<?php
/**
 * Apollo Core - Database Builder
 *
 * Handles database table creation and destruction.
 * Pattern: CHECK IF EXISTS → BUILD IF NOT
 * Uninstall: Option to keep or delete data (default: KEEP)
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database Builder
 */
final class DatabaseBuilder {

	/**
	 * WordPress database instance
	 *
	 * @var \wpdb
	 */
	private \wpdb $db;

	/**
	 * Table prefix for Apollo tables
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Schema definitions
	 *
	 * @var array
	 */
	private array $schemas = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->db     = $wpdb;
		$this->prefix = $wpdb->prefix . 'apollo_';
		$this->load_schemas();
	}

	/**
	 * Load all table schemas
	 */
	private function load_schemas(): void {
		$charset = $this->db->get_charset_collate();

		$this->schemas = array(
			// ═══════════════════════════════════════════════════════════════
			// CORE TABLES (apollo-core)
			// ═══════════════════════════════════════════════════════════════
			'audit_log'           => array(
				'plugin' => 'apollo-core',
				'table'  => $this->prefix . 'audit_log',
				'sql'    => "CREATE TABLE {$this->prefix}audit_log (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    action VARCHAR(100) NOT NULL,
                    object_type VARCHAR(50),
                    object_id BIGINT UNSIGNED,
                    user_id BIGINT UNSIGNED,
                    user_ip VARCHAR(45),
                    user_agent TEXT,
                    details LONGTEXT,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY action (action),
                    KEY object_type (object_type),
                    KEY user_id (user_id),
                    KEY created_at (created_at)
                ) {$charset}",
			),

			'registry_state'      => array(
				'plugin' => 'apollo-core',
				'table'  => $this->prefix . 'registry_state',
				'sql'    => "CREATE TABLE {$this->prefix}registry_state (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    component_type ENUM('cpt', 'taxonomy', 'meta', 'role') NOT NULL,
                    component_slug VARCHAR(100) NOT NULL,
                    owner_plugin VARCHAR(100) NOT NULL,
                    registered_by VARCHAR(100) NOT NULL,
                    is_fallback TINYINT(1) DEFAULT 0,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY component (component_type, component_slug),
                    KEY owner_plugin (owner_plugin)
                ) {$charset}",
			),

			'settings'            => array(
				'plugin' => 'apollo-core',
				'table'  => $this->prefix . 'settings',
				'sql'    => "CREATE TABLE {$this->prefix}settings (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    setting_group VARCHAR(100) NOT NULL,
                    setting_key VARCHAR(100) NOT NULL,
                    setting_value LONGTEXT,
                    autoload ENUM('yes', 'no') DEFAULT 'yes',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY setting_group_key (setting_group, setting_key),
                    KEY autoload (autoload)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// LOGIN TABLES (apollo-login)
			// ═══════════════════════════════════════════════════════════════
			'login_attempts'      => array(
				'plugin' => 'apollo-login',
				'table'  => $this->prefix . 'login_attempts',
				'sql'    => "CREATE TABLE {$this->prefix}login_attempts (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_login VARCHAR(100) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    attempt_type ENUM('login', 'register', 'reset') DEFAULT 'login',
                    success TINYINT(1) DEFAULT 0,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY user_login (user_login),
                    KEY ip_address (ip_address),
                    KEY created_at (created_at)
                ) {$charset}",
			),

			'lockouts'            => array(
				'plugin' => 'apollo-login',
				'table'  => $this->prefix . 'lockouts',
				'sql'    => "CREATE TABLE {$this->prefix}lockouts (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    lockout_type ENUM('ip', 'user', 'email') NOT NULL,
                    lockout_key VARCHAR(255) NOT NULL,
                    attempts_count INT UNSIGNED DEFAULT 0,
                    locked_until DATETIME,
                    reason VARCHAR(255),
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY lockout_type_key (lockout_type, lockout_key(191)),
                    KEY locked_until (locked_until)
                ) {$charset}",
			),

			'quiz_sessions'       => array(
				'plugin' => 'apollo-login',
				'table'  => $this->prefix . 'quiz_sessions',
				'sql'    => "CREATE TABLE {$this->prefix}quiz_sessions (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    session_token VARCHAR(64) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    stage1_completed TINYINT(1) DEFAULT 0,
                    stage1_score INT DEFAULT 0,
                    stage2_completed TINYINT(1) DEFAULT 0,
                    stage2_score INT DEFAULT 0,
                    stage3_completed TINYINT(1) DEFAULT 0,
                    stage3_answers TEXT,
                    stage4_completed TINYINT(1) DEFAULT 0,
                    stage4_data TEXT,
                    registration_data TEXT,
                    completed_at DATETIME,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY session_token (session_token),
                    KEY ip_address (ip_address),
                    KEY expires_at (expires_at)
                ) {$charset}",
			),

			'email_verifications' => array(
				'plugin' => 'apollo-login',
				'table'  => $this->prefix . 'email_verifications',
				'sql'    => "CREATE TABLE {$this->prefix}email_verifications (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED,
                    email VARCHAR(255) NOT NULL,
                    token VARCHAR(64) NOT NULL,
                    type ENUM('verify', 'reset', 'change') DEFAULT 'verify',
                    used TINYINT(1) DEFAULT 0,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY token (token),
                    KEY user_id (user_id),
                    KEY email (email(191)),
                    KEY expires_at (expires_at)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// USER TABLES (apollo-users)
			// ═══════════════════════════════════════════════════════════════
			'matchmaking'         => array(
				'plugin' => 'apollo-users',
				'table'  => $this->prefix . 'matchmaking',
				'sql'    => "CREATE TABLE {$this->prefix}matchmaking (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED NOT NULL,
                    sound_preferences TEXT COMMENT 'JSON array of sound term IDs',
                    location VARCHAR(255),
                    preferences TEXT COMMENT 'JSON object of preferences',
                    last_active DATETIME,
                    match_score_cache TEXT COMMENT 'JSON cache of match scores',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY user_id (user_id),
                    KEY last_active (last_active)
                ) {$charset}",
			),

			'user_fields'         => array(
				'plugin' => 'apollo-users',
				'table'  => $this->prefix . 'user_fields',
				'sql'    => "CREATE TABLE {$this->prefix}user_fields (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED NOT NULL,
                    field_key VARCHAR(100) NOT NULL,
                    field_value LONGTEXT,
                    is_public TINYINT(1) DEFAULT 1,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY user_field (user_id, field_key),
                    KEY field_key (field_key)
                ) {$charset}",
			),

			'profile_views'       => array(
				'plugin' => 'apollo-users',
				'table'  => $this->prefix . 'profile_views',
				'sql'    => "CREATE TABLE {$this->prefix}profile_views (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    profile_user_id BIGINT UNSIGNED NOT NULL,
                    viewer_user_id BIGINT UNSIGNED,
                    viewer_ip VARCHAR(45),
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY profile_user_id (profile_user_id),
                    KEY viewer_user_id (viewer_user_id),
                    KEY created_at (created_at)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// SOCIAL TABLES (apollo-social)
			// ═══════════════════════════════════════════════════════════════
			'follows'             => array(
				'plugin' => 'apollo-social',
				'table'  => $this->prefix . 'follows',
				'sql'    => "CREATE TABLE {$this->prefix}follows (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    follower_id BIGINT UNSIGNED NOT NULL,
                    following_id BIGINT UNSIGNED NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY follow_pair (follower_id, following_id),
                    KEY follower_id (follower_id),
                    KEY following_id (following_id)
                ) {$charset}",
			),

			'blocks'              => array(
				'plugin' => 'apollo-social',
				'table'  => $this->prefix . 'blocks',
				'sql'    => "CREATE TABLE {$this->prefix}blocks (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    blocker_id BIGINT UNSIGNED NOT NULL,
                    blocked_id BIGINT UNSIGNED NOT NULL,
                    reason VARCHAR(255),
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY block_pair (blocker_id, blocked_id),
                    KEY blocker_id (blocker_id),
                    KEY blocked_id (blocked_id)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// FAVORITES TABLES (apollo-fav)
			// ═══════════════════════════════════════════════════════════════
			'favorites'           => array(
				'plugin' => 'apollo-fav',
				'table'  => $this->prefix . 'favorites',
				'sql'    => "CREATE TABLE {$this->prefix}favorites (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED NOT NULL,
                    object_type VARCHAR(50) NOT NULL,
                    object_id BIGINT UNSIGNED NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY user_object (user_id, object_type, object_id),
                    KEY object_type (object_type),
                    KEY object_id (object_id)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// WOW REACTIONS TABLES (apollo-wow)
			// ═══════════════════════════════════════════════════════════════
			'wow_reactions'       => array(
				'plugin' => 'apollo-wow',
				'table'  => $this->prefix . 'wow_reactions',
				'sql'    => "CREATE TABLE {$this->prefix}wow_reactions (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED NOT NULL,
                    object_type VARCHAR(50) NOT NULL,
                    object_id BIGINT UNSIGNED NOT NULL,
                    reaction_type VARCHAR(50) NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY user_object_reaction (user_id, object_type, object_id, reaction_type),
                    KEY object_type (object_type),
                    KEY object_id (object_id),
                    KEY reaction_type (reaction_type)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// NOTIFICATION TABLES (apollo-notif)
			// ═══════════════════════════════════════════════════════════════
			'notifications'       => array(
				'plugin' => 'apollo-notif',
				'table'  => $this->prefix . 'notifications',
				'sql'    => "CREATE TABLE {$this->prefix}notifications (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT UNSIGNED NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT,
                    data TEXT COMMENT 'JSON object with additional data',
                    link VARCHAR(500),
                    is_read TINYINT(1) DEFAULT 0,
                    read_at DATETIME,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY user_id (user_id),
                    KEY type (type),
                    KEY is_read (is_read),
                    KEY created_at (created_at)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// GROUPS TABLES (apollo-groups)
			// ═══════════════════════════════════════════════════════════════
			'groups'              => array(
				'plugin' => 'apollo-groups',
				'table'  => $this->prefix . 'groups',
				'sql'    => "CREATE TABLE {$this->prefix}groups (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) NOT NULL,
                    description TEXT,
                    cover_image BIGINT UNSIGNED,
                    creator_id BIGINT UNSIGNED NOT NULL,
                    type ENUM('comuna', 'nucleo') DEFAULT 'comuna',
                    privacy ENUM('public', 'private', 'secret') DEFAULT 'public',
                    member_count INT UNSIGNED DEFAULT 0,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY slug (slug(191)),
                    KEY creator_id (creator_id),
                    KEY privacy (privacy),
                    KEY type (type)
                ) {$charset}",
			),

			'group_members'       => array(
				'plugin' => 'apollo-groups',
				'table'  => $this->prefix . 'group_members',
				'sql'    => "CREATE TABLE {$this->prefix}group_members (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    group_id BIGINT UNSIGNED NOT NULL,
                    user_id BIGINT UNSIGNED NOT NULL,
                    role ENUM('member', 'moderator', 'admin') DEFAULT 'member',
                    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY group_user (group_id, user_id),
                    KEY group_id (group_id),
                    KEY user_id (user_id)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// MODERATION TABLES (apollo-mod)
			// ═══════════════════════════════════════════════════════════════
			'mod_reports'         => array(
				'plugin' => 'apollo-mod',
				'table'  => $this->prefix . 'mod_reports',
				'sql'    => "CREATE TABLE {$this->prefix}mod_reports (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    reporter_id BIGINT UNSIGNED NOT NULL,
                    object_type VARCHAR(50) NOT NULL,
                    object_id BIGINT UNSIGNED NOT NULL,
                    reason VARCHAR(100) NOT NULL,
                    details TEXT,
                    status ENUM('pending', 'reviewed', 'actioned', 'dismissed') DEFAULT 'pending',
                    reviewed_by BIGINT UNSIGNED,
                    reviewed_at DATETIME,
                    action_taken VARCHAR(255),
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY reporter_id (reporter_id),
                    KEY object_type (object_type),
                    KEY object_id (object_id),
                    KEY status (status),
                    KEY created_at (created_at)
                ) {$charset}",
			),

			'mod_actions'         => array(
				'plugin' => 'apollo-mod',
				'table'  => $this->prefix . 'mod_actions',
				'sql'    => "CREATE TABLE {$this->prefix}mod_actions (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    moderator_id BIGINT UNSIGNED NOT NULL,
                    action_type VARCHAR(50) NOT NULL,
                    target_type VARCHAR(50) NOT NULL,
                    target_id BIGINT UNSIGNED NOT NULL,
                    reason TEXT,
                    metadata TEXT COMMENT 'JSON object',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY moderator_id (moderator_id),
                    KEY action_type (action_type),
                    KEY target_type (target_type),
                    KEY target_id (target_id),
                    KEY created_at (created_at)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// EMAIL TABLES (apollo-email)
			// ═══════════════════════════════════════════════════════════════
			'email_queue'         => array(
				'plugin' => 'apollo-email',
				'table'  => $this->prefix . 'email_queue',
				'sql'    => "CREATE TABLE {$this->prefix}email_queue (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    to_email VARCHAR(255) NOT NULL,
                    to_name VARCHAR(255),
                    subject VARCHAR(500) NOT NULL,
                    body LONGTEXT NOT NULL,
                    template_id BIGINT UNSIGNED,
                    priority TINYINT DEFAULT 5,
                    status ENUM('pending', 'sending', 'sent', 'failed') DEFAULT 'pending',
                    attempts TINYINT DEFAULT 0,
                    sent_at DATETIME,
                    error_message TEXT,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY status (status),
                    KEY priority (priority),
                    KEY created_at (created_at)
                ) {$charset}",
			),

			'email_log'           => array(
				'plugin' => 'apollo-email',
				'table'  => $this->prefix . 'email_log',
				'sql'    => "CREATE TABLE {$this->prefix}email_log (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    to_email VARCHAR(255) NOT NULL,
                    subject VARCHAR(500) NOT NULL,
                    template_id BIGINT UNSIGNED,
                    status ENUM('sent', 'bounced', 'opened', 'clicked') DEFAULT 'sent',
                    opened_at DATETIME,
                    clicked_at DATETIME,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY to_email (to_email(191)),
                    KEY template_id (template_id),
                    KEY status (status),
                    KEY created_at (created_at)
                ) {$charset}",
			),

			// ═══════════════════════════════════════════════════════════════
			// STATISTICS TABLES (apollo-statistics)
			// ═══════════════════════════════════════════════════════════════
			'statistics'          => array(
				'plugin' => 'apollo-statistics',
				'table'  => $this->prefix . 'statistics',
				'sql'    => "CREATE TABLE {$this->prefix}statistics (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    stat_type VARCHAR(50) NOT NULL,
                    stat_key VARCHAR(100) NOT NULL,
                    stat_value BIGINT DEFAULT 0,
                    date_period DATE NOT NULL,
                    metadata TEXT COMMENT 'JSON object',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY stat_period (stat_type, stat_key, date_period),
                    KEY stat_type (stat_type),
                    KEY date_period (date_period)
                ) {$charset}",
			),
		);
	}

	/**
	 * Check if table exists
	 */
	public function table_exists( string $table_name ): bool {
		$full_name = strpos( $table_name, $this->db->prefix ) === 0
			? $table_name
			: $this->prefix . $table_name;

		$result = $this->db->get_var(
			$this->db->prepare( 'SHOW TABLES LIKE %s', $full_name )
		);

		return $result === $full_name;
	}

	/**
	 * Build all tables (CHECK IF EXISTS → CREATE IF NOT)
	 */
	public function build(): array {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$results = array(
			'created' => array(),
			'existed' => array(),
			'errors'  => array(),
		);

		foreach ( $this->schemas as $key => $schema ) {
			$table = $schema['table'];

			if ( $this->table_exists( $table ) ) {
				$results['existed'][] = $table;
				continue;
			}

			// Create table
			dbDelta( $schema['sql'] );

			// Verify creation
			if ( $this->table_exists( $table ) ) {
				$results['created'][] = $table;
			} else {
				$results['errors'][] = array(
					'table' => $table,
					'error' => $this->db->last_error,
				);
			}
		}

		return $results;
	}

	/**
	 * Build tables for specific plugin only
	 */
	public function build_for_plugin( string $plugin ): array {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$results = array(
			'created' => array(),
			'existed' => array(),
			'errors'  => array(),
		);

		foreach ( $this->schemas as $key => $schema ) {
			if ( $schema['plugin'] !== $plugin ) {
				continue;
			}

			$table = $schema['table'];

			if ( $this->table_exists( $table ) ) {
				$results['existed'][] = $table;
				continue;
			}

			dbDelta( $schema['sql'] );

			if ( $this->table_exists( $table ) ) {
				$results['created'][] = $table;
			} else {
				$results['errors'][] = array(
					'table' => $table,
					'error' => $this->db->last_error,
				);
			}
		}

		return $results;
	}

	/**
	 * Drop all Apollo tables (ONLY if admin chooses to delete data)
	 *
	 * WARNING: This is destructive and should only be called
	 * when admin explicitly chooses to delete all data.
	 */
	public function drop_all_tables(): array {
		$dropped = array();

		foreach ( $this->schemas as $key => $schema ) {
			$table = $schema['table'];

			if ( $this->table_exists( $table ) ) {
				$this->db->query( "DROP TABLE IF EXISTS {$table}" );
				$dropped[] = $table;
			}
		}

		return $dropped;
	}

	/**
	 * Drop tables for specific plugin only
	 */
	public function drop_plugin_tables( string $plugin ): array {
		$dropped = array();

		foreach ( $this->schemas as $key => $schema ) {
			if ( $schema['plugin'] !== $plugin ) {
				continue;
			}

			$table = $schema['table'];

			if ( $this->table_exists( $table ) ) {
				$this->db->query( "DROP TABLE IF EXISTS {$table}" );
				$dropped[] = $table;
			}
		}

		return $dropped;
	}

	/**
	 * Get all schema definitions
	 */
	public function get_schemas(): array {
		return $this->schemas;
	}

	/**
	 * Get tables for specific plugin
	 */
	public function get_plugin_tables( string $plugin ): array {
		return array_filter(
			$this->schemas,
			function ( $schema ) use ( $plugin ) {
				return $schema['plugin'] === $plugin;
			}
		);
	}

	/**
	 * Get table status (exists, row count)
	 */
	public function get_table_status(): array {
		$status = array();

		foreach ( $this->schemas as $key => $schema ) {
			$table  = $schema['table'];
			$exists = $this->table_exists( $table );

			$status[ $key ] = array(
				'table'  => $table,
				'plugin' => $schema['plugin'],
				'exists' => $exists,
				'rows'   => $exists ? (int) $this->db->get_var( "SELECT COUNT(*) FROM {$table}" ) : 0,
			);
		}

		return $status;
	}
}
