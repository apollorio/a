<?php
/**
 * Single Event — Timetable
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $start HH:MM
 * @param string $end   HH:MM
 */
$apollo_tt_duration = static function ( string $start, string $end ): string {
	if ( '' === $start || '' === $end ) {
		return '';
	}
	$s = preg_match( '/^(\d{1,2}):(\d{2})/', $start, $sm ) ? ( (int) $sm[1] * 60 + (int) $sm[2] ) : null;
	$e = preg_match( '/^(\d{1,2}):(\d{2})/', $end, $em ) ? ( (int) $em[1] * 60 + (int) $em[2] ) : null;
	if ( null === $s || null === $e ) {
		return '';
	}
	$diff = $e - $s;
	if ( $diff <= 0 ) {
		$diff += 24 * 60;
	}
	$h = intdiv( $diff, 60 );
	$m = $diff % 60;
	if ( $h > 0 && $m > 0 ) {
		return $h . 'h' . str_pad( (string) $m, 2, '0', STR_PAD_LEFT );
	}
	if ( $h > 0 ) {
		return $h . 'h';
	}
	return $m . 'min';
};

$tt_has_slot = false;
foreach ( (array) $dj_slots as $tt_s ) {
	if ( is_array( $tt_s ) && '' !== (string) ( $tt_s['start_time'] ?? '' ) ) {
		$tt_has_slot = true;
		break;
	}
}
if ( empty( $djs ) || ! $tt_has_slot ) {
	return;
}
?>
  <!-- TIMETABLE -->
  <section class="ev-panel" id="ttPanel">
    <h2 class="ev-h2" id="ttTitle"><?php esc_html_e( 'Timetable', 'apollo-events' ); ?></h2>
    <div id="ttList">
		<?php foreach ( $djs as $i => $dj ) :
			$slot = array();
			foreach ( $dj_slots as $s ) {
				if ( is_array( $s ) && (int) ( $s['dj_id'] ?? 0 ) === (int) $dj['id'] ) {
					$slot = $s;
					break;
				}
			}
			$st       = (string) ( $slot['start_time'] ?? '' );
			$et       = (string) ( $slot['end_time'] ?? '' );
			$badge    = (string) ( $slot['badge'] ?? '' );
			$role     = $badge !== '' ? $badge : (string) ( $dj['role'] ?? '' );
			$duration = $apollo_tt_duration( $st, $et );
			$is_hl    = ( 'Headliner' === $badge ) || ( 0 === $i );
			$img      = (string) ( $dj['image'] ?? '' );
			?>
      <div class="ev-tt-row<?php echo $is_hl ? ' is-hl' : ''; ?>">
        <div>
          <div class="ev-tt-t"><?php echo esc_html( $st !== '' ? $st : '—' ); ?></div>
		  <?php if ( $duration !== '' ) : ?>
          <div class="ev-tt-d"><?php echo esc_html( $duration ); ?></div>
		  <?php endif; ?>
        </div>
        <div class="ev-tt-dot-wrap"><span class="ev-tt-dot"></span></div>
        <div class="ev-tt-artist">
			<?php if ( $img !== '' ) : ?>
          <img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" decoding="async">
			<?php endif; ?>
          <div>
            <div class="ev-tt-n"><?php echo esc_html( $dj['title'] ); ?></div>
			<?php if ( $role !== '' ) : ?>
            <div class="ev-tt-r"><?php echo esc_html( $role ); ?></div>
			<?php endif; ?>
          </div>
        </div>
      </div>
		<?php endforeach; ?>
    </div>
  </section>
