<?php

namespace Apollo\Remind\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Activation {

    public static function activate(): void {
        self::create_tables();
        self::schedule_cron();
        update_option( 'apollo_remind_version', APOLLO_REMIND_VERSION );
        update_option( 'apollo_remind_db_version', APOLLO_REMIND_DB_VERSION );
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'apollo_remind_process_queue' );
        wp_clear_scheduled_hook( 'apollo_remind_daily_cleanup' );
    }

    public static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        // 1. Reminders table — the core queue
        $t1 = $wpdb->prefix . 'apollo_reminders';
        dbDelta( "CREATE TABLE $t1 (
            id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id        BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            title          VARCHAR(255) NOT NULL DEFAULT '',
            message        TEXT NOT NULL,
            due_at_gmt     DATETIME NOT NULL,
            channels       VARCHAR(255) NOT NULL DEFAULT 'notif',
            status         VARCHAR(20) NOT NULL DEFAULT 'pending',
            context        VARCHAR(50) NOT NULL DEFAULT 'manual',
            ref_type       VARCHAR(50) NOT NULL DEFAULT '',
            ref_id         BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            recurrence     VARCHAR(30) NOT NULL DEFAULT 'none',
            recurrence_end DATETIME DEFAULT NULL,
            attempts       TINYINT UNSIGNED NOT NULL DEFAULT 0,
            last_error     TEXT DEFAULT NULL,
            sent_at_gmt    DATETIME DEFAULT NULL,
            created_at_gmt DATETIME NOT NULL,
            updated_at_gmt DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_queue      (status, due_at_gmt),
            KEY idx_user       (user_id, status),
            KEY idx_ref        (ref_type, ref_id),
            KEY idx_context    (context)
        ) $charset;" );

        // 2. Telegram chat_id mapping (user_id ↔ chat_id)
        $t2 = $wpdb->prefix . 'apollo_remind_telegram';
        dbDelta( "CREATE TABLE $t2 (
            id        BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id   BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            chat_id   VARCHAR(64) NOT NULL,
            username  VARCHAR(100) NOT NULL DEFAULT '',
            linked_at DATETIME NOT NULL,
            active    TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY uq_chat   (chat_id),
            KEY idx_user (user_id)
        ) $charset;" );

        // 3. Web Push subscriptions (per-device, per-user)
        $t3 = $wpdb->prefix . 'apollo_remind_push_subs';
        dbDelta( "CREATE TABLE $t3 (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id     BIGINT(20) UNSIGNED NOT NULL,
            endpoint    TEXT NOT NULL,
            p256dh      VARCHAR(512) NOT NULL DEFAULT '',
            auth_key    VARCHAR(512) NOT NULL DEFAULT '',
            user_agent  VARCHAR(255) NOT NULL DEFAULT '',
            created_at  DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_user (user_id)
        ) $charset;" );

        // 4. Delivery log — audit trail for every send attempt
        $t4 = $wpdb->prefix . 'apollo_remind_log';
        dbDelta( "CREATE TABLE $t4 (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reminder_id BIGINT(20) UNSIGNED NOT NULL,
            channel     VARCHAR(30) NOT NULL,
            status      VARCHAR(20) NOT NULL DEFAULT 'sent',
            response    TEXT DEFAULT NULL,
            sent_at_gmt DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_reminder (reminder_id),
            KEY idx_channel  (channel, sent_at_gmt)
        ) $charset;" );
    }

    public static function schedule_cron(): void {
        if ( ! wp_next_scheduled( 'apollo_remind_process_queue' ) ) {
            wp_schedule_event( time() + 60, 'apollo_remind_two_minutes', 'apollo_remind_process_queue' );
        }
        if ( ! wp_next_scheduled( 'apollo_remind_daily_cleanup' ) ) {
            wp_schedule_event( time() + 300, 'daily', 'apollo_remind_daily_cleanup' );
        }
    }
}
