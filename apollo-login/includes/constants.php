<?php

/**
 * Plugin Constants
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// REST API
define('APOLLO_LOGIN_REST_NAMESPACE', 'apollo/v1');

// Quiz System
define('APOLLO_LOGIN_QUIZ_STAGES', 4);
define('APOLLO_LOGIN_SIMON_LEVELS', 4);
define('APOLLO_LOGIN_REACTION_TARGETS', 4);

// Security
define('APOLLO_LOGIN_MAX_ATTEMPTS', 3);
define('APOLLO_LOGIN_LOCKOUT_DURATION', 900); // seconds (15 minutes)
define('APOLLO_LOGIN_CUSTOM_LOGIN_SLUG', 'acesso');
/**
 * All URL paths that load the same login template (canonical = APOLLO_LOGIN_CUSTOM_LOGIN_SLUG).
 *
 * @var array<int, string>
 */
define(
	'APOLLO_LOGIN_LOGIN_SLUG_ALIASES',
	array(
		'acesso',
		'access',
		'acessar',
		'entrar',
	)
);
define('APOLLO_LOGIN_CUSTOM_REGISTER_SLUG', 'registre');

// Progressive Lockout Tiers (durations in seconds)
define('APOLLO_LOCKOUT_TIER_0', 900);      // 15 minutes
define('APOLLO_LOCKOUT_TIER_1', 3600);     // 1 hour
define('APOLLO_LOCKOUT_TIER_2', 36000);    // 10 hours
define('APOLLO_LOCKOUT_TIER_3', 86400);    // 24 hours
define('APOLLO_LOCKOUT_TIER_4', 129600);   // 36 hours

// Progressive IP Rate-Limit Tiers (durations in seconds)
define('APOLLO_RATE_TIER_0', 3600);        // 1 hour
define('APOLLO_RATE_TIER_1', 36000);       // 10 hours
define('APOLLO_RATE_TIER_2', 86400);       // 24 hours
define('APOLLO_RATE_TIER_3', 129600);      // 36 hours
define('APOLLO_RATE_TIER_TTL', 172800);    // 48 hours — tier memory window

// Database Tables (without prefix)
define('APOLLO_LOGIN_TABLE_QUIZ_RESULTS', 'apollo_quiz_results');
define('APOLLO_LOGIN_TABLE_SIMON_SCORES', 'apollo_simon_scores');
define('APOLLO_LOGIN_TABLE_LOGIN_ATTEMPTS', 'apollo_login_attempts');
define('APOLLO_LOGIN_TABLE_URL_REWRITES', 'apollo_url_rewrites');
define('APOLLO_LOGIN_TABLE_APP_ACTIVITY', 'apollo_app_activity');
// User Meta Keys
define('APOLLO_META_SOCIAL_NAME', '_apollo_social_name');
define('APOLLO_META_EMAIL_VERIFIED', '_apollo_email_verified');
define('APOLLO_META_VERIFICATION_TOKEN', '_apollo_verification_token');
define('APOLLO_META_PASSWORD_RESET_TOKEN', '_apollo_password_reset_token');
define('APOLLO_META_PASSWORD_RESET_EXPIRES', '_apollo_password_reset_expires');
define('APOLLO_META_LOGIN_ATTEMPTS', '_apollo_login_attempts');
define('APOLLO_META_LAST_LOGIN', '_apollo_last_login');
define('APOLLO_META_LOCKOUT_UNTIL', '_apollo_lockout_until');
define('APOLLO_META_LOCKOUT_TIER', '_apollo_lockout_tier');
define('APOLLO_META_BIRTH_DATE', '_apollo_birth_date');
define('APOLLO_META_ZODIAC_SIGN', '_apollo_zodiac_sign');
define('APOLLO_META_PARTY_ROLE', '_apollo_party_role');
define('APOLLO_META_QUIZ_SCORE', '_apollo_quiz_score');
define('APOLLO_META_PHONE', '_apollo_phone');
define('APOLLO_META_TELEGRAM_CHAT_ID', '_apollo_telegram_chat_id');
define('APOLLO_LOGIN_MIN_REGISTRATION_AGE', 18);

// Pages
define('APOLLO_LOGIN_PAGE_RESET', 'reset');
