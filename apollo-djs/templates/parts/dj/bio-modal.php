<?php
/**
 * Template Part: DJ Single - Bio Modal
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;
if ( empty( $dj_bio_full ) ) {
	return;
}
?>
<div class="dj-bio-modal-backdrop" id="bioBackdrop" data-open="false" role="dialog" aria-modal="true" aria-labelledby="dj-bio-modal-title">
	<div class="dj-bio-modal">
		<div class="dj-bio-modal-header">
			<h3 id="dj-bio-modal-title">
				<?php printf( esc_html__( 'Bio completa · %s', 'apollo-djs' ), esc_html( $dj_name ) ); ?>
			</h3>
			<button type="button" class="dj-bio-modal-close" id="bioClose" aria-label="<?php esc_attr_e( 'Fechar modal', 'apollo-djs' ); ?>">
				<i class="ri-close-line"></i>
			</button>
		</div>
		<div class="dj-bio-modal-body" id="bio-full"><?php echo wp_kses_post( $dj_bio_full ); ?></div>
	</div>
</div>
