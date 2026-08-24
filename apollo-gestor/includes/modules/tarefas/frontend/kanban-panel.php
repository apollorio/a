<?php
/**
 * Panel: Kanban (Tarefas)
 *
 * 6-column drag & drop board.
 * Columns: planned, tostart, ongoing, delayed, canceled, delivered
 * Cards populated by gestor.kanban.js via AJAX.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$columns = [
    'planned'   => [ 'label' => __( 'Planejado', 'apollo-gestor' ),    'icon' => 'ri-time-line',            'empty' => __( 'Nenhuma tarefa planejada', 'apollo-gestor' ) ],
    'tostart'   => [ 'label' => __( 'A Iniciar', 'apollo-gestor' ),    'icon' => 'ri-time-line',            'empty' => __( 'Nenhuma tarefa a iniciar', 'apollo-gestor' ) ],
    'ongoing'   => [ 'label' => __( 'Em Andamento', 'apollo-gestor' ), 'icon' => 'ri-loader-4-line',        'empty' => __( 'Nenhuma tarefa em andamento', 'apollo-gestor' ) ],
    'delayed'   => [ 'label' => __( 'Atrasado', 'apollo-gestor' ),     'icon' => 'ri-alarm-warning-line',   'empty' => __( 'Nenhuma tarefa atrasada', 'apollo-gestor' ) ],
    'canceled'  => [ 'label' => __( 'Cancelado', 'apollo-gestor' ),    'icon' => 'ri-close-circle-line',    'empty' => __( 'Nenhuma tarefa cancelada', 'apollo-gestor' ) ],
    'delivered' => [ 'label' => __( 'Entregue', 'apollo-gestor' ),     'icon' => 'ri-checkbox-circle-line', 'empty' => __( 'Nenhuma tarefa entregue', 'apollo-gestor' ) ],
];
?>
<section class="panel" id="panel-kanban">
    <div class="kanban">
        <?php foreach ( $columns as $status => $col ) : ?>
        <div class="kan-col" data-status="<?php echo esc_attr( $status ); ?>">
            <div class="kan-col-hdr">
                <span class="kan-col-title">
                    <span class="dot" style="background:var(--s-<?php echo esc_attr( $status ); ?>)"></span>
                    <?php echo esc_html( $col['label'] ); ?>
                </span>
                <span class="kan-col-count" id="kan-count-<?php echo esc_attr( $status ); ?>">0</span>
            </div>
            <div class="kan-col-body" data-status="<?php echo esc_attr( $status ); ?>">
                <!-- JS: gestor.kanban.js renders .ev-card elements here -->
                <div class="kan-empty" style="padding:40px 20px;text-align:center;color:var(--gray-1);font-size:11px">
                    <i class="<?php echo esc_attr( $col['icon'] ); ?>" style="font-size:24px;display:block;margin-bottom:8px;opacity:.3"></i>
                    <?php echo esc_html( $col['empty'] ); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
