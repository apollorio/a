<?php
/**
 * Apollo Social Explore — Ultra-modular feed page template
 *
 * Template Name: Explore Feed
 * REST Route: /apollo/v1/explore OR /explore (virtual page)
 *
 * Features:
 * - Blank Canvas (no theme wrapper)
 * - Apollo CDN bootstrap (single load)
 * - Modular component rendering (PostRenderer, SidebarRenderer)
 * - Mobile-first responsive layout
 * - Tab-based UI (feed, explore, trending, settings)
 * - Safety modal for classifieds
 *
 * @package Apollo\Social
 * @version 6.5.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// Authentication check
if (! is_user_logged_in()) {
    wp_redirect(home_url('/acesso'));
    exit;
}

use Apollo\Social\Components\FeedLoader;
use Apollo\Social\Components\PostRenderer;
use Apollo\Social\Components\SidebarRenderer;

// Current user context
$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$rest_url = rest_url('apollo/v1');
$nonce = wp_create_nonce('wp_rest');

ob_start();
?>
    <meta name="description" content="<?php esc_attr_e('Explore a cena::rio do Rio de Janeiro — eventos, DJs, locais, e conexões reais.', 'apollo-social'); ?>">
    <link rel="icon" href="<?php echo esc_url((defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/') . 'favicons/favicon.ico'); ?>">
    <script src="https://cdn.apollo.rio.br/v1.0.0/js/forms.js" defer></script>
    <?php if (defined('APOLLO_SOCIAL_URL') && defined('APOLLO_SOCIAL_VERSION')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(APOLLO_SOCIAL_URL . 'assets/css/explore.css'); ?>?v=<?php echo esc_attr(APOLLO_SOCIAL_VERSION); ?>">
    <?php endif; ?>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => __('Apollo · Explore Cena::rio', 'apollo-social'),
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html lang="<?php bloginfo('language'); ?>">
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>
<body class="apollo-explore-page">

<!-- PRELOADER -->
<div class="page-loader" id="pageLoader">
    <div style="font-family:var(--ff-fun); font-size:28px; animation:pulse 2s infinite;">
        <?php bloginfo('name'); ?>
    </div>
</div>

<div class="app-main">

    <!-- FEED COLUMN -->
    <main class="feed-column" id="feed-main">

        <?php
        $active_tab = sanitize_key($_GET['tab'] ?? 'feed');
        $valid_tabs = ['feed', 'explore', 'trending', 'settings'];
        $active_tab = in_array($active_tab, $valid_tabs, true) ? $active_tab : 'feed';
        ?>

        <!-- TAB: FEED (Main feed) -->
        <div id="tab-feed" class="content-panel <?php echo 'feed' === $active_tab ? 'active' : ''; ?>">
            <div id="feed-posts" class="feed-posts-container">
                <?php
                $feed_args = [
                    'per_page' => 10,
                    'page' => 1,
                    'filter' => 'all',
                    'sort' => 'recent',
                ];

                $posts = FeedLoader::load_feed($feed_args);

                if (empty($posts)) {
                    ?>
                    <div class="placeholder-panel">
                        <h2><?php esc_html_e('Nada por aqui ainda', 'apollo-social'); ?></h2>
                        <p><?php esc_html_e('Siga pessoas para ver suas ações.', 'apollo-social'); ?></p>
                    </div>
                    <?php
                } else {
                    foreach ($posts as $post) {
                        echo PostRenderer::render_post($post, [
                            'show_comments' => true,
                            'show_actions' => true,
                            'variant' => empty($post['media']) ? 'default' : 'default',
                        ]);
                    }
                }
                ?>
            </div>
            <div id="feed-sentinel" class="feed-sentinel" style="height: 200px;"></div>
        </div>

        <!-- TAB: EXPLORE (Search) -->
        <div id="tab-explore" class="content-panel <?php echo 'explore' === $active_tab ? 'active' : ''; ?>">
            <div class="explore-filters">
                <div class="filter-search">
                    <i class="ri-search-line"></i>
                    <input type="text" id="explore-search" placeholder="<?php esc_attr_e('Buscar...', 'apollo-social'); ?>" class="apollo-input">
                </div>
            </div>
            <div id="explore-results"></div>
        </div>

        <!-- TAB: TRENDING -->
        <div id="tab-trending" class="content-panel <?php echo 'trending' === $active_tab ? 'active' : ''; ?>">
            <div id="trending-container"></div>
        </div>

        <!-- TAB: SETTINGS -->
        <div id="tab-settings" class="content-panel <?php echo 'settings' === $active_tab ? 'active' : ''; ?>">
            <div class="settings-panel">
                <h2><?php esc_html_e('Preferências', 'apollo-social'); ?></h2>
                <div class="s-group">
                    <label class="s-label"><?php esc_html_e('Aparência', 'apollo-social'); ?></label>
                    <div class="s-row">
                        <div>
                            <span class="s-text"><?php esc_html_e('Modo Escuro', 'apollo-social'); ?></span>
                        </div>
                        <span class="s-toggle off" id="toggle-dark-mode">☀️</span>
                    </div>
                </div>
                <div class="s-group">
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn-logout">
                        <?php esc_html_e('Sair', 'apollo-social'); ?>
                    </a>
                </div>
            </div>
        </div>

    </main>

    <!-- SIDEBAR -->
    <?php echo SidebarRenderer::render_sidebar(); ?>

</div>

<!-- TAB BAR (Fixed navigation) -->
<div class="tab-bar" id="tabBar">
    <div class="tab-group">
        <button class="tab-item active" data-tab="feed" data-tooltip="Feed">
            <i class="ri-home-4-line"></i>
        </button>
        <button class="tab-item" data-tab="explore" data-tooltip="Explorar">
            <i class="ri-explorer-v"></i>
        </button>
        <button class="tab-item" data-tab="trending" data-tooltip="Tendência">
            <i class="ri-fire-line"></i>
        </button>
        <button class="tab-item" data-tab="settings" data-tooltip="Config">
            <i class="ri-compasses-2-line"></i>
        </button>
    </div>
</div>

<!-- SAFETY MODAL (Classifieds) -->
<div class="modal" id="safetyModal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="ri-alert-circle-fill"></i>
            <h2><?php esc_html_e('Segurança no Marketplace', 'apollo-social'); ?></h2>
        </div>
        <p><?php esc_html_e('Ao usar o Marketplace, você concorda em:'); ?></p>
        <ul class="safety-list">
            <li>
                <i class="ri-shield-check-fill"></i>
                <?php esc_html_e('Nunca compartilhar informações financeiras por mensagem', 'apollo-social'); ?>
            </li>
            <li>
                <i class="ri-shield-check-fill"></i>
                <?php esc_html_e('Sempre encontrar em locais públicos', 'apollo-social'); ?>
            </li>
            <li>
                <i class="ri-shield-check-fill"></i>
                <?php esc_html_e('Reportar comportamento suspeito imediatamente', 'apollo-social'); ?>
            </li>
            <li>
                <i class="ri-shield-check-fill"></i>
                <?php esc_html_e('Verificar identidade do vendedor', 'apollo-social'); ?>
            </li>
        </ul>
        <div class="modal-consent">
            <div class="checkbox-wrapper">
                <input type="checkbox" id="consent-checkbox">
                <label for="consent-checkbox">
                    <div class="custom-check"></div>
                    <span><?php esc_html_e('Entendo e aceito as recomendações de segurança', 'apollo-social'); ?></span>
                </label>
            </div>
        </div>
        <button class="btn-modal-main" id="btn-accept-safety">
            <?php esc_html_e('Entendi', 'apollo-social'); ?>
        </button>
        <span class="btn-cancel" id="btn-cancel-safety">
            <?php esc_html_e('Cancelar', 'apollo-social'); ?>
        </span>
    </div>
</div>

<?php wp_footer(); ?>
<script>
// Tab switching
document.querySelectorAll('.tab-item').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        document.querySelectorAll('.content-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.querySelectorAll('.tab-item').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    });
});

// Hide preloader
setTimeout(() => {
    const loader = document.getElementById('pageLoader');
    if (loader) loader.style.transform = 'translateY(-100%)';
}, 800);
</script>

</body>
</html>

	<?php /* ─── Navbar (from apollo-templates) ─── */ ?>
	<?php
	if ( function_exists( 'apollo_render_navbar' ) ) {
		apollo_render_navbar();
	}
	?>

	<?php /* ─── Tab Bar (fixed) ─── */ ?>
	<?php require $parts_dir . 'tab-bar.php'; ?>

	<!-- ─── APP MAIN ─── -->
	<main class="app-main" id="app-main">

		<!-- Feed Column -->
		<div class="feed-column" id="feed-column">

			<!-- Tab: Feed (active by default) -->
			<div id="tab-feed" class="content-panel active">
				<?php require $parts_dir . 'compose-box.php'; ?>
				<?php require $parts_dir . 'feed-container.php'; ?>
			</div>

			<!-- Tab: Events -->
			<div id="tab-events" class="content-panel">
				<div class="placeholder-panel">
					<i class="ri-map-pin-line"></i>
					<h3>Eventos</h3>
					<p>Descubra os próximos eventos da cena carioca.</p>
				</div>
			</div>

			<!-- Tab: Comunas -->
			<div id="tab-comunas" class="content-panel">
				<div class="placeholder-panel">
					<i class="ri-user-community-fill"></i>
					<h3>Comunas</h3>
					<p>Encontre e participe de comunidades.</p>
				</div>
			</div>

			<!-- Tab: Market -->
			<div id="tab-market" class="content-panel">
				<div class="placeholder-panel">
					<i class="ri-ticket-2-line"></i>
					<h3>Market</h3>
					<p>Anúncios e ofertas do marketplace.</p>
				</div>
			</div>

			<!-- Tab: Favs -->
			<div id="tab-favs" class="content-panel">
				<div class="placeholder-panel">
					<i class="ri-shining-2-fill"></i>
					<h3>Favs</h3>
					<p>Seus itens salvos e favoritos.</p>
				</div>
			</div>

			<!-- Tab: Settings -->
			<?php require $parts_dir . 'settings-panel.php'; ?>

		</div>

		<!-- Sidebar Column (desktop sticky) -->
		<?php require $parts_dir . 'sidebar.php'; ?>

	</main>

	<?php /* ─── Modals ─── */ ?>
	<?php require $parts_dir . 'modal-delete.php'; ?>
	<?php require $parts_dir . 'modal-safety.php'; ?>

	<?php /* ─── Toast Container ─── */ ?>
	<div class="toast-container" id="toast-container" aria-live="polite"></div>

	<?php /* ─── Scripts ─── */ ?>
	<?php require $parts_dir . 'scripts.php'; ?>

</body>

</html>