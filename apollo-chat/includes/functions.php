<?php

/**
 * Apollo Chat — Helper Functions v2.0
 *
 * Core messaging + premium features:
 * - Typing indicators, read receipts, reactions
 * - File attachments, message editing/deletion
 * - Reply-to threading, search, presence
 * - User blocking, group management
 *
 * @package Apollo\Chat
 */

if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// CORE MESSAGING
// ═══════════════════════════════════════════════════════════════════

/**
 * Send a new message (or reply to a thread).
 * Supports: reply_to_id, attachment_id, message type, group creation.
 */
function apollo_send_message(array $args): int|false
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $r = wp_parse_args(
        $args,
        array(
            'sender_id'     => get_current_user_id(),
            'thread_id'     => 0,
            'recipients'    => array(),
            'subject'       => '',
            'message'       => '',
            'reply_to_id'   => 0,
            'attachment_id' => 0,
            'type'          => 'text',
            'is_group'      => false,
            'group_name'    => '',
        )
    );

    if (empty($r['message']) && ! $r['attachment_id']) {
        return false;
    }
    if (! $r['sender_id']) {
        return false;
    }

    $now = current_time('mysql');

    // ── New thread or reply? ───────────────────────────
    if ($r['thread_id'] > 0) {
        $ok = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
                $r['thread_id'],
                $r['sender_id']
            )
        );
        if (! $ok) {
            return false;
        }
    } else {
        if (empty($r['recipients'])) {
            // #region agent log
            @file_put_contents(
                ( defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '' ) . '/debug-161c5c.log',
                wp_json_encode(
                    array(
                        'sessionId'    => '161c5c',
                        'runId'        => 'chat-pre',
                        'hypothesisId' => 'C6',
                        'location'     => 'functions.php:apollo_send_message',
                        'message'      => 'fail: empty recipients on new thread',
                        'data'         => array('sender' => (int) $r['sender_id']),
                        'timestamp'    => (int) round(microtime(true) * 1000),
                    )
                ) . "\n",
                FILE_APPEND
            );
            // #endregion
            return false;
        }

        $recipients = array_values(
            array_unique(
                array_filter(
                    array_map('intval', (array) $r['recipients']),
                    static function (int $id) use ($r): bool {
                        return $id > 0 && $id !== (int) $r['sender_id'];
                    }
                )
            )
        );

        if (empty($recipients)) {
            return false;
        }

        $force_group = ! empty($r['is_group']) || count($recipients) > 1;

        // 1:1 DM — WhatsApp/Telegram model: exactly one thread per pair.
        if (! $force_group) {
            $peer         = (int) $recipients[0];
            $r['thread_id'] = apollo_chat_consolidate_dm_pair(
                (int) $r['sender_id'],
                $peer,
                (string) $r['subject']
            );

            // #region agent log
            @file_put_contents(
                ( defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '' ) . '/debug-161c5c.log',
                wp_json_encode(
                    array(
                        'sessionId'    => '161c5c',
                        'runId'        => 'chat-dm-fix',
                        'hypothesisId' => 'DM1',
                        'location'     => 'functions.php:apollo_send_message',
                        'message'      => '1:1 reuse/consolidate before insert',
                        'data'         => array(
                            'sender'    => (int) $r['sender_id'],
                            'peer'      => $peer,
                            'thread_id' => (int) $r['thread_id'],
                        ),
                        'timestamp'    => (int) round(microtime(true) * 1000),
                    )
                ) . "\n",
                FILE_APPEND
            );
            // #endregion

            if ($r['thread_id'] <= 0) {
                return false;
            }
        } else {
            $thread_ok = $wpdb->insert(
                "{$pfx}chat_threads",
                array(
                    'subject'    => sanitize_text_field($r['subject']),
                    'is_group'   => 1,
                    'group_name' => sanitize_text_field($r['group_name']),
                    'created_by' => $r['sender_id'],
                    'created_at' => $now,
                )
            );
            $r['thread_id'] = (int) $wpdb->insert_id;

            // #region agent log
            if (false === $thread_ok || $r['thread_id'] <= 0) {
                @file_put_contents(
                    ( defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '' ) . '/debug-161c5c.log',
                    wp_json_encode(
                        array(
                            'sessionId'    => '161c5c',
                            'runId'        => 'chat-pre',
                            'hypothesisId' => 'C6',
                            'location'     => 'functions.php:apollo_send_message',
                            'message'      => 'fail: chat_threads insert',
                            'data'         => array(
                                'insert_ok'  => $thread_ok !== false,
                                'insert_id'  => $r['thread_id'],
                                'db_error'   => (string) $wpdb->last_error,
                                'last_query' => substr((string) $wpdb->last_query, 0, 240),
                            ),
                            'timestamp'    => (int) round(microtime(true) * 1000),
                        )
                    ) . "\n",
                    FILE_APPEND
                );
                return false;
            }
            // #endregion

            $all = array_unique(array_merge(array( (int) $r['sender_id'] ), $recipients));
            foreach ($all as $uid) {
                $wpdb->insert(
                    "{$pfx}chat_participants",
                    array(
                        'thread_id'    => $r['thread_id'],
                        'user_id'      => (int) $uid,
                        'unread_count' => ((int) $uid === (int) $r['sender_id']) ? 0 : 1,
                        'role'         => ((int) $uid === (int) $r['sender_id']) ? 'admin' : 'member',
                        'created_at'   => $now,
                    )
                );
            }
        }
    }

    // ── Insert message ─────────────────────────────────
    $msg_ok = $wpdb->insert(
        "{$pfx}chat_messages",
        array(
            'thread_id'     => $r['thread_id'],
            'sender_id'     => $r['sender_id'],
            'message'       => wp_kses_post($r['message']),
            'message_type'  => sanitize_text_field($r['type']),
            'reply_to_id'   => (int) $r['reply_to_id'],
            'attachment_id' => (int) $r['attachment_id'],
            'created_at'    => $now,
        )
    );
    $msg_id = (int) $wpdb->insert_id;

    // #region agent log
    if (false === $msg_ok || $msg_id <= 0) {
        @file_put_contents(
            ( defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '' ) . '/debug-161c5c.log',
            wp_json_encode(
                array(
                    'sessionId'    => '161c5c',
                    'runId'        => 'chat-pre',
                    'hypothesisId' => 'C6',
                    'location'     => 'functions.php:apollo_send_message',
                    'message'      => 'fail: chat_messages insert',
                    'data'         => array(
                        'thread_id'  => (int) $r['thread_id'],
                        'insert_ok'  => $msg_ok !== false,
                        'insert_id'  => $msg_id,
                        'db_error'   => (string) $wpdb->last_error,
                        'last_query' => substr((string) $wpdb->last_query, 0, 240),
                    ),
                    'timestamp'    => (int) round(microtime(true) * 1000),
                )
            ) . "\n",
            FILE_APPEND
        );
        return false;
    }
    // #endregion

    // ── Link attachment to message ─────────────────────
    if ($r['attachment_id']) {
        $wpdb->update(
            "{$pfx}chat_attachments",
            array('message_id' => $msg_id),
            array('id' => $r['attachment_id'])
        );
    }

    // ── Update thread metadata ─────────────────────────
    $preview = wp_trim_words($r['message'], 10, '...');
    $wpdb->update(
        "{$pfx}chat_threads",
        array(
            'last_message_id'      => $msg_id,
            'last_sender_id'       => $r['sender_id'],
            'last_message_at'      => $now,
            'last_message_preview' => $preview,
        ),
        array('id' => $r['thread_id'])
    );

    // ── Increment unread (except sender, and muted users) ──
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$pfx}chat_participants
         SET unread_count = unread_count + 1
         WHERE thread_id = %d AND user_id != %d AND is_deleted = 0",
            $r['thread_id'],
            $r['sender_id']
        )
    );

    // ── Create notification ────────────────────────────
    if (function_exists('apollo_create_notification')) {
        $sender      = get_userdata($r['sender_id']);
        $sender_name = $sender ? $sender->display_name : 'Alguém';
        $recips      = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT user_id FROM {$pfx}chat_participants
             WHERE thread_id=%d AND user_id!=%d AND is_deleted=0 AND is_muted=0",
                $r['thread_id'],
                $r['sender_id']
            )
        );
        foreach ($recips as $uid) {
            apollo_create_notification(
                (int) $uid,
                'message',
                "Nova mensagem de {$sender_name}",
                $preview,
                apollo_chat_thread_permalink((int) $r['thread_id'], (int) $uid),
                array(
                    'thread_id' => $r['thread_id'],
                    'sender_id' => $r['sender_id'],
                )
            );
        }
    }

    do_action('apollo/chat/message_sent', $msg_id, $r['thread_id'], $r['sender_id']);
    return $r['thread_id'];
}

