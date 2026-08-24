<?php
/**
 * Single Part — Sidebar About
 *
 * Description / bio section.
 * Expects: $group_desc
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $group_desc ) ) {
	return;
}
?>
<div class="sb-section">
	<div class="sb-section-title"><i class="ri-information-line"></i> Sobre</div>
	<p class="sb-about-text"><?php echo wp_kses_post( nl2br( $group_desc ) ); ?></p>
</div>
