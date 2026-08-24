<?php
/**
 * Apollo Ecosystem — Meta Key Constants
 *
 * Typed constants for ALL meta keys (post, user, term).
 * Eliminates magic strings — use ApolloMeta::EVENT_START_DATE instead of '_event_start_date'.
 *
 * Usage:
 *   use Apollo\Core\Config\ApolloMeta;
 *   update_post_meta($post_id, ApolloMeta::EVENT_START_DATE, $value);
 *   $avatar = get_user_meta($user_id, ApolloMeta::USER_AVATAR_URL, true);
 *
 * @package Apollo\Core\Config
 * @since   6.1.0
 */

declare(strict_types=1);

namespace Apollo\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ApolloMeta {

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST META — EVENT
	 * ═══════════════════════════════════════════════════════════════════ */

	public const EVENT_START_DATE   = '_event_start_date';
	public const EVENT_END_DATE     = '_event_end_date';
	public const EVENT_START_TIME   = '_event_start_time';
	public const EVENT_END_TIME     = '_event_end_time';
	public const EVENT_DJ_IDS       = '_event_dj_ids';
	public const EVENT_DJ_SLOTS     = '_event_dj_slots';
	public const EVENT_LOC_ID       = '_event_loc_id';
	public const EVENT_BANNER       = '_event_banner';
	public const EVENT_TICKET_URL   = '_event_ticket_url';
	public const EVENT_TICKET_PRICE = '_event_ticket_price';
	public const EVENT_PRIVACY      = '_event_privacy';
	public const EVENT_STATUS       = '_event_status';
	public const EVENT_IS_GONE      = '_event_is_gone';
	public const EVENT_BUDGET       = '_event_budget';

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST META — DJ
	 * ═══════════════════════════════════════════════════════════════════ */

	public const DJ_IMAGE      = '_dj_image';
	public const DJ_BANNER     = '_dj_banner';
	public const DJ_WEBSITE    = '_dj_website';
	public const DJ_INSTAGRAM  = '_dj_instagram';
	public const DJ_SOUNDCLOUD = '_dj_soundcloud';
	public const DJ_SPOTIFY    = '_dj_spotify';
	public const DJ_YOUTUBE    = '_dj_youtube';
	public const DJ_MIXCLOUD   = '_dj_mixcloud';
	public const DJ_USER_ID    = '_dj_user_id';
	public const DJ_VERIFIED   = '_dj_verified';
	public const DJ_BIO_SHORT  = '_dj_bio_short';

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST META — LOCAL
	 * ═══════════════════════════════════════════════════════════════════ */

	public const LOCAL_NAME        = '_local_name';
	public const LOCAL_ADDRESS     = '_local_address';
	public const LOCAL_CITY        = '_local_city';
	public const LOCAL_STATE       = '_local_state';
	public const LOCAL_COUNTRY     = '_local_country';
	public const LOCAL_POSTAL      = '_local_postal';
	public const LOCAL_LAT         = '_local_lat';
	public const LOCAL_LNG         = '_local_lng';
	public const LOCAL_PHONE       = '_local_phone';
	public const LOCAL_WEBSITE     = '_local_website';
	public const LOCAL_INSTAGRAM   = '_local_instagram';
	public const LOCAL_CAPACITY    = '_local_capacity';
	public const LOCAL_PRICE_RANGE = '_local_price_range';

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST META — CLASSIFIED
	 * ═══════════════════════════════════════════════════════════════════ */

	public const CLASSIFIED_PRICE            = '_classified_price';
	public const CLASSIFIED_CURRENCY         = '_classified_currency';
	public const CLASSIFIED_NEGOTIABLE       = '_classified_negotiable';
	public const CLASSIFIED_CONDITION        = '_classified_condition';
	public const CLASSIFIED_LOCATION         = '_classified_loc';
	public const CLASSIFIED_CONTACT_PHONE    = '_classified_contact_phone';
	public const CLASSIFIED_CONTACT_WHATSAPP = '_classified_contact_whatsapp';
	public const CLASSIFIED_EXPIRES_AT       = '_classified_expires_at';
	public const CLASSIFIED_FEATURED         = '_classified_featured';

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST META — SUPPLIER
	 * ═══════════════════════════════════════════════════════════════════ */

	public const SUPPLIER_COMPANY       = '_supplier_company';
	public const SUPPLIER_CNPJ          = '_supplier_cnpj';
	public const SUPPLIER_CONTACT_NAME  = '_supplier_contact_name';
	public const SUPPLIER_CONTACT_EMAIL = '_supplier_contact_email';
	public const SUPPLIER_CONTACT_PHONE = '_supplier_contact_phone';
	public const SUPPLIER_WEBSITE       = '_supplier_website';
	public const SUPPLIER_ADDRESS       = '_supplier_address';
	public const SUPPLIER_VERIFIED      = '_supplier_verified';
	public const SUPPLIER_RATING        = '_supplier_rating';

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST META — DOC
	 * ═══════════════════════════════════════════════════════════════════ */