/**
 * Get threads for a user (inbox).
 * 1:1 DMs are consolidated (one chat per peer) before return — WhatsApp-style.
 */
function apollo_get_user_threads(int $user_id, int $limit = 20, int $offset = 0): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    // Over-fetch so dedupe still fills the page after collapsing duplicate DMs.
    $fetch = max(50, $limit * 5);

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT t.*, r.unread_count, r.last_read_at, r.is_muted, r.last_read_message_id
         FROM {$pfx}chat_threads t
         INNER JOIN {$pfx}chat_participants r ON t.id = r.thread_id
         WHERE r.user_id = %d AND r.is_deleted = 0
         ORDER BY COALESCE(t.last_message_at, t.created_at) DESC, t.id DESC
         LIMIT %d OFFSET %d",
            $user_id,
            $fetch,
            $offset
        ),
        ARRAY_A
    );

    if (! $results) {
        return array();
    }

    foreach ($results as &$thread) {
        // Last message
        $thread['last_message'] = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT m.*, u.display_name, u.user_login
             FROM {$pfx}chat_messages m
             LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
             WHERE m.thread_id = %d AND m.is_deleted = 0
             ORDER BY m.created_at DESC LIMIT 1",
                $thread['id']
            ),
            ARRAY_A
        );

        // Other participants
        $thread['participants'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.user_id, u.display_name, u.user_login
             FROM {$pfx}chat_participants r
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             WHERE r.thread_id = %d AND r.user_id != %d AND r.is_deleted = 0",
                $thread['id'],
                $user_id
            ),
            ARRAY_A
        );
    }
    unset($thread);

    // Collapse duplicate 1:1 threads into one row per peer (merge history).
    $seen_peers = array();
    $deduped    = array();

    foreach ($results as $thread) {
        $is_group = (int) ( $thread['is_group'] ?? 0 ) === 1;
        $parts    = $thread['participants'] ?? array();

        if (! $is_group && count($parts) === 1) {
            $peer = (int) ( $parts[0]['user_id'] ?? 0 );
            if ($peer > 0) {
                if (isset($seen_peers[ $peer ])) {
                    continue;
                }
                $canonical = apollo_chat_consolidate_dm_pair($user_id, $peer);
                $seen_peers[ $peer ] = $canonical;

                if ($canonical > 0 && $canonical !== (int) $thread['id']) {
                    // Reload canonical row metadata briefly.
                    $canon_row = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT t.*, r.unread_count, r.last_read_at, r.is_muted, r.last_read_message_id
                         FROM {$pfx}chat_threads t
                         INNER JOIN {$pfx}chat_participants r ON t.id = r.thread_id
                         WHERE t.id = %d AND r.user_id = %d AND r.is_deleted = 0",
                            $canonical,
                            $user_id
                        ),
                        ARRAY_A
                    );
                    if ($canon_row) {
                        $canon_row['participants'] = $parts;
                        $canon_row['last_message'] = $wpdb->get_row(
                            $wpdb->prepare(
                                "SELECT m.*, u.display_name, u.user_login
                             FROM {$pfx}chat_messages m
                             LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
                             WHERE m.thread_id = %d AND m.is_deleted = 0
                             ORDER BY m.created_at DESC LIMIT 1",
                                $canonical
                            ),
                            ARRAY_A
                        );
                        $thread = $canon_row;
                    }
                }
            }
        }

        $deduped[] = $thread;
        if (count($deduped) >= $limit) {
            break;
        }
    }

    return $deduped;
}

/**
 * Get messages in a thread (marks as read).
 */
function apollo_get_thread_messages(int $thread_id, int $user_id, int $limit = 50, int $offset = 0): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $ok = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $thread_id,
            $user_id
        )
    );
    if (! $ok) {
        return array();
    }

    $messages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_login
         FROM {$pfx}chat_messages m
         LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
         WHERE m.thread_id = %d
         ORDER BY m.created_at ASC
         LIMIT %d OFFSET %d",
            $thread_id,
            $limit,
            $offset
        ),
        ARRAY_A
    );

    // Mark as read
    $last_msg_id = 0;
    if (! empty($messages)) {
        $last        = end($messages);
        $last_msg_id = (int) $last['id'];
    }

    $wpdb->update(
        "{$pfx}chat_participants",
        array(
            'unread_count'         => 0,
            'last_read_at'         => current_time('mysql'),
            'last_read_message_id' => $last_msg_id,
        ),
        array(
            'thread_id' => $thread_id,
            'user_id'   => $user_id,
        )
    );

    return $messages ?: array();
}

/** Get global unread message count. */
function apollo_get_unread_message_count(int $user_id): int
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COALESCE(SUM(unread_count), 0) FROM {$pfx}chat_participants WHERE user_id=%d AND is_deleted=0",
            $user_id
        )
    );
}

/** Soft-delete a thread for a user. */
function apollo_delete_thread_for_user(int $thread_id, int $user_id): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    return (bool) $wpdb->update(
        "{$pfx}chat_participants",
        array('is_deleted' => 1),
        array(
            'thread_id' => $thread_id,
            'user_id'   => $user_id,
        )
    );
}

// ═══════════════════════════════════════════════════════════════════
// REACTIONS
// ═══════════════════════════════════════════════════════════════════

/** Get all reactions for a message. */
function apollo_get_message_reactions(int $message_id): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.emoji, r.user_id, u.display_name
         FROM {$pfx}chat_reactions r
         LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
         WHERE r.message_id = %d",
            $message_id
        ),
        ARRAY_A
    );

    // Group by emoji
    $grouped = array();
    foreach ($rows as $row) {
        $e = $row['emoji'];
        if (! isset($grouped[$e])) {
            $grouped[$e] = array(
                'emoji' => $e,
                'count' => 0,
                'users' => array(),
            );
        }
        ++$grouped[$e]['count'];
        $grouped[$e]['users'][] = array(
            'id'   => (int) $row['user_id'],
            'name' => $row['display_name'],
        );
    }
    return array_values($grouped);
}

