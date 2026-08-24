<?php

/**
 * Apollo Sign — Signature Placement Overlay Modal
 * Part: sig-overlay-modal.php
 *
 * The overlay shown on the sign panel before any rect is placed.
 * Contains the animated sim-wrap, "Definir manualmente" and
 * "Inserir no rodapé" buttons.
 *
 * No variables required — fully self-contained.
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="sign-overlay" role="dialog" aria-modal="true" aria-labelledby="ov-title-lbl">
	<div id="ov-bg"></div>
	<div id="ov-card">
		<div class="ov-icon" aria-hidden="true"><i class="ri-fingerprint-line"></i></div>
		<div class="ov-title" id="ov-title-lbl"><?php _e( 'Posicionar Assinatura', 'apollo-sign' ); ?></div>
		<div class="ov-sub"><?php _e( 'Toque dois pontos opostos para definir a área de assinatura no documento.', 'apollo-sign' ); ?></div>

		<!-- Animated preview of the placement interaction -->
		<div class="sim-wrap" aria-hidden="true">
			<div class="sim-lines">
				<div class="sim-line"></div>
				<div class="sim-line"></div>
				<div class="sim-line"></div>
			</div>
			<div class="sim-rect"></div>
			<div class="sim-dot"></div>
			<div class="sim-dot2"></div>
			<span class="sim-hand">👆</span>
			<span class="sim-label"><?php _e( 'Assinatura', 'apollo-sign' ); ?></span>
		</div>

		<button id="btn-iniciar" type="button">
			<i class="ri-cursor-line" aria-hidden="true"></i>
			<?php _e( 'Definir manualmente', 'apollo-sign' ); ?>
		</button>
		<button id="btn-auto" type="button">
			<i class="ri-layout-bottom-line" aria-hidden="true"></i>
			<?php _e( 'Inserir no rodapé', 'apollo-sign' ); ?>
		</button>
	</div>
</div>
