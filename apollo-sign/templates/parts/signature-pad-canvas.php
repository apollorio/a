<?php
/**
 * Template Part: Signature Pad Canvas
 * Hand-drawn signature capture — draw on canvas or upload image.
 * Adapted from: _library/signing doc/advanced-pdf-esignature-main signaturePad.js
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
<div class="sigpad-section">
	<button type="button" id="apollo-sigpad-toggle" class="sigpad-toggle">
		<i class="ri-edit-line"></i>
		Assinatura Visual (opcional)
		<i class="ri-arrow-down-s-line sigpad-toggle-arrow"></i>
	</button>

	<div id="apollo-sigpad-section" style="display:none;">
		<div class="sigpad-inner">
			<div class="sigpad-label">
				<span>Desenhe sua assinatura</span>
				<span class="sigpad-label-hint">ou faça upload de uma imagem</span>
			</div>

			<div class="sigpad-canvas-wrap" id="apollo-sigpad-wrap">
				<canvas id="apollo-sigpad-canvas"></canvas>
			</div>

			<div class="sigpad-toolbar">
				<button type="button" id="apollo-sigpad-undo" class="sigpad-btn" title="Desfazer último traço">
					<i class="ri-arrow-go-back-line"></i>
				</button>
				<button type="button" id="apollo-sigpad-clear" class="sigpad-btn sigpad-btn--danger" title="Limpar tudo">
					<i class="ri-delete-bin-7-line"></i>
				</button>

				<div class="sigpad-sep"></div>

				<div class="sigpad-color-wrap" title="Cor da caneta">
					<input type="color" id="apollo-sigpad-color" class="sigpad-color-input" value="#e8e8ec">
				</div>

				<div class="sigpad-sep"></div>

				<button type="button" id="apollo-sigpad-upload" class="sigpad-btn" title="Upload de imagem de assinatura">
					<i class="ri-upload-2-line"></i> Imagem
				</button>
				<input type="file" id="apollo-sigpad-file" class="sigpad-file-hidden" accept="image/png,image/jpeg,image/webp,image/svg+xml">
			</div>

			<div class="sigpad-preview" id="apollo-sigpad-preview-wrap">
				<div class="sigpad-preview-label">Preview</div>
				<img id="apollo-sigpad-preview" alt="Preview da assinatura" style="display:none;">
			</div>
		</div>
	</div>

	<!-- Hidden field: base64 PNG of drawn/uploaded signature -->
	<input type="hidden" id="sign-signature-image" name="signature_image" value="">
</div>