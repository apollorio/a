<?php

/**
 * Template Part: DJ Archive — Toolbar
 *
 * Sticky toolbar with sound filter pills and search input.
 *
 * Expects:
 *   $sound_terms  (array) — WP_Term[] from sound taxonomy
 *   $djs          (array) — DJ data array for counting per-sound
 *   $sound_counts (array) — pre-counted slug => count map
 *
 * @package Apollo\DJs
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sound_terms  = $sound_terms ?? array();
$djs          = $djs ?? array();
$sound_counts = $sound_counts ?? array();
?>
<section class="dj-toolbar" id="dj-toolbar">

	<div class="dj-toolbar__row">

		<!-- Sound Pills -->
		<div class="dj-pills" id="dj-sound-pills">
			<button class="dj-pill active" data-sound="all">
				<i class="ri-equalizer-line"></i>
				Todos
				<span class="dj-pill__count"><?php echo esc_html( count( $djs ) ); ?></span>
			</button>
			<?php
			foreach ( $sound_terms as $term ) :
				$count = $sound_counts[ $term->slug ] ?? 0;
				if ( $count === 0 ) {
					continue;
				}
				?>
				<button class="dj-pill" data-sound="<?php echo esc_attr( $term->slug ); ?>">
					<?php echo esc_html( $term->name ); ?>
					<span class="dj-pill__count"><?php echo esc_html( $count ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>

		<!-- Search -->
		<div class="dj-search">
			<i class="ri-search-line"></i>
			<input
				type="text"
				class="apollo-input dj-search__input"
				id="dj-search"
				placeholder="Buscar DJ..."
				autocomplete="off">
		</div>

	</div>

	<!-- Active Filters -->
	<div class="dj-active-filters" id="dj-active-filters">
		<span id="dj-active-text"></span>
		<button class="dj-active-filters__clear" id="dj-clear-all-inline">
			<i class="ri-close-circle-line"></i> Limpar
		</button>
	</div>

</section>