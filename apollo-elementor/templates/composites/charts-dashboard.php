<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$event_stats = $data['event_stats'] ?? [];
$global_kpis = $data['global_kpis'] ?? [];

$attendance   = $event_stats['attendance'] ?? 0;
$capacity     = $event_stats['capacity'] ?? 0;
$revenue      = $event_stats['revenue'] ?? 0;
$sell_through = $event_stats['sell_through'] ?? 0;

$live_events  = $global_kpis['live_events'] ?? 0;
$tickets_sold = $global_kpis['tickets_sold'] ?? 0;
$total_rev    = $global_kpis['revenue'] ?? 0;
$no_show      = $global_kpis['no_show_rate'] ?? 0;
?>
<section id="<?php echo esc_attr( $uid ); ?>" class="ae-ch-page" data-a-module="charts-area-line">

	<!-- header -->
	<header class="ae-ch-head">
		<span class="ae-ch-eyebrow"><?php esc_html_e( 'Analytics', 'apollo-elementor' ); ?></span>
		<h1><?php esc_html_e( 'Quiet numbers.', 'apollo-elementor' ); ?></h1>
		<p><?php esc_html_e( 'Native SVG charts. Silver-first. The headline number does the talking.', 'apollo-elementor' ); ?></p>
	</header>

	<div class="ae-ch-grid">

		<!-- area-line chart -->
		<?php if ( ! empty( $event_stats ) ) : ?>
			<section class="ae-ch-card ae-ch-area" data-a-module="charts-area-line">
				<div class="ae-ch-card-head">
					<div>
						<div class="ae-ch-card-tag"><?php esc_html_e( 'Event attendance', 'apollo-elementor' ); ?></div>
						<h3><?php echo esc_html( number_format_i18n( $attendance ) ); ?></h3>
					</div>
					<div>
						<div class="ae-ch-num"><?php echo esc_html( number_format_i18n( $attendance ) ); ?></div>
						<?php if ( $sell_through > 0 ) : ?>
							<div class="ae-ch-delta ae-ch-delta-up">
								<?php
								/* translators: %s: sell-through percentage */
								printf( esc_html__( '%s%% sell-through', 'apollo-elementor' ), esc_html( (string) $sell_through ) );
								?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<svg class="ae-ch-area-svg" viewBox="0 0 800 280" preserveAspectRatio="none" aria-hidden="true">
					<defs>
						<linearGradient id="<?php echo esc_attr( $uid ); ?>-areaFill" x1="0" y1="0" x2="0" y2="1">
							<stop offset="0%" stop-color="rgba(192,192,192,0.55)"/>
							<stop offset="100%" stop-color="rgba(192,192,192,0)"/>
						</linearGradient>
					</defs>
					<path d="M 0 200 C 80 170, 140 130, 200 110 C 270 90, 330 150, 400 130 C 470 110, 540 60, 620 80 C 690 100, 740 70, 800 50 L 800 220 L 0 220 Z"
						  fill="url(#<?php echo esc_attr( $uid ); ?>-areaFill)"/>
					<path d="M 0 200 C 80 170, 140 130, 200 110 C 270 90, 330 150, 400 130 C 470 110, 540 60, 620 80 C 690 100, 740 70, 800 50"
						  fill="none" stroke="rgba(80,80,80,0.85)" stroke-width="2.4" stroke-linecap="round"/>
					<circle cx="800" cy="50" r="6" fill="#FF5C00"/>
				</svg>
			</section>
		<?php endif; ?>

		<!-- donut chart -->
		<?php if ( $capacity > 0 ) : ?>
			<section class="ae-ch-card ae-ch-donut" data-a-module="charts-donut">
				<div class="ae-ch-card-head">
					<div>
						<div class="ae-ch-card-tag"><?php esc_html_e( 'Capacity mix', 'apollo-elementor' ); ?></div>
						<h3><?php esc_html_e( 'By segment', 'apollo-elementor' ); ?></h3>
					</div>
				</div>

				<?php
				$pct  = $capacity > 0 ? round( ( $attendance / $capacity ) * 100, 1 ) : 0;
				$circ = 490;
				$len  = round( $circ * $pct / 100 );
				?>
				<svg class="ae-ch-donut-svg" viewBox="0 0 200 200" aria-hidden="true">
					<circle cx="100" cy="100" r="78" fill="none" stroke="rgba(17,17,17,0.05)" stroke-width="22"/>
					<circle cx="100" cy="100" r="78"
							fill="none" stroke="rgba(150,150,150,0.85)" stroke-width="22"
							stroke-dasharray="<?php echo esc_attr( (string) $len ); ?> <?php echo esc_attr( (string) $circ ); ?>"
							transform="rotate(-90 100 100)"/>
				</svg>

				<div class="ae-ch-donut-center">
					<div class="ae-ch-donut-v"><?php echo esc_html( number_format_i18n( $attendance ) ); ?></div>
					<div class="ae-ch-donut-k"><?php esc_html_e( 'Tickets', 'apollo-elementor' ); ?></div>
				</div>
			</section>
		<?php endif; ?>

		<!-- sparkline grid -->
		<section class="ae-ch-bars" data-a-module="charts-sparklines">
			<div class="ae-ch-card-head">
				<div>
					<div class="ae-ch-card-tag"><?php esc_html_e( 'Quick metrics', 'apollo-elementor' ); ?></div>
					<h3><?php esc_html_e( 'Overview', 'apollo-elementor' ); ?></h3>
				</div>
			</div>

			<div class="ae-ch-bars-grid">
				<div class="ae-ch-spark">
					<span class="ae-ch-spark-k"><?php esc_html_e( 'Live events', 'apollo-elementor' ); ?></span>
					<span class="ae-ch-spark-v"><?php echo esc_html( number_format_i18n( $live_events ) ); ?></span>
				</div>
				<div class="ae-ch-spark">
					<span class="ae-ch-spark-k"><?php esc_html_e( 'Tickets sold', 'apollo-elementor' ); ?></span>
					<span class="ae-ch-spark-v"><?php echo esc_html( number_format_i18n( $tickets_sold ) ); ?></span>
				</div>
				<div class="ae-ch-spark">
					<span class="ae-ch-spark-k"><?php esc_html_e( 'Revenue', 'apollo-elementor' ); ?></span>
					<span class="ae-ch-spark-v">R$ <?php echo esc_html( number_format_i18n( $total_rev ) ); ?></span>
				</div>
				<div class="ae-ch-spark">
					<span class="ae-ch-spark-k"><?php esc_html_e( 'No-show', 'apollo-elementor' ); ?></span>
					<span class="ae-ch-spark-v"><?php echo esc_html( (string) $no_show ); ?>%</span>
				</div>
			</div>
		</section>

	</div>

</section>
