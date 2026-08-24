<?php
/**
 * Apollo Ecosystem — REST Route Constants
 *
 * Typed constants for common REST API route prefixes and helpers.
 * Full namespace: apollo/v1
 *
 * Usage:
 *   use Apollo\Core\Config\ApolloRoute;
 *   register_rest_route(ApolloRoute::NAMESPACE, ApolloRoute::EVENTS, [...]);
 *   $url = ApolloRoute::url('/events/123');
 *
 * @package Apollo\Core\Config
 * @since   6.1.0
 */

declare(strict_types=1);

namespace Apollo\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ApolloRoute {

	/*
	═══════════════════════════════════════════════════════════════════
	 * NAMESPACE
	 * ═══════════════════════════════════════════════════════════════════ */

	public const NAMESPACE = 'apollo/v1';

	/*
	═══════════════════════════════════════════════════════════════════
	 * ROUTE PREFIXES
	 * ═══════════════════════════════════════════════════════════════════ */

	// Core
	public const HEALTH   = '/health';
	public const REGISTRY = '/registry';
	public const SOUNDS   = '/sounds';

	// Auth
	public const AUTH  = '/auth';
	public const QUIZ  = '/quiz';
	public const SIMON = '/simon';

	// Users
	public const USERS   = '/users';
	public const PROFILE = '/profile';

	// Content
	public const EVENTS      = '/events';
	public const DJS         = '/djs';
	public const LOCALS      = '/locals';
	public const CLASSIFIEDS = '/classifieds';
	public const SUPPLIERS   = '/suppliers';

	// Social
	public const FEED        = '/feed';
	public const ACTIVITY    = '/activity';
	public const FOLLOWERS   = '/followers';
	public const FOLLOWING   = '/following';
	public const FAVS        = '/favs';
	public const WOWS        = '/wows';
	public const DEPOIMENTOS = '/depoimentos';
	public const GROUPS      = '/groups';

	// Documents
	public const DOCS       = '/docs';
	public const SIGNATURES = '/signatures';

	// Communication
	public const CHAT          = '/chat';
	public const NOTIFICATIONS = '/notifications';
	public const EMAIL         = '/email';

	// Industry
	public const CENA       = '/cena';
	public const MEMBERSHIP = '/membership';

	// Admin
	public const SETTINGS = '/settings';
	public const STATS    = '/stats';
	public const MOD      = '/mod';

	// Content Tools
	public const SHEETS     = '/sheets';
	public const HUBS       = '/hubs';
	public const SHORTCODES = '/shortcodes';
	public const TEMPLATES  = '/templates';
	public const CANVAS     = '/canvas';
	public const COAUTHORS  = '/coauthors';
	public const DASHBOARD  = '/dashboard';

	// Misc
	public const SEARCH     = '/search';
	public const NEWSLETTER = '/newsletter';
	public const SEO        = '/seo';
	public const PWA        = '/pwa';

	/*
	═══════════════════════════════════════════════════════════════════
	 * HELPERS
	 * ═══════════════════════════════════════════════════════════════════ */

	/**
	 * Build a full REST URL for an Apollo endpoint.
	 *
	 * @param string $path Route path (e.g., '/events/123').
	 * @return string Full URL.
	 */
	public static function url( string $path ): string {
		return rest_url( self::NAMESPACE . $path );
	}

	/**
	 * Get all route definitions from config.
	 *
	 * @return array<string, array{owner: string, methods: string[], auth: mixed}>
	 */
	public static function definitions(): array {
		return require dirname( __DIR__, 2 ) . '/config/routes.php';
	}

	/**
	 * Get routes owned by a specific plugin.
	 *
	 * @return array<string, array{owner: string, methods: string[], auth: mixed}>
	 */
	public static function by_owner( string $plugin ): array {
		$routes = self::definitions();
		$result = array();

		foreach ( $routes as $path => $def ) {
			if ( ( $def['owner'] ?? '' ) === $plugin ) {
				$result[ $path ] = $def;
			}
		}

		return $result;
	}

	/**
	 * Count total endpoints across all plugins.
	 */
	public static function count(): int {
		return count( self::definitions() );
	}

	/** Prevent instantiation. */
	private function __construct() {}
}
