<?php
/**
 * MetricRegistry — singleton that holds all registered MetricGroup instances.
 *
 * @package Apollo\Statistics\Core
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class MetricRegistry {

    private static ?self $instance = null;

    /** @var array<string, MetricGroup> slug => MetricGroup */
    private array $metrics = array();

    /** @var array<string, bool> slug => enabled (cached from option) */
    private ?array $enabled_cache = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Allow plugins to register additional MetricGroups.
        add_action('apollo/statistics/register_metrics', array($this, 'fire_registration'), 10);
    }

    /**
     * Fire after all plugins loaded — lets other plugins add metrics.
     */
    public function fire_registration(): void {
        do_action('apollo/statistics/register', $this);
    }

    /* ──────────── Registration ──────────── */

    /**
     * Register a MetricGroup instance.
     */
    public function register(MetricGroup $metric): void {
        $this->metrics[$metric->get_slug()] = $metric;
    }

    /**
     * Register multiple MetricGroups at once.
     *
     * @param MetricGroup[] $metrics
     */
    public function register_many(array $metrics): void {
        foreach ($metrics as $metric) {
            $this->register($metric);
        }
    }

    /* ──────────── Retrieval ──────────── */

    /**
     * Get a single metric by slug.
     */
    public function get(string $slug): ?MetricGroup {
        return $this->metrics[$slug] ?? null;
    }

    /**
     * Get ALL registered metrics.
     *
     * @return array<string, MetricGroup>
     */
    public function get_all(): array {
        return $this->metrics;
    }

    /**
     * Get only enabled metrics (respects admin toggles).
     *
     * @return array<string, MetricGroup>
     */
    public function get_enabled(): array {
        $enabled_map = $this->get_enabled_map();

        return array_filter(
            $this->metrics,
            static function (MetricGroup $m) use ($enabled_map): bool {
                $slug = $m->get_slug();
                if (isset($enabled_map[$slug])) {
                    return (bool) $enabled_map[$slug];
                }
                return $m->is_default_enabled();
            }
        );
    }

    /**
     * Get metrics that connect to a specific CPT.
     *
     * @return array<string, MetricGroup>
     */
    public function get_by_cpt(string $cpt): array {
        return array_filter(
            $this->metrics,
            static fn(MetricGroup $m): bool => in_array($cpt, $m->get_cpts(), true)
        );
    }

    /**
     * Get metrics that render in a specific display location.
     *
     * @return array<string, MetricGroup>
     */
    public function get_by_display(string $display): array {
        return array_filter(
            $this->metrics,
            static fn(MetricGroup $m): bool => in_array($display, $m->get_displays(), true)
        );
    }

    /**
     * Get metrics sorted by priority (1 = first).
     *
     * @return MetricGroup[]
     */
    public function get_sorted(): array {
        $sorted = array_values($this->metrics);
        usort($sorted, static fn(MetricGroup $a, MetricGroup $b): int => $a->get_priority() <=> $b->get_priority());
        return $sorted;
    }

    /* ──────────── Admin toggles ──────────── */

    /**
     * Enable or disable a metric.
     */
    public function set_enabled(string $slug, bool $enabled): void {
        $map        = $this->get_enabled_map();
        $map[$slug] = $enabled;

        $settings               = get_option('apollo_admin_settings', array());
        $settings['statistics'] = $settings['statistics'] ?? array();
        $settings['statistics']['metric_toggles'] = $map;

        update_option('apollo_admin_settings', $settings);
        $this->enabled_cache = $map;
    }

    /**
     * Check if a metric is currently enabled.
     */
    public function is_enabled(string $slug): bool {
        $map = $this->get_enabled_map();

        if (isset($map[$slug])) {
            return (bool) $map[$slug];
        }

        $metric = $this->get($slug);
        return $metric !== null && $metric->is_default_enabled();
    }

    /**
     * Get the enabled/disabled map from stored settings.
     *
     * @return array<string, bool>
     */
    private function get_enabled_map(): array {
        if ($this->enabled_cache !== null) {
            return $this->enabled_cache;
        }

        $settings           = get_option('apollo_admin_settings', array());
        $this->enabled_cache = $settings['statistics']['metric_toggles'] ?? array();

        return $this->enabled_cache;
    }

    /* ──────────── Introspection ──────────── */

    /**
     * Return all metric schemas (for REST listing).
     */
    public function get_all_schemas(): array {
        $schemas = array();
        foreach ($this->metrics as $slug => $metric) {
            $schema            = $metric->get_schema();
            $schema['enabled'] = $this->is_enabled($slug);
            $schema['available'] = $metric->is_available();
            $schemas[]         = $schema;
        }
        return $schemas;
    }

    /**
     * Count registered metrics.
     */
    public function count(): int {
        return count($this->metrics);
    }
}
