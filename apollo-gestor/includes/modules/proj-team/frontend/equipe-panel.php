<?php
/**
 * Panel: Equipe (Proj_Team)
 *
 * Staff grid with role badges. Populated by gestor.data.js + AJAX.
 * Roles: adm, gestor, tgestor, team
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section class="panel" id="panel-equipe">
    <div class="section-hdr">
        <span class="section-title"><?php esc_html_e( 'Equipe do Projeto', 'apollo-gestor' ); ?></span>
        <div class="restricted-bar"><i class="ri-eye-line"></i> <?php esc_html_e( 'Visível para: ADM, Gestor, T.Gestor', 'apollo-gestor' ); ?></div>
    </div>
    <div class="staff-grid" id="staffGrid">
        <!-- JS: gestor.data.js renders .staff-card elements here via AJAX -->
        <!--
        Template per card (rendered by JS):
        <div class="staff-card">
            <div class="staff-avatar"><img src="" alt=""></div>
            <div class="staff-name"></div>
            <div class="staff-role"></div>
            <span class="staff-badge {role}">{Role}</span>
            <div class="staff-stats">
                <div class="staff-stat"><span class="staff-stat-num">0</span><span class="staff-stat-lbl">Tarefas</span></div>
                <div class="staff-stat"><span class="staff-stat-num">0</span><span class="staff-stat-lbl">Eventos</span></div>
            </div>
        </div>
        -->
    </div>

    <!-- Add member bar (visible for adm/gestor) -->
    <div class="team-add-bar" id="teamAddBar" style="display:none;margin-top:16px">
        <div class="section-hdr"><span class="section-title"><?php esc_html_e( 'Adicionar Membro', 'apollo-gestor' ); ?></span></div>
        <div style="display:flex;gap:8px;align-items:center">
            <input class="apollo-input" type="text" id="teamSearchInput" placeholder="<?php esc_attr_e( 'Buscar por nome ou email…', 'apollo-gestor' ); ?>" autocomplete="off" style="flex:1">
            <select class="apollo-input" id="teamRoleSelect" style="width:120px">
                <option value="team"><?php esc_html_e( 'Team', 'apollo-gestor' ); ?></option>
                <option value="tgestor"><?php esc_html_e( 'T.Gestor', 'apollo-gestor' ); ?></option>
                <option value="gestor"><?php esc_html_e( 'Gestor', 'apollo-gestor' ); ?></option>
                <option value="adm"><?php esc_html_e( 'ADM', 'apollo-gestor' ); ?></option>
            </select>
            <button class="btn btn-primary" id="teamAddBtn"><i class="ri-add-line"></i> <?php esc_html_e( 'Adicionar', 'apollo-gestor' ); ?></button>
        </div>
        <div id="teamSearchResults" style="display:none"></div>
    </div>
</section>