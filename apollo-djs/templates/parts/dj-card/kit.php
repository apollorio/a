<?php
/**
 * @partial kit
 * @expects $ctx['mediaKitUrl'] — meta._dj_media_kit_url (Google Drive folder)
 *          $ctx['riderUrl']    — meta._dj_rider_url (optional)
 * CTA label: "Acessar Kit Promo" (opens Drive in new tab — never fake zip download).
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

$kit_url   = (string) ( $ctx['mediaKitUrl'] ?? '' );
$rider_url = (string) ( $ctx['riderUrl'] ?? '' );

if ( '' === $kit_url && '' === $rider_url ) {
	return;
}
?>
<section class="sec" id="kitSection">
	<div class="wrap">
		<div class="kit rv" id="kit">
			<div class="kit-wm" aria-hidden="true">EPK</div>
			<span class="lbl"><span class="dot"></span><?php esc_html_e( 'Imprensa & contratantes', 'apollo-djs' ); ?></span>
			<h2 class="kit-t"><?php esc_html_e( 'Kit de Imprensa', 'apollo-djs' ); ?></h2>
			<p class="kit-p"><?php esc_html_e( 'Fotos em alta resolução, rider técnico, logos e promos — tudo em uma pasta só, pronta pra encaminhar. Compartilhamento global em um toque.', 'apollo-djs' ); ?></p>
			<div class="kit-ctas">
				<?php if ( $kit_url ) : ?>
					<a class="btn btn-white pill" id="kitDl" href="<?php echo esc_url( $kit_url ); ?>" target="_blank" rel="noopener"><i class="ri-folder-open-line"></i> <?php esc_html_e( 'Acessar Kit Promo', 'apollo-djs' ); ?></a>
				<?php endif; ?>
				<button type="button" class="btn btn-out pill" data-share="kit"><i class="ri-share-forward-box-line"></i> <?php esc_html_e( 'Compartilhar kit', 'apollo-djs' ); ?></button>
				<?php if ( $rider_url ) : ?>
					<a class="btn btn-out pill" href="<?php echo esc_url( $rider_url ); ?>" target="_blank" rel="noopener"><i class="ri-clipboard-fill"></i> <?php esc_html_e( 'Tech Rider', 'apollo-djs' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
