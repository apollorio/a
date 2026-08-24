<?php
/**
 * StatsController — v2 REST endpoints for metric data.
 *
 * Extends apollo-core RestBase, preserves legacy endpoint compatibility
 * and adds new metric-based routes.
 *
 * @package Apollo\Statistics\API
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\API;

use Apollo\Core\API\RestBase;
use Apollo\Statistics\Core\MetricRegistry;

if (! defined('ABSPATH')) {
    exit;
}

final class StatsController extends RestBase {

    public function __construct() {
        parent::__construct();
        $this->rest_base = 'stats';
    }

    public function register_routes(): void {

        // GET /stats/metrics — list all registered MetricGroups.
        register_rest_route($this->namespace, '/' . $this->rest_base . '/metrics', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'list_metrics'),
                'permission_callback' => array($this, 'is_admin'),
            ),
        ));

        // GET /stats/metric/(?P<slug>[a-z0-9_-]+) — compute one metric.
        register_rest_route($this->namespace, '/' . $this->rest_base . '/metric/(?P<slug>[a-z0-9_-]+)', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_metric'),
                'permission_callback' => array($this, 'is_admin'),
                'args'                => $this->get_metric_args(),
            ),
        ));

        // PUT /stats/metric/(?P<slug>[a-z0-9_-]+)/toggle — enable/disable.
        register_rest_route($this->namespace, '/' . $this->rest_base . '/metric/(?P<slug>[a-z0-9_-]+)/toggle', array(
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'toggle_metric'),
                'permission_callback' => array($this, 'is_admin'),
                'args'                => array(
                    'enabled' => array(
                        'required' => true,
                        'type'     => 'boolean',
                    ),
                ),
            ),
        ));

        // GET /stats/dashboard — all enabled metrics for admin dashboard.
        register_rest_route($this->namespace, '/' . $this->rest_base . '/dashboard', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'dashboard_data'),
                'permission_callback' => array($this, 'is_admin'),
                'args'                => $this->get_metric_args(),
            ),
        ));

        // GET /stats/profile/(?P<user_id>\d+) — user profile stats (public-visible).
        register_rest_route($this->namespace, '/' . $this->rest_base . '/profile/(?P<user_id>\d+)', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'profile_stats'),
                'permission_callback' => array($this, 'profile_permission'),
                'args'                => array(
                    'user_id' => array(
                        'required' => true,
                        'type'     => 'integer',
                        'minimum'  => 1,
                    ),
                    'days' => array(
                        'type'    => 'integer',
                        'default' => 30,
                        'minimum' => 1,
                        'maximum' => 365,
                    ),
                ),
            ),
        ));
    }

    /* ──────────── Callbacks ──────────── */

    /**
     * List all metric schemas.
     */
    public function list_metrics(\WP_REST_Request $request): \WP_REST_Response {
        $registry = MetricRegistry::instance();
        return $this->prepare_response($registry->get_all_schemas());
    }

    /**
     * Compute a single metric.
     */
    public function get_metric(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $slug    = sanitize_key($request->get_param('slug'));
        $registry = MetricRegistry::instance();
        $metric   = $registry->get($slug);

        if ($metric === null) {
            return $this->prepare_error('metric_not_found', "Metric '{$slug}' not found.", 404);
        }

        if (! $metric->is_available()) {
            return $this->prepare_error('metric_unavailable', "Metric '{$slug}' dependencies not met.", 424);
        }

        $args = array(
            'days'   => absint($request->get_param('days') ?? 30),
            'limit'  => absint($request->get_param('limit') ?? 50),
        );

        return $this->prepare_response($metric->render_data($args));
    }

    /**
     * Toggle a metric enabled/disabled.
     */
    public function toggle_metric(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $slug    = sanitize_key($request->get_param('slug'));
        $enabled = (bool) $request->get_param('enabled');
        $registry = MetricRegistry::instance();

        if ($registry->get($slug) === null) {
            return $this->prepare_error('metric_not_found', "Metric '{$slug}' not found.", 404);
        }

        $registry->set_enabled($slug, $enabled);

        return $this->prepare_response(array(
            'slug'    => $slug,
            'enabled' => $enabled,
        ));
    }

    /**
     * Dashboard data: compute all enabled metrics.
     */
    public function dashboard_data(\WP_REST_Request $request): \WP_REST_Response {
        $registry = MetricRegistry::instance();
        $enabled  = $registry->get_enabled();
        $args     = array(
            'days'  => absint($request->get_param('days') ?? 30),
            'limit' => absint($request->get_param('limit') ?? 20),
        );

        $results = array();
        foreach ($enabled as $slug => $metric) {
            if (! $metric->is_available()) {
                continue;
            }
            if (! in_array('admin_dashboard', $metric->get_displays(), true)) {
                continue;
            }
            $results[] = $metric->render_data($args);
        }

        return $this->prepare_response($results);
    }

    /**
     * Profile stats: compute user-visible metrics for a given user.
     */
    public function profile_stats(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $user_id  = absint($request->get_param('user_id'));
        $registry = MetricRegistry::instance();
        $enabled  = $registry->get_enabled();
        $args     = array(
            'days'    => absint($request->get_param('days') ?? 30),
            'user_id' => $user_id,
            'limit'   => 20,
        );

        $results = array();
        foreach ($enabled as $slug => $metric) {
            if (! $metric->is_available()) {
                continue;
            }
            if (! in_array('user_profile', $metric->get_displays(), true)) {
                continue;
            }
            $results[] = $metric->render_data($args);
        }

        return $this->prepare_response(array(
            'user_id' => $user_id,
            'metrics' => $results,
        ));
    }

    /* ──────────── Permissions ──────────── */

    /**
     * Profile stats: logged-in users can see their own or public profiles.
     */
    public function profile_permission(\WP_REST_Request $request): bool|\WP_Error {
        $target_id  = absint($request->get_param('user_id'));
        $current_id = get_current_user_id();

        // Own stats — always allowed.
        if ($current_id > 0 && $current_id === $target_id) {
            return true;
        }

        // Admin — always allowed.
        if (current_user_can('manage_options')) {
            return true;
        }

        // Public profile check via meta.
        $visibility = get_user_meta($target_id, '_apollo_stats_visibility', true);
        if ($visibility === 'public') {
            return true;
        }

        // Followers-only: check if current user follows target.
        if ($visibility === 'followers' && $current_id > 0) {
            $is_following = apply_filters('apollo/social/is_following', false, $current_id, $target_id);
            if ($is_following) {
                return true;
            }
        }

        return new \WP_Error(
            'rest_forbidden',
            __('You do not have permission to view this profile stats.', 'apollo-statistics'),
            array('status' => 403)
        );
    }

    /* ──────────── Args ──────────── */

    private function get_metric_args(): array {
        return array(
            'days' => array(
                'type'    => 'integer',
                'default' => 30,
                'minimum' => 1,
                'maximum' => 365,
            ),
            'limit' => array(
                'type'    => 'integer',
                'default' => 50,
                'minimum' => 1,
                'maximum' => 200,
            ),
        );
    }
}
