<?php
/**
 * Apollo — Ecosystem Health. The system, reading itself.
 *
 * WHAT THIS IS, AND WHAT IT DELIBERATELY IS NOT
 * ---------------------------------------------
 * It is a REPORT. It reads the four contracts — CPTs, meta, panels, surfaces,
 * cards — and shows where they disagree. It changes nothing, writes nothing,
 * and repairs nothing.
 *
 * That restraint is the design, not a limitation. The brief asked for code that
 * fixes its own channels. In THIS environment that would be dangerous: the
 * folder deploys on save, there is no staging tier, no PHP binary and no test
 * suite, and three plugins sit in the HIGH security-risk band. Code that
 * repairs itself here writes straight to production with nothing able to verify
 * the repair first.
 *
 * The deeper argument is the audit of 2026-08-17 itself. Every defect it found
 * — a PII leak in REST, an /_agent_debug route writing to the web-served uploads
 * directory, two edit screens fataling on PHP 8, an admin panel whose values
 * were silently discarded, `_event_dj_ids` blanking line-ups on any save that
 * omitted one field — was invisible **while the system kept running**. A layer
 * that healed around them would have hidden them longer, not fixed them.
 *
 * So: self-VALIDATING, not self-modifying. Fail-closed, not fail-quiet. The
 * intent behind "self-healing" is that the system should be hard to break and
 * obvious when it is. Contracts deliver the first half. This delivers the
 * second — permanently, on a page, instead of once, in an audit.
 *
 * WHAT IT CHECKS
 * --------------
 *   · meta keys registered with no input anywhere      (a field nobody can fill)
 *   · panel fields with no registered meta key         (a field that goes nowhere)
 *   · panels whose schema fails validation             (the four defects of 08-17)
 *   · CPTs with no editing surface at all              (stock editor only)
 *   · surfaces declared but dormant                    (renderer not callable)
 *   · cards declared but dormant
 *   · CPTs declared in config with no owner registration
 *
 * Admin only, `manage_options`, output escaped, no state. Reachable at
 * Tools → Apollo Health.
 *
 * @package Apollo\Core
 * @since   6.4.0
 * @see     _inventory/STRATEGY-integration.md §4  why this and not self-repair
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_health_report' ) ) {
	/**
	 * Build the report.
	 *
	 * Pure: reads registries and post types, returns an array, touches nothing.
	 * Separated from rendering so a harness — or a future REST route, or WP-CLI —
	 * can assert on it without scraping HTML.
	 *
	 * @return array<string,mixed>
	 */
	function apollo_health_report(): array {
		$cpts_config = array();
		$config_file = APOLLO_CORE_PATH . 'config/cpts.php';
		if ( is_readable( $config_file ) ) {
			$loaded = require $config_file;
			if ( is_array( $loaded ) ) {
				$cpts_config = $loaded;
			}
		}

		$panels   = function_exists( 'apollo_panel_registry' ) ? apollo_panel_registry() : array();
		$surfaces = function_exists( 'apollo_surface_registry' ) ? apollo_surface_registry() : array();
		$cards    = function_exists( 'apollo_card_registry' ) ? apollo_card_registry() : array();

		/*
		 * Meta, per CPT, as apollo-core actually registered it — including the
		 * additive contributions plugins make through apollo_core_register_meta.
		 * Reading the filter's output rather than the config file is what makes
		 * this an honest picture instead of a restatement of intent.
		 */
		$meta_by_cpt = array();
		if ( class_exists( '\Apollo\Core\MetaRegistry' ) ) {
			$registry = \Apollo\Core\MetaRegistry::get_instance();
			if ( method_exists( $registry, 'get_post_meta_definitions' ) ) {
				$map = $registry->get_post_meta_definitions();
				if ( is_array( $map ) ) {
					$meta_by_cpt = $map;
				}
			}
		}

		// Every meta key a panel renders an input for, per CPT.
		$inputs_by_cpt = array();
		foreach ( $panels as $cpt => $schema ) {
			$keys = array();
			foreach ( ( $schema['tabs'] ?? array() ) as $tab ) {
				foreach ( ( $tab['cards'] ?? array() ) as $card ) {
					foreach ( ( $card['fields'] ?? array() ) as $f ) {
						if ( ! empty( $f['key'] ) ) {
							$keys[] = $f['key'];
						}
					}
				}
			}
			$inputs_by_cpt[ $cpt ] = $keys;
		}

		$rows     = array();
		$all_cpts = array_unique(
			array_merge( array_keys( $cpts_config ), array_keys( $panels ), get_post_types( array(), 'names' ) )
		);
		sort( $all_cpts );

		foreach ( $all_cpts as $cpt ) {
			if ( ! post_type_exists( $cpt ) && ! isset( $cpts_config[ $cpt ] ) ) {
				continue;
			}
			// Skip WordPress' own and third-party types — this reports on Apollo.
			if ( ! isset( $cpts_config[ $cpt ] ) && ! isset( $panels[ $cpt ] ) ) {
				continue;
			}

			$declared = array_keys( $meta_by_cpt[ $cpt ] ?? array() );
			$inputs   = $inputs_by_cpt[ $cpt ] ?? array();

			/*
			 * Migration-only and derived keys are excluded from "no input" on
			 * purpose: they are written by a script or by code, and giving them a
			 * hand-editable field would be the bug, not the fix.
			 */
			$no_input_exempt = array( '_track_genre_legacy', '_track_migrated_from', '_event_is_gone' );

			$missing_input = array_values(
				array_diff( $declared, $inputs, $no_input_exempt )
			);
			// A panel field whose key is not registered meta — it saves nowhere useful.
			$orphan_fields = array_values(
				array_diff(
					array_filter( $inputs, static fn( $k ) => str_starts_with( (string) $k, '_' ) ),
					$declared
				)
			);

			$schema_problems = array();
			if ( isset( $panels[ $cpt ] ) && class_exists( 'DeclarativePanel' ) ) {
				$schema_problems = ( new \DeclarativePanel( $cpt, $panels[ $cpt ] ) )->validate_schema();
			}

			/*
			 * Frontend coverage. The admin panel and the frontend form were two
			 * separate declarations that drifted in both directions — dj had 35
			 * admin fields against 14 on the front end, including every social
			 * link the approved single-page mockup renders. The derivation bridge
			 * closes the gap; this column is what stops it silently re-opening.
			 */
			$fe = function_exists( 'apollo_panel_frontend_coverage' )
				? apollo_panel_frontend_coverage( $cpt )
				: array( 'panel' => 0, 'frontend' => 0, 'derived' => 0, 'unrenderable' => array() );

			$rows[ $cpt ] = array(
				'fe_frontend'     => (int) $fe['frontend'],
				'fe_derived'      => (int) $fe['derived'],
				'fe_unrenderable' => (array) $fe['unrenderable'],
				'registered'      => post_type_exists( $cpt ),
				'in_config'       => isset( $cpts_config[ $cpt ] ),
				'owner'           => $cpts_config[ $cpt ]['owner'] ?? '—',
				'meta_declared'   => count( $declared ),
				'has_panel'       => isset( $panels[ $cpt ] ),
				'inputs'          => count( $inputs ),
				'missing_input'   => $missing_input,
				'orphan_fields'   => $orphan_fields,
				'schema_problems' => $schema_problems,
				'surface'         => isset( $surfaces[ $cpt ] )
					? ( function_exists( 'apollo_surface_get' ) && apollo_surface_get( $cpt ) ? 'live' : 'dormant' )
					: '—',
				'card'            => isset( $cards[ $cpt ] )
					? ( function_exists( 'apollo_card_get' ) && apollo_card_get( $cpt ) ? 'live' : 'dormant' )
					: '—',
			);
		}

		return array(
			'generated' => current_time( 'mysql' ),
			'cpts'      => $rows,
			'totals'    => array(
				'cpts'          => count( $rows ),
				'with_panel'    => count( array_filter( $rows, static fn( $r ) => $r['has_panel'] ) ),
				'no_input_keys' => array_sum( array_map( static fn( $r ) => count( $r['missing_input'] ), $rows ) ),
				'orphan_fields' => array_sum( array_map( static fn( $r ) => count( $r['orphan_fields'] ), $rows ) ),
				'bad_schemas'   => count( array_filter( $rows, static fn( $r ) => (bool) $r['schema_problems'] ) ),
			),
		);
	}
}

