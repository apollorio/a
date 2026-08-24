<?php
/**
 * Pending Controller — REST + AJAX for pending drafts approval.
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Frontend\Controller;

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

final class PendingController {

    /**
     * Register hooks.
     */
    public function init(): void {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'wp_ajax_apollo_approve_post', array( $this, 'ajax_approve' ) );
        add_action( 'wp_ajax_apollo_reject_post', array( $this, 'ajax_reject' ) );
    }

    /* ── REST Routes ─────────────────────────────────────────────── */

    public function register_routes(): void {
        register_rest_route(
            APOLLO_ADMIN_REST_NAMESPACE,
            '/admin/pending',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_get_pending' ),
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            )
        );

        register_rest_route(
            APOLLO_ADMIN_REST_NAMESPACE,
            '/admin/pending/(?P<id>\d+)/approve',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_approve' ),
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function ( $param ) {
                            return \is_numeric( $param );
                        },
                    ),
                ),
            )
        );

        register_rest_route(
            APOLLO_ADMIN_REST_NAMESPACE,
            '/admin/pending/(?P<id>\d+)/reject',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_reject' ),
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function ( $param ) {
                            return \is_numeric( $param );
                        },
                    ),
                ),
            )
        );
    }

    public function rest_get_pending( \WP_REST_Request $request ): \WP_REST_Response {
        $cpts  = array( 'event', 'classified', 'dj', 'hub', 'local', 'page', 'post' );
        $posts = get_posts(
            array(
                'post_type'      => $cpts,
                'post_status'    => array( 'pending', 'draft' ),
                'posts_per_page' => 100,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        $items = array();
        foreach ( $posts as $p ) {
            $items[] = array(
                'id'        => $p->ID,
                'title'     => $p->post_title,
                'type'      => $p->post_type,
                'status'    => $p->post_status,
                'author'    => get_the_author_meta( 'display_name', $p->post_author ),
                'date'      => $p->post_date,
                'edit_url'  => get_edit_post_link( $p->ID, 'raw' ),
                'permalink' => get_permalink( $p->ID ),
            );
        }

        return new \WP_REST_Response( $items, 200 );
    }

    public function rest_approve( \WP_REST_Request $request ): \WP_REST_Response {
        $post_id = absint( $request->get_param( 'id' ) );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new \WP_REST_Response( array( 'message' => 'Post not found.' ), 404 );
        }

        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
        do_action( 'apollo/admin/post_approved', $post_id, $post->post_type );

        return new \WP_REST_Response( array( 'message' => 'Post approved.', 'id' => $post_id ), 200 );
    }

    public function rest_reject( \WP_REST_Request $request ): \WP_REST_Response {
        $post_id = absint( $request->get_param( 'id' ) );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new \WP_REST_Response( array( 'message' => 'Post not found.' ), 404 );
        }

        wp_trash_post( $post_id );
        do_action( 'apollo/admin/post_rejected', $post_id, $post->post_type );

        return new \WP_REST_Response( array( 'message' => 'Post rejected.', 'id' => $post_id ), 200 );
    }

    /* ── AJAX Handlers ───────────────────────────────────────────── */

    public function ajax_approve(): void {
        check_ajax_referer( 'apollo_pending_action', '_wpnonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'No permission.' ), 403 );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $post    = get_post( $post_id );

        if ( ! $post ) {
            wp_send_json_error( array( 'message' => 'Post not found.' ), 404 );
        }

        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
        do_action( 'apollo/admin/post_approved', $post_id, $post->post_type );

        wp_send_json_success( array( 'message' => 'Post approved.', 'id' => $post_id ) );
    }

    public function ajax_reject(): void {
        check_ajax_referer( 'apollo_pending_action', '_wpnonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'No permission.' ), 403 );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $post    = get_post( $post_id );

        if ( ! $post ) {
            wp_send_json_error( array( 'message' => 'Post not found.' ), 404 );
        }

        wp_trash_post( $post_id );
        do_action( 'apollo/admin/post_rejected', $post_id, $post->post_type );

        wp_send_json_success( array( 'message' => 'Post rejected.', 'id' => $post_id ) );
    }
}
