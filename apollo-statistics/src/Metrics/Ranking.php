<?php
/**
 * Ranking — ranks items by any numeric metric.
 *
 * Instances: hub_block_performance, fav_rankings, wow_rankings, doc_downloads.
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

class Ranking extends MetricGroup {

    protected string $slug        = 'ranking';
    protected string $label       = 'Ranking';
    protected string $description = 'Ranks items by a numeric metric.';
    protected string $icon        = 'ri-sort-desc';
    protected int    $priority    = 3;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'table', 'library' => 'html');

    protected string $table_name  = 'apollo_stats_content';
    protected string $id_col      = 'post_id';
    protected string $value_col   = 'metric_value';
    protected string $metric_type = 'view';

    public function with_config(string $slug, string $label, string $table, string $id_col, string $value_col, string $metric_type = ''): static {
        $clone = clone $this;
        $clone->slug        = $slug;
        $clone->label       = $label;
        $clone->table_name  = $table;
        $clone->id_col      = $id_col;
        $clone->value_col   = $value_col;
        $clone->metric_type = $metric_type;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $table = $agg->table($this->table_name);
        $limit = $this->resolve_limit($args);

        $where = '';
        if ($this->metric_type !== '') {
            $where = $agg->db()->prepare('metric_type = %s', $this->metric_type);
        }

        return array(
            'items' => $agg->top_n($table, $this->id_col, $this->value_col, $limit, $where),
        );
    }
}
