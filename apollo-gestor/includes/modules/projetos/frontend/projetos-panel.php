<?php
/**
 * Panel: Overview (Projetos)
 *
 * Stat cards, charts grid (amCharts 5), activity feed, mini-cards by status.
 * Single event view toggled by selector.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section class="panel on" id="panel-overview">

    <!-- ── All Events View ── -->
    <div id="overviewContent" class="on">

        <!-- STAT CARDS — populated by gestor.data.js -->
        <div class="stats-row">
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Eventos Ativos', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="statActiveEvents">--</span>
                <span class="stat-card-trend" id="statActiveEventsTrend"></span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Tarefas Prontas', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="statTasksDone">--</span>
                <span class="stat-card-trend" id="statTasksDoneTrend"></span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Receita Mensal', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="statRevenue" style="font-family:var(--ff-mono)">--</span>
                <span class="stat-card-trend" id="statRevenueTrend"></span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Ingressos Vendidos', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="statTickets">--</span>
                <span class="stat-card-trend" id="statTicketsTrend"></span>
            </div>
            <div class="stat-card">
                <span class="stat-card-label"><?php esc_html_e( 'Time Total', 'apollo-gestor' ); ?></span>
                <span class="stat-card-value" id="statTeamTotal">--</span>
                <span class="stat-card-trend" id="statTeamTrend"></span>
            </div>
        </div>

        <!-- CHARTS GRID — amCharts 5 renders into these containers -->
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-card-header">
                    <span class="chart-card-title"><?php esc_html_e( 'Receita por Evento', 'apollo-gestor' ); ?></span>
                    <div class="chart-card-actions">
                        <button title="<?php esc_attr_e( 'Expandir', 'apollo-gestor' ); ?>"><i class="ri-fullscreen-line"></i></button>
                        <button title="<?php esc_attr_e( 'Baixar', 'apollo-gestor' ); ?>"><i class="ri-download-2-line"></i></button>
                    </div>
                </div>
                <div class="chart-periods">
                    <span class="chart-period on">6M</span>
                    <span class="chart-period">3M</span>
                    <span class="chart-period">1M</span>
                    <span class="chart-period">1S</span>
                </div>
                <div class="chart-body" id="chart-revenue"></div>
            </div>
            <div class="chart-card">
                <div class="chart-card-header">
                    <span class="chart-card-title"><?php esc_html_e( 'Venda de Ingressos (30 dias)', 'apollo-gestor' ); ?></span>
                    <div class="chart-card-actions">
                        <button title="<?php esc_attr_e( 'Expandir', 'apollo-gestor' ); ?>"><i class="ri-fullscreen-line"></i></button>
                    </div>
                </div>
                <div class="chart-body" id="chart-tickets"></div>
            </div>
            <div class="chart-card">
                <div class="chart-card-header">
                    <span class="chart-card-title"><?php esc_html_e( 'Audiência por Gênero', 'apollo-gestor' ); ?></span>
                    <div class="chart-card-actions">
                        <button title="<?php esc_attr_e( 'Expandir', 'apollo-gestor' ); ?>"><i class="ri-fullscreen-line"></i></button>
                    </div>
                </div>
                <div class="chart-body" id="chart-audience" style="min-height:200px"></div>
            </div>
        </div>

        <!-- ACTIVITY + MINI CARDS -->
        <div class="overview-bottom">
            <!-- Recent Activity — populated by gestor.data.js -->
            <div>
                <div class="section-hdr"><span class="section-title"><?php esc_html_e( 'Atividade Recente', 'apollo-gestor' ); ?></span></div>
                <div class="activity-feed" id="activityFeed">
                    <!-- JS populates .activity-item elements -->
                </div>
            </div>

            <!-- Overview Columns — grouped by status -->
            <div class="overview-cols" id="overviewCols">
                <div>
                    <div class="overview-col-title"><span style="width:6px;height:6px;border-radius:50%;background:var(--s-planned)"></span> <?php esc_html_e( 'Planejado', 'apollo-gestor' ); ?> <span class="count" id="colPlannedCount">0</span></div>
                    <div id="colPlanned"></div>
                </div>
                <div>
                    <div class="overview-col-title"><span style="width:6px;height:6px;border-radius:50%;background:var(--s-ongoing)"></span> <?php esc_html_e( 'Em Andamento', 'apollo-gestor' ); ?> <span class="count" id="colOngoingCount">0</span></div>
                    <div id="colOngoing"></div>
                </div>
                <div>
                    <div class="overview-col-title"><span style="width:6px;height:6px;border-radius:50%;background:var(--s-delivered)"></span> <?php esc_html_e( 'Entregues', 'apollo-gestor' ); ?> <span class="count" id="colDeliveredCount">0</span></div>
                    <div id="colDelivered"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Single Event View (toggled by selector) ── -->
    <div id="singleContent" class="single-event">
        <div class="single-hero">
            <div class="single-hero-inner">
                <div class="date-box"><span class="day" id="evDay">--</span><span class="month" id="evMonth">---</span></div>
                <h2 id="evTitle"></h2>
                <div class="loc"><i class="ri-map-pin-line"></i> <span id="evLoc"></span></div>
            </div>
        </div>

        <div class="stats-row" style="margin-bottom:20px">
            <div class="stat-card"><span class="stat-card-label"><?php esc_html_e( 'Budget', 'apollo-gestor' ); ?></span><span class="stat-card-value" id="evBudget" style="font-family:var(--ff-mono)">--</span></div>
            <div class="stat-card"><span class="stat-card-label"><?php esc_html_e( 'Ingressos', 'apollo-gestor' ); ?></span><span class="stat-card-value" id="evTickets">--</span></div>
            <div class="stat-card"><span class="stat-card-label"><?php esc_html_e( 'Time', 'apollo-gestor' ); ?></span><span class="stat-card-value" id="evTeamCount">--</span></div>
            <div class="stat-card"><span class="stat-card-label"><?php esc_html_e( 'Progresso', 'apollo-gestor' ); ?></span><span class="stat-card-value ev-progress-label" id="evProgress">--</span></div>
        </div>

        <div class="ev-meta-grid" id="evMetaGrid">
            <div class="ev-meta-item"><div class="ev-meta-label"><?php esc_html_e( 'Status', 'apollo-gestor' ); ?></div><div class="ev-meta-value" id="evStatus"></div></div>
            <div class="ev-meta-item"><div class="ev-meta-label"><?php esc_html_e( 'Data', 'apollo-gestor' ); ?></div><div class="ev-meta-value" id="evDate"></div></div>
            <div class="ev-meta-item"><div class="ev-meta-label"><?php esc_html_e( 'Coordenador', 'apollo-gestor' ); ?></div><div class="ev-meta-value" id="evCoord"></div></div>
            <div class="ev-meta-item"><div class="ev-meta-label"><?php esc_html_e( 'Gênero', 'apollo-gestor' ); ?></div><div class="ev-meta-value" id="evGenre"></div></div>
        </div>

        <div style="margin-bottom:24px">
            <div class="section-hdr"><span class="section-title"><?php esc_html_e( 'Progresso Geral', 'apollo-gestor' ); ?></span></div>
            <div class="budget-progress"><div class="ev-progress-bar budget-progress-bar" id="evProgressBar" style="width:0%"></div></div>
        </div>

        <div class="section-hdr"><span class="section-title"><?php esc_html_e( 'Tarefas', 'apollo-gestor' ); ?></span></div>
        <div class="task-list" id="evTaskList">
            <!-- JS populates .task-item elements -->
        </div>
    </div>
</section>
