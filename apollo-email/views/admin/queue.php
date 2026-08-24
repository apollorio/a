<?php

/**
 * Admin View: Fila de Envio.
 *
 * @var array  $items   Queue items with 'data' and 'total' keys.
 * @var array  $stats   Queue stats.
 * @var string $status  Current filter status.
 * @var int    $paged   Current page.
 */

if (! defined('ABSPATH')) {
    exit;
}

$purged   = isset($_GET['purged']) ? absint($_GET['purged']) : null;
$base_url = admin_url('admin.php?page=apollo-email-queue');

$status_labels = array(
    'pending'    => __('Pendente', 'apollo-email'),
    'processing' => __('Processando', 'apollo-email'),
    'sent'       => __('Enviado', 'apollo-email'),
    'failed'     => __('Falhou', 'apollo-email'),
    'cancelled'  => __('Cancelado', 'apollo-email'),
);

$items_data  = $items['items'] ?? array();
$total       = $items['total'] ?? 0;
$per_page    = 20;
$total_pages = ceil($total / $per_page);
?>
<div class="wrap apollo-email-admin">
    <h1><?php esc_html_e('Fila de Envio', 'apollo-email'); ?></h1>

    <?php if ($purged !== null) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html(sprintf(__('%d itens removidos da fila.', 'apollo-email'), $purged)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Status filter tabs -->
    <ul class="subsubsub">
        <li>
            <a href="<?php echo esc_url($base_url); ?>" <?php echo empty($status) ? 'class="current"' : ''; ?>>
                <?php esc_html_e('Todos', 'apollo-email'); ?>
            </a> |
        </li>
        <?php
        foreach ($status_labels as $k => $label) :
            $count = $stats[$k] ?? 0;
        ?>
            <li>
                <a href="<?php echo esc_url(add_query_arg('status', $k, $base_url)); ?>" <?php echo $status === $k ? 'class="current"' : ''; ?>>
                    <?php echo esc_html($label); ?>
                    <span class="count">(<?php echo esc_html($count); ?>)</span>
                </a> <?php echo $k !== 'cancelled' ? '|' : ''; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <br class="clear" />

    <?php if (empty($items_data)) : ?>
        <div class="apollo-email-empty">
            <span class="dashicons dashicons-email-alt2"></span>
            <p><?php esc_html_e('A fila está vazia.', 'apollo-email'); ?></p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:40px;">ID</th>
                    <th><?php esc_html_e('Destinatário', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Assunto', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Template', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Prioridade', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Status', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Tentativas', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Agendado', 'apollo-email'); ?></th>
                    <th style="width:140px;"><?php esc_html_e('Ações', 'apollo-email'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_data as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item->id); ?></td>
                        <td>
                            <?php echo esc_html($item->to_email); ?>
                            <?php if (! empty($item->to_name)) : ?>
                                <br><small><?php echo esc_html($item->to_name); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($item->subject); ?></td>
                        <td>
                            <?php echo $item->template ? '<code>' . esc_html($item->template) . '</code>' : '—'; ?>
                        </td>
                        <td>
                            <span class="priority-badge priority-<?php echo esc_attr($item->priority); ?>">
                                <?php echo esc_html($item->priority); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo esc_attr($item->status); ?>">
                                <?php echo esc_html($status_labels[$item->status] ?? ucfirst($item->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo esc_html($item->attempts . '/' . $item->max_attempts); ?>
                        </td>
                        <td>
                            <?php echo esc_html(wp_date('d/m/Y H:i', strtotime($item->scheduled_at ?: $item->created_at))); ?>
                        </td>
                        <td>
                            <?php if ($item->status === 'pending' || $item->status === 'processing') : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                    <input type="hidden" name="action" value="apollo_email_queue_action" />
                                    <input type="hidden" name="queue_action" value="cancel" />
                                    <input type="hidden" name="queue_id" value="<?php echo esc_attr($item->id); ?>" />
                                    <?php wp_nonce_field('apollo_email_queue_action'); ?>
                                    <button type="submit" class="button button-small"><?php esc_html_e('Cancelar', 'apollo-email'); ?></button>
                                </form>
                            <?php endif; ?>

                            <?php if ($item->status === 'failed' || $item->status === 'cancelled') : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                    <input type="hidden" name="action" value="apollo_email_queue_action" />
                                    <input type="hidden" name="queue_action" value="retry" />
                                    <input type="hidden" name="queue_id" value="<?php echo esc_attr($item->id); ?>" />
                                    <?php wp_nonce_field('apollo_email_queue_action'); ?>
                                    <button type="submit" class="button button-small"><?php esc_html_e('Reenviar', 'apollo-email'); ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if (! empty($item->error_message)) : ?>
                        <tr class="error-detail-row">
                            <td></td>
                            <td colspan="8">
                                <span class="error-detail"><?php echo esc_html($item->error_message); ?></span>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo esc_html(sprintf(__('%d itens', 'apollo-email'), $total)); ?>
                    </span>
                    <?php
                    echo paginate_links(
                        array(
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $paged,
                            'total'   => $total_pages,
                            'type'    => 'plain',
                        )
                    );
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Purge -->
    <div class="apollo-email-card apollo-email-card-full" style="margin-top: 20px;">
        <h3><?php esc_html_e('Limpar Fila', 'apollo-email'); ?></h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="apollo-email-purge-form">
            <input type="hidden" name="action" value="apollo_email_purge_queue" />
            <?php wp_nonce_field('apollo_email_purge_queue'); ?>
            <p>
                <label>
                    <?php esc_html_e('Remover itens enviados/cancelados mais antigos que', 'apollo-email'); ?>
                    <input type="number" name="purge_days" value="30" min="1" max="365" class="apollo-input small-text" />
                    <?php esc_html_e('dias', 'apollo-email'); ?>
                </label>
                <button type="submit" class="button" onclick="return confirm('<?php echo esc_js(__('Tem certeza?', 'apollo-email')); ?>');">
                    <?php esc_html_e('Limpar', 'apollo-email'); ?>
                </button>
            </p>
        </form>
    </div>
</div>