/** Toggle reaction (add/remove). */
function apollo_chat_toggle_reaction(int $message_id, int $user_id, string $emoji): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$pfx}chat_reactions WHERE message_id=%d AND user_id=%d AND emoji=%s",
            $message_id,
            $user_id,
            $emoji
        )
    );

    if ($exists) {
        $wpdb->delete(
            "{$pfx}chat_reactions",
            array(
                'message_id' => $message_id,
                'user_id'    => $user_id,
                'emoji'      => $emoji,
            )
        );
        $action = 'removed';
    } else {
        $wpdb->insert(
            "{$pfx}chat_reactions",
            array(
                'message_id' => $message_id,
                'user_id'    => $user_id,
                'emoji'      => $emoji,
                'created_at' => current_time('mysql'),
            )
        );
        $action = 'added';
    }

    return array(
        'action'    => $action,
        'reactions' => apollo_get_message_reactions($message_id),
    );
}

// ═══════════════════════════════════════════════════════════════════
// TYPING INDICATORS
// ═══════════════════════════════════════════════════════════════════

/** Set typing status for a user in a thread. */
function apollo_chat_set_typing(int $thread_id, int $user_id, bool $is_typing): void
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    if ($is_typing) {
        $wpdb->replace(
            "{$pfx}chat_typing",
            array(
                'thread_id'  => $thread_id,
                'user_id'    => $user_id,
                'updated_at' => current_time('mysql'),
            )
        );
    } else {
        $wpdb->delete(
            "{$pfx}chat_typing",
            array(
                'thread_id' => $thread_id,
                'user_id'   => $user_id,
            )
        );
    }
}

/** Get who is typing in a thread (excluding current user, within 5s). */
function apollo_chat_get_typing(int $thread_id, int $user_id): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT t.user_id, u.display_name
         FROM {$pfx}chat_typing t
         LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
         WHERE t.thread_id = %d AND t.user_id != %d
           AND t.updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)",
            $thread_id,
            $user_id
        ),
        ARRAY_A
    ) ?: array();
}

// ═══════════════════════════════════════════════════════════════════
// PRESENCE (online/offline)
// ═══════════════════════════════════════════════════════════════════

/** Update user's last-seen timestamp (heartbeat). */
function apollo_chat_update_presence(int $user_id): void
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    $wpdb->replace(
        "{$pfx}chat_presence",
        array(
            'user_id'   => $user_id,
            'status'    => 'online',
            'last_seen' => current_time('mysql'),
        )
    );
}

/** Check if a user is online (seen within 2 minutes). */
function apollo_chat_is_online(int $user_id): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_presence
         WHERE user_id = %d AND last_seen > DATE_SUB(NOW(), INTERVAL 2 MINUTE)",
            $user_id
        )
    );
}

/** Get online contacts for a user. */
function apollo_chat_get_online_contacts(int $user_id): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    return $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT p.user_id
         FROM {$pfx}chat_presence p
         INNER JOIN {$pfx}chat_participants r1 ON p.user_id = r1.user_id
         INNER JOIN {$pfx}chat_participants r2 ON r1.thread_id = r2.thread_id AND r2.user_id = %d
         WHERE p.last_seen > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
           AND p.user_id != %d",
            $user_id,
            $user_id
        )
    ) ?: array();
}

// ═══════════════════════════════════════════════════════════════════
// READ RECEIPTS
// ═══════════════════════════════════════════════════════════════════

/** Mark thread as read and update last_read_message_id. */
function apollo_chat_mark_read(int $thread_id, int $user_id): void
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $last_id = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT MAX(id) FROM {$pfx}chat_messages WHERE thread_id = %d AND is_deleted = 0",
            $thread_id
        )
    );

    $wpdb->update(
        "{$pfx}chat_participants",
        array(
            'unread_count'         => 0,
            'last_read_at'         => current_time('mysql'),
            'last_read_message_id' => $last_id,
        ),
        array(
            'thread_id' => $thread_id,
            'user_id'   => $user_id,
        )
    );
}

/** Get read receipts for a thread. Returns array of last_read_message_id per user. */
function apollo_chat_get_read_receipts(int $thread_id, int $current_user_id): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.user_id, r.last_read_message_id, r.last_read_at, u.display_name
         FROM {$pfx}chat_participants r
         LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
         WHERE r.thread_id = %d AND r.user_id != %d AND r.is_deleted = 0",
            $thread_id,
            $current_user_id
        ),
        ARRAY_A
    ) ?: array();
}

// ═══════════════════════════════════════════════════════════════════
// MESSAGE EDITING & DELETION
// ═══════════════════════════════════════════════════════════════════

/** Edit a message (own messages only, within 15 min). */
function apollo_chat_edit_message(int $message_id, int $user_id, string $new_text): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $msg = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$pfx}chat_messages WHERE id = %d AND sender_id = %d AND is_deleted = 0",
            $message_id,
            $user_id
        )
    );

    if (! $msg) {
        return false;
    }

    // Allow edit within 15 minutes
    $created = strtotime($msg->created_at);
    if ((time() - $created) > 900) {
        return false;
    }

    return (bool) $wpdb->update(
        "{$pfx}chat_messages",
        array(
            'message'    => wp_kses_post($new_text),
            'edited_at'  => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ),
        array('id' => $message_id)
    );
}

/** Soft-delete a message (own messages only). */
function apollo_chat_delete_message(int $message_id, int $user_id): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    return (bool) $wpdb->update(
        "{$pfx}chat_messages",
        array(
            'is_deleted' => 1,
            'message'    => '',
            'updated_at' => current_time('mysql'),
        ),
        array(
            'id'        => $message_id,
            'sender_id' => $user_id,
        )
    );
}

// ═══════════════════════════════════════════════════════════════════
// REPLY-TO PREVIEW
// ═══════════════════════════════════════════════════════════════════

/** Get a short preview of a replied-to message. */
function apollo_get_reply_preview(int $message_id): ?array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT m.id, m.sender_id, m.message, m.message_type, m.is_deleted, u.display_name
         FROM {$pfx}chat_messages m
         LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
         WHERE m.id = %d",
            $message_id
        ),
        ARRAY_A
    );

    if (! $row) {
        return null;
    }

    return array(
        'id'        => (int) $row['id'],
        'sender'    => $row['display_name'] ?? 'Desconhecido',
        'sender_id' => (int) $row['sender_id'],
        'preview'   => (int) $row['is_deleted'] === 1
            ? 'Mensagem apagada'
            : wp_trim_words($row['message'], 12, '...'),
        'type'      => $row['message_type'],
    );
}

// ═══════════════════════════════════════════════════════════════════
// SEARCH
// ═══════════════════════════════════════════════════════════════════

