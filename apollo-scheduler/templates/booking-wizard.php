<?php
/**
 * Booking wizard template — luxury Apollo dark canvas.
 *
 * @package Apollo\Scheduler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string $config wp_json_encode config */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'apollo-scheduler apollo-scheduler-book' ); ?>>
<main class="apollo-scheduler-canvas" data-apollo-scheduler-config="<?php echo esc_attr( (string) $config ); ?>">
	<header class="apollo-scheduler-header">
		<h1><?php esc_html_e( 'Book', 'apollo-scheduler' ); ?></h1>
		<ol class="apollo-scheduler-steps" aria-label="<?php esc_attr_e( 'Booking progress', 'apollo-scheduler' ); ?>">
			<li class="is-active" data-step="service"><i class="ri-service-line"></i><span><?php esc_html_e( 'Service', 'apollo-scheduler' ); ?></span></li>
			<li data-step="agent"><i class="ri-user-star-line"></i><span><?php esc_html_e( 'Agent', 'apollo-scheduler' ); ?></span></li>
			<li data-step="resource"><i class="ri-building-line"></i><span><?php esc_html_e( 'Room', 'apollo-scheduler' ); ?></span></li>
			<li data-step="time"><i class="ri-time-line"></i><span><?php esc_html_e( 'Time', 'apollo-scheduler' ); ?></span></li>
			<li data-step="confirm"><i class="ri-check-double-line"></i><span><?php esc_html_e( 'Confirm', 'apollo-scheduler' ); ?></span></li>
		</ol>
	</header>

	<section class="apollo-scheduler-panel" data-panel="service">
		<div class="apollo-scheduler-cards" id="apollo-scheduler-services"></div>
	</section>
	<section class="apollo-scheduler-panel is-hidden" data-panel="agent">
		<div class="apollo-scheduler-cards" id="apollo-scheduler-agents"></div>
	</section>
	<section class="apollo-scheduler-panel is-hidden" data-panel="resource">
		<div class="apollo-scheduler-cards" id="apollo-scheduler-resources"></div>
	</section>
	<section class="apollo-scheduler-panel is-hidden" data-panel="time">
		<input type="date" id="apollo-scheduler-date" class="apollo-scheduler-date" />
		<div class="apollo-scheduler-slots" id="apollo-scheduler-slots"></div>
	</section>
	<section class="apollo-scheduler-panel is-hidden" data-panel="confirm">
		<div class="apollo-scheduler-summary" id="apollo-scheduler-summary"></div>
		<button type="button" class="apollo-scheduler-confirm" id="apollo-scheduler-confirm">
			<i class="ri-calendar-check-line"></i>
			<?php esc_html_e( 'Confirm booking', 'apollo-scheduler' ); ?>
		</button>
	</section>
</main>
<?php wp_footer(); ?>
</body>
</html>
