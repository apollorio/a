<?php
/**
 * Apollo Ecosystem — Database Tables
 *
 * ALL 41+ custom tables defined centrally.
 * Used by DatabaseBuilder on activation.
 *
 * Structure: table_suffix => [ owner, columns (DDL), indexes ]
 * Full table name = {wp_prefix}apollo_{suffix}  (or {wp_prefix}{full_name})
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	/*
	═══════════════════════════════════════════════════════════════════
	 * L0 — CORE
	 * ═══════════════════════════════════════════════════════════════════ */

	'apollo_audit_log'         => array(
		'owner' => 'apollo-core',
		'group' => 'core',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * L1 — AUTH
	 * ═══════════════════════════════════════════════════════════════════ */

	'apollo_quiz_results'      => array(
		'owner' => 'apollo-login',
		'group' => 'auth',
	),
	'apollo_simon_scores'      => array(
		'owner' => 'apollo-login',
		'group' => 'auth',
	),
	'apollo_login_attempts'    => array(
		'owner' => 'apollo-login',
		'group' => 'auth',
	),
	'apollo_url_rewrites'      => array(
		'owner' => 'apollo-login',
		'group' => 'auth',
	),

	'apollo_matchmaking'       => array(
		'owner' => 'apollo-users',
		'group' => 'auth',
	),
	'apollo_user_fields'       => array(
		'owner' => 'apollo-users',
		'group' => 'auth',
	),
	'apollo_profile_views'     => array(
		'owner' => 'apollo-users',
		'group' => 'auth',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * L1 — MEMBERSHIP / GAMIFICATION
	 * ═══════════════════════════════════════════════════════════════════ */

	'apollo_achievements'      => array(
		'owner' => 'apollo-membership',
		'group' => 'membership',
	),
	'apollo_points'            => array(
		'owner' => 'apollo-membership',
		'group' => 'membership',
	),
	'apollo_ranks'             => array(
		'owner' => 'apollo-membership',
		'group' => 'membership',
	),
	'apollo_triggers'          => array(
		'owner' => 'apollo-membership',
		'group' => 'membership',
	),
	'apollo_steps'             => array(
		'owner' => 'apollo-membership',
		'group' => 'membership',
	),
	'apollo_membership_log'    => array(
		'owner' => 'apollo-membership',
		'group' => 'membership',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * L3 — SOCIAL
	 * ═══════════════════════════════════════════════════════════════════ */

	'apollo_activity'          => array(
		'owner' => 'apollo-social',
		'group' => 'social',
	),
	'apollo_connections'       => array(
		'owner' => 'apollo-social',
		'group' => 'social',
	),

	'apollo_groups'            => array(
		'owner' => 'apollo-groups',
		'group' => 'social',
	),
	'apollo_group_members'     => array(
		'owner' => 'apollo-groups',
		'group' => 'social',
	),
	'apollo_group_meta'        => array(
		'owner' => 'apollo-groups',
		'group' => 'social',
	),

	'apollo_favs'              => array(
		'owner' => 'apollo-fav',
		'group' => 'social',
	),

	'apollo_wows'              => array(
		'owner' => 'apollo-wow',
		'group' => 'social',
	),
	'apollo_wow_types'         => array(
		'owner' => 'apollo-wow',
		'group' => 'social',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * L4 — COMMUNICATION
	 * ═══════════════════════════════════════════════════════════════════ */

	'apollo_notifications'     => array(
		'owner' => 'apollo-notif',
		'group' => 'communication',
	),
	'apollo_notif_prefs'       => array(
		'owner' => 'apollo-notif',
		'group' => 'communication',
	),

	'apollo_email_queue'       => array(
		'owner' => 'apollo-email',
		'group' => 'communication',
	),
	'apollo_email_log'         => array(
		'owner' => 'apollo-email',
		'group' => 'communication',
	),

	'apollo_chat_messages'     => array(
		'owner' => 'apollo-chat',
		'group' => 'communication',
	),
	'apollo_chat_threads'      => array(
		'owner' => 'apollo-chat',
		'group' => 'communication',
	),
	'apollo_chat_participants' => array(
		'owner' => 'apollo-chat',
		'group' => 'communication',
	),
	'apollo_chat_typing'       => array(
		'owner' => 'apollo-chat',
		'group' => 'communication',
	),
	'apollo_chat_presence'     => array(
		'owner' => 'apollo-chat',
		'group' => 'communication',
	),
	'apollo_chat_blocks'       => array(
		'owner' => 'apollo-chat',
		'group' => 'communication',
	),
	'apollo_chat_attachments'  => array(
		'owner' => 'apollo-chat',
		'group' => 'communication',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * L5 — DOCUMENTS
	 * ═══════════════════════════════════════════════════════════════════ */

	'apollo_doc_versions'      => array(
		'owner' => 'apollo-docs',
		'group' => 'documents',
	),
	'apollo_doc_downloads'     => array(
		'owner' => 'apollo-docs',
		'group' => 'documents',
	),

	'apollo_signatures'        => array(
		'owner' => 'apollo-sign',
		'group' => 'documents',
	),
	'apollo_signature_audit'   => array(
		'owner' => 'apollo-sign',
		'group' => 'documents',
	),

	'apollo_gestor_tasks'      => array(
		'owner' => 'apollo-gestor',
		'group' => 'documents',
	),
	'apollo_gestor_team'       => array(
		'owner' => 'apollo-gestor',
		'group' => 'documents',
	),
	'apollo_gestor_payments'   => array(
		'owner' => 'apollo-gestor',
		'group' => 'documents',
	),
	'apollo_gestor_milestones' => array(
		'owner' => 'apollo-gestor',
		'group' => 'documents',
	),
	'apollo_gestor_activity'   => array(
		'owner' => 'apollo-gestor',
		'group' => 'documents',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * L7 — ADMIN / MODERATION / STATS
	 * ═══════════════════════════════════════════════════════════════════ */

	'apollo_mod_queue'         => array(
		'owner' => 'apollo-mod',
		'group' => 'admin',
	),
	'apollo_mod_log'           => array(
		'owner' => 'apollo-mod',
		'group' => 'admin',
	),

	'apollo_stats_events'      => array(
		'owner' => 'apollo-statistics',
		'group' => 'admin',
	),
	'apollo_stats_users'       => array(
		'owner' => 'apollo-statistics',
		'group' => 'admin',
	),
	'apollo_stats_content'     => array(
		'owner' => 'apollo-statistics',
		'group' => 'admin',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * L6 — CALENDAR
	 * ═══════════════════════════════════════════════════════════════════ */

	'apollo_user_appointments' => array(
		'owner' => 'apollo-calendar',
		'group' => 'calendar',
	),

	'apollo_holidays'          => array(
		'owner' => 'apollo-calendar',
		'group' => 'calendar',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * L8 — INDUSTRY
	 * ═══════════════════════════════════════════════════════════════════ */

	'apollo_industry_calendar' => array(
		'owner' => 'apollo-cena',
		'group' => 'industry',
	),
);
