<?php
/**
 * Profile — aggregated dashboard of all metrics for a user.
 *
 * Instance: user_own_stats (shown at /id/{username}/stats).
 *
 * @package Apollo\Statistics\Metrics
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Metrics;

use Apollo\Statistics\Core\MetricGroup;
use Apollo\Statistics\Core\MetricRegistry;

if (! defined('ABSPATH')) {
    exit;
}

class Profile extends MetricGroup {

    protected string $slug        = 'user_own_stats';
    protected string $label       = 'Profile Stats';
    protected string $description = 'Aggregated dashboard for user profiles.';
    protected string $icon        = 'ri-user-star-line';
    protected int    $priority    = 1;
    protected array  $displays    = array('user_profile');
    protected array  $chart       = array('type' => 'dashboard', 'library' => 'html');
    protected bool   $configurable = false;

    public function compute(array $args = array()): array {
        $user_id = ! empty($args['user_id']) ? absint($args['user_id']) : get_current_user_id();
        if ($user_id <= 0) {
            return array('error' => 'no_user');
        }

        $registry = MetricRegistry::instance();
        $enabled  = $registry->get_enabled();

        $user_args = array_merge($args, array('user_id' => $user_id));
        $widgets   = array();

        foreach ($enabled as $slug => $metric) {
            if ($slug === $this->slug) {
                continue; // Skip self.
            }
            if (! in_array('user_profile', $metric->get_displays(), true)) {
                continue;
            }
            if (! $metric->is_available()) {
                continue;
            }

            $widgets[] = $metric->render_data($user_args);
        }

        // Always include static user-level stats.
        $widgets[] = array(
            'slug'   => 'profile_summary',
            'label'  => 'Summary',
            'icon'   => 'ri-user-line',
            'chart'  => array('type' => 'number', 'library' => 'html'),
            'status' => 'ok',
            'data'   => $this->get_summary($user_id),
        );

        return array(
            'user_id' => $user_id,
            'widgets' => $widgets,
        );
    }

    private function get_summary(int $user_id): array {
        return array(
            'engagement_score' => (int) get_user_meta($user_id, '_apollo_stats_engagement_score', true),
            'online_minutes'   => (int) get_user_meta($user_id, '_apollo_online_minutes', true),
            'last_seen'        => (int) get_user_meta($user_id, '_apollo_last_seen', true),
            'member_since'     => get_userdata($user_id)->user_registered ?? '',
        );
    }
}
