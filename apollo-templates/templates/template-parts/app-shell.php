<?php

/**
 * APP-SHELL — canonical Apollo topbar (showcase contract).
 *
 * This is the single source of truth named by the design-system conversion map:
 *   "topbar/aside/painéis → apollo-templates/parts/app-shell.php"
 *
 * Markup is the APPROVED showcase structure verbatim (.ax-top-blur / .ax-top /
 * .ax-top-l / .ax-top-r / .ax-overlay / .apps-pop / .panel-r). Inline SVGs are
 * used exactly as the showcase does so icons render even before the icon font
 * loads. NOTHING here is invented — data comes from real WP sources.
 *
 * Auth states:
 *   guest  → .ax-top-r holds a single login control → /acesso
 *   logged → .ax-top-r holds #ic-act, #ic-apps, #ic-pf (showcase 1:1)
 *            + #apps-pop (real apps list) + #panel-profile (real user)
 *
 * Excluded pages: /acesso, /registre (and every apollo-login virtual page) —
 * they own their own chrome, same guard the legacy navbar used.
 *
 * Usage (any template / any plugin, incl. blank canvas apollo+):
 *   if ( function_exists( 'apollo_render_app_shell' ) ) { apollo_render_app_shell(); }
 *
 * @package Apollo\Templates
 * @since   1.2.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/* ── Exceptions: login/register virtual pages render no shell ── */
if (! empty(get_query_var('apollo_login_page', ''))) {
    return;
}

/* ── Claim the navbar slot so the legacy nh-navbar is never injected on top ── */
if (! defined('APOLLO_NAVBAR_LOADED')) {
    define('APOLLO_NAVBAR_LOADED', true);
}

$ash_logged  = is_user_logged_in();
$ash_user    = $ash_logged ? wp_get_current_user() : null;
$ash_uid     = $ash_logged ? (int) $ash_user->ID : 0;
$ash_profile = $ash_logged ? home_url('/id/' . $ash_user->user_login) : home_url('/acesso');

/* Initials for .ax-avb-init — same rule as the showcase avatar button. */
$ash_initials = '';
if ($ash_logged) {
    $ash_parts = preg_split('/\s+/', trim((string) $ash_user->display_name));
    foreach (array_slice((array) $ash_parts, 0, 2) as $ash_p) {
        $ash_initials .= mb_strtoupper(mb_substr($ash_p, 0, 1));
    }
    if ('' === $ash_initials) {
        $ash_initials = mb_strtoupper(mb_substr((string) $ash_user->user_login, 0, 2));
    }
}

$ash_notif = ($ash_logged && function_exists('apollo_get_unread_notif_count'))
    ? (int) apollo_get_unread_notif_count($ash_uid)
    : 0;

/* Real apps list — same source the legacy navbar used. */
if ($ash_logged) {
    if (class_exists('Apollo\\Templates\\NavbarSettings')) {
        $ash_apps = \Apollo\Templates\NavbarSettings::get_apps();
    } else {
        $ash_apps = array(
            array('label' => 'Eventos',       'url' => home_url('/eventos'),       'icon' => 'ri-calendar-line'),
            array('label' => 'Classificados', 'url' => home_url('/classificados'), 'icon' => 'ri-exchange-box-line'),
            array('label' => 'DJs',           'url' => home_url('/djs'),           'icon' => 'ri-sound-module-line'),
            array('label' => 'Espaços',       'url' => home_url('/criativo'),      'icon' => 'ri-map-pin-line'),
            array('label' => 'Acomoda',       'url' => home_url('/acomoda'),       'icon' => 'ri-home-heart-line'),
            array('label' => 'Feed',          'url' => home_url('/feed'),          'icon' => 'ri-global-line'),
            array('label' => 'Comunas',       'url' => home_url('/comunas'),       'icon' => 'ri-user-community-fill'),
            array('label' => 'Perfil',        'url' => $ash_profile,               'icon' => 'ri-contacts-book-fill'),
        );
    }
}
?>
<!-- ═══ TOPBAR (app-shell · showcase contract) ═══ -->
<div class="ax-top-blur" aria-hidden="true"></div>
<header class="ax-top" role="banner">
    <div class="ax-top-l">
        <button class="ax-burger" id="burger" aria-label="<?php esc_attr_e('Menu', 'apollo-templates'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M3 5h18v2H3V5Zm0 6h18v2H3v-2Zm0 6h18v2H3v-2Z"/></svg></button>
        <a class="ax-top-brand" href="<?php echo esc_url(home_url('/casa')); ?>" aria-label="apollo::rio">
            <i class="apollo"></i>
        </a>
    </div>
    <div class="ax-top-r">
        <?php if ($ash_logged) : ?>
            <a class="ax-ic" id="ic-act" href="<?php echo esc_url(home_url('/notificacoes')); ?>" aria-label="<?php esc_attr_e('Atividades', 'apollo-templates'); ?>"><?php if ($ash_notif > 0) : ?><span class="ax-dot"></span><?php endif; ?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M6.11629 20.0868C3.62137 18.2684 2 15.3236 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 15.3236 20.3786 18.2684 17.8837 20.0868L16.8692 18.348C18.7729 16.8856 20 14.5861 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 14.5861 5.2271 16.8856 7.1308 18.348L6.11629 20.0868ZM8.14965 16.6018C6.83562 15.5012 6 13.8482 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 13.8482 17.1644 15.5012 15.8503 16.6018L14.8203 14.8365C15.549 14.112 16 13.1087 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 13.1087 8.45105 14.112 9.17965 14.8365L8.14965 16.6018ZM11 13H13L14 22H10L11 13Z"></path></svg></a>
            <button class="ax-ic" id="ic-apps" aria-label="<?php esc_attr_e('Apps', 'apollo-templates'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M21 17C21 19.2091 19.2091 21 17 21C14.7909 21 13 19.2091 13 17C13 14.7909 14.7909 13 17 13C19.2091 13 21 14.7909 21 17ZM11 7C11 9.20914 9.20914 11 7 11C4.79086 11 3 9.20914 3 7C3 4.79086 4.79086 3 7 3C9.20914 3 11 4.79086 11 7ZM21 7C21 9.20914 19.2091 11 17 11C16.2584 11 15.5634 10.7972 14.9678 10.4453L10.4453 14.9678C10.7972 15.5634 11 16.2584 11 17C11 19.2091 9.20914 21 7 21C4.79086 21 3 19.2091 3 17C3 14.7909 4.79086 13 7 13C7.74116 13 8.43593 13.2022 9.03125 13.5537L13.5537 9.03125C13.2022 8.43593 13 7.74116 13 7C13 4.79086 14.7909 3 17 3C19.2091 3 21 4.79086 21 7Z"></path></svg></button>
            <button class="ax-avb" id="ic-pf" aria-label="<?php esc_attr_e('Usuá::rio', 'apollo-templates'); ?>"><span class="ax-avb-init"><?php echo esc_html($ash_initials); ?></span></button>
        <?php else : ?>
            <a class="ax-ic ax-login" id="ic-login" href="<?php echo esc_url(home_url('/acesso')); ?>" aria-label="<?php esc_attr_e('Entrar', 'apollo-templates'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M10 11V8L15 12L10 16V13H1V11H10ZM2.4578 15H4.58152C5.76829 17.9318 8.64262 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C8.64262 4 5.76829 6.06817 4.58152 9H2.4578C3.73207 4.94289 7.52236 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22C7.52236 22 3.73207 19.0571 2.4578 15Z"></path></svg></a>
        <?php endif; ?>
    </div>
