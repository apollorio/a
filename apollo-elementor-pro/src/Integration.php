<?php
/**
 * Integration — reads apollo-core MASTER_REGISTRY so widgets can resolve
 * CPT slugs, taxonomy names, and meta keys without hardcoding them.
 *
 * @package Apollo\Elementor
 */

declare(strict_types=1);

namespace Apollo\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Integration {

	private static bool  $bootstrapped = false;
	private static array $cpts         = [];
	private static array $taxonomies   = [];

	public static function bootstrap(): void {
		if ( self::$bootstrapped ) {
			return;
		}
		self::$bootstrapped = true;

		if ( class_exists( 'Apollo\Core\CPTRegistry' ) ) {
			self::$cpts = \Apollo\Core\CPTRegistry::get_instance()->get_definitions();
		}
		if ( class_exists( 'Apollo\Core\TaxonomyRegistry' ) ) {
			self::$taxonomies = \Apollo\Core\TaxonomyRegistry::get_instance()->get_definitions();
		}
	}

	/** @return array<string,array<string,mixed>> */
	public static function cpts(): array {
		return self::$cpts;
	}

	/** @return array<string,array<string,mixed>> */
	public static function taxonomies(): array {
		return self::$taxonomies;
	}

	/**
	 * CDN base URL with no trailing slash.
	 */
	public static function cdn_url(): string {
		return rtrim( (string) get_option( 'apollo_cdn_url', APOLLO_ELEMENTOR_URL ), '/' );
	}
}
