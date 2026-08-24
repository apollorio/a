<?php

/**
 * Template Part: Events Archive — Toolbar
 *
 * Sticky toolbar with sound filter pills, date range pills, search input,
 * and active filters indicator. All client-side driven.
 *
 * Expects:
 *   $sound_terms    (array) — WP_Term[] from sound taxonomy
 *   $category_terms (array) — WP_Term[] from event_category taxonomy
 *   $events         (array) — events data array for counting per-sound
 *
 * @package Apollo\Event
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sound_terms    = $sound_terms ?? array();
$category_terms = $category_terms ?? array();
$events         = $events ?? array();

// Pre-count events per sound slug for pill badges
$sound_counts = array();
foreach ( $events as $ev ) {
	if ( ! empty( $ev['sound_slugs'] ) ) {
		foreach ( $ev['sound_slugs'] as $slug ) {
			$sound_counts[ $slug ] = ( $sound_counts[ $slug ] ?? 0 ) + 1;
		}
	}
}
?>
<section class="ev-toolbar" id="ev-toolbar">

	<!-- Row 1: Sound pills + Search -->
	<div class="ev-toolbar__row">

		<!-- Sound Pills -->
		<div class="ev-pills" id="ev-sound-pills">
			<button class="ev-pill active" data-sound="all">
				<i class="ri-equalizer-line"></i>
				Todos
				<span class="ev-pill__count"><?php echo esc_html( count( $events ) ); ?></span>
			</button>
			<?php
			foreach ( $sound_terms as $term ) :
				$count = $sound_counts[ $term->slug ] ?? 0;
				if ( $count === 0 ) {
					continue;
				}
				?>
				<button class="ev-pill" data-sound="<?php echo esc_attr( $term->slug ); ?>">
					<?php echo esc_html( $term->name ); ?>
					<span class="ev-pill__count"><?php echo esc_html( $count ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>

		<!-- Search -->
		<div class="ev-search">
			<i class="ri-search-line"></i>
			<input
				type="text"
				class="apollo-input ev-search__input"
				id="ev-search"
				placeholder="Buscar evento, DJ, local..."
				autocomplete="off">
		</div>

	</div>

	<!-- Row 2: Date pills + Category filter -->
	<div class="ev-toolbar__row ev-toolbar__row--secondary">

		<!-- Date Pills -->
		<div class="ev-pills ev-pills--dates" id="ev-date-pills">
			<button class="ev-pill active" data-date="all">
				<i class="ri-calendar-line"></i>
				Todas as datas
			</button>
			<button class="ev-pill" data-date="today">Hoje</button>
			<button class="ev-pill" data-date="week">Esta semana</button>
			<button class="ev-pill" data-date="month">Este mês</button>
		</div>

		<!-- Category dropdown (only if terms exist) -->
		<?php if ( ! empty( $category_terms ) ) : ?>
			<div class="ev-search" style="width:200px;">
				<i class="ri-folder-line"></i>
				<select class="ev-search__input" id="ev-category-filter" style="appearance:none;padding-left:38px;">
					<option value="all">Categoria</option>
					<?php foreach ( $category_terms as $cat ) : ?>
						<option value="<?php echo esc_attr( $cat->slug ); ?>">
							<?php echo esc_html( $cat->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>

	</div>

	<!-- Active Filters Info -->
	<div class="ev-active-filters" id="ev-active-filters">
		<span id="ev-active-text"></span>
		<button class="ev-active-filters__clear" id="ev-clear-all">
			<i class="ri-close-circle-line"></i> Limpar
		</button>
	</div>

</section>