</header>

<!-- shared overlay -->
<div class="ax-overlay" id="ax-overlay" aria-hidden="true"></div>

<?php if ($ash_logged) : ?>
<!-- ═══ APPS POPUP ═══ -->
<div class="apps-pop" id="apps-pop" aria-hidden="true">
    <div class="apps-pop-hd">
        <p class="apps-pop-title">Apollo Suite</p>
        <button type="button" class="apps-pop-close" data-close-apps aria-label="<?php esc_attr_e('Fechar apps', 'apollo-templates'); ?>"><i class="ri-close-line"></i></button>
    </div>
    <div class="apps-grid">
        <?php foreach ($ash_apps as $ash_app) : ?>
            <a class="app-cell" href="<?php echo esc_url($ash_app['url']); ?>"><div class="app-icon"><i class="<?php echo esc_attr($ash_app['icon']); ?>"></i></div><span><?php echo esc_html($ash_app['label']); ?></span></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══ PROFILE PANEL ═══ -->
<div class="panel-r" id="panel-profile" aria-label="<?php esc_attr_e('Usuário', 'apollo-templates'); ?>" aria-hidden="true">
    <div class="panel-r-hd">
        <span class="panel-r-title">Usuá::rio</span>
        <button class="ax-ic" data-close="panel-profile" aria-label="<?php esc_attr_e('Fechar', 'apollo-templates'); ?>"><i class="ri-close-line"></i></button>
    </div>
    <div class="panel-r-body" data-lenis-prevent data-lenis-prevent-wheel data-lenis-prevent-touch>
        <div class="profile-panel-top">
            <div class="profile-panel-av"><?php echo esc_html($ash_initials); ?></div>
            <p class="profile-panel-name"><?php echo esc_html($ash_user->display_name); ?></p>
            <p class="profile-panel-role">@<?php echo esc_html($ash_user->user_login); ?></p>
        </div>
        <div class="profile-panel-actions">
            <a class="profile-action" href="<?php echo esc_url($ash_profile); ?>"><i class="ri-id-card-line"></i><?php esc_html_e('Meu perfil', 'apollo-templates'); ?></a>
            <a class="profile-action" href="<?php echo esc_url($ash_profile . '/edit'); ?>"><i class="ri-compasses-2-line"></i><?php esc_html_e('Editar Perfil', 'apollo-templates'); ?></a>
            <a class="profile-action" href="<?php echo esc_url(home_url('/mensagens')); ?>"><i class="ri-message-3-line"></i><?php esc_html_e('Mensagens', 'apollo-templates'); ?></a>
            <a class="profile-action" href="<?php echo esc_url(home_url('/notificacoes')); ?>"><i class="ri-notification-3-line"></i><?php esc_html_e('Notificações', 'apollo-templates'); ?></a>
            <a class="profile-action" href="<?php echo esc_url(home_url('/painel')); ?>"><i class="ri-server-line"></i>Dashboard</a>
            <button type="button" class="profile-action supportBTN" data-apollo-suporte><i class="ri-user-community-line" aria-hidden="true"></i><?php esc_html_e('Suporte', 'apollo-templates'); ?></button>
            <a class="profile-action danger" href="<?php echo esc_url(wp_logout_url(home_url())); ?>"><i class="ri-door-open-line"></i><?php esc_html_e('Sair', 'apollo-templates'); ?></a>
        </div>
    </div>
</div>
<?php endif; ?>
