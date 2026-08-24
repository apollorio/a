<?php
/**
 * Apollo Ecosystem — Taxonomy Slug Constants
 *
 * Typed constants for ALL taxonomies.
 * Eliminates magic strings — use ApolloTax::SOUND instead of 'sound'.
 *
 * Usage:
 *   use Apollo\Core\Config\ApolloTax;
 *   $terms = get_terms(['taxonomy' => ApolloTax::SOUND]);
 *
 * @package Apollo\Core\Config
 * @since   6.1.0
 */

declare(strict_types=1);

namespace Apollo\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ApolloTax {

	/*
	═══════════════════════════════════════════════════════════════════
	 * TAXONOMY SLUGS
	 * ═══════════════════════════════════════════════════════════════════ */

	/** Global — Musical genre tree (Gênero Musical) */
	public const SOUND = 'sound';

	/** Global — Seasonal tags */
	public const SEASON = 'season';

	/** Event — Category (Carnaval, Festa, Show…) */
	public const EVENT_CATEGORY = 'event_category';

	/** Event — Type (Festival, Pool Party, Club Night…) */
	public const EVENT_TYPE = 'event_type';

	/** Event — Tags (free-form) */
	public const EVENT_TAG = 'event_tag';

	/** Local — Type (Bar, Club, Beach Club…) */
	public const LOCAL_TYPE = 'local_type';

	/** Local — Area/Neighborhood (Lapa, Leblon, Centro…) */
	public const LOCAL_AREA = 'local_area';

	/** Classified — Domain (Equipamentos, Serviços…) */
	public const CLASSIFIED_DOMAIN = 'classified_domain';

	/** Classified — Intent (COMPRAR, VENDER, TROCAR, ALUGAR) */
	public const CLASSIFIED_INTENT = 'classified_intent';

	/** Doc — Folder (hierarchical) */
	public const DOC_FOLDER = 'doc_folder';

	/** Doc — Type tag */
	public const DOC_TYPE = 'doc_type';

	/** Supplier — Category */
	public const SUPPLIER_CATEGORY = 'supplier_category';

	/** Supplier — Service type */
	public const SUPPLIER_SERVICE = 'supplier_service';

	/** Coauthor — Guest author taxonomy */
	public const COAUTHOR = 'coauthor';

	/*
	═══════════════════════════════════════════════════════════════════
	 * OWNER MAP
	 * ═══════════════════════════════════════════════════════════════════ */

	public const OWNERS = array(
		self::SOUND             => 'apollo-core',
		self::SEASON            => 'apollo-core',
		self::EVENT_CATEGORY    => 'apollo-events',
		self::EVENT_TYPE        => 'apollo-events',
		self::EVENT_TAG         => 'apollo-events',
		self::LOCAL_TYPE        => 'apollo-loc',
		self::LOCAL_AREA        => 'apollo-loc',
		self::CLASSIFIED_DOMAIN => 'apollo-adverts',
		self::CLASSIFIED_INTENT => 'apollo-adverts',
		self::DOC_FOLDER        => 'apollo-docs',
		self::DOC_TYPE          => 'apollo-docs',
		self::SUPPLIER_CATEGORY => 'apollo-suppliers',
		self::SUPPLIER_SERVICE  => 'apollo-suppliers',
		self::COAUTHOR          => 'apollo-coauthor',
	);

	/*
	═══════════════════════════════════════════════════════════════════
	 * OBJECT TYPE MAP — taxonomy → post_types[]
	 * ═══════════════════════════════════════════════════════════════════ */

	public const OBJECT_TYPES = array(
		self::SOUND             => array( ApolloCPT::EVENT, ApolloCPT::DJ, ApolloCPT::LOCAL ),
		self::SEASON            => array( ApolloCPT::EVENT ),
		self::EVENT_CATEGORY    => array( ApolloCPT::EVENT ),
		self::EVENT_TYPE        => array( ApolloCPT::EVENT ),
		self::EVENT_TAG         => array( ApolloCPT::EVENT ),
		self::LOCAL_TYPE        => array( ApolloCPT::LOCAL ),
		self::LOCAL_AREA        => array( ApolloCPT::LOCAL ),
		self::CLASSIFIED_DOMAIN => array( ApolloCPT::CLASSIFIED ),
		self::CLASSIFIED_INTENT => array( ApolloCPT::CLASSIFIED ),
		self::DOC_FOLDER        => array( ApolloCPT::DOC ),
		self::DOC_TYPE          => array( ApolloCPT::DOC ),
		self::SUPPLIER_CATEGORY => array( ApolloCPT::SUPPLIER ),
		self::SUPPLIER_SERVICE  => array( ApolloCPT::SUPPLIER ),
		self::COAUTHOR          => array( 'post', ApolloCPT::EVENT, ApolloCPT::DJ ),
	);

	/*
	═══════════════════════════════════════════════════════════════════
	 * HELPERS
	 * ═══════════════════════════════════════════════════════════════════ */

	/**
	 * @return string[]
	 */
	public static function all(): array {
		return array(
			self::SOUND,
			self::SEASON,
			self::EVENT_CATEGORY,
			self::EVENT_TYPE,
			self::EVENT_TAG,
			self::LOCAL_TYPE,
			self::LOCAL_AREA,
			self::CLASSIFIED_DOMAIN,
			self::CLASSIFIED_INTENT,
			self::DOC_FOLDER,
			self::DOC_TYPE,
			self::SUPPLIER_CATEGORY,
			self::SUPPLIER_SERVICE,
			self::COAUTHOR,
		);
	}

	/**
	 * All hierarchical taxonomies.
	 *
	 * @return string[]
	 */
	public static function hierarchical(): array {
		return array(
			self::SOUND,
			self::EVENT_CATEGORY,
			self::LOCAL_TYPE,
			self::LOCAL_AREA,
			self::CLASSIFIED_DOMAIN,
			self::DOC_FOLDER,
			self::SUPPLIER_CATEGORY,
		);
	}

	/**
	 * All flat (tag-like) taxonomies.
	 *
	 * @return string[]
	 */
	public static function flat(): array {
		return array_values( array_diff( self::all(), self::hierarchical() ) );
	}

	public static function is_valid( string $slug ): bool {
		return in_array( $slug, self::all(), true );
	}

	public static function owner( string $slug ): ?string {
		return self::OWNERS[ $slug ] ?? null;
	}

	/**
	 * Get the post types a taxonomy is registered for.
	 *
	 * @return string[]|null
	 */
	public static function object_types( string $slug ): ?array {
		return self::OBJECT_TYPES[ $slug ] ?? null;
	}

	/** Prevent instantiation. */
	private function __construct() {}
}
