<?php
/**
 * Partial: Permissions modal
 *
 * Team member role management overlay.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- Permissions Modal -->
<div class="modal-overlay" id="permModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="ri-shield-keyhole-line"></i> Permissões da Equipe</h3>
            <button class="btn-icon modal-close" type="button"><i class="ri-close-fill"></i></button>
        </div>
        <div class="modal-body" id="permList">
            <!-- JS populates team member permission rows -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost modal-close" type="button">Cancelar</button>
            <button class="btn btn-primary" id="permSave" type="button">
                <i class="ri-check-line"></i> Salvar
            </button>
        </div>
    </div>
</div>
