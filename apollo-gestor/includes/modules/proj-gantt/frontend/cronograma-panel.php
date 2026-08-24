<?php
/**
 * Panel: Cronograma (Proj_Gantt)
 *
 * Gantt chart (amCharts 5), milestones, phase progress bars.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section class="panel" id="panel-cronograma">
    <div class="section-hdr">
        <span class="section-title"><?php esc_html_e( 'Cronograma', 'apollo-gestor' ); ?></span>
    </div>

    <!-- Toolbar -->
    <div class="gantt-toolbar">
        <button class="btn-sm" id="ganttToday"><i class="ri-focus-3-line"></i> <?php esc_html_e( 'Hoje', 'apollo-gestor' ); ?></button>
        <button class="btn-sm primary" id="ganttNewTask"><i class="ri-add-line"></i> <?php esc_html_e( 'Nova Tarefa', 'apollo-gestor' ); ?></button>
        <button class="btn-sm" id="ganttSave"><i class="ri-save-line"></i> <?php esc_html_e( 'Salvar', 'apollo-gestor' ); ?></button>
    </div>

    <!-- amCharts Gantt Container -->
    <div id="gantt-chart"></div>

    <!-- Milestones -->
    <div class="section-hdr" style="margin-top:24px"><span class="section-title"><?php esc_html_e( 'Marcos do Evento', 'apollo-gestor' ); ?></span></div>
    <div class="milestones" id="milestonesList">
        <!-- JS: gestor.gantt.js renders .milestone elements -->
        <!--
        Template:
        <div class="milestone {done}">
            <div class="check"><i class="ri-check-line"></i></div> Title
        </div>
        -->
    </div>

    <!-- Add Milestone -->
    <div style="display:flex;gap:8px;margin-top:12px;align-items:center" id="milestoneAddBar">
        <input class="apollo-input" type="text" id="milestoneTitle" placeholder="<?php esc_attr_e( 'Novo marco…', 'apollo-gestor' ); ?>" style="flex:1">
        <input class="apollo-input" type="date" id="milestoneDue" style="width:140px" data-apollo-date-picker="true">
        <button class="btn btn-primary" id="milestoneAddBtn"><i class="ri-add-line"></i></button>
    </div>

    <!-- Phase Progress -->
    <div class="section-hdr" style="margin-top:24px"><span class="section-title"><?php esc_html_e( 'Progresso por Fase', 'apollo-gestor' ); ?></span></div>
    <div id="phaseProgressContainer">
        <!-- JS: gestor.gantt.js renders .phase-progress-section elements -->
        <!--
        Template:
        <div class="phase-progress-section">
            <div class="phase-title">Event Name</div>
            <div class="phase-bars">
                <div class="phase-bar-row">
                    <span class="phase-bar-label">Phase</span>
                    <div class="phase-bar"><div class="phase-bar-fill" style="width:X%;background:var(--s-status)"></div></div>
                    <span class="phase-bar-pct">X%</span>
                </div>
            </div>
        </div>
        -->
    </div>
</section>