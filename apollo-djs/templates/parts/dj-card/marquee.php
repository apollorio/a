<?php
/**
 * @partial marquee
 * @expects $ctx['genres'] — taxonomy sound (apollo_dj_get_sounds)
 * JS fills #mqInner from APOLLO_DJ.genres (doubled for loop).
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $ctx['genres'] ) ) {
	return;
}
?>
<div class="mq" id="mq" aria-hidden="true"><div class="mq-inner" id="mqInner"></div></div>
