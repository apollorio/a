<?php

/**
 * Apollo Ecosystem — Hook Definitions
 *
 * ALL cross-plugin hooks (actions + filters) defined centrally.
 * Pattern: apollo/{plugin}/{action}
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
	 * ACTIONS — do_action()
	 * ═══════════════════════════════════════════════════════════════════ */
	'actions' => array(

		/* ─── Core lifecycle ────────────────────────────────────── */
		'apollo/core/initialized'              => array(
			'params'      => array( 'info' ),
			'fired_by'    => 'apollo-core',
			'description' => 'Core finished bootstrapping',
		),
		'apollo/cpts/registered'               => array(
			'params'      => array( 'registered' ),
			'fired_by'    => 'apollo-core',
			'description' => 'All CPTs registered (fallback included)',
		),

		/* ─── Auth ──────────────────────────────────────────────── */
		'apollo/login/registered'              => array(
			'params'      => array( 'user_id' ),
			'fired_by'    => 'apollo-login',
			'description' => 'New user registered',
		),
		'apollo/login/authenticated'           => array(
			'params'      => array( 'user_id' ),
			'fired_by'    => 'apollo-login',
			'description' => 'User logged in',
		),
		'apollo/login/failed'                  => array(
			'params'      => array( 'username' ),
			'fired_by'    => 'apollo-login',
			'description' => 'Login failed',
		),
		'apollo/login/locked_out'              => array(
			'params'      => array( 'user_id' ),
			'fired_by'    => 'apollo-login',
			'description' => 'Account locked',
		),
		'apollo/login/verification_email'        => array(
			'params'      => array( 'user_id', 'verify_url' ),
			'fired_by'    => 'apollo-login',
			'description' => 'Request registration verification email dispatch',
		),
		'apollo/login/email_verified'            => array(
			'params'      => array( 'user_id' ),
			'fired_by'    => 'apollo-login',
			'description' => 'User confirmed email via verification token',
		),

		/* ─── Content ───────────────────────────────────────────── */
		'apollo/event/created'                 => array(
			'params'      => array( 'post_id' ),
			'fired_by'    => 'apollo-events',
			'description' => 'Event created',
		),
		'apollo/event/updated'                 => array(
			'params'      => array( 'post_id' ),
			'fired_by'    => 'apollo-events',
			'description' => 'Event updated',
		),
		'apollo/event/deleted'                 => array(
			'params'      => array( 'post_id' ),
			'fired_by'    => 'apollo-events',
			'description' => 'Event deleted',
		),
		'apollo/event/expired'                 => array(
			'params'      => array( 'post_id' ),
			'fired_by'    => 'apollo-events',
			'description' => 'Event expired (_event_is_gone)',
		),

		'apollo/dj/created'                    => array(
			'params'      => array( 'post_id' ),
			'fired_by'    => 'apollo-djs',
			'description' => 'DJ created',
		),
		'apollo/dj/updated'                    => array(
			'params'      => array( 'post_id' ),
			'fired_by'    => 'apollo-djs',
			'description' => 'DJ updated',
		),

		'apollo/classified/created'            => array(
			'params'      => array( 'post_id' ),
			'fired_by'    => 'apollo-adverts',
			'description' => 'Classified created',
		),
		'apollo/classified/expired'            => array(
			'params'      => array( 'post_id' ),
			'fired_by'    => 'apollo-adverts',
			'description' => 'Classified expired',
		),

		/* ─── Social ────────────────────────────────────────────── */
		'apollo/social/post_created'           => array(
			'params'      => array( 'activity_id' ),
			'fired_by'    => 'apollo-social',
			'description' => 'Feed post created',
		),
		'apollo/social/new_user'               => array(
			'params'   => array( 'user_id' ),
			'fired_by' => 'apollo-social',
			'description' => 'New user joined the community (party model — auto-connected)',
		),

		'apollo/fav/added'                     => array(
			'params'   => array( 'user_id', 'post_id' ),
			'fired_by' => 'apollo-fav',
		),
		'apollo/fav/removed'                   => array(
			'params'   => array( 'user_id', 'post_id' ),
			'fired_by' => 'apollo-fav',
		),

		'apollo/wow/added'                     => array(
			'params'   => array( 'user_id', 'post_id', 'type' ),
			'fired_by' => 'apollo-wow',
		),

		'apollo/group/joined'                  => array(
			'params'   => array( 'user_id', 'group_id' ),
			'fired_by' => 'apollo-groups',
		),
		'apollo/group/left'                    => array(
			'params'   => array( 'user_id', 'group_id' ),
			'fired_by' => 'apollo-groups',
		),

		/* ─── Communication ─────────────────────────────────────── */
		'apollo/notif/created'                 => array(
			'params'      => array( 'notif_id' ),
			'fired_by'    => 'apollo-notif',
			'description' => 'Notification created',
		),
		'apollo/notif/digest'                  => array(
			'params'      => array( 'user_id', 'notifications' ),
			'fired_by'    => 'apollo-notif',
			'description' => 'Legacy digest payload dispatcher',
		),
		'apollo/email/digest/notifications'    => array(
			'params'      => array( 'user_id', 'items' ),
			'fired_by'    => 'apollo-notif',
			'description' => 'Segmented digest: notifications',
		),
		'apollo/email/digest/fav_events'       => array(
			'params'      => array( 'user_id', 'items' ),
			'fired_by'    => 'apollo-notif',
			'description' => 'Segmented digest: favorited event updates',
		),
		'apollo/email/digest/event_match'      => array(
			'params'      => array( 'user_id', 'items' ),
			'fired_by'    => 'apollo-notif',
			'description' => 'Segmented digest: event sound matching',
		),
		'apollo/email/digest/chat'             => array(
			'params'      => array( 'user_id', 'items' ),
			'fired_by'    => 'apollo-notif',
			'description' => 'Segmented digest: chat',
		),
		'apollo/email/digest/comuna'           => array(
			'params'      => array( 'user_id', 'items' ),
			'fired_by'    => 'apollo-notif',
			'description' => 'Segmented digest: comuna',
		),
		'apollo/email/digest/news'             => array(
			'params'      => array( 'user_id', 'items' ),
			'fired_by'    => 'apollo-notif',
			'description' => 'Segmented digest: apollo news',
		),
		'apollo/email/digest/social'           => array(
			'params'      => array( 'user_id', 'items' ),
			'fired_by'    => 'apollo-notif',
			'description' => 'Segmented digest: social profile/reaction updates',
		),
		'apollo/email/sent'                    => array(
			'params'      => array( 'email_id' ),
			'fired_by'    => 'apollo-email',
			'description' => 'Email sent',
		),
		'apollo/email/failed'                  => array(
			'params'      => array( 'email_id' ),
			'fired_by'    => 'apollo-email',
			'description' => 'Email failed',
		),
		'apollo/chat/message_sent'             => array(
			'params'   => array( 'msg_id', 'thread_id' ),
			'fired_by' => 'apollo-chat',
		),

		/* ─── Documents ─────────────────────────────────────────── */
		'apollo/docs/created'                  => array(
			'params'      => array( 'doc_id' ),
			'fired_by'    => 'apollo-docs',
			'description' => 'Document created',
		),
		'apollo/docs/updated'                  => array(
			'params'      => array( 'doc_id' ),
			'fired_by'    => 'apollo-docs',
			'description' => 'Document updated',
		),
		'apollo/docs/locked'                   => array(
			'params'      => array( 'doc_id' ),
			'fired_by'    => 'apollo-docs',
			'description' => 'Document locked',
		),
		'apollo/docs/finalized'                => array(
			'params'      => array( 'doc_id' ),
			'fired_by'    => 'apollo-docs',
			'description' => 'Document finalized → triggers sign',
		),
		'apollo/docs/downloaded'               => array(
			'params'   => array( 'doc_id', 'user_id' ),
			'fired_by' => 'apollo-docs',
		),

		'apollo/sign/created'                  => array(
			'params'      => array( 'sig_id' ),
			'fired_by'    => 'apollo-sign',
			'description' => 'Signature request created',
		),
		'apollo/sign/signed'                   => array(
			'params'      => array( 'sig_id' ),
			'fired_by'    => 'apollo-sign',
			'description' => 'Document signed with certificate',
		),
		'apollo/sign/revoked'                  => array(
			'params'      => array( 'sig_id' ),
			'fired_by'    => 'apollo-sign',
			'description' => 'Signature revoked',
		),

		/* ─── Gamification ──────────────────────────────────────── */
		'apollo/membership/achievement_earned' => array(
			'params'   => array( 'user_id', 'achievement_id' ),
			'fired_by' => 'apollo-membership',
		),
		'apollo/membership/points_awarded'     => array(
			'params'   => array( 'user_id', 'points' ),
			'fired_by' => 'apollo-membership',
		),
		'apollo/membership/rank_changed'       => array(
			'params'   => array( 'user_id', 'rank_id' ),
			'fired_by' => 'apollo-membership',
		),

		/* ─── Moderation ────────────────────────────────────────── */
		'apollo/mod/approved'                  => array(
			'params'   => array( 'post_id' ),
			'fired_by' => 'apollo-mod',
		),
		'apollo/mod/rejected'                  => array(
			'params'   => array( 'post_id' ),
			'fired_by' => 'apollo-mod',
		),
		'apollo/mod/flagged'                   => array(
			'params'   => array( 'post_id' ),
			'fired_by' => 'apollo-mod',
		),
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * FILTERS — apply_filters()
	 * ═══════════════════════════════════════════════════════════════════ */
	'filters' => array(

		'apollo/registry/data'                    => array(
			'params'      => array( 'data' ),
			'description' => 'Filter registry data after JSON decode',
		),
		'apollo/cdn/url'                          => array(
			'params'      => array( 'url' ),
			'description' => 'Filter CDN URL',
		),
		'apollo/docs/can_access'                  => array(
			'params'      => array( 'access', 'doc_id', 'user_id' ),
			'description' => 'Filter document access check',
		),
		'apollo/event/query_args'                 => array(
			'params'      => array( 'args' ),
			'description' => 'Filter event listing query',
		),
		'apollo/social/feed_query'                => array(
			'params'      => array( 'args' ),
			'description' => 'Filter social feed query',
		),
		'apollo/user/profile_fields'              => array(
			'params'      => array( 'fields', 'user_id' ),
			'description' => 'Filter visible profile fields',
		),
		'apollo/seo/meta_tags'                    => array(
			'params'      => array( 'tags', 'context' ),
			'description' => 'Filter SEO meta tags',
		),
		'apollo/email/template_vars'              => array(
			'params'      => array( 'vars', 'template_id' ),
			'description' => 'Filter email template variables',
		),
		'apollo/notif/digest/classify_segments'   => array(
			'params'      => array( 'segments', 'notification' ),
			'description' => 'Filter digest segment classifier for a notification',
		),
		'apollo/notif/digest/notifications_items' => array(
			'params'      => array( 'items', 'user_id' ),
			'description' => 'Filter digest notifications (unclassified) segment items',
		),
		'apollo/notif/digest/fav_events_items'    => array(
			'params'      => array( 'items', 'user_id' ),
			'description' => 'Filter digest fav events segment items',
		),
		'apollo/notif/digest/event_match_items'   => array(
			'params'      => array( 'items', 'user_id' ),
			'description' => 'Filter digest event match segment items',
		),
		'apollo/notif/digest/chat_items'          => array(
			'params'      => array( 'items', 'user_id' ),
			'description' => 'Filter digest chat segment items',
		),
		'apollo/notif/digest/comuna_items'        => array(
			'params'      => array( 'items', 'user_id' ),
			'description' => 'Filter digest comuna segment items',
		),
		'apollo/notif/digest/news_items'          => array(
			'params'      => array( 'items', 'user_id' ),
			'description' => 'Filter digest news segment items',
		),
		'apollo/notif/digest/social_items'        => array(
			'params'      => array( 'items', 'user_id' ),
			'description' => 'Filter digest social segment items',
		),
		'apollo/chat/can_message'                 => array(
			'params'      => array( 'can', 'sender_id', 'recipient_id' ),
			'description' => 'Filter chat permission',
		),
	),
);
