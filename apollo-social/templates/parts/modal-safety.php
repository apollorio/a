<?php

/**
 * Template Part: Safety Modal — Classifieds Disclaimer
 *
 * Before opening inbox/chat for a classified ad, user MUST:
 * 1. Read the safety warning
 * 2. Check the consent checkbox (custom styled)
 * 3. Only then the real "Abrir conversa" button becomes enabled
 *
 * Uses .modal / .modal-content / .custom-check per approved design.
 *
 * @package Apollo\Social
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="modal" id="modal-safety" role="dialog" aria-modal="true" aria-labelledby="modal-safety-title" style="display:none;">
	<div class="modal-content modal-safety-content">
		<div class="modal-header">
			<i class="ri-shield-check-fill"></i>
			<h3 id="modal-safety-title">Aviso de Segurança</h3>
		</div>
		<p>Antes de iniciar uma conversa sobre este anúncio, leia atentamente:</p>
		<div class="safety-list">
			<div class="safety-item"><i class="ri-checkbox-circle-fill"></i> Nunca envie dinheiro antecipadamente sem confirmar a autenticidade</div>
			<div class="safety-item"><i class="ri-checkbox-circle-fill"></i> Prefira encontros em locais públicos para transações presenciais</div>
			<div class="safety-item"><i class="ri-checkbox-circle-fill"></i> Desconfie de ofertas muito abaixo do mercado</div>
			<div class="safety-item"><i class="ri-checkbox-circle-fill"></i> Apollo não se responsabiliza por transações entre User<font style="font-family:system-ui; font-size:110%">::</font>rios</div>
		</div>

		<div class="modal-consent">
			<label class="custom-checkbox" class="checkbox-wrapper">
				<input type="checkbox" id="safety-consent-check">
				<span class="custom-check"></span>
				<span>Li e compreendo os riscos. Desejo prosseguir.</span>
			</label>
		</div>

		<div class="modal-actions">
			<button class="btn-cancel" id="safety-cancel-btn">Cancelar</button>
			<button class="btn-modal-main" id="safety-proceed-btn" disabled>
				<i class="ri-chat-1-fill"></i> Abrir conversa
			</button>
		</div>
	</div>
</div>