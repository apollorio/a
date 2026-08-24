<?php
/**
 * HookCollector — listens to 40+ hooks across the Apollo ecosystem
 * and records events into the statistics tables.
 *
 * @package Apollo\Statistics\Collectors
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Collectors;

if (! defined('ABSPATH')) {
    exit;
}

final class HookCollector {

    public function init(): void {
        // ── Users ────────────────────────────────────────────
        add_action('wp_login', array($this, 'on_login'), 10, 2);
        add_action('user_register', array($this, 'on_registration'), 10, 1);
        add_action('apollo/users/profile_visited', array($this, 'on_profile_visit'), 10, 2);

        // ── Content views (legacy compat) ────────────────────
        add_action('template_redirect', array($this, 'on_page_view'));

        // ── Events ───────────────────────────────────────────
        add_action('apollo/event/published', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/event/created', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/event/updated', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/event/deleted', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/event/rsvp_updated', array($this, 'on_event_rsvp'), 10, 3);
        add_action('apollo/event/gone', array($this, 'on_event_rsvp'), 10, 3);

        // ── DJs ──────────────────────────────────────────────
        add_action('apollo/dj/published', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/dj/created', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/dj/updated', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/dj/deleted', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/dj/followed', array($this, 'on_user_action'), 10, 2);

        // ── Loc ──────────────────────────────────────────────
        add_action('apollo/loc/published', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/loc/created', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/loc/updated', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/loc/deleted', array($this, 'on_content_action'), 10, 2);
        add_action('apollo/loc/geocoded', array($this, 'on_content_action'), 10, 2);

        // ── Social ───────────────────────────────────────────
        add_action('apollo/social/activity_created', array($this, 'on_social_activity'), 10, 2);
        add_action('apollo/social/reply_created', array($this, 'on_social_activity'), 10, 3);
        add_action('apollo/social/follow', array($this, 'on_user_action'), 10, 2);

        // ── Chat ─────────────────────────────────────────────
        add_action('apollo/chat/message_sent', array($this, 'on_chat_message'), 10, 2);
        add_action('apollo/chat/thread_created', array($this, 'on_chat_thread'), 10, 1);

        // ── Groups ───────────────────────────────────────────
        add_action('apollo/groups/user_joined', array($this, 'on_group_join'), 10, 2);

        // ── Wow ──────────────────────────────────────────────
        add_action('apollo/wow/added', array($this, 'on_wow'), 10, 3);
        add_action('apollo/wow/reaction_received', array($this, 'on_wow'), 10, 3);

        // ── Fav ──────────────────────────────────────────────
        add_action('apollo/fav/added', array($this, 'on_fav'), 10, 2);
        add_action('apollo/fav/removed', array($this, 'on_fav_removed'), 10, 2);

        // ── Membership / Gamification ────────────────────────
        add_action('apollo/membership/points_awarded', array($this, 'on_points_change'), 10, 3);
        add_action('apollo/membership/points_deducted', array($this, 'on_points_change'), 10, 3);
        add_action('apollo/membership/badge_assigned', array($this, 'on_achievement'), 10, 2);
        add_action('apollo/membership/achievement_awarded', array($this, 'on_achievement'), 10, 2);
        add_action('apollo/membership/rank_awarded', array($this, 'on_achievement'), 10, 2);

        // ── Email ────────────────────────────────────────────
        add_action('apollo/email/sent', array($this, 'on_email'), 10, 1);
        add_action('apollo/email/failed', array($this, 'on_email'), 10, 1);
        add_action('apollo/email/opened', array($this, 'on_email'), 10, 1);

        // ── CoAuthor ─────────────────────────────────────────
        add_action('apollo/coauthor/invited', array($this, 'on_coauthor'), 10, 2);

        // ── Notif ────────────────────────────────────────────
        add_action('apollo/notif/push_subscribed', array($this, 'on_push_subscribe'), 10, 1);

        // ── Journal ──────────────────────────────────────────
        add_action('apollo/journal/nrep_assigned', array($this, 'on_content_action'), 10, 2);

        // ── Gestor ───────────────────────────────────────────
        add_action('apollo/gestor/task_created', array($this, 'on_gestor_task'), 10, 2);
        add_action('apollo/gestor/task_completed', array($this, 'on_gestor_task'), 10, 2);
        add_action('apollo/gestor/payment_recorded', array($this, 'on_gestor_payment'), 10, 2);
        add_action('apollo/gestor/milestone_reached', array($this, 'on_gestor_task'), 10, 2);
    }

    /* ══════════════════════════════════════════════════════════
       CALLBACK IMPLEMENTATIONS
       ══════════════════════════════════════════════════════════ */

    /**
     * Login tracking.
     */
    public function on_login(string $user_login, \WP_User $user): void {
        $this->record_user((int) $user->ID, 'login');
    }

    /**
     * Registration tracking.
     */
    public function on_registration(int $user_id): void {
        $this->record_user($user_id, 'registration');
    }

    /**
     * Profile page visited.
     */
    public function on_profile_visit(int $viewer_id, int $profile_id): void {
        $this->record_user($profile_id, 'profile_view');
    }

    /**
     * Server-side page view (legacy compat — new tracker.js handles most views).
     */
    public function on_page_view(): void {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        if (is_singular()) {
            $post = get_queried_object();
            if ($post instanceof \WP_Post) {
                $this->record_content($post->ID, $post->post_type, 'view');
            }
        }
    }

    /**
     * Generic content action (published, created, updated, deleted, etc.).
     */
    public function on_content_action(int $post_id, string $action = 'updated'): void {
        $post_type = get_post_type($post_id);
        if ($post_type) {
            $this->record_content($post_id, $post_type, $action);
        }
    }

    /**
     * Event RSVP status change.
     */
    public function on_event_rsvp(int $event_id, int $user_id, string $status = 'going'): void {
        $this->record_event($event_id, 'rsvp_' . sanitize_key($status));
        $this->record_user($user_id, 'rsvp_' . sanitize_key($status));
    }

    /**
     * Social activity (post, reply).
     * Hook: apollo/social/activity_created, apollo/social/reply_created.
     */
    public function on_social_activity(int $activity_id, int|array $user_context, ?int $user_id = null): void {
        $action = str_contains(current_action(), 'reply') ? 'social_reply' : 'social_post';

        if (is_int($user_context)) {
            $user_id = $user_context;
        } elseif (
            is_array($user_context)
            && isset($user_context['user_id'])
            && is_numeric($user_context['user_id'])
        ) {
            $user_id = (int) $user_context['user_id'];
        }

        if ($user_id <= 0) {
            return;
        }

        $this->record_user($user_id, $action);
    }

    /**
     * User-centric action (follow, etc.).
     */
    public function on_user_action(int $actor_id, int $target_id): void {
        $hook   = current_action();
        $action = str_contains($hook, 'follow') ? 'follow' : sanitize_key(basename($hook));
        $this->record_user($actor_id, $action);
        $this->record_user($target_id, $action . '_received');
    }

    /**
     * Chat message sent.
     */
    public function on_chat_message(int $message_id, int $user_id): void {
        $this->record_user($user_id, 'chat_message');
    }

    /**
     * Chat thread created.
     */
    public function on_chat_thread(int $thread_id): void {
        $this->record_event($thread_id, 'chat_thread_created');
    }

    /**
     * Group join.
     */
    public function on_group_join(int $group_id, int $user_id): void {
        $this->record_user($user_id, 'group_joined');
        $this->record_event($group_id, 'member_joined');
    }

    /**
     * Wow reaction.
     */
    public function on_wow(int $post_id, int $user_id, string $reaction_type = 'wow'): void {
        $this->record_content($post_id, get_post_type($post_id) ?: 'post', 'wow');
        $this->record_user($user_id, 'wow_given');
    }

    /**
     * Fav added.
     */
    public function on_fav(int $post_id, int $user_id): void {
        $this->record_content($post_id, get_post_type($post_id) ?: 'post', 'fav');
        $this->record_user($user_id, 'fav_added');
    }

    /**
     * Fav removed.
     */
    public function on_fav_removed(int $post_id, int $user_id): void {
        $this->record_content($post_id, get_post_type($post_id) ?: 'post', 'fav_removed');
        $this->record_user($user_id, 'fav_removed');
    }

    /**
     * Points awarded/deducted.
     */
    public function on_points_change(int $user_id, int $points, string $trigger = ''): void {
        $action = str_contains(current_action(), 'deducted') ? 'points_deducted' : 'points_awarded';
        $this->record_user($user_id, $action, $points);
    }

    /**
     * Badge/achievement/rank earned.
     *
     * `apollo/membership/badge_assigned` fires (int $user_id, string $badge_type, …).
     * `apollo/membership/achievement_awarded` fires (int $user_id, int $achievement_id).
     * `apollo/membership/rank_awarded` fires (int $user_id, int $rank_id).
     *
     * int|string covers all three hook signatures. The value is intentionally
     * unused in this method — only the action label is recorded.
     */
    public function on_achievement(int $user_id, int|string $achievement_id): void {
        $hook   = current_action();
        $action = 'achievement';
        if (str_contains($hook, 'badge')) {
            $action = 'badge_earned';
        } elseif (str_contains($hook, 'rank')) {
            $action = 'rank_up';
        }
        $this->record_user($user_id, $action);
    }

    /**
     * Email sent/failed/opened.
     */
    public function on_email(int $email_id): void {
        $hook   = current_action();
        $action = 'email_sent';
        if (str_contains($hook, 'failed')) {
            $action = 'email_failed';
        } elseif (str_contains($hook, 'opened')) {
            $action = 'email_opened';
        }
        $this->record_event($email_id, $action);
    }

    /**
     * CoAuthor invited.
     */
    public function on_coauthor(int $post_id, int $user_id): void {
        $this->record_content($post_id, get_post_type($post_id) ?: 'post', 'coauthor_invited');
        $this->record_user($user_id, 'coauthor_invited');
    }

    /**
     * Push notification subscribed.
     */
    public function on_push_subscribe(int $user_id): void {
        $this->record_user($user_id, 'push_subscribed');
    }

    /**
     * Gestor task created/completed/milestone.
     */
    public function on_gestor_task(int $task_id, int $user_id): void {
        $hook   = current_action();
        $action = 'task_created';
        if (str_contains($hook, 'completed')) {
            $action = 'task_completed';
        } elseif (str_contains($hook, 'milestone')) {
            $action = 'milestone_reached';
        }
        $this->record_event($task_id, $action);
        $this->record_user($user_id, $action);
    }

    /**
     * Gestor payment recorded.
     */
    public function on_gestor_payment(int $payment_id, float $amount): void {
        $this->record_event($payment_id, 'payment_recorded', (int) ($amount * 100));
    }

    /* ══════════════════════════════════════════════════════════
       RECORDING HELPERS — bridge to legacy functions
       ══════════════════════════════════════════════════════════ */

    private function record_event(int $event_id, string $metric_type, int $value = 1): void {
        if (function_exists('apollo_stats_record_event')) {
            apollo_stats_record_event($event_id, $metric_type, $value);
        }
    }

    private function record_user(int $user_id, string $metric_type, int $value = 1): void {
        if (function_exists('apollo_stats_record_user')) {
            apollo_stats_record_user($user_id, $metric_type, $value);
        }
    }

    private function record_content(int $post_id, string $post_type, string $metric_type): void {
        if (function_exists('apollo_stats_record_content')) {
            apollo_stats_record_content($post_id, $metric_type);
        }
    }
}
