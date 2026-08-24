<?php

/**
 * Apollo Membership — Canonical Hook Bridges
 *
 * Translates legacy procedural actions (apollo_award_user_points, etc.)
 * into the namespaced Apollo hook convention (apollo/membership/*).
 *
 * Other plugins MUST listen on `apollo/membership/*` hooks, never on the
 * legacy procedural hooks. This file is the single bridge layer.
 *
 * @package Apollo\Membership
 * @since   6.5.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// POINTS — award / deduct / reset / update
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Bridge: points awarded to user.
 *
 * @param int    $user_id  User ID.
 * @param int    $points   Points amount.
 * @param string $trigger  Trigger slug.
 * @param int    $admin_id Admin ID (0 = automatic).
 */
add_action(
    'apollo_award_user_points',
    static function (int $user_id, int $points, string $trigger, int $admin_id) {
        /**
         * Fires when points are awarded to a user.
         *
         * @param int    $user_id  User ID.
         * @param int    $points   Points amount.
         * @param string $trigger  Trigger slug.
         * @param int    $admin_id Admin ID (0 = automatic).
         */
        do_action('apollo/membership/points_awarded', $user_id, $points, $trigger, $admin_id);
    },
    10,
    4
);

/**
 * Bridge: points deducted from user.
 *
 * @param int    $user_id  User ID.
 * @param int    $points   Points amount.
 * @param string $trigger  Trigger slug.
 * @param int    $admin_id Admin ID.
 */
add_action(
    'apollo_deduct_user_points',
    static function (int $user_id, int $points, string $trigger, int $admin_id) {
        /**
         * Fires when points are deducted from a user.
         *
         * @param int    $user_id  User ID.
         * @param int    $points   Points amount.
         * @param string $trigger  Trigger slug.
         * @param int    $admin_id Admin ID.
         */
        do_action('apollo/membership/points_deducted', $user_id, $points, $trigger, $admin_id);
    },
    10,
    4
);

/**
 * Bridge: user points reset to zero.
 *
 * @param int $user_id  User ID.
 * @param int $admin_id Admin who triggered the reset.
 */
add_action(
    'apollo_user_points_reset',
    static function (int $user_id, int $admin_id) {
        /**
         * Fires when a user's points are reset to zero.
         *
         * @param int $user_id  User ID.
         * @param int $admin_id Admin ID.
         */
        do_action('apollo/membership/points_reset', $user_id, $admin_id);
    },
    10,
    2
);

/**
 * Bridge: user total points recalculated / updated.
 *
 * @param int $user_id   User ID.
 * @param int $new_total New total after recalculation.
 * @param int $old_total Previous total.
 * @param int $admin_id  Admin ID (0 = automatic).
 */
add_action(
    'apollo_update_users_points',
    static function (int $user_id, int $new_total, int $old_total, int $admin_id) {
        /**
         * Fires when a user's points total is updated.
         *
         * @param int $user_id   User ID.
         * @param int $new_total New total points.
         * @param int $old_total Previous total points.
         * @param int $admin_id  Admin ID.
         */
        do_action('apollo/membership/points_updated', $user_id, $new_total, $old_total, $admin_id);
    },
    10,
    4
);

// ═══════════════════════════════════════════════════════════════════════════
// BADGES — membership badge assigned
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Bridge: membership badge assigned to user.
 *
 * @param int    $user_id    User ID.
 * @param string $badge_type New badge slug (e.g. 'verificado', 'premium').
 * @param string $old_badge  Previous badge slug.
 * @param int    $admin_id   Admin who assigned.
 */
add_action(
    'apollo_membership_badge_assigned',
    static function (int $user_id, string $badge_type, string $old_badge, int $admin_id) {
        /**
         * Fires when a membership badge is assigned to a user.
         *
         * @param int    $user_id    User ID.
         * @param string $badge_type New badge slug.
         * @param string $old_badge  Previous badge slug.
         * @param int    $admin_id   Admin ID.
         */
        do_action('apollo/membership/badge_assigned', $user_id, $badge_type, $old_badge, $admin_id);
    },
    10,
    4
);

// ═══════════════════════════════════════════════════════════════════════════
// ACHIEVEMENTS — achievement awarded
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Bridge: achievement awarded to user.
 *
 * @param int    $user_id        User ID.
 * @param int    $achievement_id Achievement post ID.
 * @param string $trigger        Trigger slug.
 * @param int    $site_id        Site ID (multisite).
 * @param array  $args           Additional context.
 */
add_action(
    'apollo_award_achievement',
    static function (int $user_id, int $achievement_id, string $trigger, int $site_id, array $args) {
        /**
         * Fires when an achievement is awarded to a user.
         *
         * @param int    $user_id        User ID.
         * @param int    $achievement_id Achievement post ID.
         * @param string $trigger        Trigger slug.
         * @param int    $site_id        Site ID.
         * @param array  $args           Additional context.
         */
        do_action('apollo/membership/achievement_awarded', $user_id, $achievement_id, $trigger, $site_id, $args);
    },
    10,
    5
);

// ═══════════════════════════════════════════════════════════════════════════
// RANKS — rank awarded
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Bridge: rank awarded to user.
 *
 * @param int    $user_id  User ID.
 * @param int    $rank_id  Rank post ID.
 * @param string $trigger  Trigger slug.
 * @param int    $admin_id Admin ID (0 = automatic).
 */
add_action(
    'apollo_award_rank',
    static function (int $user_id, int $rank_id, string $trigger, int $admin_id) {
        /**
         * Fires when a rank is awarded to a user.
         *
         * @param int    $user_id  User ID.
         * @param int    $rank_id  Rank post ID.
         * @param string $trigger  Trigger slug.
         * @param int    $admin_id Admin ID.
         */
        do_action('apollo/membership/rank_awarded', $user_id, $rank_id, $trigger, $admin_id);
    },
    10,
    4
);

// ═══════════════════════════════════════════════════════════════════════════
// CREDITS — credit added / logged
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Bridge: credit entry added to the points log.
 *
 * @param int    $user_id   User ID.
 * @param string $type      Credit type ('Award' | 'Deduct' | 'points').
 * @param int    $amount    Credit amount.
 * @param int    $credit_id Credit type/post ID.
 * @param string $trigger   Trigger slug.
 */
add_action(
    'apollo_membership_credit_added',
    static function (int $user_id, string $type, int $amount, int $credit_id, string $trigger) {
        /**
         * Fires when a credit entry is inserted in the points log.
         *
         * @param int    $user_id   User ID.
         * @param string $type      Credit direction ('Award' | 'Deduct').
         * @param int    $amount    Credit amount.
         * @param int    $credit_id Credit type ID.
         * @param string $trigger   Trigger slug.
         */
        do_action('apollo/membership/credit_added', $user_id, $type, $amount, $credit_id, $trigger);
    },
    10,
    5
);
