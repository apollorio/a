<?php
/**
 * Partial: FAB Menu — Mobile-first, ALL navigation lives here.
 * No navbar. FAB + upward sheet with grouped navigation items.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_logged_in = is_user_logged_in();
$current_user = $is_logged_in ? wp_get_current_user() : null;
?>
<button class="nh-menu-fab"
        id="nhMenuFab"
        aria-label="Menu Apollo"
        aria-expanded="false"
        aria-haspopup="true">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="20" height="20"><path d="M21 17C21 19.2091 19.2091 21 17 21C14.7909 21 13 19.2091 13 17C13 14.7909 14.7909 13 17 13C19.2091 13 21 14.7909 21 17ZM11 7C11 9.20914 9.20914 11 7 11C4.79086 11 3 9.20914 3 7C3 4.79086 4.79086 3 7 3C9.20914 3 11 4.79086 11 7ZM21 7C21 9.20914 19.2091 11 17 11C16.2584 11 15.5634 10.7972 14.9678 10.4453L10.4453 14.9678C10.7972 15.5634 11 16.2584 11 17C11 19.2091 9.20914 21 7 21C4.79086 21 3 19.2091 3 17C3 14.7909 4.79086 13 7 13C7.74116 13 8.43593 13.2022 9.03125 13.5537L13.5537 9.03125C13.2022 8.43593 13 7.74116 13 7C13 4.79086 14.7909 3 17 3C19.2091 3 21 4.79086 21 7Z"/></svg>
</button>

<div class="nh-menu-sheet" id="nhMenuSheet" role="menu" aria-label="Menu de navegação">
    <span class="nh-sheet-label">Você</span>
    <?php if ( $is_logged_in ) : ?>
        <a href="/id/<?php echo esc_attr( $current_user->user_login ); ?>" class="nh-sheet-item" role="menuitem"><i class="ri-user-smile-line"></i>Meu Perfil</a>
        <a href="/chat" class="nh-sheet-item" role="menuitem"><i class="ri-message-3-line"></i>Chat</a>
        <a href="/notificacoes" class="nh-sheet-item" role="menuitem"><i class="ri-notification-3-line"></i>Notificações</a>
        <a href="/dashboard" class="nh-sheet-item" role="menuitem"><i class="ri-dashboard-line"></i>Dashboard</a>
    <?php else : ?>
        <a href="/acesso" class="nh-sheet-item nh-sheet-item--cta" role="menuitem">
            <i class="ri-login-circle-line"></i>Entrar / Cadastrar
        </a>
    <?php endif; ?>

    <div class="nh-sheet-divider" aria-hidden="true"></div>
    <span class="nh-sheet-label">Apollo</span>
    <a href="/eventos" class="nh-sheet-item" role="menuitem"><i class="ri-calendar-event-line"></i>Eventos</a>
    <a href="/djs" class="nh-sheet-item" role="menuitem"><i class="ri-music-2-line"></i>DJs &amp; Artistas</a>
    <a href="/criativo" class="nh-sheet-item" role="menuitem"><i class="ri-map-pin-2-line"></i>Espaços</a>
    <a href="/classificados" class="nh-sheet-item" role="menuitem"><i class="ri-exchange-box-line"></i>Classificados</a>
    <a href="/acomoda" class="nh-sheet-item" role="menuitem"><i class="ri-home-heart-line"></i>Acomoda::Rio</a>

    <?php if ( $is_logged_in ) : ?>
        <div class="nh-sheet-divider" aria-hidden="true"></div>
        <a href="<?php echo esc_url( wp_logout_url( home_url( '/home' ) ) ); ?>" class="nh-sheet-item" role="menuitem">
            <i class="ri-logout-box-r-line"></i>Sair
        </a>
    <?php endif; ?>
</div>
