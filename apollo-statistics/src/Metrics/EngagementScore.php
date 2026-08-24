<?php
/**
 * EngagementScore — composite weighted score.
 *
 * Instances: user_engagement_score, user_time_online_points, social_engagement.
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

class EngagementScore extends MetricGroup {

    protected string $slug        = 'engagement_score';
    protected string $label       = 'Engagement Score';
    protected string $description = 'Composite weighted engagement score.';
    protected string $icon        = 'ri-fire-line';
    protected int    $priority    = 2;
    protected array  $displays    = array('admin_dashboard', 'user_profile');
    protected array  $chart       = array('type' => 'number', 'library' => 'html');

    /**
     * Weights: metric_type => multiplier.
     * @var array<string, float>
     */
    protected array $weights = array(
        'view'         => 1.0,
        'profile_view' => 1.0,
        'wow_given'    => 2.0,
        'fav_added'    => 3.0,
        'social_post'  => 5.0,
        'chat_message' => 2.0,
    );

    /** Add time_online factor (minutes × weight). */
    protected float $time_weight = 0.5;

    public function with_weights(string $slug, string $label, array $weights, float $time_weight = 0.5): static {
        $clone = clone $this;
        $clone->slug        = $slug;
        $clone->label       = $label;
        $clone->weights     = $weights;
        $clone->time_weight = $time_weight;
        return $clone;
    }

    public function compute(array $args = array()): array {
        $agg   = $this->aggregator();
        $since = $this->resolve_since($args);
        $table = $agg->table('apollo_stats_users');
        $db    = $agg->db();

        $user_id = ! empty($args['user_id']) ? absint($args['user_id']) : 0;

        if ($user_id > 0) {
            return $this->compute_for_user($user_id, $since, $table, $db);
        }

        // Global: top N users by engagement score.
        $limit = $this->resolve_limit($args);
        $users = $db->get_results($db->prepare(
            "SELECT user_id, SUM(metric_value) AS total
             FROM {$table}
             WHERE recorded_date >= %s AND user_id > 0
             GROUP BY user_id
             ORDER BY total DESC
             LIMIT %d",
            $since,
            $limit
        ), ARRAY_A) ?: array();

        // Attach pre-computed scores from meta.
        foreach ($users as &$row) {
            $row['engagement_score'] = (int) get_user_meta((int) $row['user_id'], '_apollo_stats_engagement_score', true);
        }

        return array(
            'leaderboard' => $users,
        );
    }

    private function compute_for_user(int $user_id, string $since, string $table, \wpdb $db): array {
        $metrics = $db->get_results($db->prepare(
            "SELECT metric_type, SUM(metric_value) AS total
             FROM {$table}
             WHERE user_id = %d AND recorded_date >= %s
             GROUP BY metric_type",
            $user_id,
            $since
        ), ARRAY_A) ?: array();

        $map   = array();
        $score = 0;
        foreach ($metrics as $row) {
            $map[$row['metric_type']] = (int) $row['total'];
        }

        foreach ($this->weights as $type => $weight) {
            $score += ($map[$type] ?? 0) * $weight;
        }

        // Time online factor.
        $minutes = (int) get_user_meta($user_id, '_apollo_online_minutes', true);
        $score  += (int) round($minutes * $this->time_weight);

        return array(
            'score'    => (int) $score,
            'breakdown' => $map,
            'minutes_online' => $minutes,
        );
    }
}
