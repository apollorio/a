<?php

/**
 * fav ranking functionality for Apollo Shortcodes.
 *
 * @package Apollo\Fav
 */

declare(strict_types=1);

namespace Apollo\Fav;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles fav ranking and related AJAX/shortcode functionality.
 */
final class Fav_Ranking
{

    private const TAXONOMIES = array('event_sounds', 'event_category', 'event_type', 'event_tag');

    /**
     * Meta keys — aligned with apollo-registry.json (fav terminology).
     * Legacy keys (_user_interested_events, apollo_favorites, _event_interested_users,
     * _apollo_favorited_users) were renamed for compliance with FORBIDDEN 'interesse' rule.
     *
     * @todo Run a one-time migration to copy old meta to new keys if DB data exists.
     */
    private const USER_META_FAV_EVENTS = '_user_fav_events';
    private const USER_META_FAV_ITEMS  = '_apollo_fav_items';
    private const POST_META_FAV_USERS  = '_event_fav_users';
    private const POST_META_FAV_ALL    = '_apollo_fav_users';

    /** @deprecated Legacy aliases — do not use in new code. */
    private const LEGACY_USER_META_INTEREST  = '_user_interested_events';
    private const LEGACY_USER_META_FAVORITES = 'apollo_favorites';
    private const LEGACY_POST_META_INTEREST  = '_event_interested_users';
    private const LEGACY_POST_META_FAVORITES = '_apollo_favorited_users';

    /**
     * Initializes the class.
     */
    public static function init(): void
    {
        add_action('wp_ajax_apollo_get_ranking_data', array(__CLASS__, 'ajax_ranking'));
        add_action('wp_ajax_apollo_get_user_top_sounds', array(__CLASS__, 'ajax_user_sounds'));
        add_action('wp_ajax_nopriv_apollo_get_user_top_sounds', array(__CLASS__, 'ajax_user_sounds_nopriv'));
        add_shortcode('apollo_top_sounds', array(__CLASS__, 'shortcode_top_sounds'));
    }

    /**
     * Gets the events a user is interested in.
     *
     * @param int $uid User ID.
     * @return array List of event IDs.
     */
    public static function get_user_interested_events(int $uid): array
    {
        if (! $uid) {
            return array();
        }
        $module = self::get_interest_module();
        if ($module) {
            return $module->get_user_interested_events($uid);
        }
        // Read from new keys; fall back to legacy keys for backward compat.
        $i = get_user_meta($uid, self::USER_META_FAV_EVENTS, true)
            ?: get_user_meta($uid, self::LEGACY_USER_META_INTEREST, true);
        $f = get_user_meta($uid, self::USER_META_FAV_ITEMS, true)
            ?: get_user_meta($uid, self::LEGACY_USER_META_FAVORITES, true);
        $i = is_array($i) ? $i : array();
        $f = is_array($f) ? $f : array();
        return array_values(array_unique(array_filter(array_map('absint', array_merge($i, $f)))));
    }

    /**
     * Gets the users interested in an event.
     *
     * @param int $eid Event ID.
     * @return array List of user IDs.
     */
    public static function get_event_interested_users(int $eid): array
    {
        if (! $eid) {
            return array();
        }
        $module = self::get_interest_module();
        if ($module) {
            return $module->get_interested_users($eid);
        }
        // Read from new key; fall back to legacy key for backward compat.
        $i = get_post_meta($eid, self::POST_META_FAV_USERS, true)
            ?: get_post_meta($eid, self::LEGACY_POST_META_INTEREST, true);
        $f = get_post_meta($eid, self::POST_META_FAV_ALL, true)
            ?: get_post_meta($eid, self::LEGACY_POST_META_FAVORITES, true);
        $i = is_array($i) ? $i : array();
        $f = is_array($f) ? $f : array();
        return array_values(array_unique(array_filter(array_map('absint', array_merge($i, $f)))));
    }

    /**
     * Gets the interest module.
     *
     * @psalm-suppress UndefinedFunction
     * @return ?object
     */
    private static function get_interest_module(): ?object
    {
        static $m = null;
        if (null !== $m) {
            return $m ? $m : null;
        }
        if (class_exists('Interest_Module')) {
            global $apollo_events_bootloader;
            if (isset($apollo_events_bootloader) && method_exists($apollo_events_bootloader, 'get_module')) {
                $m = $apollo_events_bootloader->get_module('interest');
            } else {
                $m = false;
            }
        } else {
            $m = false;
        }
        return $m ? $m : null;
    }

