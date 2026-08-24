<?php
/**
 * ViewCounter — counts views for any CPT or URL.
 *
 * Instances: user_pageviews, hub_views, event_views, classified_views, journal_readership.
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

class ViewCounter extends MetricGroup {

    protected string $slug        = 'view_counter';
    protected string $label       = 'View Counter';
    protected string $description = 'Counts views for a CPT or URL over time.';
    protected string $icon        = 'ri-eye-line';
    protected int    $priority    = 1;
    protected array  $displays    = array('admin_dashboard', 'user_profile');
    protected array  $chart       = array('type' => 'line', 'library' => 'amcharts');

    /** CPT to filter on (e.g. 'event', 'hub'). Empty = all. */
    protected string $post_type = '';

    /** Table to query. */
    protected string $table_name = 'apollo_stats_content';

    public function with_post_type(string $post_type): static {
        $clone = clone $this;
        $clone->post_type = $post_type;
        $clone->slug      = $post_type . '_views';
        $clone->label     = ucfirst($post_type) . ' Views';
        $clone->cpts      = array($post_type);
        return $clone;
    }

    public function with_table(string $table): static {
        $clone = clone $this;
        $clone->table_name = $table;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $since = $this->resolve_since($args);
        $table = $agg->table($this->table_name);
        $db    = $agg->db();

        $where = "metric_type = 'view'";
        if ($this->post_type !== '') {
            $where .= $db->prepare(' AND post_type = %s', $this->post_type);
        }
        if (! empty($args['user_id'])) {
            // For user profile: use pageviews table instead.
            $pv_table = $agg->table('apollo_stats_pageviews');
            return array(
                'time_series' => $agg->count_by_date($pv_table, 'recorded_at', $since, '', 'user_id = ' . absint($args['user_id'])),
                'total'       => $agg->single_value_prepared($pv_table, 'COUNT(*)', 'user_id = %d AND recorded_at >= %s', array(absint($args['user_id']), $since)),
            );
        }

        return array(
            'time_series' => $agg->sum_by_date($table, 'metric_value', 'recorded_date', $since, '', $where),
            'total'       => $agg->single_value_prepared($table, 'COALESCE(SUM(metric_value), 0)', $where . ' AND recorded_date >= %s', array($since)),
        );
    }
}
