<?php
/**
 * Template: Local Card (Listing / Grid)
 * ShadCN-inspired card for venue listings
 * Ported from 1212 workspace
 *
 * @package Apollo\Local
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

$local_id   = get_the_ID();
$local_name = get_post_meta( $local_id, '_local_name', true ) ?: get_the_title( $local_id );
$local_url  = get_permalink( $local_id );

// Image
$local_image = get_the_post_thumbnail_url( $local_id, 'medium_large' );
if ( ! $local_image ) {
	$local_image = get_post_meta( $local_id, '_local_image_1', true );
	if ( is_numeric( $local_image ) ) {
		$local_image = wp_get_attachment_image_url( $local_image, 'medium_large' );
	}
}

// Region
$areas  = get_the_terms( $local_id, 'local_area' );
$region = '';
if ( ! is_wp_error( $areas ) && ! empty( $areas ) ) {
	$region = $areas[0]->name;
}

// Capacity
$capacity = get_post_meta( $local_id, '_local_capacity', true ) ?: '';

// Upcoming events
$upcoming = array();
if ( post_type_exists( 'event' ) ) {
	$ev_q = new WP_Query(
		array(
			'post_type'      => 'event',
			'posts_per_page' => 3,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'   => '_event_local_id',
					'value' => (string) $local_id,
				),
				array(
					'key'     => '_event_local_ids',
					'value'   => (string) $local_id,
					'compare' => 'LIKE',
				),
			),
			'meta_key'       => '_event_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'date_query'     => array( array( 'after' => 'now' ) ),
		)
	);
	if ( $ev_q->have_posts() ) {
		foreach ( $ev_q->posts as $ev_id ) {
			$ev_date    = get_post_meta( $ev_id, '_event_date', true );
			$ts         = strtotime( $ev_date );
			$upcoming[] = array(
				'title' => get_the_title( $ev_id ),
				'url'   => get_permalink( $ev_id ),
				'day'   => $ts ? date_i18n( 'd', $ts ) : '--',
				'month' => $ts ? strtoupper( date_i18n( 'M', $ts ) ) : '--',
			);
		}
	}
	wp_reset_postdata();
}
?>
<article class="apollo-local-card shadcn-card" data-local-id="<?php echo esc_attr( $local_id ); ?>">
	<a href="<?php echo esc_url( $local_url ); ?>" class="card-image">
		<?php if ( $local_image ) : ?>
		<img src="<?php echo esc_url( $local_image ); ?>" alt="<?php echo esc_attr( $local_name ); ?>" loading="lazy" decoding="async">
		<?php else : ?>
		<div class="card-image-placeholder"><i class="ri-map-pin-2-line"></i></div>
		<?php endif; ?>
		<div class="card-image-overlay"></div>
	</a>

	<div class="card-content">
		<div class="local-header">
			<h3 class="local-name"><a href="<?php echo esc_url( $local_url ); ?>"><?php echo esc_html( $local_name ); ?></a></h3>
			<?php if ( $region ) : ?>
			<span class="local-region"><i class="ri-map-pin-line"></i> <?php echo esc_html( $region ); ?></span>
			<?php endif; ?>
			<?php if ( $capacity ) : ?>
			<span class="local-capacity"><i class="ri-group-line"></i> <?php echo esc_html( $capacity ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $upcoming ) ) : ?>
		<div class="local-next-events">
			<span class="events-label"><i class="ri-calendar-line"></i> <?php esc_html_e( 'Próximos', 'apollo-local' ); ?></span>
			<ul class="events-list">
				<?php foreach ( $upcoming as $ev ) : ?>
				<li>
					<a href="<?php echo esc_url( $ev['url'] ); ?>">
						<span class="ev-date"><?php echo esc_html( $ev['day'] . ' ' . $ev['month'] ); ?></span>
						<span class="ev-title"><?php echo esc_html( $ev['title'] ); ?></span>
					</a>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php else : ?>
		<div class="local-no-events">
			<i class="ri-calendar-close-line"></i>
			<span><?php esc_html_e( 'Sem eventos próximos', 'apollo-local' ); ?></span>
		</div>
		<?php endif; ?>
	</div>

	<div class="card-footer">
		<a href="<?php echo esc_url( $local_url ); ?>" class="card-action">
			<?php esc_html_e( 'Ver Local', 'apollo-local' ); ?> <i class="ri-arrow-right-up-line"></i>
		</a>
	</div>
</article>
