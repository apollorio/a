<?php

/**
 * Template Part: DJ Archive — DJ Card
 *
 * Apollo ap-card shape with clip-path, avatar, verified badge,
 * sound tags, social links, upcoming events count.
 *
 * Expects: $dj (array) — single DJ data from parent loop.
 *
 * @package Apollo\DJs
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $dj ) || ! is_array( $dj ) ) {
	return;
}

$is_verified = ! empty( $dj['verified'] );
$sounds      = $dj['sounds'] ?? array();
$s_slugs     = $dj['sound_slugs'] ?? array();
$links       = $dj['links'] ?? array();
$upcoming    = (int) ( $dj['upcoming_count'] ?? 0 );

// Flatten social/music/platforms links
$all_links = array_merge(
	$links['social'] ?? array(),
	$links['music'] ?? array(),
	$links['platforms'] ?? array()
);
$all_links = array_slice( $all_links, 0, 4 );
?>
<article
	class="dj-card"
	data-id="<?php echo esc_attr( $dj['id'] ); ?>"
	data-sounds="<?php echo esc_attr( implode( ',', $s_slugs ) ); ?>"
	data-search="
	<?php
	echo esc_attr(
		mb_strtolower(
			( $dj['name'] ?? '' ) . ' ' .
								( $dj['bio'] ?? '' ) . ' ' .
								implode( ' ', $sounds )
		)
	);
	?>
					">
	<!-- Avatar Link -->
	<a href="<?php echo esc_url( $dj['url'] ?? '#' ); ?>" class="dj-card__link" aria-label="<?php echo esc_attr( $dj['name'] ?? '' ); ?>">
		<div class="dj-card__avatar">
			<?php if ( ! empty( $dj['image'] ) ) : ?>
				<img
					src="<?php echo esc_url( $dj['image'] ); ?>"
					alt="<?php echo esc_attr( $dj['name'] ?? '' ); ?>"
					loading="lazy"
					decoding="async">
			<?php else : ?>
				<div class="dj-card__avatar-placeholder">
					<i class="ri-disc-line"></i>
				</div>
			<?php endif; ?>
		</div>
	</a>

	<!-- Verified Badge (on avatar) -->
	<?php if ( $is_verified ) : ?>
		<div class="dj-card__verified" title="DJ Verificado">
			<i class="ri-verified-badge-fill"></i>
		</div>
	<?php endif; ?>

	<!-- Upcoming Events Badge -->
	<?php if ( $upcoming > 0 ) : ?>
		<div class="dj-card__upcoming">
			<i class="ri-calendar-event-fill"></i>
			<?php echo esc_html( $upcoming ); ?>
		</div>
	<?php endif; ?>

	<!-- Body -->
	<div class="dj-card__body">

		<!-- Name + inline verified -->
		<div class="dj-card__header">
			<h3 class="dj-card__name">
				<a href="<?php echo esc_url( $dj['url'] ?? '#' ); ?>">
					<?php echo esc_html( $dj['name'] ?? 'DJ' ); ?>
				</a>
			</h3>
			<?php if ( $is_verified ) : ?>
				<i class="ri-verified-badge-fill dj-card__verified-inline" title="Verificado"></i>
			<?php endif; ?>
		</div>

		<!-- Bio -->
		<?php if ( ! empty( $dj['bio'] ) ) : ?>
			<p class="dj-card__bio"><?php echo esc_html( $dj['bio'] ); ?></p>
		<?php endif; ?>

		<!-- Sound Tags -->
		<?php if ( ! empty( $sounds ) ) : ?>
			<div class="dj-card__sounds">
				<?php
				$max_tags  = 3;
				$show_tags = array_slice( $sounds, 0, $max_tags );
				$extra     = count( $sounds ) - $max_tags;
				foreach ( $show_tags as $tag ) :
					?>
					<span class="dj-card__sound"><?php echo esc_html( $tag ); ?></span>
				<?php endforeach; ?>
				<?php if ( $extra > 0 ) : ?>
					<span class="dj-card__sound dj-card__sound--more">+<?php echo esc_html( $extra ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Footer -->
		<div class="dj-card__footer">
			<span class="dj-card__event-count">
				<?php if ( $upcoming > 0 ) : ?>
					<strong><?php echo esc_html( $upcoming ); ?></strong>
					evento<?php echo $upcoming > 1 ? 's' : ''; ?>
				<?php else : ?>
					Sem eventos
				<?php endif; ?>
			</span>

			<?php if ( ! empty( $all_links ) ) : ?>
				<div class="dj-card__links">
					<?php foreach ( $all_links as $link ) : ?>
						<a href="<?php echo esc_url( $link['url'] ?? '#' ); ?>"
							target="_blank"
							rel="noopener"
							title="<?php echo esc_attr( $link['label'] ?? '' ); ?>">
							<i class="<?php echo esc_attr( $link['icon'] ?? 'ri-link' ); ?>"></i>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

	</div>

</article>
