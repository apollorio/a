<?php

/**
 * Apollo Sign — Global Doc Header Bar
 * Part: sig-header-bar.php
 *
 * Renders the fixed top header (.doc-hd) shared across all panels.
 * Adapts its content based on the $header_mode variable.
 *
 * Variables expected:
 *   $header_mode  — 'preview' | 'sign' | 'done'
 *   $doc_data     — array: doc_title, doc_number, status, issuer_name
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$header_mode = $header_mode ?? 'preview';
$doc_data    = $doc_data ?? array();
$doc_title   = esc_html( $doc_data['doc_title'] ?? 'Documento' );
$doc_number  = esc_html( $doc_data['doc_number'] ?? '—' );
$is_signed   = ( $doc_data['status'] ?? 'pending' ) === 'signed';
?>
<header class="doc-hd">

	<?php if ( $header_mode === 'preview' ) : ?>

		<!-- Panel 1: brand + doc title + status -->
		<div class="dh-brand">
			<span class="gl">Apollo</span>
			<span class="ga">docs</span>
		</div>
		<div class="dh-sep"></div>
		<div class="dh-doc">
			<span class="dh-title"><?php echo $doc_title; ?></span>
			<span class="dh-id">Nº <?php echo $doc_number; ?></span>
		</div>
		<div class="dh-right">
			<span class="pill pending" id="pill-p1"><span class="sdot"></span> Pendente</span>
			<button class="icon-btn" title="<?php esc_attr_e( 'Baixar documento', 'apollo-sign' ); ?>">
				<i class="ri-download-line" aria-hidden="true"></i>
			</button>
		</div>

	<?php elseif ( $header_mode === 'sign' ) : ?>

		<!-- Panel 2: back button + breadcrumb + status -->
		<button data-back="1" data-dir="left" class="icon-btn" style="color:var(--ghost);" aria-label="<?php esc_attr_e( 'Voltar', 'apollo-sign' ); ?>">
			<i class="ri-arrow-left-line" aria-hidden="true"></i>
		</button>
		<div class="dh-sep"></div>
		<div class="breadcrumb">
			<i class="ri-quill-pen-line" aria-hidden="true"></i>
			<span><?php _e( 'Assinar Documento', 'apollo-sign' ); ?></span>
		</div>
		<div class="dh-right">
			<span class="pill pending" id="pill-p2"><span class="sdot"></span> Pendente</span>
		</div>

	<?php elseif ( $header_mode === 'done' ) : ?>

		<!-- Panel 3: brand + confirmed breadcrumb + signed pill -->
		<div class="dh-brand">
			<span class="gl">Apollo</span>
			<span class="ga">docs</span>
		</div>
		<div class="dh-sep"></div>
		<div class="breadcrumb">
			<i class="ri-shield-check-fill" style="color:var(--live);" aria-hidden="true"></i>
			<span style="color:var(--live);font-weight:800;"><?php _e( 'Assinatura Confirmada', 'apollo-sign' ); ?></span>
		</div>
		<div class="dh-right">
			<span class="pill signed" id="pill-p3">
				<i class="ri-check-line" aria-hidden="true"></i>
				<?php _e( 'Assinado', 'apollo-sign' ); ?>
			</span>
		</div>

	<?php endif; ?>

</header>
