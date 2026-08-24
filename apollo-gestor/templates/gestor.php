<?php
/**
 * Gestor de Projetos — Main template shell (modular)
 *
 * Loads all partials from templates/partials/ and module frontend panels.
 * CDN: Apollo core.js + uni.css + amCharts 5
 *
 * Role-based access:
 *   Level >= 5 (admin/editor/author) → full edit, all panels
 *   Level  1 (team, invited)         → limited: Kanban (create task) + Mural (post)
 *
 * Backend→Frontend naming contract:
 *   proj → projeto/projetos | task → tarefa/tarefas
 *   proj-finance → Financeiro | proj-team → Equipe
 *   proj-gantt → Cronograma | proj-board → Mural
 *   proj-equip → Equipamentos | proj-staff → Staff
 *
 * Status taxonomy (6-col Kanban):
 *   planned (#6366f1) | tostart (#0ea5e9) | ongoing (#d97706)
 *   delayed (#dc2626) | canceled (#94a3b8) | delivered (#16a34a)
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Apollo\Gestor\Admin\RoleAccess;

$current_user = wp_get_current_user();
$is_admin     = current_user_can( 'manage_options' );
$avatar_url   = get_avatar_url( $current_user->ID, [ 'size' => 64 ] );
$cdn_url      = defined( 'APOLLO_CDN_URL' ) ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';
$assets_url   = 'https://assets.apollo.rio.br/i/';

// Status taxonomy
$status_map = [
    'planned'   => [ 'label' => 'Planejado',    'color' => '#6366f1' ],
    'tostart'   => [ 'label' => 'A Iniciar',    'color' => '#0ea5e9' ],
    'ongoing'   => [ 'label' => 'Em Andamento', 'color' => '#d97706' ],
    'delayed'   => [ 'label' => 'Atrasado',     'color' => '#dc2626' ],
    'canceled'  => [ 'label' => 'Cancelado',    'color' => '#94a3b8' ],
    'delivered' => [ 'label' => 'Entregue',     'color' => '#16a34a' ],
];

// Module manager reference (passed from Controller::render_page)
/** @var \Apollo\Gestor\Core\Module_Manager $modules */
/** @var int $access_level  from Controller::render_page */
/** @var array $access_ctx  from Controller::render_page */
$access_level = $access_level ?? RoleAccess::LEVEL_FULL;
$access_ctx   = $access_ctx   ?? RoleAccess::resolve();
$can_edit     = $access_level >= RoleAccess::LEVEL_FULL;
$partials_dir = APOLLO_GESTOR_DIR . 'templates/partials/';
?>
<!-- VIEWPORT (mobile-first mandatory) -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

<!-- APOLLO CDN CORE -->
<script src="<?php echo esc_url( function_exists( 'apollo_cdn_core_js_url' ) ? apollo_cdn_core_js_url() : $cdn_url . 'core.js?v=t0x1x' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>

<!-- DARK-MODE BLOCKER (force light-only for Gestor) -->
<script>(function(){document.documentElement.classList.remove('dark-mode');document.documentElement.removeAttribute('data-theme');try{localStorage.removeItem('theme');}catch(e){}})();</script>

<!-- UNI.CSS (Apollo universal CSS) -->
<link rel="stylesheet" href="<?php echo esc_url( $cdn_url . 'css/uni.css?x=1.0.1' ); ?>">

<!-- THEME + FORMS (Apollo CDN JS) -->
<script src="<?php echo esc_url( $cdn_url . 'js/theme.1.js' ); ?>" fetchpriority="high"></script>
<script src="<?php echo esc_url( $cdn_url . 'js/forms.js' ); ?>" fetchpriority="high"></script>

<!-- amCharts 5 -->
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

