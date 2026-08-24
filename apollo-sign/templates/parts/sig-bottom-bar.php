<?php

/**
 * Apollo Sign — Sign Panel Bottom Action Bar
 * Part: sig-bottom-bar.php
 *
 * Renders the collapsible bottom bar on the sign panel:
 *   - Collapse / expand toggle pill
 *   - Agreement checkbox
 *   - Position + Reset + Sign action row
 *   - Security note
 *
 * Variables expected:
 *   $doc_data — array: doc_number
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_data   = $doc_data ?? array();
$doc_number = esc_html( $doc_data['doc_number'] ?? '—' );
?>
<div class="sign-bottom" id="sign-bottom">

	<!-- Toggle pill — sits above the border, outside inner so it's never clipped -->
	<button class="sbottom-toggle" id="sbottom-toggle"
		title="<?php esc_attr_e( 'Minimizar / Expandir', 'apollo-sign' ); ?>"
		aria-expanded="true" aria-controls="sign-bottom-inner">
		<i class="ri-arrow-down-s-line" aria-hidden="true"></i>
	</button>

	<!-- Collapsible content -->
	<div class="sign-bottom-inner" id="sign-bottom-inner">

		<p class="sign-pg-title"><?php _e( 'Validar &amp; Assinar', 'apollo-sign' ); ?></p>
		<p class="sign-pg-sub"><?php printf( __( 'Documento Nº %s', 'apollo-sign' ), $doc_number ); ?></p>

		<label class="custom-checkbox" class="chk-wrap">
			<input type="checkbox" id="chk-agree">
			<div class="chk-box"></div>
			<span class="chk-text">
				<?php _e( 'De acordo com os termos e ciente da responsabilidade pelo cumprimento integral das cláusulas deste instrumento.', 'apollo-sign' ); ?>
			</span>
		</label>

		<div class="action-row">
			<!-- Reset signature area (hidden until a rect is placed) -->
			<button class="btn-reset-sig" id="btn-reset-sig"
				title="<?php esc_attr_e( 'Resetar área de assinatura', 'apollo-sign' ); ?>"
				aria-label="<?php esc_attr_e( 'Resetar área de assinatura', 'apollo-sign' ); ?>">
				<i class="ri-refresh-line" aria-hidden="true"></i>
			</button>

			<!-- Position button -->
			<button class="btn-pos" id="btn-pos" type="button">
				<i class="ri-drag-move-2-line" aria-hidden="true"></i>
				<span id="btn-pos-lbl"><?php _e( 'Posicionar', 'apollo-sign' ); ?></span>
			</button>

			<!-- Sign button (disabled until checkbox + rect are confirmed) -->
			<button class="btn-assinar" id="btn-assinar" type="button" disabled
				aria-label="<?php esc_attr_e( 'Assinar documento digitalmente', 'apollo-sign' ); ?>">
				<i class="ri-quill-pen-line" aria-hidden="true"></i>
				<?php _e( 'Assinar', 'apollo-sign' ); ?>
			</button>
		</div>

		<div class="lock-note" aria-label="<?php esc_attr_e( 'Informações de segurança', 'apollo-sign' ); ?>">
			<i class="ri-lock-2-line" aria-hidden="true"></i>
			<?php _e( 'Criptografado ponta-a-ponta &nbsp;·&nbsp; ICP-Brasil A3 &nbsp;·&nbsp; Lei 14.063/2020', 'apollo-sign' ); ?>
		</div>

	</div><!-- /sign-bottom-inner -->
</div><!-- /sign-bottom -->