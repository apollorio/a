<?php
/**
 * Shortcode: [apollo_classified]
 *
 * Displays a single classified ad.
 * Adapted from WPAdverts shortcode_adverts_add() single view logic.
 *
 * Attributes:
 *   id - classified post ID
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [apollo_classified] shortcode handler
 * Adapted from WPAdverts single ad display pattern
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function apollo_adverts_shortcode_single( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'apollo_classified'
	);

	wp_enqueue_style( 'apollo-adverts' );
	wp_enqueue_script( 'apollo-adverts' );

	$post_id = absint( $atts['id'] );

	// If no ID, try from query var or current post
	if ( ! $post_id ) {
		$post_id = get_query_var( 'classified_id', 0 );
	}
	if ( ! $post_id && is_singular( APOLLO_CPT_CLASSIFIED ) ) {
		$post_id = get_the_ID();
	}

	if ( ! $post_id ) {
		return '<p class="apollo-adverts-notice">' . esc_html__( 'Anúncio não encontrado.', 'apollo-adverts' ) . '</p>';
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
		return '<p class="apollo-adverts-notice">' . esc_html__( 'Anúncio não encontrado.', 'apollo-adverts' ) . '</p>';
	}

	// Check if expired
	$is_expired = apollo_adverts_is_expired( $post_id );
	if ( $is_expired && $post->post_status !== 'publish' ) {
		$can_view = ( is_user_logged_in() && (int) $post->post_author === get_current_user_id() ) || current_user_can( 'manage_options' );
		if ( ! $can_view ) {
			return '<p class="apollo-adverts-notice">' . esc_html__( 'Este anúncio expirou.', 'apollo-adverts' ) . '</p>';
		}
	}

	// Increment views (also in Plugin.php template_redirect hook for CPT archive)
	apollo_adverts_increment_views( $post_id );

	// Get gallery
	$images = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_parent'    => $post_id,
			'post_mime_type' => 'image',
			'posts_per_page' => APOLLO_ADVERTS_MAX_IMAGES,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	// Get meta data
	$meta = array();
	foreach ( APOLLO_ADVERTS_META_KEYS as $key => $config ) {
		$meta[ $key ] = get_post_meta( $post_id, $key, true );
	}

	// Get taxonomy terms
	$domains = wp_get_object_terms( $post_id, APOLLO_TAX_CLASSIFIED_DOMAIN );
	$intents = wp_get_object_terms( $post_id, APOLLO_TAX_CLASSIFIED_INTENT );

	ob_start();

	$template_data = array(
		'post'       => $post,
		'post_id'    => $post_id,
		'images'     => $images,
		'meta'       => $meta,
		'domains'    => is_wp_error( $domains ) ? array() : $domains,
		'intents'    => is_wp_error( $intents ) ? array() : $intents,
		'is_expired' => $is_expired,
		'is_owner'   => is_user_logged_in() && (int) $post->post_author === get_current_user_id(),
	);

	apollo_adverts_load_template( 'single.php', $template_data );

	return ob_get_clean();
}
