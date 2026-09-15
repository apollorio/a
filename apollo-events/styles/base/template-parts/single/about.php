<?php
/**
 * Single Event — About + Spotify
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( '' === trim( wp_strip_all_tags( (string) $content ) ) && empty( $audio_url ) ) {
	return;
}
$plain = wp_strip_all_tags( $content );
$words = array_values( array_filter( preg_split( '/\s+/', $plain ) ?: array() ) );
$about_words = array_slice( $words, 0, 4 );
if ( count( $about_words ) < 2 ) {
	$about_words = array( 'Apollo', 'Evento' );
}
/* Max 2 display lines — balance words across lines (was 1 word/line = tall skinny stack). */
$n_about = count( $about_words );
$about_lines = array();
if ( $n_about <= 2 ) {
	foreach ( $about_words as $w ) {
		$about_lines[] = array( $w );
	}
} else {
	$mid           = (int) ceil( $n_about / 2 );
	$about_lines[] = array_slice( $about_words, 0, $mid );
	$about_lines[] = array_slice( $about_words, $mid );
}
$spotify = $audio_url;
if ( $spotify && false !== strpos( $spotify, 'open.spotify.com' ) && false === strpos( $spotify, '/embed/' ) ) {
	$spotify = str_replace( 'open.spotify.com/', 'open.spotify.com/embed/', $spotify );
}
?>
  <!-- ABOUT -->
  <section class="ev-sec ev-sec-loose" id="<?php echo esc_attr( apollo_ev_id( 'aboutSec', (string) ( $uid ?? '' ) ) ); ?>" data-ev="about">
    <p class="ev-label"><?php esc_html_e( 'O Evento', 'apollo-events' ); ?></p>
    <div class="ev-about-words" data-ev="about-words">
		<?php
		$word_i = 0;
		foreach ( $about_lines as $line_words ) :
			?>
      <div class="ev-about-line">
			<?php
			foreach ( $line_words as $w ) :
				$ac = ( 1 === $word_i ) ? ' is-ac' : '';
				?>
        <span class="ev-about-word<?php echo esc_attr( $ac ); ?>" data-reveal-word><?php echo esc_html( $w ); ?></span>
				<?php
				++$word_i;
			endforeach;
			?>
      </div>
		<?php endforeach; ?>
    </div>
    <div class="ev-about-body" data-ev="about-body">
		<?php
		if ( $content ) {
			echo function_exists( 'Apollo\\Event\\apollo_event_kses_about' )
				? \Apollo\Event\apollo_event_kses_about( (string) $content )
				: wp_kses_post( $content );
		}
		?>
    </div>
  </section>

  <?php if ( $spotify ) : ?>
  <!-- SPOTIFY -->
  <div class="ev-spotify" data-ev="spotify">
    <iframe src="<?php echo esc_url( $spotify ); ?>" height="152"
      allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
      loading="lazy" title="Playlist"></iframe>
  </div>
  <?php else : ?>
  <div class="ev-spotify" data-ev="spotify" hidden></div>
  <?php endif; ?>
