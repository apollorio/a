<?php

/**
 * Apollo Sign — Panel 2 Toolbar Strip
 * Part: sig-toolbar.php
 *
 * Renders the .doc-tb toolbar below the header on the sign panel.
 * Contains: doc meta label, zoom buttons, restart, download.
 *
 * Variables expected:
 *   $doc_data — array: doc_title, doc_number
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_data   = $doc_data ?? array();
$doc_title  = esc_html( $doc_data['doc_title'] ?? 'Documento' );
$doc_number = esc_html( $doc_data['doc_number'] ?? '—' );
?>
<div class="doc-tb" role="toolbar" aria-label="<?php esc_attr_e( 'Ferramentas do documento', 'apollo-sign' ); ?>">
	<div class="doc-tb-meta">
		<i class="ri-file-pdf-line" aria-hidden="true"></i>
		<span><?php echo $doc_title; ?> &middot; <?php echo $doc_number; ?></span>
	</div>
	<div class="doc-tb-right">
		<button class="tb-btn" id="btn-minus" title="<?php esc_attr_e( 'Reduzir (−)', 'apollo-sign' ); ?>" aria-label="<?php esc_attr_e( 'Reduzir zoom', 'apollo-sign' ); ?>" style="font-size:18px;font-weight:700;color:var(--smoke);">&#x2212;</button>
		<button class="tb-btn" id="btn-plus" title="<?php esc_attr_e( 'Ampliar (+)', 'apollo-sign' ); ?>" aria-label="<?php esc_attr_e( 'Ampliar zoom', 'apollo-sign' ); ?>" style="font-size:18px;font-weight:700;color:var(--smoke);">+</button>
		<div class="tb-sep" aria-hidden="true"></div>
		<button class="tb-btn" id="btn-restart-pos" title="<?php esc_attr_e( 'Reiniciar posicionamento', 'apollo-sign' ); ?>" aria-label="<?php esc_attr_e( 'Reiniciar posicionamento da assinatura', 'apollo-sign' ); ?>" style="color:var(--primary);">
			<i class="ri-restart-line" aria-hidden="true"></i>
		</button>
		<div class="tb-sep" aria-hidden="true"></div>
		<button class="tb-btn" title="<?php esc_attr_e( 'Baixar', 'apollo-sign' ); ?>" aria-label="<?php esc_attr_e( 'Baixar documento', 'apollo-sign' ); ?>">
			<i class="ri-download-line" aria-hidden="true"></i>
		</button>
	</div>
</div>
