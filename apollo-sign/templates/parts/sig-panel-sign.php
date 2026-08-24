<?php

/**
 * Apollo Sign — Panel 2: Signature Placement
 * Part: sig-panel-sign.php
 *
 * Interactive signing panel with draggable rect, touch overlay,
 * ghost SVG layer, placement modal, and bottom action bar.
 *
 * Variables expected:
 *   $doc_data — array: doc_title, doc_number, doc_version, issuer_name, issuer_cnpj
 *   $clauses  — array (compact view — only essential clauses shown)
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_data    = $doc_data ?? array();
$doc_number  = esc_html( $doc_data['doc_number'] ?? 'APOLLO-DOC-0000' );
$doc_title   = esc_html( $doc_data['doc_title'] ?? 'Documento' );
$issuer_name = esc_html( $doc_data['issuer_name'] ?? 'Apollo Plataforma Digital Ltda.' );
$issuer_cnpj = esc_html( $doc_data['issuer_cnpj'] ?? 'CNPJ 00.000.000/0001-00' );
$parts_dir   = __DIR__ . '/';
?>
<section data-panel="sign">

	<?php
	$header_mode = 'sign';
	require $parts_dir . 'sig-header-bar.php';
	?>

	<?php require $parts_dir . 'sig-toolbar.php'; ?>

	<!-- Scrollable tray -->
	<div class="doc-tray" style="flex:1;">
		<div class="paper-sheet" id="pdf-p2" style="position:relative;overflow:hidden;">
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

				<!-- Clauses — compact mode shows summary text -->
				<?php
				$compact = true;
				require $parts_dir . 'sig-paper-clauses.php';
				?>

				<!-- Sig row — waiting state; JS will update to signed after btn-assinar -->
				<?php
				$signer_state = 'waiting';
				$panel_id     = 'p2';
				$show_stamp   = false;
				require $parts_dir . 'sig-paper-sigs.php';
				?>

				<!-- Paper footer -->
				<div class="paper-foot">
					<div class="pf-left"><span><?php echo $doc_number; ?></span></div>
					<div class="pf-right">
						<span>HASH: <span id="doc-hash-prev" style="color:var(--faint);">PENDING</span></span>
					</div>
				</div>

			</div><!-- /paper -->

			<!-- QR + Seal -->
			<?php require $parts_dir . 'sig-paper-ornaments.php'; ?>

			<!-- Touch overlay + ghost SVG for rect drawing -->
			<div id="touch-ov" aria-hidden="true">
				<!-- viewBox matches A4 natural size at 96dpi so coordinates always align -->
				<svg id="ghost-svg" xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 794 1123"
					preserveAspectRatio="none"></svg>

				<!-- Placement hint toast -->
				<div id="place-hint" style="
					position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
					background:rgba(18,18,20,.78);color:#fff;
					font-family:'Space Mono',monospace;font-size:11px;font-weight:700;
					text-transform:uppercase;letter-spacing:.08em;text-align:center;
					padding:10px 18px;border-radius:8px;pointer-events:none;
					line-height:1.6;white-space:nowrap;display:none;
				" aria-live="polite">
					<?php _e( 'Toque no 1º ponto', 'apollo-sign' ); ?><br>
					<span style="font-size:9px;opacity:.6;">
						<?php _e( 'depois no 2º para definir a área', 'apollo-sign' ); ?>
					</span>
				</div>
			</div>

			<!-- Interactive draggable/resizable signature rectangle -->
			<div id="sign-rect" role="presentation" aria-label="<?php esc_attr_e( 'Área de assinatura — arraste para reposicionar', 'apollo-sign' ); ?>">
				<div class="rect-inner">
					<span class="rect-lbl" id="rect-lbl"><?php _e( 'Assinatura', 'apollo-sign' ); ?></span>
					<span class="rect-dim" id="rect-dim"><?php _e( 'posicione', 'apollo-sign' ); ?></span>
				</div>
				<div class="rh rh-tl" aria-hidden="true"></div>
				<div class="rh rh-tr" aria-hidden="true"></div>
				<div class="rh rh-bl" aria-hidden="true"></div>
				<div class="rh rh-br" aria-hidden="true"></div>
			</div>

			<!-- Placement overlay modal -->
			<?php require $parts_dir . 'sig-overlay-modal.php'; ?>

		</div><!-- /pdf-p2 -->
	</div><!-- /doc-tray -->

	<!-- Bottom action bar -->
	<?php require $parts_dir . 'sig-bottom-bar.php'; ?>

</section>
