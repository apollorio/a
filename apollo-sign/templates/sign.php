<?php

/**
 * Apollo Sign — Virtual Signing Page (SPA — 3 Panels)
 * URL: /assinar/{hash}
 * Blank Canvas — zero theme, Apollo CDN only.
 *
 * Flow: Panel 1 (preview) → Panel 2 (sign) → Panel 3 (done)
 *
 * Parts (sig- prefix = new SPA system):
 *   sig-head.php          — <!DOCTYPE>, meta, CDN, fonts (leaves <head> open)
 *   sig-styles.php        — <style> block with all tokens + components
 *   sig-panel-preview.php — Panel 1: read-only A4 + participants strip + CTA
 *   sig-panel-sign.php    — Panel 2: interactive doc + placement rect + action bar
 *   sig-panel-done.php    — Panel 3: confirmation + titan-stamp + download
 *   sig-scripts.php       — Full SPA JS (GSAP navigation, interact.js, REST sign, stamp)
 *
 * @package Apollo\Sign
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── Raw data from Plugin::handle_virtual_page() ─────────────────────────── */
$sign_data = $GLOBALS['apollo_sign_data'] ?? null;
if ( ! $sign_data ) {
	wp_die( 'Dados inválidos.', 'Assinatura', array( 'response' => 400 ) );
}

$sig_id    = absint( $sign_data['id'] ?? 0 );
$doc_id    = absint( $sign_data['doc_id'] ?? 0 );
$status    = sanitize_text_field( $sign_data['status'] ?? 'pending' );
$hash_val  = sanitize_text_field( $sign_data['hash'] ?? '' );
$signed_at = $sign_data['signed_at'] ?? null;
$sig_hash  = sanitize_text_field( $sign_data['signature_data'] ?? str_repeat( '0', 64 ) );

/* ── Document post ───────────────────────────────────────────────────────── */
$doc_post    = $doc_id ? get_post( $doc_id ) : null;
$doc_title   = $doc_post ? get_the_title( $doc_post ) : __( 'Documento', 'apollo-sign' );
$doc_number  = get_post_meta( $doc_id, '_doc_number', true ) ?: sprintf( 'APOLLO-%04d', $doc_id );
$doc_version = get_post_meta( $doc_id, '_doc_version', true ) ?: 'v1.0';
$doc_date    = $doc_post ? get_the_date( 'd.m.Y', $doc_post ) : wp_date( 'd.m.Y' );
$issuer_name = get_post_meta( $doc_id, '_doc_issuer_name', true ) ?: get_bloginfo( 'name' );
$issuer_cnpj = get_post_meta( $doc_id, '_doc_issuer_cnpj', true ) ?: '';
$verify_url  = home_url( '/verificar/' . $hash_val );

/* ── Clauses (from doc post-meta or collapsed content fallback) ──────────── */
$clauses_raw = get_post_meta( $doc_id, '_doc_clauses', true );
$clauses     = is_array( $clauses_raw ) ? $clauses_raw : array();
if ( empty( $clauses ) && $doc_post ) {
	$clauses = array(
		array(
			'label'   => __( 'Objeto', 'apollo-sign' ),
			'content' => wp_strip_all_tags( apply_filters( 'the_content', $doc_post->post_content ) ),
		),
	);
}

/* ── Signer ──────────────────────────────────────────────────────────────── */
$signer_user = ! empty( $sign_data['signer_id'] ) ? get_userdata( (int) $sign_data['signer_id'] ) : null;
$signer_name = sanitize_text_field(
	$sign_data['signer_name'] ?: ( $signer_user ? $signer_user->display_name : __( 'Signatário', 'apollo-sign' ) )
);
$signer_role = sanitize_text_field(
	get_post_meta( $doc_id, '_doc_signer_role', true ) ?: __( 'Signatário', 'apollo-sign' )
);
$signer_cpf  = sanitize_text_field( $sign_data['signer_cpf'] ?? '' );
/* Normalise: strip formatting → 11 raw digits (ICP-Brasil cert CN stores raw digits) */
$cpf_digits = preg_replace( '/\D/', '', $signer_cpf );
$cpf_masked = ( strlen( $cpf_digits ) === 11 )
	? substr( $cpf_digits, 0, 3 ) . '.***.***-' . substr( $cpf_digits, 9, 2 )
	: ( $signer_cpf ?: '—' );

