<?php
/**
 * Apollo Ecosystem — Hook Name Constants
 *
 * Typed constants for ALL cross-plugin hooks (actions + filters).
 * Eliminates magic strings — use ApolloHook::EVENT_CREATED instead of 'apollo/event/created'.
 *
 * Usage:
 *   use Apollo\Core\Config\ApolloHook;
 *   do_action(ApolloHook::EVENT_CREATED, $post_id);
 *   add_action(ApolloHook::LOGIN_REGISTERED, [$this, 'on_register']);
 *
 * @package Apollo\Core\Config
 * @since   6.1.0
 */

declare(strict_types=1);

namespace Apollo\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ApolloHook {

	/*
	═══════════════════════════════════════════════════════════════════
	 * ACTIONS — Core Lifecycle
	 * ═══════════════════════════════════════════════════════════════════ */

	public const CORE_INITIALIZED = 'apollo/core/initialized';
	public const CPTS_REGISTERED  = 'apollo/cpts/registered';

	/*
	═══════════════════════════════════════════════════════════════════
	 * ACTIONS — Auth
	 * ═══════════════════════════════════════════════════════════════════ */

	public const LOGIN_REGISTERED    = 'apollo/login/registered';
	public const LOGIN_AUTHENTICATED = 'apollo/login/authenticated';
	public const LOGIN_FAILED        = 'apollo/login/failed';
	public const LOGIN_LOCKED_OUT    = 'apollo/login/locked_out';

	/*
	═══════════════════════════════════════════════════════════════════
	 * ACTIONS — Content (Events, DJs, Classifieds)
	 * ═══════════════════════════════════════════════════════════════════ */

	public const EVENT_CREATED = 'apollo/event/created';
	public const EVENT_UPDATED = 'apollo/event/updated';
	public const EVENT_DELETED = 'apollo/event/deleted';
	public const EVENT_EXPIRED = 'apollo/event/expired';

	public const DJ_CREATED = 'apollo/dj/created';
	public const DJ_UPDATED = 'apollo/dj/updated';

	public const CLASSIFIED_CREATED = 'apollo/classified/created';
	public const CLASSIFIED_EXPIRED = 'apollo/classified/expired';

	/*
	═══════════════════════════════════════════════════════════════════
	 * ACTIONS — Social
	 * ═══════════════════════════════════════════════════════════════════ */

	public const SOCIAL_POST_CREATED    = 'apollo/social/post_created';
	public const SOCIAL_NEW_USER        = 'apollo/social/new_user';

	public const FAV_ADDED   = 'apollo/fav/added';
	public const FAV_REMOVED = 'apollo/fav/removed';

	public const WOW_ADDED = 'apollo/wow/added';

	public const GROUP_JOINED = 'apollo/group/joined';
	public const GROUP_LEFT   = 'apollo/group/left';

	/*
	═══════════════════════════════════════════════════════════════════
	 * ACTIONS — Communication
	 * ═══════════════════════════════════════════════════════════════════ */

	public const NOTIF_CREATED     = 'apollo/notif/created';
	public const EMAIL_SENT        = 'apollo/email/sent';
	public const EMAIL_FAILED      = 'apollo/email/failed';
	public const CHAT_MESSAGE_SENT = 'apollo/chat/message_sent';

	/*
	═══════════════════════════════════════════════════════════════════
	 * ACTIONS — Documents
	 * ═══════════════════════════════════════════════════════════════════ */

	public const DOC_CREATED    = 'apollo/docs/created';
	public const DOC_UPDATED    = 'apollo/docs/updated';
	public const DOC_LOCKED     = 'apollo/docs/locked';
	public const DOC_FINALIZED  = 'apollo/docs/finalized';
	public const DOC_DOWNLOADED = 'apollo/docs/downloaded';

	public const SIGN_CREATED = 'apollo/sign/created';
	public const SIGN_SIGNED  = 'apollo/sign/signed';
	public const SIGN_REVOKED = 'apollo/sign/revoked';

	/*
	═══════════════════════════════════════════════════════════════════
	 * ACTIONS — Gamification
	 * ═══════════════════════════════════════════════════════════════════ */

	public const ACHIEVEMENT_EARNED = 'apollo/membership/achievement_earned';
	public const POINTS_AWARDED     = 'apollo/membership/points_awarded';
	public const RANK_CHANGED       = 'apollo/membership/rank_changed';

	/*
	═══════════════════════════════════════════════════════════════════
	 * ACTIONS — Moderation
	 * ═══════════════════════════════════════════════════════════════════ */

	public const MOD_APPROVED = 'apollo/mod/approved';
	public const MOD_REJECTED = 'apollo/mod/rejected';
	public const MOD_FLAGGED  = 'apollo/mod/flagged';

	/*
	═══════════════════════════════════════════════════════════════════
	 * FILTERS — apply_filters()
	 * ═══════════════════════════════════════════════════════════════════ */

	public const FILTER_REGISTRY_DATA    = 'apollo/registry/data';
	public const FILTER_CDN_URL          = 'apollo/cdn/url';
	public const FILTER_DOC_CAN_ACCESS   = 'apollo/docs/can_access';
	public const FILTER_EVENT_QUERY      = 'apollo/event/query_args';
	public const FILTER_FEED_QUERY       = 'apollo/social/feed_query';
	public const FILTER_PROFILE_FIELDS   = 'apollo/user/profile_fields';
	public const FILTER_SEO_META         = 'apollo/seo/meta_tags';
	public const FILTER_EMAIL_VARS       = 'apollo/email/template_vars';
	public const FILTER_CHAT_CAN_MESSAGE = 'apollo/chat/can_message';

	/*
	═══════════════════════════════════════════════════════════════════
	 * HELPERS
	 * ═══════════════════════════════════════════════════════════════════ */

	/**
	 * Get all action hook names.
	 *
	 * @return string[]
	 */
	public static function actions(): array {
		$config = require dirname( __DIR__, 2 ) . '/config/hooks.php';

		return array_keys( $config['actions'] ?? array() );
	}

	/**
	 * Get all filter hook names.
	 *
	 * @return string[]
	 */
	public static function filters(): array {
		$config = require dirname( __DIR__, 2 ) . '/config/hooks.php';

		return array_keys( $config['filters'] ?? array() );
	}

	/**
	 * Get the definition for a specific hook.
	 *
	 * @return array{params: string[], fired_by?: string, description?: string}|null
	 */
	public static function definition( string $hook ): ?array {
		$config = require dirname( __DIR__, 2 ) . '/config/hooks.php';

		return $config['actions'][ $hook ]
			?? $config['filters'][ $hook ]
			?? null;
	}

	/** Prevent instantiation. */
	private function __construct() {}
}
