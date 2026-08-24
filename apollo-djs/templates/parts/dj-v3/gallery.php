<?php
/**
 * DJ v3 Partial: Gallery
 *
 * Photo grid from post attachments or gallery meta.
 * Variables: $dj_id, $dj_name, $all_links.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;

// Try gallery meta first
$gallery_ids = get_post_meta( $dj_id, '_dj_gallery', true );
$photos      = array();

if ( is_array( $gallery_ids ) && ! empty( $gallery_ids ) ) {
	foreach ( $gallery_ids as $att_id ) {
		$url = wp_get_attachment_image_url( (int) $att_id, 'medium_large' );
		if ( $url ) {
			$photos[] = array(
				'url' => $url,
				'alt' => wp_get_attachment_caption( (int) $att_id ) ?: $dj_name,
			);
		}
	}
}

// Fallback: attached images
if ( empty( $photos ) ) {
	$attachments = get_posts(
		array(
			'post_parent'    => $dj_id,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => 8,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);
	foreach ( $attachments as $att ) {
		$url = wp_get_attachment_image_url( $att->ID, 'medium_large' );
		if ( $url ) {
			$photos[] = array(
				'url' => $url,
				'alt' => $att->post_excerpt ?: $dj_name,
			);
		}
	}
}

if ( empty( $photos ) ) {
	return;
}

// Instagram link for CTA
$instagram_url = $all_links['social']['instagram']['url'] ?? '';
?>

<section class="dj-section" id="galeria">
	<div class="dj-section-head">
		<div>
			<span class="dj-section-label"><?php esc_html_e( 'Visuals', 'apollo-djs' ); ?></span>
			<h2><?php esc_html_e( 'Moments', 'apollo-djs' ); ?></h2>
		</div>
		<?php if ( $instagram_url ) : ?>
			<a href="<?php echo esc_url( $instagram_url ); ?>" class="dj-section-link" target="_blank" rel="noopener">
				Instagram <i class="ri-arrow-right-s-line"></i>
			</a>
		<?php endif; ?>
	</div>

	<div class="dj-gallery-grid">
		<?php foreach ( array_slice( $photos, 0, 8 ) as $photo ) : ?>
			<div class="dj-gallery-item">
				<img
					src="<?php echo esc_url( $photo['url'] ); ?>"
					alt="<?php echo esc_attr( $photo['alt'] ); ?>"
					loading="lazy"
					width="600"
					height="600"
				>
			</div>
		<?php endforeach; ?>
	</div>
</section>
