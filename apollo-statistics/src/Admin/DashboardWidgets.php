<?php
/**
 * DashboardWidgets — registers WP admin dashboard widgets.
 *
 * @package Apollo\Statistics\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Admin;

use Apollo\Statistics\Core\MetricRegistry;

if (! defined('ABSPATH')) {
    exit;
}

final class DashboardWidgets {

    public function init(): void {
        add_action('wp_dashboard_setup', array($this, 'register_widgets'));
    }

    public function register_widgets(): void {
        if (! current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            'apollo_stats_overview',
            'Apollo Statistics — Overview',
            array($this, 'render_overview')
        );
    }

    public function render_overview(): void {
        $registry = MetricRegistry::instance();
        $count    = $registry->count();
        $enabled  = count($registry->get_enabled());

        printf(
            '<p><strong>%d</strong> metrics registered, <strong>%d</strong> enabled.</p>',
            $count,
            $enabled
        );

        echo '<p><a href="' . esc_url(admin_url('admin.php?page=apollo-admin#statistics')) . '" class="button">Configure Metrics</a></p>';
    }
}
