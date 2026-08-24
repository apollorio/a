<?php
/**
 * @partial played-on
 * @expects $ctx['playedOn'] — apollo_dj_get_past_events → map
 *          WP_Query('event') WHERE meta._event_dj_ids CONTAINS dj_id
 *          AND _event_start_date < today
 * JS fills #poTrack cards from APOLLO_DJ.playedOn.
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $ctx['playedOn'] ) ) {
	return;
}
?>
<section class="sec po" id="playedOn">
	<div class="wrap sh">
		<div><span class="lbl"><span class="dot"></span><?php esc_html_e( 'Histórico ao vivo', 'apollo-djs' ); ?></span><h2 class="serif sh-t"><?php esc_html_e( 'Tocou em', 'apollo-djs' ); ?></h2></div>
		<span class="sh-side"><?php esc_html_e( 'Fonte · eventos apollo.rio', 'apollo-djs' ); ?></span>
	</div>
	<div class="po-stage" id="poStage">
		<div class="po-track" id="poTrack"></div>
		<div class="po-bar" aria-hidden="true"><i id="poBar"></i></div>
	</div>
</section>
