<?php if ( ! defined( 'ABSPATH' ) ) { exit; } // plan-001 · G07 — file was directly reachable over HTTP ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport"
		content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
	<meta name="theme-color" content="#000000">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<?php
	// Get displayed user
	$profile_username = get_query_var( 'apollo_profile_user', '' );
	$displayed_user   = null;

	if ( ! empty( $profile_username ) ) {
		// Try by user_nicename first (URL-friendly slug)
		$displayed_user = get_user_by( 'slug', $profile_username );

		// If not found, try by login
		if ( ! $displayed_user ) {
			$displayed_user = get_user_by( 'login', $profile_username );
		}

		// If not found, try by ID
		if ( ! $displayed_user && is_numeric( $profile_username ) ) {
			$displayed_user = get_user_by( 'ID', intval( $profile_username ) );
		}
	}

	// If no user found, show 404
	if ( ! $displayed_user ) {
		status_header( 404 );
		nocache_headers();
		include get_query_template( '404' );
		exit;
	}

	// Get user meta
	$social_name       = get_user_meta( $displayed_user->ID, '_apollo_social_name', true );
	$instagram         = get_user_meta( $displayed_user->ID, '_apollo_instagram', true );
	$avatar_url        = get_user_meta( $displayed_user->ID, '_apollo_avatar_url', true );
	$sound_preferences = get_user_meta( $displayed_user->ID, '_apollo_sound_preferences', true );
	$last_login        = get_user_meta( $displayed_user->ID, '_apollo_last_login', true );
	$display_name      = ! empty( $social_name ) ? $social_name : $displayed_user->display_name;

	// Default avatar
	if ( empty( $avatar_url ) ) {
		$avatar_url = get_avatar_url( $displayed_user->ID, array( 'size' => 300 ) );
	}

	// Check if viewing own profile
	$is_own_profile = is_user_logged_in() && get_current_user_id() === $displayed_user->ID;
	?>
	<meta name="robots" content="index,follow">
	<title><?php echo esc_html( $display_name ); ?> - <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>

	<!-- Open Graph -->
	<meta property="og:title"
		content="<?php echo esc_attr( $display_name ); ?> - <?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<meta property="og:type" content="profile">
	<meta property="og:url"
		content="<?php echo esc_url( home_url( '/id/' . $displayed_user->user_nicename . '/' ) ); ?>">
	<meta property="og:image" content="<?php echo esc_url( $avatar_url ); ?>">

	<!-- Apollo CDN - Canvas Mode (NO wp_head to prevent theme interference) -->
	<script src="https://cdn.apollo.rio.br/v1.0.0/core.min.js" fetchpriority="high"></script>

	<!-- Profile Styles -->
	<link rel="stylesheet"
		href="<?php echo esc_url( APOLLO_LOGIN_URL . 'assets/css/profile.css?v=' . APOLLO_LOGIN_VERSION ); ?>">

	<style>
		.nav-off {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 14px 18px;
			background: rgba(var(--rgb-t), 0.9);
			border-bottom: 1px solid rgba(var(--rgb-d), 0.06);
		}
		.nav-off a {
			text-decoration: none;
			color: #111;
			font-weight: 600;
		}
	</style>
</head>

