<?php
/**
 * apollo-loc — Apollo Surface declaration.
 *
 * DECLARED, NOT YET LIVE — see apollo-djs/includes/surface.php for the full
 * reasoning; the situation here is identical.
 *
 * Short version: apollo-loc ships the parts (templates/parts/loc-hero.php,
 * loc-contact, loc-events, loc-gallery, loc-residents, loc-reviews,
 * loc-sidebar) but the per-loc context that feeds them is built inline in
 * templates/single-local.php, so there is no way to render one loc outside a
 * page request. Until apollo_loc_render_single() exists, this registration is
 * inert: no REST route, and apollo_surface_open_attrs( 'loc', $id ) returns ''
 * so a card degrades to a plain anchor rather than a dead click.
 *
 * Next step, mirroring apollo-events:
 *   1. apollo_loc_single_context( int $post_id ): array   — lift the inline block
 *   2. apollo_loc_render_single( int $post_id, array $args = [] ): string
 *   3. nothing else — the endpoint, card contract and lightbox follow.
 *
 * VOCABULARY NOTE (15-conventions.json): the surface key is `loc`, never
 * `venue`, `local` or `location`. The CPT slug on disk is `local` for legacy
 * reasons and is left alone — 'post_type' below records that, and the public
 * key stays `loc` so the REST base and the card contract read
 * data-ap-open="loc:123".
 *
 * @package Apollo\Local
 * @since   1.0.3
 * @see     apollo-core/includes/surface-contract.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare the loc surface.
 *
 * @return void
 */
function apollo_loc_register_surface(): void {
	if ( ! function_exists( 'apollo_surface_register' ) ) {
		return;
	}

	apollo_surface_register(
		'loc',
		array(
			'post_type' => post_type_exists( 'local' ) ? 'local' : 'loc',
			'rest_base' => 'locs',
			'renderer'  => 'apollo_loc_render_single',
			'can_view'  => function_exists( 'apollo_loc_can_view' ) ? 'apollo_loc_can_view' : '',
			'enqueue'   => 'apollo_event_lightbox_enqueue',
		)
	);
}
add_action( 'init', 'apollo_loc_register_surface', 20 );
