<?php

/**
 * Template Part: Filters Row
 *
 * @package Apollo\Classifieds
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$filters = $args['filters'] ?? array( 'Todos' );
?>

<div class="filters-row reveal-up">
	<?php foreach ( $filters as $index => $filter ) : ?>
		<button class="filter-pill <?php echo $index === 0 ? 'active' : ''; ?>">
			<?php echo esc_html( $filter ); ?>
		</button>
	<?php endforeach; ?>
</div>
