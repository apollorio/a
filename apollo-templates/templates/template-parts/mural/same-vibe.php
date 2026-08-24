<?php
/**
 * Mural: Same Vibe
 *
 * Horizontal marquee (reverse) of events matching user's sound preferences.
 *
 * @package Apollo\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variable: $same_vibe_events (array of WP_Post from page-mural.php)
if ( empty( $same_vibe_events ) ) {
	return;
}

// Duplicate events for infinite scroll illusion
$marquee_events = array_merge( $same_vibe_events, $same_vibe_events );
?>

<section class="marquee-section">
	<div class="app-container">
		<div class="marquee-header">
			<i class="ri-pulse-fill"></i> Same Vibe
		</div>
	</div>

	<div class="marquee-track-container">
		<div class="marquee-content reverse">
			<?php
			foreach ( $marquee_events as $event ) :
				$banner       = function_exists( 'apollo_event_get_banner' ) ? apollo_event_get_banner( $event->ID, 'medium' ) : get_the_post_thumbnail_url( $event, 'medium' );
				$ev_date      = get_post_meta( $event->ID, '_event_start_date', true );
				$loc          = function_exists( 'apollo_event_get_loc' ) ? apollo_event_get_loc( $event->ID ) : null;
				$date_display = $ev_date ? wp_date( 'j M', strtotime( $ev_date ) ) : '';
				?>
			<a href="<?php echo esc_url( get_permalink( $event ) ); ?>" class="marquee-card">
				<img src="<?php echo esc_url( $banner ); ?>" class="mq-img"
					alt="<?php echo esc_attr( $event->post_title ); ?>" loading="lazy">
				<div class="mq-title"><?php echo esc_html( $event->post_title ); ?></div>
				<?php if ( $date_display ) : ?>
				<div class="mq-date"><?php echo esc_html( strtoupper( $date_display ) ); ?></div>
				<?php endif; ?>
				<?php if ( $loc ) : ?>
				<div class="mq-venue"><i class="ri-map-pin-line"></i> <?php echo esc_html( $loc['title'] ); ?></div>
				<?php endif; ?>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
