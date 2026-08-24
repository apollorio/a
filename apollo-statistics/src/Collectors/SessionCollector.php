<?php
/**
 * SessionCollector — manages session lifecycle via heartbeat.
 *
 * Works in tandem with tracker.js: the tracker sends session/start,
 * session/heartbeat, and session/end via REST. This collector handles
 * the server-side session timeout and cleanup logic.
 *
 * @package Apollo\Statistics\Collectors
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Collectors;

if (! defined('ABSPATH')) {
    exit;
}

final class SessionCollector {

    /** Sessions without heartbeat for this many seconds are considered expired. */
    private const IDLE_TIMEOUT = 1800; // 30 minutes.

    /** Maximum session length in seconds. */
    private const MAX_DURATION = 86400; // 24 hours.

    public function init(): void {
        // Schedule cleanup cron if not present.
        if (! wp_next_scheduled('apollo_stats_session_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'apollo_stats_session_cleanup');
        }
        add_action('apollo_stats_session_cleanup', array($this, 'cleanup_stale_sessions'));

        // Hook into the REST session events for server-side processing.
        add_action('apollo/statistics/session_start', array($this, 'on_session_start'), 10, 2);
        add_action('apollo/statistics/session_heartbeat', array($this, 'on_session_heartbeat'), 10, 2);
        add_action('apollo/statistics/session_end', array($this, 'on_session_end'), 10, 2);
    }

    /**
     * When a session starts, update the user's last_seen meta.
     */
    public function on_session_start(string $session_id, array $params): void {
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            update_user_meta($user_id, '_apollo_last_seen', time());
        }
    }

    /**
     * Heartbeat: update last_seen and accumulate online minutes.
     */
    public function on_session_heartbeat(string $session_id, array $params): void {
        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return;
        }

        $now       = time();
        $last_seen = (int) get_user_meta($user_id, '_apollo_last_seen', true);

        // Only accumulate if last seen within idle timeout.
        if ($last_seen > 0 && ($now - $last_seen) < self::IDLE_TIMEOUT) {
            $elapsed_minutes = (int) round(($now - $last_seen) / 60);
            if ($elapsed_minutes > 0) {
                $total = (int) get_user_meta($user_id, '_apollo_online_minutes', true);
                update_user_meta($user_id, '_apollo_online_minutes', $total + $elapsed_minutes);
            }
        }

        update_user_meta($user_id, '_apollo_last_seen', $now);
    }

    /**
     * Session end: final last_seen update.
     */
    public function on_session_end(string $session_id, array $params): void {
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            update_user_meta($user_id, '_apollo_last_seen', time());
        }
    }

    /**
     * Close sessions that have been idle for too long.
     * Runs hourly via WP-Cron.
     */
    public function cleanup_stale_sessions(): void {
        global $wpdb;

        $table      = $wpdb->prefix . 'apollo_stats_sessions';
        $cutoff     = gmdate('Y-m-d H:i:s', time() - self::IDLE_TIMEOUT);
        $max_cutoff = gmdate('Y-m-d H:i:s', time() - self::MAX_DURATION);

        // Close sessions that haven't had a heartbeat.
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET ended_at = NOW(),
                 duration_secs = TIMESTAMPDIFF(SECOND, started_at, NOW())
             WHERE ended_at IS NULL
               AND started_at < %s",
            $cutoff
        ));

        // Force-close sessions exceeding max duration.
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET ended_at = DATE_ADD(started_at, INTERVAL %d SECOND),
                 duration_secs = %d
             WHERE ended_at IS NULL
               AND started_at < %s",
            self::MAX_DURATION,
            self::MAX_DURATION,
            $max_cutoff
        ));
    }
}
