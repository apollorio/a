<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$events    = $data['events'] ?? [];
$today_iso = current_time( 'Y-m-d' );
$now       = current_datetime();
$year      = (int) $now->format( 'Y' );
$month     = (int) $now->format( 'n' );

$month_names = [
	1 => __( 'January', 'apollo-elementor' ),   2 => __( 'February', 'apollo-elementor' ),
	3 => __( 'March', 'apollo-elementor' ),      4 => __( 'April', 'apollo-elementor' ),
	5 => __( 'May', 'apollo-elementor' ),        6 => __( 'June', 'apollo-elementor' ),
	7 => __( 'July', 'apollo-elementor' ),       8 => __( 'August', 'apollo-elementor' ),
	9 => __( 'September', 'apollo-elementor' ), 10 => __( 'October', 'apollo-elementor' ),
	11 => __( 'November', 'apollo-elementor' ), 12 => __( 'December', 'apollo-elementor' ),
];

$weekdays = [
	__( 'Mon', 'apollo-elementor' ), __( 'Tue', 'apollo-elementor' ),
	__( 'Wed', 'apollo-elementor' ), __( 'Thu', 'apollo-elementor' ),
	__( 'Fri', 'apollo-elementor' ), __( 'Sat', 'apollo-elementor' ),
	__( 'Sun', 'apollo-elementor' ),
];

$events_by_date = [];
foreach ( $events as $ev ) {
	$d = $ev['date'] ?? '';
	if ( '' !== $d ) {
		$events_by_date[ $d ][] = $ev;
	}
}

$days_in_month = (int) gmdate( 't', mktime( 0, 0, 0, $month, 1, $year ) );
$first_weekday = ( (int) gmdate( 'N', mktime( 0, 0, 0, $month, 1, $year ) ) ) - 1;
$prev_month_days = (int) gmdate( 't', mktime( 0, 0, 0, $month - 1, 1, $year ) );

$cells = [];
for ( $i = $first_weekday - 1; $i >= 0; $i-- ) {
	$cells[] = [ 'day' => $prev_month_days - $i, 'muted' => true, 'iso' => '' ];
}
for ( $d = 1; $d <= $days_in_month; $d++ ) {
	$iso     = sprintf( '%04d-%02d-%02d', $year, $month, $d );
	$cells[] = [ 'day' => $d, 'muted' => false, 'iso' => $iso ];
}
while ( count( $cells ) % 7 !== 0 ) {
	$nx      = count( $cells ) - ( $first_weekday + $days_in_month ) + 1;
	$cells[] = [ 'day' => $nx, 'muted' => true, 'iso' => '' ];
}

$upcoming = array_filter( $events, static function ( $ev ) use ( $today_iso ) {
	return ( $ev['date'] ?? '' ) >= $today_iso;
} );
$upcoming = array_slice( array_values( $upcoming ), 0, 4 );
?>
<section id="<?php echo esc_attr( $uid ); ?>" class="ae-cal-page" data-a-module="monthly-calendar">

	<!-- header -->
	<header class="ae-cal-head">
		<div>
			<span class="ae-cal-eyebrow"><?php esc_html_e( 'Planner · monthly view', 'apollo-elementor' ); ?></span>
			<h1 class="ae-cal-month">
				<?php echo esc_html( $month_names[ $month ] ?? '' ); ?>
				<span class="ae-cal-yr"><?php echo esc_html( (string) $year ); ?></span>
			</h1>
			<div class="ae-cal-legend">
				<span><i class="ae-cal-legend-event"></i> <?php esc_html_e( 'Apollo event', 'apollo-elementor' ); ?></span>
				<span><i class="ae-cal-legend-today"></i> <?php esc_html_e( 'Today', 'apollo-elementor' ); ?></span>
			</div>
		</div>
	</header>

	<!-- weekdays -->
	<div class="ae-cal-weekdays" aria-hidden="true">
		<?php foreach ( $weekdays as $wd ) : ?>
			<span><?php echo esc_html( $wd ); ?></span>
		<?php endforeach; ?>
	</div>

	<!-- month grid -->
	<div class="ae-cal-grid" role="grid" aria-label="<?php esc_attr_e( 'Month grid', 'apollo-elementor' ); ?>">
		<?php foreach ( $cells as $cell ) :
			$is_today   = ( ! $cell['muted'] && $cell['iso'] === $today_iso );
			$cell_events = ! $cell['muted'] ? ( $events_by_date[ $cell['iso'] ] ?? [] ) : [];
			$classes     = 'ae-cal-cell';
			if ( $cell['muted'] ) {
				$classes .= ' ae-cal-cell-muted';
			}
			if ( $is_today ) {
				$classes .= ' ae-cal-cell-today';
			}
			?>
			<div class="<?php echo esc_attr( $classes ); ?>">
				<span class="ae-cal-dnum"><?php echo esc_html( (string) $cell['day'] ); ?></span>
				<?php if ( ! empty( $cell_events ) ) : ?>
					<div class="ae-cal-chips">
						<?php foreach ( array_slice( $cell_events, 0, 2 ) as $ce ) : ?>
							<span class="ae-cal-chip"><?php echo esc_html( $ce['title'] ?? '' ); ?></span>
						<?php endforeach; ?>
						<?php if ( count( $cell_events ) > 2 ) : ?>
							<span class="ae-cal-more">
								+<?php echo esc_html( (string) ( count( $cell_events ) - 2 ) ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<!-- upcoming -->
	<?php if ( ! empty( $upcoming ) ) : ?>
		<section class="ae-cal-upcoming">
			<span class="ae-cal-upcoming-label"><?php esc_html_e( 'Upcoming this week', 'apollo-elementor' ); ?></span>
			<div class="ae-cal-upcoming-list">
				<?php foreach ( $upcoming as $ue ) :
					$ue_date = $ue['date'] ?? '';
					$ue_day  = $ue_date ? wp_date( 'j', strtotime( $ue_date ) ) : '';
					$ue_mon  = $ue_date ? wp_date( 'M', strtotime( $ue_date ) ) : '';
					?>
					<div class="ae-cal-up-card">
						<div class="ae-cal-up-date">
							<span class="ae-cal-up-d"><?php echo esc_html( $ue_day ); ?></span>
							<span class="ae-cal-up-m"><?php echo esc_html( $ue_mon ); ?></span>
						</div>
						<div class="ae-cal-up-body">
							<div class="ae-cal-up-ttl"><?php echo esc_html( $ue['title'] ?? '' ); ?></div>
							<div class="ae-cal-up-sub"><?php echo esc_html( $ue['venue'] ?? '' ); ?></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

</section>
