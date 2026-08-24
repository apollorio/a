<?php

/**
 * NREP — Nota de Repúdio Auto-Coding System
 *
 * Hooks into transition_post_status to auto-generate sequential
 * NREP codes for posts in the "nota-de-repudio" category.
 *
 * Format: NREP.YYYY-NNN (e.g., NREP.2026-001)
 *
 * @package Apollo\Journal
 */

declare(strict_types=1);

namespace Apollo\Journal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NREP handler.
 */
class NREP {


	/**
	 * Wire hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'transition_post_status', array( $this, 'maybe_assign_code' ), 10, 3 );
		// Cron retry for any lock-blocked assigns.
		add_action( 'apollo_journal_retry_nrep', array( $this, 'retry_assign_code' ) );
	}

	/**
	 * Retry NREP code assignment for a post that was blocked by the lock.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function retry_assign_code( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post || get_post_meta( $post_id, '_nrep_code', true ) ) {
			return;
		}
		// Re-run via status transition simulation.
		$this->maybe_assign_code( 'publish', 'new', $post );
	}

	/**
	 * Assign NREP code when a post is published for the first time
	 * and belongs to the "nota-de-repudio" category.
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Previous post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function maybe_assign_code( string $new_status, string $old_status, \WP_Post $post ): void {
		// Only on first publish of a regular post.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		$allowed_types = array( 'post', 'journal_nota' );
		if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
			return;
		}

		// journal_nota always qualifies; regular posts must be in "nota-de-repudio" category.
		if ( 'post' === $post->post_type && ! has_term( 'nota-de-repudio', 'category', $post ) ) {
			return;
		}

		// Skip if already coded.
		if ( get_post_meta( $post->ID, '_nrep_code', true ) ) {
			return;
		}

		// ── Distributed lock — prevents duplicate codes under concurrent requests ──
		// Acquire lock: set_transient returns false if the key already exists.
		$lock_key = 'aj_nrep_lock_' . gmdate( 'Y' );
		$lock_ttl = 5; // seconds — enough for one wp_update_post cycle.

		// Spin-wait up to 2s for an existing lock to clear (handles brief overlaps).
		$attempts = 0;
		while ( get_transient( $lock_key ) && $attempts < 4 ) {
			usleep( 500000 ); // 0.5s
			$attempts++;
		}

		// Re-check post meta after any wait (another worker may have coded it).
		if ( get_post_meta( $post->ID, '_nrep_code', true ) ) {
			return;
		}

		// Use add_transient for atomic claim — only one process succeeds.
		if ( ! set_transient( $lock_key, $post->ID, $lock_ttl ) ) {
			// Lock acquired by another process. Schedule a late retry.
			wp_schedule_single_event( time() + 3, 'apollo_journal_retry_nrep', array( $post->ID ) );
			return;
		}

		$code = $this->generate_code( $post->ID );

		delete_transient( $lock_key ); // Release lock immediately after generation.

		if ( ! $code ) {
			return;
		}

		// Store meta.
		update_post_meta( $post->ID, '_nrep_code', $code['code'] );
		update_post_meta( $post->ID, '_nrep_year', $code['year'] );
		update_post_meta( $post->ID, '_nrep_seq', $code['seq'] );

		/**
		 * Fires after a NREP code is assigned to a post.
		 *
		 * @since 1.0.0
		 * @param int    $post_id Post ID.
		 * @param string $code    The assigned NREP code string.
		 * @param array  $meta    Full code array (code, year, seq).
		 */
		do_action( 'apollo/journal/nrep_assigned', $post->ID, $code['code'], $code );

		// Prepend code to the title (avoid infinite loop).
		$current_title = get_the_title( $post->ID );
		$prefix = get_option( 'aj_nrep_prefix', APOLLO_JOURNAL_NREP_PREFIX );

		if ( str_starts_with( $current_title, $prefix ) ) {
			return;
		}

		remove_action( 'transition_post_status', array( $this, 'maybe_assign_code' ), 10 );

		wp_update_post(
			array(
				'ID'         => $post->ID,
				'post_title' => $code['code'] . ' — ' . $current_title,
			)
		);

		add_action( 'transition_post_status', array( $this, 'maybe_assign_code' ), 10, 3 );
	}

	/**
	 * Generate the next sequential NREP code for the current year.
	 *
	 * Uses get_posts with meta_query + orderby meta_value_num
	 * for clean, performant retrieval.
	 *
	 * @param int $exclude_id Post ID to exclude from query.
	 * @return array{code: string, year: string, seq: int}|null
	 */
	private function generate_code( int $exclude_id ): ?array {
		$year = gmdate( 'Y' );

		$last = get_posts(
			array(
				'posts_per_page' => 1,
				'post_type'      => array( 'post', 'journal_nota' ),
				'post_status'    => 'publish',
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_nrep_seq',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_nrep_year',
						'value' => $year,
					),
				),
				'post__not_in'   => array( $exclude_id ),
				'no_found_rows'  => true,
				'fields'         => 'ids',
			)
		);

		$max  = $last ? (int) get_post_meta( $last[0], '_nrep_seq', true ) : 0;
		$next = $max + 1;

		$prefix = get_option( 'aj_nrep_prefix', APOLLO_JOURNAL_NREP_PREFIX );
		$padded = str_pad( (string) $next, 3, '0', STR_PAD_LEFT );
		$code   = $prefix . $year . '-' . $padded;

		return array(
			'code' => $code,
			'year' => $year,
			'seq'  => $next,
		);
	}
}
