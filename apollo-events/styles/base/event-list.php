<?php
/**
 * Template: Event List Item — Base Style
 *
 * @var int    $id
 * @var string $title
 * @var string $permalink
 * @var string $banner
 * @var string $start_date
 * @var string $start_time
 * @var string $end_time
 * @var array  $parsed_date
 * @var string $loc_name
 * @var string $dj_names
 * @var string $status
 * @var bool   $is_gone
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gone_class = $is_gone ? ' a-eve-gone' : '';
?>

<div class="a-eve-list-item<?php echo esc_attr( $gone_class ); ?>" data-event-id="<?php echo esc_attr( $id ); ?>">

	<!-- Date Block -->
	<div class="a-eve-list-item__date-block">
		<?php if ( ! empty( $parsed_date ) ) : ?>
			<span class="a-eve-list-item__day"><?php echo esc_html( $parsed_date['dia'] ); ?></span>
			<span class="a-eve-list-item__month"><?php echo esc_html( $parsed_date['mes_abrev'] ); ?></span>
		<?php else : ?>
			<span class="a-eve-list-item__day"><?php echo esc_html( date( 'd', strtotime( $start_date ) ) ); ?></span>
			<span class="a-eve-list-item__month"><?php echo esc_html( date( 'M', strtotime( $start_date ) ) ); ?></span>
		<?php endif; ?>
	</div>

	<!-- Info -->
	<div class="a-eve-list-item__info">
		<h4 class="a-eve-list-item__title">
			<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			<?php if ( 'scheduled' !== $status ) : ?>
				<small class="a-eve-card__status a-eve-card__status--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status ); ?></small>
			<?php endif; ?>
		</h4>
		<div class="a-eve-list-item__details">
			<?php if ( $start_time ) : ?>
				<span>🕐 <?php echo esc_html( $start_time ); ?><?php echo $end_time ? ' – ' . esc_html( $end_time ) : ''; ?></span>
			<?php endif; ?>
			<?php if ( $loc_name ) : ?>
				<span>📍 <?php echo esc_html( $loc_name ); ?></span>
			<?php endif; ?>
			<?php if ( $dj_names ) : ?>
				<span>🎧 <?php echo esc_html( $dj_names ); ?></span>
			<?php endif; ?>
		</div>
	</div>

</div>