/** Search messages across user's threads. */
function apollo_chat_search_messages(int $user_id, string $query, int $limit = 20): array
{
    global $wpdb;
    $pfx  = $wpdb->prefix . 'apollo_';
    $like = '%' . $wpdb->esc_like($query) . '%';

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT m.id, m.thread_id, m.sender_id, m.message, m.created_at,
                u.display_name AS sender_name, t.subject
         FROM {$pfx}chat_messages m
         INNER JOIN {$pfx}chat_participants r ON m.thread_id = r.thread_id
         INNER JOIN {$pfx}chat_threads t ON m.thread_id = t.id
         LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
         WHERE r.user_id = %d AND r.is_deleted = 0
           AND m.is_deleted = 0 AND m.message LIKE %s
         GROUP BY m.id
         ORDER BY m.created_at DESC
         LIMIT %d",
            $user_id,
            $like,
            $limit
        ),
        ARRAY_A
    ) ?: array();
}

// ═══════════════════════════════════════════════════════════════════
// USER BLOCKING
// ═══════════════════════════════════════════════════════════════════

function apollo_chat_block_user(int $blocker_id, int $blocked_id): void
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    $wpdb->replace(
        "{$pfx}chat_blocks",
        array(
            'blocker_id' => $blocker_id,
            'blocked_id' => $blocked_id,
            'created_at' => current_time('mysql'),
        )
    );
}

function apollo_chat_unblock_user(int $blocker_id, int $blocked_id): void
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    $wpdb->delete(
        "{$pfx}chat_blocks",
        array(
            'blocker_id' => $blocker_id,
            'blocked_id' => $blocked_id,
        )
    );
}

/** Check if sender is blocked by anyone in the thread. */
function apollo_chat_is_blocked_in_thread(int $thread_id, int $sender_id): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_blocks b
         INNER JOIN {$pfx}chat_participants r ON b.blocker_id = r.user_id
         WHERE r.thread_id = %d AND b.blocked_id = %d",
            $thread_id,
            $sender_id
        )
    );
}

// ═══════════════════════════════════════════════════════════════════
// GROUP THREAD MANAGEMENT
// ═══════════════════════════════════════════════════════════════════

function apollo_chat_get_thread_members(int $thread_id, int $user_id): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    // Verify membership
    $ok = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $thread_id,
            $user_id
        )
    );
    if (! $ok) {
        return array();
    }

    $members = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.user_id, r.role, r.is_muted, u.display_name, u.user_login
         FROM {$pfx}chat_participants r
         LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
         WHERE r.thread_id = %d AND r.is_deleted = 0",
            $thread_id
        ),
        ARRAY_A
    );

    foreach ($members as &$m) {
        $m['is_online']  = apollo_chat_is_online((int) $m['user_id']);
        $m['avatar_url'] = function_exists('apollo_get_user_avatar_url')
            ? apollo_get_user_avatar_url((int) $m['user_id'])
            : get_avatar_url((int) $m['user_id'], array('size' => 40));
    }

    return $members;
}

function apollo_chat_add_thread_members(int $thread_id, int $user_id, array $new_members): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    // Only admin can add
    $role = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT role FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $thread_id,
            $user_id
        )
    );
    if ($role !== 'admin') {
        return false;
    }

    $now = current_time('mysql');
    foreach ($new_members as $uid) {
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d",
                $thread_id,
                $uid
            )
        );
        if ($exists) {
            $wpdb->update(
                "{$pfx}chat_participants",
                array('is_deleted' => 0),
                array(
                    'thread_id' => $thread_id,
                    'user_id'   => $uid,
                )
            );
        } else {
            $wpdb->insert(
                "{$pfx}chat_participants",
                array(
                    'thread_id'    => $thread_id,
                    'user_id'      => $uid,
                    'unread_count' => 0,
                    'role'         => 'member',
                    'created_at'   => $now,
                )
            );
        }
    }

    // Mark as group if > 2 participants
    $total = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND is_deleted=0",
            $thread_id
        )
    );
    if ($total > 2) {
        $wpdb->update("{$pfx}chat_threads", array('is_group' => 1), array('id' => $thread_id));
    }

    return true;
}

function apollo_chat_remove_thread_member(int $thread_id, int $user_id, int $remove_id): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    // Admin can remove others; anyone can leave
    if ($remove_id !== $user_id) {
        $role = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT role FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
                $thread_id,
                $user_id
            )
        );
        if ($role !== 'admin') {
            return false;
        }
    }

    return (bool) $wpdb->update(
        "{$pfx}chat_participants",
        array('is_deleted' => 1),
        array(
            'thread_id' => $thread_id,
            'user_id'   => $remove_id,
        )
    );
}

// ═══════════════════════════════════════════════════════════════════
// OLDER MESSAGES (scroll-up pagination)
// ═══════════════════════════════════════════════════════════════════

function apollo_chat_get_older_messages(int $thread_id, int $user_id, int $before_id, int $limit = 30): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $ok = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $thread_id,
            $user_id
        )
    );
    if (! $ok) {
        return array();
    }

    if ($before_id > 0) {
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, u.display_name, u.user_login
             FROM {$pfx}chat_messages m
             LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
             WHERE m.thread_id = %d AND m.id < %d
             ORDER BY m.created_at DESC
             LIMIT %d",
                $thread_id,
                $before_id,
                $limit
            ),
            ARRAY_A
        ) ?: array();
    }

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_login
         FROM {$pfx}chat_messages m
         LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
         WHERE m.thread_id = %d
         ORDER BY m.created_at DESC
         LIMIT %d",
            $thread_id,
            $limit
        ),
        ARRAY_A
    ) ?: array();
}

// ═══════════════════════════════════════════════════════════════════
// ATTACHMENTS & THREAD META
// ═══════════════════════════════════════════════════════════════════

/** Get attachment data by ID. */
function apollo_chat_get_attachment(int $attachment_id): ?array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$pfx}chat_attachments WHERE id = %d",
            $attachment_id
        ),
        ARRAY_A
    );
    return $row ?: null;
}

/** Get thread metadata for a user. */
function apollo_chat_get_thread_meta(int $thread_id, int $user_id): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $thread = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$pfx}chat_threads WHERE id = %d",
            $thread_id
        ),
        ARRAY_A
    );

    $participants = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.user_id, r.role, u.display_name, u.user_login
         FROM {$pfx}chat_participants r
         LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
         WHERE r.thread_id = %d AND r.is_deleted = 0",
            $thread_id
        ),
        ARRAY_A
    );

    foreach ($participants as &$p) {
        $p['avatar_url'] = function_exists('apollo_get_user_avatar_url')
            ? apollo_get_user_avatar_url((int) $p['user_id'])
            : get_avatar_url((int) $p['user_id'], array('size' => 40));
        $p['is_online']  = apollo_chat_is_online((int) $p['user_id']);
    }

    return array(
        'id'           => $thread ? (int) $thread['id'] : 0,
        'subject'      => $thread['subject'] ?? '',
        'is_group'     => (int) ($thread['is_group'] ?? 0) === 1,
        'group_name'   => $thread['group_name'] ?? '',
        'participants' => $participants,
        'created_at'   => $thread['created_at'] ?? '',
    );
}

// ═══════════════════════════════════════════════════════════════════
// MUTE / UNMUTE THREAD
// ═══════════════════════════════════════════════════════════════════

