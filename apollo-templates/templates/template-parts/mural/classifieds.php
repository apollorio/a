<?php
/**
 * Mural: Classifieds / Marketplace
 *
 * 2-column layout: Hosting + Tickets.
 *
 * @package Apollo\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variables: $classifieds_hosting, $classifieds_tickets (arrays of WP_Post)
?>

<section class="section-classifieds">
	<div class="section-header">
		<h3 class="section-title"><i class="ri-advertisement-fill"></i> Marketplace</h3>
		<a href="<?php echo esc_url( home_url( '/classificados/' ) ); ?>" class="section-link">Post New Ad</a>
	</div>

	<div class="classified-row">

		<?php if ( ! empty( $classifieds_hosting ) ) : ?>
		<div class="class-col">
			<div class="class-col-header">
				<i class="ri-home-4-line"></i> Renting & Hosting
			</div>
			<?php
			foreach ( $classifieds_hosting as $cl ) :
				$price = get_post_meta( $cl->ID, '_classified_price', true );
				$meta  = get_post_meta( $cl->ID, '_classified_loc', true ) ?: get_the_excerpt( $cl );
				$icon  = 'ri-hotel-bed-line';
				?>
			<a href="<?php echo esc_url( get_permalink( $cl ) ); ?>" class="class-item">
				<div class="class-icon"><i class="<?php echo esc_attr( $icon ); ?>"></i></div>
				<div class="class-info">
					<div class="class-title"><?php echo esc_html( $cl->post_title ); ?></div>
					<?php if ( $meta ) : ?>
					<div class="class-meta"><?php echo esc_html( $meta ); ?></div>
					<?php endif; ?>
				</div>
				<?php if ( $price ) : ?>
				<div class="class-price"><?php echo esc_html( $price ); ?></div>
				<?php endif; ?>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $classifieds_tickets ) ) : ?>
		<div class="class-col">
			<div class="class-col-header">
				<i class="ri-coupon-3-line"></i> Ticket Resale
			</div>
			<?php
			foreach ( $classifieds_tickets as $cl ) :
				$price = get_post_meta( $cl->ID, '_classified_price', true );
				$meta  = get_post_meta( $cl->ID, '_classified_loc', true ) ?: get_the_excerpt( $cl );
				?>
			<a href="<?php echo esc_url( get_permalink( $cl ) ); ?>" class="class-item">
				<div class="class-icon"><i class="ri-ticket-2-line"></i></div>
				<div class="class-info">
					<div class="class-title"><?php echo esc_html( $cl->post_title ); ?></div>
					<?php if ( $meta ) : ?>
					<div class="class-meta"><?php echo esc_html( $meta ); ?></div>
					<?php endif; ?>
				</div>
				<?php if ( $price ) : ?>
				<div class="class-price"><?php echo esc_html( $price ); ?></div>
				<?php endif; ?>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

	</div>
</section>