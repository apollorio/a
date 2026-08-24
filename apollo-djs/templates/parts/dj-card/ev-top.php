<?php
/**
 * @partial ev-top
 * @expects $ctx['name'] — title / meta._dj_name
 * Fixed top bar: back · artist name · share.
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="ev-top" id="evTop">
	<button type="button" class="ev-chip" aria-label="<?php esc_attr_e( 'Voltar', 'apollo-djs' ); ?>" onclick="history.back()"><i class="ri-arrow-left-s-line"></i></button>
	<div class="ev-top-id" aria-hidden="true"><b id="evTopName"><?php echo esc_html( $dj_name ); ?></b><span><?php esc_html_e( 'Cartão de artista', 'apollo-djs' ); ?></span></div>
	<button type="button" class="ev-chip" data-share="profile" aria-label="<?php esc_attr_e( 'Compartilhar cartão', 'apollo-djs' ); ?>"><i class="ri-share-forward-box-line"></i></button>
</div>
