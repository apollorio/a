<?php
/**
 * Single Event — Gallery
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$gallery_urls = array();
foreach ( $gallery_ids as $gid ) {
	/* Entries are attachment ids OR absolute URLs (externally hosted images). */
	$url = function_exists( 'apollo_event_image_url' )
		? apollo_event_image_url( $gid, 'large' )
		: (string) wp_get_attachment_image_url( absint( $gid ), 'large' );
	if ( $url ) {
		$gallery_urls[] = $url;
	}
}
if ( empty( $gallery_urls ) ) {
	return;
}
?>
  <!-- GALLERY -->
  <div class="ev-gallery" data-ev="gallery">
	<?php foreach ( $gallery_urls as $src ) : ?>
    	<div class="ev-gi" role="button" tabindex="0" data-full="<?php echo esc_url( $src ); ?>"><img src="<?php echo esc_url( $src ); ?>" alt="" loading="lazy" decoding="async"></div>
	<?php endforeach; ?>
  </div>
