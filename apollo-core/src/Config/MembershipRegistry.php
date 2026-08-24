<?php

/**
 * Membership slug registry — reads apollo-core/config/registry.php.
 *
 * @package Apollo\Core\Config
 * @since   6.1.0
 */

declare(strict_types=1);

namespace Apollo\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MembershipRegistry {

	/** @var array<string, mixed>|null */
	private static ?array $config = null;

	/**
	 * @return array<string, mixed>
	 */
	private static function membership_config(): array {
		if ( null !== self::$config ) {
			return self::$config;
		}

		$data = ConfigLoader::load( 'registry' );
		self::$config = is_array( $data['membership'] ?? null ) ? $data['membership'] : array();

		return self::$config;
	}

	public static function storage_version(): int {
		return (int) ( self::membership_config()['storage_version'] ?? 2 );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function profile_badges(): array {
		$badges = self::membership_config()['profile_badges'] ?? array();
		return is_array( $badges ) ? $badges : array();
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function access_slugs(): array {
		$slugs = self::membership_config()['access_slugs'] ?? array();
		return is_array( $slugs ) ? $slugs : array();
	}

	/**
	 * @return array<int, string>
	 */
	public static function all_profile_slugs(): array {
		return array_keys( self::profile_badges() );
	}

	/**
	 * @return array<int, string>
	 */
	public static function all_access_slugs(): array {
		return array_keys( self::access_slugs() );
	}

	/**
	 * @return array<int, string>
	 */
	public static function all_valid_slugs(): array {
		return array_values( array_unique( array_merge( self::all_profile_slugs(), self::all_access_slugs() ) ) );
	}

	/**
	 * @return array<string, string>
	 */
	public static function legacy_aliases(): array {
		$aliases = self::membership_config()['legacy_aliases'] ?? array();
		return is_array( $aliases ) ? $aliases : array();
	}

	/**
	 * @return array<int, string>
	 */
	public static function deprecated_user_meta_keys(): array {
		$keys = self::membership_config()['deprecated_user_meta'] ?? array();
		return is_array( $keys ) ? $keys : array();
	}

	public static function normalize_slug( string $slug ): string {
		$slug    = sanitize_key( $slug );
		$aliases = self::legacy_aliases();

		if ( isset( $aliases[ $slug ] ) ) {
			return sanitize_key( (string) $aliases[ $slug ] );
		}

		return $slug;
	}

	public static function is_access_slug( string $slug ): bool {
		return in_array( sanitize_key( $slug ), self::all_access_slugs(), true );
	}

	public static function is_profile_slug( string $slug ): bool {
		return in_array( sanitize_key( $slug ), self::all_profile_slugs(), true );
	}

	public static function is_deprecated( string $slug ): bool {
		$slug = sanitize_key( $slug );
		$def  = self::get_badge_def( $slug );

		return ! empty( $def['deprecated'] );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_badge_def( string $slug ): ?array {
		$slug = sanitize_key( $slug );

		if ( isset( self::profile_badges()[ $slug ] ) ) {
			return self::profile_badges()[ $slug ];
		}

		if ( isset( self::access_slugs()[ $slug ] ) ) {
			return self::access_slugs()[ $slug ];
		}

		return null;
	}

	/**
	 * All badge/access definitions keyed by slug (for admin UI + BC constant).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all_badge_types(): array {
		return array_merge( self::profile_badges(), self::access_slugs() );
	}

	public static function nicotine_tier_rank( string $slug ): int {
		$slug  = sanitize_key( $slug );
		$ranks = self::membership_config()['nicotine_tier_rank'] ?? array();

		if ( ! is_array( $ranks ) ) {
			return 0;
		}

		return (int) ( $ranks[ $slug ] ?? 0 );
	}

	/**
	 * Slugs that require explicit admin whitelisting for agent delegation.
	 *
	 * @return array<int, string>
	 */
	public static function agent_restricted_slugs(): array {
		$slugs = self::membership_config()['agent_restricted_slugs'] ?? array();
		return is_array( $slugs ) ? array_values( $slugs ) : array();
	}

	public static function is_agent_restricted_slug( string $slug ): bool {
		$slug = self::normalize_slug( sanitize_key( $slug ) );
		return in_array( $slug, self::agent_restricted_slugs(), true );
	}

	/** Prevent instantiation. */
	private function __construct() {}
}
