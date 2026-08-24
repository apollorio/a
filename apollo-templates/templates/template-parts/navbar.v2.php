<?php

/**
 * Apollo Navbar v2 — Global Persistent UI
 *
 * New pill navbar + FAB (bottom-right) for ALL WordPress pages.
 * Replaces navbar.v1.php in the injection chain via apollo-templates.php.
 *
 * Structure:
 * - <nav class="nh-navbar">  : pill, glass, fixed top-center
 * - .nh-menu-fab              : dark circle, fixed bottom-right
 * - #nhMenuSheet              : upward navigation sheet
 * - #nhAppsDropdown           : apps grid (logged only)
 * - #nhProfileDropdown        : profile menu (logged only)
 *
 * Exclusions (FAB hidden via PHP):
 * - /acesso and virtual login pages (caller returns early — never reaches here)
 * - Single CPT pages (event, dj, loc, etc.)
 * - Chat / private messages page
 *
 * @package Apollo\Templates
 * @since   6.2.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// Prevent double injection (canvas templates that include this manually set this constant)
if (! defined('APOLLO_NAVBAR_LOADED')) {
    define('APOLLO_NAVBAR_LOADED', true);
}

/* ─── Auth State ─── */
$is_logged_in = is_user_logged_in();
$auth_state   = $is_logged_in ? 'logged' : 'guest';
$current_user = $is_logged_in ? wp_get_current_user() : null;
$user_id      = $is_logged_in ? get_current_user_id() : 0;

/* ─── User Avatar ─── */
$avatar_url = '';
if ($is_logged_in && $user_id) {
    $avatar_url = get_avatar_url($user_id, array('size' => 64, 'default' => 'mystery'));
}

/* ─── Profile / Auth URLs ─── */
$profile_url = $is_logged_in && $current_user ? home_url('/id/' . $current_user->user_login) : home_url('/acesso');
$logout_url  = $is_logged_in ? wp_logout_url(home_url()) : '';

/* ─── Notification & Chat Counts ─── */
$notif_count = function_exists('apollo_get_unread_notif_count') ? apollo_get_unread_notif_count($user_id) : 0;
$chat_count  = function_exists('apollo_get_unread_message_count') ? apollo_get_unread_message_count($user_id) : 0;

/* ─── Apps List (NavbarSettings or fallback) ─── */
if (class_exists('Apollo\\Templates\\NavbarSettings')) {
    $apps_list = \Apollo\Templates\NavbarSettings::get_apps();
} else {
    $apps_list = array(
        array('label' => 'Eventos',      'url' => home_url('/eventos'),      'icon' => 'ri-calendar-line',         'bg_color' => 'FF9820'),
        array('label' => 'Classificados', 'url' => home_url('/classificados'), 'icon' => 'ri-exchange-box-line',       'bg_color' => '#3b82f6'),
        array('label' => 'DJs',          'url' => home_url('/djs'),           'icon' => 'ri-sound-module-line',     'bg_color' => '#a855f7'),
        array('label' => 'Espaços',      'url' => home_url('/criativo'),      'icon' => 'ri-map-pin-line',          'bg_color' => '#22c55e'),
        array('label' => 'Acomoda',      'url' => home_url('/acomoda'),       'icon' => 'ri-home-heart-line',       'bg_color' => '#ec4899'),
        array('label' => 'Feed',         'url' => home_url('/feed'),          'icon' => 'ri-global-line',           'bg_color' => '#06b6d4'),
        array('label' => 'Comunas',      'url' => home_url('/comunas'),       'icon' => 'ri-user-community-fill',   'bg_color' => '#eab308'),
        array('label' => 'Perfil',       'url' => $profile_url,                 'icon' => 'ri-contacts-book-fill',    'bg_color' => '#64748b'),
        array('label' => 'Documentos',   'url' => home_url('/docs'),          'icon' => 'ri-book-read-fill',        'bg_color' => '#334155'),
    );
}

/* ─── FAB Exclusion — hide on single CPT, chat, inbox pages ─── */
$hide_fab = is_singular(array('event', 'dj', 'loc', 'apollo_classified', 'supplier'))
    || is_page(array('mensagens', 'chat', 'inbox'));

/* ─── Aside detection (used for body class only, NOT for hiding FAB) ─── */
$has_aside = apply_filters('apollo/templates/has_aside', false);
?>

<?php
/* ═══════════════════════════════════════════════════════════════════════
   LEGACY TOPBAR (nh-navbar + its dropdowns).
   Skipped whenever the canonical Apollo app-shell (.ax-top) already
   rendered — the mandatory layout contract owns the topbar. The FAB +
   navigation sheet below ALWAYS render: they are this file's unique
   contribution and the only nav menu these pages have.
   ═══════════════════════════════════════════════════════════════════════ */
