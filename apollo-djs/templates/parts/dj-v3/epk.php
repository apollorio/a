<?php
/**
 * DJ v3 Partial: EPK (Electronic Press Kit)
 *
 * Media kit download section with press photos.
 * Variables: $dj_id, $dj_name, $media_kit_url, $rider_url, $dj_banner_url, $dj_photo_url.
 *
 * Meta: _dj_media_kit_url, _dj_rider_url.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $media_kit_url ) && empty( $rider_url ) ) {
	return;
}

// Collect press photos from gallery or featured + banner
$press_photos = array();

// Try gallery attachment IDs first
$gallery_ids = get_post_meta( $dj_id, '_dj_gallery', true );
if ( is_array( $gallery_ids ) ) {
	foreach ( array_slice( $gallery_ids, 0, 3 ) as $att_id ) {
		$url = wp_get_attachment_image_url( (int) $att_id, 'medium_large' );
		if ( $url ) {
			$press_photos[] = $url;
		}
	}
}

// Fallback: use banner + photo
if ( empty( $press_photos ) ) {
	if ( $dj_banner_url ) {
		$press_photos[] = $dj_banner_url;
	}
	if ( $dj_photo_url && $dj_photo_url !== $dj_banner_url ) {
		$press_photos[] = $dj_photo_url;
	}
}
?>

<section class="dj-epk-section" id="epk">
	<div class="dj-epk-inner">
		<div class="dj-epk-text">
			<div class="dj-epk-label"><?php esc_html_e( 'Instant Download', 'apollo-djs' ); ?></div>
			<h2><?php esc_html_e( 'Full Media Kit', 'apollo-djs' ); ?></h2>
			<p>
				<?php esc_html_e( 'Press photos em alta resolução, tech rider, stage plot e logos oficiais. Pronto para promoters.', 'apollo-djs' ); ?>
			</p>

			<?php if ( $media_kit_url ) : ?>
				<a href="<?php echo esc_url( $media_kit_url ); ?>" class="btn btn-primary" target="_blank" rel="noopener">
					<i class="ri-download-2-line"></i> <?php esc_html_e( 'Download Media Kit', 'apollo-djs' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $rider_url ) : ?>
				<a href="<?php echo esc_url( $rider_url ); ?>" class="btn btn-ghost" target="_blank" rel="noopener" style="margin-top:10px;">
					<i class="ri-clipboard-fill"></i> <?php esc_html_e( 'Tech Rider', 'apollo-djs' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $press_photos ) ) : ?>
			<div class="dj-epk-photos">
				<?php if ( isset( $press_photos[0] ) ) : ?>
					<div class="dj-epk-photo">
						<img
							src="<?php echo esc_url( $press_photos[0] ); ?>"
							alt="<?php echo esc_attr( $dj_name ); ?> — Press 1"
							loading="lazy"
							width="800"
							height="800"
						>
					</div>
				<?php endif; ?>

				<?php if ( isset( $press_photos[1] ) || isset( $press_photos[2] ) ) : ?>
					<div class="dj-epk-col-2">
						<?php if ( isset( $press_photos[1] ) ) : ?>
							<div class="dj-epk-photo">
								<img
									src="<?php echo esc_url( $press_photos[1] ); ?>"
									alt="<?php echo esc_attr( $dj_name ); ?> — Press 2"
									loading="lazy"
									width="800"
									height="800"
								>
							</div>
						<?php endif; ?>
						<?php if ( isset( $press_photos[2] ) ) : ?>
							<div class="dj-epk-photo">
								<img
									src="<?php echo esc_url( $press_photos[2] ); ?>"
									alt="<?php echo esc_attr( $dj_name ); ?> — Press 3"
									loading="lazy"
									width="800"
									height="800"
								>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