<body class="apollo-profile-page" data-theme="normal">
	<header class="nav-off" aria-label="Profile quick navigation">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">APOLLO</a>
		<a href="<?php echo esc_url( home_url( '/radar/' ) ); ?>">Radar</a>
	</header>

	<div class="apollo-profile-container" data-a-user="<?php echo esc_attr( (string) $displayed_user->ID ); ?>" data-a-component="login-profile">

		<!-- Background Effects -->
		<div class="bg-layer"></div>
		<div class="grid-overlay"></div>
		<div class="noise-overlay"></div>

		<!-- Profile Content -->
		<div class="profile-wrapper">

			<!-- Profile Header -->
			<header class="profile-header">
				<a href="<?php echo esc_url( home_url() ); ?>" class="back-link">
					<i class="ri-arrow-left-line"></i>
					<span>Voltar</span>
				</a>
				<?php if ( $is_own_profile ) : ?>
					<a href="<?php echo esc_url( home_url( '/conta/' ) ); ?>" class="edit-profile-link">
						<i class="ri-compasses-2-line"></i>
						<span>Editar</span>
					</a>
				<?php endif; ?>
			</header>

			<!-- Avatar Section -->
			<section class="profile-avatar-section">
				<div class="avatar-wrapper">
					<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>"
						class="profile-avatar">
					<?php if ( $is_own_profile ) : ?>
						<button class="avatar-change-btn" title="Alterar foto">
							<i class="ri-camera-line"></i>
						</button>
					<?php endif; ?>
				</div>
			</section>

			<!-- User Info Section -->
			<section class="profile-info-section">
				<h1 class="profile-name"><?php echo esc_html( $display_name ); ?></h1>

				<?php if ( ! empty( $instagram ) ) : ?>
					<a href="https://instagram.com/<?php echo esc_attr( $instagram ); ?>" target="_blank"
						rel="noopener noreferrer" class="instagram-link">
						<i class="ri-instagram-line"></i>
						<span>@<?php echo esc_html( $instagram ); ?></span>
					</a>
				<?php endif; ?>

				<p class="profile-username">@<?php echo esc_html( $displayed_user->user_nicename ); ?></p>

				<?php if ( ! empty( $displayed_user->description ) ) : ?>
					<p class="profile-bio"><?php echo esc_html( $displayed_user->description ); ?></p>
				<?php endif; ?>

				<div class="profile-meta">
					<span class="meta-item">
						<i class="ri-calendar-line"></i>
						<span>Membro desde
							<?php echo esc_html( date_i18n( 'M Y', strtotime( $displayed_user->user_registered ) ) ); ?></span>
					</span>
				</div>
			</section>

			<!-- Sound Preferences Section -->
			<?php if ( ! empty( $sound_preferences ) && is_array( $sound_preferences ) ) : ?>
				<section class="profile-sounds-section">
					<h2 class="section-title">
						<i class="ri-headphone-line"></i>
						<span>Sons que curte</span>
					</h2>
					<div class="sound-chips">
						<?php
						foreach ( $sound_preferences as $term_id ) {
							$term = get_term( $term_id, 'sound' );
							if ( $term && ! is_wp_error( $term ) ) {
								echo '<span class="sound-chip">' . esc_html( $term->name ) . '</span>';
							}
						}
						?>
					</div>
				</section>
			<?php endif; ?>

			<!-- Profile Tabs -->
			<section class="profile-tabs-section">
				<div class="tabs-nav">
					<button class="tab-btn active" data-tab="activity">
						<i class="ri-time-line"></i>
						<span>Atividade</span>
					</button>
					<button class="tab-btn" data-tab="events">
						<i class="ri-calendar-event-line"></i>
						<span>Eventos</span>
					</button>
					<button class="tab-btn" data-tab="about">
						<i class="ri-user-line"></i>
						<span>Sobre</span>
					</button>
				</div>

				<div class="tabs-content">
					<!-- Activity Tab -->
					<div class="tab-pane active" id="tab-activity">
						<div class="activity-list">
							<p class="empty-state">Nenhuma atividade recente.</p>
						</div>
					</div>

					<!-- Events Tab -->
					<div class="tab-pane" id="tab-events">
						<div class="events-list">
							<p class="empty-state">Nenhum evento encontrado.</p>
						</div>
					</div>

					<!-- About Tab -->
					<div class="tab-pane" id="tab-about">
						<div class="about-content">
							<?php if ( ! empty( $displayed_user->description ) ) : ?>
								<div class="about-section">
									<h3>Bio</h3>
									<p><?php echo nl2br( esc_html( $displayed_user->description ) ); ?></p>
								</div>
							<?php endif; ?>

							<div class="about-section">
								<h3>Informações</h3>
								<ul class="info-list">
									<li>
										<span class="info-label">Username</span>
										<span
											class="info-value">@<?php echo esc_html( $displayed_user->user_nicename ); ?></span>
									</li>
									<?php if ( ! empty( $instagram ) ) : ?>
										<li>
											<span class="info-label">Instagram</span>
											<span class="info-value">
												<a href="https://instagram.com/<?php echo esc_attr( $instagram ); ?>"
													target="_blank">
													@<?php echo esc_html( $instagram ); ?>
												</a>
											</span>
										</li>
									<?php endif; ?>
									<li>
										<span class="info-label">Membro desde</span>
										<span
											class="info-value"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $displayed_user->user_registered ) ) ); ?></span>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</section>

		</div>

	</div>

	<!-- Profile Scripts -->
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Tab switching
			const tabBtns = document.querySelectorAll('.tab-btn');
			const tabPanes = document.querySelectorAll('.tab-pane');

			tabBtns.forEach(btn => {
				btn.addEventListener('click', function() {
					const targetTab = this.dataset.tab;

					// Remove active from all
					tabBtns.forEach(b => b.classList.remove('active'));
					tabPanes.forEach(p => p.classList.remove('active'));

					// Add active to current
					this.classList.add('active');
					document.getElementById('tab-' + targetTab).classList.add('active');
				});
			});
		});
	</script>

	<?php /* Canvas Mode - NO wp_footer() to prevent theme interference */ ?>