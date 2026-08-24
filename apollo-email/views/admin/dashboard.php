<?php

/**
 * Admin View: Dashboard.
 *
 * @var array $log_stats
 * @var array $queue_stats
 * @var array $daily_stats
 * @var int   $templates
 * @var array $settings
 */

if (! defined('ABSPATH')) {
    exit;
}

$test_sent  = isset($_GET['test_sent']) ? absint($_GET['test_sent']) : null;
$test_error = isset($_GET['test_error']) ? sanitize_text_field($_GET['test_error']) : '';
?>
<div class="wrap apollo-email-admin">
    <h1><?php esc_html_e('Email — Apollo', 'apollo-email'); ?></h1>

    <?php if ($test_sent === 1) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('E-mail de teste enviado com sucesso!', 'apollo-email'); ?></p>
        </div>
    <?php elseif ($test_sent === 0) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html(sprintf(__('Falha ao enviar e-mail de teste: %s', 'apollo-email'), $test_error)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="apollo-email-stats-grid">
        <div class="apollo-email-stat-card">
            <div class="stat-icon dashicons dashicons-email"></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($log_stats['total'] ?? 0); ?></span>
                <span class="stat-label"><?php esc_html_e('E-mails Enviados', 'apollo-email'); ?></span>
                <span class="stat-period"><?php esc_html_e('últimos 30 dias', 'apollo-email'); ?></span>
            </div>
        </div>

        <div class="apollo-email-stat-card stat-success">
            <div class="stat-icon dashicons dashicons-yes-alt"></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html(number_format_i18n(($log_stats['rate_open'] ?? 0), 1)); ?>%</span>
                <span class="stat-label"><?php esc_html_e('Taxa de Abertura', 'apollo-email'); ?></span>
                <span class="stat-period"><?php echo esc_html(($log_stats['opened'] ?? 0) . ' aberturas'); ?></span>
            </div>
        </div>

        <div class="apollo-email-stat-card stat-info">
            <div class="stat-icon dashicons dashicons-admin-links"></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html(number_format_i18n(($log_stats['rate_click'] ?? 0), 1)); ?>%</span>
                <span class="stat-label"><?php esc_html_e('Taxa de Cliques', 'apollo-email'); ?></span>
                <span class="stat-period"><?php echo esc_html(($log_stats['clicked'] ?? 0) . ' cliques'); ?></span>
            </div>
        </div>

        <div class="apollo-email-stat-card stat-warning">
            <div class="stat-icon dashicons dashicons-warning"></div>
            <div class="stat-content">
                <span class="stat-value"><?php echo esc_html($log_stats['failed'] ?? 0); ?></span>
                <span class="stat-label"><?php esc_html_e('Falhas', 'apollo-email'); ?></span>
                <span class="stat-period"><?php esc_html_e('últimos 30 dias', 'apollo-email'); ?></span>
            </div>
        </div>
    </div>

    <!-- Two-Column Layout -->
    <div class="apollo-email-dashboard-columns">

        <!-- Queue Status -->
        <div class="apollo-email-card">
            <h2><?php esc_html_e('Fila de Envio', 'apollo-email'); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <td><?php esc_html_e('Pendentes', 'apollo-email'); ?></td>
                        <td><span class="badge badge-pending"><?php echo esc_html($queue_stats['pending'] ?? 0); ?></span></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Processando', 'apollo-email'); ?></td>
                        <td><span class="badge badge-processing"><?php echo esc_html($queue_stats['processing'] ?? 0); ?></span></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Enviados', 'apollo-email'); ?></td>
                        <td><span class="badge badge-sent"><?php echo esc_html($queue_stats['sent'] ?? 0); ?></span></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Falhas', 'apollo-email'); ?></td>
                        <td><span class="badge badge-failed"><?php echo esc_html($queue_stats['failed'] ?? 0); ?></span></td>
                    </tr>
                </tbody>
            </table>
            <p class="card-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=apollo-email-queue')); ?>" class="button">
                    <?php esc_html_e('Ver Fila Completa', 'apollo-email'); ?>
                </a>
            </p>
        </div>

        <!-- Quick Info -->
        <div class="apollo-email-card">
            <h2><?php esc_html_e('Informações', 'apollo-email'); ?></h2>
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <td><?php esc_html_e('Transporte', 'apollo-email'); ?></td>
                        <td><code><?php echo esc_html($settings['transport'] ?? 'wp_mail'); ?></code></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Remetente', 'apollo-email'); ?></td>
                        <td><?php echo esc_html(($settings['from_name'] ?? 'Apollo') . ' <' . ($settings['from_email'] ?? get_option('admin_email')) . '>'); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Templates', 'apollo-email'); ?></td>
                        <td><?php echo esc_html($templates); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Rastreamento', 'apollo-email'); ?></td>
                        <td>
                            <?php if (! empty($settings['track_opens'])) : ?>
                                <span class="badge badge-sent"><?php esc_html_e('Aberturas', 'apollo-email'); ?></span>
                            <?php endif; ?>
                            <?php if (! empty($settings['track_clicks'])) : ?>
                                <span class="badge badge-sent"><?php esc_html_e('Cliques', 'apollo-email'); ?></span>
                            <?php endif; ?>
                            <?php if (empty($settings['track_opens']) && empty($settings['track_clicks'])) : ?>
                                <span class="badge badge-cancelled"><?php esc_html_e('Desativado', 'apollo-email'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily Chart -->
    <?php if (! empty($daily_stats)) : ?>
        <div class="apollo-email-card apollo-email-card-full">
            <h2><?php esc_html_e('Envios por Dia (últimos 14 dias)', 'apollo-email'); ?></h2>
            <div class="apollo-email-chart">
                <div class="chart-bars">
                    <?php
                    $max_value = max(array_column($daily_stats, 'total'));
                    $max_value = max($max_value, 1);
                    foreach ($daily_stats as $day) :
                        $height   = round(($day['total'] / $max_value) * 100);
                        $day_name = wp_date('d/m', strtotime($day['date']));
                    ?>
                        <div class="chart-bar-group">
                            <div class="chart-bar" style="height: <?php echo esc_attr($height); ?>%;" title="<?php echo esc_attr($day['total'] . ' emails'); ?>">
                                <span class="chart-bar-value"><?php echo esc_html($day['total']); ?></span>
                            </div>
                            <span class="chart-bar-label"><?php echo esc_html($day_name); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Test Email -->
    <div class="apollo-email-card apollo-email-card-full">
        <h2><?php esc_html_e('Enviar E-mail de Teste', 'apollo-email'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="apollo-email-test-form">
            <input type="hidden" name="action" value="apollo_email_test" />
            <?php wp_nonce_field('apollo_email_test_nonce'); ?>
            <p>
                <label for="test_email"><?php esc_html_e('Enviar para:', 'apollo-email'); ?></label>
                <input type="email" name="test_email" id="test_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" class="apollo-input regular-text" required />
                <button type="submit" class="button button-primary"><?php esc_html_e('Enviar Teste', 'apollo-email'); ?></button>
            </p>
        </form>
    </div>
</div>