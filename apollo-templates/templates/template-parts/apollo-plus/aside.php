<?php

/**
 * Apollo+ Shell — .ax-aside (mandatory persistent primary nav)
 *
 * The LEFT SIDEBAR of the "Blank Canvas Apollo+" shell. Despite the informal
 * name "header" this is NOT the topbar (.ax-top, owned by app-shell.php) —
 * it's the drawer that slides in from #burger on mobile and pins at 1000px+.
 *
 * Renders for GUESTS and LOGGED-IN users alike: the primary nav is chrome,
 * not a member feature. Only the "Gestor" group and the user row are
 * auth-gated, because those are about *your* things.
 *
 * ROUTES — real WordPress URLs, not the mockup's #/hash routes. The mockup is
 * a single-page router; this is WordPress, so every entry is a real page. Each
 * target was resolved against plugins/_inventory/registry/:
 *   Feed             /feed      apollo-templates page-feed.php (auth:true)
 *   Eventos          /eventos   apollo-events archive (Portal de Eventos)
 *   Marketplace      /anuncios  apollo-adverts classified archive
 *   Comuna           /comunas   apollo-groups virtual route
 *   Hub.rio          /hub       apollo-hub
 *   Redução de Danos external   https://sos.apollo.rio.br/ (target=_blank)
 *   Mapa             /mapa      apollo-maps
 *
 * The active row is resolved server-side from the current request path, so
 * the highlight is correct on first paint with no JS and no flash.
 *
 * @package Apollo\Templates
 * @since   1.4.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/* Login/register own their chrome — same guard app-shell.php uses. */
if (! empty(get_query_var('apollo_login_page', ''))) {
    return;
}

if (defined('APOLLO_PLUS_ASIDE_LOADED')) {
    return;
}
define('APOLLO_PLUS_ASIDE_LOADED', true);

require_once __DIR__ . '/aside-styles.php';

$apl_logged = is_user_logged_in();
$apl_user   = $apl_logged ? wp_get_current_user() : null;

/* Current path, normalised to a leading+trailing-slash-free segment string. */
$apl_path = trim((string) wp_parse_url(add_query_arg(array()), PHP_URL_PATH), '/');

/**
 * Is $slug the screen currently being viewed?
 * Matches the segment exactly or as a path prefix (so /eventos/meus still
 * lights up Eventos), never a substring (so /anuncios never matches /anu).
 */
$apl_is_on = static function (string $slug) use ($apl_path): bool {
    if ('' === $slug) {
        return false;
    }
    return $apl_path === $slug || str_starts_with($apl_path, $slug . '/');
};

/* Primary nav — the mandatory fixed structure. */
$apl_nav = array(
    array('slug' => 'feed',     'url' => home_url('/feed'),     'icon' => 'ri-building-3-line',    'label' => __('Feed', 'apollo-templates')),
    array('slug' => 'eventos',  'url' => home_url('/eventos'),  'icon' => 'ri-ticket-2-line',      'label' => __('Eventos', 'apollo-templates')),
    array('slug' => 'anuncios', 'url' => home_url('/anuncios'), 'icon' => 'ri-store-3-line',       'label' => __('Marketplace', 'apollo-templates')),
    array('slug' => 'comunas',  'url' => home_url('/comunas'),  'icon' => 'ri-group-3-fill',       'label' => __('Comuna', 'apollo-templates')),
    array('slug' => 'hub',      'url' => home_url('/hub'),      'icon' => 'ri-body-scan-line',     'label' => __('Hub.rio', 'apollo-templates')),
    array('slug' => '',         'url' => 'https://sos.apollo.rio.br/', 'icon' => 'ri-mental-health-line', 'label' => __('Redução de Danos', 'apollo-templates'), 'external' => true),
    array('slug' => 'mapa',     'url' => home_url('/mapa'),     'icon' => 'ri-map-2-line',         'label' => __('Mapa', 'apollo-templates')),
);

/* Gestor group — "my things", members only. Counts come from real queries via
   apollo_plus_gestor_count(); absent providers simply render 0. */