/** Toggle mute on a thread for a user. */
function apollo_chat_toggle_mute(int $thread_id, int $user_id, bool $mute): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    return (bool) $wpdb->update(
        "{$pfx}chat_participants",
        array('is_muted' => $mute ? 1 : 0),
        array(
            'thread_id' => $thread_id,
            'user_id'   => $user_id,
        )
    );
}

/** Check if a thread is muted for a user. */
function apollo_chat_is_muted(int $thread_id, int $user_id): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT is_muted FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $thread_id,
            $user_id
        )
    );
}

// ═══════════════════════════════════════════════════════════════════
// FORWARD MESSAGE
// ═══════════════════════════════════════════════════════════════════

/** Forward a message to another thread. */
function apollo_chat_forward_message(int $message_id, int $target_thread_id, int $user_id): int|false
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    // Get original message
    $orig = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$pfx}chat_messages WHERE id = %d AND is_deleted = 0",
            $message_id
        ),
        ARRAY_A
    );
    if (! $orig) {
        return false;
    }

    // Verify user belongs to target thread
    $ok = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $target_thread_id,
            $user_id
        )
    );
    if (! $ok) {
        return false;
    }

    $sender      = get_userdata((int) $orig['sender_id']);
    $sender_name = $sender ? $sender->display_name : 'Alguém';
    $fwd_text    = "↪ Encaminhada de {$sender_name}:\n" . $orig['message'];

    return apollo_send_message(
        array(
            'sender_id'     => $user_id,
            'thread_id'     => $target_thread_id,
            'message'       => $fwd_text,
            'type'          => 'forwarded',
            'attachment_id' => (int) ($orig['attachment_id'] ?? 0),
        )
    );
}

// ═══════════════════════════════════════════════════════════════════
// PINNED MESSAGES
// ═══════════════════════════════════════════════════════════════════

/** Pin a message in a thread. Uses options storage (lightweight). */
function apollo_chat_pin_message(int $thread_id, int $message_id, int $user_id): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    // Verify membership
    $ok = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $thread_id,
            $user_id
        )
    );
    if (! $ok) {
        return false;
    }

    // Verify message belongs to thread
    $msg = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_messages WHERE id=%d AND thread_id=%d AND is_deleted=0",
            $message_id,
            $thread_id
        )
    );
    if (! $msg) {
        return false;
    }

    $option_key = 'apollo_chat_pinned_' . $thread_id;
    $pinned     = get_option($option_key, array());
    if (! is_array($pinned)) {
        $pinned = array();
    }

    if (! in_array($message_id, $pinned, true)) {
        $pinned[] = $message_id;
        $pinned   = array_slice($pinned, -25);
        update_option($option_key, $pinned, false);
    }

    return true;
}

/** Unpin a message. */
function apollo_chat_unpin_message(int $thread_id, int $message_id, int $user_id): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $ok = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $thread_id,
            $user_id
        )
    );
    if (! $ok) {
        return false;
    }

    $option_key = 'apollo_chat_pinned_' . $thread_id;
    $pinned     = get_option($option_key, array());
    if (! is_array($pinned)) {
        return true;
    }

    $pinned = array_values(array_diff($pinned, array($message_id)));
    update_option($option_key, $pinned, false);
    return true;
}

// ═══════════════════════════════════════════════════════════════════
// THREAD CONTEXT — Find-or-Create for integrations (Classifieds, etc.)
// ═══════════════════════════════════════════════════════════════════

/**
 * All 1:1 DM thread IDs between two users (including soft-deleted participant rows),
 * newest activity first. Groups are excluded.
 *
 * @return array<int, int>
 */
function apollo_chat_find_dm_thread_ids(int $user_a, int $user_b): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    if ($user_a <= 0 || $user_b <= 0 || $user_a === $user_b) {
        return array();
    }

    $ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT t.id
         FROM {$pfx}chat_threads t
         INNER JOIN {$pfx}chat_participants r1 ON t.id = r1.thread_id AND r1.user_id = %d
         INNER JOIN {$pfx}chat_participants r2 ON t.id = r2.thread_id AND r2.user_id = %d
         WHERE t.is_group = 0
           AND (
             SELECT COUNT(*) FROM {$pfx}chat_participants rx
             WHERE rx.thread_id = t.id
           ) = 2
         ORDER BY COALESCE(t.last_message_at, t.created_at) DESC, t.id DESC",
            $user_a,
            $user_b
        )
    );

    return array_map('intval', $ids ?: array());
}

/**
 * Create a fresh 1:1 DM thread (no lookup). Prefer apollo_chat_consolidate_dm_pair().
 */
function apollo_chat_create_dm_thread(int $user_a, int $user_b, string $context = '', string $subject = ''): int
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';
    $now = current_time('mysql');

    $insert_data = array(
        'subject'    => sanitize_text_field($subject),
        'is_group'   => 0,
        'created_by' => $user_a,
        'created_at' => $now,
    );

    $col_exists = $wpdb->get_var(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = '{$pfx}chat_threads'
           AND COLUMN_NAME = 'context'"
    );
    if ($col_exists && $context !== '') {
        $insert_data['context'] = sanitize_text_field($context);
    }

    $ok = $wpdb->insert("{$pfx}chat_threads", $insert_data);
    $thread_id = (int) $wpdb->insert_id;
    if (false === $ok || $thread_id <= 0) {
        return 0;
    }

    foreach (array($user_a, $user_b) as $uid) {
        $wpdb->insert(
            "{$pfx}chat_participants",
            array(
                'thread_id'    => $thread_id,
                'user_id'      => $uid,
                'unread_count' => 0,
                'role'         => ($uid === $user_a) ? 'admin' : 'member',
                'created_at'   => $now,
            )
        );
    }

    return $thread_id;
}

/**
 * WhatsApp/Telegram model: one DM thread per user pair.
 * Merges duplicate threads (moves messages into the newest, soft-deletes the rest).
 *
 * @return int Canonical thread ID (0 on failure)
 */
