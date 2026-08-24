<?php
/**
 * Template Part: loc-gallery — Galeria de fotos do local (strip horizontal)
 *
 * Exibido abaixo do hero como galeria secundária de miniaturas.
 *
 * @var int   $local_id
 * @var array $gallery   URLs de imagens (até 5)
 *
 * @package Apollo\Local
 */

defined( 'ABSPATH' ) || exit;

// Precisa de pelo menos 2 imagens para exibir a galeria separada do hero
if ( count( $gallery ) < 2 ) {
	return;
}
?>
<section class="loc-section loc-gallery-section" aria-label="<?php esc_attr_e( 'Galeria de fotos', 'apollo-local' ); ?>">
	<div class="gallery-strip">
		<?php foreach ( $gallery as $idx => $img_url ) : ?>
			<button
				class="gallery-strip__thumb"
				type="button"
				aria-label="<?php printf( esc_attr__( 'Ver foto %d', 'apollo-local' ), $idx + 1 ); ?>"
				data-gallery-index="<?php echo esc_attr( $idx ); ?>"
			>
				<img
					src="<?php echo esc_url( $img_url ); ?>"
					alt="<?php printf( esc_attr__( 'Foto %d de %s', 'apollo-local' ), $idx + 1, get_the_title( $local_id ) ); ?>"
					loading="lazy"
					decoding="async"
				>
				<?php if ( $idx === count( $gallery ) - 1 && count( $gallery ) >= 5 ) : ?>
					<span class="gallery-more-label" aria-hidden="true">+<?php echo count( $gallery ) - 4; ?></span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>
</section>
