<?php
/**
 * Apollo Ecosystem — CPT Slug Constants
 *
 * Typed constants for ALL Custom Post Types.
 * Eliminates magic strings — use ApolloCPT::EVENT instead of 'event'.
 *
 * Usage:
 *   use Apollo\Core\Config\ApolloCPT;
 *   $events = get_posts(['post_type' => ApolloCPT::EVENT]);
 *
 * @package Apollo\Core\Config
 * @since   6.1.0
 */

declare(strict_types=1);

namespace Apollo\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ApolloCPT {

	/*
	═══════════════════════════════════════════════════════════════════
	 * POST TYPE SLUGS
	 * ═══════════════════════════════════════════════════════════════════ */

	public const EVENT      = 'event';
	public const DJ         = 'dj';
	public const LOCAL      = 'local';
	public const CLASSIFIED = 'classified';
	public const SUPPLIER   = 'supplier';
	public const DOC        = 'doc';
	public const EMAIL      = 'email_aprio';
	public const HUB        = 'hub';
	public const SHEET      = 'apollo_sheet';

	/*
	═══════════════════════════════════════════════════════════════════
	 * OWNER MAP — CPT → responsible plugin
	 * ═══════════════════════════════════════════════════════════════════ */

	public const OWNERS = array(
		self::EVENT      => 'apollo-events',
		self::DJ         => 'apollo-djs',
		self::LOCAL      => 'apollo-loc',
		self::CLASSIFIED => 'apollo-adverts',
		self::SUPPLIER   => 'apollo-suppliers',
		self::DOC        => 'apollo-docs',
		self::EMAIL      => 'apollo-email',
		self::HUB        => 'apollo-hub',
		self::SHEET      => 'apollo-sheets',
	);

	/*
	═══════════════════════════════════════════════════════════════════
	 * REST BASE MAP — CPT → REST API base
	 * ═══════════════════════════════════════════════════════════════════ */

	public const REST_BASES = array(
		self::EVENT      => 'events',
		self::DJ         => 'djs',
		self::LOCAL      => 'locals',
		self::CLASSIFIED => 'classifieds',
		self::SUPPLIER   => 'suppliers',
		self::DOC        => 'docs',
		self::EMAIL      => 'email-templates',
		self::HUB        => 'hubs',
		self::SHEET      => 'sheets',
	);

	/*
	═══════════════════════════════════════════════════════════════════
	 * HELPERS
	 * ═══════════════════════════════════════════════════════════════════ */

	/**
	 * Get all CPT slugs.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array(
			self::EVENT,
			self::DJ,
			self::LOCAL,
			self::CLASSIFIED,
			self::SUPPLIER,
			self::DOC,
			self::EMAIL,
			self::HUB,
			self::SHEET,
		);
	}

	/**
	 * Check if a slug is a valid Apollo CPT.
	 */
	public static function is_valid( string $slug ): bool {
		return in_array( $slug, self::all(), true );
	}

	/**
	 * Get the owner plugin for a CPT.
	 */
	public static function owner( string $slug ): ?string {
		return self::OWNERS[ $slug ] ?? null;
	}

	/**
	 * Get the REST base for a CPT.
	 */
	public static function rest_base( string $slug ): ?string {
		return self::REST_BASES[ $slug ] ?? null;
	}

	/** Prevent instantiation. */
	private function __construct() {}
}
