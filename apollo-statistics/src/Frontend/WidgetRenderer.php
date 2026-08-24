<?php
/**
 * WidgetRenderer — renders MetricGroup data as HTML cards with chart hooks.
 *
 * Produces the HTML scaffold that admin-charts.js auto-initializes via
 * data-apollo-chart attributes. Used by DashboardWidgets and ProfileStats.
 *
 * @package Apollo\Statistics\Frontend
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Frontend;

use Apollo\Statistics\Core\MetricGroup;
use Apollo\Statistics\Core\MetricRegistry;

if (! defined('ABSPATH')) {
    exit;
}

final class WidgetRenderer {

    /**
     * Render a single metric as an HTML card.
     *
     * @param MetricGroup $metric The metric instance.
     * @param array       $args   Compute args for ChartAdapter.
     * @return string HTML output.
     */
    public static function render_card(MetricGroup $metric, array $args = array()): string {
        $adapted  = ChartAdapter::adapt($metric, $args);
        $slug     = esc_attr($adapted['slug'] ?? $metric->get_slug());
        $label    = esc_html($adapted['label'] ?? $metric->get_label());
        $icon     = esc_attr($adapted['icon'] ?? 'ri-bar-chart-2-line');
        $type     = esc_attr($adapted['chart_type'] ?? 'time_series');
        $status   = $adapted['status'] ?? 'ok';
        $chart_id = 'apollo-chart-' . $slug;

        $data_attr = '';
        if ($status === 'ok' && ! empty($adapted['data'])) {
            $data_attr = ' data-chart-payload="' . esc_attr(wp_json_encode($adapted['data'])) . '"';
        }

        ob_start();
        ?>
        <div class="apollo-stat-card" data-metric="<?php echo $slug; ?>" data-priority="<?php echo esc_attr((string) $metric->get_priority()); ?>">
            <div class="apollo-stat-card__header">
                <i class="<?php echo $icon; ?>"></i>
                <h3><?php echo $label; ?></h3>
            </div>
            <div class="apollo-stat-card__body">
                <?php if ($status === 'unavailable') : ?>
                    <p class="apollo-stat-card__unavailable">
                        <?php echo esc_html($adapted['reason'] ?? 'Metric unavailable.'); ?>
                    </p>
                <?php elseif ($type === 'number') : ?>
                    <?php self::render_number_card($adapted['data'] ?? array()); ?>
                <?php elseif ($type === 'table' || $type === 'leaderboard') : ?>
                    <?php self::render_table_card($adapted['data'] ?? array()); ?>
                <?php else : ?>
                    <div id="<?php echo esc_attr($chart_id); ?>"
                         class="apollo-chart-container"
                         data-apollo-chart="<?php echo $slug; ?>"
                         data-chart-type="<?php echo $type; ?>"
                         <?php echo $data_attr; ?>
                         style="width:100%;min-height:240px;"></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean() ?: '';
    }

    /**
     * Render a grid of metric cards for a given display context.
     *
     * @param string $display  Display location: 'admin_dashboard', 'user_profile', 'frontend_panel'.
     * @param array  $args     Compute args passed to each metric.
     * @return string HTML grid.
     */
    public static function render_grid(string $display, array $args = array()): string {
        $registry = MetricRegistry::instance();
        $metrics  = $registry->get_by_display($display);

        // Filter to enabled + available only.
        $enabled = array();
        foreach ($metrics as $slug => $metric) {
            if ($registry->is_enabled($slug) && $metric->is_available()) {
                $enabled[$slug] = $metric;
            }
        }

        // Sort by priority.
        uasort($enabled, static fn(MetricGroup $a, MetricGroup $b): int => $a->get_priority() <=> $b->get_priority());

        if (empty($enabled)) {
            return '<p class="apollo-stats-empty">' . esc_html__('No statistics available.', 'apollo-statistics') . '</p>';
        }

        ob_start();
        ?>
        <div class="apollo-stats-grid">
            <?php foreach ($enabled as $metric) : ?>
                <?php echo self::render_card($metric, $args); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean() ?: '';
    }

    /**
     * Render a number card (big value + delta).
     */
    private static function render_number_card(array $data): void {
        $value = number_format_i18n((int) ($data['value'] ?? 0));
        $delta = (float) ($data['delta'] ?? 0);
        $delta_label = esc_html((string) ($data['delta_label'] ?? ''));
        $delta_class = $delta > 0 ? 'positive' : ($delta < 0 ? 'negative' : 'neutral');
        $delta_icon  = $delta > 0 ? 'ri-arrow-up-line' : ($delta < 0 ? 'ri-arrow-down-line' : 'ri-subtract-line');
        ?>
        <div class="apollo-number-card">
            <span class="apollo-number-card__value"><?php echo esc_html($value); ?></span>
            <?php if ($delta !== 0.0) : ?>
                <span class="apollo-number-card__delta apollo-number-card__delta--<?php echo $delta_class; ?>">
                    <i class="<?php echo esc_attr($delta_icon); ?>"></i>
                    <?php echo esc_html(number_format_i18n(abs($delta), 1) . '%'); ?>
                    <?php if ($delta_label) : ?>
                        <small><?php echo $delta_label; ?></small>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render a table / leaderboard card.
     */
    private static function render_table_card(array $data): void {
        $items = $data['items'] ?? array();
        if (empty($items)) {
            echo '<p class="apollo-stat-card__empty">' . esc_html__('No data yet.', 'apollo-statistics') . '</p>';
            return;
        }
        ?>
        <table class="apollo-stat-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php esc_html_e('Name', 'apollo-statistics'); ?></th>
                    <th><?php esc_html_e('Value', 'apollo-statistics'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($items, 0, 20) as $i => $item) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ($i + 1)); ?></td>
                        <td><?php echo esc_html((string) ($item['label'] ?? '')); ?></td>
                        <td><?php echo esc_html(number_format_i18n((int) ($item['value'] ?? 0))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
