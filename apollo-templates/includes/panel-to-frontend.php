<?php
/**
 * Panel → Frontend derivation. One field declaration, two renderers.
 *
 * ── THE PROBLEM THIS ENDS ────────────────────────────────────────────────────
 *
 * Every editable field in this ecosystem is declared TWICE — once as an admin
 * panel schema (apollo-lux-panels) and once as a frontend field definition
 * (apollo_frontend_fields_{cpt}). Measured 2026-08-17:
 *
 *              admin panel      frontend form     missing from frontend
 *   dj              35                14                   21
 *   loc             21                16                    8
 *
 * The dj gaps include EVERY social and platform link — _dj_instagram,
 * _dj_soundcloud, _dj_spotify, _dj_bandcamp, _dj_beatport, _dj_mixcloud,
 * _dj_resident_advisor, _dj_youtube, _dj_facebook, _dj_twitter, _dj_tiktok,
 * _dj_website — and the approved single-page mockup RENDERS three of them. A DJ
 * editing their own page cannot fill fields their own page displays.
 *
 * They also diverge in BOTH directions: the frontend owns _local_image_1..5 and
 * both loc taxonomies, which the admin panel lacks. Neither is a superset.
 *
 * ── WHY DERIVATION AND NOT "ADD THE MISSING 21" ──────────────────────────────
 *
 * Writing 21 more definitions by hand makes it worse: two files of 35 that must
 * now stay in sync forever. One schema with two renderers cannot drift from
 * itself. This is the same reasoning that produced the surface, card and panel
 * contracts — see _inventory/STRATEGY-integration.md §3.
 *
 * ── ADDITIVE, NEVER DESTRUCTIVE ──────────────────────────────────────────────
 *
 * This runs at priority 20 on apollo_frontend_fields_{cpt}, AFTER each plugin's
 * own definitions (priority 10). A hand-written field always wins; derivation
 * only fills gaps. That is deliberate: the hand-written definitions carry
 * placeholders, descriptions and section assignments tuned by a human, and a
 * generated field should never overwrite considered copy.
 *
 * So the old files keep working unchanged and can be retired one at a time,
 * only once the derived output is proven equivalent field-for-field.
 *
 * @package Apollo\Templates
 * @since   1.5.4
 * @see     apollo-lux-panels/includes/registry.php   the schema being read
 * @see     _inventory/PLAN-dj-loc-finalization.md    §D2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_panel_type_to_frontend' ) ) {
	/**
	 * Panel field type → FrontendEditor field type.
	 *
	 * THE MAP LIVES HERE, ONCE. Both systems grew their own vocabulary — the
	 * panel says `toggle` and `media_single`, the editor says `checkbox` and
	 * `image`; the panel says `wysiwyg`, the editor only has `textarea`. Every
	 * plugin that bridged them previously did so by hand, which is how
	 * _dj_verified ended up a `toggle` in admin and a `checkbox` on the front end
	 * with different saved values.
	 *
	 * Returns '' for panel types the editor genuinely cannot render. Those are
	 * SKIPPED rather than downgraded: a `repeater` rendered as a text input is a
	 * data-loss bug wearing a field's clothing.
	 *
	 * @param string $panel_type Panel field type.
	 * @return string Frontend type, or '' when unrenderable.
	 */
	function apollo_panel_type_to_frontend( string $panel_type ): string {
		$map = array(
			// Straight-through — same name, same semantics.
			'text'             => 'text',
			'url'              => 'url',
			'email'            => 'email',
			'number'           => 'number',
			'textarea'         => 'textarea',
			'select'           => 'select',
			'taxonomy'         => 'taxonomy',

			// Renamed across the two systems.
			'toggle'           => 'checkbox',
			'media_single'     => 'image',
			'wysiwyg'          => 'textarea',
			'color'            => 'text',
			'map'              => 'coordinates',

			/*
			 * Deliberately unmapped — the editor has no renderer that preserves
			 * their shape. sanitize_field() returns a string and has no array
			 * branch, so a repeater submitted through the frontend would be
			 * flattened and the stored rows destroyed.
			 *
			 *   repeater · repeater_card · media_gallery · hours · lineup
			 *   user_multiselect
			 *
			 * These stay admin-only until the editor grows array support. That
			 * gap is exactly why `track` became a CPT instead of staying a
			 * repeater on the dj post.
			 */
			'date'             => 'text',
			'time'             => 'text',
		);

		return $map[ $panel_type ] ?? '';
	}
}

