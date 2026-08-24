<?php

/**
 * Profile Login Required Template
 *
 * Shown when a members-only profile is viewed by non-logged user
 *
 * @package Apollo\Users
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current URL for redirect after login
$current_url = home_url( $_SERVER['REQUEST_URI'] );

// Minimal header to avoid theme issues
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo 'Acesso Necessário - ' . get_bloginfo( 'name' ); ?></title>

	<!-- Apollo CDN - Canvas Mode (NO wp_head to prevent theme interference) -->
	<script src="https://cdn.apollo.rio.br/v1.0.0/core.min.js" fetchpriority="high"></script>

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

<body <?php body_class(); ?>>
	<header class="nav-off" aria-label="Profile quick navigation">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">APOLLO</a>
		<a href="<?php echo esc_url( home_url( '/radar/' ) ); ?>">Radar</a>
	</header>

	<div id="page" class="site">

		<div id="content" class="site-content">
			<div class="container">

				<div class="apollo-profile-restricted">
					<div class="apollo-restricted-content">
						<span class="dashicons dashicons-admin-users"></span>
						<h1><?php esc_html_e( 'Apenas para Membros' ); ?></h1>
						<p><?php esc_html_e( 'Faça login para ver este perfil.' ); ?></p>

						<div class="apollo-restricted-actions">
							<a href="<?php echo esc_url( home_url( '/acesso?redirect=' . urlencode( $current_url ) ) ); ?>" class="apollo-btn apollo-btn-primary">
								<?php esc_html_e( 'Fazer Login' ); ?>
							</a>
							<a href="<?php echo esc_url( home_url( '/registre' ) ); ?>" class="apollo-btn apollo-btn-secondary">
								<?php esc_html_e( 'Criar Conta' ); ?>
							</a>
						</div>
					</div>
				</div>

			</div><!-- #content -->
		</div><!-- #page -->

		<?php /* Canvas Mode - NO wp_footer() to prevent theme interference */ ?>
</body>

</html>