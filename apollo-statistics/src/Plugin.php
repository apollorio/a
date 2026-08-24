<?php
/**
 * Plugin singleton — boots all v2 subsystems.
 *
 * @package Apollo\Statistics
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin {

    private static ?self $instance = null;

    /** @var Core\MetricRegistry */
    private Core\MetricRegistry $registry;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->maybe_upgrade_db();
        $this->boot_legacy();
        $this->boot_core();
        $this->boot_collectors();
        $this->boot_processors();
        $this->boot_api();
        $this->boot_admin();
        $this->boot_assets();

        do_action('apollo/statistics/initialized', $this);
    }

    /* ──────────────── Database upgrade check ──────────────── */

    private function maybe_upgrade_db(): void {
        $installed = get_option('apollo_statistics_db_version', '0');

        if (version_compare($installed, APOLLO_STATS_DB_VERSION, '<')) {
            // dbDelta is idempotent — safe to run on every upgrade.
            $this->create_tables();
            update_option('apollo_statistics_db_version', APOLLO_STATS_DB_VERSION);
        }
    }

    private function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $tables = $this->get_table_schemas($wpdb->prefix, $charset);

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Returns DDL statements for all 7 tables.
     * Centralised here so activation hook + upgrade check share the same source.
     */
    public static function get_table_schemas(string $prefix, string $charset): array {
        return array(
            // sessions
            "CREATE TABLE IF NOT EXISTS {$prefix}apollo_stats_sessions (
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
            ) {$charset};",
            // pageviews
            "CREATE TABLE IF NOT EXISTS {$prefix}apollo_stats_pageviews (
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
            ) {$charset};",
            // clicks
            "CREATE TABLE IF NOT EXISTS {$prefix}apollo_stats_clicks (
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
            ) {$charset};",
            // radio
            "CREATE TABLE IF NOT EXISTS {$prefix}apollo_stats_radio (
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
            ) {$charset};",
        );
    }

    /* ──────────────── Legacy v1 subsystems ──────────────── */

    private function boot_legacy(): void {
        // Data Collector (12 hooks).
        if (class_exists(Data_Collector::class)) {
            $collector = new Data_Collector();
            $collector->init();
        }

        // Metrics Processor.
        if (class_exists(Metrics_Processor::class)) {
            $processor = new Metrics_Processor();
            $processor->init();
        }

        // Reports (admin dashboard widget + shortcode).
        if (class_exists(Reports::class)) {
            $reports = new Reports();
            $reports->init();
        }

        // User stats widget.
        if (class_exists(User_Stats_Widget::class)) {
            $user_stats = new User_Stats_Widget();
            $user_stats->init();
        }
    }

    /* ──────────────── v2 Core ──────────────── */

    private function boot_core(): void {
        $this->registry = Core\MetricRegistry::instance();

        // Register all 68 MetricGroup instances.
        add_action('apollo/statistics/register', array(Core\MetricBootstrap::class, 'register'));

        // Fire registration now (allows other plugins to add metrics too).
        $this->registry->fire_registration();
    }

    private function boot_collectors(): void {
        // HookCollector, SessionCollector, CronCollector — F2.
        if (class_exists(Collectors\HookCollector::class)) {
            (new Collectors\HookCollector())->init();
        }
        if (class_exists(Collectors\SessionCollector::class)) {
            (new Collectors\SessionCollector())->init();
        }
        if (class_exists(Collectors\CronCollector::class)) {
            (new Collectors\CronCollector())->init();
        }
        if (class_exists(Collectors\WeeklyDigestCron::class)) {
            (new Collectors\WeeklyDigestCron())->init();
        }
    }

    private function boot_processors(): void {
        if (class_exists(Processors\ScoreProcessor::class)) {
            (new Processors\ScoreProcessor())->init();
        }
        if (class_exists(Processors\GamificationBridge::class)) {
            (new Processors\GamificationBridge())->init();
        }
    }

    private function boot_api(): void {
        add_action('rest_api_init', function (): void {
            // v2 StatsController.
            if (class_exists(API\StatsController::class)) {
                (new API\StatsController())->register_routes();
            }
            // v2 TrackController.
            if (class_exists(API\TrackController::class)) {
                (new API\TrackController())->register_routes();
            }
            // v2 ProfileController.
            if (class_exists(API\ProfileController::class)) {
                (new API\ProfileController())->register_routes();
            }
            // Legacy REST (preserved for backward compat).
            if (class_exists(REST_Controller::class)) {
                (new REST_Controller())->register_routes();
            }
        });
    }

    private function boot_admin(): void {
        if (class_exists(Admin\SettingsSchema::class)) {
            (new Admin\SettingsSchema())->init();
        }
        if (class_exists(Admin\DashboardWidgets::class)) {
            (new Admin\DashboardWidgets())->init();
        }
        if (class_exists(Admin\TestPanel::class)) {
            (new Admin\TestPanel())->init();
        }
        // Frontend profile stats route.
        if (class_exists(Frontend\ProfileStats::class)) {
            (new Frontend\ProfileStats())->init();
        }
    }

    private function boot_assets(): void {
        // ── Frontend tracker (theme pages via wp_enqueue_scripts) ──
        add_action('wp_enqueue_scripts', function (): void {
            if (! $this->is_tracker_enabled()) {
                return;
            }

            wp_enqueue_script(
                'apollo-tracker',
                APOLLO_STATS_URL . 'assets/js/tracker.js',
                array(),
                APOLLO_STATS_VERSION,
                true
            );
        });

        // ── Canvas pages skip wp_footer — inject tracker on Apollo hooks ──
        $print_canvas_tracker = function (): void {
            if (! $this->is_tracker_enabled()) {
                return;
            }

            static $printed = false;
            if ($printed) {
                return;
            }
            $printed = true;

            printf(
                '<script src="%s" defer></script>' . "\n",
                esc_url(APOLLO_STATS_URL . 'assets/js/tracker.js?v=' . rawurlencode(APOLLO_STATS_VERSION))
            );
        };

        add_action('apollo/canvas/head', $print_canvas_tracker, 99);
        add_action('apollo/feed/after_content', $print_canvas_tracker, 5);

        // ── Profile stats scripts (enqueued before template_redirect exits) ──
        add_action('wp_enqueue_scripts', function (): void {
            if (! get_query_var('apollo_profile_stats')) {
                return;
            }

            $am5_base = defined('APOLLO_CDN_URL') ? constant('APOLLO_CDN_URL') . 'am5/' : 'https://cdn.apollo.rio.br/v1.0.0/am5/';

            wp_enqueue_style(
                'apollo-profile-stats',
                APOLLO_STATS_URL . 'assets/css/profile-stats.css',
                array(),
                APOLLO_STATS_VERSION
            );

            wp_enqueue_script('am5-index', $am5_base . 'index.js', array(), '5.0.0', true);
            wp_enqueue_script('am5-xy', $am5_base . 'xy.js', array('am5-index'), '5.0.0', true);
            wp_enqueue_script('am5-percent', $am5_base . 'percent.js', array('am5-index'), '5.0.0', true);
            wp_enqueue_script('am5-animated', $am5_base . 'themes/Animated.js', array('am5-index'), '5.0.0', true);

            wp_enqueue_script(
                'apollo-admin-charts',
                APOLLO_STATS_URL . 'assets/js/admin-charts.js',
                array('am5-index', 'am5-xy', 'am5-percent', 'am5-animated'),
                APOLLO_STATS_VERSION,
                true
            );
            wp_localize_script(
                'apollo-admin-charts',
                'apolloStats',
                array(
                    'restUrl' => rest_url('apollo/v1/stats/'),
                    'nonce'   => wp_create_nonce('wp_rest'),
                    'am5Root' => $am5_base,
                )
            );

            wp_enqueue_script(
                'apollo-profile-stats',
                APOLLO_STATS_URL . 'assets/js/profile-stats.js',
                array('apollo-admin-charts'),
                APOLLO_STATS_VERSION,
                true
            );
        });

        // ── Admin charts — scoped to Apollo admin pages only ────
        add_action('admin_enqueue_scripts', function (string $hook): void {
            // Only load heavy chart assets on pages that actually use them.
            $apollo_pages = array(
                'toplevel_page_apollo',
                'apollo_page_apollo-statistics-tests',
            );
            if (! current_user_can('manage_options') || ! in_array($hook, $apollo_pages, true)) {
                return;
            }

            wp_enqueue_style(
                'apollo-admin-stats',
                APOLLO_STATS_URL . 'assets/css/admin-stats.css',
                array(),
                APOLLO_STATS_VERSION
            );

            // amCharts 5 — self-hosted via Apollo CDN.
            $am5_base = defined('APOLLO_CDN_URL') ? constant('APOLLO_CDN_URL') . 'am5/' : 'https://cdn.apollo.rio.br/v1.0.0/am5/';

            wp_enqueue_script(
                'am5-index',
                $am5_base . 'index.js',
                array(),
                '5.0.0',
                true
            );
            wp_enqueue_script(
                'am5-xy',
                $am5_base . 'xy.js',
                array('am5-index'),
                '5.0.0',
                true
            );
            wp_enqueue_script(
                'am5-percent',
                $am5_base . 'percent.js',
                array('am5-index'),
                '5.0.0',
                true
            );
            wp_enqueue_script(
                'am5-animated',
                $am5_base . 'themes/Animated.js',
                array('am5-index'),
                '5.0.0',
                true
            );

            wp_enqueue_script(
                'apollo-admin-charts',
                APOLLO_STATS_URL . 'assets/js/admin-charts.js',
                array('am5-index', 'am5-xy', 'am5-percent', 'am5-animated'),
                APOLLO_STATS_VERSION,
                true
            );

            wp_localize_script(
                'apollo-admin-charts',
                'apolloStats',
                array(
                    'restUrl' => rest_url('apollo/v1/stats/'),
                    'nonce'   => wp_create_nonce('wp_rest'),
                    'am5Root' => $am5_base,
                )
            );
        });
    }

    /* ──────────────── Accessors ──────────────── */

    public function registry(): Core\MetricRegistry {
        return $this->registry;
    }

    private function is_tracker_enabled(): bool {
        $settings = get_option('apollo_admin_settings', array());

        return (bool) ( $settings['statistics']['tracker_enabled'] ?? true );
    }
}
