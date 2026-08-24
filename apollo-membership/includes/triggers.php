<?php

/**
 * Triggers System
 *
 * Activity trigger registration and event handling.
 * Adapted from BadgeOS triggers.php (includes/triggers.php).
 *
 * 21 triggers per apollo-registry.json:
 *   9 WordPress core + 12 Apollo-specific
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// TRIGGER DEFINITIONS — adapted from badgeos_get_activity_triggers()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get all registered activity triggers
 *
 * @return array Associative trigger_hook => label
 */
function apollo_get_activity_triggers(): array {
	/**
	 * 9 WordPress core triggers (adapted from BadgeOS)
	 * + 12 Apollo-specific triggers (from registry spec)
	 */
	$triggers = array(
		// ─── WordPress Core Triggers ───
		'wp_login'                   => __( 'Fazer login no site', 'apollo-membership' ),
		'comment_post'               => __( 'Comentar em um post', 'apollo-membership' ),
		'publish_post'               => __( 'Publicar um post', 'apollo-membership' ),
		'user_register'              => __( 'Registrar-se no site', 'apollo-membership' ),
		'daily_visit'                => __( 'Visitar o site diariamente', 'apollo-membership' ),
		'profile_update'             => __( 'Atualizar o perfil', 'apollo-membership' ),
		'wp_head'                    => __( 'Visitar qualquer página', 'apollo-membership' ),
		'delete_comment'             => __( 'Deletar um comentário', 'apollo-membership' ),
		'transition_post_status'     => __( 'Alterar status de publicação', 'apollo-membership' ),

		// ─── Apollo-specific Triggers ───
		'apollo_social_message_sent' => __( 'Enviar uma mensagem social', 'apollo-membership' ),
		'apollo_event_favorited'     => __( 'Favoritar um evento', 'apollo-membership' ),
		'apollo_page_favorited'      => __( 'Favoritar uma página', 'apollo-membership' ),
		'apollo_depoiment_submitted' => __( 'Enviar um depoimento', 'apollo-membership' ),
		'apollo_reaction_added'      => __( 'Adicionar uma reação', 'apollo-membership' ),
		'apollo_hours_online'        => __( 'Tempo online no site', 'apollo-membership' ),
		'apollo_interaction_logged'  => __( 'Registrar interação na plataforma', 'apollo-membership' ),
		'apollo_event_created'       => __( 'Criar um evento', 'apollo-membership' ),
		'apollo_event_attended'      => __( 'Participar de um evento', 'apollo-membership' ),
		'apollo_classified_posted'   => __( 'Publicar um classificado', 'apollo-membership' ),
		'apollo_group_joined'        => __( 'Entrar em um grupo', 'apollo-membership' ),
		'apollo_badge_earned'        => __( 'Ganhar uma badge de membership', 'apollo-membership' ),
	);

	return (array) apply_filters( 'apollo_activity_triggers', $triggers );
}

// ═══════════════════════════════════════════════════════════════════════════
// LOAD TRIGGERS — adapted from badgeos_load_activity_triggers()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Register WordPress action hooks for each trigger
 */
function apollo_load_activity_triggers(): void {
	$triggers = apollo_get_activity_triggers();

	foreach ( $triggers as $hook => $label ) {
		// Skip pseudo-triggers that don't map to real actions
		if ( in_array( $hook, array( 'daily_visit', 'apollo_hours_online' ), true ) ) {
			continue;
		}

		add_action( $hook, 'apollo_trigger_event', 10, 20 );
	}

	// Daily visit uses separate handler
	add_action( 'wp_head', 'apollo_handle_daily_visit_trigger', 10 );
}
add_action( 'init', 'apollo_load_activity_triggers', 20 );

// ═══════════════════════════════════════════════════════════════════════════
// TRIGGER EVENT HANDLER — adapted from badgeos_trigger_event()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Handle a triggered event
 * Adapted from badgeos_trigger_event() in BadgeOS triggers.php
 *
 * @param mixed ...$args Hook arguments
 * @return mixed
 */
function apollo_trigger_event( ...$args ) {
	// Get the current action being triggered
	$this_trigger = current_filter();

	// Determine user ID from the trigger
	$user_id = apollo_trigger_get_user_id( $this_trigger, $args );

	if ( empty( $user_id ) ) {
		return $args[0] ?? null;
	}

	// Avoid recursion — skip during achievement processing
	if ( did_action( 'apollo_trigger_processing' ) && ! did_action( 'apollo_trigger_processing_complete' ) ) {
		return $args[0] ?? null;
	}

	/**
	 * Filter: Allow plugins to abort trigger processing
	 *
	 * @param bool   $process    Whether to process (default true)
	 * @param int    $user_id    User ID
	 * @param string $trigger    Trigger name
	 * @param array  $args       Hook args
	 */
	$process = apply_filters( 'apollo_user_deserves_trigger', true, $user_id, $this_trigger, $args );

	if ( ! $process ) {
		return $args[0] ?? null;
	}

	do_action( 'apollo_trigger_processing' );

	// Update trigger count
	$count = apollo_update_user_trigger_count( $user_id, $this_trigger, get_current_blog_id(), $args );

	// Award points for this trigger (gamification)
	apollo_maybe_award_points_for_trigger( $user_id, $this_trigger, $count, $args );

	// Check achievements associated with this trigger
	apollo_maybe_award_achievements_for_trigger( $user_id, $this_trigger, $count, $args );

	do_action( 'apollo_trigger_processing_complete' );

	return $args[0] ?? null;
}

