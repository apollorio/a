<?php
/**
 * Draft Handler — creates CPT drafts from the luxury modal form.
 *
 * Supports: event, dj, hub, local, classified CPTs + report form.
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class DraftHandler {

    /**
     * Handle CPT draft creation via AJAX.
     */
    public static function handle(): void {
        if (
            ! isset( $_POST['_wpnonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'apollo_admin_create_draft' )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'No permission.' ), 403 );
        }

        $form_id = isset( $_POST['apollo_form_id'] ) ? sanitize_key( $_POST['apollo_form_id'] ) : '';
        $cpt     = isset( $_POST['apollo_cpt'] ) ? sanitize_key( $_POST['apollo_cpt'] ) : '';

        // Report form (no CPT)
        if ( $form_id === 'report' || empty( $cpt ) ) {
            self::handle_report();
            return;
        }

        // Validate CPT
        $allowed_cpts = array( 'event', 'dj', 'hub', 'local', 'classified' );
        if ( ! in_array( $cpt, $allowed_cpts, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid CPT: ' . $cpt ), 400 );
        }

        $post_title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $post_content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

        if ( empty( $post_title ) ) {
            wp_send_json_error( array( 'message' => 'Title is required.' ), 400 );
        }

        $post_id = wp_insert_post(
            array(
                'post_type'    => $cpt,
                'post_title'   => $post_title,
                'post_content' => $post_content,
                'post_status'  => 'draft',
                'post_author'  => get_current_user_id(),
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => $post_id->get_error_message() ), 500 );
        }

        self::save_meta( $post_id );

        do_action( 'apollo/admin/draft_created', $post_id, $cpt );

        wp_send_json_success(
            array(
                'message'  => 'Draft created.',
                'post_id'  => $post_id,
                'edit_url' => get_edit_post_link( $post_id, 'raw' ),
            )
        );
    }

    /**
     * Handle report form submission (no CPT).
     */
    private static function handle_report(): void {
        $report_data = array(
            'name'    => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
            'email'   => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
            'subject' => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
            'message' => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
        );

        do_action( 'apollo/admin/report_submitted', $report_data );

        wp_send_json_success( array( 'message' => 'Report submitted.' ) );
    }

    /**
     * Save meta keys for the created post using prefix-based sanitizers.
     */
    private static function save_meta( int $post_id ): void {
        $sanitizers = self::get_meta_sanitizers();

        foreach ( $sanitizers as $meta_key => $callback ) {
            if ( isset( $_POST[ $meta_key ] ) ) {
                $raw   = wp_unslash( $_POST[ $meta_key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
                $value = call_user_func( $callback, $raw );
                update_post_meta( $post_id, $meta_key, $value );
            }
        }
    }

    /**
     * Return the map of meta keys → sanitizer callbacks.
     */
    private static function get_meta_sanitizers(): array {
        return array(
            // Event meta
            '_event_start_date'      => 'sanitize_text_field',
            '_event_end_date'        => 'sanitize_text_field',
            '_event_start_time'      => 'sanitize_text_field',
            '_event_end_time'        => 'sanitize_text_field',
            '_event_loc_id'          => 'absint',
            '_event_ticket_url'      => 'esc_url_raw',
            '_event_ticket_price'    => 'sanitize_text_field',
            '_event_coupon_code'     => 'sanitize_text_field',
            '_event_list_url'        => 'esc_url_raw',
            '_event_video_url'       => static function ( $value ) {
                if ( \function_exists( 'Apollo\\Event\\apollo_event_sanitize_video_url' ) ) {
                    return \Apollo\Event\apollo_event_sanitize_video_url( $value );
                }
                return esc_url_raw( (string) $value );
            },
            '_event_privacy'         => 'sanitize_key',
            '_event_status'          => 'sanitize_key',
            // DJ meta
            '_dj_bio_short'          => 'sanitize_text_field',
            '_dj_instagram'          => 'sanitize_text_field',
            '_dj_soundcloud'         => 'esc_url_raw',
            '_dj_spotify'            => 'esc_url_raw',
            '_dj_youtube'            => 'esc_url_raw',
            '_dj_mixcloud'           => 'esc_url_raw',
            '_dj_website'            => 'esc_url_raw',
            '_dj_user_id'            => 'absint',
            '_dj_verified'           => 'absint',
            // Classified meta
            '_classified_price'      => 'sanitize_text_field',
            '_classified_currency'   => 'sanitize_text_field',
            '_classified_negotiable' => 'absint',
            '_classified_condition'  => 'sanitize_key',
            // Local meta
            '_local_address'         => 'sanitize_text_field',
            '_local_city'            => 'sanitize_text_field',
            '_local_lat'             => 'sanitize_text_field',
            '_local_lng'             => 'sanitize_text_field',
            '_local_capacity'        => 'absint',
            '_local_phone'           => 'sanitize_text_field',
            '_local_instagram'       => 'sanitize_text_field',
            '_local_website'         => 'esc_url_raw',
        );
    }
}