<div class="gestor-wrap" id="gestorApp">

    <?php // ═══ LOADER ═══
    include $partials_dir . 'loader.php'; ?>

    <?php // ═══ HEADER ═══
    include $partials_dir . 'header.php'; ?>

    <?php // ═══ TAB NAV ═══
    include $partials_dir . 'tabs.php'; ?>

    <!-- ═══ MAIN PANELS ═══ -->
    <div class="main">

        <?php if ( $can_edit ) : // ═══ FULL-ACCESS PANELS (admin / editor / author) ═══ ?>

            <?php // Panel: Overview (projetos module)
            if ( isset( $modules ) && $modules->has( 'projetos' ) ) {
                $modules->render_partial( 'projetos', 'projetos-panel', compact( 'is_admin', 'assets_url', 'status_map' ) );
            } ?>

        <?php endif; ?>

        <?php // Panel: Kanban (tarefas module) — visible to ALL levels (team can create tasks)
        if ( isset( $modules ) && $modules->has( 'tarefas' ) ) {
            $modules->render_partial( 'tarefas', 'kanban-panel', compact( 'status_map', 'can_edit' ) );
        } ?>

        <?php if ( $can_edit ) : ?>

            <?php // Panel: Equipe (proj-team module)
            if ( isset( $modules ) && $modules->has( 'proj-team' ) ) {
                $modules->render_partial( 'proj-team', 'equipe-panel', compact( 'is_admin' ) );
            } ?>

            <?php // Panel: Budget (proj-finance module — budget)
            if ( isset( $modules ) && $modules->has( 'proj-finance' ) ) {
                $modules->render_partial( 'proj-finance', 'budget-panel', compact( 'is_admin' ) );
            } ?>

            <?php // Panel: Financeiro (proj-finance module — financeiro)
            if ( isset( $modules ) && $modules->has( 'proj-finance' ) ) {
                $modules->render_partial( 'proj-finance', 'financeiro-panel', compact( 'is_admin' ) );
            } ?>

            <?php // Panel: Controle Financeiro Unificado (proj-finance module)
            if ( isset( $modules ) && $modules->has( 'proj-finance' ) ) {
                $modules->render_partial( 'proj-finance', 'ctrl-financeiro-panel', compact( 'is_admin' ) );
            } ?>

            <?php // Panel: Fornecedores (proj-staff module)
            if ( isset( $modules ) && $modules->has( 'proj-staff' ) ) {
                $modules->render_partial( 'proj-staff', 'fornecedores-panel' );
            } ?>

            <?php // Panel: Cronograma (proj-gantt module)
            if ( isset( $modules ) && $modules->has( 'proj-gantt' ) ) {
                $modules->render_partial( 'proj-gantt', 'cronograma-panel' );
            } ?>

            <!-- Panel: DOC (text editor) -->
            <div class="panel" id="panel-doc">
                <?php
                if ( isset( $modules ) && $modules->has( 'editor' ) ) {
                    $modules->render_partial( 'editor', 'editor-panel' );
                } else { ?>
                    <div class="panel-empty">
                        <i class="ri-book-read-fill" style="font-size:48px;color:var(--ghost);"></i>
                        <p>Editor de documentos</p>
                    </div>
                <?php } ?>
            </div>

            <!-- Panel: Assina::rio (signing flow) -->
            <div class="panel" id="panel-assina">
                <div class="panel-empty" id="assina-placeholder">
                    <i class="ri-shield-keyhole-fill" style="font-size:48px;color:var(--ghost);"></i>
                    <p>Selecione um documento para assinar</p>
                </div>
            </div>

        <?php endif; ?>

        <?php // Panel: Mural — visible to ALL levels (team can post)
        if ( isset( $modules ) && $modules->has( 'proj-board' ) ) {
            $modules->render_partial( 'proj-board', 'mural-panel' );
        } ?>

    </div><!-- end .main -->

    <?php // ═══ OVERLAYS & MODALS ═══
    include $partials_dir . 'slideover.php';
    if ( $can_edit ) :
        include $partials_dir . 'calculator.php';
        include $partials_dir . 'modals.php';
        include $partials_dir . 'modal-new-event.php';
        include $partials_dir . 'cmd-palette.php';
    endif;
    include $partials_dir . 'fab-menu.php'; ?>

    <!-- TOAST CONTAINER -->
    <div id="toast-container"></div>

</div><!-- end .gestor-wrap -->
