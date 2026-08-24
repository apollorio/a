<?php
/**
 * Apollo — Panel Contract. Declare an admin editing surface with an array.
 *
 * THE THIRD WIRE
 * --------------
 * apollo-core already holds two contracts of exactly this shape:
 *
 *   apollo_surface_register()  a CPT's single page, its REST fragment, its
 *                              lightbox trigger — one call.
 *   apollo_card_register()     a CPT's repeated card, in every list, grid, rail
 *                              and embed, with a print-once style ledger.
 *
 * The missing third was the ADMIN side. Until now a plugin wanting a real
 * editing surface had to subclass Panel — which meant a class file, an
 * instantiation, and a require in apollo-lux-panels' bootstrap. That coupling is
 * why only three CPTs out of seventeen ever got one, and why `track` and
 * `hostel` shipped with seventeen registered meta keys and zero inputs between
 * them.
 *
 *     apollo_panel_register( 'track', array(
 *         'title'    => 'Faixa',
 *         'subtitle' => 'Lançamento — Out Now',
 *         'tabs'     => array( … ),
 *     ) );
 *
 * That is the whole integration. No class, no require, no edit to this plugin.
 * Call it on `init` from anywhere; the panel appears on the next admin load.
 *
 * WHY A REGISTRY AND NOT A FILTER
 * -------------------------------
 * A filter would work and would be one line shorter. It was rejected because a
 * registry can be READ: apollo_panel_registry() enumerates every declared panel,
 * which is what lets the schema validator run across all of them at once, lets a
 * harness assert coverage per CPT, and will let the field list drive
 * show_in_rest instead of a second declaration in MetaRegistry. A filter is
 * write-only — you can add to it, but nothing can ask it what it holds.
 *
 * SAFETY, INHERITED FROM ITS TWO SIBLINGS ON PURPOSE
 * --------------------------------------------------
 *   · DORMANT-SAFE  — a panel for a post type that is not registered is ignored,
 *     so a plugin may declare its panel before (or without) its CPT.
 *   · NEVER CLOBBERS — a second registration for a live post type is refused
 *     unless $replace is explicit. Two plugins fighting over one edit screen is
 *     the `event` situation (two live metabox systems, the legacy one winning on
 *     DOM order) and this contract must not make it easy to repeat.
 *   · VALIDATED      — every declarative panel runs Panel::validate_schema() at
 *     boot, so the four defects of 2026-08-17 are caught at registration rather
 *     than by a fatal on someone's edit screen.
 *   · REGISTERS NOTHING — no CPT, taxonomy, meta key or table. apollo-core
 *     remains the only place those happen.
 *
 * @package Apollo\LuxPanels
 * @since   1.2.0
 * @see     apollo-core/includes/surface-contract.php
 * @see     apollo-core/includes/card-contract.php
 * @see     _inventory/PLAN-cpt-and-admin-panels.md §4 Phase 2-5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A Panel built from a plain array instead of a subclass.
 *
 * Everything Panel already does — render, save, validate, per-field caps,
 * sanitize callbacks — works unchanged. This only removes the requirement to
 * write a class to get at it.
 */
final class DeclarativePanel extends \Apollo\LuxPanels\Panel {

	private string $cpt;

	/** @var array<string,mixed> */
	private array $schema;

	/**
	 * @param string              $cpt    Post type.
	 * @param array<string,mixed> $schema title, subtitle, tabs.
	 */
	public function __construct( string $cpt, array $schema ) {
		$this->cpt    = $cpt;
		$this->schema = wp_parse_args(
			$schema,
			array(
				'title'    => ucfirst( $cpt ),
				'subtitle' => '',
				'tabs'     => array(),
			)
		);
	}

	public function post_type(): string {
		return $this->cpt;
	}

	/** @return array{title:string,subtitle:string,tabs:array} */
	public function schema(): array {
		return $this->schema;
	}
}

if ( ! function_exists( 'apollo_panel_registry' ) ) {
	/**
	 * The panel table. Read-mostly; written only by apollo_panel_register().
	 *
	 * @param string|null              $cpt    Post type to set.
	 * @param array<string,mixed>|null $schema Definition.
	 * @return array<string,array<string,mixed>>
	 */
	function apollo_panel_registry( ?string $cpt = null, ?array $schema = null ): array {
		static $panels = array();
		if ( null !== $cpt && null !== $schema ) {
			$panels[ $cpt ] = $schema;
		}
		return $panels;
	}
}

if ( ! function_exists( 'apollo_panel_register' ) ) {
	/**
	 * Declare an admin editing surface for a post type.
	 *
	 * Safe to call on every request. Call it on `init` (any priority) — the
	 * panels are booted on `admin_menu`, which is later.
	 *
	 * @param string              $cpt     Post type.
	 * @param array<string,mixed> $schema  title, subtitle, tabs.
	 * @param bool                $replace Overwrite an existing declaration.
	 * @return void
	 */
	function apollo_panel_register( string $cpt, array $schema, bool $replace = false ): void {
		$cpt = sanitize_key( $cpt );
		if ( '' === $cpt || empty( $schema['tabs'] ) ) {
			return;
		}

		/*
		 * First registration wins. A silent overwrite here would reproduce the
		 * `event` defect — two systems owning one screen, the winner decided by
		 * load order rather than by intent.
		 */
		$existing = apollo_panel_registry();
		if ( isset( $existing[ $cpt ] ) && ! $replace ) {
			return;
		}

		apollo_panel_registry( $cpt, $schema );
	}
}

if ( ! function_exists( 'apollo_panel_boot_registered' ) ) {
	/**
	 * Instantiate and boot every declared panel.
	 *
	 * Runs late on `init` so a plugin registering at any earlier priority is
	 * picked up, and so post_type_exists() is answerable — a panel for a CPT
	 * that never registered is skipped rather than producing an orphan metabox
	 * on no screen.
	 *
	 * @return void
	 */
	function apollo_panel_boot_registered(): void {
		if ( ! is_admin() ) {
			return;
		}
		foreach ( apollo_panel_registry() as $cpt => $schema ) {
			if ( ! post_type_exists( $cpt ) ) {
				continue; // Dormant — declared ahead of its CPT, or CPT retired.
			}
			( new DeclarativePanel( $cpt, $schema ) )->boot();
		}
	}
}

add_action( 'init', 'apollo_panel_boot_registered', 99 );
