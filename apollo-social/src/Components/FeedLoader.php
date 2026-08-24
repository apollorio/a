<?php
/**
 * Feed Loader — Query and load posts from REST API / DB
 *
 * @package Apollo\Social
 * @version 6.5.0
 */

namespace Apollo\Social\Components;

if (! defined('ABSPATH')) {
    exit;
}

class FeedLoader {
    /**
     * Load feed posts with pagination
     *
     * @param array $args Query arguments.
     * @return array Feed posts
     */
    public static function load_feed(array $args = []): array {
        $defaults = [
            'per_page' => 10,
            'page' => 1,
            'filter' => 'all', // all|sounds|djs|events|locs|groups
            'sort' => 'recent', // recent|popular|trending
        ];
        $args = wp_parse_args($args, $defaults);

        $posts = [];

        // Always use direct DB query for server-side rendering.
        // REST endpoint is for client-side JS (explore.js) calls.
        $posts = self::fetch_from_db($args);

        return $posts;
    }

    /**
     * Fetch from REST API — stub.
     * REST endpoint is consumed by client-side JS (explore.js), not server-side.
     * Kept for interface compatibility.
     */
    private static function fetch_from_rest(array $args): array {
        return [];
    }

    /**
     * Fallback: Query database directly (activities from apollo_activity table)
     */
    private static function fetch_from_db(array $args): array {
        global $wpdb;

        $per_page = absint($args['per_page']);
        $page = max(1, absint($args['page']));
        $offset = ($page - 1) * $per_page;

        $table = $wpdb->prefix . 'apollo_activity';
        $blocks = $wpdb->prefix . 'apollo_blocks';

        $where = 'WHERE a.hide_sitewide = 0 AND a.is_spam = 0';

        // Exclude blocked users (assuming user_id is available, using 0 for now)
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            $where .= $wpdb->prepare(
                " AND a.user_id NOT IN (SELECT blocked_id FROM {$blocks} WHERE blocker_id = %d)",
                $user_id
            );
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, u.display_name, u.user_login
                 FROM {$table} a
                 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                 {$where}
                 ORDER BY a.created_at DESC
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $activities = [];
        foreach($results as $row) {
            // For social posts, use the content; for other activities, use action_text
            $content = '';
            if ($row['component'] === 'social' && $row['type'] === 'post' && !empty($row['content'])) {
                $content = $row['content'];
            } elseif (!empty($row['action_text'])) {
                $content = $row['action_text'];
            }

            $activities[] = [
                'id' => $row['id'],
                'content' => $content,
                'author_id' => absint($row['user_id']),
                'date' => $row['created_at'],
                'type' => $row['type'],
                'component' => $row['component'],
                'action_text' => $row['action_text'] ?: '',
                'user_login' => $row['user_login'],
                'display_name' => $row['display_name'],
                'primary_link' => $row['primary_link'],
                'item_id' => absint($row['item_id']),
                'media' => null, // Activities don't have media
            ];
        }

        return $activities;
    }

    /**
     * Load depoimentos (comments) for a post
     */
    private static function load_comments(int $post_id): array {
        $comments = get_comments([
            'post_id' => $post_id,
            'status' => 'approve',
            'number' => 5,
        ]);

        $output = [];
        foreach($comments as $comment) {
            $output[] = [
                'id' => $comment->comment_ID,
                'author_id' => $comment->user_id,
                'author_name' => $comment->comment_author,
                'content' => wp_kses_post($comment->comment_content),
                'date' => $comment->comment_date,
            ];
        }

        return $output;
    }
}
