<?php
/**
 * Apollo Pane Engine — Blank Canvas: /casa
 *
 * v2.0 — Alpine.js 3.14 + HTMX 2.0 architecture.
 * This is a "Blank Canvas" template — zero theme dependencies.
 * Loads only the Apollo CDN + Alpine + HTMX + pane-alpine.js.
 *
 * @package Apollo\PaneEngine
 */

if (! defined('ABSPATH')) {
    exit;
}

$cdn_url = defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';
$ver     = APOLLO_PANE_ENGINE_VERSION;
$pe_url  = APOLLO_PANE_ENGINE_URL;

// Auth gate — handled by functions.php P5 template_redirect.
// Defence-in-depth: if somehow reached without auth, bail cleanly (no redirect = no loop).
if (! is_user_logged_in()) {
    status_header(403);
    exit;
}

$nonce   = wp_create_nonce('wp_rest');

// Preload detection — set by PaneModeAdapter when intercepting CPT URLs
$preload_section = sanitize_key(get_query_var('apollo_pane_preload_section', ''));
$preload_id      = absint(get_query_var('apollo_pane_preload_id', 0));
$preload_back    = sanitize_key(get_query_var('apollo_pane_back_route', 'casa'));
$preload_html    = '';
$preload_title   = '';
if ($preload_section && $preload_id) {
    $req = new \WP_REST_Request('GET', '/apollo/v1/pane/section/' . $preload_section . '/' . $preload_id);
    $res = rest_do_request($req);
    if (! is_wp_error($res) && 200 === $res->get_status()) {
        $preload_html = (string) $res->get_data();
        // Extract data-pane-title from preloaded HTML for ApolloPane config
        if (preg_match('/data-pane-title="([^"]*)"/i', $preload_html, $m)) {
            $preload_title = $m[1];
        }
    }
}

// Manifest data for navigation rendering
$manifest_path = APOLLO_PANE_ENGINE_PATH . 'pane-engine-casa.json';
$manifest      = file_exists($manifest_path)
    ? json_decode(file_get_contents($manifest_path), true)
    : ['navigation' => [], 'routes' => []];

