<?php

/**
 * Plugin Activation Handler
 *
 * Creates default terms, sets options.
 *
 * @package Apollo\Journal
 */

declare(strict_types=1);

namespace Apollo\Journal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation handler.
 */
class Activation {


	/**
	 * Run activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::set_defaults();

		// Register CPTs and taxonomies so terms can be created.
		$plugin = Plugin::get_instance();
		$plugin->register_cpts();
		$plugin->register_taxonomies();
		$plugin->register_rewrites();

		self::create_default_terms();

		// Flush rewrite rules so CPT/rewrite slugs work immediately.
		flush_rewrite_rules( false );

		update_option( 'apollo_journal_activated', time() );
	}

	/**
	 * Set default option values.
	 *
	 * @return void
	 */
	private static function set_defaults(): void {
		add_option( 'aj_nrep_prefix', 'NREP.' );
		add_option( 'aj_nrep_format', '%prefix%%year%-%seq%' );
	}

	/**
	 * Create "nota-de-repudio" category and default taxonomy terms.
	 *
	 * @return void
	 */
	private static function create_default_terms(): void {
		// ── Nota de Repúdio (core WP category) ──
		if ( ! term_exists( 'nota-de-repudio', 'category' ) ) {
			wp_insert_term(
				'Nota de Repúdio',
				'category',
				array(
					'slug'        => 'nota-de-repudio',
					'description' => 'Notas de repúdio oficiais — código NREP auto-gerado.',
				)
			);
		}

		// Ensure "news" category exists (used by mural/news.php).
		if ( ! term_exists( 'news', 'category' ) ) {
			wp_insert_term(
				'News',
				'category',
				array(
					'slug' => 'news',
				)
			);
		}

		// ── Register taxonomies first (init may not have fired yet) ──
		// NOTE: Already called in activate() before this method.

		// ── Custom Taxonomy Default Terms ──
		$tax_terms = array(
			'music'   => array( 'Funk', 'Samba', 'Rap', 'Rock', 'MPB', 'Eletrônica', 'Pagode', 'Forró' ),
			'culture' => array( 'Cinema', 'Literatura', 'Artes Visuais', 'Teatro', 'Dança', 'Gastronomia' ),
			'rio'     => array( 'Centro', 'Zona Sul', 'Zona Norte', 'Zona Oeste', 'Baixada', 'Niterói' ),
			'formato' => array( 'Reportagem', 'Entrevista', 'Opinião', 'Cobertura', 'Guia', 'Lista' ),
		);

		foreach ( $tax_terms as $taxonomy => $terms ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			foreach ( $terms as $name ) {
				$slug = sanitize_title( $name );
				if ( ! term_exists( $slug, $taxonomy ) ) {
					wp_insert_term( $name, $taxonomy );
				}
			}
		}
	}
}
