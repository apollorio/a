<?php

/**
 * Apollo Sign — Panel 3: Signing Done
 * Part: sig-panel-done.php
 *
 * Confirmation panel with:
 *   Desktop: paper + aside side-by-side
 *   Mobile: done-info (aside) ↔ done-doc (paper) — slide sub-views
 *
 * The paper signature column renders the TITAN-STAMP certificate.
 *
 * Variables expected:
 *   $doc_data    — array: doc_title, doc_number, doc_version, issuer_name, issuer_cnpj, verify_url
 *   $signer_data — array: name, role, cpf_masked
 *   $clauses     — array (compact)
 *   $stamp       — array for titan-stamp (populated by JS → PHP pre-populates defaults)
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_data    = $doc_data ?? array();
$signer_data = $signer_data ?? array();
$doc_number  = esc_html( $doc_data['doc_number'] ?? 'APOLLO-DOC-0000' );
$doc_title   = esc_html( $doc_data['doc_title'] ?? 'Documento' );
$issuer_name = esc_html( $doc_data['issuer_name'] ?? 'Apollo Plataforma Digital Ltda.' );
$issuer_cnpj = esc_html( $doc_data['issuer_cnpj'] ?? 'CNPJ 00.000.000/0001-00' );
$parts_dir   = __DIR__ . '/';

// Pre-populate stamp defaults — JS will overwrite all values at signing time
$stamp = array(
	'signer_name' => $signer_data['name'] ?? '—',
	'doc_cpf'     => $signer_data['cpf_masked'] ?? '—',
	'registered'  => '—',
	'hash'        => str_repeat( '0', 64 ),
	'context'     => 'paper',
	'element_id'  => 'titan-stamp-p3',
);
?>
<section data-panel="done">

	<?php
	$header_mode = 'done';
	require $parts_dir . 'sig-header-bar.php';
	?>

	<div class="done-scroll">

		<!-- ═══════════════════════════════════════════════════
			MOBILE SUB-VIEW A — done-info (primary landing)
			Desktop: display:contents → renders normally in flex row.
		════════════════════════════════════════════════════════ -->
		<div class="done-info" id="done-info">
			<?php require $parts_dir . 'sig-done-aside.php'; ?>
		</div><!-- /done-info -->


		<!-- ═══════════════════════════════════════════════════
			MOBILE SUB-VIEW B — done-doc (paper preview)
			Slides in from right on "Visualizar documento".
			Desktop: display:contents → renders alongside aside.
		════════════════════════════════════════════════════════ -->
		<div class="done-doc" id="done-doc">

			<!-- Back button (mobile only) -->
			<div class="done-doc-back" id="done-doc-back" role="button" tabindex="0"
				aria-label="<?php esc_attr_e( 'Voltar para informações do documento', 'apollo-sign' ); ?>">
				<i class="ri-arrow-left-s-line" aria-hidden="true"></i>
				<?php _e( 'Voltar', 'apollo-sign' ); ?>
			</div>

			<!-- Scaled A4 paper — shows titan-stamp in sig col -->
			<div class="done-paper">
				<div class="paper-sheet" id="pdf-p3" style="position:relative;overflow:hidden;">
					<div class="paper">

						<!-- Paper header (compact) -->
						<div class="paper-hd">
							<div class="paper-wordmark">
								<span class="wm-label">Apollo</span>
								<span class="wm-dot" aria-hidden="true"></span>
								<span class="wm-sub">Plataforma Digital</span>
							</div>
							<span class="paper-rule" aria-hidden="true"></span>
							<div class="paper-title"><?php echo $doc_title; ?></div>
							<div class="paper-docid">Nº <?php echo $doc_number; ?></div>
						</div>

						<!-- Clauses — compact -->
						<?php
						$compact = true;
						require $parts_dir . 'sig-paper-clauses.php';
						?>

						<!-- Sig row — signed + TITAN STAMP in user col -->
						<?php
						$signer_state = 'signed';
						$panel_id     = 'p3';
						$show_stamp   = true;   // render titan-stamp inside sig col
						require $parts_dir . 'sig-paper-sigs.php';
						?>

						<!-- Paper footer -->
						<div class="paper-foot">
							<div class="pf-left">
								<span><?php echo $doc_number; ?></span>
							</div>
							<div class="pf-right">
								<span style="color:var(--live);">
									HASH: <span id="done-hash-short">—</span>
								</span>
							</div>
						</div>

					</div><!-- /paper -->

					<!-- QR + Seal -->
					<?php require $parts_dir . 'sig-paper-ornaments.php'; ?>

					<!-- Signature placement rect indicator (mirrored from panel 2 by JS) -->
					<div id="done-sig-rect" style="display:none;" role="presentation" aria-hidden="true"></div>

				</div><!-- /pdf-p3 -->
			</div><!-- /done-paper -->

		</div><!-- /done-doc -->

	</div><!-- /done-scroll -->

</section>
