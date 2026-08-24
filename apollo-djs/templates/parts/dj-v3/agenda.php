<?php
/**
 * DJ v3 Partial: Agenda (Gig Rows)
 *
 * Ultra-thin event rows pulled from real WP_Query via apollo_dj_get_upcoming_events().
 * Variables: $dj_id, $dj_events, $dj_events_count.
 *
 * Meta dependencies: _event_start_date, _event_dj_ids from apollo-events.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $dj_events ) ) {
	return;
}

$months_pt = array(
	'01' => 'JAN', '02' => 'FEV', '03' => 'MAR', '04' => 'ABR',
	'05' => 'MAI', '06' => 'JUN', '07' => 'JUL', '08' => 'AGO',
	'09' => 'SET', '10' => 'OUT', '11' => 'NOV', '12' => 'DEZ',
);
?>

<section class="dj-section" id="agenda">
	<div class="dj-section-head">
		<div>
			<span class="dj-section-label"><?php esc_html_e( 'Próximos shows', 'apollo-djs' ); ?></span>
			<h2><?php esc_html_e( 'Agenda', 'apollo-djs' ); ?> <span><?php esc_html_e( 'Global', 'apollo-djs' ); ?></span></h2>
		</div>
	</div>

	<div class="dj-gigs-list">
		<?php foreach ( $dj_events as $event ) :
			$event_id   = $event->ID;
			$start_date = get_post_meta( $event_id, '_event_start_date', true );
			$day        = $start_date ? gmdate( 'd', strtotime( $start_date ) ) : '--';
			$month_num  = $start_date ? gmdate( 'm', strtotime( $start_date ) ) : '01';
			$month      = $months_pt[ $month_num ] ?? 'JAN';
			$event_name = get_the_title( $event_id );
			$event_url  = get_permalink( $event_id );

			// Location from event meta or loc CPT
			$loc_id   = (int) get_post_meta( $event_id, '_event_loc_id', true );
			$location = $loc_id ? get_the_title( $loc_id ) : '';
			$city     = $loc_id ? get_post_meta( $loc_id, '_loc_city', true ) : '';
			if ( $city ) {
				$location = $location ? $location . ', ' . $city : $city;
			}
		?>
			<a href="<?php echo esc_url( $event_url ); ?>" class="dj-gig-row">
				<div class="dj-gig-date">
					<span class="dj-gig-date-day"><?php echo esc_html( $day ); ?></span>
					<span class="dj-gig-date-month"><?php echo esc_html( $month ); ?></span>
				</div>
				<div class="dj-gig-info">
					<h4><?php echo esc_html( $event_name ); ?></h4>
					<?php if ( $location ) : ?>
						<div class="dj-gig-location">
							<i class="ri-map-pin-2-line"></i> <?php echo esc_html( $location ); ?>
						</div>
					<?php endif; ?>
				</div>
				<span class="dj-gig-btn">
					<?php esc_html_e( 'Ver evento', 'apollo-djs' ); ?> <i class="ri-arrow-right-up-line"></i>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
