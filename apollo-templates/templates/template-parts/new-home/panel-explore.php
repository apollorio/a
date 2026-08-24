<?php
/**
 * Panel: Explore — Social Feed & Discovery (Logged Center)
 *
 * First active panel for authenticated users.
 * data-panel="explore" — center position (auto-activated by page-layout.js)
 *
 * Directions from explore:
 *   UP    → mural  (dynamic single page viewer)
 *   DOWN  → forms  (create event/ad/group, editor, admin)
 *   LEFT  → chat-list  (conversations)
 *   RIGHT → dynamic    (any single page detail)
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → panels.explore
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
?>
<section data-panel="explore" data-glyph="✦"
	<?php if ( $user_id ) : ?> data-a-user="<?php echo esc_attr( (string) $user_id ); ?>"<?php endif; ?>
	data-a-component="panel-explore">

	<!-- ── Header ── -->
	<header class="pnl-head">
		<h2 class="pnl-head__title">explore</h2>
		<div class="pnl-head__actions">
			<button class="pnl-btn pnl-btn--icon" data-to="forms" data-dir="down"
				aria-label="<?php esc_attr_e( 'Criar novo', 'apollo-templates' ); ?>">
				<i class="ri-add-line"></i>
			</button>
		</div>
	</header>

	<!-- ── Filter Tabs ── -->
	<nav class="pnl-tabs" role="tablist" id="exploreTabs">
		<button class="pnl-tab pnl-tab--active" role="tab" data-feed="all"><?php esc_html_e( 'Tudo', 'apollo-templates' ); ?></button>
		<button class="pnl-tab" role="tab" data-feed="events"><?php esc_html_e( 'Eventos', 'apollo-templates' ); ?></button>
		<button class="pnl-tab" role="tab" data-feed="social"><?php esc_html_e( 'Social', 'apollo-templates' ); ?></button>
		<button class="pnl-tab" role="tab" data-feed="ads"><?php esc_html_e( 'Anúncios', 'apollo-templates' ); ?></button>
		<button class="pnl-tab" role="tab" data-feed="djs">DJs</button>
	</nav>

	<!-- ── Feed Body ── -->
	<div class="pnl-body" id="exploreBody">

		<?php
		/**
		 * Hook: apollo/social/render_feed
		 * Plugins render feed cards here. Each card should include:
		 *   data-to="dynamic" data-dir="right" data-detail-type="{cpt}" data-detail-id="{id}"
		 * for navigation to the dynamic single panel.
		 *
		 * @param int $user_id Current user ID
		 */
		do_action( 'apollo/social/render_feed', $user_id );
		?>

		<!-- Fallback skeleton if no plugin populates feed -->
		<div class="pnl-skeleton" id="exploreSkeleton">
			<div class="pnl-skeleton__card"></div>
			<div class="pnl-skeleton__card"></div>
			<div class="pnl-skeleton__card"></div>
			<div class="pnl-skeleton__card"></div>
		</div>

		<?php
		/**
		 * Hook: apollo/explore/after_feed
		 * Additional sections below the main feed (trending, suggestions, etc.)
		 */
		do_action( 'apollo/explore/after_feed', $user_id );
		?>
	</div>
</section>

<script>
(function(){
	/* ── Feed tab filter ── */
	var tabs = document.querySelectorAll('#exploreTabs .pnl-tab');
	tabs.forEach(function(tab){
		tab.addEventListener('click', function(){
			tabs.forEach(function(t){ t.classList.remove('pnl-tab--active'); });
			tab.classList.add('pnl-tab--active');
			var filter = tab.getAttribute('data-feed');
			document.dispatchEvent(new CustomEvent('apollo:feed:filter', { detail: { filter: filter } }));
		});
	});

	/* ── Hide skeleton when feed loads ── */
	document.addEventListener('apollo:feed:loaded', function(){
		var sk = document.getElementById('exploreSkeleton');
		if (sk) sk.style.display = 'none';
	});
})();
</script>
