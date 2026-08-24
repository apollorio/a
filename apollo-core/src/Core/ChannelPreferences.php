<?php
declare(strict_types=1);

namespace Apollo\Core;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Channel Preferences — Unified preference service for all notification channels.
 *
 * Queries user preferences across apollo-notif and apollo-email to determine
 * if a notification should be dispatched on a given channel.
 *
 * @package Apollo\Core
 * @since   6.1.0
 */
final class ChannelPreferences {

    /** @var self|null */
    private static ?self $instance = null;

    /** @var array<string, string> Map notification types → email preference categories */
    private const TYPE_TO_EMAIL_CATEGORY = array(
        'reminder'       => 'transactional',
        'event_reminder' => 'transactional',
        'password_reset' => 'transactional',
        'verification'   => 'transactional',
        'welcome'        => 'transactional',
        'chat'           => 'transactional',
        'group_invite'   => 'transactional',
        'achievement'    => 'transactional',
        'digest'         => 'digest',
        'weekly_digest'  => 'digest',
        'newsletter'     => 'marketing',
        'marketing'      => 'marketing',
        'gestor'         => 'gestor_reminders',
        'task'           => 'gestor_reminders',
        'task_reminder'  => 'gestor_reminders',
    );

    private function __construct() {}

    public static function get_instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if a user has allowed a specific channel for a given notification type.
     *
     * @param int    $user_id User ID.
     * @param string $channel Channel slug: 'notif', 'email', 'push', 'telegram'.
     * @param string $type    Notification type (e.g., 'reminder', 'chat', 'wow').
     * @return bool Whether the channel is allowed for this user and type.
     */
    public function user_channel_allowed( int $user_id, string $channel, string $type = '' ): bool {
        if ( $user_id <= 0 ) {
            return false;
        }

        $allowed = true;

        // 1. Check in-app notification type toggle
        if ( $channel === 'notif' && $type !== '' ) {
            $notif_prefs = get_user_meta( $user_id, '_apollo_notif_prefs', true );
            if ( is_array( $notif_prefs ) && isset( $notif_prefs[ $type ] ) && ! $notif_prefs[ $type ] ) {
                $allowed = false;
            }
        }

        // 2. Check snooze system (uses apollo-notif function if available)
        if ( $allowed && $channel === 'notif' && $type !== '' && function_exists( 'apollo_is_type_snoozed' ) ) {
            if ( apollo_is_type_snoozed( $user_id, $type ) ) {
                $allowed = false;
            }
        }

        // 3. Check email preferences by category
        if ( $allowed && $channel === 'email' ) {
            $email_prefs = get_user_meta( $user_id, '_apollo_email_prefs', true );
            if ( is_array( $email_prefs ) ) {
                $category = self::get_email_category_for_type( $type );
                if ( isset( $email_prefs[ $category ] ) && empty( $email_prefs[ $category ] ) ) {
                    $allowed = false;
                }
            }
        }

        // 4. Check Do Not Disturb window (all channels)
        if ( $allowed && $this->is_dnd_active( $user_id ) ) {
            $allowed = false;
        }

        /**
         * Filter whether a channel is allowed for a user and notification type.
         *
         * @since 6.1.0
         *
         * @param bool   $allowed  Whether the channel is allowed.
         * @param int    $user_id  User ID.
         * @param string $channel  Channel slug.
         * @param string $type     Notification type.
         */
        return (bool) apply_filters( 'apollo/preferences/channel_allowed', $allowed, $user_id, $channel, $type );
    }

    /**
     * Get all available channels and their enabled status for a user.
     *
     * @param int $user_id User ID.
     * @return array<string, bool> Channel availability map.
     */
    public function get_user_channels( int $user_id ): array {
        $channels = array(
            'notif'    => true,
            'email'    => true,
            'push'     => false,
            'telegram' => false,
        );

        if ( $user_id <= 0 ) {
            return $channels;
        }

        // Push: check if user has any subscriptions (shared meta)
        $push_subs = get_user_meta( $user_id, '_apollo_push_subscriptions', true );
        $channels['push'] = is_array( $push_subs ) && ! empty( $push_subs );

        // Telegram: check if user has active linked account
        global $wpdb;
        $telegram_table = $wpdb->prefix . 'apollo_remind_telegram';
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $telegram_table ) ) === $telegram_table ) {
            $channels['telegram'] = (bool) $wpdb->get_var( $wpdb->prepare(
                "SELECT 1 FROM {$telegram_table} WHERE user_id = %d AND active = 1 LIMIT 1",
                $user_id
            ) );
        }

        return $channels;
    }

    /**
     * Map a notification type to an email preference category.
     *
     * @param string $type Notification type slug.
     * @return string Email preference category key.
     */
    public static function get_email_category_for_type( string $type ): string {
        return self::TYPE_TO_EMAIL_CATEGORY[ $type ] ?? 'transactional';
    }

    /**
     * Check if Do Not Disturb is currently active for a user.
     *
     * @param int $user_id User ID.
     * @return bool Whether DND is active right now.
     */
    public function is_dnd_active( int $user_id ): bool {
        $dnd = get_user_meta( $user_id, '_apollo_dnd_window', true );

        if ( ! is_array( $dnd ) || empty( $dnd['enabled'] ) || empty( $dnd['start'] ) || empty( $dnd['end'] ) ) {
            return false;
        }

        $now   = current_time( 'H:i' );
        $start = $dnd['start'];
        $end   = $dnd['end'];

        // Handle overnight windows (e.g., 23:00 → 08:00)
        if ( $start > $end ) {
            return $now >= $start || $now < $end;
        }

        return $now >= $start && $now < $end;
    }
}