$apl_gestor = array(
    array('slug' => 'eventos/meus',   'url' => home_url('/eventos/meus'),   'icon' => 'ri-calendar-check-line',   'label' => __('Meus eventos', 'apollo-templates'),   'cnt' => 'meus-eventos'),
    array('slug' => 'anuncios/meus',  'url' => home_url('/anuncios/meus'),  'icon' => 'ri-swap-box-line',         'label' => __('Meus anúncios', 'apollo-templates'),  'cnt' => 'meus-anuncios'),
    array('slug' => 'comunas/gestao', 'url' => home_url('/comunas/gestao'), 'icon' => 'ri-group-3-line',          'label' => __('Minhas Comunas', 'apollo-templates'), 'cnt' => 'comunas-gestao'),
    array('slug' => 'nucleos',        'url' => home_url('/nucleos'),        'icon' => 'ri-briefcase-4-line',      'label' => __('Meus Núcleos', 'apollo-templates'),   'cnt' => 'meus-nucleos'),
    array('slug' => 'hub/projetos',   'url' => home_url('/hub/projetos'),   'icon' => 'ri-terminal-window-line',  'label' => __('Meus projetos', 'apollo-templates'),  'cnt' => 'meus-projetos'),
    array('slug' => 'hub/tarefas',    'url' => home_url('/hub/tarefas'),    'icon' => 'ri-todo-line',             'label' => __('Minhas tarefas', 'apollo-templates'), 'cnt' => 'meus-tarefas'),
);
?>
<aside class="ax-aside" id="ax-aside" aria-label="<?php esc_attr_e('Navegação', 'apollo-templates'); ?>">
    <div class="s">
        <div class="hd">
            <div class="lg"><i class="ri-hexagon-fill" data-apollo-icon="apollo-s" aria-hidden="true"></i></div>
            <span class="wm">apollo<b>::</b>rio</span>
        </div>
        <button type="button" class="ax-aside-close" aria-label="<?php esc_attr_e('Fechar menu', 'apollo-templates'); ?>">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>

        <nav class="ax-aside-scroll" data-lenis-prevent data-lenis-prevent-wheel data-lenis-prevent-touch>

            <?php /* ── Primary nav: always rendered, guests included ── */ ?>
            <div class="nb" style="line-height:1">
                <?php foreach ($apl_nav as $apl_item) : ?>
                    <?php $apl_on = empty($apl_item['external']) && $apl_is_on($apl_item['slug']); ?>
                    <a class="ni<?php echo $apl_on ? ' on' : ''; ?>"
                        href="<?php echo esc_url($apl_item['url']); ?>"
                        <?php if (! empty($apl_item['external'])) : ?>target="_blank" rel="noopener"<?php endif; ?>
                        <?php if ($apl_on) : ?>aria-current="page"<?php endif; ?>>
                        <i class="<?php echo esc_attr($apl_item['icon']); ?>" aria-hidden="true"></i>
                        <span class="sn"><?php echo esc_html($apl_item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
                <div class="dv" style="margin:2px 10px;"></div>
            </div>

            <?php if ($apl_logged) : ?>
                <div class="fs">
                    <?php /* Eventos Radar — hydrated from the user's real RSVPs. */ ?>
                    <div class="sh" data-col="col-files">
                        <span class="slbl"><?php esc_html_e('Eventos Radar', 'apollo-templates'); ?></span>
                        <i class="ri-more-2-line tog" aria-hidden="true"></i>
                    </div>
                    <div class="col" id="col-files">
                        <div class="inn" id="radarInn">
                            <?php
                            $apl_radar = function_exists('apollo_plus_radar_rows') ? apollo_plus_radar_rows(5) : array();
                            if (empty($apl_radar)) :
                                ?>
                                <p class="fr" style="cursor:default;opacity:.55"><span class="fn"><?php esc_html_e('Nenhum evento no radar', 'apollo-templates'); ?></span></p>
                            <?php else : ?>
                                <?php foreach ($apl_radar as $apl_row) : ?>
                                    <a class="fr" href="<?php echo esc_url($apl_row['url']); ?>">
                                        <span class="fd"><?php echo esc_html($apl_row['date']); ?></span>
                                        <span class="fn"><?php echo esc_html($apl_row['title']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                                <a class="va" href="<?php echo esc_url(home_url('/eventos')); ?>"><?php esc_html_e('Ver tudo', 'apollo-templates'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dv"></div>

                    <div class="sh" data-col="col-mgr">
                        <span class="slbl"><?php esc_html_e('Gestor', 'apollo-templates'); ?></span>
                        <i class="ri-more-2-line tog" aria-hidden="true"></i>
                    </div>
                    <div class="col" id="col-mgr">
                        <div class="inn">
                            <?php foreach ($apl_gestor as $apl_g) : ?>
                                <a class="si<?php echo $apl_is_on($apl_g['slug']) ? ' on' : ''; ?>" href="<?php echo esc_url($apl_g['url']); ?>">
                                    <i class="<?php echo esc_attr($apl_g['icon']); ?>" aria-hidden="true"></i>
                                    <span class="sn"><?php echo esc_html($apl_g['label']); ?></span>
                                    <span class="cnt" data-cnt="<?php echo esc_attr($apl_g['cnt']); ?>"><?php
                                        echo esc_html((string) (function_exists('apollo_plus_gestor_count') ? apollo_plus_gestor_count($apl_g['cnt']) : 0));
                                    ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <div class="ft">
            <?php /* Suporte — data-apollo-suporte is the ecosystem-wide hook every
                     help/support control must carry (apollo-suporte.js binds to it). */ ?>
            <?php /* data-apollo-suporte sits on the button, but the icon and label are
                     what the pointer actually lands on. If the support runtime matches
                     the event TARGET rather than walking up to the nearest
                     [data-apollo-suporte] ancestor, a click on the glyph or on the word
                     "Suporte" hits nothing. Children are made click-transparent in CSS
                     AND carry the hook themselves, so the whole row is live regardless
                     of which selector strategy the runtime uses. */ ?>
            <button type="button" class="fni supportBTN" data-apollo-suporte style="line-height:.7">
                <i class="ri-user-community-line" data-apollo-icon="user-community-v" data-apollo-suporte aria-hidden="true"></i>
                <span class="sn" data-apollo-suporte><?php esc_html_e('Suporte', 'apollo-templates'); ?></span>
            </button>

            <?php /* "Ajustes" — the ecosystem label for every preferences/settings/
                     config surface, with its fixed icon ri-compasses-2-line. */ ?>
            <?php /* MEMBERS ONLY: there is nothing for a guest to adjust and /ajustes
                     itself requires a session, so showing the row to visitors only
                     offers a dead end. */ ?>
            <?php if ($apl_logged) : ?>
            <div class="adj" id="aside-prefs">
                <a class="adjl" href="<?php echo esc_url(home_url('/ajustes')); ?>" style="line-height:.7">
                    <i class="ri-compasses-2-line" aria-hidden="true"></i>
                    <span class="sn"><?php esc_html_e('Ajustes', 'apollo-templates'); ?></span>
                </a>
                <button type="button" class="pill" id="aside-pill"
                    aria-label="<?php esc_attr_e('Alternar modo escuro', 'apollo-templates'); ?>" aria-pressed="false">
                    <div class="th"></div>
                </button>
            </div>
            <?php endif; ?>

            <?php if ($apl_logged) : ?>
                <?php
                $apl_initials = '';
                foreach (array_slice(preg_split('/\s+/', trim((string) $apl_user->display_name)) ?: array(), 0, 2) as $apl_p) {
                    $apl_initials .= mb_strtoupper(mb_substr((string) $apl_p, 0, 1));
                }
                if ('' === $apl_initials) {
                    $apl_initials = mb_strtoupper(mb_substr((string) $apl_user->user_login, 0, 2));
                }
                $apl_role = function_exists('apollo_plus_user_role_label')
                    ? apollo_plus_user_role_label($apl_user)
                    : '';
                ?>
                <a class="urow" href="<?php echo esc_url(home_url('/id/' . $apl_user->user_login)); ?>">
                    <div class="avw">
                        <div class="av"><?php echo esc_html($apl_initials); ?><img
                            src="<?php echo esc_url(get_avatar_url($apl_user->ID, array('size' => 90))); ?>" alt=""
                            onload="this.style.opacity=1" style="opacity:0"></div>
                        <div class="aon"></div>
                    </div>
                    <div class="ui">
                        <div class="un"><?php echo esc_html($apl_user->display_name); ?></div>
                        <div class="uro"><?php echo esc_html($apl_role); ?></div>
                    </div>
                    <div class="um" id="aside-user-btn"><i class="ri-more-2-line" aria-hidden="true"></i></div>
                </a>
            <?php else : ?>
                <a class="urow" href="<?php echo esc_url(home_url('/acesso')); ?>">
                    <div class="avw"><div class="av"><i class="ri-user-line" aria-hidden="true"></i></div></div>
                    <div class="ui">
                        <div class="un"><?php esc_html_e('Entrar', 'apollo-templates'); ?></div>
                        <div class="uro"><?php esc_html_e('Acesse sua conta', 'apollo-templates'); ?></div>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</aside>

<script>
    (function () {
        'use strict';
        var aside = document.getElementById('ax-aside');
        if (!aside) return;

        /* Drawer: #burger is rendered by app-shell.php (the topbar), so bind
           defensively — the aside must still work if the topbar is absent. */
        var burger = document.getElementById('burger');
        var overlay = document.querySelector('.ax-overlay');
        function open() { aside.classList.add('open'); if (overlay) overlay.classList.add('on'); }
        function close() { aside.classList.remove('open'); if (overlay) overlay.classList.remove('on'); }

        if (burger) burger.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); open(); });
        var closeBtn = aside.querySelector('.ax-aside-close');
        if (closeBtn) closeBtn.addEventListener('click', close);
        if (overlay) overlay.addEventListener('click', close);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && aside.classList.contains('open')) close();
        });

        /* Collapsible groups — height animated from the measured content so a
           group can grow without hard-coding a max-height guess. */
        aside.querySelectorAll('.sh[data-col]').forEach(function (head) {
            var col = document.getElementById(head.getAttribute('data-col'));
            if (!col) return;
            head.addEventListener('click', function () {
                var shut = col.classList.toggle('shut');
                head.classList.toggle('shut', shut);
                col.style.height = shut ? '0px' : (col.firstElementChild.offsetHeight + 'px');
                if (!shut) setTimeout(function () { col.style.height = 'auto'; }, 360);
            });
        });
    })();
</script>
