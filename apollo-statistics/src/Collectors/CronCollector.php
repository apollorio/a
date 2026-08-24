<?php
/**
 * CronCollector — daily and periodic aggregation tasks.
 *
 * Runs on WP-Cron to compute engagement scores, rotate old data,
 * and pre-aggregate expensive queries.
 *
 * @package Apollo\Statistics\Collectors
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Collectors;

use Apollo\Statistics\Core\DataAggregator;

if (! defined('ABSPATH')) {
    exit;
}

final class CronCollector {

    /** Default retention days per table type (plan spec). */
    private const DEFAULT_RETENTION = array(
        'sessions'  => 365,
        'pageviews' => 90,
        'clicks'    => 30,
        'radio'     => 365,
    );

    public function init(): void {
        // Schedule daily aggregation cron.
        if (! wp_next_scheduled('apollo_stats_daily_aggregate')) {
            wp_schedule_event(time(), 'daily', 'apollo_stats_daily_aggregate');
        }
        add_action('apollo_stats_daily_aggregate', array($this, 'daily_aggregate'));

        // Schedule weekly data rotation.
        if (! wp_next_scheduled('apollo_stats_weekly_rotate')) {
            wp_schedule_event(time(), 'weekly', 'apollo_stats_weekly_rotate');
        }
        add_action('apollo_stats_weekly_rotate', array($this, 'rotate_old_data'));
    }

    /**
     * Daily aggregation: compute engagement scores for all active users.
     */
    public function daily_aggregate(): void {
        $this->compute_engagement_scores();
        do_action('apollo/statistics/daily_aggregate_completed');
    }

    /**
     * Compute and store engagement score for each user with recent activity.
     *
     * Score formula: views×1 + wows×2 + favs×3 + posts×5 + time_online×0.5
     */
    private function compute_engagement_scores(): void {
        global $wpdb;

        $agg   = DataAggregator::instance();
        $since = gmdate('Y-m-d', strtotime('-30 days'));
        $table = $agg->table('apollo_stats_users');

        // Get all users with any activity in the last 30 days.
        $users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$table} WHERE recorded_date >= %s AND user_id > 0",
            $since
        ));

        if (empty($users)) {
            return;
        }

        foreach ($users as $user_id) {
            $user_id = (int) $user_id;

            // Sum metric values by type.
            $metrics = $wpdb->get_results($wpdb->prepare(
                "SELECT metric_type, SUM(metric_value) AS total
                 FROM {$table}
                 WHERE user_id = %d AND recorded_date >= %s
                 GROUP BY metric_type",
                $user_id,
                $since
            ), ARRAY_A);

            $map = array();
            foreach ($metrics ?: array() as $row) {
                $map[$row['metric_type']] = (int) $row['total'];
            }

            // Weighted score.
            $score = 0;
            $score += ($map['view'] ?? 0) * 1;
            $score += ($map['profile_view'] ?? 0) * 1;
            $score += ($map['wow_given'] ?? 0) * 2;
            $score += ($map['fav_added'] ?? 0) * 3;
            $score += ($map['social_post'] ?? 0) * 5;
            $score += ($map['chat_message'] ?? 0) * 2;

            // Time online factor.
            $minutes = (int) get_user_meta($user_id, '_apollo_online_minutes', true);
            $score  += (int) round($minutes * 0.5);

            update_user_meta($user_id, '_apollo_stats_engagement_score', $score);
        }
    }

    /**
     * Remove detailed data older than per-table retention period.
     */
    public function rotate_old_data(): void {
        global $wpdb;

        $retention = $this->get_retention_days();

        // Pageviews — 90d default.
        $cutoff_pv = gmdate('Y-m-d H:i:s', strtotime('-' . $retention['pageviews'] . ' days'));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}apollo_stats_pageviews WHERE recorded_at < %s",
            $cutoff_pv
        ));

        // Clicks — 30d default.
        $cutoff_cl = gmdate('Y-m-d H:i:s', strtotime('-' . $retention['clicks'] . ' days'));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}apollo_stats_clicks WHERE recorded_at < %s",
            $cutoff_cl
        ));

        // Sessions — 365d default.
        $cutoff_sess = gmdate('Y-m-d H:i:s', strtotime('-' . $retention['sessions'] . ' days'));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}apollo_stats_sessions WHERE ended_at IS NOT NULL AND ended_at < %s",
            $cutoff_sess
        ));

        // Radio — 365d default.
        $cutoff_radio = gmdate('Y-m-d H:i:s', strtotime('-' . $retention['radio'] . ' days'));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}apollo_stats_radio WHERE started_at < %s",
            $cutoff_radio
        ));

        do_action('apollo/statistics/data_rotated', $retention);
    }

    /**
     * Get per-table retention days, merging settings override with defaults.
     * Enforces a minimum of 1 day to prevent accidental full deletion.
     *
     * @return array{sessions: int, pageviews: int, clicks: int, radio: int}
     */
    private function get_retention_days(): array {
        $settings  = get_option('apollo_admin_settings', array());
        $stats_cfg = $settings['statistics'] ?? array();

        return array(
            'sessions'  => max(1, absint($stats_cfg['retention_sessions']  ?? self::DEFAULT_RETENTION['sessions'])),
            'pageviews' => max(1, absint($stats_cfg['retention_pageviews'] ?? self::DEFAULT_RETENTION['pageviews'])),
            'clicks'    => max(1, absint($stats_cfg['retention_clicks']    ?? self::DEFAULT_RETENTION['clicks'])),
            'radio'     => max(1, absint($stats_cfg['retention_radio']     ?? self::DEFAULT_RETENTION['radio'])),
        );
    }
}
