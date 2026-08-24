<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array  $settings
 * @var array  $data
 * @var string $uid
 */

$bars       = $data['bars'] ?? [];
$bar_count  = count( $bars );
$svg_width  = 320;
$svg_height = 200;
$padding    = 40;
$chart_h    = $svg_height - $padding;
$bar_width  = $bar_count > 0 ? max( 12, (int) floor( ( $svg_width - $padding ) / $bar_count * 0.6 ) ) : 12;
$gap        = $bar_count > 0 ? (int) floor( ( $svg_width - $padding ) / $bar_count ) : 0;
?>
<div class="ae-chart-bar" data-a-module="chart-bar" id="<?php echo esc_attr( $uid ); ?>">
	<svg class="ae-chart-bar__svg" viewBox="0 0 <?php echo esc_attr( (string) $svg_width ); ?> <?php echo esc_attr( (string) $svg_height ); ?>" preserveAspectRatio="xMidYMid meet">
		<line x1="<?php echo esc_attr( (string) $padding ); ?>" y1="<?php echo esc_attr( (string) $chart_h ); ?>" x2="<?php echo esc_attr( (string) $svg_width ); ?>" y2="<?php echo esc_attr( (string) $chart_h ); ?>" stroke="var(--ae-border, #2a2a2e)" stroke-width="1" />

		<?php foreach ( $bars as $i => $bar ) :
			$x       = $padding + ( $i * $gap ) + (int) floor( ( $gap - $bar_width ) / 2 );
			$bar_h   = (float) $bar['pct'] / 100 * ( $chart_h - 10 );
			$y       = $chart_h - $bar_h;
			$label_x = $x + (int) floor( $bar_width / 2 );
		?>
			<rect
				class="ae-chart-bar__rect"
				x="<?php echo esc_attr( (string) $x ); ?>"
				y="<?php echo esc_attr( (string) round( $y, 2 ) ); ?>"
				width="<?php echo esc_attr( (string) $bar_width ); ?>"
				height="<?php echo esc_attr( (string) round( $bar_h, 2 ) ); ?>"
				rx="3"
				fill="<?php echo esc_attr( $bar['color'] ); ?>"
			/>
			<text
				class="ae-chart-bar__label"
				x="<?php echo esc_attr( (string) $label_x ); ?>"
				y="<?php echo esc_attr( (string) ( $chart_h + 14 ) ); ?>"
				text-anchor="middle"
				fill="currentColor"
				font-size="10"
			><?php echo esc_html( $bar['label'] ); ?></text>
		<?php endforeach; ?>
	</svg>
</div>
