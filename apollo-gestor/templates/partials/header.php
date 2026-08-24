<?php
/**
 * Partial: Header bar
 *
 * Contains logo, AS3 glass event selector, search (⌘K), action buttons, avatar.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<header class="hd">
    <div class="hd-logo"><span class="gl">Projeto</span><span class="ga">apollo</span></div>

    <!-- AS3 Glass Event Selector -->
    <div class="hd-evento-wrap as3">
        <button class="as3-trigger" data-value="overview" type="button">
            <span class="as3-lbl-name">Overview Projetos</span>
            <i class="ri-arrow-down-s-line as3-chevron"></i>
        </button>
        <div class="as3-drop">
            <div class="as3-search-wrap">
                <i class="ri-search-line"></i>
                <input type="text" class="apollo-input as3-search" placeholder="Buscar projeto…">
            </div>
            <div class="as3-opts" id="as3Opts">
                <div class="as3-opt is-selected" data-value="overview" data-name="Overview Projetos" data-desc="Todos os projetos">
                    <span class="as3-swatch" style="background:var(--white-6)"></span>
                    <span class="as3-lbl-name">Overview Projetos</span>
                </div>
                <!-- JS populates event options from AJAX data -->
            </div>
            <div class="as3-empty" style="display:none">Nenhum projeto encontrado</div>
        </div>
    </div>

    <!-- Search bar + ⌘K shortcut -->
    <div class="hd-search">
        <i class="ri-search-line"></i>
        <input type="text" placeholder="Buscar…" id="gestorSearch" autocomplete="off" class="apollo-input">
        <span class="sc">⌘K</span>
    </div>

    <!-- Header actions -->
    <div class="hd-actions">
        <button class="hd-act" id="btnNotif" type="button" data-tooltip="Notificações">
            <i class="ri-notification-3-line"></i>
            <span class="dot"></span>
        </button>
        <button class="hd-act primary-act" id="btnNewProject" type="button" data-tooltip="Novo Projeto">
            <i class="ri-add-line"></i>
        </button>
        <div class="hd-avatar" data-tooltip="<?php echo esc_attr( $current_user->display_name ); ?>">
            <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $current_user->display_name ); ?>" loading="lazy">
        </div>
    </div>

    <!-- Notification panel (toggled by bell) -->
    <div class="notif-panel" id="notifPanel">
        <div class="notif-header">
            <span>Notificações</span>
            <button class="btn-ghost" id="notifMarkAll" type="button">Marcar todas</button>
        </div>
        <div class="notif-list" id="notifList">
            <!-- JS populates -->
        </div>
    </div>

    <!-- Hero background text -->
    <div class="hero-bg-text" aria-hidden="true">PROJETO</div>
</header>