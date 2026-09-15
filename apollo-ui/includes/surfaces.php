<?php
/**
 * Apollo UI — lightbox surfaces.
 *
 * WHAT ALREADY EXISTS (do not rebuild it)
 * apollo-events ships a complete, working lightbox:
 *   apollo-events/includes/render-single.php
 *     · apollo_event_lightbox_shell()   prints <div class="ev-lb" data-ev-lightbox>
 *     · apollo_event_lightbox_enqueue() the public enqueue API
 *     · [data-ev-open="{id}"]           the trigger contract
 *     · GET /wp-json/apollo/v1/eventos/{id}/fragmento   the HTML source
 *   Verified live on /eventos: shell present, 16 triggers, fragment HTTP 200
 *   with 8.6 KB of markup, close button `.ev-lb-x[data-ev-lb-close]`.
 *
 * apollo-core already publishes a generic surface table:
 *   window.APOLLO_SURFACES = {"event":{"rest":"…/eventos/"},"dj":{"rest":"…/djs/"}}
 *
 * WHAT THIS FILE ADDS
 * A single place that says, per post type, "this CPT opens in the lightbox and
 * its fragment lives here". apollo-ui's JS reads that table, so adding a CPT to
 * the overlay becomes a settings change rather than new JS.
 *
 * The event path deliberately DELEGATES: if apollo-events' runtime is present,
 * apollo-ui hands the click to it rather than opening its own overlay. Two
 * overlays for one card is the exact duplication this plugin exists to end.
 *
 * @package Apollo\UI
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is the lightbox enabled for this post type?
 */
function apollo_ui_lightbox_enabled( string $post_type ): bool {
	$s = apollo_ui_settings();
	if ( empty( $s['lightbox'] ) ) {
		return false;
	}
	return in_array( $post_type, (array) $s['lightbox_cpts'], true );
}

/**
 * The surface table apollo-ui publishes to the browser.
 *
 * Shape, per post type:
 *   rest      absolute REST base for the item, e.g. …/apollo/v1/eventos/
 *   fragment  path appended to {rest}{id} to fetch renderable HTML
 *   delegate  name of an existing runtime that owns this surface, or ''
 *
 * Filterable so a plugin can add its own surface without editing apollo-ui:
 *   add_filter( 'apollo_ui_surfaces', fn( $s ) => $s + [ 'local' => [ … ] ] );
 *
 * @return array<string,array<string,string>>
 */
function apollo_ui_surfaces(): array {
	$base = function ( string $rest_base ): string {
		return esc_url_raw( rest_url( 'apollo/v1/' . $rest_base . '/' ) );
	};

	$surfaces = array(
		'event' => array(
			'rest'     => $base( 'eventos' ),
			'fragment' => 'fragmento',
			// apollo-events owns this overlay; hand the click over.
			'delegate' => 'apollo-events',
		),
		'dj'    => array(
			'rest'     => $base( 'djs' ),
			'fragment' => '',
			'delegate' => '',
		),
	);

	/**
	 * Filter the lightbox surface table.
	 *
	 * @param array<string,array<string,string>> $surfaces
	 */
	$surfaces = (array) apply_filters( 'apollo_ui_surfaces', $surfaces );

	// Only publish surfaces the operator actually enabled.
	$enabled = (array) apollo_ui_settings()['lightbox_cpts'];
	foreach ( array_keys( $surfaces ) as $cpt ) {
		if ( ! in_array( $cpt, $enabled, true ) ) {
			unset( $surfaces[ $cpt ] );
		}
	}
	return $surfaces;
}

/**
 * Make sure the event lightbox shell exists in the document when we are going
 * to delegate to it.
 *
 * Guarded twice on purpose: the function may not exist (apollo-events inactive)
 * and apollo-events keeps its own print-once ledger, so calling it a second
 * time is safe but calling it blind is not.
 */
function apollo_ui_maybe_print_event_shell(): void {
	if ( ! apollo_ui_settings()['lightbox'] ) {
		return;
	}
	if ( ! apollo_ui_lightbox_enabled( 'event' ) ) {
		return;
	}
	if ( function_exists( 'apollo_event_lightbox_shell' ) ) {
		apollo_event_lightbox_shell();
	}
}
add_action( 'wp_footer', 'apollo_ui_maybe_print_event_shell', 5 );
