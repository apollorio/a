<?php

/**
 * Template Part: Accommodation Card
 *
 * Same expandable stub as tickets. Hostels stay public + outbound CTA.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'apollo_adverts_render_rt_card' ) ) {
	apollo_adverts_render_rt_card(
		(int) get_the_ID(),
		array(
			'kind'    => 'accommodation',
			'variant' => 'grid',
		)
	);
}
