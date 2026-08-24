<?php
/**
 * Template Part: Document Info
 * Card displaying document title, ID, and creation date.
 *
 * @package Apollo\Sign
 * @var WP_Post|null $doc
 * @var array        $sign_data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="sign-doc-info">
	<div class="sign-doc-title"><?php echo esc_html( $doc ? $doc->post_title : 'Documento #' . $sign_data['doc_id'] ); ?></div>
	<div class="sign-doc-meta">
		<div class="sign-doc-row">
			<span class="sign-doc-label">Documento</span>
			<span class="sign-doc-value">#<?php echo esc_html( $sign_data['doc_id'] ); ?></span>
		</div>
		<div class="sign-doc-row">
			<span class="sign-doc-label">Criado em</span>
			<span class="sign-doc-value"><?php echo esc_html( date( 'd/m/Y H:i', strtotime( $sign_data['created_at'] ) ) ); ?></span>
		</div>
	</div>
</div>
