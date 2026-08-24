<?php
/**
 * Partial: Tab navigation
 *
 * Role-based visibility:
 *   Level >= 5 (admin/editor/author) → all tabs
 *   Level 1 (team)                   → Kanban + Mural only
 *
 * Tab labels hidden on mobile, icons always visible.
 * Sensitive tabs render `.icon-user-access` via the icon-user-access partial
 * (replaces the old `.lock` span pattern).
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Apollo\Gestor\Admin\RoleAccess;

// Load the icon-user-access helper function (idempotent — has function_exists guard).
require_once __DIR__ . '/icon-user-access.php';

$access_level = $access_level ?? RoleAccess::LEVEL_FULL;
$allowed_tabs = RoleAccess::allowed_tabs( $access_level );

/**
 * Helper: render tab only if allowed.
 *
 * @param string $panel_id     Panel CSS id (e.g. 'panel-overview')
 * @param string $icon         RemixIcon class
 * @param string $label        Tab label text
 * @param array  $allowed      Allowed panel IDs
 * @param bool   $active       Is this the default active tab?
 * @param string $access_icon  RemixIcon for the visibility badge ('' = none).
 *                             'ri-eye-line'     → content visible to specific members
 *                             'ri-eye-off-line' → content restricted/hidden from team
 * @param string $access_state State passed to gestor_icon_user_access().
 */
$render_tab = function (
    string $panel_id,
    string $icon,
    string $label,
    array  $allowed,
    bool   $active       = false,
    string $access_icon  = '',
    string $access_state = 'visible'
): void {
    if ( ! in_array( $panel_id, $allowed, true ) ) {
        return;
    }
    $on   = $active ? ' on' : '';
    $aria = $active ? 'true' : 'false';

    // Capture icon-user-access badge output.
    $badge = '';
    if ( $access_icon ) {
        ob_start();
        gestor_icon_user_access( $access_icon, '', $access_state );
        $badge = ob_get_clean();
    }

    printf(
        '<button class="tab%s" data-tab="%s" type="button" role="tab" aria-selected="%s"><i class="%s"></i><span class="tab-label">%s</span>%s</button>',
        esc_attr( $on ),
        esc_attr( $panel_id ),
        esc_attr( $aria ),
        esc_attr( $icon ),
        esc_html( $label ),
        $badge // Output from gestor_icon_user_access() — already escaped inside.
    );
};

// Determine default active tab based on access level
$default_tab = $access_level >= RoleAccess::LEVEL_FULL ? 'panel-overview' : 'panel-kanban';
?>
<nav class="tabs" role="tablist">
    <?php
    $render_tab( 'panel-overview',        'ri-dashboard-horizontal-fill', 'Overview',     $allowed_tabs, $default_tab === 'panel-overview' );
    $render_tab( 'panel-kanban',          'ri-kanban-view-2',            'Tarefas',        $allowed_tabs, $default_tab === 'panel-kanban' );
    $render_tab( 'panel-equipe',          'ri-team-line',                'Equipe',        $allowed_tabs );
    $render_tab( 'panel-budget',          'ri-money-dollar-box-line',    'Budget',        $allowed_tabs, false, 'ri-eye-off-line', 'restricted' );
    $render_tab( 'panel-financeiro',      'ri-bank-card-line',           'Financeiro',    $allowed_tabs, false, 'ri-eye-line',     'visible' );
    $render_tab( 'panel-ctrl-financeiro', 'ri-pie-chart-2-line',         'Controle Fin.', $allowed_tabs, false, 'ri-eye-off-line', 'restricted' );
    $render_tab( 'panel-fornecedores',    'ri-store-2-line',             'Fornecedores',  $allowed_tabs );
    $render_tab( 'panel-cronograma',      'ri-bar-chart-grouped-line',   'Cronograma',    $allowed_tabs );
    $render_tab( 'panel-doc',             'ri-book-read-fill',           'DOC',           $allowed_tabs );
    $render_tab( 'panel-assina',          'ri-shield-keyhole-fill',      'Assina::rio',   $allowed_tabs );
    $render_tab( 'panel-mural',           'ri-chat-quote-line',          'Mural',         $allowed_tabs );
    ?>
</nav>
