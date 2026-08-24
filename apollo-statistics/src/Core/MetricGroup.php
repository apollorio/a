<?php
/**
 * Abstract MetricGroup — the core building block.
 *
 * Every analytics feature in the system is a MetricGroup instance.
 * Subclasses implement compute() with their specific aggregation logic.
 *
 * @package Apollo\Statistics\Core
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Core;

if (! defined('ABSPATH')) {
    exit;
}

abstract class MetricGroup {

    /* ──────────── Identity ──────────── */

    /** Unique slug, e.g. 'event_rsvp_funnel'. */
    protected string $slug = '';

    /** Human label, e.g. 'RSVP Funnel'. */
    protected string $label = '';

    /** Short description. */
    protected string $description = '';

    /** RemixIcon class, e.g. 'ri-funnel-line'. */
    protected string $icon = 'ri-bar-chart-2-line';

    /** Priority tier 1–10 (1 = highest). */
    protected int $priority = 5;

    /* ──────────── Data source ──────────── */

    /**
     * Source config:
     *  type   => 'db_table' | 'cpt_meta' | 'taxonomy' | 'user_meta' | 'rest_endpoint' | 'hook'
     *  target => table name, meta key, or hook name
     *  metric_key => column or meta field to aggregate
     *  group_by   => grouping dimension
     */
    protected array $source = array();

    /* ──────────── Display ──────────── */

    /**
     * Where this metric renders.
     * Possible values: 'admin_dashboard', 'frontend_panel', 'user_profile', 'cpt_single'
     */
    protected array $displays = array('admin_dashboard');

    /**
     * Chart config:
     *  type    => 'line' | 'bar' | 'donut' | 'funnel' | 'sparkline' | 'number' | 'table' | 'leaderboard'
     *  library => 'amcharts' | 'html'
     */
    protected array $chart = array(
        'type'    => 'line',
        'library' => 'amcharts',
    );

    /* ──────────── Dependencies ──────────── */

    /** Required plugin slugs, e.g. ['apollo-events']. */
    protected array $requires = array();

    /** CPT slugs this connects to. */
    protected array $cpts = array();

    /** Apollo tables read by this metric. */
    protected array $tables = array();

    /* ──────────── Admin config ──────────── */

    /** Whether the admin can toggle this on/off. */
    protected bool $configurable = true;

    /** Whether enabled by default on fresh install. */
    protected bool $default_enabled = true;

    /* ──────────── Core methods ──────────── */

    /**
     * Compute the metric data.
     *
     * @param array $args {
     *     @type int    $days   Period in days.
     *     @type string $since  Date string Y-m-d.
     *     @type int    $limit  Max items.
     *     @type int    $user_id Filter by user (optional).
     *     @type int    $object_id Filter by object (optional).
     * }
     * @return array Computed data in this metric's format.
     */
    abstract public function compute(array $args = array()): array;

    /**
     * Returns the JSON-serialisable schema describing this metric
     * for admin configuration / REST responses.
     */
    public function get_schema(): array {
        return array(
            'slug'            => $this->slug,
            'label'           => $this->label,
            'description'     => $this->description,
            'icon'            => $this->icon,
            'priority'        => $this->priority,
            'source'          => $this->source,
            'displays'        => $this->displays,
            'chart'           => $this->chart,
            'requires'        => $this->requires,
            'cpts'            => $this->cpts,
            'tables'          => $this->tables,
            'configurable'    => $this->configurable,
            'default_enabled' => $this->default_enabled,
        );
    }

    /**
     * Check whether all plugin dependencies for this metric are satisfied.
     */
    public function is_available(): bool {
        if (empty($this->requires)) {
            return true;
        }

        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach ($this->requires as $plugin_slug) {
            $file = $plugin_slug . '/' . $plugin_slug . '.php';
            if (! is_plugin_active($file)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Render-ready data: runs compute() and formats for chart library.
     */
    public function render_data(array $args = array()): array {
        if (! $this->is_available()) {
            return array(
                'slug'   => $this->slug,
                'status' => 'unavailable',
                'reason' => 'Missing dependency: ' . implode(', ', $this->requires),
            );
        }

        $data = $this->compute($args);

        return array(
            'slug'   => $this->slug,
            'label'  => $this->label,
            'icon'   => $this->icon,
            'chart'  => $this->chart,
            'status' => 'ok',
            'data'   => $data,
        );
    }

    /* ──────────── Getters ──────────── */

    public function get_slug(): string {
        return $this->slug;
    }

    public function get_label(): string {
        return $this->label;
    }

    public function get_priority(): int {
        return $this->priority;
    }

    public function get_displays(): array {
        return $this->displays;
    }

    public function get_cpts(): array {
        return $this->cpts;
    }

    public function is_configurable(): bool {
        return $this->configurable;
    }

    public function is_default_enabled(): bool {
        return $this->default_enabled;
    }

    /* ──────────── Helpers ──────────── */

    /**
     * Resolve 'days' → 'since' date string.
     */
    protected function resolve_since(array $args): string {
        if (! empty($args['since'])) {
            return $args['since'];
        }
        $days = isset($args['days']) ? absint($args['days']) : 30;
        return wp_date('Y-m-d', strtotime("-{$days} days")) ?: gmdate('Y-m-d', strtotime("-{$days} days"));
    }

    /**
     * Resolve pagination limit.
     */
    protected function resolve_limit(array $args): int {
        return isset($args['limit']) ? min(absint($args['limit']), 200) : 50;
    }

    /**
     * Get the DataAggregator singleton.
     */
    protected function aggregator(): DataAggregator {
        return DataAggregator::instance();
    }
}
