<?php

/**
 * Apollo Sign — Titan Stamp (Digital Certificate Block)
 * Part: titan-stamp.php
 *
 * Renders the official digital signature certificate block.
 * Used in two contexts:
 *   1. Inside the done-paper A4 (sig column, scaled via parent)
 *   2. Inside #print-certificate (full A4 print layout)
 *
 * Variables expected ($stamp array from parent):
 *   $stamp['signer_name']  — Full name (string)
 *   $stamp['doc_cpf']      — Masked CPF (string)
 *   $stamp['registered']   — Date/time string (string)
 *   $stamp['hash']         — Full SHA-256 hash (string)
 *   $stamp['context']      — 'paper' | 'print' — controls sizing class
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Hold raw values and escape at the point of output (WPCS "escape late").
$stamp        = $stamp ?? array();
$signer_name  = $stamp['signer_name'] ?? '—';
$doc_cpf      = $stamp['doc_cpf'] ?? '—';
$registered   = $stamp['registered'] ?? '—';
$hash         = $stamp['hash'] ?? str_repeat( '0', 64 );
$context      = $stamp['context'] ?? 'paper'; // 'paper' | 'print'
$stamp_id     = $stamp['element_id'] ?? 'titan-stamp-main';
$verify_hash  = $stamp['verify_hash'] ?? $stamp['hash'] ?? '';
$verify_url   = $verify_hash ? home_url( '/verificar/' . $verify_hash ) : '';
$content_hash = $stamp['content_hash'] ?? '';
?>
<div class="titan-stamp titan-stamp--<?php echo esc_attr( $context ); ?>" id="<?php echo esc_attr( $stamp_id ); ?>" role="img" aria-label="Certificado de Assinatura Digital Apollo">

	<!-- Apollo Platform watermark icon (SVG) -->
	<svg class="ts-watermark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="0.5" stroke-linejoin="round" aria-hidden="true">
		<path d="M10.208521 14.887119l2.975605 0c2.878458 0 4.409489 0.720844 6.359568 2.627895c0.108236 0.105828 0.12279 0.278202 0.020394 0.389215l-2.484774 2.694557c-0.102376 0.110992 -0.286064 0.130942 -0.389215 0.020407l-3.228883 -3.454773 0 4.553169c0 0.151587 -0.124013 0.2756 -0.2756 0.2756l-2.975605 0c-0.151574 0 -0.2756 -0.124013 -0.2756 -0.2756l0 -6.554906c0 -0.151574 0.124026 -0.275587 0.2756 -0.275587zM9.106143 10.203854l0 2.975605c0 2.878458 -0.720832 4.409489 -2.627895 6.359581c-0.105828 0.108223 -0.278215 0.122777 -0.389215 0.020381l-2.69457 -2.484774c-0.110979 -0.102376 -0.130929 -0.286064 -0.020407 -0.389215l3.454773 -3.228883 -4.553169 0c-0.151574 0 -0.275587 -0.124026 -0.275587 -0.275613l0 -2.975592c0 -0.151587 0.124013 -0.2756 0.275587 -0.2756l6.554906 0c0.151574 0 0.2756 0.124013 0.2756 0.2756zM13.791479 9.115478l-2.975592 0c-2.878458 0 -4.409489 -0.720844 -6.359581 -2.627895c-0.108223 -0.105828 -0.122777 -0.278202 -0.020394 -0.389215l2.484774 -2.69457c0.102363 -0.110979 0.286051 -0.130929 0.389215 -0.020407l3.228883 3.454773 0 -4.553169c0 -0.151574 0.124026 -0.275587 0.2756 -0.275587l2.975592 0c0.151587 0 0.275613 0.124013 0.275613 0.275587l0 6.554906c0 0.151587 -0.124026 0.275613 -0.275613 0.275613zM14.893857 13.783143l0 -2.975592c0 -2.878458 0.720832 -4.409489 2.627895 -6.359581c0.105828 -0.108236 0.278202 -0.12279 0.389215 -0.020394l2.694557 2.484774c0.110992 0.102376 0.130942 0.286064 0.02042 0.389215l-3.454786 3.228883 4.553182 0c0.151574 0 0.275587 0.124026 0.275587 0.275613l0 2.975592c0 0.151587 -0.124013 0.2756 -0.275587 0.2756l-6.554906 0c-0.151574 0 -0.275587 -0.124013 -0.275587 -0.2756z" />
	</svg>

	<div class="ts-header">ASSINATURA DIGITAL &bull; SISTEMA APOLLO.RIO.BR</div>

	<div class="ts-label">Signatário</div>
	<div class="ts-name"><?php echo esc_html( $signer_name ); ?></div>

	<div class="ts-meta">
		<span class="ts-label">CPF</span>
		<span class="ts-val"><?php echo esc_html( $doc_cpf ); ?></span>
	</div>
	<div class="ts-meta">
		<span class="ts-label">Registro (ACT)</span>
		<span class="ts-val"><?php echo esc_html( $registered ); ?></span>
	</div>

	<div class="ts-hash">
		<b>HASH SHA-256 DO DOCUMENTO</b>
		<span class="ts-hash-val" id="<?php echo esc_attr( $stamp_id ); ?>-hash"><?php echo esc_html( $hash ); ?></span>
	</div>

	<?php if ( $content_hash ) : ?>
	<div class="ts-hash">
		<b>HASH SHA-256 DO CONTEÚDO ASSINADO</b>
		<span class="ts-hash-val"><?php echo esc_html( $content_hash ); ?></span>
	</div>
	<?php endif; ?>

	<?php if ( $verify_url ) : ?>
	<div class="ts-verify-row">
		<!-- QR code rendered client-side via Apollo CDN qrcode utility (if available) -->
		<div class="ts-qr" id="<?php echo esc_attr( $stamp_id ); ?>-qr" data-verify-url="<?php echo esc_url( $verify_url ); ?>" aria-label="QR Code de verificação"></div>
		<div class="ts-verify-url">
			<b>VERIFICAR EM:</b>
			<span><?php echo esc_html( $verify_url ); ?></span>
		</div>
	</div>
	<?php endif; ?>

</div>
