<?php

/**
 * New Home — Menu FAB + Sheet (9 Apps)
 *
 * Fixed bottom-right circle button. Opens navigation sheet upward.
 * Auth-aware: shows 5 public apps for guests, 9 apps for logged users.
 *
 * Navigation rules:
 * - App links navigate to full pages (not panels)
 * - "Criar" actions → data-to="acesso" data-dir="down" (forms slide DOWN)
 * - Dashboard link for logged users
 * - Login CTA for guests
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json → persistent_ui
 * @see     _inventory/icon.apollo.md
 */

if (! defined('ABSPATH')) {
    exit;
}

$is_logged_in = is_user_logged_in();
?>

<!-- Menu App FAB — bottom-right, always visible -->
<button class="nh-menu-fab"
    id="nhMenuFab"
    aria-label="Menu Apollo"
    aria-expanded="false"
    aria-haspopup="true">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="20" height="20" style="display:block;flex-shrink:0;pointer-events:none">
        <path d="M21 17C21 19.2091 19.2091 21 17 21C14.7909 21 13 19.2091 13 17C13 14.7909 14.7909 13 17 13C19.2091 13 21 14.7909 21 17ZM11 7C11 9.20914 9.20914 11 7 11C4.79086 11 3 9.20914 3 7C3 4.79086 4.79086 3 7 3C9.20914 3 11 4.79086 11 7ZM21 7C21 9.20914 19.2091 11 17 11C16.2584 11 15.5634 10.7972 14.9678 10.4453L10.4453 14.9678C10.7972 15.5634 11 16.2584 11 17C11 19.2091 9.20914 21 7 21C4.79086 21 3 19.2091 3 17C3 14.7909 4.79086 13 7 13C7.74116 13 8.43593 13.2022 9.03125 13.5537L13.5537 9.03125C13.2022 8.43593 13 7.74116 13 7C13 4.79086 14.7909 3 17 3C19.2091 3 21 4.79086 21 7Z" />
    </svg>
</button>

<!-- Upward sheet — slides from bottom to top on FAB click -->
<div class="nh-menu-sheet" id="nhMenuSheet" role="menu" aria-label="<?php esc_attr_e('Menu de navegação', 'apollo-templates'); ?>">

    <!-- ── Public Apps (always visible) ── -->
    <a href="<?php echo esc_url(home_url('/portal')); ?>" class="nh-sheet-item" role="menuitem">
        <i class="ri-calendar-line"></i><?php esc_html_e('Eventos', 'apollo-templates'); ?>
    </a>
    <a href="<?php echo esc_url(home_url('/djs')); ?>" class="nh-sheet-item" role="menuitem">
        <i class="ri-sound-module-line"></i><?php esc_html_e('DJs & Artistas', 'apollo-templates'); ?>
    </a>
    <a href="<?php echo esc_url(home_url('/mapa')); ?>" class="nh-sheet-item" role="menuitem">
        <i class="ri-map-pin-line"></i><?php esc_html_e('Mapa', 'apollo-templates'); ?>
    </a>
    <a href="<?php echo esc_url(home_url('/classificados')); ?>" class="nh-sheet-item" role="menuitem">
        <i class="ri-exchange-box-line"></i><?php esc_html_e('Classificados', 'apollo-templates'); ?>
    </a>
    <a href="https://plano.apollo.rio.br/" class="nh-sheet-item" role="menuitem" target="_blank" rel="noopener noreferrer">
        <i class="ri-home-heart-line"></i>Plano, creative studio
    </a>

    <?php if ($is_logged_in) : ?>

        <div class="nh-sheet-divider" aria-hidden="true"></div>

        <!-- ── Logged-only Apps ── -->
        <a href="<?php echo esc_url(home_url('/documentos')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-book-read-line"></i><?php esc_html_e('Documentos', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/mensagens')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-message-3-line"></i><?php esc_html_e('Mensagens', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/apollo-gestor')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-dashboard-horizontal-line"></i><?php esc_html_e('Gestor', 'apollo-templates'); ?>
        </a>
        <a href="<?php echo esc_url(home_url('/notificacoes')); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-notification-3-line"></i><?php esc_html_e('Notificações', 'apollo-templates'); ?>
        </a>

        <div class="nh-sheet-divider" aria-hidden="true"></div>

        <!-- ── Create Actions (forms → DOWN direction) ── -->
        <button class="nh-sheet-item nh-sheet-item--action"
            data-to="acesso" data-dir="down" data-form="create-event" role="menuitem">
            <i class="ri-calendar-line"></i><?php esc_html_e('Criar Evento', 'apollo-templates'); ?>
        </button>
        <button class="nh-sheet-item nh-sheet-item--action"
            data-to="acesso" data-dir="down" data-form="create-ad" role="menuitem">
            <i class="ri-exchange-box-line"></i><?php esc_html_e('Criar Anúncio', 'apollo-templates'); ?>
        </button>
        <button class="nh-sheet-item nh-sheet-item--action"
            data-to="acesso" data-dir="down" data-form="create-group" role="menuitem">
            <i class="ri-user-community-fill"></i><?php esc_html_e('Criar Grupo', 'apollo-templates'); ?>
        </button>

        <div class="nh-sheet-divider" aria-hidden="true"></div>

        <a href="<?php echo esc_url(home_url('/painel')); ?>" class="nh-sheet-item nh-sheet-item--cta" role="menuitem">
            <i class="ri-server-line"></i>Dashboard
        </a>

    <?php else : ?>

        <div class="nh-sheet-divider" aria-hidden="true"></div>

        <!-- Guest: Login CTA (link direto para /acesso) -->
        <a href="<?php echo esc_url(home_url('/acesso')); ?>" class="nh-sheet-item nh-sheet-item--cta" role="menuitem">
            <i class="ri-login-circle-line"></i><?php esc_html_e('Entrar / Cadastrar', 'apollo-templates'); ?>
        </a>

    <?php endif; ?>
</div>