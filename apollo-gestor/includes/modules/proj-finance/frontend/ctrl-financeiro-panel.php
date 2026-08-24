<?php
/**
 * Panel: Controle Financeiro — Visão Unificada
 *
 * Sub-tabs internos:
 *   Receitas | Ingressos | Staff | Despesas | Resumo Geral
 *
 * Restricted: ADM + Gestor + T.Gestor (LEVEL_FULL)
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section class="panel" id="panel-ctrl-financeiro">

    <!-- ══ HEADER ══ -->
    <div class="section-hdr">
        <span class="section-title"><?php esc_html_e( 'Controle Financeiro', 'apollo-gestor' ); ?></span>
        <div class="restricted-bar"><i class="ri-lock-line"></i> <span><?php esc_html_e( 'Restrito', 'apollo-gestor' ); ?></span></div>
    </div>

    <!-- ══ SUB-TABS ══ -->
    <nav class="ctrl-fin-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Financeiro', 'apollo-gestor' ); ?>">
        <button class="ctrl-fin-tab on" data-ctab="receitas" type="button" aria-selected="true">
            <i class="ri-arrow-down-circle-line"></i>
            <span><?php esc_html_e( 'Receitas', 'apollo-gestor' ); ?></span>
        </button>
        <button class="ctrl-fin-tab" data-ctab="ingressos" type="button" aria-selected="false">
            <i class="ri-ticket-2-line"></i>
            <span><?php esc_html_e( 'Ingressos', 'apollo-gestor' ); ?></span>
        </button>
        <button class="ctrl-fin-tab" data-ctab="staff" type="button" aria-selected="false">
            <i class="ri-group-line"></i>
            <span><?php esc_html_e( 'Staff', 'apollo-gestor' ); ?></span>
        </button>
        <button class="ctrl-fin-tab" data-ctab="despesas" type="button" aria-selected="false">
            <i class="ri-arrow-up-circle-line"></i>
            <span><?php esc_html_e( 'Despesas', 'apollo-gestor' ); ?></span>
        </button>
        <button class="ctrl-fin-tab" data-ctab="resumo" type="button" aria-selected="false">
            <i class="ri-pie-chart-2-line"></i>
            <span><?php esc_html_e( 'Resumo Geral', 'apollo-gestor' ); ?></span>
        </button>
    </nav>

    <!-- ══════════════════════════════════════════
         SUB-PANEL: RECEITAS (todas as entradas)
    ══════════════════════════════════════════ -->
    <div class="ctrl-fin-panel" id="ctab-receitas">

        <!-- KPI strip -->
        <div class="budget-kpi-strip">
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Total Entradas', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinReceitasTotal" style="color:var(--s-delivered);font-family:var(--ff-mono)">R$ 0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Ingressos', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinReceitasIngressos" style="font-family:var(--ff-mono)">R$ 0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Bar / Consumação', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinReceitasBar" style="font-family:var(--ff-mono)">R$ 0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Outros', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinReceitasOutros" style="font-family:var(--ff-mono)">R$ 0</span>
            </div>
        </div>

        <!-- Form -->
        <form id="cfinIncomeForm" class="budget-add-form" style="margin-bottom:16px">
            <?php wp_nonce_field( 'apollo_gestor_nonce', '_gestor_income_nonce' ); ?>
            <div class="input-group">
                <label><?php esc_html_e( 'Categoria', 'apollo-gestor' ); ?></label>
                <select id="cfinIncomeCat" class="apollo-input">
                    <option value="ingressos"><?php esc_html_e( 'Ingressos', 'apollo-gestor' ); ?></option>
                    <option value="bar"><?php esc_html_e( 'Bar / Consumação', 'apollo-gestor' ); ?></option>
                    <option value="patrocinio"><?php esc_html_e( 'Patrocínio', 'apollo-gestor' ); ?></option>
                    <option value="cobertura"><?php esc_html_e( 'Cobertura / Cover', 'apollo-gestor' ); ?></option>
                    <option value="outros"><?php esc_html_e( 'Outros', 'apollo-gestor' ); ?></option>
                </select>
            </div>
            <div class="input-group">
                <label><?php esc_html_e( 'Descrição', 'apollo-gestor' ); ?></label>
                <input id="cfinIncomeDesc" type="text" class="apollo-input" placeholder="<?php esc_attr_e( 'Ex: Lote 1, Mesa VIP...', 'apollo-gestor' ); ?>" required>
            </div>
            <div class="input-group">
                <label><?php esc_html_e( 'Valor (R$)', 'apollo-gestor' ); ?></label>
                <input id="cfinIncomeAmt" type="number" step="0.01" min="0" class="apollo-input" placeholder="0,00" required>
            </div>
            <div class="input-group">
                <label><?php esc_html_e( 'Data', 'apollo-gestor' ); ?></label>
                <input id="cfinIncomeDate" type="date" class="apollo-input">
            </div>
            <button type="submit" class="btn btn-primary" style="height:38px">
                <i class="ri-add-line"></i> <?php esc_html_e( 'Adicionar Receita', 'apollo-gestor' ); ?>
            </button>
        </form>

        <!-- Table -->
        <div class="budget-table-wrap">
            <table class="budget-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Data', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Categoria', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Descrição', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Valor', 'apollo-gestor' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cfinReceitasBody">
                    <!-- JS: gestor.financeiro-ctrl.js populates -->
                </tbody>
            </table>
            <p class="ctrl-fin-empty" id="cfinReceitasEmpty" style="display:none;color:var(--ghost);padding:24px;text-align:center">
                <?php esc_html_e( 'Nenhuma receita registrada.', 'apollo-gestor' ); ?>
            </p>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         SUB-PANEL: INGRESSOS (tickets specifically)
    ══════════════════════════════════════════ -->
    <div class="ctrl-fin-panel" id="ctab-ingressos" style="display:none">

        <div class="budget-kpi-strip">
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Total Ingressos', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinTicketsTotal" style="color:var(--s-delivered);font-family:var(--ff-mono)">R$ 0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Qtd. Lotes', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinTicketsQtd" style="font-family:var(--ff-mono)">0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Ticket Médio', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinTicketsMedio" style="font-family:var(--ff-mono)">R$ 0</span>
            </div>
        </div>

        <!-- Ticket batch form -->
        <form id="cfinTicketForm" class="budget-add-form" style="margin-bottom:16px">
            <?php wp_nonce_field( 'apollo_gestor_nonce', '_gestor_income_nonce' ); ?>
            <input type="hidden" id="cfinTicketCat" value="ingressos">
            <div class="input-group">
                <label><?php esc_html_e( 'Lote / Tipo', 'apollo-gestor' ); ?></label>
                <input id="cfinTicketDesc" type="text" class="apollo-input" placeholder="<?php esc_attr_e( 'Ex: Lote 1 — Pista', 'apollo-gestor' ); ?>" required>
            </div>
            <div class="input-group">
                <label><?php esc_html_e( 'Qtd. Ingressos Vendidos', 'apollo-gestor' ); ?></label>
                <input id="cfinTicketQtd" type="number" min="1" class="apollo-input" placeholder="100">
            </div>
            <div class="input-group">
                <label><?php esc_html_e( 'Valor Unitário (R$)', 'apollo-gestor' ); ?></label>
                <input id="cfinTicketUnit" type="number" step="0.01" min="0" class="apollo-input" placeholder="0,00" required>
            </div>
            <div class="input-group">
                <label><?php esc_html_e( 'Data de Fechamento', 'apollo-gestor' ); ?></label>
                <input id="cfinTicketDate" type="date" class="apollo-input">
            </div>
            <button type="submit" class="btn btn-primary" style="height:38px">
                <i class="ri-ticket-2-line"></i> <?php esc_html_e( 'Registrar Lote', 'apollo-gestor' ); ?>
            </button>
        </form>

        <div class="budget-table-wrap">
            <table class="budget-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Data', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Lote / Tipo', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Qtd.', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Unit.', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Total', 'apollo-gestor' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cfinTicketsBody">
                </tbody>
            </table>
            <p class="ctrl-fin-empty" id="cfinTicketsEmpty" style="display:none;color:var(--ghost);padding:24px;text-align:center">
                <?php esc_html_e( 'Nenhum ingresso registrado.', 'apollo-gestor' ); ?>
            </p>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         SUB-PANEL: STAFF (pagamentos equipe)
    ══════════════════════════════════════════ -->
    <div class="ctrl-fin-panel" id="ctab-staff" style="display:none">

        <!-- KPI strip -->
        <div class="budget-kpi-strip">
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Total Staff', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinStaffTotal" style="color:var(--s-delayed);font-family:var(--ff-mono)">R$ 0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Pago', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinStaffPago" style="color:var(--s-delivered);font-family:var(--ff-mono)">R$ 0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Pendente', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinStaffPendente" style="color:var(--s-ongoing);font-family:var(--ff-mono)">R$ 0</span>
            </div>
        </div>

        <!-- Search + counter bar -->
        <div class="stf-filter">
            <div class="stf-search">
                <i class="ri-search-line"></i>
                <input id="cfinStaffSearch" type="text" class="apollo-input" placeholder="<?php esc_attr_e( 'Buscar membro...', 'apollo-gestor' ); ?>">
            </div>
            <div class="stf-counter">
                <span><?php esc_html_e( 'Mostrando', 'apollo-gestor' ); ?></span>
                <strong id="cfinStaffCount">0</strong>
            </div>
        </div>

        <!-- Staff data table -->
        <div class="stf-table-wrap">
            <div class="stf-table-scroll">
                <div class="stf-table">
                    <!-- Head -->
                    <div class="stf-row stf-head">
                        <div class="stf-cell stf-cell--member"><?php esc_html_e( 'Membro', 'apollo-gestor' ); ?></div>
                        <div class="stf-cell stf-cell--role"><?php esc_html_e( 'Função', 'apollo-gestor' ); ?></div>
                        <div class="stf-cell stf-cell--progress"><?php esc_html_e( 'Progresso', 'apollo-gestor' ); ?></div>
                        <div class="stf-cell stf-cell--pix"><?php esc_html_e( 'PIX', 'apollo-gestor' ); ?></div>
                        <div class="stf-cell stf-cell--status"><?php esc_html_e( 'Status', 'apollo-gestor' ); ?></div>
                        <div class="stf-cell stf-cell--amount"><?php esc_html_e( 'Valor Total', 'apollo-gestor' ); ?></div>
                    </div>
                    <!-- Body (JS-populated) -->
                    <div id="cfinStaffBody" class="stf-body"></div>
                </div>
            </div>
            <p class="ctrl-fin-empty" id="cfinStaffEmpty" style="display:none;color:var(--ghost);padding:24px;text-align:center">
                <?php esc_html_e( 'Nenhum pagamento de staff registrado.', 'apollo-gestor' ); ?>
            </p>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: Staff Payment Update (partial / full)
    ══════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="staffPayModal">
        <div class="modal-box" style="width:480px">
            <div class="modal-header">
                <h3><i class="ri-money-dollar-circle-line"></i> <?php esc_html_e( 'Atualizar Pagamento', 'apollo-gestor' ); ?></h3>
                <button type="button" class="btn btn-icon" id="staffPayModalClose" aria-label="<?php esc_attr_e( 'Fechar', 'apollo-gestor' ); ?>">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Member info -->
                <div class="stfm-member-info">
                    <img id="stfmAvatar" src="" alt="" class="stfm-avatar">
                    <div class="stfm-member-data">
                        <div id="stfmName" class="stfm-name"></div>
                        <div id="stfmRole" class="stfm-role"></div>
                    </div>
                </div>

                <!-- PIX display -->
                <div class="stfm-pix-block">
                    <label><?php esc_html_e( 'Chave PIX', 'apollo-gestor' ); ?></label>
                    <div class="stfm-pix-value">
                        <code id="stfmPix">—</code>
                        <button type="button" class="btn btn-icon btn-sm" id="stfmCopyPix" title="<?php esc_attr_e( 'Copiar PIX', 'apollo-gestor' ); ?>">
                            <i class="ri-file-copy-line"></i>
                        </button>
                    </div>
                </div>

                <!-- Progress visual -->
                <div class="stfm-progress-block">
                    <div class="stfm-progress-header">
                        <span><?php esc_html_e( 'Progresso do Pagamento', 'apollo-gestor' ); ?></span>
                        <span id="stfmProgressPct" style="font-weight:700;font-family:var(--ff-mono)">0%</span>
                    </div>
                    <div class="stf-progress-bar"><span id="stfmProgressBar" style="width:0%"></span></div>
                    <div class="stfm-progress-amounts">
                        <span><?php esc_html_e( 'Pago:', 'apollo-gestor' ); ?> <strong id="stfmPaidSoFar" style="color:var(--s-delivered)">R$ 0</strong></span>
                        <span><?php esc_html_e( 'Total:', 'apollo-gestor' ); ?> <strong id="stfmTotalAmount">R$ 0</strong></span>
                    </div>
                </div>

                <!-- Payment input -->
                <div class="stfm-input-block">
                    <label for="stfmPayValue"><?php esc_html_e( 'Valor Pago (R$)', 'apollo-gestor' ); ?></label>
                    <input id="stfmPayValue" type="number" step="0.01" min="0" class="apollo-input" placeholder="0,00" style="font-family:var(--ff-mono)">
                    <small class="stfm-hint"><?php esc_html_e( 'Restante:', 'apollo-gestor' ); ?> <span id="stfmRemaining" style="font-weight:600;font-family:var(--ff-mono)">R$ 0</span></small>
                </div>

                <!-- Status shortcut -->
                <div class="stfm-status-block">
                    <label><?php esc_html_e( 'Marcar como', 'apollo-gestor' ); ?></label>
                    <div class="stfm-status-btns">
                        <button type="button" class="btn btn-sm stfm-status-btn" data-stfm-status="pending">
                            <i class="ri-time-line"></i> <?php esc_html_e( 'Pendente', 'apollo-gestor' ); ?>
                        </button>
                        <button type="button" class="btn btn-sm stfm-status-btn" data-stfm-status="paid">
                            <i class="ri-check-line"></i> <?php esc_html_e( 'Pago', 'apollo-gestor' ); ?>
                        </button>
                        <button type="button" class="btn btn-sm stfm-status-btn" data-stfm-status="late">
                            <i class="ri-error-warning-line"></i> <?php esc_html_e( 'Atrasado', 'apollo-gestor' ); ?>
                        </button>
                    </div>
                </div>

                <input type="hidden" id="stfmPaymentId" value="">
                <input type="hidden" id="stfmEventId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" id="stfmCancel"><?php esc_html_e( 'Cancelar', 'apollo-gestor' ); ?></button>
                <button type="button" class="btn btn-primary" id="stfmSave">
                    <i class="ri-save-line"></i> <?php esc_html_e( 'Salvar', 'apollo-gestor' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         SUB-PANEL: DESPESAS (saídas / budget)
    ══════════════════════════════════════════ -->
    <div class="ctrl-fin-panel" id="ctab-despesas" style="display:none">
        <div class="budget-kpi-strip">
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Total Despesas', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinDespesasTotal" style="color:var(--s-delayed);font-family:var(--ff-mono)">R$ 0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Pago', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinDespesasPago" style="color:var(--s-delivered);font-family:var(--ff-mono)">R$ 0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Pendente', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinDespesasPendente" style="color:var(--s-ongoing);font-family:var(--ff-mono)">R$ 0</span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Saldo Budget', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="cfinDespesasSaldo" style="font-family:var(--ff-mono)">R$ 0</span>
            </div>
        </div>
        <div class="budget-table-wrap">
            <table class="budget-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Descrição', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Categoria', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Responsável', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Valor', 'apollo-gestor' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'apollo-gestor' ); ?></th>
                    </tr>
                </thead>
                <tbody id="cfinDespesasBody">
                </tbody>
            </table>
            <p class="ctrl-fin-empty" id="cfinDespesasEmpty" style="display:none;color:var(--ghost);padding:24px;text-align:center">
                <?php esc_html_e( 'Nenhuma despesa registrada.', 'apollo-gestor' ); ?>
            </p>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         SUB-PANEL: RESUMO GERAL (controle unificado)
    ══════════════════════════════════════════ -->
    <div class="ctrl-fin-panel" id="ctab-resumo" style="display:none">

        <!-- Balance hero -->
        <div class="cfin-balance-hero">
            <div class="cfin-balance-label"><?php esc_html_e( 'Saldo Líquido do Evento', 'apollo-gestor' ); ?></div>
            <div class="cfin-balance-value" id="cfinSaldoLiquido">R$ 0</div>
            <div class="cfin-balance-sub" id="cfinSaldoSub"></div>
        </div>

        <!-- Fluxo in/out bars -->
        <div class="cfin-fluxo-grid">
            <!-- ENTRADAS -->
            <div class="cfin-fluxo-card cfin-entrada">
                <div class="cfin-fluxo-icon"><i class="ri-arrow-down-circle-fill"></i></div>
                <div class="cfin-fluxo-info">
                    <span class="cfin-fluxo-title"><?php esc_html_e( 'Total Entradas', 'apollo-gestor' ); ?></span>
                    <span class="cfin-fluxo-value" id="cfinResEntradas">R$ 0</span>
                </div>
                <div class="cfin-fluxo-breakdown">
                    <div class="cfin-bd-row"><span><?php esc_html_e( 'Ingressos', 'apollo-gestor' ); ?></span><span id="resBdIngressos">R$ 0</span></div>
                    <div class="cfin-bd-row"><span><?php esc_html_e( 'Bar', 'apollo-gestor' ); ?></span><span id="resBdBar">R$ 0</span></div>
                    <div class="cfin-bd-row"><span><?php esc_html_e( 'Patrocínio', 'apollo-gestor' ); ?></span><span id="resBdPatrocinio">R$ 0</span></div>
                    <div class="cfin-bd-row"><span><?php esc_html_e( 'Outros', 'apollo-gestor' ); ?></span><span id="resBdOutros">R$ 0</span></div>
                </div>
            </div>

            <!-- SAÍDAS -->
            <div class="cfin-fluxo-card cfin-saida">
                <div class="cfin-fluxo-icon"><i class="ri-arrow-up-circle-fill"></i></div>
                <div class="cfin-fluxo-info">
                    <span class="cfin-fluxo-title"><?php esc_html_e( 'Total Saídas', 'apollo-gestor' ); ?></span>
                    <span class="cfin-fluxo-value" id="cfinResSaidas">R$ 0</span>
                </div>
                <div class="cfin-fluxo-breakdown">
                    <div class="cfin-bd-row"><span><?php esc_html_e( 'Despesas / Budget', 'apollo-gestor' ); ?></span><span id="resBdBudget">R$ 0</span></div>
                    <div class="cfin-bd-row"><span><?php esc_html_e( 'Staff', 'apollo-gestor' ); ?></span><span id="resBdStaff">R$ 0</span></div>
                    <div class="cfin-bd-row"><span><?php esc_html_e( 'Fornecedores', 'apollo-gestor' ); ?></span><span id="resBdFornecedores">R$ 0</span></div>
                </div>
            </div>
        </div>

        <!-- Visual bar comparison -->
        <div class="cfin-bar-wrap">
            <div class="cfin-bar-row">
                <span class="cfin-bar-label"><?php esc_html_e( 'Entradas', 'apollo-gestor' ); ?></span>
                <div class="cfin-bar-track">
                    <div class="cfin-bar cfin-bar-in" id="cfinBarIn" style="width:0%"></div>
                </div>
                <span class="cfin-bar-pct" id="cfinBarInPct">0%</span>
            </div>
            <div class="cfin-bar-row">
                <span class="cfin-bar-label"><?php esc_html_e( 'Saídas', 'apollo-gestor' ); ?></span>
                <div class="cfin-bar-track">
                    <div class="cfin-bar cfin-bar-out" id="cfinBarOut" style="width:0%"></div>
                </div>
                <span class="cfin-bar-pct" id="cfinBarOutPct">0%</span>
            </div>
        </div>

        <!-- Detail table -->
        <div class="fin-summary" style="max-width:420px;margin-top:16px">
            <div class="fin-summary-title"><?php esc_html_e( 'Detalhamento Final', 'apollo-gestor' ); ?></div>
            <div class="fin-breakdown">
                <div class="fin-row">
                    <span class="fin-row-label" style="color:var(--s-delivered)"><?php esc_html_e( '(+) Total Receitas', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="resDetReceitas" style="color:var(--s-delivered)">R$ 0</span>
                </div>
                <div class="fin-row">
                    <span class="fin-row-label" style="color:var(--s-delayed)"><?php esc_html_e( '(-) Total Despesas', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="resDetDespesas" style="color:var(--s-delayed)">R$ 0</span>
                </div>
                <div class="fin-row">
                    <span class="fin-row-label" style="color:var(--s-delayed)"><?php esc_html_e( '(-) Total Staff', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="resDetStaff" style="color:var(--s-delayed)">R$ 0</span>
                </div>
                <div class="fin-divider"></div>
                <div class="fin-row">
                    <span class="fin-row-label" style="font-weight:700"><?php esc_html_e( '(=) Saldo Líquido', 'apollo-gestor' ); ?></span>
                    <span class="fin-row-value" id="resDetSaldo" style="font-weight:700">R$ 0</span>
                </div>
            </div>
        </div>

    </div><!-- end ctab-resumo -->

</section>
