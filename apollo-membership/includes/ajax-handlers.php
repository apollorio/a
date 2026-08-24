<?php
/**
 * AJAX Handlers
 *
 * Server-side handlers for admin AJAX tools.
 * Adapted from BadgeOS AJAX patterns.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// RECALCULATE POINTS
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_apollo_membership_recalc_points', 'apollo_membership_ajax_recalc_points' );

/**
 * Recalculate all user points from transaction history
 * Adapted from badgeos_recalc_total_points()
 */
function apollo_membership_ajax_recalc_points(): void {
	check_ajax_referer( 'apollo_membership_admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Permissão negada.', 'apollo-membership' ) );
	}

	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_POINTS;

	// Get all users with point transactions
	$user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$table}" );
	$count    = 0;

	foreach ( $user_ids as $uid ) {
		apollo_membership_recalc_total_points( (int) $uid );
		++$count;
	}

	wp_send_json_success(
		array(
			'message' => sprintf(
				__( 'Pontos recalculados para %d usuários.', 'apollo-membership' ),
				$count
			),
			'count'   => $count,
		)
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// RECALCULATE ACHIEVEMENTS
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_apollo_membership_recalc_achievements', 'apollo_membership_ajax_recalc_achievements' );

/**
 * Recalculate achievements for all users
 * Adapted from badgeos_recalc_achievements()
 */
function apollo_membership_ajax_recalc_achievements(): void {
	check_ajax_referer( 'apollo_membership_admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Permissão negada.', 'apollo-membership' ) );
	}

	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_ACHIEVEMENTS;

	// Get all users with achievements
	$user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$table}" );
	$count    = 0;

	foreach ( $user_ids as $uid ) {
		if ( function_exists( 'apollo_recalculate_achievements_for_user' ) ) {
			apollo_recalculate_achievements_for_user( (int) $uid );
		}
		++$count;
	}

	wp_send_json_success(
		array(
			'message' => sprintf(
				__( 'Achievements recalculados para %d usuários.', 'apollo-membership' ),
				$count
			),
			'count'   => $count,
		)
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// RECALCULATE RANKS
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_apollo_membership_recalc_ranks', 'apollo_membership_ajax_recalc_ranks' );

/**
 * Recalculate rank progression for all users
 * Adapted from BadgeOS rank recalculation
 */
function apollo_membership_ajax_recalc_ranks(): void {
	check_ajax_referer( 'apollo_membership_admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Permissão negada.', 'apollo-membership' ) );
	}

	global $wpdb;

	// Get all users
	$user_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );
	$count    = 0;

	foreach ( $user_ids as $uid ) {
		if ( function_exists( 'apollo_check_rank_progression' ) ) {
			apollo_check_rank_progression( (int) $uid );
			++$count;
		}
	}

	wp_send_json_success(
		array(
			'message' => sprintf(
				__( 'Ranks recalculados para %d usuários.', 'apollo-membership' ),
				$count
			),
			'count'   => $count,
		)
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// RESET TRIGGER COUNTS
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_apollo_membership_reset_triggers', 'apollo_membership_ajax_reset_triggers' );

/**
 * Reset all trigger counts
 */
function apollo_membership_ajax_reset_triggers(): void {
	check_ajax_referer( 'apollo_membership_admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Permissão negada.', 'apollo-membership' ) );
	}

	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_TRIGGERS;
	$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL

	// Also clear user meta trigger counts
	$wpdb->query(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key = '_apollo_trigger_count'"
	);

	wp_send_json_success(
		array(
			'message' => __( 'Contadores de triggers resetados com sucesso.', 'apollo-membership' ),
		)
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// RE-SEED DEFAULT TRIGGER POINTS
// ═══════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_apollo_membership_seed_defaults', 'apollo_membership_ajax_seed_defaults' );

/**
 * Re-seed default trigger point values
 */
function apollo_membership_ajax_seed_defaults(): void {
	check_ajax_referer( 'apollo_membership_admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Permissão negada.', 'apollo-membership' ) );
	}

	if ( function_exists( 'apollo_seed_default_trigger_points' ) ) {
		apollo_seed_default_trigger_points();
	}

	wp_send_json_success(
		array(
			'message' => __( 'Valores padrão de triggers re-carregados.', 'apollo-membership' ),
		)
	);
}