    /**
     * Gets all interested events.
     *
     * @return array
     */
    public static function get_all_interested_events(): array
    {
        global $wpdb;
        static $c = null;
        if (null !== $c) {
            return $c;
        }
        // Query both new and legacy meta keys to cover migrated and old data.
        $r = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->prepare(
                "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN (%s,%s,%s,%s)",
                self::POST_META_FAV_USERS,
                self::POST_META_FAV_ALL,
                self::LEGACY_POST_META_INTEREST,
                self::LEGACY_POST_META_FAVORITES
            )
        );
        $c = array_values(array_unique(array_filter(array_map('absint', $r))));
        return $c;
    }

    public static function get_taxonomy_score(string $tax, ?int $uid = null): array
    {
        if (! in_array($tax, self::TAXONOMIES, true)) {
            return array();
        }
        $events = $uid ? self::get_user_interested_events($uid) : self::get_all_interested_events();
        if (empty($events)) {
            return array();
        }
        $scores = array();
        foreach ($events as $eid) {
            $terms = wp_get_object_terms($eid, $tax, array('fields' => 'all'));
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }
            foreach ($terms as $t) {
                $tid = $t->term_id;
                if (! isset($scores[$tid])) {
                    $scores[$tid] = array(
                        'id'    => $tid,
                        'name'  => $t->name,
                        'slug'  => $t->slug,
                        'count' => 0,
                    );
                }
                ++$scores[$tid]['count'];
            }
        }
        usort($scores, fn($a, $b) => $b['count'] <=> $a['count']);
        return $scores;
    }

    public static function get_all_taxonomy_scores(?int $uid = null): array
    {
        $r = array();
        foreach (self::TAXONOMIES as $t) {
            $r[$t] = self::get_taxonomy_score($t, $uid);
        }
        return $r;
    }

    public static function get_user_top_sounds(int $uid, int $limit = 10): array
    {
        return array_slice(self::get_taxonomy_score('event_sounds', $uid), 0, $limit);
    }

    public static function get_global_ranking(string $tax, int $limit = 20): array
    {
        return array_slice(self::get_taxonomy_score($tax), 0, $limit);
    }

    public static function ajax_ranking(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error('Forbidden', 403);
        }
        check_ajax_referer('apollo_analytics_nonce', 'nonce');
        $tax   = sanitize_key($_POST['taxonomy'] ?? 'event_sounds');
        $uid   = isset($_POST['user_id']) ? absint($_POST['user_id']) : null;
        $limit = min(100, absint($_POST['limit'] ?? 50));
        wp_send_json_success(
            array(
                'ranking'  => array_slice(self::get_taxonomy_score($tax, $uid), 0, $limit),
                'taxonomy' => $tax,
            )
        );
    }

    public static function ajax_user_sounds(): void
    {
        check_ajax_referer('apollo_fav_nonce', 'nonce');

        $uid = get_current_user_id();
        if (! $uid) {
            wp_send_json_error('Login required', 401);
        }

        $limit = isset($_GET['limit']) ? absint($_GET['limit']) : 10; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified above.
        wp_send_json_success(array('sounds' => self::get_user_top_sounds($uid, min(20, $limit))));
    }

    public static function ajax_user_sounds_nopriv(): void
    {
        wp_send_json_error('Login required', 401);
    }

    public static function shortcode_top_sounds($atts): string
    {
        $a   = shortcode_atts(
            array(
                'limit'   => '10',
                'user_id' => '0',
            ),
            $atts,
            'apollo_top_sounds'
        );
        $uid = absint($a['user_id']) ?: get_current_user_id();
        if (! $uid) {
            return '<p class="apollo-login-required">' . esc_html__('Faça login para ver seus sons favoritos.', 'apollo-core') . '</p>';
        }
        $sounds = self::get_user_top_sounds($uid, min(20, absint($a['limit'])));
        if (empty($sounds)) {
            return '<p class="apollo-empty">' . esc_html__('Nenhum som encontrado baseado nos seus favoritos.', 'apollo-core') . '</p>';
        }
        $h   = '<div class="apollo-top-sounds"><ol class="apollo-ranking-list">';
        $max = $sounds[0]['count'] ?? 1;
        foreach ($sounds as $i => $s) {
            $n   = $i + 1;
            $pct = $max > 0 ? round(($s['count'] / $max) * 100) : 0;
            $h  .= '<li class="apollo-ranking-item" data-rank="' . $n . '"><span class="rank-pos">#' . $n . '</span><span class="rank-name">' . esc_html($s['name']) . '</span><span class="rank-bar"><span class="rank-fill" style="width:' . $pct . '%"></span></span><span class="rank-count">' . absint($s['count']) . '</span></li>';
        }
        return $h . '</ol></div><style>.apollo-top-sounds{max-width:400px}.apollo-ranking-list{list-style:none;margin:0;padding:0}.apollo-ranking-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #eee}.rank-pos{font-weight:700;color:#3498db;min-width:30px}.rank-name{flex:1;font-weight:500}.rank-bar{flex:1;height:8px;background:#eee;border-radius:4px;overflow:hidden}.rank-fill{height:100%;background:linear-gradient(90deg,#9b59b6,#3498db);border-radius:4px}.rank-count{font-size:12px;color:#7f8c8d;min-width:30px;text-align:right}</style>';
    }
}
add_action('init', array('Apollo\Fav\Fav_Ranking', 'init'));
