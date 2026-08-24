<?php
/**
 * Template: Event Map Marker (popup content) — Base Style
 *
 * @var int    $id
 * @var string $title
 * @var string $permalink
 * @var string $start_date
 * @var string $start_time
 * @var array  $parsed_date
 * @var string $loc_name
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="a-eve-map-popup">
	<div class="a-eve-map-popup__title">
		<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
	</div>
	<div class="a-eve-map-popup__date">
		<?php
		if ( ! empty( $parsed_date ) ) {
			echo esc_html( $parsed_date['dia'] . ' de ' . $parsed_date['mes'] );
		} else {
			echo esc_html( $start_date );
		}
		if ( $start_time ) {
			echo ' · ' . esc_html( $start_time );
		}
		?>
	</div>
	<?php if ( $loc_name ) : ?>
		<div class="a-eve-map-popup__loc">📍 <?php echo esc_html( $loc_name ); ?></div>
	<?php endif; ?>
</div>
