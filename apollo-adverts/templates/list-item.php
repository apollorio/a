<?php

/**
 * Template: Resale Ticket Card (list shortcode)
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = isset( $post_id ) ? (int) $post_id : (int) get_the_ID();
$type    = (string) get_post_meta( $post_id, '_classified_type', true );
$kind    = in_array( $type, array( 'accommodation', 'hospedagem', 'crash', 'stay' ), true )
	? 'accommodation'
	: 'ticket';

if ( function_exists( 'apollo_adverts_render_rt_card' ) ) {
	apollo_adverts_render_rt_card( $post_id, array( 'kind' => $kind, 'variant' => 'grid' ) );
}
