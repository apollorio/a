<?php

/**
 * Apollo Admin — Dashboard Shell Template
 *
 * Assembles all partials into the full CPanel UI.
 * Loaded by AdminPage::render_page()
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'apollo-admin' ) );
}

$partials_dir = APOLLO_ADMIN_DIR . 'templates/partials/';
$sections_dir = $partials_dir . 'sections/';

// ── Load all settings from single option (source of truth for CPanel) ──
$apollo = get_option( APOLLO_ADMIN_OPTION_KEY, array() );
if ( ! is_array( $apollo ) ) {
	$apollo = array();
}
// Bridge alias for partials — use $B::get('key', $default) for live option values.
$B = \Apollo\Admin\Admin\SettingsBridge::class;
?>
<div class="wrap apollo-cpanel-wrap" style="margin:0;padding:0;max-width:100%">

	<?php
	// Inline styles
	?>
	<?php require $partials_dir . 'styles.php'; ?>

	<?php
	// Mobile topbar toggle
	?>
	<div class="mobile-topbar" style="display:none">
		<button class="mobile-menu-btn" onclick="toggleMobileSidebar()" aria-label="<?php esc_attr_e( 'Menu', 'apollo-admin' ); ?>">
			<i class="ri-menu-3-line"></i>
		</button>
		<span style="font-family:var(--ff-display);color:var(--primary);font-size:18px">Apollo</span>
	</div>
	<div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileSidebar()"></div>

	<div class="shell">

		<?php
		// Sidebar navigation
		?>
		<?php require $partials_dir . 'sidebar.php'; ?>

		<div class="main-area">

			<?php
			// Top tab bars
			?>
			<?php require $partials_dir . 'topbar.php'; ?>

			<div class="content-area">

				<form id="apollo-cpanel-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'apollo_cpanel_save', 'apollo_cpanel_nonce' ); ?>
					<input type="hidden" name="action" value="apollo_cpanel_save">

					<?php
					// ═══ SECTION 1: ADMIN ═══
					?>
					<?php require $sections_dir . 'admin/overview.php'; ?>
					<?php require $sections_dir . 'admin/users.php'; ?>
					<?php require $sections_dir . 'admin/membership.php'; ?>
					<?php require $sections_dir . 'admin/moderate.php'; ?>
					<?php require $sections_dir . 'admin/notify.php'; ?>
					<?php require $sections_dir . 'admin/spreadsheet.php'; ?>

					<?php
					// ═══ SECTION 2: IDENTITY ═══
					?>
					<?php require $sections_dir . 'identity/login.php'; ?>
					<?php require $sections_dir . 'identity/users.php'; ?>
					<?php require $sections_dir . 'identity/membership.php'; ?>

					<?php
					// ═══ SECTION 3: EMAIL ═══
					?>
					<?php require $sections_dir . 'email/settings.php'; ?>
					<?php require $sections_dir . 'email/templates.php'; ?>
					<?php require $sections_dir . 'email/subscribers.php'; ?>
					<?php require $sections_dir . 'email/stats.php'; ?>
					<?php require $sections_dir . 'email/workflows.php'; ?>
					<?php require $sections_dir . 'email/logger.php'; ?>
					<?php require $sections_dir . 'email/unsub.php'; ?>
					<?php require $sections_dir . 'email/tools.php'; ?>
					<?php require $sections_dir . 'email/rss.php'; ?>

					<?php
					// ═══ SECTION 4: EVENTS ═══
					?>
					<?php require $sections_dir . 'events/general.php'; ?>
					<?php require $sections_dir . 'events/calendar.php'; ?>
					<?php require $sections_dir . 'events/maps.php'; ?>
					<?php require $sections_dir . 'events/theme.php'; ?>
					<?php require $sections_dir . 'events/eventtop.php'; ?>
					<?php require $sections_dir . 'events/eventcard.php'; ?>
					<?php require $sections_dir . 'events/custom.php'; ?>
					<?php require $sections_dir . 'events/single.php'; ?>
					<?php require $sections_dir . 'events/social.php'; ?>
					<?php require $sections_dir . 'events/repeat.php'; ?>

					<?php
					// ═══ SECTION 5: SOCIAL ═══
					?>
					<?php require $sections_dir . 'social/activity.php'; ?>
					<?php require $sections_dir . 'social/chat.php'; ?>
					<?php require $sections_dir . 'social/groups.php'; ?>
					<?php require $sections_dir . 'social/reactions.php'; ?>
					<?php require $sections_dir . 'social/notif.php'; ?>

					<?php
					// ═══ SECTION 6: SYSTEM ═══
					?>
					<?php require $sections_dir . 'system/core.php'; ?>
					<?php require $sections_dir . 'system/content.php'; ?>
					<?php require $sections_dir . 'system/templates.php'; ?>
					<?php require $sections_dir . 'system/stats.php'; ?>
					<?php require $sections_dir . 'system/pwa.php'; ?>
					<?php require $sections_dir . 'system/seo.php'; ?>
					<?php require $sections_dir . 'system/security.php'; ?>

				</form>

				<?php
				// Luxury slide-in modal for CPT creation / report
				?>
				<?php require $partials_dir . 'modal-form.php'; ?>

			</div><!-- .content-area -->
		</div><!-- .main-area -->
	</div><!-- .shell -->

	<?php
	// JavaScript: section/tab switching, dark mode, color sync, AJAX save
	?>
	<?php require $partials_dir . 'footer.php'; ?>

</div><!-- .apollo-cpanel-wrap -->