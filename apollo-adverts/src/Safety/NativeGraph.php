<?php

/**
 * NativeGraph — people in common, computed from Apollo's own data.
 *
 * NO API. NO TOKEN. NO SESSION. NO OUTBOUND REQUEST.
 *
 * WHY THIS IS THE MAIN METHOD AND INSTAGRAM IS NOT
 * There is no Instagram endpoint — official or otherwise — that returns a
 * follower list, so every "mutual friends" integration is a session scraper:
 * it needs a live cookie, it breaks on every layout change, and it gets the
 * account banned. A safety feature that can be switched off by Meta is not a
 * safety feature. This one cannot be rate-limited, cannot be banned, and works
 * offline.
 *
 * WHY apollo_activity AND THE SOCIAL FOLLOW TABLE ARE NOT USED
 * apollo-social auto-connects every user to every other user on activation —
 * "no friends/followers, everyone is connected". Intersecting that graph would
 * report every stranger on the platform as a mutual friend, which is worse
 * than reporting nothing: it would manufacture the exact false confidence this
 * whole gate exists to prevent.
 *
 * WHAT COUNTS AS AN EDGE
 * A depoimento — a native WP comment, since apollo-comment only relabels them.
 * Someone wrote about you, or you wrote about them. It is deliberate, it is
 * attributable, it is moderated, and it cannot be farmed in an afternoon the
 * way followers can. Two people who both have that edge with the same third
 * person genuinely share someone.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts\Safety;

if (! defined('ABSPATH')) {
    exit;
}

final class NativeGraph
{
    /** Cache lifetime. Long enough to matter, short enough to stay true. */
    private const TTL = 6 * HOUR_IN_SECONDS;

    /**
     * Everyone who has an earned edge with this member.
     *
     * Two directions, both deliberate acts by a named account:
     *   · someone left a depoimento on something this member published
     *   · this member left a depoimento on something someone else published
     *
     * @return int[] User ids, self excluded.
     */
    public static function contacts(int $user_id): array
    {
        if ($user_id < 1) {
            return array();
        }

        $key    = 'ap_graph_' . $user_id;
        $cached = get_transient($key);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;

        // Two simple queries beat one clever UNION here: each uses an index we
        // already have, and neither needs a temp table on a large comment set.
        $inbound = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT c.user_id
                   FROM {$wpdb->comments} c
                   INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
                  WHERE p.post_author = %d
                    AND c.user_id > 0
                    AND c.user_id <> %d
                    AND c.comment_approved = '1'",
                $user_id,
                $user_id
            )
        );

        $outbound = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT p.post_author
                   FROM {$wpdb->comments} c
                   INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
                  WHERE c.user_id = %d
                    AND p.post_author > 0
                    AND p.post_author <> %d
                    AND c.comment_approved = '1'",
                $user_id,
                $user_id
            )
        );

        $ids = array_values(array_unique(array_map('intval', array_merge((array) $inbound, (array) $outbound))));

        set_transient($key, $ids, self::TTL);

        return $ids;
    }

    /**
     * People both members genuinely share.
     *
     * The intersection is the whole idea: someone who has an earned edge with
     * the buyer AND with the seller is a person the buyer can actually ask.
     *
     * @return array<int, array{id: string, username: string, full_name: string, profile_pic_url: string|null}>
     */
    public static function mutuals(int $viewer_id, int $seller_id): array
    {
        if ($viewer_id < 1 || $seller_id < 1 || $viewer_id === $seller_id) {
            return array();
        }

        $shared = array_intersect(self::contacts($viewer_id), self::contacts($seller_id));
        if (! $shared) {
            return array();
        }

        /**
         * Cap the list. Six are shown before "ver os outros", and a buyer who
         * needs more than a few dozen names is not reading them anyway.
         *
         * @param int $limit Maximum mutuals returned.
         */
        $limit = (int) apply_filters('apollo_safety_mutuals_limit', 60);

        $users = get_users(
            array(
                'include' => array_slice(array_values($shared), 0, $limit),
                'fields'  => array('ID', 'user_login', 'display_name'),
                'orderby' => 'display_name',
                'order'   => 'ASC',
            )
        );

        return array_map(
            static function ($u) {
                return array(
                    /* Same four keys the private-API contract uses, so a future
                       Instagram provider is a drop-in and the renderer never
                       branches on where a person came from. */
                    'id'              => (string) $u->ID,
                    'username'        => (string) $u->user_login,
                    'full_name'       => (string) ($u->display_name ?: $u->user_login),
                    'profile_pic_url' => self::avatar((int) $u->ID),
                );
            },
            $users
        );
    }

    /**
     * Local avatar URL, or null so the renderer draws its monogram.
     *
     * get_avatar_url() is asked for a local one only: Gravatar would be an
     * outbound request per row, which is exactly the dependency this class
     * exists to avoid — and it leaks who the buyer is looking at.
     */
    private static function avatar(int $user_id): ?string
    {
        $id = get_user_meta($user_id, 'apollo_avatar_id', true);
        if ($id) {
            $src = wp_get_attachment_image_url((int) $id, 'thumbnail');
            if ($src) {
                return $src;
            }
        }

        return null;
    }

    /**
     * Drop a member's cached edges. Call when a depoimento lands or is removed.
     */
    public static function forget(int $user_id): void
    {
        delete_transient('ap_graph_' . $user_id);
    }

    /**
     * Keep the cache honest: a new or moderated depoimento changes both sides.
     */
    public static function watch(): void
    {
        $bust = static function ($comment_id) {
            $comment = get_comment($comment_id);
            if (! $comment) {
                return;
            }
            self::forget((int) $comment->user_id);
            $author = (int) get_post_field('post_author', (int) $comment->comment_post_ID);
            self::forget($author);
        };

        add_action('wp_insert_comment', $bust, 10, 1);
        add_action('deleted_comment', $bust, 10, 1);
        add_action(
            'transition_comment_status',
            static function ($new, $old, $comment) use ($bust) {
                $bust($comment->comment_ID);
            },
            10,
            3
        );
    }
}
