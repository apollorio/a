<?php

/**
 * Template Part: Sidebar — Right sticky column (desktop only)
 *
 * Sections: News, Events, Trending Tracks, Núcleos, Market, Community Stats.
 * Uses .sb-* class namespace per approved design.
 *
 * Expected vars: $username
 *
 * @package Apollo\Social
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<aside class="sidebar-column" id="sidebar-column">

	<!-- ─── Notícias ─── -->
	<div class="sb-card gsap-el">
		<div class="sb-title">
			<i class="ri-newspaper-line"></i> Notícias
		</div>
		<div id="sidebar-news" class="sb-news-list">
			<a href="#" class="sb-news-item">
				<span class="news-cat">Geral</span>
				<span class="news-title">Carregando...</span>
				<span class="news-time">&mdash;</span>
			</a>
		</div>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sb-see-more">
			Ver tudo <i class="ri-arrow-right-s-line"></i>
		</a>
	</div>

	<!-- ─── Próximos Eventos ─── -->
	<div class="sb-card gsap-el">
		<div class="sb-title">
			<i class="ri-calendar-event-fill"></i> Próximos Eventos
		</div>
		<div id="sidebar-events" class="sb-events-list">
			<a href="#" class="sb-ev">
				<div class="sbe-date">
					<span class="sbe-day">--</span>
					<span class="sbe-month">---</span>
				</div>
				<div class="sbe-info">
					<span class="sbe-name">Carregando...</span>
					<span class="sbe-loc">&mdash;</span>
				</div>
			</a>
		</div>
		<a href="<?php echo esc_url( home_url( '/eventos' ) ); ?>" class="sb-see-more">
			Ver agenda <i class="ri-arrow-right-s-line"></i>
		</a>
	</div>

	<!-- ─── Trending Tracks ─── -->
	<div class="sb-card gsap-el">
		<div class="sb-title">
			<i class="ri-fire-fill"></i> Trending Tracks
		</div>
		<div id="sidebar-trending" class="sb-trending-list">
			<div class="sb-trending-track">
				<span class="sbt-num">1</span>
				<div class="sbt-art" style="background:#333;"></div>
				<div class="sbt-info">
					<span class="sbt-name">Carregando...</span>
					<span class="sbt-artist">&mdash;</span>
				</div>
			</div>
		</div>
	</div>

	<!-- ─── Núcleos (Work Teams) ─── -->
	<div class="sb-card gsap-el">
		<div class="sb-title">
			<i class="ri-group-fill"></i> Núcleos
		</div>
		<div id="sidebar-nucleos" class="sb-links-list">
			<a href="#" class="sb-link">
				<i class="ri-arrow-right-s-line"></i> Carregando...
			</a>
		</div>
		<a href="<?php echo esc_url( home_url( '/nucleos' ) ); ?>" class="sb-see-more">
			Ver todos <i class="ri-arrow-right-s-line"></i>
		</a>
	</div>

	<!-- ─── Market (Classifieds quick) ─── -->
	<div class="sb-card gsap-el">
		<div class="sb-title">
			<i class="ri-exchange-box-line"></i> Market
		</div>
		<div id="sidebar-market" class="sb-market-list">
			<a href="#" class="sb-market-item">
				<div class="sbm-thumb" style="background:#333;"></div>
				<div class="sbm-info">
					<span class="sbm-name">Carregando...</span>
					<span class="sbm-price">&mdash;</span>
				</div>
			</a>
		</div>
		<a href="<?php echo esc_url( home_url( '/marketplace' ) ); ?>" class="sb-see-more">
			Ver marketplace <i class="ri-arrow-right-s-line"></i>
		</a>
	</div>

	<!-- ─── Community Stats ─── -->
	<div class="sb-card gsap-el">
		<div class="sb-title">
			<i class="ri-bar-chart-fill"></i> Comunidade
		</div>
		<div class="sb-stat-grid">
			<div class="sb-stat">
				<div class="sb-stat-num" id="stat-users">—</div>
				<div class="sb-stat-label">Membros</div>
			</div>
			<div class="sb-stat">
				<div class="sb-stat-num" id="stat-events">—</div>
				<div class="sb-stat-label">Eventos</div>
			</div>
			<div class="sb-stat">
				<div class="sb-stat-num" id="stat-posts">—</div>
				<div class="sb-stat-label">Posts</div>
			</div>
			<div class="sb-stat">
				<div class="sb-stat-num" id="stat-djs">—</div>
				<div class="sb-stat-label">DJs</div>
			</div>
		</div>
	</div>

	<!-- ─── Footer ─── -->
	<div class="sb-footer">
		<span>Apollo &copy; <?php echo esc_html( date( 'Y' ) ); ?></span>
		<a href="<?php echo esc_url( home_url( '/termos' ) ); ?>">Termos</a>
		<a href="<?php echo esc_url( home_url( '/privacidade' ) ); ?>">Privacidade</a>
	</div>

</aside>
