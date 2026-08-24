<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$days       = $data['days'] ?? [];
$today_iso  = current_time( 'Y-m-d' );

$type_config = [
	'appt'   => [ 'label' => __( 'Appointment', 'apollo-elementor' ), 'chip' => '' ],
	'apollo' => [ 'label' => __( 'Apollo Event', 'apollo-elementor' ), 'chip' => 'ae-wk-chip-apollo' ],
	'focus'  => [ 'label' => __( 'Focus', 'apollo-elementor' ), 'chip' => 'ae-wk-chip-focus' ],
	'health' => [ 'label' => __( 'Health', 'apollo-elementor' ), 'chip' => 'ae-wk-chip-health' ],
];
?>
<div id="<?php echo esc_attr( $uid ); ?>" class="ae-wk-app" data-a-module="kanban-drag">

	<!-- topbar -->
	<div class="ae-wk-topbar">
		<span class="ae-wk-tb-brand"><?php esc_html_e( 'Apollo · Planner', 'apollo-elementor' ); ?></span>
		<div class="ae-wk-tb-week">
			<?php if ( ! empty( $days ) ) : ?>
				<span class="ae-wk-tb-range">
					<?php
					echo esc_html(
						wp_date( 'M j', strtotime( $days[0]['date'] ?? '' ) )
						. ' — '
						. wp_date( 'M j · Y', strtotime( $days[ count( $days ) - 1 ]['date'] ?? '' ) )
					);
					?>
				</span>
			<?php endif; ?>
		</div>
		<button class="ae-wk-tb-today" type="button"><?php esc_html_e( 'Today', 'apollo-elementor' ); ?></button>
	</div>

	<!-- pills (mobile day nav) -->
	<nav class="ae-wk-pills" aria-label="<?php esc_attr_e( 'Day navigation', 'apollo-elementor' ); ?>">
		<?php foreach ( $days as $i => $day ) :
			$is_today = ( $day['date'] === $today_iso );
			$item_count = count( $day['items'] ?? [] );
			?>
			<button class="ae-wk-pill<?php echo $is_today ? ' ae-wk-pill-today' : ''; ?>" type="button"
					data-day-index="<?php echo esc_attr( (string) $i ); ?>">
				<?php echo esc_html( ( $day['label'] ?? '' ) . ' ' . ( $day['day'] ?? '' ) ); ?>
				<?php if ( $item_count > 0 ) : ?>
					<span class="ae-wk-pill-badge"><?php echo esc_html( (string) $item_count ); ?></span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</nav>

	<!-- track: 7-day columns -->
	<div class="ae-wk-track" role="region" aria-label="<?php esc_attr_e( 'Weekly planner columns', 'apollo-elementor' ); ?>">
		<?php foreach ( $days as $day ) :
			$is_today = ( $day['date'] === $today_iso );
			$items    = $day['items'] ?? [];
			?>
			<div class="ae-wk-col<?php echo $is_today ? ' ae-wk-col-today' : ''; ?>"
				 data-col="<?php echo esc_attr( $day['date'] ); ?>">

				<div class="ae-wk-col-hdr">
					<div class="ae-wk-col-title">
						<div class="ae-wk-col-name"><?php echo esc_html( $day['label'] ?? '' ); ?></div>
						<div class="ae-wk-col-num"><?php echo esc_html( (string) ( $day['day'] ?? '' ) ); ?></div>
					</div>
					<div class="ae-wk-col-actions">
						<span class="ae-wk-col-count"><?php echo esc_html( (string) count( $items ) ); ?></span>
					</div>
				</div>

				<div class="ae-wk-col-body" data-col="<?php echo esc_attr( $day['date'] ); ?>">
					<?php if ( $is_today ) : ?>
						<div class="ae-wk-nowline" aria-hidden="true"></div>
					<?php endif; ?>

					<?php if ( empty( $items ) ) : ?>
						<div class="ae-wk-empty"><?php esc_html_e( 'Free day', 'apollo-elementor' ); ?></div>
					<?php else : ?>
						<?php foreach ( $items as $item ) :
							$type   = $item['type'] ?? 'appt';
							$config = $type_config[ $type ] ?? $type_config['appt'];
							?>
							<div class="ae-wk-card" data-id="<?php echo esc_attr( (string) ( $item['id'] ?? '' ) ); ?>">
								<div class="ae-wk-card-stripe" style="background: var(--accent)"></div>
								<div class="ae-wk-card-body">
									<div class="ae-wk-card-time">
										<?php echo esc_html( ( $item['time_start'] ?? '' ) . ' — ' . ( $item['time_end'] ?? '' ) ); ?>
									</div>
									<div class="ae-wk-card-title"><?php echo esc_html( $item['title'] ?? '' ); ?></div>
									<?php if ( ! empty( $item['venue'] ) ) : ?>
										<div class="ae-wk-card-venue"><?php echo esc_html( $item['venue'] ); ?></div>
									<?php endif; ?>
									<?php if ( ( $item['progress'] ?? 0 ) > 0 ) : ?>
										<div class="ae-wk-card-prog">
											<div class="ae-wk-card-prog-bar" style="width: <?php echo esc_attr( (string) $item['progress'] ); ?>%"></div>
										</div>
									<?php endif; ?>
								</div>
								<div class="ae-wk-card-foot">
									<span class="ae-wk-card-chip <?php echo esc_attr( $config['chip'] ); ?>">
										<?php echo esc_html( $config['label'] ); ?>
									</span>
									<div class="ae-wk-drag-grip" aria-hidden="true">
										<span></span><span></span><span></span><span></span><span></span><span></span>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

			</div>
		<?php endforeach; ?>
	</div>

	<!-- summary strip -->
	<div class="ae-wk-summary">
		<?php
		$today_items = [];
		foreach ( $days as $day ) {
			if ( $day['date'] === $today_iso ) {
				$today_items = $day['items'] ?? [];
				break;
			}
		}
		$appts_today  = count( array_filter( $today_items, static fn( $i ) => in_array( $i['type'] ?? '', [ 'appt', 'personal' ], true ) ) );
		$events_today = count( array_filter( $today_items, static fn( $i ) => ( $i['type'] ?? '' ) === 'apollo' ) );
		$focus_today  = count( array_filter( $today_items, static fn( $i ) => ( $i['type'] ?? '' ) === 'focus' ) );
		?>
		<div class="ae-wk-sum-cell">
			<span class="ae-wk-sum-k"><?php esc_html_e( 'Appointments', 'apollo-elementor' ); ?></span>
			<span class="ae-wk-sum-v"><?php echo esc_html( $appts_today > 0 ? (string) $appts_today : '—' ); ?></span>
			<span class="ae-wk-sum-s"><?php echo esc_html( $appts_today > 0 ? $appts_today . ' today' : __( 'Free today', 'apollo-elementor' ) ); ?></span>
		</div>
		<div class="ae-wk-sum-cell">
			<span class="ae-wk-sum-k"><?php esc_html_e( 'Apollo Events', 'apollo-elementor' ); ?></span>
			<span class="ae-wk-sum-v"><?php echo esc_html( $events_today > 0 ? (string) $events_today : '—' ); ?></span>
			<span class="ae-wk-sum-s"><?php echo esc_html( $events_today > 0 ? __( 'Today', 'apollo-elementor' ) : __( 'None today', 'apollo-elementor' ) ); ?></span>
		</div>
		<div class="ae-wk-sum-cell">
			<span class="ae-wk-sum-k"><?php esc_html_e( 'Focus Blocks', 'apollo-elementor' ); ?></span>
			<span class="ae-wk-sum-v"><?php echo esc_html( $focus_today > 0 ? (string) $focus_today : '—' ); ?></span>
			<span class="ae-wk-sum-s"><?php echo esc_html( $focus_today > 0 ? __( 'Reserved', 'apollo-elementor' ) : __( 'None blocked', 'apollo-elementor' ) ); ?></span>
		</div>
		<div class="ae-wk-sum-cell">
			<span class="ae-wk-sum-k"><?php esc_html_e( 'Total this week', 'apollo-elementor' ); ?></span>
			<span class="ae-wk-sum-v"><?php echo esc_html( (string) array_sum( array_map( static fn( $d ) => count( $d['items'] ?? [] ), $days ) ) ); ?></span>
			<span class="ae-wk-sum-s"><?php esc_html_e( 'All items', 'apollo-elementor' ); ?></span>
		</div>
	</div>

</div>
