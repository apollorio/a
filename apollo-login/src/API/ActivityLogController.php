<?php

/**
 * Activity Log REST Controller
 *
 * Records apolloDJ.exe activity (action, exe_version, timestamp) to a
 * master table that is visible ONLY to admin-role users.
 *
 * Auth: X-Apollo-App-Token header (same opaque-token scheme as AppAuthController).
 * Endpoints (namespace: apollo/v1):
 *   POST /activity/log   — authenticated apollodj app token required
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_User;
use Apollo\Login\Security\RateLimiter;

if (! defined('ABSPATH')) {
	exit;
}

// User meta keys for live .exe status — powers admin master table columns.
if (! defined('APOLLO_META_EXE_LAST_SEEN')) {
	define('APOLLO_META_EXE_LAST_SEEN',     '_apollo_exe_last_seen');
}
if (! defined('APOLLO_META_EXE_CURRENT_ACTION')) {
	define('APOLLO_META_EXE_CURRENT_ACTION', '_apollo_exe_current_action');
}
if (! defined('APOLLO_META_EXE_VERSION')) {
	define('APOLLO_META_EXE_VERSION',        '_apollo_exe_version');
}

/**
 * Activity Log Controller — admin-only master table for apolloDJ.exe activity.
 */
class ActivityLogController extends WP_REST_Controller
{

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = APOLLO_LOGIN_REST_NAMESPACE;

	/**
	 * App identifier this controller accepts tokens for.
	 *
	 * @var string
	 */
	private const APP_ID = 'apollodj';

