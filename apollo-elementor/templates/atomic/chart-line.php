<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array  $settings
 * @var array  $data
 * @var string $uid
 */

$title     = $data['title'] ?? '';
$headline  = $data['headline_number'] ?? '';
$delta     = $data['delta'] ?? '';
$path      = $data['path'] ?? '';
$area_path = $data['area_path'] ?? '';
$svg_w     = (int) ( $data['svg_width'] ?? 320 );
$svg_h     = (int) ( $data['svg_height'] ?? 160 );

$delta_class = '';
if ( str_starts_with( $delta, '+' ) ) {
	$delta_class = 'ae-chart-line__delta--up';
} elseif ( str_starts_with( $delta, '-' ) ) {
	$delta_class = 'ae-chart-line__delta--down';
}
?>
<div class="ae-chart-line" data-a-module="chart-line" id="<?php echo esc_attr( $uid ); ?>">
	<?php if ( '' !== $title || '' !== $headline ) : ?>
		<div class="ae-chart-line__header">
			<?php if ( '' !== $title ) : ?>
				<span class="ae-chart-line__title"><?php echo esc_html( $title ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $headline ) : ?>
				<span class="ae-chart-line__headline"><?php echo esc_html( $headline ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $delta ) : ?>
				<span class="ae-chart-line__delta <?php echo esc_attr( $delta_class ); ?>"><?php echo esc_html( $delta ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $path ) : ?>
		<svg class="ae-chart-line__svg" viewBox="0 0 <?php echo esc_attr( (string) $svg_w ); ?> <?php echo esc_attr( (string) $svg_h ); ?>" preserveAspectRatio="xMidYMid meet">
			<?php if ( '' !== $area_path ) : ?>
				<path class="ae-chart-line__area" d="<?php echo esc_attr( $area_path ); ?>" fill="var(--ae-accent-alpha, rgba(255,92,0,.12))" />
			<?php endif; ?>
			<path
				class="ae-chart-line__stroke ae-accent"
				d="<?php echo esc_attr( $path ); ?>"
				fill="none"
				stroke="currentColor"
				stroke-width="2.5"
				stroke-linecap="round"
				stroke-linejoin="round"
			>
				<animate attributeName="stroke-dashoffset" from="1000" to="0" dur="1.2s" fill="freeze" />
				<set attributeName="stroke-dasharray" to="1000" />
			</path>
		</svg>
	<?php endif; ?>
</div>
