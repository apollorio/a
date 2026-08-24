<?php
/**
 * ScoreProcessor — processes and transforms raw metrics into scored outputs.
 *
 * @package Apollo\Statistics\Processors
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Processors;

if (! defined('ABSPATH')) {
    exit;
}

final class ScoreProcessor {

    public function init(): void {
        // Hook into daily cron to re-process scores.
        add_action('apollo/statistics/daily_aggregate_completed', array($this, 'process_content_scores'));
    }

    /**
     * Compute content engagement scores for all post types.
     * Formula: views×1 + favs×3 + wows×2 + shares×5
     */
    public function process_content_scores(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'apollo_stats_content';
        $since = gmdate('Y-m-d', strtotime('-30 days'));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id,
                    SUM(CASE WHEN metric_type = 'view' THEN metric_value ELSE 0 END) AS views,
                    SUM(CASE WHEN metric_type = 'fav' THEN metric_value ELSE 0 END) AS favs,
                    SUM(CASE WHEN metric_type = 'wow' THEN metric_value ELSE 0 END) AS wows,
                    SUM(CASE WHEN metric_type = 'share' THEN metric_value ELSE 0 END) AS shares
             FROM {$table}
             WHERE recorded_date >= %s
             GROUP BY post_id",
            $since
        ), ARRAY_A);

        foreach ($results ?: array() as $row) {
            $score = (int) $row['views'] * 1
                   + (int) $row['favs'] * 3
                   + (int) $row['wows'] * 2
                   + (int) $row['shares'] * 5;

            update_post_meta((int) $row['post_id'], '_apollo_content_score', $score);
        }
    }
}
