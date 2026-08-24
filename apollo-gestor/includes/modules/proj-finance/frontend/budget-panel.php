<?php
/**
 * Panel: Budget (Proj_Finance)
 *
 * KPI strip, budget progress bar, budget table, add form, calculator link.
 * Restricted: ADM + Gestor only.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section class="panel" id="panel-budget">
    <div class="section-hdr">
        <span class="section-title"><?php esc_html_e( 'Budget do Evento', 'apollo-gestor' ); ?></span>
        <div class="restricted-bar"><i class="ri-lock-line"></i> <?php esc_html_e( 'Visível: ADM + Gestor', 'apollo-gestor' ); ?></div>
    </div>

    <!-- KPI Strip -->
    <div class="budget-kpi-strip">
        <div class="stat-card">
            <span class="stat-card-label"><?php esc_html_e( 'Budget Total', 'apollo-gestor' ); ?></span>
            <span class="stat-card-value" id="budgetTotal" style="font-family:var(--ff-mono)">--</span>
        </div>
        <div class="stat-card">
            <span class="stat-card-label"><?php esc_html_e( 'Comprometido', 'apollo-gestor' ); ?></span>
            <span class="stat-card-value" id="budgetCommitted" style="font-family:var(--ff-mono)">--</span>
        </div>
        <div class="stat-card">
            <span class="stat-card-label"><?php esc_html_e( 'Realizado', 'apollo-gestor' ); ?></span>
            <span class="stat-card-value" id="budgetRealized" style="font-family:var(--ff-mono);color:var(--s-delivered)">--</span>
        </div>
        <div class="stat-card">
            <span class="stat-card-label"><?php esc_html_e( 'Saldo', 'apollo-gestor' ); ?></span>
            <span class="stat-card-value" id="budgetBalance" style="font-family:var(--ff-mono)">--</span>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="budget-progress-wrap">
        <div class="budget-progress"><div class="budget-progress-bar" id="budgetProgressBar" style="width:0%"></div></div>
        <div class="budget-progress-labels">
            <span id="budgetProgressPct">0% comprometido</span>
            <span id="budgetProgressRemaining">R$ 0 restante</span>
        </div>
    </div>

    <!-- Budget Table -->
    <div class="budget-table-wrap">
        <table class="budget-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Descrição', 'apollo-gestor' ); ?></th>
                    <th><?php esc_html_e( 'Valor', 'apollo-gestor' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'apollo-gestor' ); ?></th>
                    <th><?php esc_html_e( 'Responsável', 'apollo-gestor' ); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="budgetBody">
                <!-- JS: gestor.budget.js populates rows -->
            </tbody>
        </table>
    </div>

    <!-- Add Budget Item Form -->
    <form id="budgetAddForm" class="budget-add-form">
        <?php wp_nonce_field( 'apollo_gestor_nonce', '_gestor_budget_nonce' ); ?>
        <div class="input-group">
            <label><?php esc_html_e( 'Descrição', 'apollo-gestor' ); ?></label>
            <input id="bDesc" type="text" placeholder="<?php esc_attr_e( 'Ex: Cachê DJ extra', 'apollo-gestor' ); ?>" class="apollo-input" required>
        </div>
        <div class="input-group">
            <label><?php esc_html_e( 'Valor (R$)', 'apollo-gestor' ); ?></label>
            <input id="bVal" type="number" step="0.01" placeholder="0,00" required class="apollo-input">
        </div>
        <div class="input-group">
            <label><?php esc_html_e( 'Status', 'apollo-gestor' ); ?></label>
            <select id="bStatus">
                <option value="planned"><?php esc_html_e( 'Planejado', 'apollo-gestor' ); ?></option>
                <option value="pending"><?php esc_html_e( 'Comprometido', 'apollo-gestor' ); ?></option>
                <option value="paid"><?php esc_html_e( 'Pago', 'apollo-gestor' ); ?></option>
            </select>
        </div>
        <div class="input-group">
            <label><?php esc_html_e( 'Responsável', 'apollo-gestor' ); ?></label>
            <input id="bResp" type="text" placeholder="<?php esc_attr_e( 'Nome', 'apollo-gestor' ); ?>" class="apollo-input">
        </div>
        <button type="submit" class="btn btn-primary" style="height:38px"><i class="ri-add-line"></i> <?php esc_html_e( 'Adicionar', 'apollo-gestor' ); ?></button>
    </form>

    <!-- Calculator Link -->
    <div style="display:flex;gap:12px;margin-top:12px">
        <button class="btn btn-ghost" id="calcOpen"><i class="ri-calculator-line"></i> <?php esc_html_e( 'Calculadora de Cachê', 'apollo-gestor' ); ?></button>
    </div>
</section>