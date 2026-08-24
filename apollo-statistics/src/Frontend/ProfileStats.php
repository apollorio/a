<?php
/**
 * ProfileStats — frontend profile stats route and rendering.
 *
 * Renders the /id/{username}/stats page using Apollo CDN.
 *
 * @package Apollo\Statistics\Frontend
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Frontend;

if (! defined('ABSPATH')) {
    exit;
}

final class ProfileStats {

    public function init(): void {
        // Register the stats endpoint for user profiles.
        add_action('init', array($this, 'add_rewrite'));
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('template_redirect', array($this, 'maybe_render'));
    }

    public function add_rewrite(): void {
        // Match /id/{username}/stats
        add_rewrite_rule(
            '^id/([^/]+)/stats/?$',
            'index.php?apollo_profile_user=$matches[1]&apollo_profile_stats=1',
            'top'
        );
    }

    /**
     * @param string[] $vars
     * @return string[]
     */
    public function query_vars(array $vars): array {
        $vars[] = 'apollo_profile_user';
        $vars[] = 'apollo_profile_stats';
        return $vars;
    }

    public function maybe_render(): void {
        if (! get_query_var('apollo_profile_stats')) {
            return;
        }

        $username = sanitize_user(get_query_var('apollo_profile_user'));
        if (empty($username)) {
            return;
        }

        $user = get_user_by('login', $username);
        if (! $user) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        // Check visibility via Analytics class (single source of truth).
        $current_id = get_current_user_id();
        $can_view   = \Apollo\Statistics\Analytics::can_view_user_stats($user->ID, $current_id);

        if (! $can_view) {
            status_header(403);
            wp_die(
                esc_html__('This user\'s stats are private.', 'apollo-statistics'),
                403
            );
        }

        // Enqueue the profile stats script.
        $this->render_page($user);
        exit;
    }

    private function render_page(\WP_User $user): void {
        $cdn_url  = defined('APOLLO_CDN_URL') ? constant('APOLLO_CDN_URL') : 'https://cdn.apollo.rio.br/v1.0.0/';
        $rest_url = rest_url('apollo/v1/profile-stats/' . $user->ID);
        // Public profiles may be viewed (and cached) by guests; only authenticated users
        // perform write actions (e.g. visibility toggle), so never emit a nonce to guests.
        $nonce    = is_user_logged_in() ? wp_create_nonce('wp_rest') : '';

        // Canvas template — separated for modularity.
        include APOLLO_STATS_PATH . 'templates/profile-stats.php';
    }
}
