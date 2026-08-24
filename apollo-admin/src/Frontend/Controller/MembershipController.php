<?php
/**
 * Membership Controller — placeholder for membership management logic.
 *
 * Template rendering is handled by Router; this controller holds
 * any REST/AJAX endpoints specific to memberships management.
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Frontend\Controller;

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

final class MembershipController {

    /**
     * Register hooks.
     */
    public function init(): void {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes(): void {
        register_rest_route(
            APOLLO_ADMIN_REST_NAMESPACE,
            '/admin/memberships',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_get_memberships' ),
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            )
        );
    }

    /**
     * Get all membership CPT posts.
     */
    public function rest_get_memberships( \WP_REST_Request $request ): \WP_REST_Response {
        $posts = get_posts(
            array(
                'post_type'      => 'membership',
                'post_status'    => 'any',
                'posts_per_page' => 100,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        $items = array();
        foreach ( $posts as $p ) {
            $items[] = array(
                'id'     => $p->ID,
                'title'  => $p->post_title,
                'status' => $p->post_status,
                'date'   => $p->post_date,
            );
        }

        return new \WP_REST_Response( $items, 200 );
    }
}
