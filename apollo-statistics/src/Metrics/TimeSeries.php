<?php
/**
 * TimeSeries — any metric plotted over time.
 *
 * Instances: event_timeline, chat_volume, feed_activity,
 * fav_trends, user_visit_graph, user_points_map, gestor_budget_burn,
 * chat_response_time, login_security.
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

class TimeSeries extends MetricGroup {

    protected string $slug        = 'time_series';
    protected string $label       = 'Time Series';
    protected string $description = 'Metric values plotted over time.';
    protected string $icon        = 'ri-line-chart-line';
    protected int    $priority    = 3;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'line', 'library' => 'amcharts');

    protected string $table_name  = 'apollo_stats_events';
    protected string $date_col    = 'recorded_date';
    protected string $value_col   = 'metric_value';
    protected string $where       = '';
    protected bool   $use_count   = false;

    public function with_config(string $slug, string $label, string $table, string $date_col, string $value_col = '', string $where = '', bool $use_count = false): static {
        $clone = clone $this;
        $clone->slug       = $slug;
        $clone->label      = $label;
        $clone->table_name = $table;
        $clone->date_col   = $date_col;
        $clone->value_col  = $value_col ?: 'metric_value';
        $clone->where      = $where;
        $clone->use_count  = $use_count;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $since = $this->resolve_since($args);
        $table = $agg->table($this->table_name);

        $where = $this->where;
        if (! empty($args['user_id'])) {
            $user_cond = 'user_id = ' . absint($args['user_id']);
            $where     = $where !== '' ? "({$where}) AND {$user_cond}" : $user_cond;
        }

        if ($this->use_count) {
            return array(
                'time_series' => $agg->count_by_date($table, $this->date_col, $since, '', $where),
            );
        }

        return array(
            'time_series' => $agg->sum_by_date($table, $this->value_col, $this->date_col, $since, '', $where),
        );
    }
}
