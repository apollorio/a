<?php
/**
 * Template: Month Calendar — Base Style
 *
 * Variáveis disponíveis:
 *
 * @var int   $year
 * @var int   $month
 * @var array $events_by_date  [ 'Y-m-d' => [ post_id, ... ], ... ]
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

$first_day = mktime( 0, 0, 0, $month, 1, $year );
$days_in   = (int) date( 't', $first_day );
$start_dow = ( (int) date( 'N', $first_day ) ) - 1;
$today     = current_time( 'Y-m-d' );

$prev_month = $month - 1;
$prev_year  = $year;
if ( $prev_month < 1 ) {
	$prev_month = 12;
	--$prev_year; }

$next_month = $month + 1;
$next_year  = $year;
if ( $next_month > 12 ) {
	$next_month = 1;
	++$next_year; }
?>

<div class="a-eve-calendar a-eve-calendar--month-cal">
	<div class="a-eve-cal-month">
		<div class="a-eve-cal-header">
			<button class="a-eve-cal-nav a-eve-cal-nav--prev"
				data-month="<?php echo esc_attr( $prev_month ); ?>"
				data-year="<?php echo esc_attr( $prev_year ); ?>"
				aria-label="<?php esc_attr_e( 'Mês anterior', 'apollo-events' ); ?>">←</button>
			<h3 class="a-eve-cal-title"><?php echo esc_html( $meses_pt[ $month ] . ' ' . $year ); ?></h3>
			<button class="a-eve-cal-nav a-eve-cal-nav--next"
				data-month="<?php echo esc_attr( $next_month ); ?>"
				data-year="<?php echo esc_attr( $next_year ); ?>"
				aria-label="<?php esc_attr_e( 'Próximo mês', 'apollo-events' ); ?>">→</button>
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
				$date_key  = sprintf( '%04d-%02d-%02d', $year, $month, $day );
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
</div>
