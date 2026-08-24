<?php
/**
 * @partial footer
 * @expects $ctx['name'] / platforms / _dj_instagram
 *          $ctx['footerImage'] — meta._dj_banner (full-bleed bottom)
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

$sc  = (string) ( $ctx['soundcloud'] ?? '' );
$bc  = (string) ( $ctx['bandcamp'] ?? '' );
$sp  = (string) ( $ctx['spotify'] ?? '' );
$ig  = (string) ( $ctx['instagram'] ?? '' );
$img = (string) ( $ctx['footerImage'] ?? $ctx['heroImage'] ?? '' );
$year = wp_date( 'Y' );
?>
<footer class="foot">
	<div class="wrap">
		<div class="foot-name" id="footName"><?php echo esc_html( mb_strtoupper( $dj_name ) ); ?></div>
		<div class="foot-grid">
			<div class="socials">
				<?php if ( $sc ) : ?>
					<a class="soc pill" id="footSc" href="<?php echo esc_url( $sc ); ?>" target="_blank" rel="noopener" aria-label="SoundCloud"><i class="ri-soundcloud-line"></i></a>
				<?php endif; ?>
				<?php if ( $bc ) : ?>
					<a class="soc pill" id="footBc" href="<?php echo esc_url( $bc ); ?>" target="_blank" rel="noopener" aria-label="Bandcamp"><i class="ri-bandcamp-line"></i></a>
				<?php endif; ?>
				<?php if ( $sp ) : ?>
					<a class="soc pill" id="footSp" href="<?php echo esc_url( $sp ); ?>" target="_blank" rel="noopener" aria-label="Spotify"><i class="ri-spotify-line"></i></a>
				<?php endif; ?>
				<?php if ( $ig ) : ?>
					<a class="soc pill" id="footIg" href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="ri-instagram-line"></i></a>
				<?php endif; ?>
				<button type="button" class="soc pill" data-share="profile" aria-label="<?php esc_attr_e( 'Compartilhar', 'apollo-djs' ); ?>"><i class="ri-share-forward-box-line"></i></button>
			</div>
			<span class="foot-copy">© <?php echo esc_html( $year ); ?> apollo.rio.br — <?php esc_html_e( 'cartão de artista via Apollo', 'apollo-djs' ); ?></span>
		</div>
	</div>
	<?php if ( $img ) : ?>
		<figure class="foot-img" id="footImgWrap">
			<img id="footImg" src="<?php echo esc_url( $img ); ?>" alt="">
			<div class="foot-img-ov"><small><?php esc_html_e( 'O Rio não fecha', 'apollo-djs' ); ?></small></div>
		</figure>
	<?php endif; ?>
</footer>
