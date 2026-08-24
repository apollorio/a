<?php

/**
 * Rating Handler Component
 *
 * Anonymous user-to-user voting system.
 * Each logged-in user can vote ONCE per category per target user.
 * Public view: aggregated averages only (no voter identity revealed).
 * Admin view: full voter list + ability to adjust votes.
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users\Components;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RatingHandler {


	/**
	 * Rating categories with icons and colors
	 */
	public const CATEGORIES = array(
		'sexy'      => array(
			'icon'  => 'ri-heart-3-fill',
			'label' => 'Sexy',
			'color' => 'FF9820',
		),
		'legal'     => array(
			'icon'  => 'ri-user-smile-line',
			'label' => 'Legal',
			'color' => '#22c55e',
		),
		'confiavel' => array(
			'icon'  => 'ri-instance-line',
			'label' => 'Confiável',
			'color' => '#3b82f6',
		),
	);

	/**
	 * Max score per category (number of icons)
	 */
	public const MAX_SCORE = 3;

	public function __construct() {
		// Public AJAX (logged-in users)
		add_action( 'wp_ajax_apollo_submit_rating', array( $this, 'ajax_submit_rating' ) );
		add_action( 'wp_ajax_apollo_get_ratings', array( $this, 'ajax_get_ratings' ) );
		add_action( 'wp_ajax_apollo_get_voter_list', array( $this, 'ajax_get_voter_list' ) );

		// Admin AJAX
		add_action( 'wp_ajax_apollo_admin_get_voters', array( $this, 'ajax_admin_get_voters' ) );
		add_action( 'wp_ajax_apollo_admin_adjust_rating', array( $this, 'ajax_admin_adjust_rating' ) );
		add_action( 'wp_ajax_apollo_admin_delete_rating', array( $this, 'ajax_admin_delete_rating' ) );
	}

	// ─── PUBLIC ─────────────────────────────────────────────────────────

	/**
	 * Submit or update a vote.
	 * One vote per voter per target per category (UPSERT via UNIQUE KEY).
	 */
	public function ajax_submit_rating(): void {
		check_ajax_referer( 'apollo_profile_nonce', 'nonce' );

		$voter_id  = get_current_user_id();
		$target_id = absint( $_POST['target_id'] ?? 0 );
		$category  = sanitize_text_field( $_POST['category'] ?? '' );
		$score     = absint( $_POST['score'] ?? 0 );

		// Validation
		if ( ! $voter_id ) {
			wp_send_json_error( array( 'message' => 'Login required.' ) );
		}
		if ( $voter_id === $target_id ) {
			wp_send_json_error( array( 'message' => 'Você não pode votar em si mesmo.' ) );
		}
		if ( ! array_key_exists( $category, self::CATEGORIES ) ) {
			wp_send_json_error( array( 'message' => 'Categoria inválida.' ) );
		}
		if ( $score < 0 || $score > self::MAX_SCORE ) {
			wp_send_json_error( array( 'message' => 'Pontuação inválida.' ) );
		}
		if ( ! get_userdata( $target_id ) ) {
			wp_send_json_error( array( 'message' => 'Usuário não encontrado.' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'apollo_user_ratings';

		// UPSERT: INSERT ... ON DUPLICATE KEY UPDATE
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (voter_id, target_id, category, score, created_at, updated_at)
			 VALUES (%d, %d, %s, %d, NOW(), NOW())
			 ON DUPLICATE KEY UPDATE score = %d, updated_at = NOW()",
				$voter_id,
				$target_id,
				$category,
				$score,
				$score
			)
		);

		/**
		 * Fires after a user rating is submitted/updated.
		 *
		 * @param int    $voter_id  The user who voted.
		 * @param int    $target_id The user being rated.
		 * @param string $category  Rating category (sexy, legal, confiavel).
		 * @param int    $score     Score given (0 to MAX_SCORE).
		 */
		do_action( 'apollo/matchmaking/signal_updated', $voter_id, $target_id, 'ratings' );

		// Return updated averages
		wp_send_json_success(
			array(
				'averages'   => self::get_averages( $target_id ),
				'user_votes' => self::get_user_votes( $voter_id, $target_id ),
			)
		);
	}

	/**
	 * Get ratings for a user (public — no voter identities).
	 */
	public function ajax_get_ratings(): void {
		$target_id = absint( $_GET['target_id'] ?? $_POST['target_id'] ?? 0 );
		if ( ! $target_id ) {
			wp_send_json_error( array( 'message' => 'target_id required.' ) );
		}

		$voter_id = get_current_user_id(); // 0 if guest

		wp_send_json_success(
			array(
				'averages'    => self::get_averages( $target_id ),
				'total_votes' => self::get_total_voters( $target_id ),
				'user_votes'  => $voter_id ? self::get_user_votes( $voter_id, $target_id ) : array(),
			)
		);
	}

	// ─── ADMIN ──────────────────────────────────────────────────────────

	/**
	 * Admin: Get full voter list for a target user.
	 */
	public function ajax_admin_get_voters(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}

		$target_id = absint( $_GET['target_id'] ?? $_POST['target_id'] ?? 0 );
		if ( ! $target_id ) {
			wp_send_json_error( array( 'message' => 'target_id required.' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'apollo_user_ratings';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.id, r.voter_id, r.category, r.score, r.created_at, r.updated_at,
			        u.user_login, u.display_name
			 FROM {$table} r
			 LEFT JOIN {$wpdb->users} u ON r.voter_id = u.ID
			 WHERE r.target_id = %d
			 ORDER BY r.updated_at DESC",
				$target_id
			),
			ARRAY_A
		);

		wp_send_json_success(
			array(
				'voters'   => $rows,
				'averages' => self::get_averages( $target_id ),
			)
		);
	}

	/**
	 * Admin: Adjust a specific vote.
	 */
	public function ajax_admin_adjust_rating(): void {
		check_ajax_referer( 'apollo_admin_rating_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}

		$rating_id = absint( $_POST['rating_id'] ?? 0 );
		$new_score = absint( $_POST['score'] ?? 0 );

		if ( ! $rating_id || $new_score > self::MAX_SCORE ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters.' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'apollo_user_ratings';

		$wpdb->update(
			$table,
			array(
				'score'      => $new_score,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $rating_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		wp_send_json_success( array( 'message' => 'Vote updated.' ) );
	}

	/**
	 * Admin: Delete a specific vote (hate attack removal).
	 */
	public function ajax_admin_delete_rating(): void {
		check_ajax_referer( 'apollo_admin_rating_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}

		$rating_id = absint( $_POST['rating_id'] ?? 0 );
		if ( ! $rating_id ) {
			wp_send_json_error( array( 'message' => 'rating_id required.' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'apollo_user_ratings';

		$wpdb->delete( $table, array( 'id' => $rating_id ), array( '%d' ) );

		wp_send_json_success( array( 'message' => 'Vote removed.' ) );
	}

	// ─── STATIC HELPERS (used in templates) ─────────────────────────────

	/**
	 * Get averages per category for a target user.
	 *
	 * @return array [ 'sexy' => 2.3, 'legal' => 1.0, 'confiavel' => 0 ]
	 */
	public static function get_averages( int $target_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_user_ratings';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category, AVG(score) as avg_score, COUNT(*) as vote_count
			 FROM {$table}
			 WHERE target_id = %d
			 GROUP BY category",
				$target_id
			),
			ARRAY_A
		);

		$averages = array();
		foreach ( self::CATEGORIES as $key => $meta ) {
			$averages[ $key ] = 0;
		}
		foreach ( $rows as $row ) {
			if ( isset( $averages[ $row['category'] ] ) ) {
				$averages[ $row['category'] ] = round( (float) $row['avg_score'], 1 );
			}
		}

		return $averages;
	}

	/**
	 * Get a specific voter's votes for a target user.
	 *
	 * @return array [ 'sexy' => 2, 'legal' => 0, 'confiavel' => 1 ]
	 */
	public static function get_user_votes( int $voter_id, int $target_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_user_ratings';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category, score FROM {$table}
			 WHERE voter_id = %d AND target_id = %d",
				$voter_id,
				$target_id
			),
			ARRAY_A
		);

		$votes = array();
		foreach ( self::CATEGORIES as $key => $meta ) {
			$votes[ $key ] = 0;
		}
		foreach ( $rows as $row ) {
			if ( isset( $votes[ $row['category'] ] ) ) {
				$votes[ $row['category'] ] = (int) $row['score'];
			}
		}

		return $votes;
	}

	/**
	 * Get total unique voters for a target user.
	 */
	public static function get_total_voters( int $target_id ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_user_ratings';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT voter_id) FROM {$table} WHERE target_id = %d",
				$target_id
			)
		);
	}

	/**
	 * Get voter count per category for a target user.
	 *
	 * @return array [ 'sexy' => 120, 'legal' => 340, 'confiavel' => 2340 ]
	 */
	public static function get_vote_counts( int $target_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_user_ratings';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category, COUNT(*) as cnt
				 FROM {$table}
				 WHERE target_id = %d AND score > 0
				 GROUP BY category",
				$target_id
			),
			ARRAY_A
		);

		$counts = array();
		foreach ( self::CATEGORIES as $key => $meta ) {
			$counts[ $key ] = 0;
		}
		foreach ( $rows as $row ) {
			if ( isset( $counts[ $row['category'] ] ) ) {
				$counts[ $row['category'] ] = (int) $row['cnt'];
			}
		}

		return $counts;
	}

	/**
	 * Get recent voter avatars for a category (max 4).
	 * Returns array of [ 'user_id', 'avatar_url', 'display_name' ].
	 *
	 * @param int    $target_id Profile user ID.
	 * @param string $category  Rating category slug.
	 * @param int    $limit     Max avatars to return.
	 * @return array
	 */
	public static function get_voter_avatars( int $target_id, string $category, int $limit = 4 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_user_ratings';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.voter_id, r.created_at, u.display_name
				 FROM {$table} r
				 LEFT JOIN {$wpdb->users} u ON r.voter_id = u.ID
				 WHERE r.target_id = %d AND r.category = %s AND r.score > 0
				 ORDER BY r.created_at DESC
				 LIMIT %d",
				$target_id,
				$category,
				$limit
			),
			ARRAY_A
		);

		$avatars = array();
		foreach ( $rows as $row ) {
			$uid        = (int) $row['voter_id'];
			$avatar_url = function_exists( 'Apollo\Users\apollo_get_user_avatar_url' )
				? \Apollo\Users\apollo_get_user_avatar_url( $uid, 'thumbnail' )
				: get_avatar_url( $uid, array( 'size' => 56 ) );

			$avatars[] = array(
				'user_id'      => $uid,
				'avatar_url'   => $avatar_url,
				'display_name' => $row['display_name'] ?? '',
				'created_at'   => $row['created_at'],
			);
		}

		return $avatars;
	}

	/**
	 * Get full voter list for a category (used in popup).
	 * Confiável: visible to ALL users. Sexy/Legal: ADMIN ONLY.
	 *
	 * @param int    $target_id   Profile user ID.
	 * @param string $category    Rating category slug.
	 * @param int    $page        Page number (1-based).
	 * @param int    $per_page    Results per page.
	 * @return array [ 'voters' => [...], 'total' => int ]
	 */
	public static function get_voter_list( int $target_id, string $category, int $page = 1, int $per_page = 20 ): array {
		global $wpdb;
		$table  = $wpdb->prefix . 'apollo_user_ratings';
		$offset = ( $page - 1 ) * $per_page;

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table}
				 WHERE target_id = %d AND category = %s AND score > 0",
				$target_id,
				$category
			)
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.voter_id, r.score, r.created_at, r.updated_at,
				        u.display_name, u.user_login
				 FROM {$table} r
				 LEFT JOIN {$wpdb->users} u ON r.voter_id = u.ID
				 WHERE r.target_id = %d AND r.category = %s AND r.score > 0
				 ORDER BY r.created_at DESC
				 LIMIT %d OFFSET %d",
				$target_id,
				$category,
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$voters = array();
		foreach ( $rows as $row ) {
			$uid        = (int) $row['voter_id'];
			$avatar_url = function_exists( 'Apollo\Users\apollo_get_user_avatar_url' )
				? \Apollo\Users\apollo_get_user_avatar_url( $uid, 'thumbnail' )
				: get_avatar_url( $uid, array( 'size' => 56 ) );

			$social_name = get_user_meta( $uid, '_apollo_social_name', true );

			$voters[] = array(
				'user_id'      => $uid,
				'username'     => $row['user_login'] ?? '',
				'display_name' => $social_name ?: ( $row['display_name'] ?? '' ),
				'avatar_url'   => $avatar_url,
				'score'        => (int) $row['score'],
				'voted_at'     => $row['created_at'],
				'profile_url'  => home_url( '/id/' . ( $row['user_login'] ?? '' ) ),
			);
		}

		return array(
			'voters' => $voters,
			'total'  => $total,
		);
	}

	// ─── AJAX: VOTER LIST FOR POPUP ─────────────────────────────────────

	/**
	 * AJAX: Get voter list for popup.
	 * Confiável = open to all logged-in users.
	 * Sexy/Legal = admin only (SECURITY: never leak to non-admin).
	 */
	public function ajax_get_voter_list(): void {
		$target_id = absint( $_GET['target_id'] ?? $_POST['target_id'] ?? 0 );
		$category  = sanitize_text_field( $_GET['category'] ?? $_POST['category'] ?? '' );
		$page      = max( 1, absint( $_GET['page'] ?? 1 ) );

		if ( ! $target_id || ! array_key_exists( $category, self::CATEGORIES ) ) {
			wp_send_json_error( array( 'message' => 'Parâmetros inválidos.' ) );
		}

		// SECURITY GATE: Sexy/Legal = admin only
		if ( in_array( $category, array( 'sexy', 'legal' ), true ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Acesso restrito.' ), 403 );
		}

		// Confiável requires login
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Login necessário.' ), 401 );
		}

		$result = self::get_voter_list( $target_id, $category, $page );

		wp_send_json_success( $result );
	}
}
