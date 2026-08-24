<?php

/**
 * Apollo Sign — Done Panel Aside Cards
 * Part: sig-done-aside.php
 *
 * Participants list, preview button (mobile), download,
 * blockchain hash verification card.
 * Also renders the "Imprimir Certificado" button that
 * triggers @media print on #print-certificate.
 *
 * Variables expected:
 *   $doc_data    — array: doc_number, doc_title, issuer_name
 *   $signer_data — array: signer_name, signer_role
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_data    = $doc_data ?? array();
$signer_data = $signer_data ?? array();
$doc_number  = esc_html( $doc_data['doc_number'] ?? '—' );
$issuer_name = esc_html( $doc_data['issuer_name'] ?? 'Apollo Admin' );
$signer_name = esc_html( $signer_data['name'] ?? 'Você' );
$signer_role = esc_html( $signer_data['role'] ?? 'Gestor do Evento' );
?>
<div class="done-aside">

	<!-- Status badge + doc ID -->
	<div class="aside-card">
		<p class="done-badge">
			<i class="ri-shield-check-fill" aria-hidden="true"></i>
			<?php _e( 'Assinatura Confirmada', 'apollo-sign' ); ?>
		</p>
		<p class="done-id" id="done-id-line"><?php echo $doc_number; ?></p>
	</div>

	<!-- Participants -->
	<div class="aside-card">
		<div class="aside-label"><?php _e( 'Participantes', 'apollo-sign' ); ?></div>
		<div class="part-list" role="list">

			<div class="part-row" role="listitem">
				<div class="part-av" aria-hidden="true"><i class="ri-shield-check-line"></i></div>
				<div class="part-meta">
					<div class="part-name"><?php echo $issuer_name; ?></div>
					<div class="part-role"><?php _e( 'Plataforma · Emissor', 'apollo-sign' ); ?></div>
				</div>
				<i class="ri-checkbox-circle-fill part-ic" aria-label="<?php esc_attr_e( 'Assinado', 'apollo-sign' ); ?>"></i>
			</div>

			<div class="part-row" role="listitem">
				<div class="part-av" aria-hidden="true"><i class="ri-check-line"></i></div>
				<div class="part-meta">
					<div class="part-name" id="done-part-name"><?php echo $signer_name; ?></div>
					<div class="part-role"><?php echo $signer_role; ?></div>
				</div>
				<i class="ri-checkbox-circle-fill part-ic" aria-label="<?php esc_attr_e( 'Assinado', 'apollo-sign' ); ?>"></i>
			</div>

		</div>
	</div>

	<!-- Document actions -->
	<div class="aside-card">
		<div class="aside-label"><?php _e( 'Documento Assinado', 'apollo-sign' ); ?></div>

		<!-- Mobile: slide to done-doc sub-view -->
		<button class="btn-dl-preview" id="btn-preview-doc" type="button">
			<i class="ri-file-shield-2-line" aria-hidden="true"></i>
			<span><?php _e( 'Visualizar documento assinado', 'apollo-sign' ); ?></span>
		</button>

		<!-- Download PDF -->
		<button class="btn-dl" id="btn-download" type="button">
			<i class="ri-download-2-line" aria-hidden="true"></i>
			<span id="btn-dl-lbl"><?php _e( 'Baixar PDF Assinado', 'apollo-sign' ); ?></span>
		</button>
		<p class="dl-note"><?php _e( 'Administradores podem baixar em qualquer fase do fluxo.', 'apollo-sign' ); ?></p>

		<!-- Print digital certificate -->
		<button class="btn-dl" id="btn-print-cert" type="button"
			onclick="window.print()"
			style="margin-top:8px;background:var(--sf2);color:var(--smoke);border:1.5px solid var(--brd-h);">
			<i class="ri-printer-line" aria-hidden="true"></i>
			<span><?php _e( 'Imprimir Certificado', 'apollo-sign' ); ?></span>
		</button>
	</div>

	<!-- Blockchain hash -->
	<div class="aside-card">
		<div class="aside-label"><?php _e( 'Verificação Blockchain', 'apollo-sign' ); ?></div>
		<div class="hash-strip" role="region" aria-label="<?php esc_attr_e( 'Hash de verificação SHA-256', 'apollo-sign' ); ?>">
			<span class="hash-k"><i class="ri-links-line" aria-hidden="true"></i> SHA-256</span>
			<span class="hash-v" id="done-hash-full">—</span>
		</div>
	</div>

</div><!-- /done-aside -->