// ═══════════════════════════════════════════════════════════════════════════
// USER ID DETECTION — adapted from badgeos_trigger_get_user_id()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get user ID from trigger context
 * Adapted from badgeos_trigger_get_user_id()
 *
 * @param string $trigger Trigger name
 * @param array  $args    Hook args
 * @return int
 */
function apollo_trigger_get_user_id( string $trigger, array $args ): int {
	switch ( $trigger ) {
		case 'wp_login':
			// wp_login fires with $user_login, $user
			if ( isset( $args[1] ) && is_a( $args[1], 'WP_User' ) ) {
				return $args[1]->ID;
			}
			if ( isset( $args[0] ) && is_string( $args[0] ) ) {
				$user = get_user_by( 'login', $args[0] );
				return $user ? $user->ID : 0;
			}
			return get_current_user_id();

		case 'comment_post':
		case 'delete_comment':
			// comment_post fires with $comment_id, $approved
			if ( isset( $args[0] ) ) {
				$comment = get_comment( $args[0] );
				if ( $comment && $comment->user_id ) {
					return (int) $comment->user_id;
				}
			}
			return get_current_user_id();

		case 'publish_post':
		case 'transition_post_status':
			if ( isset( $args[2] ) && is_a( $args[2], 'WP_Post' ) ) {
				return (int) $args[2]->post_author;
			}
			if ( isset( $args[0] ) && is_numeric( $args[0] ) ) {
				$post = get_post( $args[0] );
				return $post ? (int) $post->post_author : get_current_user_id();
			}
			return get_current_user_id();

		case 'user_register':
			return isset( $args[0] ) ? (int) $args[0] : get_current_user_id();

		case 'profile_update':
			return isset( $args[0] ) ? (int) $args[0] : get_current_user_id();

		default:
			// Apollo triggers: first arg is usually user_id
			if ( str_starts_with( $trigger, 'apollo_' ) && isset( $args[0] ) && is_numeric( $args[0] ) ) {
				return (int) $args[0];
			}
			return get_current_user_id();
	}
}

// ═══════════════════════════════════════════════════════════════════════════
// TRIGGER COUNT CRUD — adapted from badgeos trigger count functions
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Update user's trigger count
 * Stores in custom table instead of user meta for performance
 *
 * @param int    $user_id     User ID
 * @param string $trigger     Trigger name
 * @param int    $site_id     Site ID
 * @param array  $args        Trigger args
 * @return int Updated count
 */
function apollo_update_user_trigger_count( int $user_id, string $trigger, int $site_id = 0, array $args = array() ): int {
	global $wpdb;

	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	$table     = $wpdb->prefix . APOLLO_TABLE_TRIGGERS;
	$now       = apollo_membership_default_datetime();
	$date_col  = apollo_membership_triggers_date_column();

	// Get object_id if applicable
	$object_id = 0;
	if ( isset( $args[0] ) && is_numeric( $args[0] ) ) {
		$object_id = (int) $args[0];
	}

	// Check existing
	$existing = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id, trigger_count FROM {$table} WHERE user_id = %d AND trigger_name = %s AND site_id = %d",
			$user_id,
			$trigger,
			$site_id
		)
	);

	if ( $existing ) {
		$new_count = (int) $existing->trigger_count + 1;
		$wpdb->update(
			$table,
			array(
				'trigger_count' => $new_count,
				$date_col       => $now,
			),
			array( 'id' => $existing->id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
		return $new_count;
	}

	$wpdb->insert(
		$table,
		array(
			'user_id'       => $user_id,
			'trigger_name'  => $trigger,
			'trigger_count' => 1,
			'site_id'       => $site_id,
			'object_id'     => $object_id,
			$date_col       => $now,
		),
		array( '%d', '%s', '%d', '%d', '%d', '%s' )
	);

	return 1;
}

/**
 * Get user's trigger count
 *
 * @param int    $user_id
 * @param string $trigger
 * @param int    $site_id
 * @return int
 */
function apollo_get_user_trigger_count( int $user_id, string $trigger, int $site_id = 0 ): int {
	global $wpdb;

	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	$table = $wpdb->prefix . APOLLO_TABLE_TRIGGERS;

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT trigger_count FROM {$table} WHERE user_id = %d AND trigger_name = %s AND site_id = %d",
			$user_id,
			$trigger,
			$site_id
		)
	);

	return $count ? (int) $count : 0;
}

