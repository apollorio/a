<?php

/**
 * Template Part: GPS Archive — Location Card
 *
 * Apollo ap-card shape with clip-path, image, type badge, meta, area, footer.
 *
 * Expects: $loc (array) — single location data from parent loop.
 *
 * @package Apollo\Local
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $loc ) || ! is_array( $loc ) ) {
	return;
}

$types      = $loc['types'] ?? array();
$t_slugs    = $loc['type_slugs'] ?? array();
$areas      = $loc['areas'] ?? array();
$a_slugs    = $loc['area_slugs'] ?? array();
$first_type = $types[0] ?? '';
?>
<article
	class="gps-card"
	data-id="<?php echo esc_attr( $loc['id'] ); ?>"
	data-types="<?php echo esc_attr( implode( ',', $t_slugs ) ); ?>"
	data-areas="<?php echo esc_attr( implode( ',', $a_slugs ) ); ?>"
	data-search="
	<?php
	echo esc_attr(
		mb_strtolower(
			( $loc['name'] ?? '' ) . ' ' .
								( $loc['address'] ?? '' ) . ' ' .
								( $loc['city'] ?? '' ) . ' ' .
								implode( ' ', $types ) . ' ' .
								implode( ' ', $areas )
		)
	);
	?>
					">
	<!-- Image Link -->
	<a href="<?php echo esc_url( $loc['url'] ?? '#' ); ?>" class="gps-card__link" aria-label="<?php echo esc_attr( $loc['name'] ?? '' ); ?>">
		<div class="gps-card__image">
			<?php if ( ! empty( $loc['image'] ) ) : ?>
				<img
					src="<?php echo esc_url( $loc['image'] ); ?>"
					alt="<?php echo esc_attr( $loc['name'] ?? '' ); ?>"
					loading="lazy"
					decoding="async">
			<?php else : ?>
				<div class="gps-card__image-placeholder">
					<i class="ri-map-pin-line"></i>
				</div>
			<?php endif; ?>
		</div>
	</a>

	<!-- Type Badge -->
	<?php if ( $first_type ) : ?>
		<div class="gps-card__type">
			<i class="ri-store-2-line"></i>
			<?php echo esc_html( $first_type ); ?>
		</div>
	<?php endif; ?>

	<!-- Price Range -->
	<?php if ( ! empty( $loc['price_range'] ) ) : ?>
		<div class="gps-card__price"><?php echo esc_html( $loc['price_range'] ); ?></div>
	<?php endif; ?>

	<!-- Body -->
	<div class="gps-card__body">

		<h3 class="gps-card__name">
			<a href="<?php echo esc_url( $loc['url'] ?? '#' ); ?>">
				<?php echo esc_html( $loc['name'] ?? 'Sem nome' ); ?>
			</a>
		</h3>

		<!-- Meta -->
		<div class="gps-card__meta">
			<?php if ( ! empty( $loc['address'] ) ) : ?>
				<span class="gps-card__meta-item">
					<i class="ri-map-pin-line"></i>
					<span><?php echo esc_html( $loc['address'] ); ?></span>
				</span>
			<?php endif; ?>

			<?php if ( ! empty( $loc['phone'] ) ) : ?>
				<span class="gps-card__meta-item">
					<i class="ri-phone-line"></i>
					<span><?php echo esc_html( $loc['phone'] ); ?></span>
				</span>
			<?php endif; ?>
		</div>

		<!-- Area -->
		<?php if ( ! empty( $areas ) ) : ?>
			<span class="gps-card__area"><?php echo esc_html( $areas[0] ); ?></span>
		<?php endif; ?>

		<!-- Footer -->
		<div class="gps-card__footer">
			<?php if ( ! empty( $loc['capacity'] ) ) : ?>
				<span class="gps-card__capacity">
					<i class="ri-group-line"></i>
					<?php echo esc_html( $loc['capacity'] ); ?>
				</span>
			<?php else : ?>
				<span></span>
			<?php endif; ?>

			<div class="gps-card__links">
				<?php if ( ! empty( $loc['instagram'] ) ) : ?>
					<a href="<?php echo esc_url( 'https://instagram.com/' . ltrim( $loc['instagram'], '@' ) ); ?>" target="_blank" rel="noopener" title="Instagram">
						<i class="ri-instagram-line"></i>
					</a>
				<?php endif; ?>

				<?php if ( ! empty( $loc['website'] ) ) : ?>
					<a href="<?php echo esc_url( $loc['website'] ); ?>" target="_blank" rel="noopener" title="Website">
						<i class="ri-global-line"></i>
					</a>
				<?php endif; ?>
			</div>
		</div>

	</div>

</article>