	public const DOC_FILE_ID   = '_doc_file_id';
	public const DOC_FOLDER_ID = '_doc_folder_id';
	public const DOC_ACCESS    = '_doc_access';
	public const DOC_VERSION   = '_doc_version';
	public const DOC_DOWNLOADS = '_doc_downloads';
	public const DOC_STATUS    = '_doc_status';
	public const DOC_CHECKSUM  = '_doc_checksum';
	public const DOC_CPF       = '_doc_cpf';

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST META — HUB
	 * ═══════════════════════════════════════════════════════════════════ */

	public const HUB_BIO        = '_hub_bio';
	public const HUB_LINKS      = '_hub_links';
	public const HUB_SOCIALS    = '_hub_socials';
	public const HUB_THEME      = '_hub_theme';
	public const HUB_AVATAR     = '_hub_avatar';
	public const HUB_COVER      = '_hub_cover';
	public const HUB_CUSTOM_CSS = '_hub_custom_css';

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST META — EMAIL TEMPLATE
	 * ═══════════════════════════════════════════════════════════════════ */

	public const EMAIL_SUBJECT   = '_email_subject';
	public const EMAIL_TYPE      = '_email_type';
	public const EMAIL_VARIABLES = '_email_variables';

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST META — CROSS-CPT (GLOBAL)
	 * ═══════════════════════════════════════════════════════════════════ */

	public const FAV_COUNT       = '_fav_count';
	public const WOW_COUNT       = '_wow_count';
	public const WOW_COUNTS      = '_wow_counts';
	public const COAUTHORS       = '_coauthors';
	public const MOD_STATUS      = '_mod_status';
	public const MOD_NOTES       = '_mod_notes';
	public const MOD_REVIEWED_BY = '_mod_reviewed_by';
	public const MOD_REVIEWED_AT = '_mod_reviewed_at';
	public const SEO_POST        = '_apollo_seo';

	/*
	═══════════════════════════════════════════════════════════════════
	 * USER META — LOGIN / REGISTRATION
	 * ═══════════════════════════════════════════════════════════════════ */

	public const USER_SOCIAL_NAME            = '_apollo_social_name';
	public const USER_INSTAGRAM              = '_apollo_instagram';
	public const USER_AVATAR_URL             = '_apollo_avatar_url';
	public const USER_AVATAR_ATTACHMENT_ID   = '_apollo_avatar_attachment_id';
	public const USER_AVATAR_THUMB           = 'avatar_thumb';
	public const USER_SOUND_PREFERENCES      = '_apollo_sound_preferences';
	public const USER_QUIZ_SCORE             = '_apollo_quiz_score';
	public const USER_SIMON_HIGHSCORE        = '_apollo_simon_highscore';
	public const USER_QUIZ_ANSWERS           = '_apollo_quiz_answers';
	public const USER_EMAIL_VERIFIED         = '_apollo_email_verified';
	public const USER_VERIFICATION_TOKEN     = '_apollo_verification_token';
	public const USER_PASSWORD_RESET_TOKEN   = '_apollo_password_reset_token';
	public const USER_PASSWORD_RESET_EXPIRES = '_apollo_password_reset_expires';
	public const USER_LOGIN_ATTEMPTS         = '_apollo_login_attempts';
	public const USER_LAST_LOGIN             = '_apollo_last_login';
	public const USER_LOCKOUT_UNTIL          = '_apollo_lockout_until';

	/*
	═══════════════════════════════════════════════════════════════════
	 * USER META — PROFILE
	 * ═══════════════════════════════════════════════════════════════════ */

	public const USER_VERIFIED          = '_apollo_user_verified';
	public const USER_MEMBERSHIP        = '_apollo_membership';
	public const USER_PROFILE_COMPLETED = '_apollo_profile_completed';
	public const USER_MATCHMAKING_DATA  = '_apollo_matchmaking_data';
	public const USER_COVER_IMAGE       = 'cover_image';
	public const USER_CUSTOM_AVATAR     = 'custom_avatar';
	public const USER_PROFILE_INSTAGRAM = 'instagram';
	public const USER_LOCATION          = 'user_location';
	public const USER_BIO               = '_apollo_bio';
	public const USER_WEBSITE           = '_apollo_website';
	public const USER_PHONE             = '_apollo_phone';
	public const USER_BIRTH_DATE        = '_apollo_birth_date';
	public const USER_PRIVACY_PROFILE   = '_apollo_privacy_profile';
	public const USER_PRIVACY_EMAIL     = '_apollo_privacy_email';
	public const USER_DISABLE_AUTHOR    = '_apollo_disable_author_url';
	public const USER_PROFILE_VIEWS     = '_apollo_profile_views';

