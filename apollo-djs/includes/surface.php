<?php
/**
 * apollo-djs — Apollo Surface declaration.
 *
 * LIVE. (This docblock described a dormant surface until 2026-08-17; the
 * renderer it was waiting for had already shipped and the note had gone stale.
 * A stale "not yet built" comment is worse than no comment — it invites a
 * second implementation of something that exists.)
 *
 * WHAT MAKES IT LIVE
 * ------------------
 * apollo_surface_get( 'dj' ) returns the definition only when 'renderer' is
 * callable. includes/render-single.php defines apollo_dj_render_single() and is
 * required BEFORE this file in apollo-djs.php, so by the time init:20 runs the
 * surface is usable and apollo-core gives it, for free:
 *
 *   · GET apollo/v1/djs/{id}/fragmento
 *   · apollo_surface_open_attrs( 'dj', $id ) → data-ap-open + real permalink
 *   · the shared lightbox runtime
 *
 * TWO SINGLE-DJ PATHS EXIST — KNOW WHICH ONE YOU ARE IN
 * ----------------------------------------------------
 * · styles/base/single-dj.php  → composes cells through apollo_dj_render_single()
 *   (template-parts/single/*). THIS is the SSOT and what the fragment renders.
 * · templates/single-dj.php    → the older monolith, context inline at the top,
 *   parts in templates/parts/dj/*. Retained; not the surface path.
 *
 * Do not "unify" them by pointing the renderer at the monolith. The cell path
 * is the one the mockup generator (_sandbox/build-dj-cells.py) writes to.
 *
 * @package Apollo\DJs
 * @since   1.0.5
 * @see     apollo-core/includes/surface-contract.php
 * @see     includes/render-single.php                the DJ SSOT renderer
 * @see     apollo-events/includes/render-single.php  the reference implementation
 * @see     _inventory/PLAN-surface-lightbox-global.md
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare the DJ surface.
 *
 * @return void
 */
function apollo_dj_register_surface(): void {
	if ( ! function_exists( 'apollo_surface_register' ) ) {
		return;
	}

	apollo_surface_register(
		'dj',
		array(
			'post_type' => 'dj',
			'rest_base' => 'djs',
			// Defined in includes/render-single.php, required before this file.
			'renderer'  => 'apollo_dj_render_single',
			'can_view'  => function_exists( 'apollo_dj_can_view' ) ? 'apollo_dj_can_view' : '',
			/*
			 * The lightbox SHELL and runtime live in apollo-events and are
			 * surface-neutral, so this borrows them rather than shipping a
			 * second overlay. The DJ fragment carries its own styles and
			 * runtime cells, which the lightbox hydrates on inject.
			 */
			'enqueue'   => 'apollo_event_lightbox_enqueue',
		)
	);
}
add_action( 'init', 'apollo_dj_register_surface', 20 );
