<?php
/**
 * Template: Week Calendar — Base Style
 *
 * @var int   $year
 * @var int   $month
 * @var array $events_by_date
 * @var string $style
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dias_semana_full = array( 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo' );

$today       = current_time( 'Y-m-d' );
$today_ts    = strtotime( $today );
$day_of_week = ( (int) date( 'N', $today_ts ) ) - 1; // 0=Seg, 6=Dom

// Calcular início da semana (segunda)
$week_start = strtotime( "-{$day_of_week} days", $today_ts );
?>

<div class="a-eve-calendar a-eve-calendar--week-cal">
	<div class="a-eve-cal-week">
		<div class="a-eve-cal-header">
			<button class="a-eve-cal-nav a-eve-cal-nav--prev" aria-label="<?php esc_attr_e( 'Semana anterior', 'apollo-events' ); ?>">←</button>
			<h3 class="a-eve-cal-title">
				<?php
				$week_end = strtotime( '+6 days', $week_start );
				echo esc_html( date( 'd/m', $week_start ) . ' – ' . date( 'd/m/Y', $week_end ) );
				?>
			</h3>
			<button class="a-eve-cal-nav a-eve-cal-nav--next" aria-label="<?php esc_attr_e( 'Próxima semana', 'apollo-events' ); ?>">→</button>
		</div>

		<div class="a-eve-cal-week-grid">
			<?php
			for ( $d = 0; $d < 7; $d++ ) :
				$day_ts    = strtotime( "+{$d} days", $week_start );
				$date_key  = date( 'Y-m-d', $day_ts );
				$has_event = isset( $events_by_date[ $date_key ] );
				$is_today  = $date_key === $today;

				$classes = 'a-eve-cal-week-day';
				if ( $has_event ) {
					$classes .= ' a-eve-cal-day--has-event';
				}
				if ( $is_today ) {
					$classes .= ' a-eve-cal-day--today';
				}
				?>
				<div class="<?php echo esc_attr( $classes ); ?>" data-date="<?php echo esc_attr( $date_key ); ?>">
					<div class="a-eve-cal-week-day__header">
						<span class="a-eve-cal-week-day__name"><?php echo esc_html( $dias_semana_full[ $d ] ); ?></span>
						<span class="a-eve-cal-week-day__num"><?php echo esc_html( date( 'd', $day_ts ) ); ?></span>
					</div>
					<?php
					if ( $has_event ) :
						foreach ( $events_by_date[ $date_key ] as $event_id ) :
							$time = get_post_meta( $event_id, '_event_start_time', true );
							?>
						<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="a-eve-cal-week-event">
							<?php if ( $time ) : ?>
								<span class="a-eve-cal-week-event__time"><?php echo esc_html( $time ); ?></span>
							<?php endif; ?>
							<span class="a-eve-cal-week-event__title"><?php echo esc_html( get_the_title( $event_id ) ); ?></span>
						</a>
							<?php
					endforeach;
endif;
					?>
				</div>
			<?php endfor; ?>
		</div>
	</div>
</div>
