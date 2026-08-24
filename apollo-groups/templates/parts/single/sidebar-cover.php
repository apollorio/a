<?php
/**
 * Single Part — Sidebar Cover
 *
 * Cover image with group name overlay and type pill.
 * Expects: $group_cover, $group_name, $group_type
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sb-cover">
	<img src="<?php echo esc_url( $group_cover ); ?>" alt="<?php echo esc_attr( $group_name ); ?>" loading="lazy">
	<span class="sb-cover-type"><?php echo esc_html( ucfirst( $group_type ) ); ?></span>
	<span class="sb-cover-name"><?php echo esc_html( $group_name ); ?></span>
</div>
