<?php

/**
 * Template Part: Ticket Card (Repasse)
 *
 * Shared expandable rt-card. Contact via safety URL.
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
			'kind'    => 'ticket',
			'variant' => 'grid',
		)
	);
}
