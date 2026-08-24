<?php
/**
 * Apollo Sign — Admin Signatures Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap" style="margin:0;padding:0;max-width:100%">
<div class="apollo-sign-admin" id="apollo-sign-admin">

	<!-- ═══ Header ═══ -->
	<div class="sign-admin-header">
		<div class="sign-admin-title">
			<i class="ri-shield-keyhole-fill"></i>
			<span>Assinaturas Digitais</span>
			<span class="sign-badge-icpbr">ICP-Brasil</span>
		</div>
		<div class="sign-admin-actions">
			<select id="sign-filter-status" class="sign-select">
				<option value="">Todos</option>
				<option value="pending">Pendentes</option>
				<option value="signed">Assinados</option>
				<option value="revoked">Revogados</option>
			</select>
			<button class="sign-admin-btn refresh" id="sign-refresh">
				<i class="ri-refresh-line"></i>
			</button>
		</div>
	</div>

	<!-- ═══ Table ═══ -->
	<div class="sign-admin-table-wrap">
		<table class="sign-admin-table" id="sign-table">
			<thead>
				<tr>
					<th>ID</th>
					<th>Documento</th>
					<th>Signatário</th>
					<th>CPF</th>
					<th>Status</th>
					<th>Certificado</th>
					<th>Assinado em</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody id="sign-tbody">
				<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--docs-text-dim)">Carregando...</td></tr>
			</tbody>
		</table>
	</div>

	<!-- ═══ Modal Overlay ═══ -->
	<div class="sign-modal-overlay" id="sign-modal-overlay">
		<div class="sign-modal" id="sign-modal"></div>
	</div>

</div>
</div>
<?php