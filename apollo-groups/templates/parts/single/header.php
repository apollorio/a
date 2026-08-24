<?php
/**
 * Single Part — Mobile Header
 *
 * Sticky header bar for mobile (replaces sidebar).
 * Expects: $group_name, $group_type, $member_count
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mobile-header" id="mobileHeader">
	<button class="mobile-header-back" onclick="history.back()">
		<i class="ri-arrow-left-s-line"></i>
	</button>
	<div class="mobile-header-info">
		<div class="mobile-header-name"><?php echo esc_html( $group_name ); ?></div>
		<div class="mobile-header-meta">
			<?php echo esc_html( ucfirst( $group_type ) ); ?> · <?php echo esc_html( $member_count ); ?> membros
		</div>
	</div>
	<button class="mobile-header-menu" id="btnDrawerOpen">
		<i class="ri-menu-line"></i>
	</button>
</div>
