<?php
/**
 * Apollo Core — Channel Preferences procedural wrappers.
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_user_channel_allowed')) {
    /**
     * Check if a user has allowed a specific notification channel.
     *
     * @param int    $user_id User ID.
     * @param string $channel Channel slug: 'notif', 'email', 'push', 'telegram'.
     * @param string $type    Notification type slug (e.g. 'reminder', 'chat').
     * @return bool
     */
    function apollo_user_channel_allowed( int $user_id, string $channel, string $type = '' ): bool {
        return \Apollo\Core\ChannelPreferences::get_instance()->user_channel_allowed( $user_id, $channel, $type );
    }
}

if (! function_exists('apollo_get_user_channels')) {
    /**
     * Get all available channels and their enabled status for a user.
     *
     * @param int $user_id User ID.
     * @return array<string, bool>
     */
    function apollo_get_user_channels( int $user_id ): array {
        return \Apollo\Core\ChannelPreferences::get_instance()->get_user_channels( $user_id );
    }
}

if (! function_exists('apollo_is_dnd_active')) {
    /**
     * Check if Do Not Disturb is currently active for a user.
     *
     * @param int $user_id User ID.
     * @return bool
     */
    function apollo_is_dnd_active( int $user_id ): bool {
        return \Apollo\Core\ChannelPreferences::get_instance()->is_dnd_active( $user_id );
    }
}
