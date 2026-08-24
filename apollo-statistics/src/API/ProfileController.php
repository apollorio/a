<?php
/**
 * ProfileController — REST endpoints for public profile stats.
 *
 * Separate from StatsController because it has different permissions
 * and serves the frontend /id/{username}/stats page.
 *
 * @package Apollo\Statistics\API
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\API;

use Apollo\Core\API\RestBase;
use Apollo\Statistics\Metrics\Profile;

if (! defined('ABSPATH')) {
    exit;
}

final class ProfileController extends RestBase {

    public function __construct() {
        parent::__construct();
        $this->rest_base = 'profile-stats';
    }

    public function register_routes(): void {
        // GET /profile-stats/(?P<user_id>\d+)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<user_id>\d+)', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_profile_stats'),
                'permission_callback' => array($this, 'check_visibility'),
                'args'                => array(
                    'user_id' => array('required' => true, 'type' => 'integer', 'minimum' => 1),
                    'days'    => array('type' => 'integer', 'default' => 30, 'minimum' => 1, 'maximum' => 365),
                ),
            ),
        ));

        // PUT /profile-stats/visibility
        register_rest_route($this->namespace, '/' . $this->rest_base . '/visibility', array(
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_visibility'),
                'permission_callback' => array($this, 'is_logged_in'),
                'args'                => array(
                    'visibility' => array(
                        'required' => true,
                        'type'     => 'string',
                        'enum'     => array('public', 'followers', 'private'),
                    ),
                ),
            ),
        ));
    }

    public function get_profile_stats(\WP_REST_Request $request): \WP_REST_Response {
        $user_id = absint($request->get_param('user_id'));
        $days    = absint($request->get_param('days') ?? 30);

        $profile = new Profile();
        $data    = $profile->compute(array('user_id' => $user_id, 'days' => $days));

        return $this->prepare_response($data);
    }

    public function update_visibility(\WP_REST_Request $request): \WP_REST_Response {
        $visibility = sanitize_key($request->get_param('visibility'));
        $user_id    = get_current_user_id();

        update_user_meta($user_id, '_apollo_stats_visibility', $visibility);

        return $this->prepare_response(array(
            'user_id'    => $user_id,
            'visibility' => $visibility,
        ));
    }

    /**
     * Check if the requester can view the target user's stats.
     */
    public function check_visibility(\WP_REST_Request $request): bool|\WP_Error {
        $target_id  = absint($request->get_param('user_id'));
        $current_id = get_current_user_id();

        if ($current_id > 0 && $current_id === $target_id) {
            return true;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $visibility = get_user_meta($target_id, '_apollo_stats_visibility', true) ?: 'public';

        if ($visibility === 'public') {
            return true;
        }

        if ($visibility === 'followers' && $current_id > 0) {
            if (apply_filters('apollo/social/is_following', false, $current_id, $target_id)) {
                return true;
            }
        }

        return new \WP_Error('rest_forbidden', 'Stats are private.', array('status' => 403));
    }
}
