<?php

/**
 * Plugin Name: Apollo Statistics
 * Plugin URI:  https://apollo.rio.br
 * Description: Ultra Modular Pro Analytics Engine — 15 MetricGroup classes, 68 plug-and-play metric instances, PostHog-inspired tracker, amCharts 5 visualizations, gamification bridge. Covers ALL 27 Apollo plugins.
 * Version:     2.0.6
 * Author:      Apollo RIO
 * Author URI:  https://apollo.rio.br
 * Text Domain: apollo-statistics
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.4
 * License:     GPL-2.0+
 *
 * @package Apollo\Statistics
 *
 * COMPLIANCE: apollo-registry.json
 * - Namespace: Apollo\Statistics
 * - Layer: L7_admin
 * - Priority: 5
 * - Tables: apollo_stats_events, apollo_stats_users, apollo_stats_content,
 *           apollo_stats_sessions, apollo_stats_pageviews, apollo_stats_clicks, apollo_stats_radio
 * - Depends: apollo-core
 * - REST: /stats/*, /track
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// Constantes do plugin
// ─────────────────────────────────────────────────────────────
define('APOLLO_STATS_VERSION', '2.0.6');
define('APOLLO_STATS_PATH', plugin_dir_path(__FILE__));
define('APOLLO_STATS_URL', plugin_dir_url(__FILE__));
define('APOLLO_STATS_FILE', __FILE__);
define('APOLLO_STATS_DB_VERSION', '2.0.0');

// Legacy constants (backward compat).
if (! defined('APOLLO_STATISTICS_VERSION')) {
    define('APOLLO_STATISTICS_VERSION', APOLLO_STATS_VERSION);
}
if (! defined('APOLLO_STATISTICS_PATH')) {
    define('APOLLO_STATISTICS_PATH', APOLLO_STATS_PATH);
}
if (! defined('APOLLO_STATISTICS_URL')) {
    define('APOLLO_STATISTICS_URL', APOLLO_STATS_URL);
}
if (! defined('APOLLO_STATISTICS_FILE')) {
    define('APOLLO_STATISTICS_FILE', APOLLO_STATS_FILE);
}

// ─────────────────────────────────────────────────────────────
// PSR-4 Autoloader → src/
// ─────────────────────────────────────────────────────────────
spl_autoload_register(
    static function (string $class_name): void {
        $prefix   = 'Apollo\\Statistics\\';
        $base_dir = APOLLO_STATS_PATH . 'src/';

        if (strncmp($class_name, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class_name, strlen($prefix));
        $file     = $base_dir . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
);

// ─────────────────────────────────────────────────────────────
// Legacy autoloader → includes/ (backward compat for old class names)
// ─────────────────────────────────────────────────────────────
spl_autoload_register(
    static function (string $class_name): void {
        $prefix   = 'Apollo\\Statistics\\';
        $base_dir = APOLLO_STATS_PATH . 'includes/';

        if (strncmp($class_name, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class_name, strlen($prefix));
        $file     = $base_dir . 'class-' . strtolower(
            str_replace(array('\\', '_'), array('/', '-'), $relative)
        ) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
);

// ─────────────────────────────────────────────────────────────
// Ativação — cria TODAS as 7 tabelas de estatísticas
// ─────────────────────────────────────────────────────────────
register_activation_hook(
    __FILE__,
    static function (): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── Legacy tables (preserved) ───────────────────────────

        $table_events = $wpdb->prefix . 'apollo_stats_events';
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$table_events} (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                event_id        BIGINT UNSIGNED NOT NULL,
                metric_type     VARCHAR(50)     NOT NULL DEFAULT 'view',
                metric_value    BIGINT          NOT NULL DEFAULT 0,
                recorded_date   DATE            NOT NULL,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_event_metric_date (event_id, metric_type, recorded_date),
                KEY idx_recorded_date (recorded_date),
                KEY idx_metric_type (metric_type)
            ) {$charset_collate};"
        );

        $table_users = $wpdb->prefix . 'apollo_stats_users';
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$table_users} (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id         BIGINT UNSIGNED NOT NULL DEFAULT 0,
                metric_type     VARCHAR(50)     NOT NULL DEFAULT 'registration',
                metric_value    BIGINT          NOT NULL DEFAULT 0,
                recorded_date   DATE            NOT NULL,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_user_metric_date (user_id, metric_type, recorded_date),
                KEY idx_recorded_date (recorded_date),
                KEY idx_metric_type (metric_type)
            ) {$charset_collate};"
        );

        $table_content = $wpdb->prefix . 'apollo_stats_content';
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$table_content} (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id         BIGINT UNSIGNED NOT NULL DEFAULT 0,
                post_type       VARCHAR(60)     NOT NULL DEFAULT 'post',
                metric_type     VARCHAR(50)     NOT NULL DEFAULT 'view',
                metric_value    BIGINT          NOT NULL DEFAULT 0,
                recorded_date   DATE            NOT NULL,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_content_metric_date (post_id, metric_type, recorded_date),
                KEY idx_recorded_date (recorded_date),
                KEY idx_post_type (post_type),
                KEY idx_metric_type (metric_type)
            ) {$charset_collate};"
        );

        // ── NEW v2 tables ───────────────────────────────────────

        $table_sessions = $wpdb->prefix . 'apollo_stats_sessions';
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$table_sessions} (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                session_id      CHAR(36)        NOT NULL,
                user_id         BIGINT UNSIGNED NOT NULL DEFAULT 0,
                started_at      DATETIME        NOT NULL,
                ended_at        DATETIME        DEFAULT NULL,
                duration_secs   INT UNSIGNED    NOT NULL DEFAULT 0,
                pages_viewed    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                entry_url       VARCHAR(500)    NOT NULL DEFAULT '',
                exit_url        VARCHAR(500)    NOT NULL DEFAULT '',
                referrer        VARCHAR(500)    NOT NULL DEFAULT '',
                utm_source      VARCHAR(100)    DEFAULT NULL,
                utm_medium      VARCHAR(100)    DEFAULT NULL,
                utm_campaign    VARCHAR(100)    DEFAULT NULL,
                device_type     VARCHAR(10)     NOT NULL DEFAULT 'desktop',
                browser         VARCHAR(50)     DEFAULT NULL,
                ip_hash         CHAR(64)        NOT NULL DEFAULT '',
                PRIMARY KEY (id),
                UNIQUE KEY uk_session (session_id),
                KEY idx_user (user_id),
                KEY idx_started (started_at),
                KEY idx_device (device_type)
            ) {$charset_collate};"
        );

        $table_pageviews = $wpdb->prefix . 'apollo_stats_pageviews';
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$table_pageviews} (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                session_id      CHAR(36)        NOT NULL,
                user_id         BIGINT UNSIGNED NOT NULL DEFAULT 0,
                url             VARCHAR(500)    NOT NULL,
                page_type       VARCHAR(50)     NOT NULL DEFAULT 'page',
                object_id       BIGINT UNSIGNED DEFAULT NULL,
                time_on_page_ms INT UNSIGNED    NOT NULL DEFAULT 0,
                scroll_depth    TINYINT UNSIGNED NOT NULL DEFAULT 0,
                recorded_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_session (session_id),
                KEY idx_user_date (user_id, recorded_at),
                KEY idx_page_type (page_type),
                KEY idx_object (object_id),
                KEY idx_recorded (recorded_at)
            ) {$charset_collate};"
        );

        $table_clicks = $wpdb->prefix . 'apollo_stats_clicks';
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$table_clicks} (
                id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                session_id       CHAR(36)        NOT NULL,
                user_id          BIGINT UNSIGNED NOT NULL DEFAULT 0,
                source_url       VARCHAR(500)    NOT NULL,
                target_url       VARCHAR(500)    NOT NULL,
                element_type     VARCHAR(50)     NOT NULL DEFAULT 'link',
                source_page_type VARCHAR(50)     DEFAULT NULL,
                source_object_id BIGINT UNSIGNED DEFAULT NULL,
                recorded_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_session (session_id),
                KEY idx_user (user_id),
                KEY idx_source_type (source_page_type, source_object_id),
                KEY idx_recorded (recorded_at)
            ) {$charset_collate};"
        );

        $table_radio = $wpdb->prefix . 'apollo_stats_radio';
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$table_radio} (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id         BIGINT UNSIGNED NOT NULL DEFAULT 0,
                session_id      CHAR(36)        NOT NULL,
                started_at      DATETIME        NOT NULL,
                ended_at        DATETIME        DEFAULT NULL,
                duration_secs   INT UNSIGNED    NOT NULL DEFAULT 0,
                track_count     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                pause_count     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY idx_user (user_id),
                KEY idx_started (started_at),
                KEY idx_session (session_id)
            ) {$charset_collate};"
        );

        update_option('apollo_statistics_db_version', APOLLO_STATS_DB_VERSION);
        flush_rewrite_rules();
    }
);

// ─────────────────────────────────────────────────────────────
// Desativação
// ─────────────────────────────────────────────────────────────
register_deactivation_hook(
    __FILE__,
    static function (): void {
        wp_clear_scheduled_hook('apollo_stats_daily_collect');
        wp_clear_scheduled_hook('apollo_stats_daily_aggregate');
        wp_clear_scheduled_hook('apollo_stats_weekly_rotate');
        wp_clear_scheduled_hook('apollo_stats_session_cleanup');
        wp_clear_scheduled_hook('apollo_stats_cron_events_reminder_email');
        wp_clear_scheduled_hook('apollo_stats_cron_weekly_roundup_email');
        flush_rewrite_rules();
    }
);

// ─────────────────────────────────────────────────────────────
// Verificação de dependência (XSS fixed)
// ─────────────────────────────────────────────────────────────
add_action(
    'admin_init',
    static function (): void {
        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (! is_plugin_active('apollo-core/apollo-core.php')) {
            add_action(
                'admin_notices',
                static function (): void {
                    printf(
                        '<div class="notice notice-error"><p>%s</p></div>',
                        wp_kses_post(
                            '<strong>Apollo Statistics:</strong> Requer <code>apollo-core</code> ativo.'
                        )
                    );
                }
            );
        }
    }
);

// ─────────────────────────────────────────────────────────────
// Inicialização principal — v2 Plugin singleton
// ─────────────────────────────────────────────────────────────
add_action(
    'plugins_loaded',
    static function (): void {
        // Load legacy global functions.
        if (file_exists(APOLLO_STATS_PATH . 'includes/functions.php')) {
            require_once APOLLO_STATS_PATH . 'includes/functions.php';
        }

        // Boot the v2 Plugin singleton.
        Apollo\Statistics\Plugin::instance();
    },
    25
);
