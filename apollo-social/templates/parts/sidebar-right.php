<?php

/**
 * Template Part: Right Sidebar — Trending, explore, footer
 *
 * @package Apollo\Social
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<aside class="feed-sidebar feed-sidebar-right">
	<div class="sidebar-inner">
		<!-- Trending -->
		<div class="sidebar-card">
			<h3 class="sidebar-card-title">
				<i class="ri-fire-fill" style="color:var(--primary);"></i> Em alta
			</h3>
			<div id="sidebar-trending" class="sidebar-trending">
				<div class="sidebar-placeholder">Carregando...</div>
			</div>
		</div>

		<!-- Explore -->
		<div class="sidebar-card">
			<h3 class="sidebar-card-title">
				<i class="ri-compass-fill" style="color:var(--primary);"></i> Explorar
			</h3>
			<a href="<?php echo esc_url( home_url( '/eventos' ) ); ?>" class="sidebar-explore-link">
				<i class="ri-calendar-event-line"></i> Próximos Eventos
			</a>
			<a href="<?php echo esc_url( home_url( '/djs' ) ); ?>" class="sidebar-explore-link">
				<i class="ri-disc-line"></i> DJs em destaque
			</a>
			<a href="<?php echo esc_url( home_url( '/classificados' ) ); ?>" class="sidebar-explore-link">
				<i class="ri-exchange-box-line"></i> Classificados recentes
			</a>
		</div>

		<!-- Footer -->
		<div class="sidebar-footer">
			<span>Apollo &copy; <?php echo esc_html( date( 'Y' ) ); ?></span>
			<a href="#">Termos</a>
			<a href="#">Privacidade</a>
		</div>
	</div>
</aside>
