<?php
/**
 * Single Event — Hero
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uid = (string) ( $uid ?? '' );
?>
<!-- HERO —
     Fallback chain: background-color (always on, instant) → background-image
     (banner, fades in on load via .is-ready) → YouTube ambient video (front
     layer, fades in ~2.6s after ready; if it never loads the image+color
     underneath keep showing, nothing breaks). -->
<section class="ev-hero" id="<?php echo esc_attr( apollo_ev_id( 'evHero', $uid ) ); ?>" data-ev="hero" aria-label="<?php echo esc_attr( $title ); ?>" style="background-color:<?php echo esc_attr( $bg_color ); ?>;">
  <div class="ev-hero-media" data-ev="hero-media">
    <?php if ( $banner ) : ?>
      <img
        data-ev="hero-bg"
        src="<?php echo esc_url( $banner ); ?>"
        alt=""
        decoding="async"
        onload="this.closest('.ev-hero-media').classList.add('is-ready');this.closest('.ev-hero').classList.add('is-ready');">
    <?php endif; ?>
  </div>
  <?php if ( $youtube_id ) : ?>
  <div class="ev-hero-yt" data-ev="hero-yt" aria-hidden="true">
    <iframe
      src="https://www.youtube.com/embed/<?php echo esc_attr( $youtube_id ); ?>?autoplay=1&amp;mute=1&amp;controls=0&amp;loop=1&amp;playlist=<?php echo esc_attr( $youtube_id ); ?>&amp;playsinline=1"
      allow="autoplay; encrypted-media"
      title=""></iframe>
  </div>
  <?php endif; ?>
  <div class="ev-hero-veil" aria-hidden="true"></div>

  <div class="ev-hero-top">
    <button type="button" class="ev-chip" data-ev-action="back" aria-label="<?php esc_attr_e( 'Voltar', 'apollo-events' ); ?>">
      <i class="ri-arrow-left-line"></i>
    </button>
    <button type="button" class="ev-chip" data-ev-action="share" aria-label="<?php esc_attr_e( 'Compartilhar', 'apollo-events' ); ?>">
      <i class="ri-share-line"></i>
    </button>
  </div>

  <div class="ev-hero-copy">
    <div class="ev-kicker">Apollo · Rio</div>
    <h1 class="ev-title" data-ev="hero-title">
		<?php
		foreach ( $title_parts as $i => $line ) {
			echo esc_html( $line );
			if ( $i < count( $title_parts ) - 1 || $title_accent ) {
				echo '<br>';
			}
		}
		if ( $title_accent ) {
			echo '<em>' . esc_html( $title_accent ) . '</em>';
		}
		?>
	</h1>
    <div class="ev-meta">
      <span><i class="ri-calendar-line"></i> <?php echo esc_html( $meta_date ); ?></span>
      <?php if ( $meta_time ) : ?>
        <span><i class="ri-time-line"></i> <?php echo esc_html( $meta_time ); ?></span>
      <?php endif; ?>
      <?php if ( $loc_name ) : ?>
        <span><i class="ri-map-pin-line"></i> <?php echo esc_html( $loc_name ); ?></span>
      <?php endif; ?>
    </div>
  </div>
  <div class="ev-scroll-cue" aria-hidden="true"><i class="ri-arrow-down-s-line"></i>scroll</div>
</section>
