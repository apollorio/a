<?php

/**
 * Template Part: Section Header
 *
 * Supports optional view toggle (carousel ↔ grid) via $args['view_toggle'].
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$icon        = $args['icon'] ?? 'ri-grid-fill';
$title       = $args['title'] ?? 'Section';
$count       = $args['count'] ?? '';
$view_toggle = ! empty( $args['view_toggle'] );
$carousel_id = $args['carousel_id'] ?? '';
?>

<div class="section-header reveal-up">
	<h2 class="section-title">
		<i class="<?php echo esc_attr( $icon ); ?>"></i>
		<?php echo esc_html( $title ); ?>
	</h2>
	<div class="section-header-right">
		<?php if ( $count ) : ?>
			<span class="section-count"><?php echo esc_html( $count ); ?></span>
		<?php endif; ?>
		<?php if ( $view_toggle ) : ?>
			<div class="view-toggle" <?php echo $carousel_id ? 'data-carousel="' . esc_attr( $carousel_id ) . '"' : ''; ?>>
				<button class="filter-pill active" data-view="carousel" title="Carousel">
					<i class="ri-stack-line"></i>
				</button>
				<button class="filter-pill" data-view="grid" title="Grade">
					<i class="ri-grid-fill"></i>
				</button>
			</div>
		<?php endif; ?>
	</div>
</div>
