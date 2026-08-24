<?php
/**
 * @partial about
 * @expects $ctx['bio']         — meta._dj_bio
 *          $ctx['videoUrl']    — meta._dj_about_video (priority over photo)
 *          $ctx['aboutPhoto']  — meta._dj_about_photo (≠ hero)
 *          $ctx['genres']      — taxonomy sound tags
 * Video (muted loop) covers the 4/5 box when set; else about photo.
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

$bio         = (string) ( $ctx['bio'] ?? '' );
$video       = (string) ( $ctx['videoUrl'] ?? '' );
$about_photo = (string) ( $ctx['aboutPhoto'] ?? '' );
$genres      = $ctx['genres'] ?? array();

if ( '' === $bio && '' === $video && '' === $about_photo ) {
	return;
}
?>
<section class="sec-tight">
	<div class="wrap about">
		<figure class="about-img rv" id="aboutFig">
			<?php if ( $video ) : ?>
				<video id="aboutVideo" src="<?php echo esc_url( $video ); ?>" autoplay muted loop playsinline></video>
			<?php elseif ( $about_photo ) : ?>
				<img id="aboutImg" src="<?php echo esc_url( $about_photo ); ?>" alt="<?php echo esc_attr( $dj_name ); ?>">
			<?php endif; ?>
		</figure>
		<div>
			<span class="lbl" style="display:block;margin-bottom:18px"><?php esc_html_e( 'Sobre', 'apollo-djs' ); ?></span>
			<h2 class="serif" style="font-size:clamp(44px,7vw,92px);margin-bottom:26px"><?php esc_html_e( 'O artista', 'apollo-djs' ); ?></h2>
			<?php if ( $bio ) : ?>
				<p class="about-p rv"><?php echo esc_html( $bio ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $genres ) ) : ?>
				<div class="tags rv">
					<?php foreach ( $genres as $tag ) : ?>
						<span class="tag"><?php echo esc_html( (string) $tag ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
