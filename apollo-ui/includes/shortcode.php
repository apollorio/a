<?php
/**
 * Apollo UI — the global shortcodes.
 *
 * [apollo_card id="72" type="type-1"]
 * [apollo_cards cpt="event" count="8" type="type-2" columns="4"]
 *
 * Both are registered with shortcode_exists() guards. add_shortcode() SILENTLY
 * overwrites an existing tag, and this ecosystem has already lost four
 * shortcodes that way — apollo-events now runs at init:20 specifically so it
 * yields to apollo-templates. apollo-ui yields to everyone.
 *
 * @package Apollo\UI
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [apollo_card] — one card.
 *
 * @param array<string,string>|string $atts
 */
function apollo_ui_sc_card( $atts ): string {
	$a = shortcode_atts(
		array(
			'id'       => '0',
			'type'     => '',
			'variant'  => 'default',
			'class'    => '',
			'lightbox' => '',
		),
		is_array( $atts ) ? $atts : array(),
		'apollo_card'
	);

	$id = absint( $a['id'] );
	if ( ! $id ) {
		$id = (int) get_the_ID();
	}
	if ( ! $id ) {
		return '';
	}

	$type = $a['type'];
	if ( ! $type || ! apollo_ui_is_type( $type ) ) {
		$post = get_post( $id );
		$type = $post ? apollo_ui_type_for( (string) $post->post_type ) : 'type-1';
	}

	$lb = '' === $a['lightbox'] ? null : filter_var( $a['lightbox'], FILTER_VALIDATE_BOOLEAN );

	return apollo_ui_card(
		$type,
		$id,
		array(
			'variant'  => (string) $a['variant'],
			'class'    => (string) $a['class'],
			'lightbox' => $lb,
		)
	);
}

/**
 * [apollo_cards] — a grid of cards for one post type.
 *
 * @param array<string,string>|string $atts
 */
function apollo_ui_sc_cards( $atts ): string {
	$a = shortcode_atts(
		array(
			'cpt'      => 'event',
			'count'    => '8',
			'type'     => '',
			'variant'  => 'grid',
			'columns'  => '4',
			'orderby'  => 'date',
			'order'    => 'DESC',
			'lightbox' => '',
		),
		is_array( $atts ) ? $atts : array(),
		'apollo_cards'
	);

	$cpt = sanitize_key( $a['cpt'] );
	if ( ! $cpt || ! post_type_exists( $cpt ) ) {
		return '';
	}

	$type = $a['type'];
	if ( ! $type || ! apollo_ui_is_type( $type ) ) {
		$type = apollo_ui_type_for( $cpt );
	}

	$q = new WP_Query(
		array(
			'post_type'              => $cpt,
			'post_status'            => 'publish',
			'posts_per_page'         => max( 1, min( 48, absint( $a['count'] ) ) ),
			'orderby'                => sanitize_key( $a['orderby'] ),
			'order'                  => 'ASC' === strtoupper( (string) $a['order'] ) ? 'ASC' : 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	if ( ! $q->have_posts() ) {
		return '';
	}

	$lb  = '' === $a['lightbox'] ? null : filter_var( $a['lightbox'], FILTER_VALIDATE_BOOLEAN );
	$col = max( 1, min( 8, absint( $a['columns'] ) ) );

	$out = sprintf(
		'<div class="aui-grid aui-grid--%1$s" style="--aui-cols: %2$d;" data-aui-cpt="%3$s">',
		esc_attr( $type ),
		$col,
		esc_attr( $cpt )
	);
	foreach ( $q->posts as $p ) {
		$out .= apollo_ui_card(
			$type,
			(int) $p->ID,
			array( 'variant' => (string) $a['variant'], 'lightbox' => $lb )
		);
	}
	$out .= '</div>';

	wp_reset_postdata();
	return $out;
}

/**
 * Register both tags, yielding to any existing owner.
 */
function apollo_ui_register_shortcodes(): void {
	if ( ! apollo_ui_settings()['enabled'] ) {
		return;
	}
	foreach ( array(
		'apollo_card'  => 'apollo_ui_sc_card',
		'apollo_cards' => 'apollo_ui_sc_cards',
	) as $tag => $cb ) {
		if ( ! shortcode_exists( $tag ) ) {
			add_shortcode( $tag, $cb );
		}
	}
}
add_action( 'init', 'apollo_ui_register_shortcodes', 25 );
