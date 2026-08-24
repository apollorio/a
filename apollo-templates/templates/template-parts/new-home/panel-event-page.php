<?php

/**
 * Panel: Event Page — Single Event Detail (Guest RIGHT)
 *
 * Public event viewer for non-logged users.
 * Slides RIGHT from home when guest clicks an event card.
 * data-panel="event-page" data-dir="right"
 *
 * Only handles event type. Logged users use panel-dynamic.php instead.
 *
 * Trigger format:
 *   <a data-to="event-page" data-dir="right" data-id="123">
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → panels.event-page
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rest_url = esc_url_raw( rest_url( 'apollo/v1/' ) );
$nonce    = wp_create_nonce( 'wp_rest' );
?>
<section data-panel="event-page" data-glyph="►">

	<!-- ── Header ── -->
	<header class="pnl-head">
		<button class="pnl-head__back" onclick="ApolloSlider&&ApolloSlider.back()" aria-label="Voltar">
			<i class="ri-arrow-left-s-line"></i>
		</button>
		<h2 class="pnl-head__title" id="eventTitle"><?php esc_html_e( 'Evento', 'apollo-templates' ); ?></h2>
		<div class="pnl-head__actions">
			<button class="pnl-btn pnl-btn--icon" id="eventShare" aria-label="Compartilhar">
				<i class="ri-share-forward-line"></i>
			</button>
		</div>
	</header>

	<!-- ── Loader ── -->
	<div class="pnl-loader" id="eventLoader">
		<div class="pnl-loader__ring"></div>
	</div>

	<!-- ── Event Content ── -->
	<div class="pnl-body" id="eventBody" style="display:none">

		<div id="eventContent"></div>

		<!-- ── Guest CTA — Login to interact ── -->
		<div class="pnl-cta-gate" id="eventGuestCta">
			<div class="pnl-divider"></div>
			<div class="pnl-cta-block">
				<i class="ri-lock-line pnl-cta-block__icon"></i>
				<p class="pnl-cta-block__text"><?php esc_html_e( 'Entre na sua conta para confirmar presença, favoritar e interagir.', 'apollo-templates' ); ?></p>
				<button class="pnl-btn pnl-btn--primary" data-to="acesso" data-dir="down">
					<i class="ri-login-circle-line"></i> <?php esc_html_e( 'Entrar / Cadastrar', 'apollo-templates' ); ?>
				</button>
			</div>
		</div>
	</div>
</section>

<script>
	(function() {
		var REST = <?php echo wp_json_encode( $rest_url ); ?>;
		var NONCE = <?php echo wp_json_encode( $nonce ); ?>;
		var loader = document.getElementById('eventLoader');
		var body = document.getElementById('eventBody');
		var content = document.getElementById('eventContent');
		var titleEl = document.getElementById('eventTitle');

		/* ── Listen for event triggers ── */
		document.addEventListener('click', function(e) {
			var trigger = e.target.closest('[data-to="event-page"]');
			if (!trigger) return;
			var id = trigger.getAttribute('data-id');
			if (id) loadEvent(id);
		});

		function loadEvent(id) {
			loader.style.display = '';
			body.style.display = 'none';

			fetch(REST + 'events/' + id, {
					headers: {
						'X-WP-Nonce': NONCE
					}
				})
				.then(function(r) {
					return r.ok ? r.text() : Promise.reject(r.status);
				})
				.then(function(html) {
					content.innerHTML = html;
					loader.style.display = 'none';
					body.style.display = '';

					/* Update title from loaded content */
					var h = content.querySelector('h1, h2, .event-title');
					if (h && titleEl) titleEl.textContent = h.textContent;

					/* GSAP entrance */
					if (typeof gsap !== 'undefined') {
						gsap.fromTo('#eventContent > *', {
							opacity: 0,
							y: 10
						}, {
							opacity: 1,
							y: 0,
							stagger: .05,
							duration: .35,
							ease: 'power3.out'
						});
					}
				})
				.catch(function() {
					content.innerHTML = '<div class="pnl-empty"><i class="ri-error-warning-line pnl-empty__icon"></i><p>Erro ao carregar evento</p></div>';
					loader.style.display = 'none';
					body.style.display = '';
				});
		}

		/* ── Share ── */
		var shareBtn = document.getElementById('eventShare');
		if (shareBtn) {
			shareBtn.addEventListener('click', function() {
				var t = titleEl ? titleEl.textContent : 'Evento';
				if (navigator.share) navigator.share({
					title: t,
					url: location.href
				});
				else navigator.clipboard.writeText(location.href);
			});
		}
	})();
</script>
