<?php

namespace Apollo\Remind\Cron;

if ( ! defined( 'ABSPATH' ) ) exit;

class Scheduler {
    public static function add_schedules( array $schedules ): array {
        $schedules['apollo_remind_two_minutes'] = [
            'interval' => 120,
            'display'  => __( 'Every 2 minutes (Apollo Remind)', 'apollo-remind' ),
        ];
        return $schedules;
    }
}
