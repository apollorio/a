<?php

/**
 * Rate Limiter
 *
 * IP-based rate limiting using WordPress transients.
 * After N failed login attempts from one IP within a window,
 * the IP is temporarily blacklisted via Firewall::temp_blacklist_ip().
 *
 * Thresholds (configurable via constants or wp_options):
 *   APOLLO_RATE_LIMIT_MAX_HITS   — max requests in window (default 20)
 *   APOLLO_RATE_LIMIT_WINDOW     — window in seconds (default 60)
 *   APOLLO_RATE_LIMIT_BLOCK_TIME — block duration in seconds (default 3600 = 1h)
 *
 * @package Apollo\Login\Security
 */

declare(strict_types=1);

namespace Apollo\Login\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate Limiter class
 */
class RateLimiter {


	/** Max failed login attempts per window before temporary block */
	private int $max_login_failures;

	/** Window length in seconds */
	private int $window;

	/** Block duration in seconds after threshold exceeded */
	private int $block_time;

	/** Transient key prefix for hit counters */
	private const PREFIX = 'apollo_rl_';

	/** Progressive IP block durations by tier (seconds) */
	private const IP_TIER_DURATIONS = array(
		0 => 3600,    // 1 hour
		1 => 36000,   // 10 hours
		2 => 86400,   // 24 hours
		3 => 129600,  // 36 hours (max)
	);

	/** Tier memory TTL (48 hours) */
	private const TIER_TTL = 172800;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->max_login_failures = (int) ( defined( 'APOLLO_RATE_LIMIT_MAX_HITS' ) ? APOLLO_RATE_LIMIT_MAX_HITS : get_option( 'apollo_rate_max_hits', 20 ) );
		$this->window             = (int) ( defined( 'APOLLO_RATE_LIMIT_WINDOW' ) ? APOLLO_RATE_LIMIT_WINDOW : get_option( 'apollo_rate_window', 60 ) );
		$this->block_time         = (int) ( defined( 'APOLLO_RATE_LIMIT_BLOCK_TIME' ) ? APOLLO_RATE_LIMIT_BLOCK_TIME : get_option( 'apollo_rate_block', 3600 ) );

		// Hook into failed logins to track by IP
		add_action( 'wp_login_failed', array( $this, 'on_login_failed' ) );

		// Reset IP counters + escalation tier on ANY successful login (native, AJAX, REST).
		// REST AuthController::login() fires do_action('wp_login', ...), so this single
		// listener guarantees a clean slate for legitimate users across every login path.
		add_action( 'wp_login', array( $this, 'on_login_success' ), 10, 2 );

		// Hook into comment attempts (brute force on comment form)
		add_action( 'pre_comment_on_post', array( $this, 'on_comment_attempt' ) );

