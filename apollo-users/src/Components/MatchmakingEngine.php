<?php
/**
 * Apollo Matchmaking Engine — Offline Pre-Scheduled Scoring.
 *
 * Computes asymmetric affinity scores (A→B ≠ B→A) across 10 weighted signal
 * dimensions. Scores are pre-computed via WP-Cron and stored in the
 * `apollo_matchmaking_scores` table for instant REST reads.
 *
 * @package Apollo\Users\Components
 * @since   6.5.0
 */

declare(strict_types=1);

namespace Apollo\Users\Components;

use Apollo\Core\Config\ApolloTable;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class MatchmakingEngine {

    // ─── WEIGHT CONFIG ──────────────────────────────────────────────
    // Sum = 100. Each calculator returns 0..1, multiplied by weight → sub-score.
    private const WEIGHTS = array(
        'ratings'  => 20, // TIER 1: sexy/legal/confiável votes toward target
        'wow'      => 15, // TIER 1: wow reactions on target's posts
        'chat'     => 15, // TIER 1: messages sent to target
        'comments' => 10, // TIER 1: replies on target's activity
        'views'    =>  5, // TIER 1: profile views of target
        'sounds'   => 15, // TIER 2: sound preference similarity (symmetric)
        'favs'     => 10, // TIER 2: favorite overlap — DJs + locs + events (symmetric)
        'rsvp'     =>  5, // TIER 2: event RSVP overlap (symmetric)
        'groups'   =>  3, // TIER 3: shared group memberships (symmetric)
        'passive'  =>  2, // TIER 4: location, badge proximity (mixed)
    );

    // ─── SATURATION THRESHOLDS ──────────────────────────────────────
    // Directional counts are normalized via min(1, count/SAT).
    private const SAT_WOW      = 30;
    private const SAT_CHAT     = 100;
    private const SAT_COMMENTS = 20;
    private const SAT_VIEWS    = 50;

    // Time decay half-life in days for frequency-based signals.
    private const DECAY_HALF_LIFE = 90;

    // Active-user window (days). Only users with activity in this window are computed.
    private const ACTIVE_WINDOW_DAYS = 90;

    // Batch size for cron processing.
    private const BATCH_SIZE = 50;

    // Dirty-pair incremental batch limit.
    private const DIRTY_BATCH_LIMIT = 200;

    // Cron lock TTL in seconds.
    private const LOCK_TTL = 600;

    // ─── LIFECYCLE ──────────────────────────────────────────────────

    /**
     * Register WP hooks for cron + incremental signal listeners.
     */
    public function __construct() {
        // Cron entry points.
        add_action( 'apollo_matchmaking_recompute', array( __CLASS__, 'cron_recompute' ) );
        add_action( 'apollo_matchmaking_incremental', array( __CLASS__, 'cron_incremental' ) );

        // Real-time signal listeners → enqueue dirty pairs.
        add_action( 'apollo/matchmaking/signal_updated', array( __CLASS__, 'on_signal_rating' ), 10, 3 );
        add_action( 'apollo/wow/added', array( __CLASS__, 'on_signal_wow' ), 10, 4 );
        add_action( 'apollo/chat/message_sent', array( __CLASS__, 'on_signal_chat' ), 10, 3 );
        add_action( 'apollo/fav/added', array( __CLASS__, 'on_signal_fav' ), 10, 4 );
        add_action( 'apollo/event/rsvp', array( __CLASS__, 'on_signal_rsvp' ), 10, 3 );
        add_action( 'apollo/groups/user_joined', array( __CLASS__, 'on_signal_group' ), 10, 2 );
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CALCULATORS — one per signal dimension
    //  Each returns a float in [0, 1].
    // ═══════════════════════════════════════════════════════════════════

    /**
     * TIER 1 — Ratings: how many stars $from gave $to across all categories.
     */
    private static function calc_ratings( int $from, int $to ): float {
        global $wpdb;
        $table = $wpdb->prefix . 'apollo_user_ratings';
        $sum   = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(score), 0) FROM {$table} WHERE voter_id = %d AND target_id = %d",
                $from,
                $to
            )
        );
        // Max possible = MAX_SCORE (3) × 3 categories = 9.
        $max = 9.0;
        return min( 1.0, $sum / max( $max, 1.0 ) );
    }

    /**
     * TIER 1 — WOW reactions $from placed on $to's posts (with time decay).
     */
    private static function calc_wow( int $from, int $to ): float {
        global $wpdb;
        $wow_table = $wpdb->prefix . 'apollo_wows';
        $rows      = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT w.created_at
                 FROM {$wow_table} w
                 INNER JOIN {$wpdb->posts} p ON w.object_id = p.ID AND w.object_type = 'post'
                 WHERE w.user_id = %d AND p.post_author = %d",
                $from,
                $to
            )
        );

        if ( empty( $rows ) ) {
            return 0.0;
        }

        $weighted = 0.0;
        foreach ( $rows as $row ) {
            $weighted += self::time_decay( $row->created_at );
        }
        return min( 1.0, $weighted / self::SAT_WOW );
    }

    /**
     * TIER 1 — Chat messages $from sent in threads where $to participates.
     */
    private static function calc_chat( int $from, int $to ): float {
        global $wpdb;
        $msg_table = $wpdb->prefix . 'apollo_chat_messages';
        $par_table = $wpdb->prefix . 'apollo_chat_participants';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.created_at
                 FROM {$msg_table} m
                 INNER JOIN {$par_table} p1 ON p1.thread_id = m.thread_id AND p1.user_id = %d
                 INNER JOIN {$par_table} p2 ON p2.thread_id = m.thread_id AND p2.user_id = %d
                 WHERE m.sender_id = %d AND m.is_deleted = 0",
                $from,
                $to,
                $from
            )
        );

        if ( empty( $rows ) ) {
            return 0.0;
        }

        $weighted = 0.0;
        foreach ( $rows as $row ) {
            $weighted += self::time_decay( $row->created_at );
        }
        return min( 1.0, $weighted / self::SAT_CHAT );
    }

    /**
     * TIER 1 — Activity replies $from made on $to's activity posts.
     */
    private static function calc_comments( int $from, int $to ): float {
        global $wpdb;
        $activity_table = $wpdb->prefix . 'apollo_activity';

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$activity_table} r
                 INNER JOIN {$activity_table} parent ON r.item_id = parent.id
                 WHERE r.user_id = %d AND r.type = 'reply' AND parent.user_id = %d",
                $from,
                $to
            )
        );

        return min( 1.0, (float) $count / self::SAT_COMMENTS );
    }

    /**
     * TIER 1 — Profile views: how many times $from viewed $to's profile.
     */
    private static function calc_views( int $from, int $to ): float {
        global $wpdb;
        $views_table = $wpdb->prefix . APOLLO_USERS_TABLE_PROFILE_VIEWS;

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$views_table} WHERE viewer_user_id = %d AND profile_user_id = %d",
                $from,
                $to
            )
        );

        return min( 1.0, (float) $count / self::SAT_VIEWS );
    }

    /**
     * TIER 2 — Sound preferences Jaccard similarity (symmetric).
     */
    private static function calc_sounds( int $from, int $to ): float {
        $a = get_user_meta( $from, '_apollo_sound_preferences', true );
        $b = get_user_meta( $to, '_apollo_sound_preferences', true );

        $a = is_array( $a ) ? array_map( 'intval', $a ) : array();
        $b = is_array( $b ) ? array_map( 'intval', $b ) : array();

        return self::jaccard( $a, $b );
    }

    /**
     * TIER 2 — Favorites overlap across DJs, locs, events (symmetric, averaged).
     */
    private static function calc_favs( int $from, int $to ): float {
        global $wpdb;
        $favs_table = $wpdb->prefix . 'apollo_favs';

        $types    = array( 'dj', 'local', 'event' );
        $total_j  = 0.0;
        $counted  = 0;

        foreach ( $types as $type ) {
            $a_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT post_id FROM {$favs_table} WHERE user_id = %d AND post_type = %s",
                    $from,
                    $type
                )
            );
            $b_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT post_id FROM {$favs_table} WHERE user_id = %d AND post_type = %s",
                    $to,
                    $type
                )
            );

            $a_ids = array_map( 'intval', $a_ids );
            $b_ids = array_map( 'intval', $b_ids );

            // Only count this type if at least one user has favs in it.
            if ( ! empty( $a_ids ) || ! empty( $b_ids ) ) {
                $total_j += self::jaccard( $a_ids, $b_ids );
                ++$counted;
            }
        }

        return $counted > 0 ? $total_j / $counted : 0.0;
    }

    /**
     * TIER 2 — Event RSVP overlap (going / maybe) — symmetric.
     */
    private static function calc_rsvp( int $from, int $to ): float {
        global $wpdb;
        $rsvp_table = $wpdb->prefix . 'apollo_event_rsvp';

        $a_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT event_id FROM {$rsvp_table} WHERE user_id = %d AND status IN ('going','maybe')",
                $from
            )
        );
        $b_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT event_id FROM {$rsvp_table} WHERE user_id = %d AND status IN ('going','maybe')",
                $to
            )
        );

        return self::jaccard( array_map( 'intval', $a_ids ), array_map( 'intval', $b_ids ) );
    }

    /**
     * TIER 3 — Shared group memberships (symmetric).
     */
    private static function calc_groups( int $from, int $to ): float {
        global $wpdb;
        $gm_table = $wpdb->prefix . 'apollo_group_members';

        $a_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT group_id FROM {$gm_table} WHERE user_id = %d",
                $from
            )
        );
        $b_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT group_id FROM {$gm_table} WHERE user_id = %d",
                $to
            )
        );

        return self::jaccard( array_map( 'intval', $a_ids ), array_map( 'intval', $b_ids ) );
    }

    /**
     * TIER 4 — Passive signals: location match + badge proximity.
     */
    private static function calc_passive( int $from, int $to ): float {
        // Location match (50% of passive score).
        $loc_a = (string) get_user_meta( $from, 'user_location', true );
        $loc_b = (string) get_user_meta( $to, 'user_location', true );
        $loc_score = ( $loc_a !== '' && $loc_a === $loc_b ) ? 1.0 : 0.0;

        // Badge/rank proximity (50% of passive score).
        $rank_a = (int) get_user_meta( $from, '_apollo_points_total', true );
        $rank_b = (int) get_user_meta( $to, '_apollo_points_total', true );
        $max_rank = max( $rank_a, $rank_b, 1 );
        $rank_score = 1.0 - ( abs( $rank_a - $rank_b ) / $max_rank );

        return ( $loc_score * 0.5 ) + ( max( 0.0, $rank_score ) * 0.5 );
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ORCHESTRATOR — compute & persist
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Compute the asymmetric score for a single directional pair and UPSERT.
     *
     * @param int $from Viewer user ID.
     * @param int $to   Target user ID.
     * @return float     The computed score_total (0–100).
     */
    public static function compute_pair( int $from, int $to ): float {
        $breakdown = array();
        foreach ( self::WEIGHTS as $signal => $weight ) {
            $method = 'calc_' . $signal;
            $norm   = call_user_func( array( __CLASS__, $method ), $from, $to );
            $breakdown[ $signal ] = round( (float) $norm * $weight, 2 );
        }

        $total = array_sum( $breakdown );
        $total = round( min( 100.0, max( 0.0, $total ) ), 2 );

        global $wpdb;

        // ── FAV-USER 3× BOOST ────────────────────────────────────────
        // If viewer ($from) has favorited the target ($to) as a user,
        // apply a 3× multiplier to the final score (cap at 300).
        $favs_table = $wpdb->prefix . 'apollo_favs';
        $has_fav_user = (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$favs_table} WHERE user_id = %d AND post_id = %d AND post_type = 'user' LIMIT 1",
                $from,
                $to
            )
        );
        if ( $has_fav_user ) {
            $total = round( min( 300.0, $total * 3.0 ), 2 );
        }

        $table = ApolloTable::full( ApolloTable::MATCHMAKING_SCORES );
        $now   = current_time( 'mysql', true );

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table}
                    (user_from, user_to, score_total,
                     score_ratings, score_wow, score_chat, score_comments, score_views,
                     score_sounds, score_favs, score_rsvp, score_groups, score_passive,
                     computed_at)
                 VALUES (%d, %d, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %s)
                 ON DUPLICATE KEY UPDATE
                    score_total    = VALUES(score_total),
                    score_ratings  = VALUES(score_ratings),
                    score_wow      = VALUES(score_wow),
                    score_chat     = VALUES(score_chat),
                    score_comments = VALUES(score_comments),
                    score_views    = VALUES(score_views),
                    score_sounds   = VALUES(score_sounds),
                    score_favs     = VALUES(score_favs),
                    score_rsvp     = VALUES(score_rsvp),
                    score_groups   = VALUES(score_groups),
                    score_passive  = VALUES(score_passive),
                    computed_at    = VALUES(computed_at)",
                $from,
                $to,
                $total,
                $breakdown['ratings'],
                $breakdown['wow'],
                $breakdown['chat'],
                $breakdown['comments'],
                $breakdown['views'],
                $breakdown['sounds'],
                $breakdown['favs'],
                $breakdown['rsvp'],
                $breakdown['groups'],
                $breakdown['passive'],
                $now
            )
        );

        /**
         * Fires after a matchmaking score is computed/updated.
         *
         * @param int   $from      Viewer user ID.
         * @param int   $to        Target user ID.
         * @param float $total     Total score 0–100.
         * @param array $breakdown Per-signal weighted sub-scores.
         */
        do_action( 'apollo/matchmaking/score_updated', $from, $to, $total, $breakdown );

        return $total;
    }

    /**
     * Compute scores FROM one user toward a set of targets.
     *
     * @param int   $user_id    The viewer.
     * @param int[] $target_ids Array of target user IDs.
     */
    public static function compute_for_user( int $user_id, array $target_ids ): void {
        foreach ( $target_ids as $tid ) {
            $tid = (int) $tid;
            if ( $tid !== $user_id && $tid > 0 ) {
                self::compute_pair( $user_id, $tid );
            }
        }
    }

    /**
     * Full recompute for all active users (cron entry point).
     * Follows the apollo-email Queue.php lock/batch/log pattern.
     */
    public static function cron_recompute(): void {
        // ── Concurrency lock ────────────────────────────────────────
        if ( get_transient( 'apollo_matchmaking_lock' ) ) {
            return;
        }
        set_transient( 'apollo_matchmaking_lock', true, self::LOCK_TTL );

        try {
            $user_ids = self::get_active_user_ids();
            $count    = count( $user_ids );

            if ( $count < 2 ) {
                return;
            }

            // Process in chunks to limit memory.
            $chunks = array_chunk( $user_ids, self::BATCH_SIZE );

            foreach ( $chunks as $chunk ) {
                foreach ( $chunk as $from ) {
                    foreach ( $user_ids as $to ) {
                        if ( $from !== $to ) {
                            self::compute_pair( (int) $from, (int) $to );
                        }
                    }
                }
            }

            /**
             * Fires after the full matchmaking batch completes.
             *
             * @param int $pair_count Approximate number of pairs computed.
             */
            do_action( 'apollo/matchmaking/batch_completed', $count * ( $count - 1 ) );

        } finally {
            delete_transient( 'apollo_matchmaking_lock' );
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  INCREMENTAL DIRTY-PAIR PROCESSOR
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Enqueue a pair for incremental recompute on next 5-min cron tick.
     *
     * @param int $from Viewer.
     * @param int $to   Target.
     */
    public static function enqueue_dirty( int $from, int $to ): void {
        if ( $from === $to || $from <= 0 || $to <= 0 ) {
            return;
        }
        $dirty = get_option( '_apollo_matchmaking_dirty_pairs', array() );
        if ( ! is_array( $dirty ) ) {
            $dirty = array();
        }
        $key = $from . ':' . $to;
        $dirty[ $key ] = array( $from, $to );

        // Cap at 1000 to prevent option bloat (full cron handles the rest).
        if ( count( $dirty ) > 1000 ) {
            $dirty = array_slice( $dirty, -1000, null, true );
        }

        update_option( '_apollo_matchmaking_dirty_pairs', $dirty, false );
    }

    /**
     * Process dirty pairs (5-min cron).
     */
    public static function cron_incremental(): void {
        $dirty = get_option( '_apollo_matchmaking_dirty_pairs', array() );
        if ( empty( $dirty ) || ! is_array( $dirty ) ) {
            return;
        }

        $batch = array_slice( $dirty, 0, self::DIRTY_BATCH_LIMIT, true );
        foreach ( $batch as $key => $pair ) {
            self::compute_pair( (int) $pair[0], (int) $pair[1] );
            unset( $dirty[ $key ] );
        }

        update_option( '_apollo_matchmaking_dirty_pairs', $dirty, false );
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SIGNAL LISTENERS → enqueue dirty pairs
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Rating submitted: voter→target dirty.
     *
     * @param int    $voter_id  Voter.
     * @param int    $target_id Target.
     * @param string $signal    Signal name.
     */
    public static function on_signal_rating( int $voter_id, int $target_id, string $signal ): void {
        self::enqueue_dirty( $voter_id, $target_id );
    }

    /**
     * WOW reaction added: reactor→post_author dirty.
     *
     * @param int    $user_id       Reactor.
     * @param string $object_type   Object type.
     * @param int    $object_id     Post ID.
     * @param string $reaction_type Reaction type (wow, fire, etc.).
     */
    public static function on_signal_wow( int $user_id, string $object_type, int $object_id, string $reaction_type ): void {
        if ( 'post' !== $object_type ) {
            return;
        }
        $post = get_post( $object_id );
        if ( $post && (int) $post->post_author !== $user_id ) {
            self::enqueue_dirty( $user_id, (int) $post->post_author );
        }
    }

    /**
     * Chat message sent: sender→each participant dirty.
     *
     * @param int $msg_id    Message ID.
     * @param int $thread_id Thread ID.
     * @param int $sender_id Sender.
     */
    public static function on_signal_chat( int $msg_id, int $thread_id, int $sender_id ): void {
        global $wpdb;
        $par_table = $wpdb->prefix . 'apollo_chat_participants';
        $user_ids  = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT user_id FROM {$par_table} WHERE thread_id = %d AND user_id != %d",
                $thread_id,
                $sender_id
            )
        );
        foreach ( $user_ids as $uid ) {
            self::enqueue_dirty( $sender_id, (int) $uid );
        }
    }

    /**
     * Favorite added: not a direct pair but marks overlap change.
     * We re-score the user against recent interlocutors.
     *
     * @param int    $fav_id    Fav ID.
     * @param int    $user_id   User who favorited.
     * @param int    $post_id   Post ID.
     * @param string $post_type Post type.
     */
    public static function on_signal_fav( int $fav_id, int $user_id, int $post_id, string $post_type ): void {
        // User fav — direct pair boost: viewer → target user.
        if ( $post_type === 'user' ) {
            self::enqueue_dirty( $user_id, $post_id );
            return;
        }

        // Find other users who also favorited this same post → dirty pair.
        global $wpdb;
        $favs_table = $wpdb->prefix . 'apollo_favs';
        $others     = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT user_id FROM {$favs_table} WHERE post_id = %d AND user_id != %d LIMIT 50",
                $post_id,
                $user_id
            )
        );
        foreach ( $others as $other_id ) {
            self::enqueue_dirty( $user_id, (int) $other_id );
            self::enqueue_dirty( (int) $other_id, $user_id );
        }
    }

    /**
     * RSVP received: mark dirty with other attendees of the same event.
     *
     * @param int    $event_id Event ID.
     * @param int    $user_id  User ID.
     * @param string $status   RSVP status.
     */
    public static function on_signal_rsvp( int $event_id, int $user_id, string $status ): void {
        if ( ! in_array( $status, array( 'going', 'maybe' ), true ) ) {
            return;
        }
        global $wpdb;
        $rsvp_table = $wpdb->prefix . 'apollo_event_rsvp';
        $others     = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT user_id FROM {$rsvp_table}
                 WHERE event_id = %d AND user_id != %d AND status IN ('going','maybe')
                 LIMIT 50",
                $event_id,
                $user_id
            )
        );
        foreach ( $others as $other_id ) {
            self::enqueue_dirty( $user_id, (int) $other_id );
            self::enqueue_dirty( (int) $other_id, $user_id );
        }
    }

    /**
     * User joined group: mark dirty with other group members.
     *
     * @param int $group_id Group ID.
     * @param int $user_id  User ID.
     */
    public static function on_signal_group( int $group_id, int $user_id ): void {
        global $wpdb;
        $gm_table = $wpdb->prefix . 'apollo_group_members';
        $others   = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT user_id FROM {$gm_table} WHERE group_id = %d AND user_id != %d LIMIT 100",
                $group_id,
                $user_id
            )
        );
        foreach ( $others as $other_id ) {
            self::enqueue_dirty( $user_id, (int) $other_id );
            self::enqueue_dirty( (int) $other_id, $user_id );
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  READ API — static, for controllers
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Get the score A→B.
     *
     * @param int $from Viewer.
     * @param int $to   Target.
     * @return float|null Score or null if not yet computed.
     */
    public static function get_score( int $from, int $to ): ?float {
        global $wpdb;
        $table = ApolloTable::full( ApolloTable::MATCHMAKING_SCORES );
        $val   = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT score_total FROM {$table} WHERE user_from = %d AND user_to = %d",
                $from,
                $to
            )
        );
        return null !== $val ? (float) $val : null;
    }

    /**
     * Top matches for a user, ordered by score DESC.
     *
     * @param int $user_id Viewer.
     * @param int $limit   Max results.
     * @return array Array of {user_id, score_total, computed_at}.
     */
    public static function get_top_matches( int $user_id, int $limit = 20 ): array {
        global $wpdb;
        $table = ApolloTable::full( ApolloTable::MATCHMAKING_SCORES );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_to AS user_id, score_total, computed_at
                 FROM {$table}
                 WHERE user_from = %d AND score_total > 0
                 ORDER BY score_total DESC
                 LIMIT %d",
                $user_id,
                $limit
            ),
            ARRAY_A
        );

        if ( empty( $rows ) ) {
            return array();
        }

        // Enrich with basic profile data.
        foreach ( $rows as &$row ) {
            $uid  = (int) $row['user_id'];
            $user = get_userdata( $uid );
            if ( $user ) {
                $row['username']     = $user->user_login;
                $row['display_name'] = $user->display_name;
                $row['avatar']       = get_avatar_url( $uid, array( 'size' => 96 ) );
            }
            $row['score_total'] = (float) $row['score_total'];
        }
        unset( $row );

        return $rows;
    }

    /**
     * Full score breakdown for a directional pair.
     *
     * @param int $from Viewer.
     * @param int $to   Target.
     * @return array Associative array with all sub-scores + computed_at.
     */
    public static function get_score_breakdown( int $from, int $to ): array {
        global $wpdb;
        $table = ApolloTable::full( ApolloTable::MATCHMAKING_SCORES );

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT score_total, score_ratings, score_wow, score_chat, score_comments,
                        score_views, score_sounds, score_favs, score_rsvp, score_groups,
                        score_passive, computed_at
                 FROM {$table}
                 WHERE user_from = %d AND user_to = %d",
                $from,
                $to
            ),
            ARRAY_A
        );

        if ( ! $row ) {
            return array();
        }

        // Cast all score columns to float.
        foreach ( $row as $key => &$val ) {
            if ( 'computed_at' !== $key ) {
                $val = (float) $val;
            }
        }
        unset( $val );

        return $row;
    }

    /**
     * Mutual affinity between two users (both directions).
     *
     * @param int $a User A.
     * @param int $b User B.
     * @return array {me_to_them: float|null, them_to_me: float|null, average: float|null}
     */
    public static function get_mutual_affinity( int $a, int $b ): array {
        $a_to_b = self::get_score( $a, $b );
        $b_to_a = self::get_score( $b, $a );

        $avg = null;
        if ( null !== $a_to_b && null !== $b_to_a ) {
            $avg = round( ( $a_to_b + $b_to_a ) / 2, 2 );
        }

        return array(
            'me_to_them'  => $a_to_b,
            'them_to_me'  => $b_to_a,
            'average'     => $avg,
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Jaccard similarity coefficient: |A∩B| / |A∪B|.
     *
     * @param int[] $set_a Set A.
     * @param int[] $set_b Set B.
     * @return float 0..1
     */
    private static function jaccard( array $set_a, array $set_b ): float {
        if ( empty( $set_a ) && empty( $set_b ) ) {
            return 0.0;
        }

        $intersect = count( array_intersect( $set_a, $set_b ) );
        $union     = count( array_unique( array_merge( $set_a, $set_b ) ) );

        return $union > 0 ? (float) $intersect / $union : 0.0;
    }

    /**
     * Time decay factor: recent activity counts more.
     * Returns a weight in (0, 1] based on age of the event.
     *
     * Formula: 2^(-days_ago / half_life)
     *
     * @param string $datetime MySQL datetime string (UTC).
     * @return float Decay weight.
     */
    private static function time_decay( string $datetime ): float {
        $timestamp = strtotime( $datetime );
        if ( ! $timestamp ) {
            return 0.0;
        }

        $days_ago = ( time() - $timestamp ) / 86400;
        if ( $days_ago < 0 ) {
            $days_ago = 0;
        }

        return pow( 2, -$days_ago / self::DECAY_HALF_LIFE );
    }

    /**
     * Get IDs of users active in the last ACTIVE_WINDOW_DAYS.
     *
     * @return int[]
     */
    private static function get_active_user_ids(): array {
        global $wpdb;

        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( self::ACTIVE_WINDOW_DAYS * 86400 ) );

        // Users who logged in recently OR have activity.
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT u.ID
                 FROM {$wpdb->users} u
                 LEFT JOIN {$wpdb->usermeta} m ON u.ID = m.user_id AND m.meta_key = 'last_login'
                 LEFT JOIN {$wpdb->prefix}apollo_activity a ON u.ID = a.user_id AND a.created_at >= %s
                 WHERE u.user_status = 0
                   AND (m.meta_value >= %s OR a.user_id IS NOT NULL)
                 ORDER BY u.ID ASC",
                $cutoff,
                $cutoff
            )
        );

        return array_map( 'intval', $ids );
    }
}
