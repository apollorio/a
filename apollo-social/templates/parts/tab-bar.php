<?php

/**
 * Template Part: Tab Bar — Fixed pill-shaped navigation for /explore
 *
 * 6 tabs as 46×46 icon circles inside a pill group. No text labels.
 * Tooltips on hover. Scrolled state adds glass blur.
 * Registry compliance: uses 'fav' not 'bookmark'.
 *
 * @package Apollo\Social
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<nav class="tab-bar" id="tab-bar" role="tablist">
	<div class="tab-group">
		<div class="tab-item active" data-tab="feed" data-tooltip="Feed" role="tab" aria-selected="true" aria-label="Feed" onclick="switchTab('feed',this)">
			<i class="ri-global-line"></i>
		</div>
		<div class="tab-item" data-tab="events" data-tooltip="Eventos" role="tab" aria-selected="false" aria-label="Eventos" onclick="switchTab('events',this)">
			<i class="ri-map-pin-line"></i>
		</div>
		<div class="tab-item" data-tab="comunas" data-tooltip="Comunas" role="tab" aria-selected="false" aria-label="Comunas" onclick="switchTab('comunas',this)">
			<i class="ri-user-community-fill"></i>
		</div>
		<div class="tab-item" data-tab="market" data-tooltip="Market" role="tab" aria-selected="false" aria-label="Market" onclick="switchTab('market',this)">
			<i class="ri-ticket-2-line"></i>
		</div>
		<div class="tab-item" data-tab="favs" data-tooltip="Favs" role="tab" aria-selected="false" aria-label="Favs" onclick="switchTab('favs',this)">
			<i class="ri-shining-2-fill"></i>
		</div>
		<div class="tab-item" data-tab="settings" data-tooltip="Config" role="tab" aria-selected="false" aria-label="Configurações" onclick="switchTab('settings',this)">
			<i class="ri-compasses-2-line"></i>
		</div>
	</div>
</nav>