if ( ! function_exists( 'apollo_panel_derive_frontend_fields' ) ) {
	/**
	 * Turn a CPT's panel schema into frontend field definitions.
	 *
	 * Tab → section, card → nothing (the editor has no card level), field →
	 * field. Labels, hints and icons carry across so derived fields are not
	 * visibly poorer than hand-written ones.
	 *
	 * @param string $cpt Post type.
	 * @return list<array<string,mixed>>
	 */
	function apollo_panel_derive_frontend_fields( string $cpt ): array {
		if ( ! function_exists( 'apollo_panel_registry' ) ) {
			return array(); // apollo-lux-panels inactive — nothing to derive from.
		}

		$panels = apollo_panel_registry();
		if ( empty( $panels[ $cpt ]['tabs'] ) ) {
			return array();
		}

		$out = array();

		foreach ( $panels[ $cpt ]['tabs'] as $tab_id => $tab ) {
			$section = sanitize_key( (string) $tab_id );

			foreach ( (array) ( $tab['cards'] ?? array() ) as $card ) {
				foreach ( (array) ( $card['fields'] ?? array() ) as $f ) {

					$key = (string) ( $f['key'] ?? '' );
					if ( '' === $key ) {
						continue;
					}

					/*
					 * Per-field capability, honoured here exactly as the panel
					 * honours it. A staff-only field such as _dj_verified must
					 * not appear on a self-service form just because it was
					 * derivable.
					 */
					$cap = (string) ( $f['cap'] ?? '' );
					if ( '' !== $cap && ! current_user_can( $cap ) ) {
						continue;
					}

					/*
					 * Explicit context opt-out. 'contexts' => ['admin'] keeps a
					 * field out of the frontend without needing a capability —
					 * for things that are simply not the author's business, like
					 * an internal relation id.
					 */
					$contexts = (array) ( $f['contexts'] ?? array( 'admin', 'frontend' ) );
					if ( ! in_array( 'frontend', $contexts, true ) ) {
						continue;
					}

					$type = apollo_panel_type_to_frontend( (string) ( $f['type'] ?? 'text' ) );
					if ( '' === $type ) {
						continue; // Unrenderable — skipped, never downgraded.
					}

					$field = array(
						'name'        => $key,
						'type'        => $type,
						'label'       => (string) ( $f['label'] ?? $key ),
						'section'     => $section,
						'description' => (string) ( $f['hint'] ?? '' ),
						'placeholder' => (string) ( $f['ph'] ?? '' ),
						/* Provenance. Lets a harness — and a human — tell a
						   derived field from a hand-written one at a glance. */
						'derived'     => true,
					);

					if ( 'select' === $type && ! empty( $f['opts'] ) && is_array( $f['opts'] ) ) {
						$field['options'] = $f['opts'];
					}
					if ( 'taxonomy' === $type && ! empty( $f['opts']['taxonomy'] ) ) {
						$field['taxonomy'] = (string) $f['opts']['taxonomy'];
						$field['multiple'] = ! empty( $f['opts']['multiple'] );
					}

					$out[] = $field;
				}
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'apollo_panel_frontend_bridge' ) ) {
	/**
	 * Fill frontend gaps from the panel schema, for every CPT that has one.
	 *
	 * Registered per editable post type at priority 20, so hand-written
	 * definitions (priority 10) are already present and always win.
	 *
	 * @return void
	 */
	function apollo_panel_frontend_bridge(): void {
		if ( ! function_exists( 'apollo_panel_registry' ) ) {
			return;
		}

		foreach ( array_keys( apollo_panel_registry() ) as $cpt ) {
			add_filter(
				"apollo_frontend_fields_{$cpt}",
				static function ( array $fields ) use ( $cpt ): array {
					$existing = array();
					foreach ( $fields as $f ) {
						if ( ! empty( $f['name'] ) ) {
							$existing[ $f['name'] ] = true;
						}
					}

					foreach ( apollo_panel_derive_frontend_fields( $cpt ) as $derived ) {
						if ( isset( $existing[ $derived['name'] ] ) ) {
							continue; // Hand-written wins. Always.
						}
						$fields[] = $derived;
					}

					return $fields;
				},
				20
			);
		}
	}
}
add_action( 'init', 'apollo_panel_frontend_bridge', 100 );

if ( ! function_exists( 'apollo_panel_frontend_coverage' ) ) {
	/**
	 * Coverage report for one CPT — what the panel has, what the form has, gaps.
	 *
	 * Read-only. Consumed by Tools → Apollo Health so the drift this file exists
	 * to close stays visible rather than becoming folklore.
	 *
	 * @param string $cpt Post type.
	 * @return array{panel:int,frontend:int,derived:int,unrenderable:list<string>}
	 */
	function apollo_panel_frontend_coverage( string $cpt ): array {
		$derived = apollo_panel_derive_frontend_fields( $cpt );

		$panel_total  = 0;
		$unrenderable = array();

		if ( function_exists( 'apollo_panel_registry' ) ) {
			$panels = apollo_panel_registry();
			foreach ( (array) ( $panels[ $cpt ]['tabs'] ?? array() ) as $tab ) {
				foreach ( (array) ( $tab['cards'] ?? array() ) as $card ) {
					foreach ( (array) ( $card['fields'] ?? array() ) as $f ) {
						++$panel_total;
						if ( '' === apollo_panel_type_to_frontend( (string) ( $f['type'] ?? '' ) ) ) {
							$unrenderable[] = (string) ( $f['key'] ?? '?' ) . ' (' . (string) ( $f['type'] ?? '?' ) . ')';
						}
					}
				}
			}
		}

		return array(
			'panel'        => $panel_total,
			'frontend'     => count( (array) apply_filters( "apollo_frontend_fields_{$cpt}", array() ) ),
			'derived'      => count( $derived ),
			'unrenderable' => $unrenderable,
		);
	}
}
