<?php

/**
 * Template Part: Feed Header — Title + Tab Navigation + Search
 *
 * Tabs filter feed by component: Para você, Social, Conteúdo, Eventos, Classificados
 *
 * @package Apollo\Social
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

// Enqueue composite search
if ( function_exists( 'apollo_enqueue_composite_search' ) ) {
	apollo_enqueue_composite_search();
}
?>
<div class="feed-header-bar">
	<div class="feed-header-top">
		<h1 class="feed-title">Feed</h1>
		<?php
		// Composite Search
		if ( function_exists( 'apollo_composite_search' ) ) {
			apollo_composite_search(
				array(
					'context'     => 'explore',
					'placeholder' => __( 'Buscar no feed...', 'apollo-social' ),
					'class'       => 'feed-search',
				)
			);
		}
		?>
	</div>
	<div class="feed-header-tabs">
		<button class="feed-tab active" data-component="">Para você</button>
		<button class="feed-tab" data-component="social">Social</button>
		<button class="feed-tab" data-component="content">Conteúdo</button>
		<button class="feed-tab" data-component="events">Eventos</button>
		<button class="feed-tab" data-component="classifieds">Classificados</button>
	</div>
</div>
