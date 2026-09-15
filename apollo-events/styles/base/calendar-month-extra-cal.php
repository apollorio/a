<?php
/**
 * Template: Month Extra Calendar — Base Style
 * Calendário mensal com sidebar de eventos do dia selecionado.
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

$first_day = mktime( 0, 0, 0, $month, 1, $year );
$days_in   = (int) date( 't', $first_day );
$start_dow = ( (int) date( 'N', $first_day ) ) - 1;
$today     = current_time( 'Y-m-d' );
?>

<div class="a-eve-calendar a-eve-calendar--month-extra-cal">
	<div class="a-eve-cal-extra-layout">
		<!-- Calendário -->
		<div class="a-eve-cal-month">
			<div class="a-eve-cal-header">
				<button class="a-eve-cal-nav a-eve-cal-nav--prev" aria-label="<?php esc_attr_e( 'Mês anterior', 'apollo-events' ); ?>">←</button>
				<h3 class="a-eve-cal-title"><?php echo esc_html( $meses_pt[ $month ] . ' ' . $year ); ?></h3>
				<button class="a-eve-cal-nav a-eve-cal-nav--next" aria-label="<?php esc_attr_e( 'Próximo mês', 'apollo-events' ); ?>">→</button>
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
						$classes .= ' a-eve-cal-day--today a-eve-cal-day--selected';
					}
					?>
					<div class="<?php echo esc_attr( $classes ); ?>" data-date="<?php echo esc_attr( $date_key ); ?>">
						<span class="a-eve-cal-day-num"><?php echo esc_html( $day ); ?></span>
						<?php if ( $has_event ) : ?>
							<span class="a-eve-cal-dot" title="<?php echo esc_attr( $count . ' evento(s)' ); ?>">
								<?php echo esc_html( $count ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endfor; ?>
			</div>
		</div>

		<!-- Sidebar: eventos do dia selecionado -->
		<div class="a-eve-cal-extra-sidebar" id="a-eve-cal-sidebar">
			<h4 class="a-eve-cal-sidebar-title"><?php esc_html_e( 'Eventos do Dia', 'apollo-events' ); ?></h4>
			<div class="a-eve-cal-sidebar-content">
				<?php
				// Mostrar eventos de hoje por padrão
				if ( isset( $events_by_date[ $today ] ) ) {
					foreach ( $events_by_date[ $today ] as $event_id ) {
						$time     = get_post_meta( $event_id, '_event_start_time', true );
						$loc_id   = get_post_meta( $event_id, '_event_loc_id', true );
						$loc_name = $loc_id ? get_the_title( $loc_id ) : '';
						?>
						<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="a-eve-cal-sidebar-event">
							<strong><?php echo esc_html( get_the_title( $event_id ) ); ?></strong>
							<?php if ( $time ) : ?>
								<span>🕐 <?php echo esc_html( $time ); ?></span>
							<?php endif; ?>
							<?php if ( $loc_name ) : ?>
								<span>📍 <?php echo esc_html( $loc_name ); ?></span>
							<?php endif; ?>
						</a>
						<?php
					}
				} else {
					echo '<p class="a-eve-cal-sidebar-empty">' . esc_html__( 'Nenhum evento neste dia.', 'apollo-events' ) . '</p>';
				}
				?>
			</div>
		</div>
	</div>
</div>
