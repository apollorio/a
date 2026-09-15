<?php
/**
 * Apollo UI — the card style table.
 *
 * THE POINT OF THIS FILE
 * A card style is a LOOK, not a post type. `type-1` is "poster"; it is not
 * "event". That separation is what lets wp-admin swap the look of a whole post
 * type without touching a template, and it is why these keys are numbered
 * rather than named after content.
 *
 * Numbering is a contract. type-1 must keep meaning "poster" forever — a
 * surface that hard-codes `type-1` in a shortcode would otherwise silently
 * change shape on upgrade. Add type-7; never renumber.
 *
 * @package Apollo\UI
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The style table.
 *
 * ratio  — media aspect box, drives the CSS custom property --aui-ratio
 * media  — poster | landscape | square | circle | none
 * layout — stack (media above text) | row (media beside text) | bare
 *
 * @return array<string,array<string,mixed>>
 */
function apollo_ui_types(): array {
	return array(
		'type-1' => array(
			'label'  => 'Type 1 — Poster',
			'desc'   => 'Portrait 2:3 media with the title overlaid. The flagship event look.',
			'media'  => 'poster',
			'ratio'  => '2 / 3',
			'layout' => 'stack',
			'overlay' => true,
		),
		'type-2' => array(
			'label'  => 'Type 2 — Wide',
			'desc'   => 'Landscape 16:9 media, title and meta below. Good for listings and classifieds.',
			'media'  => 'landscape',
			'ratio'  => '16 / 9',
			'layout' => 'stack',
			'overlay' => false,
		),
		'type-3' => array(
			'label'  => 'Type 3 — Row',
			'desc'   => 'Compact horizontal row, small thumbnail on the left. Dense lists and rails.',
			'media'  => 'square',
			'ratio'  => '1 / 1',
			'layout' => 'row',
			'overlay' => false,
		),
		'type-4' => array(
			'label'  => 'Type 4 — Avatar',
			'desc'   => 'Circular media with a centred name. People: DJs, hosts, profiles.',
			'media'  => 'circle',
			'ratio'  => '1 / 1',
			'layout' => 'stack',
			'overlay' => false,
		),
		'type-5' => array(
			'label'  => 'Type 5 — Tile',
			'desc'   => 'Square tile with a minimal overlay. Mosaics and grids.',
			'media'  => 'square',
			'ratio'  => '1 / 1',
			'layout' => 'stack',
			'overlay' => true,
		),
		'type-6' => array(
			'label'  => 'Type 6 — Text',
			'desc'   => 'No media. Title, meta and excerpt only. Documents and text-first content.',
			'media'  => 'none',
			'ratio'  => 'auto',
			'layout' => 'bare',
			'overlay' => false,
		),
	);
}

/** Is this a known style key? */
function apollo_ui_is_type( string $key ): bool {
	return array_key_exists( $key, apollo_ui_types() );
}

/**
 * One style definition, or the poster default.
 *
 * @return array<string,mixed>
 */
function apollo_ui_type( string $key ): array {
	$all = apollo_ui_types();
	return $all[ $key ] ?? $all['type-1'];
}

/**
 * Post types apollo-ui is willing to render a card for.
 *
 * Derived from what is actually registered at runtime, not a hard-coded list,
 * so a CPT added by any plugin appears in the admin chooser without an edit
 * here. Falls back to the settings keys when called too early.
 *
 * @return array<string,string> slug => label
 */
function apollo_ui_post_types(): array {
	$out = array();
	if ( function_exists( 'get_post_types' ) ) {
		$objs = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $objs as $slug => $o ) {
			if ( in_array( $slug, array( 'attachment' ), true ) ) {
				continue;
			}
			$out[ $slug ] = isset( $o->labels->name ) ? (string) $o->labels->name : $slug;
		}
	}
	if ( ! $out ) {
		foreach ( array_keys( apollo_ui_settings()['types'] ) as $slug ) {
			$out[ $slug ] = $slug;
		}
	}
	ksort( $out );
	return $out;
}
