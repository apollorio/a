<?php
/**
 * Single Event — Genre marquee
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( empty( $sounds ) ) {
	return;
}
$genres = $sounds;
$loop   = array_merge( $genres, $genres );
?>
  <!-- MARQUEE -->
  <div class="ev-mq" id="genreMarquee" aria-hidden="true">
    <div class="ev-mq-track" id="mqTrack">
		<?php foreach ( $loop as $g ) : ?>
      		<span class="ev-mq-item"><?php echo esc_html( $g ); ?><span class="ev-mq-dot"></span></span>
		<?php endforeach; ?>
    </div>
  </div>
