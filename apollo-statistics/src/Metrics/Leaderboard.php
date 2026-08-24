<?php
/**
 * Leaderboard — ranks users by a metric.
 *
 * Instances: user_online_ranking, dj_popularity, chat_active_users.
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

class Leaderboard extends MetricGroup {

    protected string $slug        = 'leaderboard';
    protected string $label       = 'Leaderboard';
    protected string $description = 'Ranks users by accumulated metric.';
    protected string $icon        = 'ri-trophy-line';
    protected int    $priority    = 2;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'table', 'library' => 'html');
    protected array  $tables      = array('apollo_stats_users');

    protected string $table_name  = 'apollo_stats_users';
    protected string $metric_type = '';
    protected string $where       = '';

    /** Use user_meta instead of stats table. */
    protected string $meta_key = '';

    public function with_config(string $slug, string $label, string $table = '', string $metric_type = '', string $where = '', string $meta_key = ''): static {
        $clone = clone $this;
        $clone->slug        = $slug;
        $clone->label       = $label;
        if ($table !== '') {
            $clone->table_name = $table;
        }
        $clone->metric_type = $metric_type;
        $clone->where       = $where;
        $clone->meta_key    = $meta_key;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $limit = $this->resolve_limit($args);

        // Meta-based leaderboard (e.g. '_apollo_online_minutes').
        if ($this->meta_key !== '') {
            return $this->compute_from_meta($limit);
        }

        $agg   = $this->aggregator();
        $since = $this->resolve_since($args);
        $table = $agg->table($this->table_name);
        $db    = $agg->db();

        $where = $db->prepare('recorded_date >= %s', $since) . ' AND user_id > 0';
        if ($this->metric_type !== '') {
            $where .= $db->prepare(' AND metric_type = %s', $this->metric_type);
        }
        if ($this->where !== '') {
            $where .= " AND ({$this->where})";
        }

        $items = $agg->top_n($table, 'user_id', 'metric_value', $limit, $where);

        // Enrich with display names.
        foreach ($items as &$item) {
            $user = get_userdata((int) $item['id']);
            $item['display_name'] = $user ? $user->display_name : '(deleted)';
            $item['avatar']       = $user ? get_avatar_url((int) $item['id'], array('size' => 40)) : '';
        }

        return array('items' => $items);
    }

    private function compute_from_meta(int $limit): array {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT um.user_id AS id, CAST(um.meta_value AS UNSIGNED) AS total
             FROM {$wpdb->usermeta} um
             INNER JOIN {$wpdb->users} u ON u.ID = um.user_id
             WHERE um.meta_key = %s AND um.meta_value > 0
             ORDER BY total DESC
             LIMIT %d",
            $this->meta_key,
            $limit
        ), ARRAY_A) ?: array();

        foreach ($rows as &$row) {
            $user = get_userdata((int) $row['id']);
            $row['display_name'] = $user ? $user->display_name : '(deleted)';
            $row['avatar']       = $user ? get_avatar_url((int) $row['id'], array('size' => 40)) : '';
        }

        return array('items' => $rows);
    }
}
