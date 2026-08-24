<?php
/**
 * Template Part: PDF Viewer
 * Canvas for PDF.js rendering + signature placement overlay.
 * Only shown when document has an attached PDF and status is pending.
 *
 * @package Apollo\Sign
 * @var array  $sign_data
 * @var string $status
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pdf_url = $sign_data['pdf_url'] ?? '';
if ( empty( $pdf_url ) || $status !== 'pending' ) {
	return;
}
?>
<div id="apollo-pdf-section" class="sign-pdf-section" data-pdf-url="<?php echo esc_url( $pdf_url ); ?>">
	<div class="sign-pdf-toolbar">
		<div class="sign-pdf-page-info">
			<span id="apollo-pdf-page-label">Página <span id="apollo-pdf-page-num">1</span> de <span id="apollo-pdf-page-total">1</span></span>
		</div>
		<div class="sign-pdf-nav">
			<button type="button" id="apollo-pdf-prev" class="sign-pdf-nav-btn" disabled aria-label="Página anterior">
				<i class="ri-arrow-left-s-line"></i>
			</button>
			<button type="button" id="apollo-pdf-next" class="sign-pdf-nav-btn" aria-label="Próxima página">
				<i class="ri-arrow-right-s-line"></i>
			</button>
		</div>
	</div>

	<div id="apollo-pdf-wrap" class="sign-pdf-wrap">
		<canvas id="apollo-pdf-canvas"></canvas>
		<div id="apollo-sign-overlay" class="sign-overlay">
			<div id="apollo-sign-rect" class="sign-rect" style="display:none;">
				<div class="sign-rect-label">Assinatura</div>
				<div class="sign-rect-handle sign-rect-handle--nw"></div>
				<div class="sign-rect-handle sign-rect-handle--ne"></div>
				<div class="sign-rect-handle sign-rect-handle--sw"></div>
				<div class="sign-rect-handle sign-rect-handle--se"></div>
			</div>
		</div>
	</div>

	<div class="sign-placement-actions">
		<button type="button" id="apollo-place-btn" class="sign-btn sign-btn-secondary">
			<i class="ri-drag-move-2-fill"></i> Posicionar Assinatura
		</button>
		<button type="button" id="apollo-reset-btn" class="sign-btn-ghost" style="display:none;">
			<i class="ri-refresh-line"></i> Resetar Posição
		</button>
	</div>

	<div id="apollo-placement-toast" class="sign-toast" style="display:none;"></div>
</div>
