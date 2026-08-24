<?php
/**
 * Events Section — Social Share
 *
 * Page ID: page-evt-social
 * 2 toggles + 7 shareable option toggles
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-evt-social">
	<div class="panel">
		<div class="panel-header"><i class="ri-share-line"></i> <?php esc_html_e( 'Social Media Share Control', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[share_single_only]" value="1" <?php checked( $apollo['share_single_only'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Show social share icons only on single event pages', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[share_disable_encoding]" value="1" <?php checked( $apollo['share_disable_encoding'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable social media event link encoding for special characters', 'apollo-admin' ); ?></span></div></div>

			<div class="section-title"><?php esc_html_e( 'Shareable Options', 'apollo-admin' ); ?></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[share_facebook]" value="1" <?php checked( $apollo['share_facebook'] ?? true ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Facebook Share', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[share_twitter]" value="1" <?php checked( $apollo['share_twitter'] ?? true ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Twitter / X', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[share_linkedin]" value="1" <?php checked( $apollo['share_linkedin'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'LinkedIn', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[share_whatsapp]" value="1" <?php checked( $apollo['share_whatsapp'] ?? true ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'WhatsApp', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[share_pinterest]" value="1" <?php checked( $apollo['share_pinterest'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Pinterest (only shows if event has featured image)', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[share_copy_link]" value="1" <?php checked( $apollo['share_copy_link'] ?? true ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Copy Event Link', 'apollo-admin' ); ?></span></div></div>
			<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[share_email]" value="1" <?php checked( $apollo['share_email'] ?? false ); ?>><span class="switch-track"></span></label><div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Share Event via Email', 'apollo-admin' ); ?></span></div></div>
		</div>
	</div>
</div>