	/*
	═══════════════════════════════════════════════════════════════════
	 * USER META — SOCIAL (Party Model: no ego counters)
	 * ═══════════════════════════════════════════════════════════════════ */

	/*
	═══════════════════════════════════════════════════════════════════
	 * USER META — CHAT
	 * ═══════════════════════════════════════════════════════════════════ */

	public const USER_CHAT_STATUS        = '_apollo_chat_status';
	public const USER_CHAT_LAST_SEEN     = '_apollo_chat_last_seen';
	public const USER_CHAT_PREFERENCES   = '_apollo_chat_preferences';
	public const USER_CHAT_BLOCKED_USERS = '_apollo_chat_blocked_users';
	public const USER_CHAT_MUTED_THREADS = '_apollo_chat_muted_threads';

	/*
	═══════════════════════════════════════════════════════════════════
	 * USER META — NOTIFICATIONS
	 * ═══════════════════════════════════════════════════════════════════ */

	public const USER_NOTIF_PREFS  = '_apollo_notif_prefs';
	public const USER_NOTIF_UNREAD = '_apollo_notif_unread';
	public const USER_EMAIL_PREFS  = '_apollo_email_prefs';

	/*
	═══════════════════════════════════════════════════════════════════
	 * USER META — GAMIFICATION / MEMBERSHIP
	 * ═══════════════════════════════════════════════════════════════════ */

	public const USER_TRIGGERED_TRIGGERS  = '_apollo_triggered_triggers';
	public const USER_CAN_NOTIFY          = '_apollo_can_notify_user';
	public const USER_ACTIVE_ACHIEVEMENTS = '_apollo_active_achievements';
	public const USER_ACHIEVEMENT_COUNT   = '_apollo_achievement_count';
	public const USER_POINTS_TOTAL        = '_apollo_points_total';
	public const USER_CURRENT_RANK        = '_apollo_current_rank';
	public const USER_RANK_ENTRY_ID       = '_apollo_rank_entry_id';

	/*
	═══════════════════════════════════════════════════════════════════
	 * USER META — INDUSTRY (CENA)
	 * ═══════════════════════════════════════════════════════════════════ */

	public const USER_CENA_ACCESS = '_apollo_cena_access';
	public const USER_CENA_ROLE   = '_apollo_cena_role';

	/*
	═══════════════════════════════════════════════════════════════════
	 * USER META — DASHBOARD
	 * ═══════════════════════════════════════════════════════════════════ */

	public const USER_DASHBOARD_LAYOUT = '_apollo_dashboard_layout';

	/*
	═══════════════════════════════════════════════════════════════════
	 * TERM META
	 * ═══════════════════════════════════════════════════════════════════ */

	public const TERM_SEO = '_apollo_seo_term';

	/*
	═══════════════════════════════════════════════════════════════════
	 * HELPERS
	 * ═══════════════════════════════════════════════════════════════════ */

	/**
	 * Get all post meta keys for a specific CPT.
	 *
	 * @return string[]
	 */
	public static function for_cpt( string $cpt ): array {
		$config = require dirname( __DIR__, 2 ) . '/config/meta.php';

		return array_keys( $config['post'][ $cpt ] ?? array() );
	}

	/**
	 * Get all user meta keys.
	 *
	 * @return string[]
	 */
	public static function user_keys(): array {
		$config = require dirname( __DIR__, 2 ) . '/config/meta.php';
		$keys   = array();

		foreach ( $config['user'] as $group => $metas ) {
			$keys = array_merge( $keys, array_keys( $metas ) );
		}

		return $keys;
	}

	/**
	 * Get the definition array for a specific meta key.
	 *
	 * @return array{type: string, format?: string, enum?: string[], rest?: bool}|null
	 */
	public static function definition( string $key ): ?array {
		$config = require dirname( __DIR__, 2 ) . '/config/meta.php';

		// Search post meta
		foreach ( $config['post'] as $group => $metas ) {
			if ( isset( $metas[ $key ] ) ) {
				return $metas[ $key ];
			}
		}

		// Search user meta
		foreach ( $config['user'] as $group => $metas ) {
			if ( isset( $metas[ $key ] ) ) {
				return $metas[ $key ];
			}
		}

		// Search term meta
		foreach ( $config['term'] as $group => $metas ) {
			if ( isset( $metas[ $key ] ) ) {
				return $metas[ $key ];
			}
		}

		return null;
	}

	/** Prevent instantiation. */
	private function __construct() {}
}
