<?php
/**
 * ClickTrack — tracks clicked URLs, navigation patterns, CTR.
 *
 * Instances: user_clicks, hub_link_clicks.
 *
 * @package Apollo\Statistics\Metrics
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Metrics;

use Apollo\Statistics\Core\MetricGroup;

if (! defined('ABSPATH')) {
    exit;
}

class ClickTrack extends MetricGroup {

    protected string $slug        = 'click_track';
    protected string $label       = 'Click Tracking';
    protected string $description = 'URL clicks, navigation patterns, and CTR analysis.';
    protected string $icon        = 'ri-cursor-line';
    protected int    $priority    = 3;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'table', 'library' => 'html');
    protected array  $tables      = array('apollo_stats_clicks');

    protected string $filter_col   = '';
    protected string $filter_value = '';

    public function with_filter(string $slug, string $label, string $col, string $value): static {
        $clone = clone $this;
        $clone->slug         = $slug;
        $clone->label        = $label;
        $clone->filter_col   = $col;
        $clone->filter_value = $value;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $since = $this->resolve_since($args);
        $limit = $this->resolve_limit($args);
        $table = $agg->table('apollo_stats_clicks');

        $db = $agg->db();

        $where = $db->prepare('recorded_at >= %s', $since);
        if ($this->filter_col !== '' && $this->filter_value !== '') {
            $where .= $db->prepare(' AND ' . esc_sql($this->filter_col) . ' = %s', $this->filter_value);
        }
        if (! empty($args['user_id'])) {
            $where .= ' AND user_id = ' . absint($args['user_id']);
        }

        // Top clicked targets.
        $top_targets = $db->get_results(
            $db->prepare(
                "SELECT target_url, COUNT(*) AS clicks
                 FROM {$table}
                 WHERE {$where}
                 GROUP BY target_url
                 ORDER BY clicks DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        ) ?: array();

        // Click volume over time.
        $daily = $agg->count_by_date($table, 'recorded_at', $since, '', $where);

        // Element type breakdown.
        $types = $agg->count_by_group($table, 'element_type', $where, 10);

        return array(
            'top_targets' => $top_targets,
            'daily'       => $daily,
            'types'       => $types,
        );
    }
}
