<?php
/**
 * Session — session analytics (duration, pages, bounce rate, devices).
 *
 * Instances: user_sessions, journal_engagement.
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

class Session extends MetricGroup {

    protected string $slug        = 'session';
    protected string $label       = 'Session Analytics';
    protected string $description = 'Session duration, page depth, bounce rate, and device breakdown.';
    protected string $icon        = 'ri-timer-line';
    protected int    $priority    = 1;
    protected array  $displays    = array('admin_dashboard', 'user_profile');
    protected array  $chart       = array('type' => 'line', 'library' => 'amcharts');
    protected array  $tables      = array('apollo_stats_sessions');

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $since = $this->resolve_since($args);
        $table = $agg->table('apollo_stats_sessions');

        $db = $agg->db();

        $where = $db->prepare('started_at >= %s', $since);
        if (! empty($args['user_id'])) {
            $where .= ' AND user_id = ' . absint($args['user_id']);
        }

        // Total sessions.
        $total_sessions = (int) ($db->get_var($db->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE started_at >= %s" . (! empty($args['user_id']) ? ' AND user_id = ' . absint($args['user_id']) : ''),
            $since
        )) ?? 0);

        // Avg duration.
        $avg_duration = (float) ($db->get_var($db->prepare(
            "SELECT AVG(duration_secs) FROM {$table} WHERE started_at >= %s AND duration_secs > 0" . (! empty($args['user_id']) ? ' AND user_id = ' . absint($args['user_id']) : ''),
            $since
        )) ?? 0);

        // Avg pages per session.
        $avg_pages = (float) ($db->get_var($db->prepare(
            "SELECT AVG(pages_viewed) FROM {$table} WHERE started_at >= %s" . (! empty($args['user_id']) ? ' AND user_id = ' . absint($args['user_id']) : ''),
            $since
        )) ?? 0);

        // Bounce rate (sessions with 1 page only).
        $bounces = (int) ($db->get_var($db->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE started_at >= %s AND pages_viewed <= 1" . (! empty($args['user_id']) ? ' AND user_id = ' . absint($args['user_id']) : ''),
            $since
        )) ?? 0);
        $bounce_rate = $total_sessions > 0 ? round(($bounces / $total_sessions) * 100, 1) : 0;

        // Sessions per day.
        $daily = $agg->count_by_date($table, 'started_at', $since, '', ! empty($args['user_id']) ? 'user_id = ' . absint($args['user_id']) : '');

        // Device breakdown.
        $devices = $agg->count_by_group($table, 'device_type', $where, 5);

        return array(
            'total_sessions' => $total_sessions,
            'avg_duration'   => round($avg_duration),
            'avg_pages'      => round($avg_pages, 1),
            'bounce_rate'    => $bounce_rate,
            'daily'          => $daily,
            'devices'        => $devices,
        );
    }
}
