<?php
/**
 * Single Event — DJ Line-up rail
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( empty( $djs ) ) {
	return;
}
?>
  <!-- LINE-UP (premium X) -->
  <section class="ev-dj-sec" id="lineupSec">
    <div class="ev-sec-head">
      <h2 class="ev-h2" id="lineupTitle"><?php esc_html_e( 'Line-up', 'apollo-events' ); ?></h2>
      <span class="ev-hint" id="djHint">scroll →</span>
    </div>
    <div class="ev-dj-pin" id="djPin">
    <div class="ev-dj-sticky" id="djSticky">
    <div class="ev-dj-viewport" id="djViewport">
      <div class="ev-dj-rail" id="djRail" data-scroll>
		<?php foreach ( $djs as $i => $dj ) :
			$slot = array();
			foreach ( $dj_slots as $s ) {
				if ( is_array( $s ) && (int) ( $s['dj_id'] ?? 0 ) === (int) $dj['id'] ) {
					$slot = $s;
					break;
				}
			}
			$st    = (string) ( $slot['start_time'] ?? '' );
			$et    = (string) ( $slot['end_time'] ?? '' );
			$badge = (string) ( $slot['badge'] ?? '' );
			if ( '' === $badge && 0 === $i ) {
				$badge = 'Headliner';
			}
			$feat    = ( 'Headliner' === $badge ) || ( 0 === $i && '' === (string) ( $slot['badge'] ?? '' ) );
			$dj_url  = get_permalink( (int) $dj['id'] );
			$dj_audio = (string) ( $dj['audio'] ?? '' );
			?>
        <article class="ev-dj<?php echo $feat ? ' is-feat' : ''; ?>"
			<?php echo $dj_audio !== '' ? ' data-audio="' . esc_attr( $dj_audio ) . '"' : ''; ?>
			data-state="idle"
			<?php echo $dj_audio !== '' ? ' aria-pressed="false"' : ''; ?>
		>
          <div class="ev-dj-photo">
			<?php if ( ! empty( $dj['image'] ) ) : ?>
            	<img src="<?php echo esc_url( $dj['image'] ); ?>" alt="" loading="lazy">
			<?php endif; ?>
          </div>
          <div class="ev-dj-scrim" aria-hidden="true"></div>
		  <?php if ( $badge !== '' ) : ?>
          <div class="ev-dj-badge"><?php echo esc_html( $badge ); ?></div>
		  <?php endif; ?>
          <div class="ev-dj-border" aria-hidden="true"></div>
          <div class="ev-dj-body">
            <h3 class="ev-dj-name">
				<?php if ( $dj_url ) : ?>
					<a href="<?php echo esc_url( $dj_url ); ?>"><?php echo esc_html( $dj['title'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $dj['title'] ); ?>
				<?php endif; ?>
			</h3>
            <div class="ev-dj-meta">
              <p class="ev-dj-slot"><?php if ( $st || $et ) : ?><i><?php echo esc_html( $st ); ?></i>-<i><?php echo esc_html( $et ); ?></i><?php endif; ?></p>
              <p class="ev-dj-time"></p>
            </div>
          </div>
        </article>
		<?php endforeach; ?>
      </div>
    </div>
    </div>
    </div>
  </section>