function apollo_chat_consolidate_dm_pair(int $user_a, int $user_b, string $subject = '', string $context = ''): int
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    if ($user_a <= 0 || $user_b <= 0 || $user_a === $user_b) {
        return 0;
    }

    $ids = apollo_chat_find_dm_thread_ids($user_a, $user_b);
    if (empty($ids)) {
        $created = apollo_chat_create_dm_thread($user_a, $user_b, $context, $subject);
        // #region agent log
        @file_put_contents(
            ( defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '' ) . '/debug-161c5c.log',
            wp_json_encode(
                array(
                    'sessionId'    => '161c5c',
                    'runId'        => 'chat-dm-fix',
                    'hypothesisId' => 'DM1',
                    'location'     => 'functions.php:consolidate_dm_pair',
                    'message'      => 'created new 1:1 thread',
                    'data'         => array('a' => $user_a, 'b' => $user_b, 'thread_id' => $created),
                    'timestamp'    => (int) round(microtime(true) * 1000),
                )
            ) . "\n",
            FILE_APPEND
        );
        // #endregion
        return $created;
    }

    $canonical = (int) $ids[0];
    $merged    = 0;

    // Ensure both users are active on the canonical thread.
    foreach (array($user_a, $user_b) as $uid) {
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$pfx}chat_participants
             SET is_deleted = 0
             WHERE thread_id = %d AND user_id = %d",
                $canonical,
                $uid
            )
        );
    }

    foreach (array_slice($ids, 1) as $dup_id) {
        $dup_id = (int) $dup_id;
        if ($dup_id <= 0 || $dup_id === $canonical) {
            continue;
        }

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$pfx}chat_messages SET thread_id = %d WHERE thread_id = %d",
                $canonical,
                $dup_id
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$pfx}chat_participants SET is_deleted = 1 WHERE thread_id = %d",
                $dup_id
            )
        );
        ++$merged;
    }

    // Refresh canonical last-message metadata from newest message.
    $last = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, sender_id, message, created_at
         FROM {$pfx}chat_messages
         WHERE thread_id = %d AND is_deleted = 0
         ORDER BY created_at DESC, id DESC LIMIT 1",
            $canonical
        ),
        ARRAY_A
    );

    if ($last) {
        $wpdb->update(
            "{$pfx}chat_threads",
            array(
                'last_message_id'      => (int) $last['id'],
                'last_sender_id'       => (int) $last['sender_id'],
                'last_message_at'      => $last['created_at'],
                'last_message_preview' => wp_trim_words((string) $last['message'], 10, '...'),
            ),
            array('id' => $canonical)
        );
    }

    if ($subject !== '') {
        $wpdb->update(
            "{$pfx}chat_threads",
            array('subject' => sanitize_text_field($subject)),
            array('id' => $canonical)
        );
    }

    // #region agent log
    @file_put_contents(
        ( defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '' ) . '/debug-161c5c.log',
        wp_json_encode(
            array(
                'sessionId'    => '161c5c',
                'runId'        => 'chat-dm-fix',
                'hypothesisId' => 'DM1',
                'location'     => 'functions.php:consolidate_dm_pair',
                'message'      => 'reused 1:1 thread',
                'data'         => array(
                    'a'           => $user_a,
                    'b'           => $user_b,
                    'thread_id'   => $canonical,
                    'dupes_found' => count($ids),
                    'merged'      => $merged,
                ),
                'timestamp'    => (int) round(microtime(true) * 1000),
            )
        ) . "\n",
        FILE_APPEND
    );
    // #endregion

    return $canonical;
}

/**
 * Find an existing 1:1 thread between two users, or create one.
 * Always consolidates duplicates — one chat per user pair (WhatsApp/Telegram).
 *
 * @param int    $user_a   First user (the one initiating)
 * @param int    $user_b   Second user (recipient, e.g., ad owner)
 * @param string $context  Kept for callers; does not fork a second DM thread
 * @param string $subject  Thread subject
 * @return int Thread ID
 */
function apollo_chat_find_or_create_thread(int $user_a, int $user_b, string $context = '', string $subject = ''): int
{
    return apollo_chat_consolidate_dm_pair($user_a, $user_b, $subject, $context);
}

/**
 * Generate a URL to open (or create) a chat thread with a specific user.
 * Used by integrations (apollo-adverts, apollo-events, etc.).
 *
 * Supports two modes:
 *   1. Direct link to /mensagens/{thread_id} if thread already exists
 *   2. Link to /mensagens?new=1&to={user_id}&ctx={context}&subject={subject}
 *
 * @param array $args {
 *     @type int    $recipient  Recipient user ID (required)
 *     @type string $subject    Thread subject (optional)
 *     @type string $context    Context string e.g. 'classified:42' (optional)
 * }
 * @return string URL
 */
function apollo_chat_thread_url(array $args): string
{
    $recipient = (int) ($args['recipient'] ?? 0);
    $subject   = $args['subject'] ?? '';
    $context   = $args['context'] ?? '';

    if (! $recipient || ! is_user_logged_in()) {
        return home_url('/mensagens');
    }

    $current_user = get_current_user_id();

    /*
     * Prefer the handle form: /mensagens/@{login}
     *
     * A DM is between two PEOPLE, so the durable name for it is the other
     * person — not the surrogate key of whichever thread row happens to hold
     * the history. Thread ids also churn: apollo_chat_consolidate_dm_pair()
     * merges duplicate DM pairs, so a link built today can point at a row that
     * has since been folded into another. A handle survives that.
     *
     * Numeric logins are emitted with the @ prefix too — /mensagens/12345 is
     * parsed as a thread id by design (see Plugin::register_rewrite_rules),
     * and the bare form would silently address the wrong thing.
     *
     * Falls through to the thread id when the login is unusable, so this can
     * only ever produce a URL at least as good as the previous behaviour.
     *
     * ONLY when there is no $subject and no $context. Those two are not
     * decoration: callers such as apollo-adverts pass context 'classified:42'
     * and a subject, and the old code seeded them into the thread through
     * find_or_create_thread(). The handle form has nowhere to carry them, so
     * taking it unconditionally would silently drop the subject of every
     * integration-opened conversation. When either is present, keep the
     * original path byte-for-byte.
     */
    if ('' === (string) $subject && '' === (string) $context) {
        $user = get_userdata($recipient);
        if ($user && '' !== (string) $user->user_login) {
            return home_url('/mensagens/@' . rawurlencode($user->user_login));
        }
    }

    // Seeded conversation, or no usable login — thread id, as before.
    $thread_id = apollo_chat_find_or_create_thread($current_user, $recipient, $context, $subject);

    if ($thread_id) {
        return home_url('/mensagens/' . $thread_id);
    }

    // Fallback: link with query params (JS will handle creation)
    $url    = home_url('/mensagens');
    $params = array(
        'new' => '1',
        'to'  => $recipient,
    );
    if ($subject) {
        $params['subject'] = urlencode($subject);
    }
    if ($context) {
        $params['ctx'] = urlencode($context);
    }

    return add_query_arg($params, $url);
}

/**
 * Permalink for an EXISTING thread, addressed by handle where that is possible.
 *
 * Every place that links a user to a conversation — notifications, e-mail,
 * REST payloads — used to hard-code home_url('/mensagens/' . $thread_id).
 * That is the surrogate key of a row, and DM rows are not stable:
 * apollo_chat_consolidate_dm_pair() merges duplicate pairs, so a link stored in
 * a notification or an e-mail can outlive the row it names.
 *
 * A DM is between two people, so name it after the other person. A group has no
 * "other person", so it keeps the numeric form — that is not a fallback, it is
 * the correct address for a group.
 *
 * $viewer_id is required to answer "other than whom". With 0 the function
 * cannot know, and returns the numeric form rather than guessing.
 *
 * @param int $thread_id Thread.
 * @param int $viewer_id The user this URL is FOR.
 * @return string
 */
function apollo_chat_thread_permalink(int $thread_id, int $viewer_id = 0): string
{
    global $wpdb;

    if ($thread_id <= 0) {
        return home_url('/mensagens');
    }
    if ($viewer_id <= 0) {
        return home_url('/mensagens/' . $thread_id);
    }

    $pfx = $wpdb->prefix . 'apollo_';

    // Group threads keep the numeric address; there is no single counterpart.
    $is_group = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT is_group FROM {$pfx}chat_threads WHERE id = %d", $thread_id)
    );
    if ($is_group) {
        return home_url('/mensagens/' . $thread_id);
    }

    // The one participant who is not the viewer.
    $peer = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT user_id FROM {$pfx}chat_participants
              WHERE thread_id = %d AND user_id <> %d AND is_deleted = 0
              LIMIT 1",
            $thread_id,
            $viewer_id
        )
    );
    if ($peer <= 0) {
        return home_url('/mensagens/' . $thread_id);
    }

    $user = get_userdata($peer);
    if (! $user || '' === (string) $user->user_login) {
        return home_url('/mensagens/' . $thread_id);
    }

    return home_url('/mensagens/@' . rawurlencode($user->user_login));
}

