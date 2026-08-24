<?php
/**
 * DataAggregator — shared SQL query builder for all MetricGroups.
 *
 * Provides reusable, secure aggregation queries used across
 * multiple MetricGroup subclasses. All queries use $wpdb->prepare().
 *
 * @package Apollo\Statistics\Core
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class DataAggregator {

    private static ?self $instance = null;

    /** @var \wpdb */
    private \wpdb $db;

    /** @var string */
    private string $prefix;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->db     = $wpdb;
        $this->prefix = $wpdb->prefix;
    }

    /* ══════════════════════════════════════════════════════════
       TIME SERIES — metric values grouped by date
       ══════════════════════════════════════════════════════════ */

    /**
     * Sum a numeric column grouped by date.
     *
     * @param string $table     Full table name (with prefix).
     * @param string $value_col Column to SUM.
     * @param string $date_col  Date column name.
     * @param string $since     Start date Y-m-d.
     * @param string $until     End date Y-m-d (default: today).
     * @param string $where     Extra WHERE clause (already prepared or safe).
     * @return array<int, array{date: string, value: int}>
     */
    public function sum_by_date(
        string $table,
        string $value_col,
        string $date_col,
        string $since,
        string $until = '',
        string $where = ''
    ): array {
        $until = $until ?: current_time('Y-m-d');

        $sql = "SELECT DATE({$date_col}) AS date, COALESCE(SUM({$value_col}), 0) AS value
                FROM {$table}
                WHERE {$date_col} >= %s AND {$date_col} <= %s";

        if ($where !== '') {
            $sql .= " AND ({$where})";
        }
        $sql .= " GROUP BY DATE({$date_col}) ORDER BY date ASC";

        $results = $this->db->get_results(
            $this->db->prepare($sql, $since, $until . ' 23:59:59'),
            ARRAY_A
        );

        return $results ?: array();
    }

    /**
     * Count rows grouped by date.
     */
    public function count_by_date(
        string $table,
        string $date_col,
        string $since,
        string $until = '',
        string $where = ''
    ): array {
        $until = $until ?: current_time('Y-m-d');

        $sql = "SELECT DATE({$date_col}) AS date, COUNT(*) AS value
                FROM {$table}
                WHERE {$date_col} >= %s AND {$date_col} <= %s";

        if ($where !== '') {
            $sql .= " AND ({$where})";
        }
        $sql .= " GROUP BY DATE({$date_col}) ORDER BY date ASC";

        $results = $this->db->get_results(
            $this->db->prepare($sql, $since, $until . ' 23:59:59'),
            ARRAY_A
        );

        return $results ?: array();
    }

    /* ══════════════════════════════════════════════════════════
       DISTRIBUTION — count grouped by a category column
       ══════════════════════════════════════════════════════════ */

    /**
     * Count rows grouped by a categorical column.
     *
     * @return array<int, array{category: string, value: int}>
     */
    public function count_by_group(
        string $table,
        string $group_col,
        string $where = '',
        int    $limit = 50
    ): array {
        $sql = "SELECT {$group_col} AS category, COUNT(*) AS value
                FROM {$table}";

        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        $sql .= " GROUP BY {$group_col} ORDER BY value DESC LIMIT %d";

        $results = $this->db->get_results(
            $this->db->prepare($sql, $limit),
            ARRAY_A
        );

        return $results ?: array();
    }

    /**
     * Sum a value column grouped by a categorical column.
     *
     * @return array<int, array{category: string, value: int}>
     */
    public function sum_by_group(
        string $table,
        string $value_col,
        string $group_col,
        string $where = '',
        int    $limit = 50
    ): array {
        $sql = "SELECT {$group_col} AS category, COALESCE(SUM({$value_col}), 0) AS value
                FROM {$table}";

        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        $sql .= " GROUP BY {$group_col} ORDER BY value DESC LIMIT %d";

        $results = $this->db->get_results(
            $this->db->prepare($sql, $limit),
            ARRAY_A
        );

        return $results ?: array();
    }

    /* ══════════════════════════════════════════════════════════
       RANKING — top N items by a metric
       ══════════════════════════════════════════════════════════ */

    /**
     * Get top N items ranked by sum of a value column.
     *
     * @param string $table      Full table name.
     * @param string $id_col     Item ID column.
     * @param string $value_col  Column to SUM for ranking.
     * @param int    $n          Number of items.
     * @param string $where      Extra WHERE.
     * @param string $join       Extra JOIN clause.
     * @param string $select_extra Additional SELECT columns.
     * @return array
     */
    public function top_n(
        string $table,
        string $id_col,
        string $value_col,
        int    $n = 10,
        string $where = '',
        string $join = '',
        string $select_extra = ''
    ): array {
        $select = "t.{$id_col} AS id, COALESCE(SUM(t.{$value_col}), 0) AS total";
        if ($select_extra !== '') {
            $select .= ', ' . $select_extra;
        }

        $sql = "SELECT {$select} FROM {$table} t";

        if ($join !== '') {
            $sql .= ' ' . $join;
        }

        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        $sql .= " GROUP BY t.{$id_col} ORDER BY total DESC LIMIT %d";

        $results = $this->db->get_results(
            $this->db->prepare($sql, $n),
            ARRAY_A
        );

        return $results ?: array();
    }

    /* ══════════════════════════════════════════════════════════
       FUNNEL — staged conversion counts
       ══════════════════════════════════════════════════════════ */

    /**
     * Count items at each funnel stage.
     *
     * @param string $table       Full table name.
     * @param string $stage_col   Column containing the stage value.
     * @param array  $stages      Ordered array of stage values, e.g. ['interested', 'maybe', 'going'].
     * @param string $where       Extra WHERE.
     * @return array<int, array{stage: string, count: int}>
     */
    public function funnel_stages(
        string $table,
        string $stage_col,
        array  $stages,
        string $where = ''
    ): array {
        if (empty($stages)) {
            return array();
        }

        $placeholders = implode(',', array_fill(0, count($stages), '%s'));

        $sql = "SELECT {$stage_col} AS stage, COUNT(*) AS count
                FROM {$table}
                WHERE {$stage_col} IN ({$placeholders})";

        if ($where !== '') {
            $sql .= " AND ({$where})";
        }

        $sql .= " GROUP BY {$stage_col}";

        $results = $this->db->get_results(
            $this->db->prepare($sql, ...$stages),
            ARRAY_A
        );

        // Re-order results to match the stages array order.
        $map = array();
        foreach ($results ?: array() as $row) {
            $map[$row['stage']] = (int) $row['count'];
        }

        $ordered = array();
        foreach ($stages as $stage) {
            $ordered[] = array(
                'stage' => $stage,
                'count' => $map[$stage] ?? 0,
            );
        }

        return $ordered;
    }

    /* ══════════════════════════════════════════════════════════
       SINGLE VALUES
       ══════════════════════════════════════════════════════════ */

    /**
     * Get a single aggregated value.
     */
    public function single_value(
        string $table,
        string $expression,
        string $where = ''
    ): int {
        $sql = "SELECT {$expression} FROM {$table}";
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        return (int) ($this->db->get_var($sql) ?? 0); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Get a single aggregated value with prepared params.
     *
     * @param string $table
     * @param string $expression  e.g. "COALESCE(SUM(metric_value), 0)"
     * @param string $where       Parameterised WHERE clause with %s/%d placeholders.
     * @param array  $params      Values for the placeholders.
     */
    public function single_value_prepared(
        string $table,
        string $expression,
        string $where,
        array  $params
    ): int {
        $sql = "SELECT {$expression} FROM {$table} WHERE {$where}";

        return (int) ($this->db->get_var(
            $this->db->prepare($sql, ...$params)
        ) ?? 0);
    }

    /* ══════════════════════════════════════════════════════════
       AVERAGES & COMPARISONS
       ══════════════════════════════════════════════════════════ */

    /**
     * Average of a column.
     */
    public function average(
        string $table,
        string $col,
        string $where = ''
    ): float {
        $sql = "SELECT COALESCE(AVG({$col}), 0) FROM {$table}";
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        return (float) ($this->db->get_var($sql) ?? 0.0); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Compare an item's metric to the average.
     *
     * @return array{item_value: float, average: float, ratio: float}
     */
    public function compare_to_average(
        string $table,
        string $value_col,
        string $group_col,
        string $item_id,
        string $where = ''
    ): array {
        // Item value.
        $item_where = "{$group_col} = %s";
        if ($where !== '') {
            $item_where .= " AND ({$where})";
        }

        $item_value = (float) ($this->db->get_var(
            $this->db->prepare(
                "SELECT COALESCE(SUM({$value_col}), 0) FROM {$table} WHERE {$item_where}",
                $item_id
            )
        ) ?? 0.0);

        // Global average.
        $avg_sql = "SELECT COALESCE(AVG(sub.total), 0) FROM (
            SELECT SUM({$value_col}) AS total FROM {$table}";
        if ($where !== '') {
            $avg_sql .= " WHERE {$where}";
        }
        $avg_sql .= " GROUP BY {$group_col}) sub";

        $global_avg = (float) ($this->db->get_var($avg_sql) ?? 0.0); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return array(
            'item_value' => $item_value,
            'average'    => $global_avg,
            'ratio'      => $global_avg > 0 ? round($item_value / $global_avg, 2) : 0.0,
        );
    }

    /* ══════════════════════════════════════════════════════════
       UTILITIES
       ══════════════════════════════════════════════════════════ */

    /**
     * Get the full table name with WP prefix.
     */
    public function table(string $name): string {
        return $this->prefix . $name;
    }

    /**
     * Get the wpdb instance (for advanced queries).
     */
    public function db(): \wpdb {
        return $this->db;
    }
}