	/**
	 * Token TTL — mirrors AppAuthController::TOKEN_TTL (30 days).
	 *
	 * @var int
	 */
	private const TOKEN_TTL = 2592000;

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void
	{
		// POST /activity/log — requires valid X-Apollo-App-Token for app_id=apollodj.
		register_rest_route(
			$this->namespace,
			'/activity/log',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'log_activity'),
				'permission_callback' => array($this, 'require_app_token'),
				'args'                => array(
					'action'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'maxLength'         => 100,
					),
					'exe_version' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'maxLength'         => 20,
					),
					'timestamp'   => array(
						'required' => false,
						'type'     => 'integer',
					),
				),
			)
		);

		// GET /activity/log — admin-only master table view.
		register_rest_route(
			$this->namespace,
			'/activity/log',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_activity'),
				'permission_callback' => function () {
					return current_user_can('manage_options');
				},
				'args'                => array(
					'per_page' => array(
						'type'    => 'integer',
						'default' => 50,
						'minimum' => 1,
						'maximum' => 200,
					),
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
				),
			)
		);

		// Ensure table exists on rest_api_init (lazy creation, non-blocking).
		$this->maybe_create_table();
	}

	// ─────────────────────────────────────────────────────────────────
	// Permission callback
	// ─────────────────────────────────────────────────────────────────

	/**
	 * Validate X-Apollo-App-Token and set current user.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function require_app_token(WP_REST_Request $request): true|WP_Error
	{
		$raw_token = $request->get_header('X-Apollo-App-Token');

		if (empty($raw_token)) {
			return new WP_Error(
				'missing_token',
				__('Token de autenticação não fornecido.', 'apollo-login'),
				array('status' => 401)
			);
		}

		$raw_token = sanitize_text_field($raw_token);

		if (! preg_match('/^[a-zA-Z0-9]{64}$/', $raw_token)) {
			return new WP_Error(
				'invalid_token_format',
				__('Formato de token inválido.', 'apollo-login'),
				array('status' => 400)
			);
		}

		$user = $this->resolve_user_by_token($raw_token);

		if (! $user) {
			return new WP_Error(
				'invalid_token',
				__('Token inválido ou expirado.', 'apollo-login'),
				array('status' => 401)
			);
		}

		// Set current user so the callback can retrieve it via get_current_user_id().
		wp_set_current_user($user->ID);

		// Rate limit: max 60 hits / 60s per user (atomic counter — audit race fix).
		$rl_bucket = 'apollo_actlog_' . $user->ID;
		if ( RateLimiter::get_counter( $rl_bucket ) >= 60 ) {
			return new WP_Error(
				'rate_limited',
				__( 'Limite de requisições atingido.', 'apollo-login' ),
				array( 'status' => 429 )
			);
		}
		RateLimiter::increment_counter( $rl_bucket, 60 );

		return true;
	}

	// ─────────────────────────────────────────────────────────────────
	// Endpoint callback
	// ─────────────────────────────────────────────────────────────────

	/**
	 * POST /activity/log — insert one activity row.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function log_activity(WP_REST_Request $request): WP_REST_Response|WP_Error
	{
		global $wpdb;

		$user_id     = get_current_user_id();
		$action      = $request->get_param('action');
		$exe_version = sanitize_text_field((string) ($request->get_param('exe_version') ?? ''));
		$ts          = $request->get_param('timestamp');
		$logged_at   = $ts
			? gmdate('Y-m-d H:i:s', (int) $ts)
			: current_time('mysql', true);

		$table = $wpdb->prefix . \APOLLO_LOGIN_TABLE_APP_ACTIVITY;
		$ip    = $this->get_client_ip();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'     => $user_id,
				'action'      => $action,
				'exe_version' => $exe_version,
				'ip_address'  => $ip,
				'logged_at'   => $logged_at,
				'created_at'  => current_time('mysql', true),
			),
			array('%d', '%s', '%s', '%s', '%s', '%s')
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

		if (false === $inserted) {
			return new WP_Error(
				'db_error',
				__('Erro ao salvar atividade.', 'apollo-login'),
				array('status' => 500)
			);
		}

		// Update live .exe status meta — powers admin master table "last seen" + "current action".
		update_user_meta($user_id, APOLLO_META_EXE_LAST_SEEN,      current_time('mysql', true));
		update_user_meta($user_id, APOLLO_META_EXE_CURRENT_ACTION, $action);
		if ($exe_version) {
			update_user_meta($user_id, APOLLO_META_EXE_VERSION, $exe_version);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $wpdb->insert_id,
			),
			201
		);
	}

	// ─────────────────────────────────────────────────────────────────
	// Admin read endpoint
	// ─────────────────────────────────────────────────────────────────

	/**
	 * GET /activity/log — paginated activity rows for manage_options users.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_activity(WP_REST_Request $request): WP_REST_Response
	{
		global $wpdb;

		$per_page = (int) $request->get_param('per_page');
		$page     = (int) $request->get_param('page');
		$offset   = ($page - 1) * $per_page;
		$table    = $wpdb->prefix . \APOLLO_LOGIN_TABLE_APP_ACTIVITY;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"); // Table name is WP prefix + PHP constant — no user input.

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.id, a.user_id, u.display_name, a.action, a.exe_version,
				        a.ip_address, a.logged_at, a.created_at
				 FROM {$table} a
				 LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
				 ORDER BY a.logged_at DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Attach live meta (last_seen) to each row — single extra query per row avoided via batch.
		if ($rows) {
			$user_ids   = array_unique(array_column($rows, 'user_id'));
			$last_seen  = array();
			foreach ($user_ids as $uid) {
				$last_seen[$uid] = get_user_meta((int) $uid, APOLLO_META_EXE_LAST_SEEN, true);
			}
			foreach ($rows as &$row) {
				$row['last_seen'] = $last_seen[$row['user_id']] ?? '';
			}
			unset($row);
		}

		return new WP_REST_Response(
			array(
				'rows'  => $rows ?: array(),
				'total' => $total,
				'pages' => $per_page > 0 ? (int) ceil($total / $per_page) : 1,
			),
			200
		);
	}

	// ─────────────────────────────────────────────────────────────────
	// Table management
	// ─────────────────────────────────────────────────────────────────

	/**
	 * Create activity table if it does not exist (idempotent).
	 *
	 * Follows the same lazy-creation pattern as JWTAuth::maybe_create_table().
	 *
	 * @return void
	 */
	public function maybe_create_table(): void
	{
		global $wpdb;

		$table   = $wpdb->prefix . \APOLLO_LOGIN_TABLE_APP_ACTIVITY;
		$charset = $wpdb->get_charset_collate();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
			return;
		}

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(100) NOT NULL,
			exe_version VARCHAR(20) NOT NULL DEFAULT '',
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			logged_at DATETIME NOT NULL,
			created_at DATETIME NOT NULL,
			KEY user_id (user_id),
			KEY logged_at (logged_at),
			KEY action (action)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	// ─────────────────────────────────────────────────────────────────
	// Token resolution — mirrors AppAuthController (private there)
	// ─────────────────────────────────────────────────────────────────

	/**
	 * Resolve WP_User from a raw 64-char opaque app token for APP_ID.
	 *
	 * Uses transient cache first, falls back to meta query.
	 * Exact logic mirror of AppAuthController::resolve_user_by_token().
	 *
	 * @param string $raw_token Raw token from header.
	 * @return WP_User|null
	 */
	private function resolve_user_by_token(string $raw_token): ?WP_User
	{
		$hashed_token = wp_hash($raw_token);
		$meta_key     = '_apollo_app_token_' . self::APP_ID;

		// Fast path: transient cache.
		$cached_uid = get_transient('apollo_apptk_' . md5($hashed_token));
		if ($cached_uid) {
			$stored = get_user_meta((int) $cached_uid, $meta_key, true);
			if ($stored && hash_equals($stored, $hashed_token)) {
				$expiry = (int) get_user_meta((int) $cached_uid, $meta_key . '_expiry', true);
				if ($expiry > time()) {
					return get_user_by('id', (int) $cached_uid) ?: null;
				}
			}
			delete_transient('apollo_apptk_' . md5($hashed_token));
		}

		// Slow path: meta query.
		$users = get_users(
			array(
				'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $hashed_token, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
			)
		);

		if (empty($users)) {
			return null;
		}

		$user   = $users[0];
		$expiry = (int) get_user_meta($user->ID, $meta_key . '_expiry', true);

		if ($expiry <= time()) {
			delete_user_meta($user->ID, $meta_key);
			delete_user_meta($user->ID, $meta_key . '_expiry');
			return null;
		}

		// Populate cache for next time.
		set_transient('apollo_apptk_' . md5($hashed_token), $user->ID, DAY_IN_SECONDS);

		return $user;
	}

	// ─────────────────────────────────────────────────────────────────
	// Helpers
	// ─────────────────────────────────────────────────────────────────

	/**
	 * Get client IP — mirrors AppAuthController::get_client_ip().
	 *
	 * @return string
	 */
	private function get_client_ip(): string
	{
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ($headers as $header) {
			if (! empty($_SERVER[$header])) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$ip = explode(',', sanitize_text_field(wp_unslash($_SERVER[$header])));
				return trim($ip[0]);
			}
		}

		return '0.0.0.0';
	}
}