/**
 * Get the display badge HTML for a user using RemixIcon (CDN).
 * Shared utility for navbar, feed, chat, profiles.
 *
 * @param int    $user_id
 * @param string $size 'sm'|'md'|'lg'
 * @return string HTML
 */
function apollo_chat_get_user_badge_html(int $user_id): string
{
    if (! function_exists('apollo_membership_get_user_badge')) {
        return '';
    }
    return apollo_get_membership_badge_html($user_id);
}

/** Get pinned messages for a thread. */
function apollo_chat_get_pinned_messages(int $thread_id, int $user_id): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $ok = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $thread_id,
            $user_id
        )
    );
    if (! $ok) {
        return array();
    }

    $option_key = 'apollo_chat_pinned_' . $thread_id;
    $pinned     = get_option($option_key, array());
    if (! is_array($pinned) || empty($pinned)) {
        return array();
    }

    $ids = implode(',', array_map('intval', $pinned));
    return $wpdb->get_results(
        "SELECT m.*, u.display_name AS sender_name
         FROM {$pfx}chat_messages m
         LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
         WHERE m.id IN ({$ids}) AND m.is_deleted = 0
         ORDER BY m.created_at DESC",
        ARRAY_A
    ) ?: array();
}

// ═══════════════════════════════════════════════════════════════════
// FLOOD CONTROL (Rate Limiting)
// ═══════════════════════════════════════════════════════════════════

/**
 * Check if user is sending messages too fast.
 * Default: max 10 messages per 60 seconds.
 *
 * @param  int $user_id
 * @return bool True = blocked (flooding), false = OK.
 */
function apollo_chat_check_flood(int $user_id): bool
{
    $max    = (int) apply_filters('apollo/chat/flood_max_messages', (int) get_option('apollo_chat_flood_max', 10));
    $window = (int) apply_filters('apollo/chat/flood_window_seconds', 60);
    $key    = 'apollo_chat_flood_' . $user_id;
    $now    = time();

    $history = get_transient($key);
    if (! is_array($history)) {
        $history = array();
    }

    // Remove timestamps outside current window
    $history = array_values(array_filter($history, fn($t) => ($now - $t) < $window));

    if (count($history) >= $max) {
        return true; // Blocked
    }

    $history[] = $now;
    set_transient($key, $history, $window + 10);
    return false;
}

// ═══════════════════════════════════════════════════════════════════
// BAD WORDS FILTER (Profanity Filter)
// ═══════════════════════════════════════════════════════════════════

/**
 * Replace bad words in a message with asterisks.
 * Word list is filterable via 'apollo/chat/bad_words' filter.
 *
 * @param  string $message Raw message.
 * @return string Censored message.
 */
function apollo_chat_filter_bad_words(string $message): string
{
    if (! (bool) get_option('apollo_chat_bad_words_enabled', 1)) {
        return $message;
    }

    $bad_words = apply_filters(
        'apollo/chat/bad_words',
        array(
            // Português
            'idiota',
            'burro',
            'imbecil',
            'cretino',
            'lixo',
            'merda',
            'porra',
            'puta',
            'viado',
            'otario',
            'babaca',
            'fdp',
            'foda',
            'fodase',
            'caralho',
            'buceta',
            'piroca',
            // English
            'fuck',
            'shit',
            'bitch',
            'asshole',
            'bastard',
            'cunt',
            'fucker',
            'motherfucker',
            'nigger',
            'faggot',
        )
    );

    foreach ($bad_words as $word) {
        $replacement = str_repeat('*', mb_strlen($word));
        $message     = preg_replace(
            '/\b' . preg_quote($word, '/') . '\b/ui',
            $replacement,
            $message
        );
    }

    return $message;
}

/**
 * Count bad words in a message (for auto-mute tracking).
 */
function apollo_chat_count_bad_words(string $message): int
{
    $bad_words = apply_filters(
        'apollo/chat/bad_words',
        array(
            'idiota',
            'burro',
            'imbecil',
            'cretino',
            'lixo',
            'merda',
            'porra',
            'puta',
            'viado',
            'otario',
            'babaca',
            'fdp',
            'foda',
            'fodase',
            'caralho',
            'buceta',
            'piroca',
            'fuck',
            'shit',
            'bitch',
            'asshole',
            'bastard',
            'cunt',
            'fucker',
            'motherfucker',
            'nigger',
            'faggot',
        )
    );

    $count = 0;
    foreach ($bad_words as $word) {
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/ui', $message)) {
            ++$count;
        }
    }
    return $count;
}

// ═══════════════════════════════════════════════════════════════════
// SPAM REPORTING
// ═══════════════════════════════════════════════════════════════════

/**
 * Report a message as spam/inappropriate.
 * One report per user per message enforced via UNIQUE KEY.
 *
 * @return bool True on success, false if already reported by this user.
 */
function apollo_chat_report_message(int $message_id, int $reporter_id, string $reason = ''): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$pfx}chat_reports WHERE message_id = %d AND reporter_id = %d",
            $message_id,
            $reporter_id
        )
    );

    if ($exists) {
        return false;
    }

    $wpdb->insert(
        "{$pfx}chat_reports",
        array(
            'message_id'  => $message_id,
            'reporter_id' => $reporter_id,
            'reason'      => sanitize_text_field($reason),
            'status'      => 'pending',
            'created_at'  => current_time('mysql'),
        )
    );

    return (bool) $wpdb->insert_id;
}

/**
 * Get spam reports list — admin use only.
 *
 * @param string $status 'pending'|'resolved'|'all'
 */
function apollo_chat_get_reports(string $status = 'pending', int $limit = 50, int $offset = 0): array
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $where = $status !== 'all' ? $wpdb->prepare('WHERE r.status = %s', $status) : '';

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.id, r.message_id, r.reason, r.status, r.created_at,
                    m.message, m.sender_id, m.thread_id,
                    u1.display_name AS reporter_name,
                    u2.display_name AS sender_name
             FROM {$pfx}chat_reports r
             LEFT JOIN {$pfx}chat_messages m ON r.message_id = m.id
             LEFT JOIN {$wpdb->users} u1 ON r.reporter_id = u1.ID
             LEFT JOIN {$wpdb->users} u2 ON m.sender_id = u2.ID
             {$where}
             ORDER BY r.created_at DESC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ),
        ARRAY_A
    ) ?: array();
}

/**
 * Resolve a spam report — admin only.
 *
 * @param string $action 'dismiss'|'delete_message'|'block_sender'
 */