if (! defined('APOLLO_APP_SHELL_LOADED')) :
?>
<!-- ═══════════════════════════════════════════════════════════════
     NAVBAR v2 — pill glass, panel-engine integrated
     ═══════════════════════════════════════════════════════════════ -->
<nav class="nh-navbar" id="nhNav" data-auth="<?php echo esc_attr($auth_state); ?>"
    <?php if ($user_id) : ?> data-a-user="<?php echo esc_attr((string) $user_id); ?>"<?php endif; ?>
    data-a-component="navbar"
    role="navigation" aria-label="<?php esc_attr_e('Navegação principal', 'apollo-templates'); ?>">

    <!-- LOGO -->
    <div class="nh-nav-logo">
        <i class="apollo" aria-label="Apollo Rio"></i>
    </div>

    <?php if ($is_logged_in) : ?>
        <div class="nh-nav-actions">

            <!-- APPS — dropdown toggle (logged only) -->
            <button class="nh-nav-btn" id="nhAppsBtn"
                aria-label="<?php esc_attr_e('Apps', 'apollo-templates'); ?>"
                aria-expanded="false" aria-haspopup="true"
                title="<?php esc_attr_e('Apps', 'apollo-templates'); ?>">
                <i class="ri-apps-fill" aria-hidden="true"></i>
            </button>

            <!-- CHAT — panel slides LEFT -->
            <button class="nh-nav-btn" id="nhChatBtn"
                data-to="chat-list" data-dir="left"
                aria-label="<?php esc_attr_e('Abrir chat', 'apollo-templates'); ?>"
                title="Chat">
                <i class="ri-message-3-fill" aria-hidden="true"></i>
                <span class="nh-notif-dot" id="nhChatBadge"
                    data-notif="<?php echo $chat_count > 0 ? 'true' : 'false'; ?>"
                    aria-hidden="true"></span>
            </button>

            <!-- NOTIFICATIONS — panel slides UP -->
            <button class="nh-nav-btn nh-nav-btn--notif" id="nhNotifBtn"
                data-to="mural" data-dir="up"
                aria-label="<?php esc_attr_e('Notificações', 'apollo-templates'); ?>"
                title="<?php esc_attr_e('Notificações', 'apollo-templates'); ?>">
                <svg class="nh-nav-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M6.11629 20.0868C3.62137 18.2684 2 15.3236 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 15.3236 20.3786 18.2684 17.8837 20.0868L16.8692 18.348C18.7729 16.8856 20 14.5861 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 14.5861 5.2271 16.8856 7.1308 18.348L6.11629 20.0868ZM8.14965 16.6018C6.83562 15.5012 6 13.8482 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 13.8482 17.1644 15.5012 15.8503 16.6018L14.8203 14.8365C15.549 14.112 16 13.1087 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 13.1087 8.45105 14.112 9.17965 14.8365L8.14965 16.6018ZM11 13H13L14 22H10L11 13Z" />
                </svg>
                <span class="nh-notif-dot" id="nhNotifBadge"
                    data-notif="<?php echo $notif_count > 0 ? 'true' : 'false'; ?>"
                    aria-hidden="true"></span>
            </button>

            <!-- PROFILE — dropdown toggle -->
            <button class="nh-nav-btn nh-nav-btn--avatar" id="nhProfileBtn"
                aria-label="<?php esc_attr_e('Meu perfil', 'apollo-templates'); ?>"
                aria-expanded="false" aria-haspopup="true"
                title="<?php esc_attr_e('Perfil', 'apollo-templates'); ?>">
                <img class="nh-avatar-img" src="<?php echo esc_url($avatar_url); ?>" alt="" width="28" height="28" loading="lazy" />
            </button>

        </div>
    <?php endif; // logged in — guest sees logo only 
    ?>
</nav>


<!-- ═══════════════════════════════════════════════════════════════
     APPS DROPDOWN — Glass grid (logged only, outside navbar for z-index)
     ═══════════════════════════════════════════════════════════════ -->
