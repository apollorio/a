<?php
/**
 * Directory Part — Grid
 *
 * Responsive card grid. Populated via JS from REST API.
 * Expects: $is_nucleos
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<div class="section-label g-fade">
		<i class="ri-layout-grid-line"></i>
		<?php echo $is_nucleos ? 'Todos os núcleos' : 'Todas as comunas'; ?>
	</div>

	<div class="grid" id="groupsGrid">
		<!-- Cards rendered via JS -->
	</div>

	<div class="load-more" id="loadMore" style="display:none;">
		<button class="btn-create" style="background:transparent;border:1px solid var(--border);color:var(--ink);" onclick="window._apolloDir.loadMore()">
			Carregar mais
		</button>
	</div>

	<div class="empty-state" id="emptyState" style="display:none;">
		<i class="ri-team-line" style="font-size:48px;color:var(--mist);display:block;margin-bottom:12px;"></i>
		<p style="color:var(--ghost);"><?php echo $is_nucleos ? 'Nenhum núcleo encontrado' : 'Nenhuma comuna encontrada'; ?>.</p>
	</div>
</div>
