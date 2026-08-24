<?php

/**
 * Apollo Sign — Paper Signature Row
 * Part: sig-paper-sigs.php
 *
 * Renders the two-column signature block at the bottom of the A4 paper.
 * Left col = issuer (always signed). Right col = user (dynamic state).
 *
 * Variables expected:
 *   $doc_data      — array with doc metadata
 *   $signer_state  — 'waiting' | 'signed'
 *   $stamp         — array for titan-stamp (only rendered when $signer_state === 'signed')
 *   $show_stamp    — bool — true = render titan-stamp inside sig col (done panel)
 *   $panel_id      — string — 'p1' | 'p2' | 'p3' (for unique element IDs)
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_data     = $doc_data ?? array();
$signer_state = $signer_state ?? 'waiting';
$show_stamp   = $show_stamp ?? false;
$panel_id     = $panel_id ?? 'p1';
$issuer_name  = esc_html( $doc_data['issuer_name'] ?? 'Apollo Plataforma Digital Ltda.' );
$issuer_cnpj  = esc_html( $doc_data['issuer_cnpj'] ?? 'CNPJ 00.000.000/0001-00' );
$stamp        = $stamp ?? array();
?>
<div class="paper-sig-row">

	<!-- Issuer — always signed -->
	<div class="paper-sig-col">
		<div class="paper-sig-line ink">
			<div class="sig-name"><?php echo $issuer_name; ?></div>
			<div class="sig-role"><?php echo $issuer_cnpj; ?> — Emissor</div>
		</div>
	</div>

	<!-- User signer — state-driven -->
	<div class="paper-sig-col" style="position:relative;">
		<?php if ( $signer_state === 'signed' ) : ?>
			<!-- SIGNED: rotated stamp + titan-stamp certificate -->
			<div class="sig-stamp" id="sig-stamp-<?php echo esc_attr( $panel_id ); ?>" style="opacity:1;">✦ ASSINADO</div>
			<?php if ( $show_stamp ) : ?>
				<!-- Titan stamp replaces plain text inside done-paper sig col -->
				<?php
				$stamp['context']    = 'paper';
				$stamp['element_id'] = 'titan-stamp-' . esc_attr( $panel_id );
				include __DIR__ . '/titan-stamp.php';
				?>
			<?php else : ?>
				<!-- Sign panel: line + name/date (stamp injected by JS) -->
				<div class="paper-sig-line ink" id="user-sig-line-<?php echo esc_attr( $panel_id ); ?>">
					<div class="sig-name" id="user-sig-name-<?php echo esc_attr( $panel_id ); ?>" style="color:var(--live);">
						<?php echo esc_html( $stamp['signer_name'] ?? '—' ); ?>
					</div>
					<div class="sig-role" id="user-sig-date-<?php echo esc_attr( $panel_id ); ?>">
						<?php echo esc_html( $stamp['registered'] ?? '—' ); ?>
					</div>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<!-- WAITING: dashed line, grayed out, JS target IDs -->
			<div class="sig-stamp" id="sig-stamp-<?php echo esc_attr( $panel_id ); ?>">✦ ASSINADO</div>
			<div class="paper-sig-line waiting" id="user-sig-line-<?php echo esc_attr( $panel_id ); ?>">
				<div class="sig-name" id="user-sig-name-<?php echo esc_attr( $panel_id ); ?>" style="color:var(--faint);">Responsável / Gestor</div>
				<div class="sig-role" id="user-sig-date-<?php echo esc_attr( $panel_id ); ?>">Aguardando assinatura eletrônica</div>
			</div>
		<?php endif; ?>
	</div>

</div>
