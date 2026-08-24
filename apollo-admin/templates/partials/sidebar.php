<?php
/**
 * Apollo Admin Panel — Sidebar Navigation
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<aside class="sidebar" id="sidebar">
	<div class="sidebar-logo"><i class="apollo"></i></div>

	<nav class="sidebar-nav">
		<!-- Section 1: Administrative -->
		<button class="nav-btn active" data-section="admin" onclick="switchSection('admin')">
			<i class="ri-pencil-ruler-2-line"></i>
			<span class="tooltip"><?php esc_html_e( 'apollo-admin', 'apollo-admin' ); ?></span>
		</button>
		<!-- Section 2: Identity -->
		<button class="nav-btn" data-section="identity" onclick="switchSection('identity')">
			<i class="ri-fingerprint-fill"></i>
			<span class="tooltip"><?php esc_html_e( 'apollo-login', 'apollo-admin' ); ?></span>
		</button>
		<!-- Section 3: Email -->
		<button class="nav-btn" data-section="email" onclick="switchSection('email')">
			<i class="ri-mail-send-fill"></i>
			<span class="tooltip"><?php esc_html_e( 'apollo-email', 'apollo-admin' ); ?></span>
		</button>
		<!-- Section 4: Events -->
		<button class="nav-btn" data-section="events" onclick="switchSection('events')">
			<i class="ri-calendar-line"></i>
			<span class="tooltip"><?php esc_html_e( 'apollo-event', 'apollo-admin' ); ?></span>
		</button>
		<!-- Section 5: Social -->
		<button class="nav-btn" data-section="social" onclick="switchSection('social')">
			<i class="ri-global-line"></i>
			<span class="tooltip"><?php esc_html_e( 'apollo-social', 'apollo-admin' ); ?></span>
		</button>
		<!-- Section 6: System -->
		<button class="nav-btn" data-section="system" onclick="switchSection('system')">
			<i class="ri-cpu-line"></i>
			<span class="tooltip"><?php esc_html_e( 'apollo-core', 'apollo-admin' ); ?></span>
		</button>
	</nav>

	<div class="sidebar-bottom">
		<button class="nav-btn" style="width:52px;height:40px" onclick="toggleDarkMode()">
			<i class="ri-contrast-drop-line"></i>
			<span class="tooltip"><?php esc_html_e( 'Theme', 'apollo-admin' ); ?></span>
		</button>
		<div class="sidebar-avatar">
			<?php
			$current_user = wp_get_current_user();
			$avatar       = get_avatar( $current_user->ID, 34 );
			if ( $avatar ) {
				echo $avatar; // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo '<i class="ri-user-3-line"></i>';
			}
			?>
		</div>
	</div>
</aside>
