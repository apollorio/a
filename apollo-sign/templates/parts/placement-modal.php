<?php
/**
 * Template Part: Placement Modal
 * Fullscreen overlay for choosing signature placement method.
 * Shows "manual" vs "auto footer" options.
 *
 * @package Apollo\Sign
 * @var string $status
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $status !== 'pending' ) {
	return;
}
?>
<div id="apollo-placement-modal" class="sign-modal" style="display:none;">
	<div class="sign-modal-backdrop"></div>
	<div class="sign-modal-content">
		<div class="sign-modal-header">
			<h3><i class="ri-map-pin-add-fill"></i> Posicionar Assinatura</h3>
			<button type="button" id="apollo-modal-close" class="sign-modal-close" aria-label="Fechar">
				<i class="ri-close-line"></i>
			</button>
		</div>

		<div class="sign-modal-body">
			<p class="sign-modal-desc">Escolha como posicionar sua assinatura visual no documento PDF.</p>

			<button type="button" class="sign-modal-option" data-placement="manual">
				<div class="sign-modal-option-icon"><i class="ri-drag-move-2-fill"></i></div>
				<div class="sign-modal-option-text">
					<strong>Posicionar manualmente</strong>
					<span>Arraste e redimensione o retângulo de assinatura diretamente no PDF</span>
				</div>
				<i class="ri-arrow-right-s-line"></i>
			</button>

			<button type="button" class="sign-modal-option" data-placement="auto_footer">
				<div class="sign-modal-option-icon"><i class="ri-align-bottom"></i></div>
				<div class="sign-modal-option-text">
					<strong>Rodapé automático</strong>
					<span>Assinatura inserida automaticamente no rodapé da última página</span>
				</div>
				<i class="ri-arrow-right-s-line"></i>
			</button>
		</div>
	</div>
</div>
