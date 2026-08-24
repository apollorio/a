<?php
/**
 * URL Import — session results list.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="card ins imp-card">
	<div class="imp-card-head">
		<span class="imp-card-title"><span class="tag">3</span> <?php esc_html_e( 'Eventos importados nesta sessão', 'apollo-events' ); ?></span>
		<span class="tag" id="countPill">0</span>
	</div>
	<div id="resultsList">
		<div class="imp-empty"><?php esc_html_e( 'nenhum evento importado ainda — os dados ficam apenas em memória enquanto a página estiver aberta', 'apollo-events' ); ?></div>
	</div>
</div>
