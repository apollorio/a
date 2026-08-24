<?php
/**
 * Partial: Slide-over drawer
 *
 * Right-side event detail panel opened from Kanban double-click.
 * Contains: event meta, checklist, add task input.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- Slide-over backdrop -->
<div class="slide-over-backdrop" id="soBackdrop"></div>

<!-- Slide-over panel -->
<div class="slide-over" id="so">
    <div class="so-header">
        <button class="so-close" id="soClose" type="button"><i class="ri-arrow-right-s-line"></i></button>
        <span class="so-header-id" id="soId"></span>
        <div class="so-header-actions">
            <button class="btn-icon" type="button" data-tooltip="Editar"><i class="ri-edit-line"></i></button>
            <button class="btn-icon" type="button" data-tooltip="Compartilhar"><i class="ri-share-line"></i></button>
            <button class="btn-icon" type="button" data-tooltip="Mais"><i class="ri-more-2-fill"></i></button>
        </div>
    </div>

    <div class="so-body">
        <h2 class="so-title" id="soTitle"></h2>
        <div class="so-loc" id="soLoc"><i class="ri-map-pin-line"></i> <span></span></div>

        <!-- Event meta grid -->
        <div class="ev-meta-grid" id="soMeta">
            <div class="ev-meta-item">
                <div class="ev-meta-label">Status</div>
                <div class="ev-meta-value" id="soStatus"></div>
            </div>
            <div class="ev-meta-item">
                <div class="ev-meta-label">Data</div>
                <div class="ev-meta-value" id="soDate"></div>
            </div>
            <div class="ev-meta-item">
                <div class="ev-meta-label">Coordenador</div>
                <div class="ev-meta-value" id="soGestor"></div>
            </div>
            <div class="ev-meta-item">
                <div class="ev-meta-label">Equipe</div>
                <div class="ev-meta-value" id="soTeamCount"></div>
            </div>
            <div class="ev-meta-item">
                <div class="ev-meta-label"><i class="ri-eye-line" style="font-size:9px"></i> Budget</div>
                <div class="ev-meta-value" id="soBudget"></div>
            </div>
        </div>

        <!-- Checklist section -->
        <div class="section-hdr">
            <span class="section-title">Checklist</span>
            <span id="soChecklistProgress"></span>
        </div>

        <div class="ev-progress-bar" id="soProgressBar">
            <div class="ev-progress-bar-fill" id="soProgressFill" style="width:0%"></div>
        </div>

        <div id="soChecklist">
            <!-- JS populates task checklist items -->
        </div>
    </div>

    <div class="so-footer">
        <div class="so-add-task">
            <input type="text" class="apollo-input" id="soAddTaskInput" placeholder="Nova tarefa…" autocomplete="off">
            <button class="btn btn-primary" id="soAddTaskBtn" type="button">
                <i class="ri-add-line"></i>
            </button>
        </div>
    </div>
</div>