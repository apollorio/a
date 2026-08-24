<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$event = $data['event'] ?? [];

$title      = $event['title'] ?? '';
$excerpt    = $event['excerpt'] ?? '';
$date       = $event['date'] ?? '';
$time_start = $event['time_start'] ?? '';
$time_end   = $event['time_end'] ?? '';
$venue      = $event['venue'] ?? '';
$venue_addr = $event['venue_addr'] ?? '';
$city       = $event['city'] ?? '';
$capacity   = $event['capacity'] ?? 0;
$rsvp_count = $event['rsvp_count'] ?? 0;
$lineup     = $event['lineup'] ?? [];
$thumbnail  = $event['thumbnail'] ?? '';

if ( empty( $title ) ) {
	return;
}

$formatted_date = $date ? wp_date( 'D · j M', strtotime( $date ) ) : '';
?>
<article id="<?php echo esc_attr( $uid ); ?>" class="ae-ev-page" data-a-module="premium-singles">

	<!-- hero -->
	<section class="ae-ev-hero" aria-label="<?php echo esc_attr( $title ); ?>">
		<?php if ( $thumbnail ) : ?>
			<img class="ae-ev-hero-img"
				 src="<?php echo esc_url( $thumbnail ); ?>"
				 alt="<?php echo esc_attr( $title ); ?>"
				 loading="eager">
		<?php endif; ?>
		<div class="ae-ev-hero-grad" aria-hidden="true"></div>

		<div class="ae-ev-hero-card">
			<?php if ( $formatted_date || $time_start ) : ?>
				<span class="ae-ev-eyebrow">
					<?php echo esc_html( $formatted_date ); ?>
					<?php if ( $time_start ) : ?>
						&middot; <?php echo esc_html( $time_start ); ?>
					<?php endif; ?>
				</span>
			<?php endif; ?>
			<h1 class="ae-ev-title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( ! empty( $lineup ) && ! empty( $lineup[0] ) ) : ?>
				<div class="ae-ev-dj-stamp">
					<?php if ( ! empty( $lineup[0]['avatar_url'] ) ) : ?>
						<img class="ae-ev-dj-avatar"
							 src="<?php echo esc_url( $lineup[0]['avatar_url'] ); ?>"
							 alt="<?php echo esc_attr( $lineup[0]['name'] ?? '' ); ?>"
							 width="54" height="54" loading="lazy">
					<?php endif; ?>
					<div>
						<div class="ae-ev-dj-name"><?php echo esc_html( $lineup[0]['name'] ?? '' ); ?></div>
						<?php if ( ! empty( $lineup[0]['role'] ) ) : ?>
							<div class="ae-ev-dj-role"><?php echo esc_html( $lineup[0]['role'] ); ?></div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- meta-strip -->
	<section class="ae-ev-meta" aria-label="<?php esc_attr_e( 'Event meta', 'apollo-elementor' ); ?>">
		<?php if ( $formatted_date ) : ?>
			<div class="ae-ev-meta-cell">
				<span class="ae-ev-meta-k"><?php esc_html_e( 'When', 'apollo-elementor' ); ?></span>
				<span class="ae-ev-meta-v"><?php echo esc_html( $formatted_date ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( $venue ) : ?>
			<div class="ae-ev-meta-cell">
				<span class="ae-ev-meta-k"><?php esc_html_e( 'Venue', 'apollo-elementor' ); ?></span>
				<span class="ae-ev-meta-v"><?php echo esc_html( $venue ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( $city ) : ?>
			<div class="ae-ev-meta-cell">
				<span class="ae-ev-meta-k"><?php esc_html_e( 'City', 'apollo-elementor' ); ?></span>
				<span class="ae-ev-meta-v"><?php echo esc_html( $city ); ?></span>
			</div>
		<?php endif; ?>
	</section>

	<div class="ae-ev-frame">

		<!-- lineup -->
		<?php if ( ! empty( $lineup ) ) : ?>
			<section class="ae-ev-block">
				<span class="ae-ev-block-label"><?php esc_html_e( 'Lineup', 'apollo-elementor' ); ?></span>
				<h2><?php esc_html_e( 'The lineup.', 'apollo-elementor' ); ?></h2>
				<?php if ( $excerpt ) : ?>
					<p class="ae-ev-block-lead"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
				<div class="ae-ev-lineup">
					<?php foreach ( $lineup as $act ) : ?>
						<div class="ae-ev-line">
							<?php if ( ! empty( $act['avatar_url'] ) ) : ?>
								<img class="ae-ev-line-art"
									 src="<?php echo esc_url( $act['avatar_url'] ); ?>"
									 alt="<?php echo esc_attr( $act['name'] ?? '' ); ?>"
									 width="56" height="56" loading="lazy">
							<?php else : ?>
								<span class="ae-ev-line-art" aria-hidden="true"></span>
							<?php endif; ?>
							<div class="ae-ev-line-body">
								<div class="ae-ev-line-nm"><?php echo esc_html( $act['name'] ?? '' ); ?></div>
								<?php if ( ! empty( $act['role'] ) ) : ?>
									<div class="ae-ev-line-rl"><?php echo esc_html( $act['role'] ); ?></div>
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $act['time_start'] ) ) : ?>
								<span class="ae-ev-line-time">
									<?php echo esc_html( $act['time_start'] ); ?>
									<?php if ( ! empty( $act['time_end'] ) ) : ?>
										— <?php echo esc_html( $act['time_end'] ); ?>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- location -->
		<?php if ( $venue ) : ?>
			<section class="ae-ev-block">
				<span class="ae-ev-block-label"><?php esc_html_e( 'Location', 'apollo-elementor' ); ?></span>
				<h2><?php echo esc_html( $venue ); ?><?php if ( $city ) : ?>, <?php echo esc_html( $city ); ?><?php endif; ?>.</h2>
				<?php if ( $capacity > 0 ) : ?>
					<p class="ae-ev-block-lead">
						<?php
						/* translators: %d: venue capacity */
						printf( esc_html__( 'Capacity %d.', 'apollo-elementor' ), $capacity );
						?>
						<?php if ( $time_start ) : ?>
							<?php
							/* translators: %s: door time */
							printf( esc_html__( 'Doors at %s.', 'apollo-elementor' ), esc_html( $time_start ) );
							?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
				<div class="ae-ev-venue">
					<div class="ae-ev-venue-body">
						<div>
							<h3><?php echo esc_html( $venue ); ?></h3>
							<?php if ( $venue_addr ) : ?>
								<div class="ae-ev-venue-addr"><?php echo esc_html( $venue_addr ); ?></div>
							<?php endif; ?>
						</div>
					</div>
					<div class="ae-ev-venue-map" aria-hidden="true">
						<span class="ae-ev-venue-pin"></span>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- share-cta -->
		<section class="ae-ev-block">
			<span class="ae-ev-block-label"><?php esc_html_e( 'Reserve', 'apollo-elementor' ); ?></span>
			<h2><?php esc_html_e( 'Save your place.', 'apollo-elementor' ); ?></h2>
			<p class="ae-ev-block-lead"><?php esc_html_e( 'Apollo manages the guest list. Reserve once — we keep the rest invisible.', 'apollo-elementor' ); ?></p>
			<div class="ae-ev-cta-row">
				<button class="ae-ev-btn ae-accent-bg" type="button">
					<?php esc_html_e( 'Reserve a spot', 'apollo-elementor' ); ?>
				</button>
				<button class="ae-ev-btn" type="button">
					<?php esc_html_e( 'Save event', 'apollo-elementor' ); ?>
				</button>
				<button class="ae-ev-btn ae-ev-btn-ghost" type="button">
					<?php esc_html_e( 'Share', 'apollo-elementor' ); ?>
				</button>
			</div>
		</section>

		<!-- related placeholder -->
		<section class="ae-ev-block">
			<span class="ae-ev-block-label"><?php esc_html_e( 'Related', 'apollo-elementor' ); ?></span>
			<h2><?php esc_html_e( 'More from Apollo.', 'apollo-elementor' ); ?></h2>
			<div class="ae-ev-related" aria-label="<?php esc_attr_e( 'Related events', 'apollo-elementor' ); ?>"></div>
		</section>

	</div>

</article>
