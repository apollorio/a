<?php

/**
 * Apollo Sign — Paper Ornaments (QR + ICP Seal)
 * Part: sig-paper-ornaments.php
 *
 * Renders the absolute-positioned QR code placeholder
 * and the ICP-Brasil A3 circular seal.
 * Both sit inside .paper-sheet (position:relative).
 *
 * Variables expected:
 *   $doc_data — array with doc metadata (doc_number, verify_url)
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_data   = $doc_data ?? array();
$verify_url = esc_url( $doc_data['verify_url'] ?? 'https://apollo.rio.br/docs/verify' );
?>
<!-- ICP-Brasil A3 Seal -->
<div class="paper-seal" title="ICP-Brasil A3 — Assinatura Digital Válida" aria-label="Selo ICP-Brasil A3">
	<span class="seal-star" aria-hidden="true">✦</span>
	<span class="seal-txt">ICP-Brasil<br>A3</span>
</div>

<!-- QR Code — verify URL -->
<div class="paper-qr" title="<?php echo esc_attr( 'Verificar em: ' . $verify_url ); ?>" role="img" aria-label="QR Code de verificação do documento">
	<div class="paper-qr-inner" aria-hidden="true"></div>
</div>
