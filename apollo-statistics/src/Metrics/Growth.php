<?php
/**
 * Growth — tracks growth of a metric over time (members, subscribers, posts).
 *
 * Instances: group_growth, connection_network.
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

class Growth extends MetricGroup {

    protected string $slug        = 'growth';
    protected string $label       = 'Growth';
    protected string $description = 'Tracks growth of a metric over time.';
    protected string $icon        = 'ri-arrow-up-line';
    protected int    $priority    = 3;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'line', 'library' => 'amcharts');

    protected string $table_name = '';
    protected string $date_col   = 'created_at';
    protected string $where      = '';

    public function with_config(string $slug, string $label, string $table, string $date_col, string $where = ''): static {
        $clone = clone $this;
        $clone->slug       = $slug;
        $clone->label      = $label;
        $clone->table_name = $table;
        $clone->date_col   = $date_col;
        $clone->where      = $where;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $since = $this->resolve_since($args);
        $table = $agg->table($this->table_name);
        $db    = $agg->db();

        // Daily new additions.
        $daily = $agg->count_by_date($table, $this->date_col, $since, '', $this->where);

        // Cumulative total.
        $total = $agg->single_value($table, 'COUNT(*)', $this->where !== '' ? $this->where : '1=1');

        // Growth rate: compare last 7d vs previous 7d.
        $recent_since = gmdate('Y-m-d', strtotime('-7 days'));
        $prev_since   = gmdate('Y-m-d', strtotime('-14 days'));
        $prev_until   = gmdate('Y-m-d', strtotime('-8 days'));

        $recent_where = $db->prepare($this->date_col . ' >= %s', $recent_since);
        $prev_where   = $db->prepare($this->date_col . ' >= %s AND ' . $this->date_col . ' <= %s', $prev_since, $prev_until);
        if ($this->where !== '') {
            $recent_where .= ' AND (' . $this->where . ')';
            $prev_where   .= ' AND (' . $this->where . ')';
        }

        $recent_count = $agg->single_value($table, 'COUNT(*)', $recent_where);
        $prev_count   = $agg->single_value($table, 'COUNT(*)', $prev_where);
        $growth_pct   = $prev_count > 0 ? round((($recent_count - $prev_count) / $prev_count) * 100, 1) : 0;

        return array(
            'daily'       => $daily,
            'total'       => $total,
            'growth_pct'  => $growth_pct,
            'recent_7d'   => $recent_count,
            'previous_7d' => $prev_count,
        );
    }
}
