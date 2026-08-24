<?php

/**
 * Apollo Core - Central Taxonomy Registry
 *
 * MASTER REGISTRY for ALL Taxonomies in Apollo ecosystem.
 * Registers taxonomies as FALLBACK if owner plugin is not active.
 *
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║  SCOPED TAXONOMY ARCHITECTURE                                            ║
 * ║  Taxonomies are registered to SPECIFIC object_types per registry.json    ║
 * ║  Bridge taxonomies (sound, season) span related CPTs                     ║
 * ║  Programmatic bridge via wp_set_object_terms() still works globally      ║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 *
 * @package Apollo\Core
 * @since 6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy Registry - Singleton Pattern
 *
 * SCOPED ARCHITECTURE:
 * - Taxonomies registered with SPECIFIC object_types per apollo-registry.json
 * - Bridge taxonomies (sound, season) shared between related CPTs
 * - Admin columns only appear on relevant CPT admin screens
 * - Programmatic access via wp_set_object_terms() works for any post type
 */
final class TaxonomyRegistry {


	/**
	 * Instance
	 *
	 * @var TaxonomyRegistry|null
	 */
	private static ?TaxonomyRegistry $instance = null;

	/**
	 * Registered taxonomies tracking
	 *
	 * @var array
	 */
	private array $registered = array();

	/**
	 * Taxonomy Definitions
	 *
	 * @var array
	 */
	private array $definitions = array();

	/**
	 * ALL Apollo CPTs - for coauthor and truly global taxonomies
	 *
	 * @var array
	 */
	private const ALL_CPTS = array( 'event', 'dj', 'local', 'classified', 'supplier', 'doc', 'email_aprio', 'hub' );

