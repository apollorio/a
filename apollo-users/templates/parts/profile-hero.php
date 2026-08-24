<?php
/**
 * Profile Hero — Cover media area
 *
 * @package Apollo\Users
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variables: $user, $user_id, $cover_url, $is_own_profile
?>
<section class="hero-section">
	<div class="hero-media">
		<?php if ( $cover_url ) : ?>
			<img src="<?php echo esc_url( $cover_url ); ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
		<?php endif; ?>

		<?php if ( $is_own_profile ) : ?>
		<label class="hero-upload-btn" title="Alterar capa">
			<i class="ri-camera-line"></i>
			<input type="file" accept="image/*" id="cover-upload-input" style="display:none;">
		</label>
		<?php endif; ?>
	</div>