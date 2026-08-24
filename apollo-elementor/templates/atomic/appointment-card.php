<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array  $settings
 * @var array  $data
 * @var string $uid
 */

$type_class = 'ae-card--' . esc_attr( $data['type'] ?? 'appt' );
$has_venue  = ! empty( $data['venue'] );
?>
<div class="ae-card <?php echo $type_class; ?>"
	draggable="true"
	style="-webkit-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; touch-action: none !important;">
	<span class="ae-card__stripe ae-accent-bg"></span>
	<div class="ae-card__content">
		<span class="ae-card__time">
			<?php echo esc_html( $data['time_start'] ?? '' ); ?>&ndash;<?php echo esc_html( $data['time_end'] ?? '' ); ?>
		</span>
		<span class="ae-card__title"><?php echo esc_html( $data['title'] ?? '' ); ?></span>
		<?php if ( $has_venue ) : ?>
			<span class="ae-card__venue"><?php echo esc_html( $data['venue'] ); ?></span>
		<?php endif; ?>
	</div>
</div>
