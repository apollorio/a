<?php

/**
 * Template Part: Feed Container — Post card list, load more, end message
 *
 * Posts are rendered via JS (explore.js) from REST API data.
 * Each post becomes a .post-card with identity, embeds, actions.
 *
 * @package Apollo\Social
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- Feed Container (JS renders post-cards here) -->
<div id="feed-container" class="feed-container">
	<div class="feed-loading">
		<div class="feed-spinner"></div>
		<span>Carregando feed...</span>
	</div>
</div>

<!-- Load More -->
<div id="feed-load-more" class="feed-load-more" style="display:none;">
	<button class="load-more-btn" id="feed-load-more-btn">
		<i class="ri-arrow-down-line"></i> Carregar mais
	</button>
</div>

<!-- End of Feed -->
<div id="feed-end" class="feed-end" style="display:none;">
	<i class="ri-check-double-line"></i>
	<span>Você está atualizado!</span>
</div>