/**
 * Reset user trigger count
 *
 * @param int    $user_id
 * @param string $trigger
 * @param int    $site_id
 * @return bool
 */
function apollo_reset_user_trigger_count( int $user_id, string $trigger, int $site_id = 0 ): bool {
	global $wpdb;

	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	$table = $wpdb->prefix . APOLLO_TABLE_TRIGGERS;

	return (bool) $wpdb->delete(
		$table,
		array(
			'user_id'      => $user_id,
			'trigger_name' => $trigger,
			'site_id'      => $site_id,
		),
		array( '%d', '%s', '%d' )
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// DAILY VISIT HANDLER — adapted from badgeos-daily-visit-handler
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Handle daily visit trigger
 * Users get credit once per day (24h window)
 */
function apollo_handle_daily_visit_trigger(): void {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	$last_visit = get_user_meta( $user_id, '_apollo_last_daily_visit', true );
	$today      = wp_date( 'Y-m-d' );

	if ( $last_visit === $today ) {
		return; // Already counted today
	}

	update_user_meta( $user_id, '_apollo_last_daily_visit', $today );

	// Fire as if it were a regular trigger
	do_action( 'apollo_daily_visit_internal', $user_id );

	// Process trigger
	apollo_trigger_event_for_user( $user_id, 'daily_visit' );
}

/**
 * Process a trigger for a specific user without relying on action hooks
 * Useful for pseudo-triggers like daily_visit
 *
 * @param int    $user_id
 * @param string $trigger
 */
function apollo_trigger_event_for_user( int $user_id, string $trigger ): void {
	if ( ! $user_id ) {
		return;
	}

	do_action( 'apollo_trigger_processing' );

	$count = apollo_update_user_trigger_count( $user_id, $trigger, get_current_blog_id() );

	apollo_maybe_award_points_for_trigger( $user_id, $trigger, $count, array() );
	apollo_maybe_award_achievements_for_trigger( $user_id, $trigger, $count, array() );

	do_action( 'apollo_trigger_processing_complete' );
}

// ═══════════════════════════════════════════════════════════════════════════
// TRIGGER → POINTS BRIDGE — gamification automatic awarding
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check if trigger should award points automatically
 * This is the gamification system — automatic, no admin intervention
 *
 * @param int    $user_id
 * @param string $trigger
 * @param int    $count
 * @param array  $args
 */
function apollo_maybe_award_points_for_trigger( int $user_id, string $trigger, int $count, array $args ): void {
	$settings = apollo_membership_get_settings();

	// Get configured point values for triggers
	$trigger_points = (array) get_option( 'apollo_membership_trigger_points', array() );

	if ( empty( $trigger_points[ $trigger ] ) ) {
		return;
	}

	$points = (int) $trigger_points[ $trigger ];

	if ( $points <= 0 ) {
		return;
	}

	// Check cooldown (prevent spam, adapted from BadgeOS points triggers)
	$cooldown = (int) ( $trigger_points[ $trigger . '_cooldown' ] ?? 0 );
	if ( $cooldown > 0 ) {
		$last_award = get_user_meta( $user_id, '_apollo_last_points_' . $trigger, true );
		if ( $last_award && ( time() - (int) $last_award ) < $cooldown ) {
			return; // Still in cooldown
		}
		update_user_meta( $user_id, '_apollo_last_points_' . $trigger, time() );
	}

	apollo_membership_add_credit( 0, $user_id, 'points', $points, $trigger );
}

// ═══════════════════════════════════════════════════════════════════════════
// TRIGGER → ACHIEVEMENTS BRIDGE
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Check achievements linked to this trigger
 * Adapted from trigger → rules-engine bridge in BadgeOS
 *
 * @param int    $user_id
 * @param string $trigger
 * @param int    $count
 * @param array  $args
 */
function apollo_maybe_award_achievements_for_trigger( int $user_id, string $trigger, int $count, array $args ): void {
	global $wpdb;

	$steps_table = $wpdb->prefix . APOLLO_TABLE_STEPS;

	// Find steps (achievement requirements) matching this trigger
	$steps = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$steps_table} WHERE trigger_type = %s AND status = 'active'",
			$trigger
		)
	);

	if ( empty( $steps ) ) {
		return;
	}

	foreach ( $steps as $step ) {
		// Check if user has met the required count
		if ( $count >= (int) $step->required_count ) {
			// Check the parent achievement
			if ( function_exists( 'apollo_maybe_award_achievement_to_user' ) ) {
				apollo_maybe_award_achievement_to_user( (int) $step->parent_achievement_id, $user_id, $trigger );
			}
		}
	}
}
