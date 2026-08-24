<?php

/**
 * Apollo Chat — Activation & DB Schema v2.0
 *
 * Tables:
 *   apollo_chat_threads       — Thread metadata + group support
 *   apollo_chat_messages      — Messages with type, edit, reply-to, attachments
 *   apollo_chat_participants  — Per-user thread state, read receipts
 *   apollo_chat_reactions     — Emoji reactions per message
 *   apollo_chat_typing        — Ephemeral typing indicators
 *   apollo_chat_presence      — User online/offline status
 *   apollo_chat_blocks        — User-to-user blocks
 *   apollo_chat_attachments   — File/image/audio/video metadata
 *
 * @package Apollo\Chat
 */

declare(strict_types=1);

namespace Apollo\Chat;

if (! defined('ABSPATH')) {
    exit;
}

final class Activation
{
    public static function activate(): void
    {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix . 'apollo_';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Chat Threads (upgraded: is_group, group_name, avatar)
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$prefix}chat_threads (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            subject VARCHAR(255) DEFAULT '',
            is_group TINYINT(1) DEFAULT 0,
            group_name VARCHAR(255) DEFAULT '',
            group_avatar VARCHAR(500) DEFAULT '',
            context VARCHAR(100) DEFAULT '',
            last_message_id BIGINT UNSIGNED DEFAULT 0,
            last_sender_id BIGINT UNSIGNED DEFAULT 0,
            last_message_at DATETIME,
            last_message_preview VARCHAR(255) DEFAULT '',
            created_by BIGINT UNSIGNED DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY last_message_at (last_message_at),
            KEY created_by (created_by),
            KEY idx_context (context)
        ) {$charset}"
        );

        // 2. Chat Messages (upgraded: type, reply_to, attachment, edit, soft-delete)
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$prefix}chat_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            thread_id BIGINT UNSIGNED NOT NULL,
            sender_id BIGINT UNSIGNED NOT NULL,
            message LONGTEXT NOT NULL,
            message_type VARCHAR(20) DEFAULT 'text',
            reply_to_id BIGINT UNSIGNED DEFAULT 0,
            attachment_id BIGINT UNSIGNED DEFAULT 0,
            is_deleted TINYINT(1) DEFAULT 0,
            edited_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY thread_id (thread_id),
            KEY sender_id (sender_id),
            KEY created_at (created_at),
            KEY reply_to_id (reply_to_id),
            KEY message_type (message_type)
        ) {$charset}"
        );

        // 3. Chat Participants (upgraded: read receipt status, muted)
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$prefix}chat_participants (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            thread_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            unread_count INT UNSIGNED DEFAULT 0,
            is_deleted TINYINT(1) DEFAULT 0,
            is_muted TINYINT(1) DEFAULT 0,
            last_read_at DATETIME,
            last_read_message_id BIGINT UNSIGNED DEFAULT 0,
            role VARCHAR(20) DEFAULT 'member',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY thread_user (thread_id, user_id),
            KEY user_id (user_id),
            KEY unread_count (unread_count)
        ) {$charset}"
        );

        // 4. Chat Reactions (emoji per message per user)
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$prefix}chat_reactions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            message_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            emoji VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY msg_user_emoji (message_id, user_id, emoji),
            KEY message_id (message_id),
            KEY user_id (user_id)
        ) {$charset}"
        );

        // 5. Typing Indicators (ephemeral, cleaned every 10s)
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$prefix}chat_typing (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            thread_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY thread_user (thread_id, user_id),
            KEY updated_at (updated_at)
        ) {$charset}"
        );

        // 6. User Presence (online/offline heartbeat)
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$prefix}chat_presence (
            user_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(20) DEFAULT 'online',
            last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            KEY last_seen (last_seen)
        ) {$charset}"
        );

        // 7. User Blocks
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$prefix}chat_blocks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            blocker_id BIGINT UNSIGNED NOT NULL,
            blocked_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY blocker_blocked (blocker_id, blocked_id),
            KEY blocked_id (blocked_id)
        ) {$charset}"
        );

        // 8. Chat Attachments (files, images, audio, video)
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$prefix}chat_attachments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            message_id BIGINT UNSIGNED DEFAULT 0,
            user_id BIGINT UNSIGNED NOT NULL,
            wp_attachment_id BIGINT UNSIGNED DEFAULT 0,
            file_url VARCHAR(500) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_type VARCHAR(20) DEFAULT 'file',
            mime_type VARCHAR(100) DEFAULT '',
            file_size BIGINT UNSIGNED DEFAULT 0,
            thumb_url VARCHAR(500) DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY message_id (message_id),
            KEY user_id (user_id)
        ) {$charset}"
        );

        // 9. Spam Reports (user-reported messages)
        dbDelta(
            "CREATE TABLE IF NOT EXISTS {$prefix}chat_reports (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            message_id BIGINT UNSIGNED NOT NULL,
            reporter_id BIGINT UNSIGNED NOT NULL,
            reason VARCHAR(255) DEFAULT '',
            status VARCHAR(20) DEFAULT 'pending',
            resolved_by BIGINT UNSIGNED DEFAULT NULL,
            resolved_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY msg_reporter (message_id, reporter_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset}"
        );

        update_option('apollo_chat_version', APOLLO_CHAT_VERSION);
        flush_rewrite_rules();
    }
}
