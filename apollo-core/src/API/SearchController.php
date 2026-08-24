<?php

/**
 * Apollo Search — REST API Controller.
 *
 * Provides unified search endpoints for all Apollo CPTs, users, posts, pages.
 * Powers the ApolloSearch typeahead component.
 *
 * Endpoints:
 *   GET /apollo/v1/search?q=...&limit=10        — Global search (all types)
 *   GET /apollo/v1/search/events?q=...&limit=10  — Events only
 *   GET /apollo/v1/search/users?q=...&limit=10   — Users only
 *   GET /apollo/v1/search/classifieds?q=...       — Classifieds only
 *   GET /apollo/v1/search/djs?q=...               — DJs only
 *   GET /apollo/v1/search/locs?q=...              — Locations only
 *   GET /apollo/v1/search/posts?q=...             — Posts only
 *   GET /apollo/v1/search/pages?q=...             — Pages only
 *
 * @package Apollo\Core\API
 * @since   1.0.0
 */

namespace Apollo\Core\API;

if (! defined('ABSPATH')) {
    exit;
}

class SearchController extends RestBase
{

    protected $namespace = 'apollo/v1';

    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        // Global search
        register_rest_route(
            $this->namespace,
            '/search',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'search_all'),
                'permission_callback' => '__return_true',
                'args'                => $this->get_search_args(),
            )
        );

        // Type-specific
        $types = array('events', 'users', 'classifieds', 'djs', 'locs', 'posts', 'pages');
        foreach ($types as $type) {
            register_rest_route(
                $this->namespace,
                '/search/' . $type,
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'search_' . $type),
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_search_args(),
                )
            );
        }
    }

    /**
     * Common search args.
     */
    private function get_search_args(): array
    {
        return array(
            'q'     => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'limit' => array(
                'type'    => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 50,
            ),
        );
    }

	// ──────────────────────────────────────────────────────────────
	// SEARCH METHODS
	// ──────────────────────────────────────────────────────────────

    /**
     * Global search across all types.
     */
    public function search_all(\WP_REST_Request $request): \WP_REST_Response
    {
        $q     = $request->get_param('q');
        $limit = absint($request->get_param('limit') ?: 10);
        $per   = max(1, intdiv($limit, 4)); // Split across types

        $results = array_merge(
            $this->_search_cpt('event', $q, $per, 'events'),
            $this->_search_users($q, $per),
            $this->_search_cpt('classified', $q, $per, 'classifieds'),
            $this->_search_cpt('post', $q, $per, 'posts')
        );

        // Sort by relevance (title starts with query first)
        usort(
            $results,
            function ($a, $b) use ($q) {
                $aStarts = stripos($a['title'], $q) === 0 ? 0 : 1;
                $bStarts = stripos($b['title'], $q) === 0 ? 0 : 1;
                return $aStarts - $bStarts;
            }
        );

        return new \WP_REST_Response(array_slice($results, 0, $limit));
    }

    /**
     * Search events.
     */
    public function search_events(\WP_REST_Request $request): \WP_REST_Response
    {
        $q     = $request->get_param('q');
        $limit = absint($request->get_param('limit') ?: 10);

        return new \WP_REST_Response($this->_search_cpt('event', $q, $limit, 'events'));
    }

    /**
     * Search users.
     */
    public function search_users(\WP_REST_Request $request): \WP_REST_Response
    {
        $q     = $request->get_param('q');
        $limit = absint($request->get_param('limit') ?: 10);

        return new \WP_REST_Response($this->_search_users($q, $limit));
    }

    /**
     * Search classifieds.
     */
    public function search_classifieds(\WP_REST_Request $request): \WP_REST_Response
    {
        $q     = $request->get_param('q');
        $limit = absint($request->get_param('limit') ?: 10);

        return new \WP_REST_Response($this->_search_cpt('classified', $q, $limit, 'classifieds'));
    }

    /**
     * Search DJs.
     */
    public function search_djs(\WP_REST_Request $request): \WP_REST_Response
    {
        $q     = $request->get_param('q');
        $limit = absint($request->get_param('limit') ?: 10);

        return new \WP_REST_Response($this->_search_cpt('dj', $q, $limit, 'djs'));
    }

    /**
     * Search locations.
     */
    public function search_locs(\WP_REST_Request $request): \WP_REST_Response
    {
        $q     = $request->get_param('q');
        $limit = absint($request->get_param('limit') ?: 10);

        return new \WP_REST_Response($this->_search_cpt('loc', $q, $limit, 'locs'));
    }

    /**
     * Search posts.
     */
    public function search_posts(\WP_REST_Request $request): \WP_REST_Response
    {
        $q     = $request->get_param('q');
        $limit = absint($request->get_param('limit') ?: 10);

        return new \WP_REST_Response($this->_search_cpt('post', $q, $limit, 'posts'));
    }

    /**
     * Search pages.
     */
    public function search_pages(\WP_REST_Request $request): \WP_REST_Response
    {
        $q     = $request->get_param('q');
        $limit = absint($request->get_param('limit') ?: 10);

        return new \WP_REST_Response($this->_search_cpt('page', $q, $limit, 'pages'));
    }

	// ──────────────────────────────────────────────────────────────
	// INTERNAL
	// ──────────────────────────────────────────────────────────────

    /**
     * Search any CPT by title.
     *
     * @param string $post_type Post type slug.
     * @param string $q         Search query.
     * @param int    $limit     Max results.
     * @param string $type      Type label for results.
     * @return array
     */
    private function _search_cpt(string $post_type, string $q, int $limit, string $type): array
    {
        // Skip if post type doesn't exist
        if (! post_type_exists($post_type) && ! in_array($post_type, array('post', 'page'), true)) {
            return array();
        }

        $args = array(
            'post_type'      => $post_type,
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
            's'              => $q,
            'orderby'        => 'relevance',
            'order'          => 'DESC',
        );

        // For events, prefer upcoming first
        if ($post_type === 'event') {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_event_start_date',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => '_event_start_date',
                    'compare' => 'NOT EXISTS',
                ),
            );
            $args['orderby']    = 'meta_value';
            $args['meta_key']   = '_event_start_date';
            $args['order']      = 'ASC';
        }

        $query   = new \WP_Query($args);
        $results = array();

        foreach ($query->posts as $post) {
            $item = array(
                'id'    => $post->ID,
                'title' => $post->post_title,
                'url'   => get_permalink($post->ID),
                'type'  => $type,
            );

            // Add subtitle based on type
            switch ($post_type) {
                case 'event':
                    $date     = get_post_meta($post->ID, '_event_start_date', true);
                    $time     = get_post_meta($post->ID, '_event_start_time', true);
                    $loc_id   = get_post_meta($post->ID, '_event_loc_id', true);
                    $loc_name = $loc_id ? get_the_title($loc_id) : '';

                    $parts = array();
                    if ($date) {
                        $parts[] = wp_date('d/m/Y', strtotime($date));
                    }
                    if ($time) {
                        $parts[] = $time;
                    }
                    if ($loc_name) {
                        $parts[] = $loc_name;
                    }
                    $item['subtitle'] = implode(' · ', $parts);
                    $item['meta']     = $item['subtitle'];
                    $item['date']     = $date ?: '';
                    break;

                case 'classified':
                    $price        = get_post_meta($post->ID, '_classified_price', true);
                    $intent_terms = wp_get_post_terms($post->ID, 'classified_intent', array('fields' => 'names'));
                    $parts        = array();
                    if ($price) {
                        $parts[] = 'R$ ' . number_format((float) $price, 2, ',', '.');
                    }
                    if (! empty($intent_terms) && ! is_wp_error($intent_terms)) {
                        $parts[] = $intent_terms[0];
                    }
                    $item['subtitle'] = implode(' · ', $parts);
                    break;

                case 'dj':
                    $sounds           = wp_get_post_terms($post->ID, 'sound', array('fields' => 'names'));
                    $item['subtitle'] = (! empty($sounds) && ! is_wp_error($sounds))
                        ? implode(', ', array_slice($sounds, 0, 3))
                        : '';
                    break;

                case 'loc':
                    $area_terms       = wp_get_post_terms($post->ID, 'loc_area', array('fields' => 'names'));
                    $item['subtitle'] = (! empty($area_terms) && ! is_wp_error($area_terms))
                        ? $area_terms[0]
                        : '';
                    break;

                default:
                    $item['subtitle'] = wp_trim_words($post->post_excerpt ?: $post->post_content, 10, '…');
                    break;
            }

            $results[] = $item;
        }

        return $results;
    }

    /**
     * Search users by display name, user_login, or user_email.
     *
     * @param string $q     Search query.
     * @param int    $limit Max results.
     * @return array
     */
    private function _search_users(string $q, int $limit): array
    {
        $user_query = new \WP_User_Query(
            array(
                'search'         => '*' . $q . '*',
                'search_columns' => array('user_login', 'display_name', 'user_email'),
                'number'         => $limit,
                'orderby'        => 'display_name',
                'order'          => 'ASC',
            )
        );

        $results = array();
        foreach ($user_query->get_results() as $user) {
            $social_name = get_user_meta($user->ID, '_apollo_social_name', true);
            $display     = $social_name ?: $user->display_name;

            $results[] = array(
                'id'       => $user->ID,
                'title'    => $display,
                'name'     => $display,
                'subtitle' => '@' . $user->user_login,
                'url'      => home_url('/id/' . $user->user_login),
                'type'     => 'users',
            );
        }

        return $results;
    }
}
