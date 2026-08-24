<?php
/**
 * Panel: Dynamic — Universal Single Page (Logged RIGHT)
 *
 * Universal single-page viewer for authenticated users.
 * Slides RIGHT from explore/center.
 * data-panel="dynamic" data-dir="right"
 *
 * Handles ALL CPT types: event, dj, local, classified, group,
 * post, profile, hub, nucleo, comuna, outnow.
 *
 * Trigger format:
 *   <a data-to="dynamic" data-dir="right" data-detail-type="{type}" data-detail-id="{id}">
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → detail_rule
 * @see     _inventory/apollo-registry.json → rest.endpoints
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rest_url = esc_url_raw( rest_url( 'apollo/v1/' ) );
$nonce    = wp_create_nonce( 'wp_rest' );
$user_id  = get_current_user_id();
?>
<section data-panel="dynamic" data-glyph="─"
	<?php if ( $user_id ) : ?> data-a-user="<?php echo esc_attr( (string) $user_id ); ?>"<?php endif; ?>
	data-a-component="panel-dynamic">

	<!-- ── Header ── -->
	<header class="pnl-head">
		<button class="pnl-head__back" onclick="ApolloSlider&&ApolloSlider.back()" aria-label="Voltar">
			<i class="ri-arrow-left-s-line"></i>
		</button>
		<h2 class="pnl-head__title" id="dynTitle"></h2>
		<div class="pnl-head__actions" id="dynActions">
			<button class="pnl-btn pnl-btn--icon" data-dyn-action="fav" aria-label="Favoritar" style="display:none">
				<i class="ri-fire-line"></i>
			</button>
			<button class="pnl-btn pnl-btn--icon" data-dyn-action="share" aria-label="Compartilhar">
				<i class="ri-share-forward-line"></i>
			</button>
			<button class="pnl-btn pnl-btn--icon" data-dyn-action="report"
				data-to="forms" data-dir="down" aria-label="Reportar">
				<i class="ri-flag-line"></i>
			</button>
		</div>
	</header>

	<!-- ── Loader ── -->
	<div class="pnl-loader" id="dynLoader">
		<div class="pnl-loader__ring"></div>
	</div>

	<!-- ── Content ── -->
	<div class="pnl-body" id="dynBody" style="display:none">
		<div id="dynContent"></div>

		<!-- ── Context Actions (shown per type) ── -->
		<div class="pnl-dyn-actions" id="dynContextActions" style="display:none">
			<div class="pnl-divider"></div>
			<div class="pnl-dyn-actions__row">
				<button class="pnl-btn" data-dyn-ctx="rsvp" style="display:none"
					data-to="forms" data-dir="down">
					<i class="ri-calendar-check-line"></i> <?php esc_html_e( 'Confirmar Presença', 'apollo-templates' ); ?>
				</button>
				<button class="pnl-btn" data-dyn-ctx="depoimento" style="display:none"
					data-to="forms" data-dir="down">
					<i class="ri-quill-pen-line"></i> <?php esc_html_e( 'Depoimento', 'apollo-templates' ); ?>
				</button>
				<button class="pnl-btn" data-dyn-ctx="chat" data-to="chat-list" data-dir="left" style="display:none">
					<i class="ri-message-3-line"></i> <?php esc_html_e( 'Mensagem', 'apollo-templates' ); ?>
				</button>
			</div>
		</div>

		<?php do_action( 'apollo/dynamic/after_content', $user_id ); ?>
	</div>
</section>

<script>
(function(){
	var REST    = <?php echo wp_json_encode( $rest_url ); ?>;
	var NONCE   = <?php echo wp_json_encode( $nonce ); ?>;
	var loader  = document.getElementById('dynLoader');
	var body    = document.getElementById('dynBody');
	var content = document.getElementById('dynContent');
	var titleEl = document.getElementById('dynTitle');
	var ctx     = document.getElementById('dynContextActions');
	var favBtn  = document.querySelector('[data-dyn-action="fav"]');

	var TYPE_MAP = {
		'event'     : { endpoint: 'events/',      label: 'Evento',      actions: ['rsvp','depoimento'] },
		'dj'        : { endpoint: 'djs/',          label: 'DJ',          actions: ['chat','depoimento'] },
		'local'     : { endpoint: 'locals/',        label: 'Espaço',      actions: ['depoimento'] },
		'classified': { endpoint: 'classifieds/',   label: 'Anúncio',     actions: ['chat'] },
		'group'     : { endpoint: 'groups/',        label: 'Grupo',       actions: [] },
		'comuna'    : { endpoint: 'groups/',        label: 'Comuna',      actions: [] },
		'nucleo'    : { endpoint: 'groups/',        label: 'Núcleo',      actions: [] },
		'post'      : { endpoint: 'feed/',          label: 'Post',        actions: ['depoimento'] },
		'profile'   : { endpoint: 'profile/',       label: 'Perfil',      actions: ['chat'] },
		'hub'       : { endpoint: 'hubs/',          label: 'Hub',         actions: ['chat'] },
		'outnow'    : { endpoint: 'outnow/',        label: 'Out Now!',    actions: [] }
	};

	var currentType = null;
	var currentId   = null;

	/* ── Listen for dynamic triggers ── */
	document.addEventListener('click', function(e){
		var trigger = e.target.closest('[data-to="dynamic"]');
		if (!trigger) return;
		var type = trigger.getAttribute('data-detail-type');
		var id   = trigger.getAttribute('data-detail-id');
		if (type && id) loadDynamic(type, id);
	});

	function loadDynamic(type, id){
		currentType = type;
		currentId   = id;
		var config  = TYPE_MAP[type];
		if (!config) return;

		titleEl.textContent = config.label;
		loader.style.display = '';
		body.style.display = 'none';

		/* Show/hide fav button */
		if (favBtn) favBtn.style.display = '';

		fetch(REST + config.endpoint + id, { headers: { 'X-WP-Nonce': NONCE } })
			.then(function(r){ return r.ok ? r.text() : Promise.reject(r.status); })
			.then(function(html){
				content.innerHTML = html;
				loader.style.display = 'none';
				body.style.display = '';

				/* Show context actions based on type */
				showContextActions(config.actions || []);

				/* Animate entrance */
				if (typeof gsap !== 'undefined') {
					gsap.fromTo('#dynContent > *',
						{ opacity: 0, x: 20 },
						{ opacity: 1, x: 0, stagger: .04, duration: .35, ease: 'power3.out' }
					);
				}

				/* Dispatch event for plugins */
				document.dispatchEvent(new CustomEvent('apollo:dynamic:loaded', {
					detail: { type: type, id: id, container: content }
				}));
			})
			.catch(function(){
				content.innerHTML = '<div class="pnl-empty"><i class="ri-error-warning-line pnl-empty__icon"></i><p>Erro ao carregar</p></div>';
				loader.style.display = 'none';
				body.style.display = '';
			});
	}

	function showContextActions(actions){
		if (!ctx) return;
		var btns = ctx.querySelectorAll('[data-dyn-ctx]');
		var any = false;
		btns.forEach(function(b){
			var show = actions.indexOf(b.getAttribute('data-dyn-ctx')) > -1;
			b.style.display = show ? '' : 'none';
			if (show) any = true;
		});
		ctx.style.display = any ? '' : 'none';

		/* Set form context for downstream panels */
		btns.forEach(function(b){
			if (b.getAttribute('data-dyn-ctx') === 'rsvp') {
				b.setAttribute('data-form', 'rsvp');
			} else if (b.getAttribute('data-dyn-ctx') === 'depoimento') {
				b.setAttribute('data-form', 'depoimento');
			}
		});
	}

	/* ── Fav toggle ── */
	if (favBtn) {
		favBtn.addEventListener('click', function(){
			if (!currentType || !currentId) return;
			var icon = favBtn.querySelector('i');
			var isFav = icon.classList.contains('ri-fire-fill');
			icon.className = isFav ? 'ri-fire-line' : 'ri-fire-fill';
			if (!isFav) icon.style.color = 'var(--primary)';
			else icon.style.color = '';

			fetch(REST + 'fav/toggle', {
				method: 'POST',
				headers: { 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' },
				body: JSON.stringify({ type: currentType, id: currentId })
			}).catch(function(){ /* silent */ });
		});
	}

	/* ── Share ── */
	var shareBtn = document.querySelector('[data-dyn-action="share"]');
	if (shareBtn) {
		shareBtn.addEventListener('click', function(){
			var t = titleEl ? titleEl.textContent : '';
			if (navigator.share) navigator.share({ title: t, url: location.href });
			else navigator.clipboard.writeText(location.href);
		});
	}
})();
</script>
