<?php
/**
 * Panel: Fornecedores (Proj_Staff)
 *
 * Supplier card grid with contact info.
 * Linked to event via _event_supplier_ids meta.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section class="panel" id="panel-fornecedores">
    <div class="section-hdr">
        <span class="section-title"><?php esc_html_e( 'Fornecedores', 'apollo-gestor' ); ?></span>
        <div style="display:flex;gap:8px">
            <button class="btn btn-ghost" id="permOpen"><i class="ri-shield-user-line"></i> <?php esc_html_e( 'Permissões', 'apollo-gestor' ); ?></button>
            <button class="btn btn-primary" id="supplierAddBtn"><i class="ri-add-line"></i> <?php esc_html_e( 'Adicionar', 'apollo-gestor' ); ?></button>
        </div>
    </div>
    <div class="supplier-grid" id="supplierGrid">
        <!-- JS: gestor.data.js renders .supplier-card elements via AJAX -->
        <!--
        Template per card (rendered by JS):
        <div class="supplier-card">
            <div class="supplier-card-hdr">
                <div>
                    <div class="supplier-name">Name</div>
                    <div class="supplier-type">Category</div>
                </div>
                <span class="status-pill">Status</span>
            </div>
            <div class="supplier-contact"><i class="ri-phone-line"></i> Phone</div>
            <div class="supplier-contact"><i class="ri-mail-line"></i> Email</div>
            <div style="margin-top:12px;font-family:var(--ff-mono);font-size:12px;font-weight:700">R$ 0</div>
        </div>
        -->
    </div>

    <!-- Link Supplier Dropdown (hidden by default) -->
    <div id="supplierLinkDropdown" style="display:none;margin-top:12px">
        <div class="section-hdr"><span class="section-title"><?php esc_html_e( 'Vincular Fornecedor', 'apollo-gestor' ); ?></span></div>
        <div style="display:flex;gap:8px;align-items:center">
            <select class="apollo-input" id="supplierSelect" style="flex:1">
                <option value=""><?php esc_html_e( 'Selecionar fornecedor…', 'apollo-gestor' ); ?></option>
            </select>
            <button class="btn btn-primary" id="supplierLinkBtn"><i class="ri-link"></i> <?php esc_html_e( 'Vincular', 'apollo-gestor' ); ?></button>
        </div>
    </div>
</section>