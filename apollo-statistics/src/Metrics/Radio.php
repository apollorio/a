<?php
/**
 * Radio — radio listening session analytics.
 *
 * Instance: radio_listening.
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

class Radio extends MetricGroup {

    protected string $slug        = 'radio_listening';
    protected string $label       = 'Radio Listening';
    protected string $description = 'Radio session analytics: duration, tracks, pauses.';
    protected string $icon        = 'ri-radio-2-line';
    protected int    $priority    = 8;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'line', 'library' => 'amcharts');
    protected array  $tables      = array('apollo_stats_radio');
    protected array  $requires    = array('apollo-radio');

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $since = $this->resolve_since($args);
        $table = $agg->table('apollo_stats_radio');
        $db    = $agg->db();

        $where = $db->prepare('started_at >= %s', $since);
        if (! empty($args['user_id'])) {
            $where .= ' AND user_id = ' . absint($args['user_id']);
        }

        // Daily sessions.
        $daily = $agg->count_by_date($table, 'started_at', $since, '', ! empty($args['user_id']) ? 'user_id = ' . absint($args['user_id']) : '');

        // Totals.
        $total_sessions  = $agg->single_value($table, 'COUNT(*)', $where);
        $total_duration  = $agg->single_value($table, 'COALESCE(SUM(duration_secs), 0)', $where);
        $avg_duration    = $total_sessions > 0 ? round($total_duration / $total_sessions) : 0;
        $total_tracks    = $agg->single_value($table, 'COALESCE(SUM(track_count), 0)', $where);
        $total_pauses    = $agg->single_value($table, 'COALESCE(SUM(pause_count), 0)', $where);

        // Top listeners.
        $top_listeners = $agg->top_n($table, 'user_id', 'duration_secs', 10, 'user_id > 0');
        foreach ($top_listeners as &$row) {
            $user = get_userdata((int) $row['id']);
            $row['display_name'] = $user ? $user->display_name : '(anon)';
        }

        return array(
            'daily'          => $daily,
            'total_sessions' => $total_sessions,
            'total_duration' => $total_duration,
            'avg_duration'   => $avg_duration,
            'total_tracks'   => $total_tracks,
            'total_pauses'   => $total_pauses,
            'top_listeners'  => $top_listeners,
        );
    }
}
