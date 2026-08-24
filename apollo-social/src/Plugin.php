<?php

/**
 * Apollo Social — Main Plugin Class
 *
 * Activity streams adapted from BuddyPress bp-activity patterns.
 * Party Model: auto-connections on registration. No likes. No followers hierarchy. No ego counters.
 *
 * Registry compliance:
 *   REST: /feed, /feed/post, /activity/{id}, /depo/{user_id}
 *   Pages: /feed
 *   Shortcodes: [apollo_feed], [apollo_follow_btn]
 *   Tables: apollo_follows (core), apollo_blocks (core), apollo_activity (plugin)
 *
 * @package Apollo\Social
 */

declare(strict_types=1);

namespace Apollo\Social;

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
        // Page routing moved to apollo-templates (canonical owner of /feed).
        // apollo-social only provides REST API, shortcodes, assets, and activity logging.
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('init', array($this, 'register_assets'), 10);
        // Redirect legacy /explore and /mural → /feed
        add_action('parse_request', array($this, 'redirect_deprecated_slugs'), 1);

        // Auto-connect new users — critical: no friends/followers system
        add_action('user_register', 'apollo_auto_connect_new_user', 10, 1);

        // Log activity for core WP events
        add_action('publish_post', array($this, 'on_publish_post'), 10, 2);
        // Listen to Apollo-namespaced hook instead of wordpress native 'publish_event'
        // (apollo-events fires this via on_status_transition in Registry.php)
        // 3 args: (int $post_id, string $action, \WP_Post $post) — emitter contract.
        add_action('apollo/event/published', array($this, 'on_publish_event'), 10, 3);
        add_action('publish_classified', array($this, 'on_publish_classified'), 10, 2);

        // Provide is_following filter for cross-plugin queries (apollo-statistics, etc.).
        add_filter('apollo/social/is_following', array($this, 'filter_is_following'), 10, 3);
    }

    // ─── Deprecated Slug Redirect (/explore, /mural → /feed) ───────
    public function redirect_deprecated_slugs(): void
    {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/');
        if ($uri === 'explore' || $uri === 'mural') {
            wp_safe_redirect(home_url('/feed'), 301);
            exit;
        }
    }

    // ─── REST API ──────────────────────────────────────────────────
    public function register_rest_routes(): void
    {
        $ns = 'apollo/v1';

        register_rest_route(
            $ns,
            '/feed',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_feed'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
                'args'                => array(
                    'per_page'  => array(
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                    ),
                    'page'      => array(
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'component' => array(
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        register_rest_route(
            $ns,
            '/feed/post',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_create_post'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            )
        );

        register_rest_route(
            $ns,
            '/activity/(?P<id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'rest_delete_activity'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            )
        );

        // Depoimentos endpoint — testimonials/comments about a user
        register_rest_route(
            $ns,
            '/depo/(?P<user_id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_user_depoimentos'),
                'permission_callback' => '__return_true',
            )
        );

        // ── Activity Replies (bp-activity reply pattern) ──────────────
        register_rest_route(
            $ns,
            '/activity/(?P<id>\d+)/replies',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array($this, 'rest_get_replies'),
                    'permission_callback' => '__return_true',
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'rest_create_reply'),
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ),
            )
        );

        register_rest_route(
            $ns,
            '/activity/(?P<id>\d+)',
            array(
                'methods'             => 'PUT',
                'callback'            => array($this, 'rest_edit_activity'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            )
        );

        // ── Spam report (bp-activity spam action) ─────────────────────
        register_rest_route(
            $ns,
            '/activity/(?P<id>\d+)/spam',
            array(
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'rest_report_spam'),
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array($this, 'rest_unmark_spam'),
                    'permission_callback' => function () {
                        return current_user_can('apollo_moderate_content');
                    },
                ),
            )
        );

        // ── Block / Unblock (bp-core moderation pattern) ──────────────
        register_rest_route(
            $ns,
            '/blocks',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_blocks'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            )
        );

        register_rest_route(
            $ns,
            '/block/(?P<user_id>\d+)',
            array(
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'rest_block_user'),
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array($this, 'rest_unblock_user'),
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ),
            )
        );

        // ── Member Directory (bp-members pattern) ─────────────────────
        register_rest_route(
            $ns,
            '/members',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_members'),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'search'   => array(
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'per_page' => array(
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                    ),
                    'page'     => array(
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'type'     => array(
                        'default'           => 'newest',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // ── Member Suggestions (bp-members suggestions) ────────────────
        register_rest_route(
            $ns,
            '/members/suggestions',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_member_suggestions'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
                'args'                => array(
                    'limit' => array(
                        'default'           => 8,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );
    }

    public function rest_get_feed(\WP_REST_Request $request): \WP_REST_Response
    {
        $per_page  = $request->get_param('per_page');
        $page      = $request->get_param('page');
        $offset    = ($page - 1) * $per_page;
        $component = $request->get_param('component');

        $feed = apollo_get_feed(get_current_user_id(), $per_page, $offset, $component);

        // Enrich with complete user display data
        foreach ($feed as &$item) {
            $uid = (int) $item['user_id'];

            // Get complete user data (name, badges, memberships, núcleos, handle, time)
            if (function_exists('apollo_get_user_display_data')) {
                $user_data = apollo_get_user_display_data($uid);
                // Merge user data into item
                $item['avatar_url']   = $user_data['avatar_url'] ?? '';
                $item['display_name'] = $user_data['display_name'] ?? '';
                $item['handle']       = $user_data['handle'] ?? '';
                $item['profile_url']  = $user_data['profile_url'] ?? '';
                $item['badge']        = $user_data['badge'] ?? array();
                $item['membership']   = $user_data['membership'] ?? array();
                $item['nucleos']      = $user_data['nucleos'] ?? array();
                $item['member_for']   = $user_data['member_for'] ?? '';
                $item['member_since'] = $user_data['member_since'] ?? '';
            } else {
                // Fallback to basic data
                $item['avatar_url'] = function_exists('apollo_get_user_avatar_url')
                    ? apollo_get_user_avatar_url($uid)
                    : get_avatar_url($uid, array('size' => 48));
                $item['badge']      = array(
                    'type'    => 'nao-verificado',
                    'label'   => '',
                    'ri_icon' => '',
                    'color'   => '',
                );
                $item['membership'] = array(
                    'level'      => 'free',
                    'is_premium' => false,
                );
                $item['nucleos']    = array();
            }

            $item['time_ago']    = function_exists('apollo_time_ago') ? apollo_time_ago($item['created_at']) : $item['created_at'];
            $item['profile_url'] = home_url('/id/' . ($item['user_login'] ?? ''));

            // Parse content: linkify mentions/hashtags + render embeds
            if (! empty($item['content']) && $item['component'] === 'social' && $item['type'] === 'post') {
                $embeds = array();

                // Try to load stored embed data from primary_link
                if (! empty($item['primary_link'])) {
                    $stored = json_decode($item['primary_link'], true);
                    if (is_array($stored)) {
                        foreach ($stored as $embed_data) {
                            $embed = ContentParser::detect_embed($embed_data['url'] ?? '');
                            if ($embed) {
                                $embeds[] = $embed;
                            }
                        }
                        // Don't show raw JSON as a link
                        $item['primary_link'] = '';
                    }
                }

                // If no stored embeds, re-parse content for URLs
                if (empty($embeds)) {
                    $parsed = ContentParser::parse($item['content']);
                    $embeds = $parsed['embeds'];
                }

                $item['content_html'] = ContentParser::render_post_html($item['content'], $embeds);
                $item['embeds']       = array_map(
                    function ($e) {
                        return array(
                            'type' => $e['type'],
                            'url'  => $e['url'] ?? '',
                            'html' => $e['html'] ?? '',
                        );
                    },
                    $embeds
                );
            } else {
                $item['content_html'] = $item['content'] ? esc_html($item['content']) : '';
                $item['embeds']       = array();
            }
        }

        return new \WP_REST_Response($feed, 200);
    }

    public function rest_create_post(\WP_REST_Request $request): \WP_REST_Response
    {
        $raw = $request->get_param('content') ?? '';

        // Security: reject any image/file upload attempts (TODO 03)
        $files = $request->get_file_params();
        if (! empty($files)) {
            return new \WP_REST_Response(
                array('error' => __('Upload de imagens não é permitido. Posts são apenas texto e URLs.', 'apollo-social')),
                403
            );
        }

        // Security: strip any base64 image data or data URIs from content
        if (preg_match('/data:\s*image\//i', $raw)) {
            return new \WP_REST_Response(
                array('error' => __('Imagens embutidas não são permitidas.', 'apollo-social')),
                403
            );
        }

        // Security: strip any <img> tags
        if (preg_match('/<img[\s>]/i', $raw)) {
            return new \WP_REST_Response(
                array('error' => __('Tags de imagem não são permitidas.', 'apollo-social')),
                403
            );
        }

        // Parse content — enforce 280 char limit, extract embeds, validate
        $parsed = ContentParser::parse($raw);

        if (! $parsed['valid']) {
            return new \WP_REST_Response(array('error' => $parsed['error']), 400);
        }

        // Store embed data as JSON in the primary_link field (serialized)
        $embed_json = '';
        if (! empty($parsed['embeds'])) {
            // Store only essential data, not full HTML (re-render on display)
            $embed_store = array_map(
                function ($e) {
                    $data = array(
                        'type' => $e['type'],
                        'url'  => $e['url'],
                    );
                    if (isset($e['subtype'])) {
                        $data['subtype'] = $e['subtype'];
                    }
                    if (isset($e['id'])) {
                        $data['id'] = $e['id'];
                    }
                    if (isset($e['slug'])) {
                        $data['slug'] = $e['slug'];
                    }
                    if (isset($e['event_id'])) {
                        $data['event_id'] = $e['event_id'];
                    }
                    if (isset($e['video_id'])) {
                        $data['video_id'] = $e['video_id'];
                    }
                    return $data;
                },
                $parsed['embeds']
            );
            $embed_json  = wp_json_encode($embed_store);
        }

        $activity_id = apollo_log_activity(
            array(
                'user_id'      => get_current_user_id(),
                'component'    => 'social',
                'type'         => 'post',
                'action_text'  => 'publicou no feed',
                'content'      => $parsed['content'],
                'primary_link' => $embed_json,
            )
        );

        if ($activity_id) {
            return new \WP_REST_Response(
                array(
                    'id'         => $activity_id,
                    'char_count' => $parsed['char_count'],
                    'embeds'     => count($parsed['embeds']),
                ),
                201
            );
        }
        return new \WP_REST_Response(array('error' => 'Erro ao criar post'), 500);
    }

    public function rest_delete_activity(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $id    = (int) $request->get_param('id');
        $table = $wpdb->prefix . 'apollo_activity';

        // Only owner or admin can delete
        $activity = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        if (! $activity) {
            return new \WP_REST_Response(array('error' => 'Não encontrado'), 404);
        }

        $uid = get_current_user_id();
        if ((int) $activity['user_id'] !== $uid && ! current_user_can('apollo_moderate_content')) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }

        $wpdb->delete($table, array('id' => $id));
        return new \WP_REST_Response(array('deleted' => true), 200);
    }

    /**
     * GET /depo/{user_id} — Depoimentos (testimonials) about a user.
     * Queries WP comments on the user's profile "page" (author archive).
     */
    public function rest_get_user_depoimentos(\WP_REST_Request $request): \WP_REST_Response
    {
        $user_id = (int) $request->get_param('user_id');
        $user    = get_userdata($user_id);
        if (! $user) {
            return new \WP_REST_Response(array('error' => 'User not found'), 404);
        }

        $args = array(
            'type'    => 'depoimento',
            'status'  => 'approve',
            'orderby' => 'comment_date',
            'order'   => 'DESC',
            'number'  => 20,
            'meta_query' => array(
                array(
                    'key'   => '_depoimento_target_user',
                    'value' => $user_id,
                ),
            ),
        );

        $comments = get_comments($args);
        $items    = array();

        foreach ($comments as $c) {
            $items[] = array(
                'id'           => (int) $c->comment_ID,
                'author_id'    => (int) $c->user_id,
                'author_name'  => $c->comment_author,
                'author_login' => get_the_author_meta('user_login', $c->user_id),
                'content'      => wp_kses_post($c->comment_content),
                'date'         => $c->comment_date,
                'avatar'       => get_avatar_url($c->user_id, array('size' => 80)),
            );
        }

        return new \WP_REST_Response(array(
            'user_id'    => $user_id,
            'user_name'  => $user->display_name,
            'user_login' => $user->user_login,
            'items'      => $items,
            'count'      => count($items),
        ), 200);
    }

    /**
     * Filter: apollo/social/is_following
     *
     * Apollo party model — ALL users are auto-connected on registration.
     * No selective follow/unfollow. No friends hierarchy. No ego counters.
     * Everyone is reachable like at a party.
     *
     * Returns true for any two valid registered users (unless one blocked the other).
     *
     * @param bool $is_following Default value (false).
     * @param int  $viewer_id   The viewing user.
     * @param int  $target_id   The target user.
     * @return bool
     */
    public function filter_is_following( bool $is_following, int $viewer_id, int $target_id ): bool {
        if ( $viewer_id <= 0 || $target_id <= 0 || $viewer_id === $target_id ) {
            return false;
        }

        // Both must be valid registered users.
        if ( ! get_userdata( $viewer_id ) || ! get_userdata( $target_id ) ) {
            return false;
        }

        // Blocked users are NOT connected.
        global $wpdb;
        $blocks_table = $wpdb->prefix . 'apollo_blocks';
        $blocked = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(1) FROM {$blocks_table}
                 WHERE (blocker_id = %d AND blocked_id = %d)
                    OR (blocker_id = %d AND blocked_id = %d)
                 LIMIT 1",
                $viewer_id, $target_id,
                $target_id, $viewer_id
            )
        );

        // Party model: everyone is connected unless blocked.
        return $blocked === 0;
    }

    // ─── Shortcodes ─────────────────────────────────────────────────
    public function register_shortcodes(): void
    {
        add_shortcode('apollo_feed', array($this, 'shortcode_feed'));
        add_shortcode('apollo_follow_btn', array($this, 'shortcode_follow_btn'));
    }

    public function shortcode_feed(array $atts): string
    {
        if (! is_user_logged_in()) {
            return '<p>Faça login para ver o feed.</p>';
        }
        $a    = shortcode_atts(
            array(
                'limit'   => 10,
                'user_id' => 0,
            ),
            $atts
        );
        $feed = apollo_get_feed(get_current_user_id(), (int) $a['limit']);

        ob_start();
        echo '<div class="apollo-feed" data-rest="' . esc_url(rest_url('apollo/v1/feed')) . '">';
        foreach ($feed as $item) {
            $avatar = function_exists('apollo_get_user_avatar_url')
                ? apollo_get_user_avatar_url((int) $item['user_id'])
                : get_avatar_url((int) $item['user_id'], array('size' => 40));
            $time   = function_exists('apollo_time_ago') ? apollo_time_ago($item['created_at']) : $item['created_at'];
?>
            <div class="feed-item" data-id="<?php echo esc_attr($item['id']); ?>">
                <div class="feed-header">
                    <img src="<?php echo esc_url($avatar); ?>" alt="" style="width:36px;height:36px;border-radius:50%;">
                    <div>
                        <a href="<?php echo esc_url(home_url('/id/' . $item['user_login'])); ?>" style="font-weight:600;">
                            <?php echo esc_html($item['display_name']); ?>
                        </a>
                        <span style="color:var(--ap-text-muted);font-size:.8rem;">
                            <?php echo esc_html($item['action_text']); ?></span>
                        <div style="font-size:.7rem;color:var(--ap-text-muted);"><?php echo esc_html($time); ?></div>
                    </div>
                </div>
                <?php if ($item['content']) : ?>
                    <div class="feed-content" style="margin-top:.5rem;"><?php echo wp_kses_post($item['content']); ?></div>
                <?php endif; ?>
            </div>
<?php
        }
        if (empty($feed)) {
            echo '<p style="text-align:center;color:var(--ap-text-muted);">Nenhuma atividade ainda.</p>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function shortcode_follow_btn(array $atts): string
    {
        // No follow buttons — all users are auto-connected
        return '<span style="font-size:.8rem;color:var(--ap-text-muted);">Conectado</span>';
    }

    // ─── Activity Loggers ────────────────────────────────────────────
    public function on_publish_post(int $post_id, \WP_Post $post): void
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        apollo_log_activity(
            array(
                'user_id'      => (int) $post->post_author,
                'component'    => 'content',
                'type'         => 'new_post',
                'action_text'  => sprintf('publicou "%s"', $post->post_title),
                'item_id'      => $post_id,
                'primary_link' => get_permalink($post),
            )
        );
    }

    /**
     * Hook: apollo/event/published (int $post_id, string $action, \WP_Post $post)
     *
     * BUGFIX (fatal): argument #2 was typed \WP_Post, but the emitter
     * (apollo-events Registry::on_status_transition) passes the STRING
     * 'published' there — the post object is argument #3. Its docblock states
     * this as an ecosystem contract; the emitter was corrected for
     * apollo-statistics' HookCollector and the listeners were never updated
     * alongside it. Result: every draft→publish transition threw a TypeError
     * and no event could be published.
     *
     * $post is now read from argument #3, with a get_post() fallback so a
     * caller that only passes the ID still works. Activity logging must never
     * be able to block a publish.
     *
     * @param int           $post_id Event post ID.
     * @param string        $action  Action name ('published').
     * @param \WP_Post|null $post    Event post object.
     */
    public function on_publish_event(int $post_id, string $action = 'published', ?\WP_Post $post = null): void
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        if (! $post instanceof \WP_Post) {
            $post = get_post($post_id);
        }
        if (! $post instanceof \WP_Post) {
            return;
        }
        apollo_log_activity(
            array(
                'user_id'      => (int) $post->post_author,
                'component'    => 'events',
                'type'         => 'new_event',
                'action_text'  => sprintf('criou o evento "%s"', $post->post_title),
                'item_id'      => $post_id,
                'primary_link' => get_permalink($post),
            )
        );
    }

    /**
     * Hook: log activity when a classified is published.
     * Triggered via publish_classified transition.
     */
    public function on_publish_classified(int $post_id, \WP_Post $post): void
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        // Only log if adverts plugin didn't already log (avoid duplicates)
        if (did_action('apollo/classifieds/created')) {
            return;
        }

        apollo_log_activity(
            array(
                'user_id'      => (int) $post->post_author,
                'component'    => 'classifieds',
                'type'         => 'classified_created',
                'action_text'  => sprintf('publicou o anúncio "%s"', $post->post_title),
                'item_id'      => $post_id,
                'primary_link' => get_permalink($post),
            )
        );
    }

	// ─── Activity Replies ────────────────────────────────────────────

    /**
     * GET /activity/{id}/replies — List replies on an activity post.
     * Adapted from BuddyPress bp-activity reply actions.
     */
    public function rest_get_replies(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $parent_id = (int) $request->get_param('id');
        $table     = $wpdb->prefix . 'apollo_activity';

        $replies = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, u.display_name, u.user_login
                 FROM {$table} a
                 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                 WHERE a.secondary_item_id = %d AND a.type = 'activity_reply' AND a.is_spam = 0
                 ORDER BY a.created_at ASC
                 LIMIT 100",
                $parent_id
            ),
            ARRAY_A
        );

        if (! $replies) {
            return new \WP_REST_Response(array(), 200);
        }

        $uid = get_current_user_id();
        foreach ($replies as &$r) {
            $rid              = (int) $r['user_id'];
            $r['avatar_url']  = function_exists('apollo_get_user_avatar_url')
                ? apollo_get_user_avatar_url($rid)
                : get_avatar_url($rid, array('size' => 36));
            $r['profile_url'] = home_url('/id/' . $r['user_login']);
            $r['time_ago']    = function_exists('apollo_time_ago') ? apollo_time_ago($r['created_at']) : $r['created_at'];
            $r['can_delete']  = $uid && ($uid === $rid || current_user_can('apollo_moderate_content'));
        }

        return new \WP_REST_Response($replies, 200);
    }

    /**
     * POST /activity/{id}/reply — Create a reply on an activity post.
     * Adapted from BuddyPress bp-activity/actions/reply.php.
     */
    public function rest_create_reply(\WP_REST_Request $request): \WP_REST_Response
    {
        $parent_id = (int) $request->get_param('id');
        $content   = sanitize_text_field($request->get_param('content') ?? '');

        if (empty($content)) {
            return new \WP_REST_Response(array('error' => 'Conteúdo obrigatório'), 400);
        }
        if (mb_strlen($content) > 280) {
            return new \WP_REST_Response(array('error' => 'Máximo 280 caracteres'), 400);
        }

        global $wpdb;
        $table    = $wpdb->prefix . 'apollo_activity';
        $activity = $wpdb->get_row(
            $wpdb->prepare("SELECT id, user_id FROM {$table} WHERE id = %d AND is_spam = 0", $parent_id),
            ARRAY_A
        );

        if (! $activity) {
            return new \WP_REST_Response(array('error' => 'Post não encontrado'), 404);
        }

        $uid      = get_current_user_id();
        $reply_id = apollo_log_activity(
            array(
                'user_id'           => $uid,
                'component'         => 'social',
                'type'              => 'activity_reply',
                'action_text'       => 'respondeu',
                'content'           => $content,
                'item_id'           => $parent_id,
                'secondary_item_id' => $parent_id,
            )
        );

        if (! $reply_id) {
            return new \WP_REST_Response(array('error' => 'Erro ao salvar resposta'), 500);
        }

        // Notify parent post author
        $author_id = (int) $activity['user_id'];
        if ($author_id !== $uid && function_exists('apollo_create_notification')) {
            $me = get_userdata($uid);
            apollo_create_notification(
                $author_id,
                'activity_reply',
                ($me ? $me->display_name : 'Alguém') . ' respondeu seu post',
                $content,
                home_url('/explore'),
                array(
                    'activity_id' => $parent_id,
                    'reply_id'    => $reply_id,
                    'sender_id'   => $uid,
                )
            );
        }

        do_action('apollo/social/reply_created', $reply_id, $parent_id, $uid);

        return new \WP_REST_Response(
            array(
                'id'        => $reply_id,
                'parent_id' => $parent_id,
            ),
            201
        );
    }

    /**
     * PUT /activity/{id} — Edit an activity post (own posts only, within 15 min).
     */
    public function rest_edit_activity(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $id      = (int) $request->get_param('id');
        $content = sanitize_text_field($request->get_param('content') ?? '');
        $table   = $wpdb->prefix . 'apollo_activity';
        $uid     = get_current_user_id();

        if (empty($content)) {
            return new \WP_REST_Response(array('error' => 'Conteúdo obrigatório'), 400);
        }

        $activity = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (! $activity) {
            return new \WP_REST_Response(array('error' => 'Não encontrado'), 404);
        }

        if ((int) $activity['user_id'] !== $uid && ! current_user_can('apollo_moderate_content')) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }

        // Allow edits within 15 minutes for non-admins
        if ((int) $activity['user_id'] === $uid && ! current_user_can('apollo_moderate_content')) {
            $age = time() - strtotime($activity['created_at']);
            if ($age > 900) {
                return new \WP_REST_Response(array('error' => 'Edição permitida apenas nos primeiros 15 minutos'), 403);
            }
        }

        if (mb_strlen($content) > 280) {
            return new \WP_REST_Response(array('error' => 'Máximo 280 caracteres'), 400);
        }

        $wpdb->update(
            $table,
            array(
                'content'   => $content,
                'is_edited' => 1,
            ),
            array('id' => $id)
        );

        return new \WP_REST_Response(
            array(
                'updated' => true,
                'id'      => $id,
            ),
            200
        );
    }

	// ─── Spam / Moderation (bp-activity spam action) ─────────────────

    /**
     * POST /activity/{id}/spam — Report as spam.
     */
    public function rest_report_spam(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $id     = (int) $request->get_param('id');
        $reason = sanitize_text_field($request->get_param('reason') ?? '');
        $table  = $wpdb->prefix . 'apollo_activity';
        $uid    = get_current_user_id();

        $activity = $wpdb->get_row(
            $wpdb->prepare("SELECT id, user_id FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (! $activity) {
            return new \WP_REST_Response(array('error' => 'Não encontrado'), 404);
        }

        // Admins/mods → fire hook for apollo-mod (cross-plugin decoupling);
        // fallback to direct update if apollo-mod is not active.
        if (current_user_can('apollo_moderate_content')) {
            /**
             * apollo/mod/report — Fired when a moderator marks content as spam.
             * apollo-mod handles setting is_spam and logging the moderation action.
             * Falls back to direct DB update when apollo-mod is not installed.
             *
             * @param int    $id      Activity ID.
             * @param string $type    Content type ('activity').
             * @param array  $context Extra context: reporter_id, reason, set_spam flag.
             */
            do_action(
                'apollo/mod/report',
                $id,
                'activity',
                array(
                    'reporter_id' => $uid,
                    'reason'      => $reason ?: 'spam',
                    'set_spam'    => true,
                )
            );

            // Fallback: if no apollo-mod listener updated the record, do it directly.
            if (! (bool) $wpdb->get_var($wpdb->prepare("SELECT is_spam FROM {$table} WHERE id = %d", $id))) {
                $wpdb->update($table, array('is_spam' => 1), array('id' => $id));
            }

            return new \WP_REST_Response(array('marked_spam' => true), 200);
        }

        // Regular users: fire hook for mod queue
        do_action(
            'apollo/mod/reported',
            $id,
            'activity',
            array(
                'reporter_id' => $uid,
                'reason'      => $reason,
            )
        );

        return new \WP_REST_Response(array('reported' => true), 200);
    }

    /**
     * DELETE /activity/{id}/spam — Unmark spam (mod only).
     */
    public function rest_unmark_spam(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $id    = (int) $request->get_param('id');
        $table = $wpdb->prefix . 'apollo_activity';

        $wpdb->update($table, array('is_spam' => 0), array('id' => $id));
        return new \WP_REST_Response(array('unmarked' => true), 200);
    }

	// ─── Block / Unblock (bp-core moderation) ────────────────────────

    /**
     * GET /blocks — Get blocked user list.
     */
    public function rest_get_blocks(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $uid   = get_current_user_id();
        $table = $wpdb->prefix . 'apollo_blocks';

        $blocked = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT b.blocked_id, b.reason, b.created_at, u.display_name, u.user_login
                 FROM {$table} b
                 LEFT JOIN {$wpdb->users} u ON b.blocked_id = u.ID
                 WHERE b.blocker_id = %d
                 ORDER BY b.created_at DESC",
                $uid
            ),
            ARRAY_A
        );

        return new \WP_REST_Response($blocked ?: array(), 200);
    }

    /**
     * POST /block/{user_id} — Block a user.
     */
    public function rest_block_user(\WP_REST_Request $request): \WP_REST_Response
    {
        $target = (int) $request->get_param('user_id');
        $uid    = get_current_user_id();
        $reason = sanitize_text_field($request->get_param('reason') ?? '');

        if ($target === $uid) {
            return new \WP_REST_Response(array('error' => 'Não pode bloquear a si mesmo'), 400);
        }
        if (! get_userdata($target)) {
            return new \WP_REST_Response(array('error' => 'Usuário não encontrado'), 404);
        }

        $ok = apollo_block_user($uid, $target, $reason);
        do_action('apollo/social/user_blocked', $uid, $target);

        return new \WP_REST_Response(
            array(
                'blocked' => $ok,
                'user_id' => $target,
            ),
            $ok ? 200 : 409
        );
    }

    /**
     * DELETE /block/{user_id} — Unblock a user.
     */
    public function rest_unblock_user(\WP_REST_Request $request): \WP_REST_Response
    {
        $target = (int) $request->get_param('user_id');
        $uid    = get_current_user_id();

        $ok = apollo_unblock_user($uid, $target);
        do_action('apollo/social/user_unblocked', $uid, $target);

        return new \WP_REST_Response(
            array(
                'unblocked' => $ok,
                'user_id'   => $target,
            ),
            $ok ? 200 : 404
        );
    }

	// ─── Member Directory & Suggestions (bp-members pattern) ─────────

    /**
     * GET /members — Member directory with search + filters.
     * Adapted from BuddyPress BP_Members_Component::setup_globals().
     * Delegates member query to the shared apollo_query_members() helper.
     */
    public function rest_get_members(\WP_REST_Request $request): \WP_REST_Response
    {
        $search   = sanitize_text_field($request->get_param('search') ?? '');
        $per_page = min(50, absint($request->get_param('per_page') ?? 20));
        $page     = absint($request->get_param('page') ?? 1);
        $type     = sanitize_key($request->get_param('type') ?? 'newest');
        $offset   = ($page - 1) * $per_page;
        $uid      = get_current_user_id();

        // Use the shared Apollo member query helper (apollo-users/includes/functions.php).
        // Falls back to direct WP_User_Query if the helper is not yet available.

        if (function_exists('apollo_query_members')) {
            $result = apollo_query_members(
                array(
                    'number' => $per_page,
                    'offset' => $offset,
                    'search' => $search,
                    'type'   => $type,
                )
            );
            $total = $result['total'];
            $users = $result['users'];
        } else {
            $args = array(
                'number' => $per_page,
                'offset' => $offset,
            );
            if ($search) {
                $args['search']         = '*' . $search . '*';
                $args['search_columns'] = array('display_name', 'user_login');
            }
            switch ($type) {
                case 'alphabetical':
                    $args['orderby'] = 'display_name';
                    $args['order']   = 'ASC';
                    break;
                default:
                    $args['orderby'] = 'registered';
                    $args['order']   = 'DESC';
                    break;
            }
            $user_query = new \WP_User_Query($args);
            $total      = $user_query->get_total();
            $users      = $user_query->get_results();
        }

        $members = array();
        foreach ($users as $user) {
            $member_data = array(
                'id'           => $user->ID,
                'login'        => $user->user_login,
                'display_name' => $user->display_name,
                'profile_url'  => home_url('/id/' . $user->user_login),
                'avatar_url'   => function_exists('apollo_get_user_avatar_url')
                    ? apollo_get_user_avatar_url($user->ID)
                    : get_avatar_url($user->ID, array('size' => 64)),
                'registered'   => $user->user_registered,
            );

            // Is blocked by current user
            if ($uid) {
                global $wpdb;
                $is_blocked                = (bool) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_blocks WHERE blocker_id = %d AND blocked_id = %d",
                        $uid,
                        $user->ID
                    )
                );
                $member_data['is_blocked'] = $is_blocked;
            }

            $members[] = $member_data;
        }

        return new \WP_REST_Response(
            array(
                'members' => $members,
                'total'   => $total,
                'pages'   => ceil($total / $per_page),
                'page'    => $page,
            ),
            200
        );
    }

    /**
     * Register stylesheets & scripts for explore page v3
     *
     * @return void
     */
    public function register_assets(): void
    {
        // Register v3 stylesheet
        wp_register_style(
            'apollo-social-v3',
            APOLLO_SOCIAL_URL . 'assets/css/admin.css',
            array(),
            APOLLO_SOCIAL_VERSION
        );

        // Enqueue on explore page
        if ($this->is_explore_page()) {
            wp_enqueue_style('apollo-social-v3');
        }
    }

    /**
     * Check if current page is explore page
     *
     * @return bool
     */
    private function is_explore_page(): bool
    {
        return is_front_page() || get_query_var('apollo_social_page') === 'explore';
    }

    /**
     * GET /members/suggestions — Suggest members to connect.
     * Adapted from BuddyPress class-bp-members-suggestions.php.
     * Returns users the current user hasn't interacted much with.
     */
    public function rest_member_suggestions(\WP_REST_Request $request): \WP_REST_Response
    {
        $limit = min(20, absint($request->get_param('limit') ?? 8));
        $uid   = get_current_user_id();

        global $wpdb;
        $blocks = $wpdb->prefix . 'apollo_blocks';

        // Get members not blocked by or blocking current user, newest first, exclude self
        $suggestions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT u.ID, u.display_name, u.user_login, u.user_registered
                 FROM {$wpdb->users} u
                 WHERE u.ID != %d
                   AND u.ID NOT IN (
                       SELECT blocked_id FROM {$blocks} WHERE blocker_id = %d
                       UNION
                       SELECT blocker_id FROM {$blocks} WHERE blocked_id = %d
                   )
                 ORDER BY u.user_registered DESC
                 LIMIT %d",
                $uid,
                $uid,
                $uid,
                $limit
            ),
            ARRAY_A
        );

        $result = array();
        foreach ($suggestions as $s) {
            $result[] = array(
                'id'           => (int) $s['ID'],
                'login'        => $s['user_login'],
                'display_name' => $s['display_name'],
                'profile_url'  => home_url('/id/' . $s['user_login']),
                'avatar_url'   => function_exists('apollo_get_user_avatar_url')
                    ? apollo_get_user_avatar_url((int) $s['ID'])
                    : get_avatar_url((int) $s['ID'], array('size' => 48)),
                'registered'   => $s['user_registered'],
            );
        }

        return new \WP_REST_Response($result, 200);
    }
}
