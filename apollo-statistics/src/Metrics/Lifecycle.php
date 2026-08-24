<?php
/**
 * Lifecycle — time between lifecycle stages (draft → published → signed).
 *
 * Instances: classified_lifecycle, gestor_task_velocity, sign_completion.
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

class Lifecycle extends MetricGroup {

    protected string $slug        = 'lifecycle';
    protected string $label       = 'Lifecycle';
    protected string $description = 'Time between lifecycle stages.';
    protected string $icon        = 'ri-route-line';
    protected int    $priority    = 4;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'funnel', 'library' => 'amcharts');

    protected string $table_name   = '';
    protected string $id_col       = 'id';
    protected string $start_col    = 'created_at';
    protected string $end_col      = 'completed_at';
    protected string $status_col   = 'status';
    protected array  $stage_order  = array();
    protected string $where        = '';

    public function with_config(string $slug, string $label, string $table, string $id_col, string $start_col, string $end_col, string $status_col, array $stages, string $where = ''): static {
        $clone = clone $this;
        $clone->slug        = $slug;
        $clone->label       = $label;
        $clone->table_name  = $table;
        $clone->id_col      = $id_col;
        $clone->start_col   = $start_col;
        $clone->end_col     = $end_col;
        $clone->status_col  = $status_col;
        $clone->stage_order = $stages;
        $clone->where       = $where;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $table = $agg->table($this->table_name);
        $db    = $agg->db();

        // Stage distribution (funnel).
        $funnel = array();
        if (! empty($this->stage_order)) {
            $funnel = $agg->funnel_stages($table, $this->status_col, $this->stage_order, $this->where);
        }

        // Average time from start to end.
        $where_complete = $this->end_col . ' IS NOT NULL';
        if ($this->where !== '') {
            $where_complete .= ' AND (' . $this->where . ')';
        }

        $avg_seconds = (float) ($db->get_var( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT AVG(TIMESTAMPDIFF(SECOND, {$this->start_col}, {$this->end_col}))
             FROM {$table}
             WHERE {$where_complete}"
        ) ?? 0);

        // Total completed.
        $completed = $agg->single_value($table, 'COUNT(*)', $where_complete);

        // Total in pipeline.
        $in_pipeline_where = $this->end_col . ' IS NULL';
        if ($this->where !== '') {
            $in_pipeline_where .= ' AND (' . $this->where . ')';
        }
        $in_pipeline = $agg->single_value($table, 'COUNT(*)', $in_pipeline_where);

        return array(
            'funnel'          => $funnel,
            'avg_duration_sec' => round($avg_seconds),
            'completed'       => $completed,
            'in_pipeline'     => $in_pipeline,
        );
    }
}
