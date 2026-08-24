<?php

/**
 * Admin View: Log de Envios.
 *
 * @var array $entries  Log entries with 'data' and 'total' keys.
 * @var array $stats    Log stats.
 * @var int   $paged    Current page.
 */

if (! defined('ABSPATH')) {
    exit;
}

$purged   = isset($_GET['purged']) ? absint($_GET['purged']) : null;
$base_url = admin_url('admin.php?page=apollo-email-log');

$per_page     = 20;
$total        = $entries['total'] ?? 0;
$total_pages  = ceil($total / $per_page);
$entries_data = $entries['items'] ?? array();

$status_labels = array(
    'sent'    => __('Enviado', 'apollo-email'),
    'failed'  => __('Falhou', 'apollo-email'),
    'opened'  => __('Aberto', 'apollo-email'),
    'clicked' => __('Clicado', 'apollo-email'),
);
?>
<div class="wrap apollo-email-admin">
    <h1><?php esc_html_e('Log de Envios', 'apollo-email'); ?></h1>

    <?php if ($purged !== null) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html(sprintf(__('%d registros removidos.', 'apollo-email'), $purged)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Stats mini bar -->
    <div class="apollo-email-log-stats">
        <span class="stat-item">
            <strong><?php echo esc_html($stats['total'] ?? 0); ?></strong>
            <?php esc_html_e('total', 'apollo-email'); ?>
        </span>
        <span class="stat-item stat-sent">
            <strong><?php echo esc_html($stats['sent'] ?? 0); ?></strong>
            <?php esc_html_e('enviados', 'apollo-email'); ?>
        </span>
        <span class="stat-item stat-failed">
            <strong><?php echo esc_html($stats['failed'] ?? 0); ?></strong>
            <?php esc_html_e('falhas', 'apollo-email'); ?>
        </span>
        <span class="stat-item stat-opened">
            <strong><?php echo esc_html(number_format_i18n(($stats['rate_open'] ?? 0), 1)); ?>%</strong>
            <?php esc_html_e('abertura', 'apollo-email'); ?>
        </span>
        <span class="stat-item stat-clicked">
            <strong><?php echo esc_html(number_format_i18n(($stats['rate_click'] ?? 0), 1)); ?>%</strong>
            <?php esc_html_e('cliques', 'apollo-email'); ?>
        </span>
    </div>

    <!-- Filters -->
    <form method="get" action="<?php echo esc_url($base_url); ?>" class="apollo-email-filters">
        <input type="hidden" name="page" value="apollo-email-log" />

        <select name="status">
            <option value=""><?php esc_html_e('Todos os status', 'apollo-email'); ?></option>
            <?php foreach ($status_labels as $k => $label) : ?>
                <option value="<?php echo esc_attr($k); ?>" <?php selected(sanitize_text_field($_GET['status'] ?? ''), $k); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="email" value="<?php echo esc_attr(sanitize_text_field($_GET['email'] ?? '')); ?>" class="apollo-input" placeholder="<?php esc_attr_e('Filtrar por e-mail', 'apollo-email'); ?>" class="regular-text" />

        <input type="text" name="template" value="<?php echo esc_attr(sanitize_text_field($_GET['template'] ?? '')); ?>" class="apollo-input" placeholder="<?php esc_attr_e('Filtrar por template', 'apollo-email'); ?>" />

        <button type="submit" class="button"><?php esc_html_e('Filtrar', 'apollo-email'); ?></button>

        <?php if (! empty($_GET['status']) || ! empty($_GET['email']) || ! empty($_GET['template'])) : ?>
            <a href="<?php echo esc_url($base_url); ?>" class="button"><?php esc_html_e('Limpar Filtros', 'apollo-email'); ?></a>
        <?php endif; ?>
    </form>

    <?php if (empty($entries_data)) : ?>
        <div class="apollo-email-empty">
            <span class="dashicons dashicons-clipboard"></span>
            <p><?php esc_html_e('Nenhum registro encontrado.', 'apollo-email'); ?></p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:40px;">ID</th>
                    <th><?php esc_html_e('Destinatário', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Assunto', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Template', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Tipo', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Status', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Transporte', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Enviado em', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Aberto', 'apollo-email'); ?></th>
                    <th><?php esc_html_e('Clique', 'apollo-email'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries_data as $entry) : ?>
                    <tr>
                        <td><?php echo esc_html($entry->id); ?></td>
                        <td><?php echo esc_html($entry->to_email); ?></td>
                        <td><?php echo esc_html(wp_trim_words($entry->subject, 8)); ?></td>
                        <td><?php echo $entry->template ? '<code>' . esc_html($entry->template) . '</code>' : '—'; ?></td>
                        <td>
                            <?php
                            $type_label = match ($entry->email_type ?? '') {
                                'transactional' => __('Transacional', 'apollo-email'),
                                'marketing'     => __('Marketing', 'apollo-email'),
                                'digest'        => __('Digest', 'apollo-email'),
                                default         => '—',
                            };
                            echo esc_html($type_label);
                            ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo esc_attr($entry->status); ?>">
                                <?php echo esc_html($status_labels[$entry->status] ?? ucfirst($entry->status)); ?>
                            </span>
                        </td>
                        <td><code><?php echo esc_html($entry->transport ?? 'wp_mail'); ?></code></td>
                        <td><?php echo esc_html($entry->sent_at ? wp_date('d/m/Y H:i', strtotime($entry->sent_at)) : '—'); ?></td>
                        <td><?php echo esc_html($entry->opened_at ? wp_date('d/m H:i', strtotime($entry->opened_at)) : '—'); ?></td>
                        <td><?php echo esc_html($entry->clicked_at ? wp_date('d/m H:i', strtotime($entry->clicked_at)) : '—'); ?></td>
                    </tr>
                    <?php if (! empty($entry->error_message)) : ?>
                        <tr class="error-detail-row">
                            <td></td>
                            <td colspan="9">
                                <span class="error-detail"><?php echo esc_html($entry->error_message); ?></span>
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
                        <?php echo esc_html(sprintf(__('%d registros', 'apollo-email'), $total)); ?>
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
        <h3><?php esc_html_e('Limpar Log', 'apollo-email'); ?></h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="apollo-email-purge-form">
            <input type="hidden" name="action" value="apollo_email_purge_log" />
            <?php wp_nonce_field('apollo_email_purge_log'); ?>
            <p>
                <label>
                    <?php esc_html_e('Remover registros mais antigos que', 'apollo-email'); ?>
                    <input type="number" name="purge_days" value="90" min="1" max="365" class="apollo-input small-text" />
                    <?php esc_html_e('dias', 'apollo-email'); ?>
                </label>
                <button type="submit" class="button" onclick="return confirm('<?php echo esc_js(__('Tem certeza?', 'apollo-email')); ?>');">
                    <?php esc_html_e('Limpar', 'apollo-email'); ?>
                </button>
            </p>
        </form>
    </div>
</div>