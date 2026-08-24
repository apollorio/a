<?php

/**
 * Template Part: Left Sidebar — User card, navigation, compose CTA
 *
 * Expected vars: $avatar, $display_name, $username
 *
 * @package Apollo\Social
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<aside class="feed-sidebar feed-sidebar-left">
	<div class="sidebar-inner">
		<!-- User Card -->
		<div class="sidebar-user-card">
			<img src="<?php echo esc_url( $avatar ); ?>" alt="" class="sidebar-avatar">
			<div class="sidebar-user-info">
				<span class="sidebar-user-name"><?php echo esc_html( $display_name ); ?></span>
				<span class="sidebar-user-handle">@<?php echo esc_html( $username ); ?></span>
			</div>
		</div>

		<!-- Navigation -->
		<nav class="sidebar-nav">
			<a href="<?php echo esc_url( home_url( '/feed' ) ); ?>" class="sidebar-link active">
				<i class="ri-home-5-fill"></i><span>Feed</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/painel' ) ); ?>" class="sidebar-link">
				<i class="ri-dashboard-fill"></i><span>Painel</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/id/' . $username ) ); ?>" class="sidebar-link">
				<i class="ri-user-fill"></i><span>Perfil</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/eventos' ) ); ?>" class="sidebar-link">
				<i class="ri-calendar-event-fill"></i><span>Eventos</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/djs' ) ); ?>" class="sidebar-link">
				<i class="ri-disc-fill"></i><span>DJs</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/classificados' ) ); ?>" class="sidebar-link">
				<i class="ri-exchange-box-line"></i><span>Classificados</span>
			</a>
			<a href="<?php echo esc_url( home_url( '/comunas' ) ); ?>" class="sidebar-link">
				<i class="ri-team-fill"></i><span>Comunas</span>
			</a>
		</nav>

		<!-- Compose CTA -->
		<button class="sidebar-compose-btn" onclick="document.getElementById('feed-compose-text').focus()">
			<i class="ri-quill-pen-fill"></i> Publicar
		</button>
	</div>
</aside>
