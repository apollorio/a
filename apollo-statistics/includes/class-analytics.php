<?php

/**
 * Analytics — provides user-stats visibility and aggregation methods.
 *
 * This class bridges the User_Stats_Widget to the Data Collector / Metrics
 * Processor pipeline. Each method queries apollo_stats_* tables and user meta
 * to produce the data the widget expects.
 *
 * @package Apollo\Statistics
 * @since   3.1.0
 */

declare(strict_types=1);

namespace Apollo\Statistics;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Analytics helper – resolves the missing Analytics class used by User_Stats_Widget.
 */
final class Analytics
{

	/* ─── Visibility ──────────────────────────────────────────────── */

    /**
     * Check whether $viewer_id may see $user_id's stats.
     *
     * @param int $user_id  Whose stats.
     * @param int $viewer_id Who is looking.
     * @return bool
     */
    public static function can_view_user_stats(int $user_id, int $viewer_id): bool
    {
        if ($viewer_id === $user_id) {
            return true;
        }
        if (user_can($viewer_id, 'manage_options')) {
            return true;
        }

        $vis = self::get_user_stats_visibility($user_id);

        switch ($vis['show_to']) {
            case 'public':
                return true;
            case 'followers':
                // Delegate to apollo-social if available.
                if (function_exists('apollo_is_following')) {
                    return (bool) apollo_is_following($viewer_id, $user_id);
                }
                return false;
            default: // 'self'
                return false;
        }
    }

    /**
     * Return the user's visibility preferences.
     *
     * @param int $user_id User ID.
     * @return array{show_profile_views: bool, show_content_views: bool, show_engagement: bool, show_to: string}
     */
    public static function get_user_stats_visibility(int $user_id): array
    {
        $defaults = array(
            'show_profile_views' => true,
            'show_content_views' => true,
            'show_engagement'    => true,
            'show_to'            => 'self',
        );

        $saved = get_user_meta($user_id, '_apollo_stats_visibility', true);
        if (! is_array($saved)) {
            return $defaults;
        }

        return wp_parse_args($saved, $defaults);
    }

    /**
     * Persist visibility settings.
     *
     * @param int   $user_id  User ID.
     * @param array $settings Visibility settings.
     * @return bool
     */
    public static function update_user_stats_visibility(int $user_id, array $settings): bool
    {
        $allowed = array('self', 'followers', 'public');
        $clean   = array(
            'show_profile_views' => ! empty($settings['show_profile_views']),
            'show_content_views' => ! empty($settings['show_content_views']),
            'show_engagement'    => ! empty($settings['show_engagement']),
            'show_to'            => in_array($settings['show_to'] ?? 'self', $allowed, true)
                ? $settings['show_to']
                : 'self',
        );

        return (bool) update_user_meta($user_id, '_apollo_stats_visibility', $clean);
    }

	/* ─── User Stats ──────────────────────────────────────────────── */

    /**
     * Fetch aggregated stats for a user in a given period.
     *
     * Reads from apollo_stats_users + apollo_stats_content tables.
     *
     * @param int    $user_id User ID.
     * @param string $period  One of 'today','week','month','year'.
     * @return array Stat values keyed by metric slug.
     */
    public static function get_user_stats(int $user_id, string $period = 'month'): array
    {
        global $wpdb;

        $since = self::period_to_date($period);
        $table_user    = $wpdb->prefix . 'apollo_stats_users';
        $table_content = $wpdb->prefix . 'apollo_stats_content';

        // ── User-level metrics (profile_views, logins, etc.) ──
        $user_metrics = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->prepare(
                "SELECT metric_type, SUM(metric_value) AS total
				 FROM {$table_user}
				 WHERE user_id = %d AND recorded_date >= %s
				 GROUP BY metric_type",
                $user_id,
                $since
            ),
            ARRAY_A
        );

        $stats = array(
            'profile_views'    => 0,
            'event_views'      => 0,
            'post_views'       => 0,
            'wows_received'    => 0,
            'social_shares'    => 0,
            'followers_gained' => 0,
            'unique_visitors'  => 0,
        );

        if (is_array($user_metrics)) {
            foreach ($user_metrics as $row) {
                $key = $row['metric_type'] ?? '';
                if (isset($stats[$key])) {
                    $stats[$key] = (int) $row['total'];
                }
            }
        }

        // ── Content-level metrics aggregated for this author ──
        $content_metrics = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->prepare(
                "SELECT c.metric_type, SUM(c.metric_value) AS total
				 FROM {$table_content} c
				 INNER JOIN {$wpdb->posts} p ON c.post_id = p.ID
				 WHERE p.post_author = %d AND c.recorded_date >= %s
				 GROUP BY c.metric_type",
                $user_id,
                $since
            ),
            ARRAY_A
        );

        if (is_array($content_metrics)) {
            foreach ($content_metrics as $row) {
                switch ($row['metric_type']) {
                    case 'view':
                        $stats['post_views'] += (int) $row['total'];
                        break;
                    case 'wow':
                        $stats['wows_received'] += (int) $row['total'];
                        break;
                    case 'share':
                        $stats['social_shares'] += (int) $row['total'];
                        break;
                }
            }
        }

        return $stats;
    }

	/* ─── Rate Limiting (simple transient-based) ──────────────────── */

    /**
     * Basic rate-limit check using transients.
     *
     * @param string $key   Rate-limit identifier.
     * @param int    $limit Max hits allowed.
     * @param int    $window Time window in seconds.
     * @return bool True if within limit.
     */
    public static function check_rate_limit(string $key, int $limit, int $window): bool
    {
        $user_id       = get_current_user_id();
        $transient_key = 'apollo_rl_' . md5($key . '_' . $user_id);
        $current       = (int) get_transient($transient_key);

        if ($current >= $limit) {
            return false;
        }

        set_transient($transient_key, $current + 1, $window);
        return true;
    }

	/* ─── Helpers ──────────────────────────────────────────────────── */

    /**
     * Convert a period label to a date string.
     *
     * @param string $period Period identifier.
     * @return string Date in Y-m-d format.
     */
    private static function period_to_date(string $period): string
    {
        return match ($period) {
            'today' => wp_date('Y-m-d'),
            'week'  => wp_date('Y-m-d', strtotime('-7 days')),
            'year'  => wp_date('Y-m-d', strtotime('-1 year')),
            default => wp_date('Y-m-d', strtotime('-30 days')),
        };
    }
}
