<?php
/**
 * Template Part: loc-hero — Hero slider com galeria de imagens
 *
 * Variáveis disponíveis via extract() na single-local.php:
 * @var int    $local_id
 * @var array  $gallery     URLs das imagens (até 5)
 * @var string $local_name
 * @var string $loc_label   Tipo/categoria (taxonomia)
 * @var string $full_address
 * @var string $loc_label
 *
 * @package Apollo\Local
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( ! empty( $gallery ) ) : ?>
<section class="hero-slider-wrapper" aria-label="<?php echo esc_attr( $local_name ); ?>">

	<div class="hero-slider-track" id="heroTrack">
		<?php foreach ( $gallery as $idx => $img_url ) : ?>
			<div class="hero-slide" role="img" aria-label="Foto <?php echo esc_attr( $idx + 1 ); ?> de <?php echo esc_attr( $local_name ); ?>">
				<img
					src="<?php echo esc_url( $img_url ); ?>"
					alt="<?php echo esc_attr( $local_name ); ?>"
					loading="<?php echo $idx === 0 ? 'eager' : 'lazy'; ?>"
					decoding="async"
				>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( count( $gallery ) > 1 ) : ?>
		<div class="hero-dots" aria-hidden="true">
			<?php foreach ( $gallery as $idx => $_ ) : ?>
				<button class="hero-dot<?php echo $idx === 0 ? ' active' : ''; ?>" data-slide="<?php echo esc_attr( $idx ); ?>" type="button" aria-label="Ir para foto <?php echo esc_attr( $idx + 1 ); ?>"></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<!-- Overlay gradiente para legibilidade do texto -->
	<div class="hero-overlay" aria-hidden="true"></div>

	<div class="hero-content">
		<?php if ( $loc_label ) : ?>
			<span class="hero-pill"><?php echo esc_html( $loc_label ); ?></span>
		<?php endif; ?>

		<h1 class="hero-title"><?php echo esc_html( $local_name ); ?></h1>

		<?php if ( $full_address ) : ?>
			<p class="hero-address">
				<i class="ri-map-pin-line" aria-hidden="true"></i>
				<?php echo esc_html( $full_address ); ?>
			</p>
		<?php endif; ?>
	</div>

</section>
<?php else : ?>
<section class="hero-placeholder">
	<div class="hero-content">
		<?php if ( $loc_label ) : ?>
			<span class="hero-pill"><?php echo esc_html( $loc_label ); ?></span>
		<?php endif; ?>
		<h1 class="hero-title"><?php echo esc_html( $local_name ); ?></h1>
		<?php if ( $full_address ) : ?>
			<p class="hero-address">
				<i class="ri-map-pin-line" aria-hidden="true"></i>
				<?php echo esc_html( $full_address ); ?>
			</p>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>