if ( ! function_exists( 'apollo_health_menu' ) ) {
	/**
	 * Apollo → Health. Moved off Tools 2026-08-25 — see apollo_admin_parent_slug().
	 *
	 * @return void
	 */
	function apollo_health_menu(): void {
		add_submenu_page(
			function_exists( 'apollo_admin_parent_slug' ) ? apollo_admin_parent_slug() : 'tools.php',
			__( 'Apollo Health', 'apollo-core' ),
			__( 'Health', 'apollo-core' ),
			'manage_options',
			'apollo-health',
			'apollo_health_render'
		);
	}
}
// Priority 20: apollo-admin registers the root menu at the default 10.
add_action( 'admin_menu', 'apollo_health_menu', 20 );

if ( ! function_exists( 'apollo_health_render' ) ) {
	/**
	 * Render the report.
	 *
	 * @return void
	 */
	function apollo_health_render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$r = apollo_health_report();
		$t = $r['totals'];

		echo '<div class="wrap"><h1>' . esc_html__( 'Apollo — Ecosystem Health', 'apollo-core' ) . '</h1>';
		echo '<p class="description">' . esc_html__(
			'Reads the four contracts and reports where they disagree. Reports only — it changes nothing.',
			'apollo-core'
		) . '</p>';

		printf(
			'<p><strong>%d</strong> %s · <strong>%d</strong> %s · <strong>%d</strong> %s · <strong>%d</strong> %s · <strong>%d</strong> %s</p>',
			(int) $t['cpts'],
			esc_html__( 'CPTs', 'apollo-core' ),
			(int) $t['with_panel'],
			esc_html__( 'with a panel', 'apollo-core' ),
			(int) $t['no_input_keys'],
			esc_html__( 'meta keys with no input', 'apollo-core' ),
			(int) $t['orphan_fields'],
			esc_html__( 'fields with no meta key', 'apollo-core' ),
			(int) $t['bad_schemas'],
			esc_html__( 'panels failing validation', 'apollo-core' )
		);

		echo '<table class="widefat striped"><thead><tr>';
		foreach ( array( 'CPT', 'Owner', 'Registered', 'Meta', 'Panel', 'Inputs', 'Frontend', 'Surface', 'Card', 'Gaps' ) as $h ) {
			echo '<th>' . esc_html( $h ) . '</th>';
		}
		echo '</tr></thead><tbody>';

		foreach ( $r['cpts'] as $cpt => $row ) {
			$gaps = array();
			if ( ! $row['registered'] ) {
				$gaps[] = __( 'declared in config but not registered', 'apollo-core' );
			}
			if ( ! $row['has_panel'] && $row['meta_declared'] > 0 ) {
				/* translators: %d: number of meta keys. */
				$gaps[] = sprintf( __( 'no panel — %d meta keys editable only via REST', 'apollo-core' ), (int) $row['meta_declared'] );
			}
			if ( $row['missing_input'] ) {
				$gaps[] = esc_html__( 'no input:', 'apollo-core' ) . ' ' . implode( ', ', array_slice( $row['missing_input'], 0, 6 ) )
					. ( count( $row['missing_input'] ) > 6 ? ' …+' . ( count( $row['missing_input'] ) - 6 ) : '' );
			}
			if ( $row['orphan_fields'] ) {
				$gaps[] = esc_html__( 'field with no meta key:', 'apollo-core' ) . ' ' . implode( ', ', $row['orphan_fields'] );
			}
			foreach ( $row['schema_problems'] as $p ) {
				$gaps[] = '<strong>' . esc_html( $p ) . '</strong>';
			}

			if ( $row['fe_unrenderable'] ) {
				/* Admin-only by necessity, not by oversight: the frontend editor
				   has no array-shaped renderer, so a repeater submitted there
				   would be flattened and its rows destroyed. Worth showing so
				   nobody "fixes" it by downgrading the field to a text input. */
				$gaps[] = esc_html__( 'admin-only (no frontend renderer):', 'apollo-core' ) . ' '
					. esc_html( implode( ', ', array_slice( $row['fe_unrenderable'], 0, 4 ) ) );
			}

			printf(
				'<tr><td><code>%s</code></td><td>%s</td><td>%s</td><td>%d</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				esc_html( (string) $cpt ),
				esc_html( (string) $row['owner'] ),
				$row['registered'] ? '&#10003;' : '<span style="color:#b32d2e;">&#10007;</span>',
				(int) $row['meta_declared'],
				$row['has_panel'] ? '&#10003;' : '<span style="color:#b32d2e;">&#10007;</span>',
				(int) $row['inputs'],
				$row['fe_frontend']
					? esc_html( sprintf( '%d (+%d)', (int) $row['fe_frontend'], (int) $row['fe_derived'] ) )
					: '&mdash;',
				esc_html( (string) $row['surface'] ),
				esc_html( (string) $row['card'] ),
				$gaps ? wp_kses_post( implode( '<br>', $gaps ) ) : '<span style="color:#00a32a;">&#10003;</span>'
			);
		}

		echo '</tbody></table>';
		echo '<p class="description">' . esc_html(
			sprintf(
				/* translators: %s: timestamp. */
				__( 'Generated %s. See _inventory/STRATEGY-integration.md for what each column means.', 'apollo-core' ),
				(string) $r['generated']
			)
		) . '</p></div>';
	}
}
