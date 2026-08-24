<?php
/**
 * MetricConnector — plug-and-play configuration layer.
 *
 * Allows admin to bind MetricGroup instances to specific CPTs,
 * DB tables, meta keys, or custom data sources at runtime.
 *
 * @package Apollo\Statistics\Core
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class MetricConnector {

    private static ?self $instance = null;

    /** @var array<string, array> Cached connections: metric_slug => config */
    private ?array $cache = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /* ──────────── Connection Management ──────────── */

    /**
     * Bind a metric to a specific data source.
     *
     * @param string $metric_slug MetricGroup slug.
     * @param array  $config {
     *     @type string $cpt         CPT slug to connect.
     *     @type string $table       DB table name (without prefix).
     *     @type string $meta_key    Meta key to aggregate.
     *     @type string $taxonomy    Taxonomy for distribution.
     *     @type string $hook        Hook name to listen.
     *     @type array  $params      Extra parameters.
     * }
     */
    public function connect(string $metric_slug, array $config): void {
        $connections                = $this->get_all();
        $connections[$metric_slug] = $this->sanitize_config($config);

        $this->save($connections);
    }

    /**
     * Remove a connection.
     */
    public function disconnect(string $metric_slug): void {
        $connections = $this->get_all();
        unset($connections[$metric_slug]);
        $this->save($connections);
    }

    /* ──────────── Retrieval ──────────── */

    /**
     * Get all connections.
     *
     * @return array<string, array>
     */
    public function get_all(): array {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $settings    = get_option('apollo_admin_settings', array());
        $this->cache = $settings['statistics']['connections'] ?? array();

        return $this->cache;
    }

    /**
     * Get connection config for a specific metric.
     */
    public function get_for(string $metric_slug): array {
        $all = $this->get_all();
        return $all[$metric_slug] ?? array();
    }

    /**
     * Get all metrics connected to a CPT.
     *
     * @return array<string, array>
     */
    public function get_by_cpt(string $cpt): array {
        return array_filter(
            $this->get_all(),
            static fn(array $config): bool => ($config['cpt'] ?? '') === $cpt
        );
    }

    /**
     * Get all metrics connected to a DB table.
     *
     * @return array<string, array>
     */
    public function get_by_table(string $table): array {
        return array_filter(
            $this->get_all(),
            static fn(array $config): bool => ($config['table'] ?? '') === $table
        );
    }

    /* ──────────── Internals ──────────── */

    private function sanitize_config(array $config): array {
        $clean = array();

        if (! empty($config['cpt'])) {
            $clean['cpt'] = sanitize_key($config['cpt']);
        }
        if (! empty($config['table'])) {
            $clean['table'] = sanitize_key($config['table']);
        }
        if (! empty($config['meta_key'])) {
            $clean['meta_key'] = sanitize_key($config['meta_key']);
        }
        if (! empty($config['taxonomy'])) {
            $clean['taxonomy'] = sanitize_key($config['taxonomy']);
        }
        if (! empty($config['hook'])) {
            $clean['hook'] = sanitize_text_field($config['hook']);
        }
        if (! empty($config['params']) && is_array($config['params'])) {
            $clean['params'] = array_map('sanitize_text_field', $config['params']);
        }

        return $clean;
    }

    private function save(array $connections): void {
        $settings                            = get_option('apollo_admin_settings', array());
        $settings['statistics']              = $settings['statistics'] ?? array();
        $settings['statistics']['connections'] = $connections;

        update_option('apollo_admin_settings', $settings);
        $this->cache = $connections;
    }
}
