<?php
/**
 * URL Import — LED validation checklist.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="card ins imp-card" id="checklistPanel" style="display:none;">
	<div class="imp-card-head">
		<span class="imp-card-title"><span class="tag">2</span> <?php esc_html_e( 'Console de validação', 'apollo-events' ); ?></span>
		<span class="tag" id="platformPill">—</span>
	</div>
	<div class="imp-led-grid" id="ledGrid"></div>
	<div class="imp-verify">
		<span class="imp-gate" id="gateMsg"><?php esc_html_e( 'preencha os campos obrigatórios para liberar a importação', 'apollo-events' ); ?></span>
		<button type="button" class="btn btn-accent" id="btnConfirmRow" disabled>
			<?php esc_html_e( 'Confirmar importação', 'apollo-events' ); ?> <i class="ri-arrow-right-line"></i>
		</button>
	</div>
</div>
