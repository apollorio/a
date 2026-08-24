<?php
/**
 * Partial: New Event (Projeto) modal
 *
 * Form: nome, data, local, gênero, budget, descrição
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="modal-overlay" id="newEventModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="ri-add-circle-line"></i> Novo Projeto</h3>
            <button class="btn-icon modal-close" type="button"><i class="ri-close-fill"></i></button>
        </div>
        <div class="modal-body">
            <div class="input-group">
                <label for="newEvtName">Nome do projeto</label>
                <input type="text" class="apollo-input" id="newEvtName" placeholder="Ex: Festa Rara" autocomplete="off" required>
            </div>
            <div class="input-group">
                <label for="newEvtDate">Data</label>
                <input type="date" class="apollo-input" id="newEvtDate" required data-apollo-date-picker="true">
            </div>
            <div class="input-group">
                <label for="newEvtLoc">Local</label>
                <input type="text" class="apollo-input" id="newEvtLoc" placeholder="Ex: Galpão 44, Botafogo" autocomplete="off">
            </div>
            <div class="input-group">
                <label for="newEvtGenre">Gênero</label>
                <input type="text" class="apollo-input" id="newEvtGenre" placeholder="Ex: Techno, House" autocomplete="off">
            </div>
            <div class="input-group">
                <label for="newEvtBudget">Budget (R$)</label>
                <input type="number" class="apollo-input" id="newEvtBudget" placeholder="0" min="0" step="100">
            </div>
            <div class="input-group">
                <label for="newEvtDesc">Descrição</label>
                <textarea class="apollo-input" id="newEvtDesc" rows="3" placeholder="Breve descrição do projeto…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost modal-close" type="button">Cancelar</button>
            <button class="btn btn-primary" id="newEvtSubmit" type="button">
                <i class="ri-add-line"></i> Criar Projeto
            </button>
        </div>
    </div>
</div>