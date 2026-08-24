<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$global_kpis = $data['global_kpis'] ?? [];
$events      = $data['events'] ?? [];

$live_events  = $global_kpis['live_events'] ?? 0;
$tickets_sold = $global_kpis['tickets_sold'] ?? 0;
$revenue      = $global_kpis['revenue'] ?? 0;
$no_show      = $global_kpis['no_show_rate'] ?? 0;
?>
<section id="<?php echo esc_attr( $uid ); ?>" class="ae-gs-page" data-a-module="gestor-dashboard">

	<!-- header -->
	<header class="ae-gs-head">
		<div>
			<span class="ae-gs-eyebrow"><?php esc_html_e( 'Gestor · all events', 'apollo-elementor' ); ?></span>
			<h1><?php esc_html_e( 'Manage everything, quietly.', 'apollo-elementor' ); ?></h1>
		</div>
		<div class="ae-gs-head-actions">
			<button class="ae-gs-btn" type="button"><?php esc_html_e( 'Filters', 'apollo-elementor' ); ?></button>
			<button class="ae-gs-btn ae-accent-bg" type="button"><?php esc_html_e( 'New event', 'apollo-elementor' ); ?></button>
		</div>
	</header>

	<!-- command bar -->
	<div class="ae-gs-cmd" data-a-module="gestor-command">
		<span class="ae-gs-cmd-icon" aria-hidden="true"></span>
		<input type="text"
			   placeholder="<?php esc_attr_e( 'Search events, venues, DJs…', 'apollo-elementor' ); ?>"
			   aria-label="<?php esc_attr_e( 'Search', 'apollo-elementor' ); ?>">
		<span class="ae-gs-cmd-kbd">⌘K</span>
	</div>

	<!-- KPI strip -->
	<div class="ae-gs-kpis" data-a-module="gestor-kpis">
		<div class="ae-gs-kpi">
			<span class="ae-gs-kpi-k"><?php esc_html_e( 'Live events', 'apollo-elementor' ); ?></span>
			<span class="ae-gs-kpi-v"><?php echo esc_html( number_format_i18n( $live_events ) ); ?></span>
		</div>
		<div class="ae-gs-kpi">
			<span class="ae-gs-kpi-k"><?php esc_html_e( 'Tickets sold', 'apollo-elementor' ); ?></span>
			<span class="ae-gs-kpi-v"><?php echo esc_html( number_format_i18n( $tickets_sold ) ); ?></span>
		</div>
		<div class="ae-gs-kpi">
			<span class="ae-gs-kpi-k"><?php esc_html_e( 'Revenue (R$)', 'apollo-elementor' ); ?></span>
			<span class="ae-gs-kpi-v">R$ <?php echo esc_html( number_format_i18n( $revenue ) ); ?></span>
		</div>
		<div class="ae-gs-kpi">
			<span class="ae-gs-kpi-k"><?php esc_html_e( 'No-show rate', 'apollo-elementor' ); ?></span>
			<span class="ae-gs-kpi-v"><?php echo esc_html( (string) $no_show ); ?>%</span>
		</div>
	</div>

	<div class="ae-gs-layout">

		<!-- side rail -->
		<aside class="ae-gs-rail" data-a-module="gestor-filters">
			<div class="ae-gs-rail-group">
				<span class="ae-gs-rail-label"><?php esc_html_e( 'Status', 'apollo-elementor' ); ?></span>
				<div class="ae-gs-rail-item ae-gs-rail-item-active">
					<span><?php esc_html_e( 'All events', 'apollo-elementor' ); ?></span>
					<span class="ae-gs-rail-ct"><?php echo esc_html( (string) count( $events ) ); ?></span>
				</div>
			</div>
		</aside>

		<!-- content -->
		<div class="ae-gs-content">

			<!-- events table -->
			<?php if ( ! empty( $events ) ) : ?>
				<section class="ae-gs-table" data-a-module="gestor-table">
					<div class="ae-gs-table-head">
						<span><?php esc_html_e( 'Event', 'apollo-elementor' ); ?></span>
						<span><?php esc_html_e( 'Venue', 'apollo-elementor' ); ?></span>
						<span><?php esc_html_e( 'When', 'apollo-elementor' ); ?></span>
						<span><?php esc_html_e( 'Tickets', 'apollo-elementor' ); ?></span>
						<span><?php esc_html_e( 'Status', 'apollo-elementor' ); ?></span>
						<span></span>
					</div>
					<?php foreach ( $events as $ev ) :
						$ev_title     = $ev['title'] ?? '';
						$ev_venue     = $ev['venue'] ?? '';
						$ev_date      = $ev['date'] ?? '';
						$ev_time      = $ev['time_start'] ?? '';
						$ev_rsvp      = $ev['rsvp_count'] ?? 0;
						$ev_cap       = $ev['capacity'] ?? 0;
						$ev_status    = $ev['status'] ?? 'draft';
						$ev_thumbnail = $ev['thumbnail'] ?? '';
						$ev_formatted = $ev_date ? wp_date( 'D · j M · Y', strtotime( $ev_date ) ) : '';
						?>
						<div class="ae-gs-row">
							<div class="ae-gs-row-title">
								<?php if ( $ev_thumbnail ) : ?>
									<img class="ae-gs-row-cover"
										 src="<?php echo esc_url( $ev_thumbnail ); ?>"
										 alt="<?php echo esc_attr( $ev_title ); ?>"
										 width="44" height="44" loading="lazy">
								<?php endif; ?>
								<div>
									<div class="ae-gs-row-tt"><?php echo esc_html( $ev_title ); ?></div>
								</div>
							</div>
							<div class="ae-gs-row-cell"><?php echo esc_html( $ev_venue ); ?></div>
							<div class="ae-gs-row-cell-mono">
								<?php echo esc_html( $ev_formatted ); ?>
								<?php if ( $ev_time ) : ?>
									&middot; <?php echo esc_html( $ev_time ); ?>
								<?php endif; ?>
							</div>
							<div class="ae-gs-row-cell-mono">
								<?php echo esc_html( (string) $ev_rsvp ); ?> / <?php echo esc_html( (string) $ev_cap ); ?>
							</div>
							<div>
								<span class="ae-gs-chip ae-gs-chip-<?php echo esc_attr( $ev_status ); ?>">
									<?php echo esc_html( ucfirst( $ev_status ) ); ?>
								</span>
							</div>
							<span class="ae-gs-row-more" aria-hidden="true">&middot;&middot;&middot;</span>
						</div>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>

			<!-- activity timeline + map -->
			<div class="ae-gs-twocol">
				<section class="ae-gs-panel" data-a-module="gestor-timeline">
					<div class="ae-gs-panel-head">
						<h3><?php esc_html_e( 'Activity', 'apollo-elementor' ); ?></h3>
					</div>
					<div class="ae-gs-tl">
						<div class="ae-gs-tl-row">
							<span class="ae-gs-tl-time"><?php esc_html_e( 'Now', 'apollo-elementor' ); ?></span>
							<div class="ae-gs-tl-body">
								<span class="ae-gs-tl-ev"><?php esc_html_e( 'Dashboard loaded', 'apollo-elementor' ); ?></span>
								<span class="ae-gs-tl-sub"><?php echo esc_html( (string) count( $events ) ); ?> <?php esc_html_e( 'events', 'apollo-elementor' ); ?></span>
							</div>
						</div>
					</div>
				</section>

				<section class="ae-gs-panel" data-a-module="gestor-map">
					<div class="ae-gs-panel-head">
						<h3><?php esc_html_e( 'Venues', 'apollo-elementor' ); ?></h3>
					</div>
					<div class="ae-gs-map">
						<span class="ae-gs-map-lbl"><?php esc_html_e( 'Active venues', 'apollo-elementor' ); ?></span>
						<div>
							<span class="ae-gs-map-pin ae-gs-map-pin-a" aria-hidden="true"></span>
							<span class="ae-gs-map-pin ae-gs-map-pin-b" aria-hidden="true"></span>
						</div>
					</div>
				</section>
			</div>

		</div>

	</div>

</section>
