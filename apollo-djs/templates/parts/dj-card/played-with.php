<?php
/**
 * @partial played-with
 * @expects $ctx['playedWith'] — apollo_dj_get_played_with()
 *          co-DJs from past lineups; avatar = _dj_image
 * JS fills #roster from APOLLO_DJ.playedWith.
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $ctx['playedWith'] ) ) {
	return;
}
?>
<section class="sec" id="playedWith">
	<div class="wrap">
		<div class="sh">
			<div><span class="lbl"><span class="dot"></span><?php esc_html_e( 'Ao lado no line-up', 'apollo-djs' ); ?></span><h2 class="serif sh-t"><?php esc_html_e( 'Tocou com', 'apollo-djs' ); ?></h2></div>
			<span class="sh-side"><?php esc_html_e( 'Fonte · line-ups apollo', 'apollo-djs' ); ?></span>
		</div>
		<div class="roster" id="roster"></div>
	</div>
</section>
