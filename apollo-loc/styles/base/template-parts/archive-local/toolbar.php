<?php

/**
 * Template Part: GPS Archive — Toolbar
 *
 * Sticky toolbar with type pills, area dropdown, search.
 *
 * Expects: $type_terms, $area_terms, $locs, $type_counts
 *
 * @package Apollo\Local
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type_terms  = $type_terms ?? array();
$area_terms  = $area_terms ?? array();
$locs        = $locs ?? array();
$type_counts = $type_counts ?? array();
?>
<section class="gps-toolbar" id="gps-toolbar">

	<!-- Row 1: Type pills + Search -->
	<div class="gps-toolbar__row">

		<div class="gps-pills" id="gps-type-pills">
			<button class="gps-pill active" data-type="all">
				<i class="ri-map-pin-line"></i>
				Todos
				<span class="gps-pill__count"><?php echo esc_html( count( $locs ) ); ?></span>
			</button>
			<?php
			foreach ( $type_terms as $term ) :
				$count = $type_counts[ $term->slug ] ?? 0;
				if ( $count === 0 ) {
					continue;
				}
				?>
				<button class="gps-pill" data-type="<?php echo esc_attr( $term->slug ); ?>">
					<?php echo esc_html( $term->name ); ?>
					<span class="gps-pill__count"><?php echo esc_html( $count ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="gps-search">
			<i class="ri-search-line"></i>
			<input
				type="text"
				class="apollo-input gps-search__input"
				id="gps-search"
				placeholder="Buscar local, bairro..."
				autocomplete="off">
		</div>

	</div>

	<!-- Row 2: Area dropdown -->
	<?php if ( ! empty( $area_terms ) ) : ?>
		<div class="gps-toolbar__row gps-toolbar__row--secondary">
			<div class="gps-search" style="width:220px;">
				<i class="ri-compass-3-line"></i>
				<select class="gps-search__input" id="gps-area-filter" style="appearance:none;padding-left:38px;">
					<option value="all">Região</option>
					<?php foreach ( $area_terms as $area ) : ?>
						<option value="<?php echo esc_attr( $area->slug ); ?>">
							<?php echo esc_html( $area->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	<?php endif; ?>

	<!-- Active Filters -->
	<div class="gps-active-filters" id="gps-active-filters">
		<span id="gps-active-text"></span>
		<button class="gps-active-filters__clear" id="gps-clear-all-inline">
			<i class="ri-close-circle-line"></i> Limpar
		</button>
	</div>

</section>