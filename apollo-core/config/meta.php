<?php
/**
 * ⚠ NOT THE REGISTRAR — read this before adding a key here.
 *
 * PHASE 1.3 · 2026-08-11. This file is a LOOKUP TABLE consumed by
 * Apollo\Core\Config\ApolloMeta::for_cpt() / ::user_keys() / ::definition().
 * It does NOT register anything with WordPress.
 *
 * The registrar is src/Core/MetaRegistry.php::load_definitions(), which holds its
 * own inline $post_meta array and is the only thing that calls register_post_meta().
 * A key added here and not there is invisible to WordPress: no REST field, no
 * sanitize_callback, no auth_callback. It will still store and read through
 * get/update_post_meta(), which is exactly why the omission is easy to miss.
 *
 * The two sets are already out of sync — this file carries 11 `dj` and 13 `local`
 * keys against MetaRegistry's 38 and 27. It was almost deleted during this pass on
 * the assumption it was dead; it is reachable, just unused (ApolloMeta:: has no
 * call sites outside its own docblock).
 *
 * TODO (own commit): make ApolloMeta read from MetaRegistry::get_post_meta_definitions()
 * and retire this file. One schema, or the drift returns.
 *
 * Apollo Ecosystem — Meta Key Definitions
 *
 * ALL meta keys across the entire ecosystem in one place.
 * Eliminates magic strings — use ApolloMeta::EVENT_START_DATE instead.
 *
 * Structure: object_type => [ group => [ key => definition ] ]
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
	 * POST META — Organized by CPT
	 * ═══════════════════════════════════════════════════════════════════ */
	'post' => array(

		/* ─── EVENT ─────────────────────────────────────────────── */
		'event'       => array(
			'_event_start_date'   => array(
				'type'   => 'string',
				'format' => 'Y-m-d',
				'rest'   => true,
			),
			'_event_end_date'     => array(
				'type'   => 'string',
				'format' => 'Y-m-d',
				'rest'   => true,
			),
			'_event_start_time'   => array(
				'type'   => 'string',
				'format' => 'H:i',
				'rest'   => true,
			),
			'_event_end_time'     => array(
				'type'   => 'string',
				'format' => 'H:i',
				'rest'   => true,
			),
			'_event_dj_ids'       => array(
				'type'  => 'array',
				'items' => 'int',
				'rest'  => true,
			),
			'_event_dj_slots'     => array(
				'type'  => 'array',
				'items' => 'object',
				'rest'  => true,
			),
			'_event_loc_id'       => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_event_banner'       => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_event_ticket_url'   => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
			'_event_ticket_price' => array(
				'type' => 'string',
				'rest' => true,
			),
			'_event_privacy'      => array(
				'type' => 'string',
				'enum' => array( 'public', 'private', 'invite' ),
				'rest' => true,
			),
			'_event_status'       => array(
				'type' => 'string',
				'enum' => array( 'scheduled', 'cancelled', 'postponed', 'ongoing', 'finished' ),
				'rest' => true,
			),
			'_event_is_gone'      => array(
				'type' => 'string',
				'rest' => true,
			),
			'_event_budget'       => array(
				'type' => 'number',
				'rest' => false,
			),
			'_event_video_url'    => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
			'_event_gallery'      => array(
				'type'  => 'array',
				'items' => 'int',
				'rest'  => true,
			),
			'_event_coupon_code'  => array(
				'type' => 'string',
				'rest' => true,
			),
			'_event_list_url'     => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
		),

		/* ─── DJ ────────────────────────────────────────────────── */
		'dj'          => array(
			'_dj_image'      => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_dj_banner'     => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_dj_website'    => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
			'_dj_instagram'  => array(
				'type' => 'string',
				'rest' => true,
			),
			'_dj_soundcloud' => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
			'_dj_spotify'    => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
			'_dj_youtube'    => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
			'_dj_mixcloud'   => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
			'_dj_user_id'    => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_dj_verified'   => array(
				'type' => 'boolean',
				'rest' => true,
			),
			'_dj_bio_short'  => array(
				'type' => 'string',
				'max'  => 280,
				'rest' => true,
			),
		),

		/* ─── LOCAL ─────────────────────────────────────────────── */
		'local'       => array(
			'_local_name'        => array(
				'type' => 'string',
				'rest' => true,
			),
			'_local_address'     => array(
				'type' => 'string',
				'rest' => true,
			),
			'_local_city'        => array(
				'type' => 'string',
				'rest' => true,
			),
			'_local_state'       => array(
				'type' => 'string',
				'rest' => true,
			),
			'_local_country'     => array(
				'type'    => 'string',
				'default' => 'Brasil',
				'rest'    => true,
			),
			'_local_postal'      => array(
				'type' => 'string',
				'rest' => true,
			),
			'_local_lat'         => array(
				'type' => 'number',
				'rest' => true,
			),
			'_local_lng'         => array(
				'type' => 'number',
				'rest' => true,
			),
			'_local_phone'       => array(
				'type' => 'string',
				'rest' => true,
			),
			'_local_website'     => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
			'_local_instagram'   => array(
				'type' => 'string',
				'rest' => true,
			),
			'_local_capacity'    => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_local_price_range' => array(
				'type' => 'string',
				'enum' => array( '$', '$$', '$$$', '$$$$' ),
				'rest' => true,
			),
		),

		/* ─── CLASSIFIED ────────────────────────────────────────── */
		'classified'  => array(
			'_classified_price'            => array(
				'type' => 'number',
				'rest' => true,
			),
			'_classified_currency'         => array(
				'type'    => 'string',
				'default' => 'BRL',
				'rest'    => true,
			),
			'_classified_negotiable'       => array(
				'type' => 'boolean',
				'rest' => true,
			),
			'_classified_condition'        => array(
				'type' => 'string',
				'enum' => array( 'novo', 'usado', 'recondicionado' ),
				'rest' => true,
			),
			'_classified_loc'              => array(
				'type' => 'string',
				'rest' => true,
			),
			'_classified_contact_phone'    => array(
				'type' => 'string',
				'rest' => true,
			),
			'_classified_contact_whatsapp' => array(
				'type' => 'string',
				'rest' => true,
			),
			'_classified_expires_at'       => array(
				'type'   => 'string',
				'format' => 'Y-m-d',
				'rest'   => true,
			),
			'_classified_featured'         => array(
				'type' => 'boolean',
				'rest' => true,
			),
		),

		/* ─── SUPPLIER ──────────────────────────────────────────── */
		'supplier'    => array(
			'_supplier_company'       => array(
				'type' => 'string',
				'rest' => true,
			),
			'_supplier_cnpj'          => array(
				'type' => 'string',
				'rest' => true,
			),
			'_supplier_contact_name'  => array(
				'type' => 'string',
				'rest' => true,
			),
			'_supplier_contact_email' => array(
				'type'   => 'string',
				'format' => 'email',
				'rest'   => true,
			),
			'_supplier_contact_phone' => array(
				'type' => 'string',
				'rest' => true,
			),
			'_supplier_website'       => array(
				'type'   => 'string',
				'format' => 'url',
				'rest'   => true,
			),
			'_supplier_address'       => array(
				'type' => 'string',
				'rest' => true,
			),
			'_supplier_verified'      => array(
				'type' => 'boolean',
				'rest' => true,
			),
			'_supplier_rating'        => array(
				'type' => 'number',
				'min'  => 0,
				'max'  => 5,
				'rest' => true,
			),
		),

		/* ─── DOC ───────────────────────────────────────────────── */
		'doc'         => array(
			'_doc_file_id'   => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_doc_folder_id' => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_doc_access'    => array(
				'type' => 'string',
				'enum' => array( 'public', 'private', 'group', 'industry' ),
				'rest' => true,
			),
			'_doc_version'   => array(
				'type' => 'string',
				'rest' => true,
			),
			'_doc_downloads' => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_doc_status'    => array(
				'type' => 'string',
				'enum' => array( 'draft', 'locked', 'finalized', 'signed' ),
				'rest' => true,
			),
			'_doc_checksum'  => array(
				'type' => 'string',
				'rest' => false,
			),
			'_doc_cpf'       => array(
				'type' => 'string',
				'rest' => false,
			),
		),

		/* ─── HUB ───────────────────────────────────────────────── */
		'hub'         => array(
			'_hub_bio'        => array(
				'type' => 'string',
				'max'  => 280,
				'rest' => true,
			),
			'_hub_links'      => array(
				'type' => 'array',
				'rest' => true,
			),
			'_hub_socials'    => array(
				'type' => 'array',
				'rest' => true,
			),
			'_hub_theme'      => array(
				'type' => 'string',
				'rest' => true,
			),
			'_hub_avatar'     => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_hub_cover'      => array(
				'type' => 'integer',
				'rest' => true,
			),
			'_hub_custom_css' => array(
				'type' => 'string',
				'rest' => false,
			),
		),

		/* ─── EMAIL ─────────────────────────────────────────────── */
		'email_aprio' => array(
			'_email_subject'   => array(
				'type' => 'string',
				'rest' => true,
			),
			'_email_type'      => array(
				'type' => 'string',
				'enum' => array( 'transactional', 'marketing', 'digest' ),
				'rest' => true,
			),
			'_email_variables' => array(
				'type' => 'array',
				'rest' => true,
			),
		),

		/* ─── CROSS-CPT (any post type) ─────────────────────────── */
		'_global'     => array(
			'_fav_count'       => array(
				'type'    => 'integer',
				'default' => 0,
				'rest'    => true,
			),
			'_wow_count'       => array(
				'type'    => 'integer',
				'default' => 0,
				'rest'    => true,
			),
			'_wow_counts'      => array(
				'type' => 'object',
				'rest' => true,
			),
			'_coauthors'       => array(
				'type'  => 'array',
				'items' => 'int',
				'rest'  => true,
			),
			'_mod_status'      => array(
				'type' => 'string',
				'enum' => array( 'pending', 'approved', 'rejected', 'flagged' ),
				'rest' => false,
			),
			'_mod_notes'       => array(
				'type' => 'string',
				'rest' => false,
			),
			'_mod_reviewed_by' => array(
				'type' => 'integer',
				'rest' => false,
			),
			'_mod_reviewed_at' => array(
				'type'   => 'string',
				'format' => 'datetime',
				'rest'   => false,
			),
			'_apollo_seo'      => array(
				'type' => 'array',
				'rest' => false,
			),
		),

		/* ─── PAGE META (apollo-templates) ──────────────────────── */
		'page'        => array(
			'_apollo_template'    => array(
				'type' => 'string',
				'rest' => true,
			),
			'_apollo_canvas_data' => array(
				'type' => 'array',
				'rest' => false,
			),
		),
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * USER META
	 * ═══════════════════════════════════════════════════════════════════ */
	'user' => array(

		/* ─── Login / Registration ──────────────────────────────── */
		'login'         => array(
			'_apollo_social_name'            => array(
				'type'     => 'string',
				'required' => true,
			),
			'_apollo_instagram'              => array(
				'type'     => 'string',
				'required' => true,
			),
			'_apollo_avatar_url'             => array( 'type' => 'string' ),
			'_apollo_avatar_attachment_id'   => array( 'type' => 'integer' ),
			'avatar_thumb'                   => array( 'type' => 'string' ),
			'_apollo_sound_preferences'      => array(
				'type'     => 'array',
				'items'    => 'int',
				'taxonomy' => 'sound',
				'required' => true,
			),
			'_apollo_quiz_score'             => array( 'type' => 'integer' ),
			'_apollo_simon_highscore'        => array( 'type' => 'integer' ),
			'_apollo_quiz_answers'           => array( 'type' => 'array' ),
			'_apollo_email_verified'         => array( 'type' => 'boolean' ),
			'_apollo_verification_token'     => array( 'type' => 'string' ),
			'_apollo_password_reset_token'   => array( 'type' => 'string' ),
			'_apollo_password_reset_expires' => array( 'type' => 'integer' ),
			'_apollo_login_attempts'         => array( 'type' => 'integer' ),
			'_apollo_last_login'             => array( 'type' => 'string' ),
			'_apollo_lockout_until'          => array( 'type' => 'integer' ),
		),

		/* ─── Profile ───────────────────────────────────────────── */
		'profile'       => array(
			'_apollo_user_verified'      => array( 'type' => 'boolean' ),
			'_apollo_membership'         => array(
				'type'    => 'array',
				'items'   => 'string',
				'enum'    => array(), // Populated at runtime from MembershipRegistry in MetaRegistry.
				'rest'    => true,
				'default' => array( 'nao-verificado' ),
			),
			'_apollo_agent_for'          => array(
				'type'        => 'array',
				'items'       => 'string',
				'enum'        => array(),
				'rest'        => false,
				'default'     => array(),
				'manage_only' => true,
			),
			'_apollo_profile_completed'  => array( 'type' => 'integer' ),
			'_apollo_matchmaking_data'   => array( 'type' => 'array' ),
			'cover_image'                => array( 'type' => 'integer' ),
			'custom_avatar'              => array( 'type' => 'integer' ),
			'instagram'                  => array( 'type' => 'string' ),
			'user_location'              => array( 'type' => 'string' ),
			'_apollo_bio'                => array(
				'type' => 'string',
				'max'  => 500,
			),
			'_apollo_website'            => array(
				'type'   => 'string',
				'format' => 'url',
			),
			'_apollo_phone'              => array( 'type' => 'string' ),
			'_apollo_birth_date'         => array(
				'type'   => 'string',
				'format' => 'Y-m-d',
			),
			'_apollo_privacy_profile'    => array(
				'type'    => 'string',
				'enum'    => array( 'public', 'members', 'private' ),
				'default' => 'public',
			),
			'_apollo_privacy_email'      => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'_apollo_disable_author_url' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'_apollo_profile_views'      => array(
				'type'    => 'integer',
				'default' => 0,
			),
		),

		/* ─── Social (Party Model: no ego counters) ────────────── */

		/* ─── Chat ──────────────────────────────────────────────── */
		'chat'          => array(
			'_apollo_chat_status'        => array(
				'type'    => 'string',
				'enum'    => array( 'online', 'away', 'busy', 'offline' ),
				'default' => 'offline',
			),
			'_apollo_chat_last_seen'     => array( 'type' => 'integer' ),
			'_apollo_chat_preferences'   => array( 'type' => 'array' ),
			'_apollo_chat_blocked_users' => array(
				'type'  => 'array',
				'items' => 'int',
			),
			'_apollo_chat_muted_threads' => array(
				'type'  => 'array',
				'items' => 'int',
			),
		),

		/* ─── Notifications / Email ─────────────────────────────── */
		'notifications' => array(
			'_apollo_notif_prefs'  => array( 'type' => 'array' ),
			'_apollo_notif_unread' => array(
				'type'    => 'integer',
				'default' => 0,
			),
			'_apollo_email_prefs'  => array( 'type' => 'array' ),
		),

		/* ─── Gamification ──────────────────────────────────────── */
		'membership'    => array(
			'_apollo_triggered_triggers'  => array( 'type' => 'array' ),
			'_apollo_can_notify_user'     => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'_apollo_active_achievements' => array( 'type' => 'array' ),
			'_apollo_achievement_count'   => array(
				'type'    => 'integer',
				'default' => 0,
			),
			'_apollo_points_total'        => array(
				'type'    => 'integer',
				'default' => 0,
			),
			'_apollo_current_rank'        => array( 'type' => 'string' ),
			'_apollo_rank_entry_id'       => array( 'type' => 'integer' ),
		),

		/* ─── Industry ──────────────────────────────────────────── */
		'cena'          => array(
			'_apollo_cena_access' => array( 'type' => 'boolean' ),
			'_apollo_cena_role'   => array(
				'type' => 'string',
				'enum' => array( 'member', 'verified', 'admin' ),
			),
		),

		/* ─── Dashboard ─────────────────────────────────────────── */
		'dashboard'     => array(
			'_apollo_dashboard_layout' => array( 'type' => 'array' ),
		),
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * TERM META
	 * ═══════════════════════════════════════════════════════════════════ */
	'term' => array(
		'seo' => array(
			'_apollo_seo_term' => array( 'type' => 'array' ),
		),
	),
);
