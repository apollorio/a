<?php
/**
 * Panel: Financeiro (Proj_Finance)
 *
 * Staff payments table + financial summary sidebar.
 * Restricted: ADM + Gestor + T.Gestor
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section class="panel" id="panel-financeiro">
    <div class="section-hdr">
        <span class="section-title"><?php esc_html_e( 'Pagamentos Staff', 'apollo-gestor' ); ?></span>
        <div class="restricted-bar"><i class="ri-lock-line"></i> <span id="finVisibility"><?php esc_html_e( 'Restrito', 'apollo-gestor' ); ?></span></div>
    </div>
    <div class="fin-layout">
        <!-- Payments Table -->
        <div class="budget-table-wrap">
            <table class="budget-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Membro', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Função', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Valor', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'PIX', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'apollo-gestor' ); ?></th>
                    </tr>
                </thead>
                <tbody id="finBody">
                    <!-- JS: gestor.budget.js populates rows -->
                </tbody>
            </table>
        </div>

        <!-- Financial Summary Sidebar -->
        <div class="fin-summary">
            <div class="fin-summary-title"><?php esc_html_e( 'Resumo Financeiro', 'apollo-gestor' ); ?></div>
            <div class="fin-summary-total" id="finTotal">R$ 0</div>
            <div class="fin-breakdown">
                <div class="fin-row">
                    <span class="fin-row-label"><?php esc_html_e( 'Staff', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="finStaff">R$ 0</span>
                </div>
                <div class="fin-row">
                    <span class="fin-row-label"><?php esc_html_e( 'Fornecedores', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="finSuppliers">R$ 0</span>
                </div>
                <div class="fin-row">
                    <span class="fin-row-label"><?php esc_html_e( 'Produção', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="finProduction">R$ 0</span>
                </div>
                <div class="fin-divider"></div>
                <div class="fin-row">
                    <span class="fin-row-label" style="color:var(--s-delivered)"><?php esc_html_e( 'Pago', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="finPaid" style="color:var(--s-delivered)">R$ 0</span>
                </div>
                <div class="fin-row">
                    <span class="fin-row-label" style="color:var(--s-ongoing)"><?php esc_html_e( 'Pendente', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="finPending" style="color:var(--s-ongoing)">R$ 0</span>
                </div>
                <div class="fin-row">
                    <span class="fin-row-label" style="color:var(--s-delayed)"><?php esc_html_e( 'Atrasado', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="finLate" style="color:var(--s-delayed)">R$ 0</span>
                </div>
            </div>
        </div>
    </div>
</section>
