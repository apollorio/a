<?php

/**
 * Cleans up stale pending registration transients.
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login\Auth;

if (! defined('ABSPATH')) {
    exit;
}

final class PendingRegistrationCleanup
{
    public static function init(): void
    {
        if (! wp_next_scheduled('apollo_login_pending_registration_cleanup')) {
            wp_schedule_event(time(), 'daily', 'apollo_login_pending_registration_cleanup');
        }

        add_action('apollo_login_pending_registration_cleanup', array(self::class, 'run'));
    }

    public static function run(): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name LIKE %s",
                $wpdb->esc_like('_transient_apollo_pending_reg_') . '%',
                '%'
            )
        );
    }
}