$navigation    = $manifest['navigation'] ?? [];
$user          = wp_get_current_user();
$display_raw   = $user->display_name ?: $user->user_login;
$display       = esc_html($display_raw);
$section_url   = esc_url_raw(rest_url('apollo/v1/pane/section/'));
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="pane-html">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0a0a0f">
    <meta name="robots" content="noindex, nofollow">
    <title>Casa — Apollo</title>

    <!-- Apollo CDN (GSAP, RemixIcon, i18n, dark theme) -->
    <script src="<?php echo esc_url($cdn_url . 'core.min.js'); ?>" fetchpriority="high"></script>

    <!-- Alpine.js 3.14 (defer — evaluates after DOM parse) -->
    <script defer src="http://cdn.jsdelivr.net/npm/alpinejs@3.14/dist/cdn.min.js"></script>

    <!-- HTMX 2.0 -->
    <script src="http://unpkg.com/htmx.org@2.0/dist/htmx.min.js"></script>

    <!-- Pane Engine CSS -->
    <link rel="stylesheet" href="<?php echo esc_url($pe_url . 'assets/css/pane-engine.css'); ?>?v=<?php echo esc_attr($ver); ?>">

    <style>
        /* Critical inline — prevent FOUC */
        html, body { margin: 0; padding: 0; background: #0a0a0f; color: #e0e0e0; overflow: hidden; height: 100%; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="pane-body" data-theme="dark"
      x-data="paneApp"
      x-init="init()"
      @keydown.escape.window="closePanel()">

    <!-- ── Header ─────────────────────────────────────────── -->
    <header class="pane-header">
        <div class="pane-header__left">
            <button type="button"
                    class="pane-header__back"
                    x-show="!!cptView"
                    @click="navigateBack()"
                    title="Voltar"
                    x-cloak>
                <i class="ri-arrow-left-line"></i>
            </button>
            <i class="ri-home-smile-2-line" x-show="!cptView"></i>
            <span class="pane-header__title" x-text="currentTitle">Casa</span>
        </div>
        <div class="pane-header__right">
            <span class="pane-header__user"><?php echo $display; ?></span>
            <a href="<?php echo esc_url(home_url('/id/' . $user->user_login)); ?>"
               class="pane-header__avatar"
               title="<?php echo esc_attr($display_raw); ?>">
                <?php echo get_avatar($user->ID, 32); ?>
            </a>
        </div>
    </header>

    <!-- ── Viewport (panel container) ─────────────────────── -->
    <div class="pane-viewport">
        <!-- CENTER — main content area (HTMX target) -->
        <section id="pane-casa" class="pane-panel pane-panel--center" data-pane="casa" role="main">
            <div id="casa-root" class="pane-content">
                <?php if ($preload_html) : ?>
                    <?php echo $preload_html; ?>
                <?php else : ?>
                <!-- HTMX loads section content here -->
                <div class="htmx-indicator pane-skeleton-wrap">
                    <div class="pane-skeleton pane-skeleton--card"></div>
                    <div class="pane-skeleton pane-skeleton--card"></div>
                    <div class="pane-skeleton pane-skeleton--text"></div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- LEFT — slide-in navigation -->
        <aside id="pane-left"
               class="pane-panel pane-panel--left"
               data-pane="left"
               role="complementary"
               x-show="activePanel === 'left'"
               x-transition:enter="pane-overlay-enter"
               x-transition:enter-start="pane-overlay-enter-start"
               x-transition:enter-end="pane-overlay-enter-end"
               x-transition:leave="pane-overlay-leave"
               x-transition:leave-start="pane-overlay-leave-start"
               x-transition:leave-end="pane-overlay-leave-end"
               x-cloak>
            <div class="pane-content">
                <div class="pane-card">
                    <div class="pane-card__title"><i class="ri-menu-line"></i> Navegação</div>
                    <div class="pane-card__body">
                        <?php foreach ($navigation as $item) :
                            $key   = esc_attr($item['key'] ?? '');
                            $icon  = esc_attr($item['icon'] ?? 'ri-question-line');
                            $label = esc_html($item['label'] ?? ucfirst($key));
                        ?>
                        <button type="button"
                                class="pane-nav-link"
                                @click="navigate('<?php echo $key; ?>'); closePanel();">
                            <i class="<?php echo $icon; ?>"></i>
                            <span><?php echo $label; ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </aside>

        <!-- UP — slide-up overlay -->
        <aside id="pane-up"
               class="pane-panel pane-panel--up"
               data-pane="up"
               role="dialog"
               x-show="activePanel === 'up'"
               x-transition:enter="pane-overlay-enter"
               x-transition:enter-start="pane-overlay-enter-start-up"
               x-transition:enter-end="pane-overlay-enter-end"
               x-transition:leave="pane-overlay-leave"
               x-transition:leave-start="pane-overlay-leave-start"
               x-transition:leave-end="pane-overlay-leave-end-up"
               x-cloak>
            <div class="pane-content"></div>
        </aside>

        <!-- DOWN — slide-down drawer -->
        <aside id="pane-down"
               class="pane-panel pane-panel--down"
               data-pane="down"
               role="complementary"
               x-show="activePanel === 'down'"
               x-transition:enter="pane-overlay-enter"
               x-transition:enter-start="pane-overlay-enter-start-down"
               x-transition:enter-end="pane-overlay-enter-end"
               x-transition:leave="pane-overlay-leave"
               x-transition:leave-start="pane-overlay-leave-start"
               x-transition:leave-end="pane-overlay-leave-end-down"
               x-cloak>
            <div class="pane-content"></div>
        </aside>
    </div>

    <!-- ── Bottom Navigation ──────────────────────────────── -->
    <nav class="pane-nav" role="navigation" aria-label="Navegação principal">
        <?php foreach ($navigation as $item) :
            $key   = esc_attr($item['key'] ?? '');
            $icon  = esc_attr($item['icon'] ?? 'ri-question-line');
            $label = esc_html($item['label'] ?? ucfirst($key));
        ?>
        <button type="button"
                class="pane-nav__item"
                :class="{ 'is-active': currentRoute === '<?php echo $key; ?>' }"
                @click="navigate('<?php echo $key; ?>')"
                @mouseenter="prefetchSection('<?php echo $key; ?>')"
                aria-label="<?php echo $label; ?>">
            <i class="<?php echo $icon; ?>"></i>
            <span><?php echo $label; ?></span>
        </button>
        <?php endforeach; ?>
    </nav>

    <!-- ── Scripts ────────────────────────────────────────── -->
    <script>
        // Localized config for pane engine
        window.ApolloPane = {
            restBase:         <?php echo wp_json_encode(esc_url_raw(rest_url('apollo/v1/'))); ?>,
            sectionBase:      <?php echo wp_json_encode($section_url); ?>,
            nonce:            <?php echo wp_json_encode($nonce); ?>,
            homeUrl:          <?php echo wp_json_encode(esc_url_raw(home_url('/'))); ?>,
            casaUrl:          <?php echo wp_json_encode(esc_url_raw(home_url('/casa'))); ?>,
            version:          <?php echo wp_json_encode($ver); ?>,
            preloadSection:   <?php echo wp_json_encode($preload_section); ?>,
            preloadId:        <?php echo wp_json_encode($preload_id); ?>,
            preloadBackRoute: <?php echo wp_json_encode($preload_back); ?>,
            preloadTitle:     <?php echo wp_json_encode($preload_title); ?>
        };
    </script>

    <!-- Configure HTMX to send WP nonce on every request -->
    <script>
        document.addEventListener('htmx:configRequest', function(e) {
            e.detail.headers['X-WP-Nonce'] = window.ApolloPane.nonce;
        });
    </script>

    <!-- pane-alpine.js — single Alpine component replacing all old JS -->
    <script defer src="<?php echo esc_url($pe_url . 'assets/js/pane-alpine.js'); ?>?v=<?php echo esc_attr($ver); ?>"></script>

</body>
</html>
