<?php

/**
 * Apollo Sign — Panel 1: Document Preview
 * Part: sig-panel-preview.php
 *
 * Full-page panel with the A4 paper in read-only mode,
 * participants strip, and "Prosseguir para Assinar" CTA.
 *
 * Variables expected:
 *   $doc_data    — array: doc_title, doc_number, doc_version, doc_date,
 *                         issuer_name, issuer_cnpj, verify_url
 *   $clauses     — array of clause definitions (forwarded to sig-paper-clauses.php)
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_data    = $doc_data ?? array();
$doc_number  = esc_html( $doc_data['doc_number'] ?? 'APOLLO-DOC-0000' );
$doc_version = esc_html( $doc_data['doc_version'] ?? 'v1.0' );
$doc_date    = esc_html( $doc_data['doc_date'] ?? date( 'd.m.Y' ) );
$doc_title   = esc_html( $doc_data['doc_title'] ?? 'Documento' );
$issuer_name = esc_html( $doc_data['issuer_name'] ?? 'Apollo Plataforma Digital Ltda.' );
$issuer_cnpj = esc_html( $doc_data['issuer_cnpj'] ?? 'CNPJ 00.000.000/0001-00' );
$parts_dir   = __DIR__ . '/';
?>
<section data-panel="preview">

	<?php
	$header_mode = 'preview';
	require $parts_dir . 'sig-header-bar.php';
	?>

	<!-- Gray tray + A4 paper -->
	<div class="doc-tray">
		<div class="paper-sheet" id="pdf-p1">
			<div class="paper">

				<!-- Paper header -->
				<div class="paper-hd">
					<div class="paper-wordmark">
						<span class="wm-label">Apollo</span>
						<span class="wm-dot" aria-hidden="true"></span>
						<span class="wm-sub">Plataforma Digital</span>
					</div>
					<span class="paper-rule" aria-hidden="true"></span>
					<div class="paper-inst"><?php echo $issuer_name; ?> &mdash; <?php echo $issuer_cnpj; ?></div>
					<div class="paper-title"><?php echo $doc_title; ?></div>
					<div class="paper-docid">
						Nº <?php echo $doc_number; ?> &nbsp;&middot;&nbsp; <?php echo $doc_version; ?> &nbsp;&middot;&nbsp; <?php echo $doc_date; ?>
					</div>
				</div>

				<!-- All clauses (full, non-compact) -->
				<?php
				$compact = false;
				require $parts_dir . 'sig-paper-clauses.php';
				?>

				<!-- Signature row — preview state (waiting) -->
				<?php
				$signer_state = 'waiting';
				$panel_id     = 'p1';
				$show_stamp   = false;
				require $parts_dir . 'sig-paper-sigs.php';
				?>

				<!-- Paper footer -->
				<div class="paper-foot">
					<div class="pf-left">
						<span><?php echo $doc_number; ?> &middot; <?php echo $doc_version; ?></span>
						<span><?php printf( __( 'Emitido em: %s', 'apollo-sign' ), $doc_date ); ?></span>
					</div>
					<div class="pf-right">
						<span>HASH SHA-256: <span style="color:var(--faint);">PENDENTE</span></span>
						<span><?php printf( __( 'Verificar em: %s', 'apollo-sign' ), esc_html( $doc_data['verify_url'] ?? 'apollo.rio.br/docs/verify' ) ); ?></span>
					</div>
				</div>

			</div><!-- /paper -->

			<!-- QR + Seal -->
			<?php require $parts_dir . 'sig-paper-ornaments.php'; ?>

		</div><!-- /paper-sheet -->
	</div><!-- /doc-tray -->

	<!-- Participants strip -->
	<div class="part-strip" aria-label="<?php esc_attr_e( 'Participantes da assinatura', 'apollo-sign' ); ?>">
		<div class="part-chip ok">
			<i class="ri-checkbox-circle-fill" aria-hidden="true"></i>
			<?php echo $issuer_name; ?>
		</div>
		<div class="part-rule" aria-hidden="true"></div>
		<div class="part-chip">
			<i class="ri-time-line" aria-hidden="true"></i>
			<?php _e( 'Você — pendente', 'apollo-sign' ); ?>
		</div>
	</div>

	<!-- CTA footer -->
	<div class="pg-footer">
		<button class="btn-proceed" data-to="sign" data-dir="right" type="button">
			<i class="ri-quill-pen-line" aria-hidden="true"></i>
			<?php _e( 'Prosseguir para Assinar', 'apollo-sign' ); ?>
		</button>
	</div>

</section>