/* ── Titan-stamp defaults (JS will overwrite at signing time) ────────────── */
$registered = ( $signed_at && $signed_at !== '0000-00-00 00:00:00' )
	? wp_date( 'd/m/Y \à\s H:i:s', strtotime( $signed_at ) )
	: '—';

/* ── Composite arrays shared across all parts ────────────────────────────── */
$doc_data = array(
	'doc_title'   => $doc_title,
	'doc_number'  => $doc_number,
	'doc_version' => $doc_version,
	'doc_date'    => $doc_date,
	'issuer_name' => $issuer_name,
	'issuer_cnpj' => $issuer_cnpj,
	'verify_url'  => $verify_url,
	'status'      => $status,
);

$signer_data = array(
	'name'       => $signer_name,
	'role'       => $signer_role,
	'cpf_masked' => $cpf_masked,
);

$stamp = array(
	'signer_name'  => $signer_name,
	'doc_cpf'      => $cpf_masked,
	'registered'   => $registered,
	'hash'         => ( $status === 'signed' ) ? $sig_hash : str_repeat( '0', 64 ),
	'verify_hash'  => $hash_val,
	'content_hash' => sanitize_text_field( $sign_data['content_hash'] ?? '' ),
	'context'      => 'paper',
	'element_id'   => 'titan-stamp-p3',
);

/* ── JS config — injected before sig-scripts.php ────────────────────────── */
$js_config = array(
	'sigId'      => $sig_id,
	'hash'       => $hash_val,
	'status'     => $status,
	'nonce'      => wp_create_nonce( 'apollo_sign_nonce' ),
	'restNonce'  => wp_create_nonce( 'wp_rest' ),
	'restUrl'    => esc_url_raw( rest_url( 'apollo/v1/' ) ),
	'ajaxUrl'    => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
	'pdfUrl'     => esc_url_raw( $sign_data['pdf_url'] ?? '' ),
	'signerName' => $signer_name,
	'cpfMasked'  => $cpf_masked,
	'docNumber'  => $doc_number,
	'cdnUrl'     => defined( 'APOLLO_CDN_URL' ) ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/',
	'verifyUrl'  => $hash_val ? esc_url_raw( home_url( '/verificar/' . $hash_val ) ) : '',
	'contentHash' => sanitize_text_field( $sign_data['content_hash'] ?? '' ),
);

$parts = __DIR__ . '/parts/';

/*
═══════════════════════════════════════════════════════════
	HEAD   (outputs <!DOCTYPE html><html><head>… leaves it open)
═══════════════════════════════════════════════════════════ */
require $parts . 'sig-head.php';

/* Inline styles appended inside <head> */
require $parts . 'sig-styles.php';
?>
</head>

<body class="apollo-sign-spa<?php echo ( $status === 'signed' ) ? ' is-signed' : ''; ?>"
	data-status="<?php echo esc_attr( $status ); ?>">

	<div class="sign-wrap" id="sign-wrap">

		<!-- ══ PANEL 1 — Preview ═══════════════════════════════════ -->
		<?php require $parts . 'sig-panel-preview.php'; ?>

		<!-- ══ PANEL 2 — Sign ══════════════════════════════════════ -->
		<?php require $parts . 'sig-panel-sign.php'; ?>

		<!-- ══ PANEL 3 — Done ══════════════════════════════════════ -->
		<?php require $parts . 'sig-panel-done.php'; ?>

	</div><!-- /sign-wrap -->

	<!-- Apollo Sign JS bootstrap config -->
	<script>
		window.APOLLO_SIGN = <?php echo wp_json_encode( $js_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
	</script>

	<!-- ══ SCRIPTS (SPA engine) ════════════════════════════════════ -->
	<?php require $parts . 'sig-scripts.php'; ?>

</body>

</html>
