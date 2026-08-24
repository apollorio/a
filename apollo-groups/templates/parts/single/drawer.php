<?php
/**
 * Single Part — Drawer (Mobile Sidebar)
 *
 * Overlay drawer that mirrors sidebar content on mobile.
 * Expects all sidebar variables available in scope.
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="drawer-overlay" id="drawerOverlay">
	<div class="drawer" id="drawerPanel">
		<button class="drawer-close" id="drawerClose"><i class="ri-close-line"></i></button>

		<?php
		/* Reuse sidebar parts inside drawer */
		require __DIR__ . '/sidebar-cover.php';
		require __DIR__ . '/sidebar-stats.php';
		require __DIR__ . '/sidebar-about.php';
		require __DIR__ . '/sidebar-tags.php';
		require __DIR__ . '/sidebar-rules.php';
		require __DIR__ . '/sidebar-members.php';
		require __DIR__ . '/sidebar-join.php';
		?>
	</div>
</div>