<?php if ($is_logged_in) : ?>
    <div class="nh-dropdown nh-dropdown--apps" id="nhAppsDropdown" role="menu"
        aria-label="<?php esc_attr_e('Menu de apps', 'apollo-templates'); ?>">

        <div class="nh-dropdown__header">
            <span class="nh-dropdown__title"><?php esc_html_e('Apps', 'apollo-templates'); ?></span>
        </div>

        <div class="nh-apps-grid">
            <?php foreach ($apps_list as $app) :
                $icon_class = isset($app['icon']) ? sanitize_text_field($app['icon']) : 'ri-apps-fill';
                $bg_color   = isset($app['bg_color']) ? $app['bg_color'] : '#64748b';
                $bg_image   = ! empty($app['bg_image']) ? esc_url($app['bg_image']) : '';
                $style      = $bg_image
                    ? sprintf('background: url(%s) center/cover;', $bg_image)
                    : sprintf('background: %s;', esc_attr($bg_color));
            ?>
                <a href="<?php echo esc_url($app['url']); ?>" class="nh-app-item" role="menuitem">
                    <span class="nh-app-icon" style="<?php echo esc_attr($style); ?>">
                        <i class="<?php echo esc_attr($icon_class); ?>"></i>
                    </span>
                    <span class="nh-app-label"><?php echo esc_html($app['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>


    <!-- ═══════════════════════════════════════════════════════════════
     PROFILE DROPDOWN (logged only)
     ═══════════════════════════════════════════════════════════════ -->
    <div class="nh-dropdown nh-dropdown--profile" id="nhProfileDropdown" role="menu"
        aria-label="<?php esc_attr_e('Menu de perfil', 'apollo-templates'); ?>">

        <!-- User header -->
        <div class="nh-profile-header">
            <img class="nh-avatar-img nh-avatar-img--lg" src="<?php echo esc_url($avatar_url); ?>" alt="" width="40" height="40" loading="lazy" />
            <div class="nh-profile-info">
                <span class="nh-profile-name"><?php echo esc_html($current_user->display_name); ?></span>
                <span class="nh-profile-handle">@<?php echo esc_html($current_user->user_login); ?></span>
            </div>
        </div>

        <div class="nh-sheet-divider" aria-hidden="true"></div>

        <a href="<?php echo esc_url($profile_url); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-user-line"></i><?php esc_html_e('Meu Perfil', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/id/' . $current_user->user_login . '/edit')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-compasses-2-line"></i><?php esc_html_e('Editar Perfil', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/mensagens')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-message-3-line"></i><?php esc_html_e('Mensagens', 'apollo-templates'); ?>
            <?php if ($chat_count > 0) : ?>
                <span class="nh-menu-badge"><?php echo esc_html($chat_count); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(home_url('/notificacoes')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-notification-3-line"></i><?php esc_html_e('Notificações', 'apollo-templates'); ?>
            <?php if ($notif_count > 0) : ?>
                <span class="nh-menu-badge"><?php echo esc_html($notif_count); ?></span>
            <?php endif; ?>
        </a>

        <div class="nh-sheet-divider" aria-hidden="true"></div>

        <a href="<?php echo esc_url(home_url('/feed')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-global-line"></i> Feed
        </a>
        <a href="<?php echo esc_url(home_url('/comunas')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-user-community-line"></i><?php esc_html_e('Comunas', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/painel')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-server-line"></i> Dashboard
        </a>

        <div class="nh-sheet-divider" aria-hidden="true"></div>

        <a href="<?php echo esc_url($logout_url); ?>" class="nh-sheet-item nh-sheet-item--danger" role="menuitem">
            <i class="ri-logout-box-r-line"></i><?php esc_html_e('Sair', 'apollo-templates'); ?>
        </a>
    </div>
<?php endif; // logged in dropdowns
?>
<?php endif; // legacy topbar — skipped when .ax-top app-shell is present
?>


<!-- ═══════════════════════════════════════════════════════════════
     MENU APP FAB — bottom-right circle + upward navigation sheet
     Hidden on: single CPT pages, chat page, inbox
     ═══════════════════════════════════════════════════════════════ -->
<?php if (! $hide_fab) : ?>
    <button class="nh-menu-fab"
        id="nhMenuFab"
        aria-label="<?php esc_attr_e('Menu Apollo', 'apollo-templates'); ?>"
        aria-expanded="false"
        aria-haspopup="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
            aria-hidden="true" width="20" height="20"
            style="display:block;flex-shrink:0;pointer-events:none">
            <path d="M21 17C21 19.2091 19.2091 21 17 21C14.7909 21 13 19.2091 13 17C13 14.7909 14.7909 13 17 13C19.2091 13 21 14.7909 21 17ZM11 7C11 9.20914 9.20914 11 7 11C4.79086 11 3 9.20914 3 7C3 4.79086 4.79086 3 7 3C9.20914 3 11 4.79086 11 7ZM21 7C21 9.20914 19.2091 11 17 11C16.2584 11 15.5634 10.7972 14.9678 10.4453L10.4453 14.9678C10.7972 15.5634 11 16.2584 11 17C11 19.2091 9.20914 21 7 21C4.79086 21 3 19.2091 3 17C3 14.7909 4.79086 13 7 13C7.74116 13 8.43593 13.2022 9.03125 13.5537L13.5537 9.03125C13.2022 8.43593 13 7.74116 13 7C13 4.79086 14.7909 3 17 3C19.2091 3 21 4.79086 21 7Z" />
        </svg>
    </button>

    <!-- Navigation sheet — slides upward on FAB click -->
    <div class="nh-menu-sheet" id="nhMenuSheet"
        role="menu"
        aria-label="<?php esc_attr_e('Menu de navegação', 'apollo-templates'); ?>">

        <!-- Public apps (always visible) -->
        <a href="<?php echo esc_url(home_url('/eventos')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-calendar-event-line"></i><?php esc_html_e('Eventos', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/djs')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-sound-module-line"></i><?php esc_html_e('DJs & Artistas', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/criativo')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-map-pin-line"></i><?php esc_html_e('Espaços', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/classificados')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-exchange-box-line"></i><?php esc_html_e('Classificados', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/acomoda')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-home-heart-line"></i><?php esc_html_e('Acomoda::Rio', 'apollo-templates'); ?>
        </a>

        <?php if ($is_logged_in) : ?>

            <div class="nh-sheet-divider" aria-hidden="true"></div>

            <!-- Logged-only quick links -->
            <a href="<?php echo esc_url(home_url('/documentos')); ?>" class="nh-sheet-item" role="menuitem">
                <i class="ri-book-read-fill"></i><?php esc_html_e('Documentos', 'apollo-templates'); ?>
            </a>
            <a href="<?php echo esc_url(home_url('/mensagens')); ?>" class="nh-sheet-item" role="menuitem">
                <i class="ri-message-3-line"></i><?php esc_html_e('Mensagens', 'apollo-templates'); ?>
            </a>
            <a href="<?php echo esc_url(home_url('/notificacoes')); ?>" class="nh-sheet-item" role="menuitem">
                <i class="ri-notification-3-line"></i><?php esc_html_e('Notificações', 'apollo-templates'); ?>
            </a>

            <div class="nh-sheet-divider" aria-hidden="true"></div>

            <!-- Create actions -->
            <a href="<?php echo esc_url(home_url('/acesso')); ?>" class="nh-sheet-item" role="menuitem"
                data-form="create-event">
                <i class="ri-calendar-line"></i><?php esc_html_e('Criar Evento', 'apollo-templates'); ?>
            </a>
            <a href="<?php echo esc_url(home_url('/classificados/novo')); ?>" class="nh-sheet-item" role="menuitem">
                <i class="ri-exchange-box-line"></i><?php esc_html_e('Criar Anúncio', 'apollo-templates'); ?>
            </a>

            <div class="nh-sheet-divider" aria-hidden="true"></div>

            <a href="<?php echo esc_url(home_url('/painel')); ?>" class="nh-sheet-item nh-sheet-item--cta" role="menuitem">
                <i class="ri-server-line"></i>Dashboard
            </a>

        <?php else : ?>

            <div class="nh-sheet-divider" aria-hidden="true"></div>

            <!-- Guest: direct link to /acesso -->
            <a href="<?php echo esc_url(home_url('/acesso')); ?>" class="nh-sheet-item nh-sheet-item--cta" role="menuitem">
                <i class="ri-login-circle-line"></i><?php esc_html_e('Entrar / Cadastrar', 'apollo-templates'); ?>
            </a>

        <?php endif; ?>
    </div>
<?php endif; // !$hide_fab 
?>


<!-- ═══════════════════════════════════════════════════════════════
     OVERLAY — Full viewport backdrop to block clicks behind menus
     ═══════════════════════════════════════════════════════════════ -->
<div class="nh-overlay" id="nhOverlay" aria-hidden="true"></div>

<!-- JS config injected inline for navbar.v2.js -->
<script>
    window.apolloNavbar = {
        ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
        restUrl: <?php echo wp_json_encode(esc_url_raw(rest_url('apollo/v1/'))); ?>,
        nonce: <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>,
        authState: <?php echo wp_json_encode($auth_state); ?>,
        showFab: <?php echo wp_json_encode(! $hide_fab); ?>,
        hasAside: <?php echo wp_json_encode($has_aside); ?>
    };
</script>
<?php if ($has_aside) : ?>
<script>document.body.classList.add('has-apollo-aside');</script>
<?php endif; ?>