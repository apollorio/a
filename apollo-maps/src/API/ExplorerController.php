<?php

/**
 * REST: /apollo/v1/map/explorer
 * Aggregates events with loc coordinates for Leaflet map.
 *
 * @package Apollo\Maps
 */

declare(strict_types=1);

namespace Apollo\Maps\API;

use Apollo\Core\API\RestBase;

if (! defined('ABSPATH')) {
    exit;
}

class ExplorerController extends RestBase
{

    /**
     * Register routes
     */
    public function register_routes(): void
    {
        register_rest_route(
            APOLLO_MAPS_REST_NAMESPACE,
            '/map/explorer',
            array(
                'args'                => $this->get_collection_params(),
                'permission_callback' => '__return_true',
                'callback'            => array($this, 'get_map_data'),
                'methods'             => \WP_REST_Server::READABLE,
            )
        );
    }

    /**
     * GET data for map rendering
     */
    public function get_map_data(\WP_REST_Request $request)
    {
        $per_page   = max(1, min(150, (int) $request->get_param('per_page') ?: 60));
        $upcoming   = (int) $request->get_param('upcoming') !== 0;
        $with_locs  = (int) $request->get_param('with_locs') !== 0;

        $events = $this->fetch_events($per_page, $upcoming);
        $locs   = $with_locs ? $this->fetch_locs() : array();

        return $this->prepare_response(
            array(
                'events' => $events,
                'locs'   => $locs,
            )
        );
    }

    /**
     * Collection params for validation
     */
    public function get_collection_params(): array
    {
        return array(
            'per_page' => array(
                'description'       => 'Items to fetch',
                'type'              => 'integer',
                'default'           => 60,
                'required'          => false,
                'sanitize_callback' => 'absint',
                // PHP8 fix: WP_REST_Request::has_valid_params() calls this
                // with 3 args (value, request, param) — the bare 'is_numeric'
                // string only accepts 1 and fatals as an ArgumentCountError
                // under PHP 8+. Wrap it so the arg count matches.
                'validate_callback' => function ($value) {
                    return is_numeric($value);
                },
            ),
            'upcoming' => array(
                'description'       => 'Upcoming only (1) or everything (0)',
                'type'              => 'integer',
                'default'           => 1,
                'required'          => false,
                'sanitize_callback' => 'absint',
            ),
            'with_locs' => array(
                'description'       => 'Include standalone loc points',
                'type'              => 'integer',
                'default'           => 1,
                'required'          => false,
                'sanitize_callback' => 'absint',
            ),
        );
    }

    /**
     * Fetch events with loc coordinates
     */
    private function fetch_events(int $per_page, bool $upcoming): array
    {
        $today = current_time('Y-m-d');

        $meta_query = array(
            'relation' => 'AND',
            array(
                'key'     => '_event_loc_id',
                'compare' => 'EXISTS',
            ),
            array(
                'key'     => '_event_loc_id',
                'value'   => '',
                'compare' => '!=',
            ),
        );

        if ($upcoming) {
            $meta_query[] = array(
                'key'     => '_event_start_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            );
        }

        $args = array(
            'post_type'      => 'event',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'fields'         => 'ids',
            'meta_query'     => $meta_query,
            'meta_key'       => '_event_start_date',
        );

        $query  = new \WP_Query($args);
        $events = array();

        if ($query->have_posts()) {
            foreach ($query->posts as $event_id) {
                $loc_id = (int) get_post_meta($event_id, '_event_loc_id', true);
                if (! $loc_id) {
                    continue;
                }

                $lat = get_post_meta($loc_id, '_local_lat', true);
                $lng = get_post_meta($loc_id, '_local_lng', true);

                if ('' === $lat || '' === $lng) {
                    continue;
                }

                $start_date = (string) get_post_meta($event_id, '_event_start_date', true);
                $start_time = (string) get_post_meta($event_id, '_event_start_time', true);
                $when       = $start_date ? $start_date : '';
                if ($start_time) {
                    $when = trim($when . ' ' . $start_time);
                }

                $events[] = array(
                    'id'    => $event_id,
                    'title' => wp_strip_all_tags(get_the_title($event_id)),
                    'link'  => get_permalink($event_id),
                    'when'  => $when,
                    'lat'   => (float) $lat,
                    'lng'   => (float) $lng,
                    'loc'   => $this->shape_loc_payload($loc_id),
                );
            }
        }

        return $events;
    }

    /**
     * Fetch loc posts for standalone map mode
     */
    private function fetch_locs(): array
    {
        $args = array(
            'post_type'      => 'local',
            'post_status'    => 'publish',
            'posts_per_page' => 120,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        $query = new \WP_Query($args);
        $locs  = array();

        if ($query->have_posts()) {
            foreach ($query->posts as $loc_id) {
                $locs[] = $this->shape_loc_payload($loc_id);
            }
        }

        return array_values(array_filter($locs));
    }

    /**
     * Shape loc payload (used by events and standalone loc fetch)
     */
    private function shape_loc_payload(int $loc_id): array
    {
        $lat = get_post_meta($loc_id, '_local_lat', true);
        $lng = get_post_meta($loc_id, '_local_lng', true);

        if ('' === $lat || '' === $lng) {
            return array();
        }

        $address = (string) get_post_meta($loc_id, '_local_address', true);

        return array(
            'id'      => $loc_id,
            'title'   => wp_strip_all_tags(get_the_title($loc_id)),
            'link'    => get_permalink($loc_id),
            'lat'     => (float) $lat,
            'lng'     => (float) $lng,
            'address' => $address ? wp_strip_all_tags($address) : '',
        );
    }
}
