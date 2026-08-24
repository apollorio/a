<?php
/**
 * Distribution — breakdown by a categorical column.
 *
 * Instances: chat_types, classified_categories, journal_taxonomy_map,
 * dj_sound_map, loc_area_heatmap, group_type_comparison, wow_emoji_distribution,
 * sound_preferences, embed_distribution.
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

class Distribution extends MetricGroup {

    protected string $slug        = 'distribution';
    protected string $label       = 'Distribution';
    protected string $description = 'Breakdown by a categorical dimension.';
    protected string $icon        = 'ri-pie-chart-line';
    protected int    $priority    = 4;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'donut', 'library' => 'amcharts');

    protected string $table_name = 'apollo_stats_content';
    protected string $group_col  = 'post_type';
    protected string $where      = '';

    public function with_config(string $slug, string $label, string $table, string $group_col, string $where = ''): static {
        $clone = clone $this;
        $clone->slug       = $slug;
        $clone->label      = $label;
        $clone->table_name = $table;
        $clone->group_col  = $group_col;
        $clone->where      = $where;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $table = $agg->table($this->table_name);
        $limit = $this->resolve_limit($args);

        return array(
            'categories' => $agg->count_by_group($table, $this->group_col, $this->where, $limit),
        );
    }
}
