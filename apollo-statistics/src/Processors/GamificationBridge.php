<?php
/**
 * GamificationBridge — connects statistics data to membership/gamification.
 *
 * Fires membership points/badge hooks based on stats milestones.
 *
 * @package Apollo\Statistics\Processors
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Processors;

if (! defined('ABSPATH')) {
    exit;
}

final class GamificationBridge {

    public function init(): void {
        // Only wire gamification if apollo-membership is active.
        if (! $this->is_membership_active()) {
            return;
        }

        // After daily aggregation, check for milestone achievements.
        add_action('apollo/statistics/daily_aggregate_completed', array($this, 'check_milestones'));

        // Session end → potential session-based awards.
        add_action('apollo/statistics/session_end', array($this, 'on_session_end'), 10, 2);
    }

    /**
     * Check if apollo-membership plugin is active.
     */
    private function is_membership_active(): bool {
        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active('apollo-membership/apollo-membership.php');
    }

    /**
     * Check if any users hit engagement milestones.
     */
    public function check_milestones(): void {
        global $wpdb;

        $users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, meta_value AS score
                 FROM {$wpdb->usermeta}
                 WHERE meta_key = %s
                   AND CAST(meta_value AS UNSIGNED) > 0
                 ORDER BY CAST(meta_value AS UNSIGNED) DESC
                 LIMIT %d",
                '_apollo_stats_engagement_score',
                500
            ),
            ARRAY_A
        );

        $milestones = array(
            100  => 'stats_100_engagement',
            500  => 'stats_500_engagement',
            1000 => 'stats_1000_engagement',
            5000 => 'stats_5000_engagement',
        );

        foreach ($users ?: array() as $row) {
            $user_id = (int) $row['user_id'];
            $score   = (int) $row['score'];

            foreach ($milestones as $threshold => $badge_slug) {
                if ($score >= $threshold) {
                    $awarded_key = '_apollo_stats_milestone_' . $threshold;
                    if (! get_user_meta($user_id, $awarded_key, true)) {
                        do_action('apollo/membership/achievement_trigger', $user_id, $badge_slug);
                        update_user_meta($user_id, $awarded_key, time());
                    }
                }
            }
        }
    }

    /**
     * Award points for long sessions.
     */
    public function on_session_end(string $session_id, array $params): void {
        $user_id  = get_current_user_id();
        $duration = absint($params['duration'] ?? 0);

        if ($user_id <= 0 || $duration < 300) { // Minimum 5 minutes.
            return;
        }

        // 1 point per 15 minutes of session.
        $points = (int) floor($duration / 900);
        if ($points > 0 && $points <= 96) { // Cap at 24h.
            do_action('apollo/membership/award_session_points', $user_id, $points);
        }
    }
}
