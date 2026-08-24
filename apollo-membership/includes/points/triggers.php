<?php

/**
 * Point Triggers
 *
 * Trigger registration specific to point awards/deductions.
 * Adapted from BadgeOS points/triggers.php.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// POINT AWARD TRIGGERS — adapted from badgeos_points_load_activity_triggers()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get triggers that can award points
 * These are the same triggers as activity triggers, but with point values attached
 *
 * @return array trigger_hook => label
 */
function apollo_get_point_award_triggers(): array
{
	$triggers = array(
		'wp_login'                   => __('Login no site', 'apollo-membership'),
		'comment_post'               => __('Comentar', 'apollo-membership'),
		'publish_post'               => __('Publicar post', 'apollo-membership'),
		'daily_visit'                => __('Visita diária', 'apollo-membership'),
		'profile_update'             => __('Atualizar perfil', 'apollo-membership'),
		'apollo_social_message_sent' => __('Enviar mensagem', 'apollo-membership'),
		'apollo_event_favorited'     => __('Favoritar evento', 'apollo-membership'),
		'apollo_page_favorited'      => __('Favoritar página', 'apollo-membership'),
		'apollo_depoiment_submitted' => __('Enviar depoimento', 'apollo-membership'),
		'apollo_reaction_added'      => __('Adicionar reação', 'apollo-membership'),
		'apollo_hours_online'        => __('Horas online', 'apollo-membership'),
		'apollo_interaction_logged'  => __('Interação na plataforma', 'apollo-membership'),
		'apollo_event_created'       => __('Criar evento', 'apollo-membership'),
		'apollo_event_attended'      => __('Participar de evento', 'apollo-membership'),
		'apollo_classified_posted'   => __('Publicar classificado', 'apollo-membership'),
		'apollo_group_joined'        => __('Entrar em grupo', 'apollo-membership'),
	);

	return (array) apply_filters('apollo_point_award_triggers', $triggers);
}

/**
 * Get triggers that can deduct points
 *
 * @return array trigger_hook => label
 */
function apollo_get_point_deduct_triggers(): array
{
	$triggers = array(
		'delete_comment'            => __('Deletar comentário', 'apollo-membership'),
		'apollo_content_reported'   => __('Conteúdo denunciado', 'apollo-membership'),
		'apollo_inactivity_penalty' => __('Penalidade por inatividade', 'apollo-membership'),
	);

	return (array) apply_filters('apollo_point_deduct_triggers', $triggers);
}

// ═══════════════════════════════════════════════════════════════════════════
// DEFAULT POINT VALUES — for initial configuration
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get default point values for triggers
 * Used during activation and settings page
 *
 * @return array trigger => points
 */
function apollo_get_default_trigger_points(): array
{
	return array(
		'wp_login'                   => 5,
		'comment_post'               => 10,
		'publish_post'               => 20,
		'daily_visit'                => 2,
		'profile_update'             => 5,
		'apollo_social_message_sent' => 3,
		'apollo_event_favorited'     => 5,
		'apollo_page_favorited'      => 5,
		'apollo_depoiment_submitted' => 15,
		'apollo_reaction_added'      => 2,
		'apollo_hours_online'        => 1,
		'apollo_interaction_logged'  => 1,
		'apollo_event_created'       => 25,
		'apollo_event_attended'      => 10,
		'apollo_classified_posted'   => 15,
		'apollo_group_joined'        => 10,
		// Deductions
		'delete_comment'             => -5,
		'apollo_content_reported'    => -10,
		'apollo_inactivity_penalty'  => -3,
	);
}

/**
 * Seed default point values if not yet set
 * Called on activation
 */
function apollo_seed_default_trigger_points(): void
{
	$existing = get_option('apollo_membership_trigger_points', array());

	if (! empty($existing)) {
		return;
	}

	$defaults = apollo_get_default_trigger_points();

	// Add cooldown defaults (seconds)
	$cooldowns = array(
		'wp_login_cooldown'                  => 3600,     // 1 hour
		'comment_post_cooldown'              => 60,       // 1 minute
		'apollo_reaction_added_cooldown'     => 30,       // 30 seconds
		'apollo_hours_online_cooldown'       => 3600,     // 1 hour
		'apollo_interaction_logged_cooldown' => 300,      // 5 minutes
	);

	update_option('apollo_membership_trigger_points', array_merge($defaults, $cooldowns));
}

// ═══════════════════════════════════════════════════════════════════════════
// HOURS ONLINE TRACKER — cron-based pseudo-trigger
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Track user's online time using heartbeat-style tracking
 * Records last activity time, calculates hours
 */
function apollo_track_user_online_time(): void
{
	// Apollo Statistics is the canonical owner of online-time accumulation.
	if (defined('APOLLO_STATISTICS_VERSION')) {
		return;
	}

	$user_id = get_current_user_id();
	if (! $user_id) {
		return;
	}

	$now        = time();
	$last_seen  = (int) get_user_meta($user_id, '_apollo_last_seen', true);
	$total_mins = (int) get_user_meta($user_id, '_apollo_online_minutes', true);

	// Update last seen
	update_user_meta($user_id, '_apollo_last_seen', $now);

	// Only count if last seen within 15 minutes (active session)
	if ($last_seen > 0 && ($now - $last_seen) <= 900) {
		$elapsed_mins = (int) (($now - $last_seen) / 60);

		if ($elapsed_mins > 0) {
			$new_total = $total_mins + $elapsed_mins;
			update_user_meta($user_id, '_apollo_online_minutes', $new_total);

			// Award points every 60 accumulated minutes
			$hours_before = (int) ($total_mins / 60);
			$hours_after  = (int) ($new_total / 60);

			if ($hours_after > $hours_before) {
				$hours_gained = $hours_after - $hours_before;
				for ($i = 0; $i < $hours_gained; $i++) {
					apollo_trigger_event_for_user($user_id, 'apollo_hours_online');
				}
			}
		}
	}
}
add_action('wp_head', 'apollo_track_user_online_time', 20);
add_action('wp_ajax_heartbeat', 'apollo_track_user_online_time', 5);
