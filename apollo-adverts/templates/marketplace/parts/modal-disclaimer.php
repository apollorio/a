<?php

/**
 * Template Part: Modal Disclaimer
 *
 * CRITICAL: This modal MUST be shown before opening any chat.
 * Users MUST check the consent checkbox to unlock the chat button.
 *
 * @package Apollo\Classifieds
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="modal" id="apollo-classifieds-modal">
	<div class="modal-content">
		<div class="modal-header-warning">
			<i class="ri-alarm-warning-fill"></i>
			<h2>AVISO IMPORTANTE</h2>
		</div>

		<div class="legal-text">
			<p>O apollo::rio existe apenas para <b>conectar pessoas</b>. Não vendemos ingressos, não processamos pagamentos e não garantimos reservas. Somos o mural de avisos, mas a negociação é 100% entre você e a outra pessoa.</p>

			<p><b>Lembre-se sempre:</b></p>
			<ul class="safety-list">
				<li>
					<i class="ri-number-1"></i>
					<div><b>Chat Interno:</b> Embora utilizemos um chat interno no Apollo, não monitoramos ou lemos o conteúdo. Assim que a conversa inicia, a responsabilidade é sua.</div>
				</li>
				<li>
					<i class="ri-number-2"></i>
					<div><b>Investigue:</b> Use o filtro de "Amigos em Comum" nas redes sociais ou verifique se o User<font style="font-family:system-ui; font-size:110%">::</font>rio possui o selo "<i class="ri-verified-badge-line"></i> Confiável" no Apollo.</div>
				</li>
				<li>
					<i class="ri-number-3"></i>
					<div><b>Segurança em 1º lugar:</b> Evite pagamentos antecipados para desconhecidos.</div>
				</li>
			</ul>

			<p class="warning-highlight">Atenção: O apollo::rio não se responsabiliza por golpes, ingressos falsos ou desistências. Ao clicar abaixo, você declara que é o único responsável por sua negociação.</p>
		</div>

		<div class="modal-consent">
			<div class="checkbox-wrapper-modal">
				<input type="checkbox" id="modal-consent-check">
				<label for="modal-consent-check">
					<div class="custom-check-box"></div>
					<span>Entendo que o apollo::rio não participa da venda e assumo total responsabilidade pela negociação.</span>
				</label>
			</div>
		</div>

		<div class="modal-actions">
			<button id="btn-proceed-chat" class="btn-modal-main" data-target-user="" data-classified-id="" data-username="">INICIAR CHAT</button>
			<button class="btn-text-only btn-close-modal">Cancelar e voltar</button>
		</div>
	</div>
</div>