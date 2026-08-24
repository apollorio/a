<?php
/**
 * Single Event — Facts strip
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
  <!-- FACTS -->
  <div class="ev-facts" id="evFacts">
    <div class="ev-fact"><i class="ri-calendar-event-line"></i><strong><?php echo esc_html( $date_fact ); ?></strong><small><?php echo esc_html( (string) $year_fact ); ?></small></div>
    <div class="ev-fact-div" aria-hidden="true"></div>
    <div class="ev-fact"><i class="ri-time-line"></i><strong><?php echo esc_html( $start_time ?: '—' ); ?></strong><small><?php echo esc_html( $time_sub ); ?></small></div>
    <div class="ev-fact-div" aria-hidden="true"></div>
    <div class="ev-fact"><i class="ri-map-pin-line"></i><strong><?php echo esc_html( $loc_name ?: '—' ); ?></strong><small><?php echo esc_html( $loc_sub ); ?></small></div>
  </div>