		// Also check on every login page load (too many page loads = bot)
		add_action( 'login_init', array( $this, 'on_login_page_load' ) );
	}

	/**
	 * Fired on every successful login — clear the IP's failure/page/comment
	 * counters and reset its progressive block tier so a previously rate-limited
	 * user does not carry stale escalation state after authenticating.
	 *
	 * @param string        $user_login Username (unused; signature matches wp_login).
	 * @param \WP_User|null $user       Authenticated user (unused).
	 * @return void
	 */
	public function on_login_success( string $user_login = '', $user = null ): void {
		$ip = Firewall::get_client_ip();
		if ( '' === $ip ) {
			return;
		}
		$this->reset( $ip );
		$this->reset_ip_tier( $ip );
	}

	/**
	 * Get block duration for current IP tier, then escalate.
	 *
	 * @param string $ip
	 * @return int Duration in seconds.
	 */
	private function get_ip_block_duration( string $ip ): int {
		$tier_key = self::PREFIX . 'tier_' . md5( $ip );
		$tier     = (int) get_transient( $tier_key );

		$max_tier = max( array_keys( self::IP_TIER_DURATIONS ) );
		$clamped  = min( $tier, $max_tier );
		$duration = self::IP_TIER_DURATIONS[ $clamped ] ?? self::IP_TIER_DURATIONS[ $max_tier ];

		// Escalate tier for next block.
		set_transient( $tier_key, $tier + 1, self::TIER_TTL );

		return $duration;
	}

	/**
	 * Reset IP tier on successful login.
	 *
	 * @param string $ip
	 * @return void
	 */
	public function reset_ip_tier( string $ip ): void {
		delete_transient( self::PREFIX . 'tier_' . md5( $ip ) );
	}

	/**
	 * Fired on every failed login — increment IP counter
	 *
	 * @param string $username
	 * @return void
	 */
	public function on_login_failed( string $username ): void {
		$ip  = Firewall::get_client_ip();
		$key = self::PREFIX . 'fail_' . md5( $ip );

		$hits = (int) get_transient( $key );
		++$hits;

		set_transient( $key, $hits, $this->window );

		if ( $hits >= $this->max_login_failures ) {
			$block_duration = $this->get_ip_block_duration( $ip );
			Firewall::temp_blacklist_ip( $ip, $block_duration );
			do_action( 'apollo/security/rate_limited', $ip, $username, $hits );
		}
	}

	/**
	 * Fired on every login page load — track repeated page hits by IP
	 * (bot mitigation: too many login page loads = suspicious)
	 *
	 * @return void
	 */
	public function on_login_page_load(): void {
		// Only count GET requests to avoid interfering with actual POST logins
		if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'GET' ) {
			return;
		}

		$ip  = Firewall::get_client_ip();
		$key = self::PREFIX . 'page_' . md5( $ip );

		$hits = (int) get_transient( $key );
		++$hits;

		set_transient( $key, $hits, $this->window );

		// Threshold: 3× the login failure threshold for page loads
		if ( $hits >= ( $this->max_login_failures * 3 ) ) {
			$block_duration = $this->get_ip_block_duration( $ip );
			Firewall::temp_blacklist_ip( $ip, $block_duration );
			do_action( 'apollo/security/rate_limited_page', $ip, $hits );
		}
	}

	/**
	 * Fired on comment submission — rate limit comment spam
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function on_comment_attempt( int $post_id ): void {
		$ip  = Firewall::get_client_ip();
		$key = self::PREFIX . 'cmt_' . md5( $ip );

		$hits = (int) get_transient( $key );
		++$hits;

		set_transient( $key, $hits, $this->window );

		// 5 comment submissions per window is suspicious
		if ( $hits >= 5 ) {
			$block_duration = $this->get_ip_block_duration( $ip );
			Firewall::temp_blacklist_ip( $ip, $block_duration );
		}
	}

	/**
	 * Get current hit count for an IP (useful for debug/admin views)
	 *
	 * @param string $ip    IP address.
	 * @param string $type  'fail' | 'page' | 'cmt'
	 * @return int
	 */
	public function get_hits( string $ip, string $type = 'fail' ): int {
		$key = self::PREFIX . $type . '_' . md5( $ip );
		return (int) get_transient( $key );
	}

	/**
	 * Reset hit counters for an IP
	 *
	 * @param string $ip
	 * @return void
	 */
	public function reset( string $ip ): void {
		foreach ( array( 'fail', 'page', 'cmt' ) as $type ) {
			delete_transient( self::PREFIX . $type . '_' . md5( $ip ) );
		}
	}

	/**
	 * Atomic-ish counter stored in wp_options (INSERT … ON DUPLICATE KEY UPDATE).
	 *
	 * Used by REST auth paths where transient read-modify-write races under parallel requests.
	 *
	 * @param string $bucket Logical bucket name (e.g. apollo_app_auth_{ip_hash}).
	 * @return int Current count after expiry reset; 0 when expired or unset.
	 */
	public static function get_counter( string $bucket ): int {
		global $wpdb;

		$key     = self::counter_option_name( $bucket );
		$exp_key = $key . '_exp';
		$exp     = (int) get_option( $exp_key, 0 );

		if ( $exp > 0 && time() > $exp ) {
			delete_option( $key );
			delete_option( $exp_key );
			return 0;
		}

		return (int) get_option( $key, 0 );
	}

	/**
	 * Increment counter and set TTL window on first increment.
	 *
	 * @param string $bucket Logical bucket name.
	 * @param int    $ttl    Window length in seconds.
	 * @return int New count value.
	 */
	public static function increment_counter( string $bucket, int $ttl ): int {
		global $wpdb;

		$key     = self::counter_option_name( $bucket );
		$exp_key = $key . '_exp';
		$exp     = (int) get_option( $exp_key, 0 );

		if ( $exp > 0 && time() > $exp ) {
			delete_option( $key );
			delete_option( $exp_key );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'no')
				ON DUPLICATE KEY UPDATE option_value = CAST(option_value AS UNSIGNED) + 1",
				$key
			)
		);

		if ( (int) get_option( $exp_key, 0 ) <= 0 ) {
			update_option( $exp_key, time() + $ttl, false );
		}

		return (int) get_option( $key, 0 );
	}

	/**
	 * Clear counter bucket (e.g. after successful login).
	 *
	 * @param string $bucket Logical bucket name.
	 * @return void
	 */
	public static function clear_counter( string $bucket ): void {
		$key = self::counter_option_name( $bucket );
		delete_option( $key );
		delete_option( $key . '_exp' );
	}

	/**
	 * @param string $bucket Logical bucket name.
	 * @return string Option name for counter storage.
	 */
	private static function counter_option_name( string $bucket ): string {
		return self::PREFIX . 'cnt_' . md5( $bucket );
	}
}
