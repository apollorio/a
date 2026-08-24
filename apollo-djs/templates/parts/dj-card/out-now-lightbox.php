<?php
/**
 * @partial out-now-lightbox
 * @expects $ctx['tracks'] — full meta._dj_tracks list
 * Modal listing all Out now! releases. Opened by row 06 "Ver todos".
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

if ( empty( $ctx['tracks'] ) || count( $ctx['tracks'] ) <= 5 ) {
	return;
}
?>
<div class="outnow-lb" id="outNowLb" role="dialog" aria-modal="true" aria-labelledby="outNowLbTitle" aria-hidden="true">
	<div class="outnow-lb-panel">
		<div class="outnow-lb-head">
			<div>
				<span class="lbl"><?php esc_html_e( 'Trabalho selecionado', 'apollo-djs' ); ?></span>
				<h3 id="outNowLbTitle"><?php esc_html_e( 'Out now!', 'apollo-djs' ); ?></h3>
			</div>
			<button type="button" class="outnow-lb-x pill" id="outNowLbClose" aria-label="<?php esc_attr_e( 'Fechar', 'apollo-djs' ); ?>"><i class="ri-close-line"></i></button>
		</div>
		<div id="outNowLbList" class="rule-draw"></div>
	</div>
</div>
