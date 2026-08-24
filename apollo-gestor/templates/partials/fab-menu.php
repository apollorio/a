<?php
/**
 * Partial: FAB (Floating Action Button) menu
 *
 * Mobile bottom-right action sheet with project + Apollo nav links.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- FAB trigger -->
<button class="nh-menu-fab" id="nhMenuFab" type="button" aria-label="Menu">
    <i class="ri-apps-line"></i>
</button>

<!-- FAB sheet (mobile action menu) -->
<div class="nh-menu-sheet" id="nhMenuSheet">
    <div class="nh-sheet-label">Projeto</div>
    <button class="nh-sheet-item" type="button" onclick="window.openTab && openTab('panel-overview')">
        <i class="ri-dashboard-horizontal-fill"></i> Overview
    </button>
    <button class="nh-sheet-item" type="button" onclick="window.openTab && openTab('panel-kanban')">
        <i class="ri-kanban-view-2"></i> Kanban
    </button>
    <button class="nh-sheet-item" type="button" onclick="window.openTab && openTab('panel-equipe')">
        <i class="ri-team-line"></i> Equipe
    </button>
    <button class="nh-sheet-item" type="button" onclick="window.openTab && openTab('panel-budget')">
        <i class="ri-money-dollar-box-line"></i> Budget
    </button>
    <button class="nh-sheet-item" type="button" onclick="window.openTab && openTab('panel-cronograma')">
        <i class="ri-bar-chart-grouped-line"></i> Cronograma
    </button>
    <button class="nh-sheet-item" type="button" onclick="window.openTab && openTab('panel-doc')">
        <i class="ri-book-read-fill"></i> DOC
    </button>
    <button class="nh-sheet-item" type="button" onclick="window.openTab && openTab('panel-assina')">
        <i class="ri-shield-keyhole-fill"></i> Assina::rio
    </button>

    <div class="nh-sheet-divider"></div>
    <div class="nh-sheet-label">Apollo</div>
    <a class="nh-sheet-item" href="<?php echo esc_url( admin_url() ); ?>">
        <i class="ri-dashboard-3-line"></i> Admin
    </a>
    <a class="nh-sheet-item" href="<?php echo esc_url( home_url( '/' ) ); ?>">
        <i class="ri-home-smile-line"></i> Site
    </a>
    <a class="nh-sheet-item nh-sheet-item--cta" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
        <i class="ri-logout-box-r-line"></i> Sair
    </a>
</div>
