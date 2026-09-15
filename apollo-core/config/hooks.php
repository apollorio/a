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
		/* ─── Boot, render and route ────────────────────────────────
		 * These carry the actual boot and render route and were absent from
		 * this file until 2026-09-09, which is why ApolloHook::definition()
		 * could not answer for any of them. Declaring them here is data
		 * only -- it registers nothing and fires nothing. Matching
		 * ApolloHook constants are deliberately NOT added in the same pass:
		 * that would invite a string-literal sweep across 43 plugins. */
		'apollo/canvas/head'                   => array(
			'params'      => array(),
			'fired_by'    => 'apollo-core, apollo-templates',
			'description' => 'Blank Canvas <head>. TWO producers: apollo-core/includes/document-head.php:192 and apollo-templates/includes/class-persistent-ui.php:233. wp_head() is never called on these templates, so assets must use this hook. Listeners MUST be idempotent.',
		),
		'apollo/canvas/before_close'           => array(
			'params'      => array(),
			'fired_by'    => 'apollo-core',
			'description' => 'Blank Canvas footer slot (document-head.php:294). wp_footer() is not called there.',
		),
		'apollo/plus/before_close'             => array(
			'params'      => array(),
			'fired_by'    => 'apollo-templates',
			'description' => 'Apollo+ footer slot (apollo-plus-api.php:171). apollo-ui and apollo-templates/mobile-runtime both emit here at priority 20.',
		),
		'apollo/route/matched'                 => array(
			'params'      => array( 'match', 'path' ),
			'fired_by'    => 'apollo-core',
			'description' => 'A virtual route matched (FrontRouteDispatcher.php:63, template_redirect:0). Fires ONLY on a match; absence of a match is not signalled here. Only BRAIN-01 in mu-plugin/apollo-brain.php listens.',
		),
		'apollo/ui/emit'                       => array(
			'params'      => array(),
			'fired_by'    => 'any template',
			'description' => 'Manual escape hatch for a template that reaches neither the canvas hooks nor wp_head/wp_footer. apollo-ui binds head at 10 and footer at 11.',
		),

		/* ─── BRAIN (mu-plugin/apollo-brain.php) ────────────────────
		 * The brain is a must-use plugin, so it is not an apollo-* plugin and
		 * cannot require this file. Its hooks are declared here anyway: this
		 * is the ecosystem's hook dictionary, and an undeclared hook is one
		 * nobody discovers. Measured 2026-09-09: zero apollo-* plugins listen
		 * to any of them. */
		'apollo/brain/loaded'                  => array(
			'params'      => array(),
			'fired_by'    => 'mu-plugin/apollo-brain.php:490',
			'description' => 'Brain file evaluated, BEFORE bootstrap() runs. For anything needing the registry path or Core, use apollo/brain/bootstrap_complete instead.',
		),
		'apollo/brain/before_apollo_core'      => array(
			'params'      => array(),
			'fired_by'    => 'mu-plugin/apollo-brain.php:47',
			'description' => 'muplugins_loaded:-100, before apollo-core is required. BRAIN-01 resolves `method` here. Earliest Apollo hook that exists at all.',
		),
		'apollo/brain/after_apollo_core_file'  => array(
			'params'      => array(),
			'fired_by'    => 'mu-plugin/apollo-brain.php:51',
			'description' => 'apollo-core.php has been required, but apollo_core_bootstrap() has NOT run yet (that is plugins_loaded:1).',
		),
		'apollo/brain/bootstrap_complete'      => array(
			'params'      => array(),
			'fired_by'    => 'mu-plugin/apollo-brain.php:61',
			'description' => 'Registry path resolved, Core file required, asset filters registered. Still before the active_plugins loop.',
		),
		'apollo/brain/core_ready'              => array(
			'params'      => array(),
			'fired_by'    => 'mu-plugin/apollo-brain.php:249',
			'description' => 'Fires INSIDE the apollo/core/initialized listener at priority 99 -- same stage, not a later one. BRAIN-01 resolves `logged_in` here.',
		),
		'apollo/brain/page_cache_maybe_init'   => array(
			'params'      => array(),
			'fired_by'    => 'mu-plugin/apollo-brain.php:133',
			'description' => 'DORMANT BY DESIGN. Zero listeners; Apollo\Core\Cache does not exist. The cache boundary is unmapped (Phase 8). Do not wire this to invent a cache layer.',
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
		/* ─── BRAIN asset policy ────────────────────────────────────
		 * The brain defers front-end scripts in script_loader_tag
		 * (apollo-brain.php:234). These two filters are the only supported
		 * way to exempt a handle or URL fragment from that. Both had zero
		 * consumers when measured on 2026-09-09. */
		'apollo/brain/defer_protected_handles'    => array(
			'params'      => array( 'handles' ),
			'description' => 'Script handles that never receive defer. Merged with the built-in jQuery/wp-polyfill set (apollo-brain.php:211).',
		),
		'apollo/brain/defer_protected_fragments'  => array(
			'params'      => array( 'fragments' ),
			'description' => 'Substrings of handle or src that keep a script synchronous. Defaults: apollo-, apollo_, popper, lenis, gsap (apollo-brain.php:224).',
		),
	),
);