function apollo_chat_resolve_report(int $report_id, string $action, int $admin_id): bool
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $report = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$pfx}chat_reports WHERE id = %d", $report_id),
        ARRAY_A
    );
    if (! $report) {
        return false;
    }

    if ($action === 'delete_message') {
        $wpdb->update(
            "{$pfx}chat_messages",
            array(
                'is_deleted' => 1,
                'message'    => '',
                'updated_at' => current_time('mysql'),
            ),
            array('id' => (int) $report['message_id'])
        );
    } elseif ($action === 'block_sender') {
        $sender_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT sender_id FROM {$pfx}chat_messages WHERE id = %d",
                (int) $report['message_id']
            )
        );
        if ($sender_id) {
            // Block via apollo_chat_blocks table (Chat owns this table)
            $wpdb->replace(
                $wpdb->prefix . 'apollo_chat_blocks',
                array(
                    'blocker_id' => 0, // 0 = system ban
                    'blocked_id' => $sender_id,
                    'created_at' => current_time('mysql'),
                )
            );
        }
    }

    return (bool) $wpdb->update(
        "{$pfx}chat_reports",
        array(
            'status'      => 'resolved',
            'resolved_by' => $admin_id,
            'resolved_at' => current_time('mysql'),
        ),
        array('id' => $report_id)
    );
}

// ═══════════════════════════════════════════════════════════════════
// EMAIL NOTIFICATION FOR OFFLINE RECIPIENTS
// ═══════════════════════════════════════════════════════════════════

/**
 * Send email notification to offline recipients of a new chat message.
 * Respects mute flag and user opt-out meta 'apollo_chat_email_notify' = '0'.
 * Uses apollo_queue_email() if available, falls back to wp_mail().
 */
function apollo_chat_maybe_notify_by_email(int $thread_id, int $sender_id, string $preview): void
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    // Get recipients who are offline (no presence within 5 min) and not muted
    $recipients = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.user_id, u.display_name, u.user_email
             FROM {$pfx}chat_participants r
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             LEFT JOIN {$pfx}chat_presence p ON r.user_id = p.user_id
             WHERE r.thread_id = %d
               AND r.user_id != %d
               AND r.is_deleted = 0
               AND r.is_muted = 0
               AND (p.last_seen IS NULL OR p.last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE))",
            $thread_id,
            $sender_id
        ),
        ARRAY_A
    );

    if (empty($recipients)) {
        return;
    }

    $sender      = get_userdata($sender_id);
    $sender_name = $sender ? $sender->display_name : 'Alguém';
    $site_name   = get_bloginfo('name');

    foreach ($recipients as $r) {
        if (empty($r['user_email'])) {
            continue;
        }

        /*
         * Built per RECIPIENT, not once for the batch.
         *
         * The handle form names the OTHER participant, so "the other person"
         * differs for each addressee — hoisting this out of the loop, as the
         * previous $thread_url did, would send everyone a link naming whoever
         * the first recipient happened to be. For a 1:1 that is the recipient
         * themselves, i.e. a link to their own handle.
         *
         * Group threads fall back to the numeric form inside the helper, which
         * is the correct address for a group and matches the old behaviour.
         */
        $thread_url = apollo_chat_thread_permalink((int) $thread_id, (int) $r['user_id']);

        // Respect user opt-out (meta = '0' means opted out)
        $pref = get_user_meta((int) $r['user_id'], 'apollo_chat_email_notify', true);
        if ($pref === '0') {
            continue;
        }

        $subject = sprintf('💬 Nova mensagem de %s — %s', $sender_name, $site_name);

        if (function_exists('apollo_queue_email')) {
            apollo_queue_email(
                array(
                    'to'       => $r['user_email'],
                    'subject'  => $subject,
                    'template' => 'chat_notification',
                    'vars'     => array(
                        'recipient_name' => $r['display_name'],
                        'sender_name'    => $sender_name,
                        'preview'        => $preview,
                        'thread_url'     => $thread_url,
                        'site_name'      => $site_name,
                    ),
                    'priority' => 5,
                )
            );
        } else {
            $body = sprintf(
                "Olá %s,\n\nVocê recebeu uma nova mensagem de %s:\n\n\"%s\"\n\nVer conversa: %s\n\n— %s",
                $r['display_name'],
                $sender_name,
                $preview,
                $thread_url,
                $site_name
            );
            wp_mail($r['user_email'], $subject, $body);
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// OLD MESSAGES AUTO-CLEANUP
// ═══════════════════════════════════════════════════════════════════

/**
 * Soft-delete messages older than N days.
 * Controlled by 'apollo_chat_retention_days' option (0 = never auto-clean).
 */
function apollo_chat_cleanup_old_messages(): void
{
    $days = (int) get_option('apollo_chat_retention_days', 0);
    if ($days <= 0) {
        return;
    }

    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$pfx}chat_messages
             SET is_deleted = 1, message = '', updated_at = NOW()
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
               AND is_deleted = 0",
            $days
        )
    );
}

// ═══════════════════════════════════════════════════════════════════
// PER-ROLE MESSAGE QUOTA
// ═══════════════════════════════════════════════════════════════════

/**
 * Check if a user has exceeded their message sending quota.
 * Option key: apollo_chat_quota_{role} (int, 0 = unlimited).
 *
 * @return bool True = over quota (block send).
 */
function apollo_chat_is_over_quota(int $user_id): bool
{
    $user = get_userdata($user_id);
    if (! $user) {
        return false;
    }

    $roles = (array) $user->roles;
    $role  = $roles[0] ?? 'subscriber';
    $quota = (int) get_option('apollo_chat_quota_' . $role, 0);

    if ($quota <= 0) {
        return false; // Unlimited
    }

    global $wpdb;
    $pfx   = $wpdb->prefix . 'apollo_';
    $count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_messages WHERE sender_id = %d AND is_deleted = 0",
            $user_id
        )
    );

    return $count >= $quota;
}

// ═══════════════════════════════════════════════════════════════════
// THREAD CSV EXPORT (Backup)
// ═══════════════════════════════════════════════════════════════════

/**
 * Generate CSV string for all messages in a thread.
 * Only available to thread participants.
 *
 * @return string CSV content, empty string if unauthorized.
 */
function apollo_chat_export_thread_csv(int $thread_id, int $user_id): string
{
    global $wpdb;
    $pfx = $wpdb->prefix . 'apollo_';

    $ok = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$pfx}chat_participants WHERE thread_id=%d AND user_id=%d AND is_deleted=0",
            $thread_id,
            $user_id
        )
    );
    if (! $ok) {
        return '';
    }

    $messages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT m.id, m.created_at, m.message_type, m.message, m.is_deleted,
                    u.display_name AS sender_name, u.user_login
             FROM {$pfx}chat_messages m
             LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
             WHERE m.thread_id = %d
             ORDER BY m.created_at ASC",
            $thread_id
        ),
        ARRAY_A
    );

    if (empty($messages)) {
        return '';
    }

    $rows   = array();
    $rows[] = 'id,data,tipo,remetente,login,mensagem';
    foreach ($messages as $m) {
        $rows[] = implode(
            ',',
            array(
                (int) $m['id'],
                '"' . $m['created_at'] . '"',
                '"' . $m['message_type'] . '"',
                '"' . addcslashes((string) $m['sender_name'], '"') . '"',
                '"' . addcslashes((string) $m['user_login'], '"') . '"',
                '"' . ((int) $m['is_deleted'] ? '[apagada]' : addcslashes((string) $m['message'], '"')) . '"',
            )
        );
    }

    return implode("\n", $rows);
}
