<?php

/**
 * Template Part: Events Archive — Event Card
 *
 * Apollo ap-card shape with clip-path, date badge, banner, meta, sound tags, footer.
 * Each card outputs as a filterable article element with data-* attributes for JS filtering.
 *
 * Expects: $ev (array) — single event data from parent loop.
 *
 * @package Apollo\Event
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $ev ) || ! is_array( $ev ) ) {
	return;
}

// Parse date
$parsed = array();
if ( ! empty( $ev['start_date'] ) ) {
	$ts = strtotime( $ev['start_date'] );
	if ( $ts ) {
		$months_pt       = array(
			1  => 'jan',
			2  => 'fev',
			3  => 'mar',
			4  => 'abr',
			5  => 'mai',
			6  => 'jun',
			7  => 'jul',
			8  => 'ago',
			9  => 'set',
			10 => 'out',
			11 => 'nov',
			12 => 'dez',
		);
		$parsed['day']   = date( 'j', $ts );
		$parsed['month'] = $months_pt[ (int) date( 'n', $ts ) ] ?? date( 'M', $ts );
		$parsed['iso']   = date( 'Y-m-d', $ts );
		$parsed['week']  = date( 'W', $ts );
	}
}

$is_gone  = ! empty( $ev['is_gone'] );
$is_priv  = ( $ev['privacy'] ?? 'public' ) === 'private';
$sounds   = $ev['sounds'] ?? array();
$s_slugs  = $ev['sound_slugs'] ?? array();
$cats     = $ev['categories'] ?? array();
$card_cls = 'ev-card';
if ( $is_gone ) {
	$card_cls .= ' ev-card--gone';
}
?>
<article class="<?php echo esc_attr( $card_cls ); ?>" data-id="<?php echo esc_attr( $ev['id'] ); ?>"
	data-sounds="<?php echo esc_attr( implode( ',', $s_slugs ) ); ?>"
	data-cats="<?php echo esc_attr( implode( ',', array_map( 'sanitize_title', $cats ) ) ); ?>"
	data-date="<?php echo esc_attr( $parsed['iso'] ?? '' ); ?>"
	data-week="<?php echo esc_attr( $parsed['week'] ?? '' ); ?>" data-search="
	<?php
	echo esc_attr(
		mb_strtolower(
			( $ev['title'] ?? '' ) . ' ' .
																							( $ev['dj_names'] ?? '' ) . ' ' .
																							( $ev['loc_name'] ?? '' ) . ' ' .
																							implode( ' ', $sounds )
		)
	);
	?>
																				">
	<!-- Banner Link — data-ev-open makes it open the single event page in the
	     lightbox; href stays the real permalink so ctrl/middle-click, "open in
	     new tab" and crawlers all keep working. -->
	<a href="<?php echo esc_url( $ev['url'] ?? '#' ); ?>" class="ev-card__link"
		data-ev-open="<?php echo esc_attr( (string) ( $ev['id'] ?? '' ) ); ?>"
		aria-label="<?php echo esc_attr( $ev['title'] ?? '' ); ?>">
		<div class="ev-card__banner">
			<?php if ( ! empty( $ev['banner'] ) ) : ?>
				<img src="<?php echo esc_url( $ev['banner'] ); ?>" alt="<?php echo esc_attr( $ev['title'] ?? '' ); ?>"
					loading="lazy" decoding="async">
			<?php else : ?>
				<div class="ev-card__banner-placeholder">
					<i class="ri-calendar-event-line"></i>
				</div>
			<?php endif; ?>

			<?php if ( $is_gone ) : ?>
				<div class="ev-card__gone-overlay"><?php esc_html_e( 'Finalizado', 'apollo-events' ); ?></div>
			<?php endif; ?>
		</div>
	</a>

	<!-- Date Badge -->
	<?php if ( ! empty( $parsed ) ) : ?>
		<div class="ev-card__badge">
			<span class="ev-card__badge-day"><?php echo esc_html( $parsed['day'] ); ?></span>
			<span class="ev-card__badge-month"><?php echo esc_html( $parsed['month'] ); ?></span>
		</div>
	<?php endif; ?>

	<!-- Privacy Badge -->
	<?php if ( $is_priv ) : ?>
		<div class="ev-card__priv" title="Evento privado">
			<i class="ri-lock-line"></i>
		</div>
	<?php endif; ?>

	<!-- Body -->
	<div class="ev-card__body">

		<!-- Title -->
		<h3 class="ev-card__title">
			<a href="<?php echo esc_url( $ev['url'] ?? '#' ); ?>"
				data-ev-open="<?php echo esc_attr( (string) ( $ev['id'] ?? '' ) ); ?>">
				<?php echo esc_html( $ev['title'] ?? 'Sem título' ); ?>
			</a>
		</h3>

		<!-- Meta -->
		<div class="ev-card__meta">
			<?php if ( ! empty( $ev['start_time'] ) ) : ?>
				<span class="ev-card__meta-item">
					<i class="ri-time-line"></i>
					<span>
						<?php echo esc_html( $ev['start_time'] ); ?>
						<?php if ( ! empty( $ev['end_time'] ) ) : ?>
							– <?php echo esc_html( $ev['end_time'] ); ?>
						<?php endif; ?>
					</span>
				</span>
			<?php endif; ?>

			<?php if ( ! empty( $ev['loc_name'] ) ) : ?>
				<span class="ev-card__meta-item">
					<i class="ri-map-pin-line"></i>
					<span><?php echo esc_html( $ev['loc_name'] ); ?></span>
				</span>
			<?php endif; ?>

			<?php if ( ! empty( $ev['dj_names'] ) ) : ?>
				<span class="ev-card__meta-item">
					<i class="ri-disc-line"></i>
					<span><?php echo esc_html( $ev['dj_names'] ); ?></span>
				</span>
			<?php endif; ?>
		</div>

		<!-- Sound Tags -->
		<?php if ( ! empty( $sounds ) ) : ?>
			<div class="ev-card__sounds">
				<?php
				$max_tags  = 3;
				$show_tags = array_slice( $sounds, 0, $max_tags );
				$extra     = count( $sounds ) - $max_tags;
				foreach ( $show_tags as $tag ) :
					?>
					<span class="ev-card__sound"><?php echo esc_html( $tag ); ?></span>
				<?php endforeach; ?>
				<?php if ( $extra > 0 ) : ?>
					<span class="ev-card__sound ev-card__sound--more">+<?php echo esc_html( $extra ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Footer -->
		<?php if ( ! empty( $ev['ticket_price'] ) || ! empty( $ev['ticket_url'] ) ) : ?>
			<div class="ev-card__footer">
				<?php if ( ! empty( $ev['ticket_price'] ) ) : ?>
					<span class="ev-card__price"><?php echo esc_html( $ev['ticket_price'] ); ?></span>
				<?php else : ?>
					<span></span>
				<?php endif; ?>

				<?php if ( ! empty( $ev['ticket_url'] ) ) : ?>
					<a href="<?php echo esc_url( $ev['ticket_url'] ); ?>" class="ev-card__ticket" target="_blank"
						rel="noopener">
						<i class="ri-ticket-line"></i> Ingressos
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>

</article>
