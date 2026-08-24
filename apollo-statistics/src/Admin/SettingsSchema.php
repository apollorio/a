<?php
/**
 * SettingsSchema — registers statistics settings in apollo-admin panel.
 *
 * @package Apollo\Statistics\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Admin;

use Apollo\Statistics\Core\MetricRegistry;

if (! defined('ABSPATH')) {
    exit;
}

final class SettingsSchema {

    public function init(): void {
        add_filter('apollo/admin/register_settings', array($this, 'register'));
    }

    /**
     * Register the statistics settings tab in apollo-admin.
     */
    public function register(array $schemas): array {
        $schemas['statistics'] = array(
            'tab_label'   => 'Statistics',
            'tab_icon'    => 'ri-bar-chart-grouped-line',
            'description' => 'Configure analytics metrics, data retention, and display options.',
            'sections'    => array(
                array(
                    'id'     => 'general',
                    'label'  => 'General',
                    'fields' => array(
                        array(
                            'id'      => 'tracker_enabled',
                            'type'    => 'toggle',
                            'label'   => 'Enable Tracker',
                            'desc'    => 'Load tracker.js on frontend pages.',
                            'default' => true,
                        ),
                        array(
                            'id'      => 'session_timeout',
                            'type'    => 'number',
                            'label'   => 'Session Timeout (minutes)',
                            'desc'    => 'Minutes of inactivity before a session is considered expired.',
                            'default' => 30,
                            'min'     => 5,
                            'max'     => 120,
                        ),
                        array(
                            'id'      => 'retention_sessions',
                            'type'    => 'number',
                            'label'   => 'Retention: Sessions (days)',
                            'desc'    => 'Days to keep closed session records.',
                            'default' => 365,
                            'min'     => 1,
                            'max'     => 3650,
                        ),
                        array(
                            'id'      => 'retention_pageviews',
                            'type'    => 'number',
                            'label'   => 'Retention: Pageviews (days)',
                            'desc'    => 'Days to keep detailed pageview rows.',
                            'default' => 90,
                            'min'     => 1,
                            'max'     => 3650,
                        ),
                        array(
                            'id'      => 'retention_clicks',
                            'type'    => 'number',
                            'label'   => 'Retention: Clicks (days)',
                            'desc'    => 'Days to keep click-tracking rows.',
                            'default' => 30,
                            'min'     => 1,
                            'max'     => 365,
                        ),
                        array(
                            'id'      => 'retention_radio',
                            'type'    => 'number',
                            'label'   => 'Retention: Radio Listening (days)',
                            'desc'    => 'Days to keep radio session rows.',
                            'default' => 365,
                            'min'     => 1,
                            'max'     => 3650,
                        ),
                    ),
                ),
                array(
                    'id'     => 'metrics',
                    'label'  => 'Metric Toggles',
                    'fields' => $this->build_metric_toggles(),
                ),
            ),
        );

        return $schemas;
    }

    /**
     * Build toggle fields for each registered MetricGroup.
     */
    private function build_metric_toggles(): array {
        $registry = MetricRegistry::instance();
        $fields   = array();

        foreach ($registry->get_sorted() as $metric) {
            $fields[] = array(
                'id'      => 'metric_toggle_' . $metric->get_slug(),
                'type'    => 'toggle',
                'label'   => $metric->get_label(),
                'desc'    => 'Enable/disable this metric.',
                'default' => $metric->is_default_enabled(),
            );
        }

        return $fields;
    }
}
