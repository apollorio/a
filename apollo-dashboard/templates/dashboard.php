<?php

/**
 * Apollo Dashboard — Painel Principal V2
 *
 * Canvas-mode single-page tab system for /painel.
 * Tabs: Feed → Favs → Comunas → Events → Settings
 * Uses Apollo CDN + GSAP + ScrollTrigger.
 *
 * @package Apollo\Dashboard
 * @since   2.0.0
 */

defined('ABSPATH') || exit;

// ═══════════════════════════════════════════════════════════════
// AUTH GATE
// ═══════════════════════════════════════════════════════════════

if (! is_user_logged_in()) {
    wp_redirect(home_url('/acesso'));
    exit;
}

// ═══════════════════════════════════════════════════════════════
// DATA COLLECTION
// ═══════════════════════════════════════════════════════════════

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

// Display name
$social_name  = get_user_meta($user_id, '_apollo_social_name', true);
$display_name = $social_name ?: $current_user->display_name;
$first_name   = explode(' ', $display_name)[0];
$username     = $current_user->user_login;

// Avatar
$avatar_url = get_user_meta($user_id, '_apollo_avatar_url', true);
if (empty($avatar_url)) {
    $avatar_url = get_avatar_url($user_id, array('size' => 120));
}

// Greeting
$hour = (int) current_time('H');
if ($hour < 6) {
    $greeting = 'Boa madrugada';
} elseif ($hour < 12) {
    $greeting = 'Bom dia';
} elseif ($hour < 18) {
    $greeting = 'Boa tarde';
} else {
    $greeting = 'Boa noite';
}

// Active tab (from query var set by class-plugin.php)
$tab_slug_map = array(
    'feed'          => 'feed',
    'favoritos'     => 'favoritos',
    'comunas'       => 'comunas',
    'eventos'       => 'eventos',
    'configuracoes' => 'configuracoes',
);
$raw_tab      = get_query_var('apollo_dashboard_tab', 'feed');
$active_tab   = $tab_slug_map[$raw_tab] ?? 'feed';

// Template parts path
$parts_dir = __DIR__ . '/template-parts/dashboard/';

ob_start();
?>
    <?php if (defined('APOLLO_TEMPLATES_URL') && defined('APOLLO_TEMPLATES_VERSION')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/css/navbar.css'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>">
        <script src="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/js/navbar.js'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>" defer></script>
    <?php endif; ?>
    <?php require $parts_dir . 'styles.php'; ?>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'Apollo · ' . $greeting . ', ' . $first_name,
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body>

    <!-- ── Page Loader ── -->
    <div class="page-loader" id="pageLoader"></div>

    <!-- ── Navbar (injected by apollo-templates if active) ── -->
    <?php
    if (function_exists('apollo_render_navbar')) {
        apollo_render_navbar();
    }
    ?>

    <!-- ═══ TAB BAR ═══ -->
    <nav class="tab-bar">
        <div class="tab-item<?php echo $active_tab === 'feed' ? ' active' : ''; ?>" data-tab="feed"><i class="ri-home-smile-2-line"></i><span>Feed</span></div>
        <div class="tab-item<?php echo $active_tab === 'favoritos' ? ' active' : ''; ?>" data-tab="favoritos"><i class="ri-heart-3-line"></i><span>Favs</span></div>
        <div class="tab-item<?php echo $active_tab === 'comunas' ? ' active' : ''; ?>" data-tab="comunas"><i class="ri-group-line"></i><span>Comunas</span></div>
        <div class="tab-item<?php echo $active_tab === 'eventos' ? ' active' : ''; ?>" data-tab="eventos"><i class="ri-calendar-event-line"></i><span>Eventos</span></div>
        <div class="tab-item<?php echo $active_tab === 'configuracoes' ? ' active' : ''; ?>" data-tab="configuracoes"><i class="ri-compasses-2-line"></i><span>Config</span></div>
    </nav>

    <!-- ═══ PANELS ═══ -->
    <?php require $parts_dir . 'panel-feed.php'; ?>
    <?php require $parts_dir . 'panel-favs.php'; ?>
    <?php require $parts_dir . 'panel-comunas.php'; ?>
    <?php require $parts_dir . 'panel-events.php'; ?>
    <?php require $parts_dir . 'panel-settings.php'; ?>

    <!-- ═══ SCRIPTS ═══ -->
    <?php require $parts_dir . 'scripts.php'; ?>

</body>

</html>