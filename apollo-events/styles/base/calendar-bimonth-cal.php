<?php
/**
 * Template: Bi-Month Calendar — Base Style
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

$meses_pt = array(
	1  => 'Janeiro',
	2  => 'Fevereiro',
	3  => 'Março',
	4  => 'Abril',
	5  => 'Maio',
	6  => 'Junho',
	7  => 'Julho',
	8  => 'Agosto',
	9  => 'Setembro',
	10 => 'Outubro',
	11 => 'Novembro',
	12 => 'Dezembro',
);

$dias_semana = array( 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom' );
$today       = current_time( 'Y-m-d' );
?>

<div class="a-eve-calendar a-eve-calendar--bimonth-cal">
	<?php
	for ( $m = 0; $m < 2; $m++ ) :
		$cm = $month + $m;
		$cy = $year;
		if ( $cm > 12 ) {
			$cm -= 12;
			++$cy; }

		$first     = mktime( 0, 0, 0, $cm, 1, $cy );
		$days_in   = (int) date( 't', $first );
		$start_dow = ( (int) date( 'N', $first ) ) - 1;
		?>
		<div class="a-eve-cal-month">
			<div class="a-eve-cal-header">
				<h3 class="a-eve-cal-title"><?php echo esc_html( $meses_pt[ $cm ] . ' ' . $cy ); ?></h3>
			</div>

			<div class="a-eve-cal-grid">
				<?php foreach ( $dias_semana as $d ) : ?>
					<div class="a-eve-cal-dow"><?php echo esc_html( $d ); ?></div>
				<?php endforeach; ?>

				<?php for ( $i = 0; $i < $start_dow; $i++ ) : ?>
					<div class="a-eve-cal-day a-eve-cal-day--empty"></div>
				<?php endfor; ?>

				<?php
				for ( $day = 1; $day <= $days_in; $day++ ) :
					$date_key  = sprintf( '%04d-%02d-%02d', $cy, $cm, $day );
					$has_event = isset( $events_by_date[ $date_key ] );
					$is_today  = $date_key === $today;
					$count     = $has_event ? count( $events_by_date[ $date_key ] ) : 0;

					$classes = 'a-eve-cal-day';
					if ( $has_event ) {
						$classes .= ' a-eve-cal-day--has-event';
					}
					if ( $is_today ) {
						$classes .= ' a-eve-cal-day--today';
					}
					?>
					<div class="<?php echo esc_attr( $classes ); ?>" data-date="<?php echo esc_attr( $date_key ); ?>">
						<span class="a-eve-cal-day-num"><?php echo esc_html( $day ); ?></span>
						<?php if ( $has_event ) : ?>
							<span class="a-eve-cal-dot" title="<?php echo esc_attr( $count . ' evento(s)' ); ?>"></span>
						<?php endif; ?>
					</div>
				<?php endfor; ?>
			</div>
		</div>
	<?php endfor; ?>
</div>
