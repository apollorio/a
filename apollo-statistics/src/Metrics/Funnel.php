<?php
/**
 * Funnel — multi-stage conversion tracking.
 *
 * Instances: event_rsvp_funnel, classified_conversion, doc_lifecycle,
 * registration_funnel, gestor_milestone_adherence.
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

class Funnel extends MetricGroup {

    protected string $slug        = 'funnel';
    protected string $label       = 'Funnel';
    protected string $description = 'Multi-stage conversion funnel.';
    protected string $icon        = 'ri-funnel-line';
    protected int    $priority    = 2;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'funnel', 'library' => 'amcharts');

    /** @var string[] Ordered stage values. */
    protected array $stages = array();

    protected string $table_name = '';
    protected string $stage_col  = 'status';
    protected string $where      = '';

    public function with_config(string $slug, string $label, string $table, string $stage_col, array $stages, string $where = ''): static {
        $clone = clone $this;
        $clone->slug       = $slug;
        $clone->label      = $label;
        $clone->table_name = $table;
        $clone->stage_col  = $stage_col;
        $clone->stages     = $stages;
        $clone->where      = $where;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $table = $agg->table($this->table_name);

        return array(
            'stages' => $agg->funnel_stages($table, $this->stage_col, $this->stages, $this->where),
        );
    }
}
