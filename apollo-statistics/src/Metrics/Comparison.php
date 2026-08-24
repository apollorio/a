<?php
/**
 * Comparison — benchmarks an item vs the global average.
 *
 * Instances: hub_vs_average, event_venue_utilization, dj_event_impact, loc_utilization.
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

class Comparison extends MetricGroup {

    protected string $slug        = 'comparison';
    protected string $label       = 'Comparison';
    protected string $description = 'Benchmarks an item against the global average.';
    protected string $icon        = 'ri-scales-line';
    protected int    $priority    = 4;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'bar', 'library' => 'amcharts');

    protected string $table_name = 'apollo_stats_content';
    protected string $value_col  = 'metric_value';
    protected string $group_col  = 'post_id';
    protected string $where      = '';

    public function with_config(string $slug, string $label, string $table, string $value_col, string $group_col, string $where = ''): static {
        $clone = clone $this;
        $clone->slug       = $slug;
        $clone->label      = $label;
        $clone->table_name = $table;
        $clone->value_col  = $value_col;
        $clone->group_col  = $group_col;
        $clone->where      = $where;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $table = $agg->table($this->table_name);

        $object_id = ! empty($args['object_id']) ? (string) absint($args['object_id']) : '0';

        $result = $agg->compare_to_average(
            $table,
            $this->value_col,
            $this->group_col,
            $object_id,
            $this->where
        );

        return array(
            'item_value' => $result['item_value'],
            'average'    => $result['average'],
            'ratio'      => $result['ratio'],
            'label'      => $result['ratio'] >= 1.0 ? 'above_average' : 'below_average',
        );
    }
}