	/**
	 * Get instance (Singleton)
	 */
	public static function get_instance(): TaxonomyRegistry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->load_definitions();
	}

	/**
	 * Initialize
	 */
	public static function init(): void {
		$instance = self::get_instance();

		// Register taxonomies on init with priority 4 (before CPTs)
		add_action( 'init', array( $instance, 'register_fallback_taxonomies' ), 4 );

		// Seed default terms after registration
		add_action( 'init', array( $instance, 'seed_default_terms' ), 7 );

		// Fire action
		add_action( 'init', array( $instance, 'fire_taxonomies_registered_action' ), 8 );
	}

	/**
	 * Load taxonomy definitions from config/taxonomies.php
	 *
	 * Each taxonomy has scoped object_types matching apollo-registry.json.
	 * Bridge taxonomies (sound, season) span multiple related CPTs.
	 *
	 * @since 6.1.0 — Migrated from hardcoded array to config file.
	 */
	private function load_definitions(): void {
		$this->definitions = \Apollo\Core\Config\ConfigLoader::load( 'taxonomies' );
	}

	/**
	 * Check if taxonomy already exists
	 */
	public function taxonomy_exists( string $slug ): bool {
		return taxonomy_exists( $slug );
	}

	/**
	 * Register fallback taxonomies
	 */
	public function register_fallback_taxonomies(): void {
		foreach ( $this->definitions as $slug => $def ) {
			// Skip if already registered
			if ( $this->taxonomy_exists( $slug ) ) {
				$this->registered[ $slug ] = array(
					'registered_by' => 'owner',
					'owner'         => $def['owner'],
				);
				continue;
			}

			// Register taxonomy
			$this->register_taxonomy( $slug, $def );

			$this->registered[ $slug ] = array(
				'registered_by' => 'apollo-core',
				'owner'         => $def['owner'],
				'fallback'      => $def['owner'] !== 'apollo-core',
			);
		}
	}

	/**
	 * Register a single taxonomy
	 */
	private function register_taxonomy( string $slug, array $def ): void {
		$args = array(
			'labels'            => $def['labels'],
			'hierarchical'      => $def['hierarchical'],
			'public'            => true,
			'show_ui'           => $def['show_ui'] ?? true,
			'show_admin_column' => $def['show_admin_column'] ?? false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => ! $def['hierarchical'],
			'show_in_rest'      => true,
			'rewrite'           => $def['rewrite'] ? array(
				'slug'       => $def['rewrite'],
				'with_front' => false,
			) : false,
		);

		register_taxonomy( $slug, $def['object_types'], $args );
	}

	/**
	 * Seed default terms (only if not exist)
	 */
	public function seed_default_terms(): void {
		foreach ( $this->definitions as $slug => $def ) {
			if ( empty( $def['default_terms'] ) ) {
				continue;
			}

			foreach ( $def['default_terms'] as $term => $children ) {
				// Check if term exists
				$existing = term_exists( $term, $slug );

				if ( ! $existing ) {
					// Create parent term
					$result = wp_insert_term( $term, $slug );

					if ( ! is_wp_error( $result ) ) {
						$parent_id = $result['term_id'];

						// Create children
						if ( is_array( $children ) && ! empty( $children ) ) {
							foreach ( $children as $child ) {
								if ( ! term_exists( $child, $slug ) ) {
									wp_insert_term( $child, $slug, array( 'parent' => $parent_id ) );
								}
							}
						}
					}
				} else {
					// Parent exists, check children
					$parent_id = is_array( $existing ) ? $existing['term_id'] : $existing;

					if ( is_array( $children ) && ! empty( $children ) ) {
						foreach ( $children as $child ) {
							if ( ! term_exists( $child, $slug ) ) {
								wp_insert_term( $child, $slug, array( 'parent' => $parent_id ) );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Fire action after all taxonomies registered
	 */
	public function fire_taxonomies_registered_action(): void {
		do_action( 'apollo/taxonomies/registered', $this->registered );
	}

	/**
	 * Get all taxonomy definitions
	 */
	public function get_definitions(): array {
		return $this->definitions;
	}

	/**
	 * Get registered taxonomies status
	 */
	public function get_registered(): array {
		return $this->registered;
	}

	/**
	 * Get 'sound' terms for user registration matchmaking
	 *
	 * GLOBAL BRIDGE: Can be called from ANY plugin
	 *
	 * @return array Array of term objects
	 */
	public static function get_sounds_for_matchmaking(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'sound',
				'hide_empty' => false,
				'parent'     => 0, // Only parent terms
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$result = array();
		foreach ( $terms as $term ) {
			$children = get_terms(
				array(
					'taxonomy'   => 'sound',
					'hide_empty' => false,
					'parent'     => $term->term_id,
				)
			);

			$result[] = array(
				'id'       => $term->term_id,
				'name'     => $term->name,
				'slug'     => $term->slug,
				'children' => ! is_wp_error( $children ) ? array_map(
					function ( $c ) {
						return array(
							'id'   => $c->term_id,
							'name' => $c->name,
							'slug' => $c->slug,
						);
					},
					$children
				) : array(),
			);
		}

		return $result;
	}

	/**
	 * Get all terms from any taxonomy (GLOBAL BRIDGE)
	 *
	 * @param string $taxonomy Taxonomy slug
	 * @param bool   $flat Whether to return flat array or hierarchical
	 * @return array
	 */
	public static function get_terms_global( string $taxonomy, bool $flat = false ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		);

		if ( ! $flat ) {
			$args['parent'] = 0;
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		if ( $flat ) {
			return array_map(
				function ( $term ) {
					return array(
						'id'   => $term->term_id,
						'name' => $term->name,
						'slug' => $term->slug,
					);
				},
				$terms
			);
		}

		// Hierarchical
		$result = array();
		foreach ( $terms as $term ) {
			$children = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'parent'     => $term->term_id,
				)
			);

			$result[] = array(
				'id'       => $term->term_id,
				'name'     => $term->name,
				'slug'     => $term->slug,
				'children' => ! is_wp_error( $children ) ? array_map(
					function ( $c ) {
						return array(
							'id'   => $c->term_id,
							'name' => $c->name,
							'slug' => $c->slug,
						);
					},
					$children
				) : array(),
			);
		}

		return $result;
	}

	/**
	 * Get all bridge taxonomies
	 *
	 * @return array
	 */
	public function get_bridge_taxonomies(): array {
		return array_filter(
			$this->definitions,
			function ( $def ) {
				return isset( $def['is_bridge'] ) && $def['is_bridge'];
			}
		);
	}

	/**
	 * Check if taxonomy is a bridge
	 *
	 * @param string $slug
	 * @return bool
	 */
	public function is_bridge_taxonomy( string $slug ): bool {
		return isset( $this->definitions[ $slug ]['is_bridge'] ) && $this->definitions[ $slug ]['is_bridge'];
	}

	/**
	 * Get ALL CPTs constant (for external use)
	 *
	 * @return array
	 */
	public static function get_all_cpts(): array {
		return self::ALL_CPTS;
	}
}
