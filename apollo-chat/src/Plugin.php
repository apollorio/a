<?php

/**
 * Apollo Chat — Main Plugin Class v2.0
 *
 * Premium instant messaging — TEXT ONLY:
 * - Real-time AJAX polling (3s) with BroadcastChannel cross-tab sync
 * - Typing indicators (3s debounce)
 * - Read receipts (sent → delivered → read)
 * - Emoji picker (native Unicode)
 * - Message reactions
 * - Message editing & deletion
 * - Reply-to (quote) threading
 * - User presence / online status
 * - Message search
 * - Sound + browser notifications
 * - Group conversations
 * - User blocking
 *
 * NO ATTACHMENTS OF ANY KIND (policy set 2026-08-25): no files, no images,
 * no audio/video, no GIFs, no voice messages. This plugin's docblocks and
 * plugin header used to advertise file/voice/GIF attachments — that was
 * true of an earlier iteration and is no longer the product. Enforcement is
 * server-side, not just UI-hiding:
 *   - rest_send_message() ignores any client-supplied 'type' param and
 *     always writes message_type = 'text' — a raw POST straight to
 *     /chat/send bypassing the UI still cannot create a non-text message.
 *   - rest_upload() (POST /chat/upload) always returns 403. The old body of
 *     this method fed multipart uploads straight into the WP media library
 *     via media_handle_upload() — removed, not just gated.
 *   - rest_gif_search() (GET /chat/gif-search, the old Tenor API proxy)
 *     always returns 403.
 * The attachment_id column/apollo_chat_attachments table and the
 * message-bubble render branch for m.attachment still exist purely to
 * display any attachment-bearing rows already in the database from before
 * this policy — that is read-only backward compat, not a way back in.
 *
 * REST namespace: apollo/v1
 * Pages: /mensagens              inbox
 *        /mensagens/{login}     the DM with that person   (preferred)
 *        /mensagens/@{login}    same, explicit form
 *        /mensagens/{id}        a thread by numeric id    (legacy, still works)
 *
 * @package Apollo\Chat
 */

declare(strict_types=1);

namespace Apollo\Chat;

