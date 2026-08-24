<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array  $settings
 * @var array  $data
 * @var string $uid
 */

$segments = $data['segments'] ?? [];
$radius   = (int) ( $data['radius'] ?? 78 );
$cx       = 90;
$cy       = 90;
$size     = 180;
?>
<div class="ae-chart-radial" data-a-module="chart-radial" id="<?php echo esc_attr( $uid ); ?>">
	<svg class="ae-chart-radial__svg" viewBox="0 0 <?php echo esc_attr( (string) $size ); ?> <?php echo esc_attr( (string) $size ); ?>" width="<?php echo esc_attr( (string) $size ); ?>" height="<?php echo esc_attr( (string) $size ); ?>">
		<?php foreach ( $segments as $seg ) : ?>
			<circle
				class="ae-chart-radial__segment"
				cx="<?php echo esc_attr( (string) $cx ); ?>"
				cy="<?php echo esc_attr( (string) $cy ); ?>"
				r="<?php echo esc_attr( (string) $radius ); ?>"
				fill="none"
				stroke="<?php echo esc_attr( $seg['color'] ); ?>"
				stroke-width="16"
				stroke-dasharray="<?php echo esc_attr( $seg['stroke_dasharray'] ); ?>"
				stroke-dashoffset="<?php echo esc_attr( (string) $seg['stroke_offset'] ); ?>"
				transform="rotate(-90 <?php echo esc_attr( (string) $cx ); ?> <?php echo esc_attr( (string) $cy ); ?>)"
			/>
		<?php endforeach; ?>
	</svg>

	<?php if ( ! empty( $segments ) ) : ?>
		<ul class="ae-chart-radial__legend">
			<?php foreach ( $segments as $seg ) : ?>
				<li class="ae-chart-radial__legend-row">
					<span class="ae-chart-radial__swatch" style="background:<?php echo esc_attr( $seg['color'] ); ?>;"></span>
					<span class="ae-chart-radial__label"><?php echo esc_html( $seg['label'] ); ?></span>
					<span class="ae-chart-radial__value"><?php echo esc_html( (string) $seg['pct'] ); ?>%</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