use Apollo\Core\Traits\BlankCanvasTrait;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{

    use BlankCanvasTrait;


    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', array($this, 'register_rewrite_rules'), 1);
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_filter('query_vars', array($this, 'register_query_vars'));
        add_action('template_redirect', array($this, 'handle_virtual_pages'), 5);
        add_action('apollo_chat_cleanup', array($this, 'cleanup_ephemeral'));

        if (! wp_next_scheduled('apollo_chat_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'apollo_chat_cleanup');
        }
    }

    // ─── Rewrite Rules ──────────────────────────────────────────────
    /**
     * URL shapes
     * ----------
     *   /mensagens              inbox
     *   /mensagens/{id}         a THREAD by numeric id  (legacy, still supported)
     *   /mensagens/{login}      the DM with that person
     *   /mensagens/@{login}     the same, explicit form
     *
     * The three patterns are MUTUALLY EXCLUSIVE by their first character, so
     * they do not depend on registration order. That matters: add_rewrite_rule
     * with 'top' PREPENDS, so the last rule added would otherwise win, and a
     * reader fixing an unrelated bug could reorder these and silently send
     * every thread link to the username handler.
     *
     *   (\d+)          digits only            -> thread id
     *   @(...)         explicit @ prefix      -> login
     *   ([^/@0-9]...)  starts with neither    -> login
     *
     * A login that is entirely numeric ("12345") is therefore only reachable as
     * /mensagens/@12345 — the bare form belongs to thread ids, which existed
     * first and are still emitted by e-mail notifications.
     */
    public function register_rewrite_rules(): void
    {
        add_rewrite_rule('^mensagens/?$', 'index.php?apollo_chat_page=inbox', 'top');
        add_rewrite_rule('^mensagens/(\d+)/?$', 'index.php?apollo_chat_page=thread&apollo_thread_id=$matches[1]', 'top');
        add_rewrite_rule('^mensagens/@([^/]+)/?$', 'index.php?apollo_chat_page=user&apollo_chat_user=$matches[1]', 'top');
        add_rewrite_rule('^mensagens/([^/@0-9][^/]*)/?$', 'index.php?apollo_chat_page=user&apollo_chat_user=$matches[1]', 'top');

        /*
         * Rewrite rules only reach the database on flush, and this plugin
         * flushes solely in Activation.php. It is already active, so without
         * this the two new rules would never resolve — /mensagens/teste would
         * 404 until someone happened to re-save permalinks.
         *
         * Gated on its own option rather than APOLLO_CHAT_VERSION so a routine
         * version bump does not trigger a needless flush, and so this does not
         * have to touch apollo-chat.php. Bump the string to force a re-flush.
         *
         * THE FLUSH MUST NOT HAPPEN HERE. This method runs on init:1, and
         * flush_rewrite_rules() serialises whatever rules exist AT THAT MOMENT
         * into the database. Most plugins — apollo-hub, apollo-events,
         * apollo-users' /id/{login} — register theirs on init:10 or later, so
         * flushing from init:1 would persist a rule set with all of them
         * MISSING and 404 half the site until the next flush.
         *
         * wp_loaded fires after every init handler has run, so the rule set is
         * complete by then. This is the only safe point to flush from a
         * plugin that is already active.
         */
        if (get_option('apollo_chat_rewrite_v') !== '2') {
            add_action(
                'wp_loaded',
                static function (): void {
                    flush_rewrite_rules(false);
                    update_option('apollo_chat_rewrite_v', '2', false);
                }
            );
        }
    }

    public function register_query_vars(array $vars): array
    {
        $vars[] = 'apollo_chat_page';
        $vars[] = 'apollo_thread_id';
        $vars[] = 'apollo_chat_user';
        return $vars;
    }

    public function handle_virtual_pages(): void
    {
        $page = get_query_var('apollo_chat_page');
        if (! $page) {
            return;
        }

        if (! is_user_logged_in()) {
            wp_redirect(home_url('/acesso'));
            exit;
        }

        /*
         * /mensagens/{login} and /mensagens/@{login}
         *
         * Resolve the login to a user, then to the DM thread the two of them
         * share, and hand the template the thread id it already understands.
         * templates/chat.php reads exactly one thing — get_query_var
         * ('apollo_thread_id') — so nothing downstream needs to change.
         *
         * Authorization is unaffected: the thread produced here always has the
         * current user as a participant, and every message read still goes
         * through apollo_get_thread_messages(), which re-checks
         * chat_participants and fails closed. This route changes ADDRESSING,
         * not access.
         */
        if ('user' === $page) {
            $login = (string) get_query_var('apollo_chat_user');
            $tid   = $this->resolve_user_thread($login);

            if ($tid <= 0) {
                // Unknown login, or the user addressed themselves. Send them to
                // the inbox rather than rendering an empty shell that looks broken.
                wp_safe_redirect(home_url('/mensagens'));
                exit;
            }

            set_query_var('apollo_thread_id', $tid);
        }

        $template = APOLLO_CHAT_PATH . 'templates/chat.php';
        $this->render_blank_canvas($template);
    }

    /**
     * Login (or nicename) -> the DM thread id shared with the current user.
     *
     * Returns 0 when the login is unknown, inactive, or is the caller's own —
     * a self-DM is not a thing, and creating one would put a junk row in every
     * inbox that visits their own handle.
     *
     * Lookup order mirrors apollo-users' /id/{login} profile URLs so the same
     * handle works in both places: login first, then nicename.
     */
    private function resolve_user_thread(string $login): int
    {
        $login = sanitize_user(rawurldecode($login), true);
        if ('' === $login) {
            return 0;
        }

        $user = get_user_by('login', $login);
        if (! $user) {
            $user = get_user_by('slug', $login);   // nicename, as used by /id/{login}
        }
        if (! $user || ! $user->ID) {
            return 0;
        }

        $me = get_current_user_id();
        if ((int) $user->ID === $me) {
            return 0;
        }

        /*
         * Look for an existing thread FIRST. find_or_create() would return the
         * right answer either way, but it routes through
         * apollo_chat_consolidate_dm_pair(), which merges duplicate DM pairs —
         * real work we should not do on every page view when a plain lookup
         * answers the question.
         */
        if (function_exists('apollo_chat_find_dm_thread_ids')) {
            $existing = apollo_chat_find_dm_thread_ids($me, (int) $user->ID);
            if (! empty($existing)) {
                return (int) $existing[0];
            }
        }

        /*
         * No thread yet: create one, so the page opens ready to type instead of
         * bouncing to the inbox.
         *
         * TRADE-OFF, stated rather than hidden: this makes a GET create a row,
         * so a logged-in user walking /mensagens/@handle could seed an empty
         * thread in other people's inboxes. That is not new — the existing
         * apollo_chat_thread_url() already calls find_or_create() whenever it
         * renders a "Message" button, and POST /chat/dm does the same. This
         * route inherits that behaviour rather than inventing a stricter one.
         * If empty-thread spam ever shows up, the fix belongs in ONE place:
         * make thread creation lazy until the first message is sent, for every
         * caller at once.
         */
        if (function_exists('apollo_chat_find_or_create_thread')) {
            return (int) apollo_chat_find_or_create_thread($me, (int) $user->ID);
        }

        return 0;
    }

    // ─── REST API Registration ──────────────────────────────────────
    public function register_rest_routes(): void
    {
        $ns   = 'apollo/v1';
        $auth = function () {
            return is_user_logged_in();
        };

        // ── Core Thread Endpoints ──────────────────────
        register_rest_route(
            $ns,
            '/chat/threads',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_threads'),
                'permission_callback' => $auth,
            )
        );

        register_rest_route(
            $ns,
            '/chat/threads/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_thread'),
                'permission_callback' => $auth,
            )
        );

        register_rest_route(
            $ns,
            '/chat/send',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_send_message'),
                'permission_callback' => $auth,
            )
        );

        // Open/create empty 1:1 DM (no first message required)
        register_rest_route(
            $ns,
            '/chat/dm',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_open_dm'),
                'permission_callback' => $auth,
            )
        );

        register_rest_route(
            $ns,
            '/chat/unread',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_unread_count'),
                'permission_callback' => $auth,
            )
        );

        register_rest_route(
            $ns,
            '/chat/poll',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_poll'),
                'permission_callback' => $auth,
            )
        );

        register_rest_route(
            $ns,
            '/chat/more',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_more_threads'),
                'permission_callback' => $auth,
            )
        );

        register_rest_route(
            $ns,
            '/chat/threads/(?P<id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'rest_delete_thread'),
                'permission_callback' => $auth,
            )
        );

        // ── v2.0 Premium Endpoints ─────────────────────

        // Typing indicator
        register_rest_route(
            $ns,
            '/chat/typing',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_typing'),
                'permission_callback' => $auth,
            )
        );

        // Mark thread as read (read receipts)
        register_rest_route(
            $ns,
            '/chat/threads/(?P<id>\d+)/read',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_mark_read'),
                'permission_callback' => $auth,
            )
        );

        // Edit message
        register_rest_route(
            $ns,
            '/chat/messages/(?P<id>\d+)',
            array(
                'methods'             => 'PUT',
                'callback'            => array($this, 'rest_edit_message'),
                'permission_callback' => $auth,
            )
        );

        // Delete message
        register_rest_route(
            $ns,
            '/chat/messages/(?P<id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'rest_delete_message'),
                'permission_callback' => $auth,
            )
        );

        // React to message (toggle emoji)
        register_rest_route(
            $ns,
            '/chat/messages/(?P<id>\d+)/react',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_react_message'),
                'permission_callback' => $auth,
            )
        );

        // Search messages
        register_rest_route(
            $ns,
            '/chat/search',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_search'),
                'permission_callback' => $auth,
            )
        );

        // Block / unblock user
        register_rest_route(
            $ns,
            '/chat/block/(?P<user_id>\d+)',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_block_user'),
                'permission_callback' => $auth,
            )
        );
        register_rest_route(
            $ns,
            '/chat/block/(?P<user_id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'rest_unblock_user'),
                'permission_callback' => $auth,
            )
        );

        // User presence heartbeat
        register_rest_route(
            $ns,
            '/chat/presence',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_presence'),
                'permission_callback' => $auth,
            )
        );

        // Older messages (scroll-up pagination)
        register_rest_route(
            $ns,
            '/chat/threads/(?P<id>\d+)/older',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_older_messages'),
                'permission_callback' => $auth,
            )
        );

        // Group thread members management
        register_rest_route(
            $ns,
            '/chat/threads/(?P<id>\d+)/members',
            array(
                'methods'             => array('GET', 'POST', 'DELETE'),
                'callback'            => array($this, 'rest_thread_members'),
                'permission_callback' => $auth,
            )
        );

        // Mute / unmute thread
        register_rest_route(
            $ns,
            '/chat/threads/(?P<id>\d+)/mute',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_mute_thread'),
                'permission_callback' => $auth,
            )
        );

        // Forward message to another thread
        register_rest_route(
            $ns,
            '/chat/messages/(?P<id>\d+)/forward',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_forward_message'),
                'permission_callback' => $auth,
            )
        );

        // Pin / unpin message
        register_rest_route(
            $ns,
            '/chat/threads/(?P<id>\d+)/pin',
            array(
                'methods'             => array('POST', 'DELETE'),
                'callback'            => array($this, 'rest_pin_message'),
                'permission_callback' => $auth,
            )
        );

        // Get pinned messages
        register_rest_route(
            $ns,
            '/chat/threads/(?P<id>\d+)/pinned',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_pinned'),
                'permission_callback' => $auth,
            )
        );

        // Find-or-create thread by context (for classified/event integrations)
        register_rest_route(
            $ns,
            '/chat/thread-for-context',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_thread_for_context'),
                'permission_callback' => $auth,
                'args'                => array(
                    'recipient_id' => array(
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'context'      => array(
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'subject'      => array(
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'message'      => array(
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            )
        );

        // GIF search (Tenor proxy — keeps API key server-side)
        register_rest_route(
            $ns,
            '/chat/gif-search',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_gif_search'),
                'permission_callback' => $auth,
                'args'                => array(
                    'q'     => array(
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'pos'   => array(
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'limit' => array(
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Report a message as spam
        register_rest_route(
            $ns,
            '/chat/messages/(?P<id>\d+)/report',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_report_message'),
                'permission_callback' => $auth,
            )
        );

        // Export thread as CSV (participants only)
        register_rest_route(
            $ns,
            '/chat/threads/(?P<id>\d+)/export',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_export_thread'),
                'permission_callback' => $auth,
            )
        );

        // Admin: list spam reports
        register_rest_route(
            $ns,
            '/chat/reports',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_reports'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            )
        );

        // Admin: resolve spam report
        register_rest_route(
            $ns,
            '/chat/reports/(?P<id>\d+)/action',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_resolve_report'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            )
        );

        // File/image/audio/video upload endpoint (replaces legacy wp_ajax)
        register_rest_route(
            $ns,
            '/chat/upload',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_upload'),
                'permission_callback' => $auth,
            )
        );
    }

    // ─── Core REST Handlers ─────────────────────────────────────────

    public function rest_get_threads(\WP_REST_Request $req): \WP_REST_Response
    {
        $uid     = get_current_user_id();
        $threads = apollo_get_user_threads($uid, 20);
        return new \WP_REST_Response($this->enrich_threads($threads, $uid), 200);
    }

    public function rest_get_thread(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid  = (int) $req->get_param('id');
        $uid  = get_current_user_id();

        // If this is a duplicate 1:1, merge history into the canonical DM first.
        if (function_exists('apollo_chat_get_thread_meta') && function_exists('apollo_chat_consolidate_dm_pair')) {
            $meta_probe = apollo_chat_get_thread_meta($tid, $uid);
            if (is_array($meta_probe) && empty($meta_probe['is_group'])) {
                $peer = 0;
                foreach ((array) ( $meta_probe['participants'] ?? array() ) as $p) {
                    $pid = (int) ( $p['user_id'] ?? 0 );
                    if ($pid > 0 && $pid !== $uid) {
                        $peer = $pid;
                        break;
                    }
                }
                if ($peer > 0) {
                    $canonical = apollo_chat_consolidate_dm_pair($uid, $peer);
                    if ($canonical > 0) {
                        $tid = $canonical;
                    }
                }
            }
        }

        $msgs = apollo_get_thread_messages($tid, $uid);

        foreach ($msgs as &$m) {
            $m['avatar_url']       = $this->get_avatar((int) $m['sender_id']);
            $m['reactions']        = apollo_get_message_reactions((int) $m['id']);
            $m['is_edited']        = ! empty($m['edited_at']);
            $m['is_mine']          = ((int) $m['sender_id'] === $uid);
            $m['is_deleted']       = ! empty($m['is_deleted']) && (int) $m['is_deleted'] === 1;
            $m['reply_to_preview'] = null;
            if (! empty($m['reply_to_id']) && (int) $m['reply_to_id'] > 0) {
                $m['reply_to_preview'] = apollo_get_reply_preview((int) $m['reply_to_id']);
            }
            // Attachment data
            if (! empty($m['attachment_id']) && (int) $m['attachment_id'] > 0) {
                $m['attachment'] = apollo_chat_get_attachment((int) $m['attachment_id']);
            }
        }

        // Thread meta
        $meta = apollo_chat_get_thread_meta($tid, $uid);

        return new \WP_REST_Response(
            array(
                'messages'  => $msgs,
                'meta'      => $meta,
                'thread_id' => $tid,
            ),
            200
        );
    }

    /**
     * POST /chat/dm — open or create an empty 1:1 thread with peer (no message).
     */
    public function rest_open_dm(\WP_REST_Request $req): \WP_REST_Response
    {
        $uid  = get_current_user_id();
        $peer = (int) $req->get_param('user_id');
        if ($peer <= 0 || $peer === $uid) {
            return new \WP_REST_Response(array('error' => 'invalid_user'), 400);
        }
        if (! get_userdata($peer)) {
            return new \WP_REST_Response(array('error' => 'user_not_found'), 404);
        }
        if (! function_exists('apollo_chat_consolidate_dm_pair')) {
            return new \WP_REST_Response(array('error' => 'unavailable'), 500);
        }
        $tid = apollo_chat_consolidate_dm_pair($uid, $peer);
        if ($tid <= 0) {
            return new \WP_REST_Response(array('error' => 'create_failed'), 500);
        }
        return new \WP_REST_Response(array('thread_id' => $tid), 200);
    }

    public function rest_send_message(\WP_REST_Request $req): \WP_REST_Response
    {
        $sender_id  = get_current_user_id();
        $thread_id  = $req->get_param('thread_id') ? (int) $req->get_param('thread_id') : 0;
        $recipients = $req->get_param('recipients');
        $message    = sanitize_textarea_field($req->get_param('message') ?? '');
        $subject    = sanitize_text_field($req->get_param('subject') ?? '');
        $reply_to   = $req->get_param('reply_to_id') ? (int) $req->get_param('reply_to_id') : 0;
        // TEXT-ONLY POLICY (2026-08-25): message type is never taken from the
        // client. Whatever the request claims (`type=gif`, an attachment_id,
        // etc.) is ignored — every message this endpoint creates is plain
        // 'text'. This is the actual enforcement point: the GIF picker and
        // /chat/upload are also disabled below, but even a raw POST straight
        // to this route (bypassing the UI entirely) cannot create a non-text
        // message. See class docblock for the full policy.
        $msg_type   = 'text';
        $is_group   = (bool) $req->get_param('is_group');
        $group_name = sanitize_text_field($req->get_param('group_name') ?? '');

        // #region agent log
        $apollo_chat_dbg = static function (string $hypothesis_id, string $message, array $data): void {
            $payload = array(
                'sessionId'    => '161c5c',
                'runId'        => 'chat-pre',
                'hypothesisId' => $hypothesis_id,
                'location'     => 'Plugin.php:rest_send_message',
                'message'      => $message,
                'data'         => $data,
                'timestamp'    => (int) round(microtime(true) * 1000),
            );
            $line = wp_json_encode($payload) . "\n";
            foreach (
                array(
                    ( defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '' ) . '/debug-161c5c.log',
                    ( defined('ABSPATH') ? ABSPATH : '' ) . 'debug-161c5c.log',
                ) as $log
            ) {
                if ($log !== '/debug-161c5c.log' && $log !== 'debug-161c5c.log') {
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                    @file_put_contents($log, $line, FILE_APPEND);
                }
            }
            if (! headers_sent()) {
                header('X-Apollo-Debug-Chat: ' . rawurlencode((string) wp_json_encode($payload)));
            }
        };
        // #endregion

        if (empty($message)) {
            // #region agent log
            $apollo_chat_dbg('C3', 'reject empty message', array('sender' => $sender_id, 'thread' => $thread_id));
            // #endregion
            return new \WP_REST_Response(array('error' => 'Mensagem vazia'), 400);
        }

        // ── Flood control ──────────────────────────────────
        if (apollo_chat_check_flood($sender_id)) {
            // #region agent log
            $apollo_chat_dbg('C4', 'reject flood', array('sender' => $sender_id));
            // #endregion
            return new \WP_REST_Response(array('error' => 'Você está enviando mensagens muito rápido. Aguarde um momento.'), 429);
        }

        // ── Per-role quota ─────────────────────────────────
        if (apollo_chat_is_over_quota($sender_id)) {
            // #region agent log
            $apollo_chat_dbg('C4', 'reject quota', array('sender' => $sender_id));
            // #endregion
            return new \WP_REST_Response(array('error' => 'Limite de mensagens atingido para seu plano.'), 403);
        }

        // ── Bad words filter ───────────────────────────────
        $message = apollo_chat_filter_bad_words($message);

        // ── Block check ────────────────────────────────────
        if ($thread_id && apollo_chat_is_blocked_in_thread($thread_id, $sender_id)) {
            // #region agent log
            $apollo_chat_dbg('C5', 'reject blocked', array('sender' => $sender_id, 'thread' => $thread_id));
            // #endregion
            return new \WP_REST_Response(array('error' => 'Bloqueado nesta conversa'), 403);
        }

        $recip_ids = is_array($recipients) ? array_map('intval', $recipients) : array();

        // #region agent log
        $apollo_chat_dbg(
            'C2',
            'send attempt',
            array(
                'sender'     => $sender_id,
                'thread'     => $thread_id,
                'recipCount' => count($recip_ids),
                'recipients' => $recip_ids,
                'msgLen'     => strlen($message),
                'is_group'   => $is_group,
            )
        );
        // #endregion

        $result = apollo_send_message(
            array(
                'sender_id'   => $sender_id,
                'thread_id'   => $thread_id,
                'recipients'  => $recip_ids,
                'subject'     => $subject,
                'message'     => $message,
                'reply_to_id' => $reply_to,
                'type'        => $msg_type,
                'is_group'    => $is_group,
                'group_name'  => $group_name,
            )
        );

        if ($result) {
            apollo_chat_set_typing($thread_id ?: $result, $sender_id, false);

            // ── Email offline recipients ───────────────────
            $preview = wp_trim_words($message, 10, '...');
            apollo_chat_maybe_notify_by_email($result, $sender_id, $preview);

            // #region agent log
            $apollo_chat_dbg('C2', 'send OK', array('thread_id' => (int) $result));
            // #endregion

            return new \WP_REST_Response(
                array(
                    'thread_id' => $result,
                    'success'   => true,
                ),
                201
            );
        }

        // #region agent log
        global $wpdb;
        $apollo_chat_dbg(
            'C6',
            'apollo_send_message returned false',
            array(
                'sender'     => $sender_id,
                'thread'     => $thread_id,
                'recipCount' => count($recip_ids),
                'db_error'   => (string) $wpdb->last_error,
                'last_query' => substr((string) $wpdb->last_query, 0, 240),
            )
        );
        // #endregion

        return new \WP_REST_Response(
            array(
                'error'    => 'Erro ao enviar mensagem',
                'db_error' => (string) $wpdb->last_error,
            ),
            500
        );
    }

    public function rest_unread_count(): \WP_REST_Response
    {
        return new \WP_REST_Response(
            array(
                'count' => apollo_get_unread_message_count(get_current_user_id()),
            ),
            200
        );
    }

    public function rest_poll(\WP_REST_Request $req): \WP_REST_Response
    {
        $uid   = get_current_user_id();
        $since = sanitize_text_field($req->get_param('since') ?? '');
        $atid  = $req->get_param('thread_id') ? (int) $req->get_param('thread_id') : 0;

        global $wpdb;
        $pfx = $wpdb->prefix . 'apollo_';

        $unread        = apollo_get_unread_message_count($uid);
        $unread_notifs = function_exists('apollo_get_unread_notif_count')
            ? apollo_get_unread_notif_count($uid) : 0;

        // New messages since timestamp
        $new_messages = array();
        if ($since) {
            $new_messages = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT m.*, u.display_name AS sender_name, u.user_login
                 FROM {$pfx}chat_messages m
                 INNER JOIN {$pfx}chat_participants r ON m.thread_id = r.thread_id
                 LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
                 WHERE r.user_id = %d AND r.is_deleted = 0
                   AND m.created_at > %s AND m.sender_id != %d
                   AND m.is_deleted = 0
                 GROUP BY m.id
                 ORDER BY m.created_at DESC LIMIT 50",
                    $uid,
                    $since,
                    $uid
                ),
                ARRAY_A
            );

            foreach ($new_messages as &$nm) {
                $nm['avatar_url'] = $this->get_avatar((int) $nm['sender_id']);
                $nm['reactions']  = apollo_get_message_reactions((int) $nm['id']);
                $nm['is_mine']    = false;
                if (! empty($nm['reply_to_id']) && (int) $nm['reply_to_id'] > 0) {
                    $nm['reply_to_preview'] = apollo_get_reply_preview((int) $nm['reply_to_id']);
                }
                if (! empty($nm['attachment_id']) && (int) $nm['attachment_id'] > 0) {
                    $nm['attachment'] = apollo_chat_get_attachment((int) $nm['attachment_id']);
                }
            }
        }

        // Typing indicators for active thread
        $typing = $atid ? apollo_chat_get_typing($atid, $uid) : array();

        // Online contacts
        $online = apollo_chat_get_online_contacts($uid);

        // Updated/deleted messages since timestamp
        $updates = array();
        if ($since) {
            $updates = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT m.id, m.thread_id,
                        CASE
                          WHEN m.is_deleted = 1 THEN 'deleted'
                          WHEN m.edited_at IS NOT NULL AND m.edited_at > %s THEN 'edited'
                          ELSE 'unknown'
                        END AS action,
                        m.message, m.edited_at
                 FROM {$pfx}chat_messages m
                 INNER JOIN {$pfx}chat_participants r ON m.thread_id = r.thread_id
                 WHERE r.user_id = %d AND r.is_deleted = 0
                   AND (
                     (m.is_deleted = 1 AND m.updated_at > %s)
                     OR (m.edited_at IS NOT NULL AND m.edited_at > %s)
                   )
                 GROUP BY m.id",
                    $since,
                    $uid,
                    $since,
                    $since
                ),
                ARRAY_A
            );
        }

        // Read receipts for active thread
        $read_receipts = array();
        if ($atid) {
            $read_receipts = apollo_chat_get_read_receipts($atid, $uid);
        }

        // Update own presence
        apollo_chat_update_presence($uid);

        return new \WP_REST_Response(
            array(
                'unread_messages' => $unread,
                'unread_notifs'   => $unread_notifs,
                'new_messages'    => $new_messages,
                'typing'          => $typing,
                'online'          => $online,
                'updates'         => $updates,
                'read_receipts'   => $read_receipts,
                'timestamp'       => current_time('mysql'),
            ),
            200
        );
    }

    public function rest_more_threads(\WP_REST_Request $req): \WP_REST_Response
    {
        $page    = (int) ($req->get_param('page') ?? 2);
        $uid     = get_current_user_id();
        $threads = apollo_get_user_threads($uid, 10, ($page - 1) * 10);
        return new \WP_REST_Response($this->enrich_threads($threads, $uid), 200);
    }

    public function rest_delete_thread(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid = (int) $req->get_param('id');
        $ok  = apollo_delete_thread_for_user($tid, get_current_user_id());
        return new \WP_REST_Response(array('deleted' => $ok), $ok ? 200 : 404);
    }

    // ─── v2.0 Premium REST Handlers ─────────────────────────────────

    public function rest_typing(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid    = (int) $req->get_param('thread_id');
        $typing = (bool) $req->get_param('typing');
        apollo_chat_set_typing($tid, get_current_user_id(), $typing);
        return new \WP_REST_Response(array('ok' => true), 200);
    }

    public function rest_mark_read(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid = (int) $req->get_param('id');
        apollo_chat_mark_read($tid, get_current_user_id());
        return new \WP_REST_Response(array('ok' => true), 200);
    }

    public function rest_edit_message(\WP_REST_Request $req): \WP_REST_Response
    {
        $mid = (int) $req->get_param('id');
        $txt = sanitize_textarea_field($req->get_param('message') ?? '');
        if (empty($txt)) {
            return new \WP_REST_Response(array('error' => 'Mensagem vazia'), 400);
        }
        $ok = apollo_chat_edit_message($mid, get_current_user_id(), $txt);
        return new \WP_REST_Response(array('edited' => $ok), $ok ? 200 : 403);
    }

    public function rest_delete_message(\WP_REST_Request $req): \WP_REST_Response
    {
        $mid = (int) $req->get_param('id');
        $ok  = apollo_chat_delete_message($mid, get_current_user_id());
        return new \WP_REST_Response(array('deleted' => $ok), $ok ? 200 : 403);
    }

    public function rest_react_message(\WP_REST_Request $req): \WP_REST_Response
    {
        $mid   = (int) $req->get_param('id');
        $emoji = sanitize_text_field($req->get_param('emoji') ?? '');
        if (empty($emoji)) {
            return new \WP_REST_Response(array('error' => 'Emoji vazio'), 400);
        }
        $result = apollo_chat_toggle_reaction($mid, get_current_user_id(), $emoji);
        return new \WP_REST_Response($result, 200);
    }

    public function rest_search(\WP_REST_Request $req): \WP_REST_Response
    {
        $q = sanitize_text_field($req->get_param('q') ?? '');
        if (strlen($q) < 2) {
            return new \WP_REST_Response(array(), 200);
        }
        return new \WP_REST_Response(apollo_chat_search_messages(get_current_user_id(), $q), 200);
    }

    public function rest_block_user(\WP_REST_Request $req): \WP_REST_Response
    {
        apollo_chat_block_user(get_current_user_id(), (int) $req->get_param('user_id'));
        return new \WP_REST_Response(array('blocked' => true), 200);
    }

    public function rest_unblock_user(\WP_REST_Request $req): \WP_REST_Response
    {
        apollo_chat_unblock_user(get_current_user_id(), (int) $req->get_param('user_id'));
        return new \WP_REST_Response(array('unblocked' => true), 200);
    }

    public function rest_presence(): \WP_REST_Response
    {
        apollo_chat_update_presence(get_current_user_id());
        return new \WP_REST_Response(array('ok' => true), 200);
    }

    public function rest_older_messages(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid    = (int) $req->get_param('id');
        $before = (int) ($req->get_param('before') ?? 0);
        $uid    = get_current_user_id();

        $msgs = apollo_chat_get_older_messages($tid, $uid, $before, 30);
        foreach ($msgs as &$m) {
            $m['avatar_url'] = $this->get_avatar((int) $m['sender_id']);
            $m['reactions']  = apollo_get_message_reactions((int) $m['id']);
            $m['is_mine']    = ((int) $m['sender_id'] === $uid);
            $m['is_edited']  = ! empty($m['edited_at']);
            if (! empty($m['reply_to_id']) && (int) $m['reply_to_id'] > 0) {
                $m['reply_to_preview'] = apollo_get_reply_preview((int) $m['reply_to_id']);
            }
            if (! empty($m['attachment_id']) && (int) $m['attachment_id'] > 0) {
                $m['attachment'] = apollo_chat_get_attachment((int) $m['attachment_id']);
            }
        }
        return new \WP_REST_Response($msgs, 200);
    }

    public function rest_thread_members(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid    = (int) $req->get_param('id');
        $uid    = get_current_user_id();
        $method = $req->get_method();

        if ($method === 'GET') {
            return new \WP_REST_Response(apollo_chat_get_thread_members($tid, $uid), 200);
        }
        if ($method === 'POST') {
            $ids = $req->get_param('user_ids');
            if (! is_array($ids)) {
                return new \WP_REST_Response(array('error' => 'user_ids required'), 400);
            }
            $ok = apollo_chat_add_thread_members($tid, $uid, array_map('intval', $ids));
            return new \WP_REST_Response(array('added' => $ok), $ok ? 200 : 403);
        }
        if ($method === 'DELETE') {
            $rid = (int) $req->get_param('remove_user_id');
            $ok  = apollo_chat_remove_thread_member($tid, $uid, $rid);
            return new \WP_REST_Response(array('removed' => $ok), $ok ? 200 : 403);
        }
        return new \WP_REST_Response(array('error' => 'Method not allowed'), 405);
    }

    // ─── Mute / Unmute Thread ────────────────────────────────────────
    public function rest_mute_thread(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid  = (int) $req->get_param('id');
        $mute = (bool) ($req->get_param('mute') ?? true);
        $ok   = apollo_chat_toggle_mute($tid, get_current_user_id(), $mute);
        return new \WP_REST_Response(
            array(
                'muted' => $mute,
                'ok'    => $ok,
            ),
            $ok ? 200 : 404
        );
    }

    // ─── Forward Message ──────────────────────────────────────────────
    public function rest_forward_message(\WP_REST_Request $req): \WP_REST_Response
    {
        $mid        = (int) $req->get_param('id');
        $target_tid = (int) ($req->get_param('thread_id') ?? 0);
        $uid        = get_current_user_id();

        if (! $target_tid) {
            return new \WP_REST_Response(array('error' => 'thread_id obrigatório'), 400);
        }

        $result = apollo_chat_forward_message($mid, $target_tid, $uid);
        if ($result) {
            return new \WP_REST_Response(
                array(
                    'success'   => true,
                    'thread_id' => $target_tid,
                ),
                201
            );
        }
        return new \WP_REST_Response(array('error' => 'Erro ao encaminhar'), 500);
    }

    // ─── Pin / Unpin Message ──────────────────────────────────────────
    public function rest_pin_message(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid    = (int) $req->get_param('id');
        $uid    = get_current_user_id();
        $method = $req->get_method();

        if ($method === 'POST') {
            $mid = (int) ($req->get_param('message_id') ?? 0);
            if (! $mid) {
                return new \WP_REST_Response(array('error' => 'message_id obrigatório'), 400);
            }
            $ok = apollo_chat_pin_message($tid, $mid, $uid);
            return new \WP_REST_Response(array('pinned' => $ok), $ok ? 200 : 403);
        }
        if ($method === 'DELETE') {
            $mid = (int) ($req->get_param('message_id') ?? 0);
            $ok  = apollo_chat_unpin_message($tid, $mid, $uid);
            return new \WP_REST_Response(array('unpinned' => $ok), $ok ? 200 : 403);
        }
        return new \WP_REST_Response(array('error' => 'Method not allowed'), 405);
    }

    public function rest_get_pinned(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid = (int) $req->get_param('id');
        $uid = get_current_user_id();
        return new \WP_REST_Response(apollo_chat_get_pinned_messages($tid, $uid), 200);
    }

    /**
     * REST: Find-or-create a thread with a specific user, optionally with context.
     * Used by classified single page, event pages, profile pages.
     * POST /chat/thread-for-context { recipient_id, context?, subject?, message? }
     */
    public function rest_thread_for_context(\WP_REST_Request $req): \WP_REST_Response
    {
        $uid          = get_current_user_id();
        $recipient_id = (int) $req->get_param('recipient_id');
        $context      = $req->get_param('context') ?? '';
        $subject      = $req->get_param('subject') ?? '';
        $message      = $req->get_param('message') ?? '';

        if (! $recipient_id || $recipient_id === $uid) {
            return new \WP_REST_Response(array('error' => 'Destinatário inválido'), 400);
        }

        // Check if recipient exists
        if (! get_userdata($recipient_id)) {
            return new \WP_REST_Response(array('error' => 'Usuário não encontrado'), 404);
        }

        // Check if blocked
        global $wpdb;
        $pfx     = $wpdb->prefix . 'apollo_';
        $blocked = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$pfx}chat_blocks
             WHERE (blocker_id = %d AND blocked_id = %d) OR (blocker_id = %d AND blocked_id = %d)",
                $uid,
                $recipient_id,
                $recipient_id,
                $uid
            )
        );
        if ($blocked) {
            return new \WP_REST_Response(array('error' => 'Não é possível iniciar conversa'), 403);
        }

        $thread_id = apollo_chat_find_or_create_thread($uid, $recipient_id, $context, $subject);

        // If a message was provided, send it as first message
        if ($message && $thread_id) {
            apollo_send_message(
                array(
                    'sender_id' => $uid,
                    'thread_id' => $thread_id,
                    'message'   => $message,
                )
            );
        }

        $recipient = get_userdata($recipient_id);

        return new \WP_REST_Response(
            array(
                'thread_id'        => $thread_id,
                'thread_url'       => apollo_chat_thread_permalink((int) $thread_id, (int) get_current_user_id()),
                'recipient_name'   => $recipient ? $recipient->display_name : '',
                'recipient_avatar' => $this->get_avatar($recipient_id),
            ),
            200
        );
    }

	// ─── GIF Search — DISABLED (text-only policy, 2026-08-25) ──────
    /**
     * GET /chat/gif-search — DISABLED. A GIF is media, not text, so this
     * route (formerly a Tenor API v2 proxy) is intentionally inert. Kept
     * registered so an old client gets a clear 403 instead of a 404.
     * See class docblock for the full text-only policy.
     */
    public function rest_gif_search(\WP_REST_Request $req): \WP_REST_Response
    {
        return new \WP_REST_Response(
            array('error' => 'Envio de GIFs não é permitido. O chat é somente texto.'),
            403
        );
    }

	// ─── Spam Report Handlers ─────────────────────────────────────────

    /** POST /chat/messages/{id}/report */
    public function rest_report_message(\WP_REST_Request $req): \WP_REST_Response
    {
        $mid    = (int) $req->get_param('id');
        $uid    = get_current_user_id();
        $reason = sanitize_text_field($req->get_param('reason') ?? '');

        $ok = apollo_chat_report_message($mid, $uid, $reason);
        if ($ok) {
            return new \WP_REST_Response(array('reported' => true), 201);
        }
        return new \WP_REST_Response(array('error' => 'Já denunciado ou inválido'), 409);
    }

    /** GET /chat/threads/{id}/export — CSV download */
    public function rest_export_thread(\WP_REST_Request $req): \WP_REST_Response
    {
        $tid = (int) $req->get_param('id');
        $uid = get_current_user_id();
        $csv = apollo_chat_export_thread_csv($tid, $uid);

        if ($csv === '') {
            return new \WP_REST_Response(array('error' => 'Sem acesso ou sem mensagens'), 403);
        }

        // Return as JSON with CSV content; client triggers download
        return new \WP_REST_Response(
            array(
                'filename' => 'conversa-' . $tid . '.csv',
                'content'  => $csv,
            ),
            200
        );
    }

    /** GET /chat/reports — Admin: list pending reports */
    public function rest_get_reports(\WP_REST_Request $req): \WP_REST_Response
    {
        $status = sanitize_text_field($req->get_param('status') ?? 'pending');
        $limit  = min((int) ($req->get_param('limit') ?? 50), 100);
        $offset = (int) ($req->get_param('offset') ?? 0);

        return new \WP_REST_Response(apollo_chat_get_reports($status, $limit, $offset), 200);
    }

    /** POST /chat/reports/{id}/action — Admin: resolve a report */
    public function rest_resolve_report(\WP_REST_Request $req): \WP_REST_Response
    {
        $report_id = (int) $req->get_param('id');
        $action    = sanitize_text_field($req->get_param('action') ?? 'dismiss');
        $admin_id  = get_current_user_id();

        $allowed = array('dismiss', 'delete_message', 'block_sender');
        if (! in_array($action, $allowed, true)) {
            return new \WP_REST_Response(array('error' => 'Ação inválida'), 400);
        }

        $ok = apollo_chat_resolve_report($report_id, $action, $admin_id);
        return new \WP_REST_Response(array('resolved' => $ok), $ok ? 200 : 404);
    }

    /**
     * POST /chat/upload — DISABLED (text-only policy, 2026-08-25).
     *
     * apollo-chat is text-only: no file, image, audio or video attachments,
     * no GIFs, no voice messages. This route is kept registered (rather than
     * removed) so a client that still POSTs here gets a clear, explicit
     * rejection instead of a generic 404 — the old implementation, which
     * uploaded straight into the WordPress media library via
     * media_handle_upload(), is intentionally gone. See class docblock.
     *
     * @param \WP_REST_Request $req Incoming REST request (ignored).
     * @return \WP_REST_Response    403 — attachments are not allowed.
     */
    public function rest_upload(\WP_REST_Request $req): \WP_REST_Response
    {
        return new \WP_REST_Response(
            array('error' => 'Anexos não são permitidos. O chat é somente texto.'),
            403
        );
    }

    // ─── Ephemeral Cleanup (hourly cron) ─────────────────────────────
    public function cleanup_ephemeral(): void
    {
        global $wpdb;
        $pfx = $wpdb->prefix . 'apollo_';
        // Note: only the trusted table prefix ($wpdb->prefix) is interpolated; there is no
        // user-supplied input in these queries, so $wpdb->prepare() is not applicable
        // (table identifiers cannot be bound as placeholders).
        // Old typing records > 10 seconds.
        $wpdb->query("DELETE FROM {$pfx}chat_typing WHERE updated_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // Stale presence > 5 minutes.
        $wpdb->query("DELETE FROM {$pfx}chat_presence WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // Old messages (if retention policy is set)
        apollo_chat_cleanup_old_messages();
    }

    // ─── Helpers ─────────────────────────────────────────────────────
    private function enrich_threads(array $threads, int $user_id): array
    {
        foreach ($threads as &$t) {
            $parts    = $t['participants'] ?? array();
            $is_group = (int) ($t['is_group'] ?? 0) === 1 || count($parts) > 1;

            if ($is_group) {
                $group_name = $t['group_name'] ?? '';
                if (empty($group_name)) {
                    $names      = array_map(fn($p) => $p['display_name'] ?? '', $parts);
                    $group_name = implode(', ', array_slice($names, 0, 3)) . (count($names) > 3 ? '...' : '');
                }
                $t['display_name'] = $group_name;
                $t['avatar_url']   = $t['group_avatar'] ?? '';
                $t['is_group']     = true;
                $t['member_count'] = count($parts) + 1;
                $t['is_online']    = false;
            } else {
                $p                  = $parts[0] ?? null;
                $uid                = $p ? (int) ($p['user_id'] ?? 0) : 0;
                $t['display_name']  = $p ? ($p['display_name'] ?? 'Desconhecido') : 'Desconhecido';
                $t['avatar_url']    = $uid ? $this->get_avatar($uid) : '';
                $t['is_group']      = false;
                $t['other_user_id'] = $uid;
                $t['is_online']     = $uid ? apollo_chat_is_online($uid) : false;

                /*
                 * The counterpart's login, so the client can put a HANDLE in the
                 * address bar instead of a thread id. Without this the SPA calls
                 * history.replaceState('/mensagens/' + threadId) and every URL a
                 * user copies out of the app is numeric again — the naming would
                 * stop at the server boundary.
                 *
                 * user_login only; no e-mail, no capabilities. It is already
                 * public at /id/{login}, so this exposes nothing new.
                 */
                $t['other_user_login'] = '';
                if ($uid) {
                    $peer = get_userdata($uid);
                    if ($peer && '' !== (string) $peer->user_login) {
                        $t['other_user_login'] = (string) $peer->user_login;
                    }
                }
            }

            $t['time_ago'] = function_exists('apollo_time_ago') && ! empty($t['last_message_at'])
                ? apollo_time_ago($t['last_message_at'])
                : ($t['last_message_at'] ?? '');

            // Membership badge for each participant
            if (function_exists('apollo_get_user_badge_data')) {
                if (! $is_group && isset($t['other_user_id']) && $t['other_user_id']) {
                    $t['badge'] = apollo_get_user_badge_data((int) $t['other_user_id']);
                } else {
                    $t['badge'] = null;
                }
            }

            // Last message preview
            $lm = $t['last_message'] ?? null;
            if ($lm) {
                $t['preview']        = wp_trim_words($lm['message'] ?? '', 8, '...');
                $t['preview_sender'] = ((int) ($lm['sender_id'] ?? 0) === $user_id)
                    ? 'Você' : ($lm['display_name'] ?? '');
            }
        }
        return $threads;
    }

    private function get_avatar(int $user_id): string
    {
        if (function_exists('apollo_get_user_avatar_url')) {
            return apollo_get_user_avatar_url($user_id);
        }
        return get_avatar_url($user_id, array('size' => 80));
    }
}
