<?php
/**
 * Module: Proj_Board — Backend Controller
 *
 * Mural: project notes board, announcements, pinned messages.
 * Lightweight activity-based module — stores in activity log as board_post type.
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Modules;

use Apollo\Gestor\Core\Base_Module;
use Apollo\Gestor\Model\Activity;
use Apollo\Gestor\Model\Team;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Proj_Board extends Base_Module {

    public function get_slug(): string {
        return 'proj-board';
    }

    public function get_label(): string {
        return 'Mural';
    }

    public function register(): void {
        $this->add_ajax( 'load_board', 'ajax_load_board' );
        $this->add_ajax( 'post_board', 'ajax_post_board' );
        $this->add_ajax( 'delete_board_post', 'ajax_delete_board_post' );
    }

    /**
     * AJAX: Load board posts for event
     */
    public function ajax_load_board(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'apollo_gestor_activity';

        $posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.*, u.display_name, u.user_email
             FROM {$table} a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.event_id = %d AND a.action = 'board_post'
             ORDER BY a.created_at DESC
             LIMIT 50",
            $event_id
        ), ARRAY_A );

        foreach ( $posts as &$post ) {
            $post['avatar']   = get_avatar_url( (int) $post['user_id'], [ 'size' => 40 ] );
            $post['time_ago'] = human_time_diff( strtotime( $post['created_at'] ), current_time( 'timestamp' ) );
            $meta             = json_decode( $post['meta'] ?? '{}', true );
            $post['content']  = $meta['content'] ?? '';
            $post['pinned']   = ! empty( $meta['pinned'] );
            unset( $post['meta'] );
        }

        $this->json_success( $posts );
    }

    /**
     * AJAX: Create board post
     */
    public function ajax_post_board(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $content  = $this->post_param( 'content' );
        $user_id  = get_current_user_id();

        if ( ! $event_id || ! $content ) {
            $this->json_error( 'Dados incompletos' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'board_post',
            'entity_type' => 'board',
            'entity_id'   => 0,
            'meta'        => wp_json_encode( [
                'content' => wp_kses_post( $content ),
                'pinned'  => false,
            ] ),
        ] );

        $this->json_success( [ 'posted' => true ] );
    }

    /**
     * AJAX: Delete board post
     */
    public function ajax_delete_board_post(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $post_id  = $this->post_int( 'post_id' );
        $event_id = $this->post_int( 'event_id' );
        $user_id  = get_current_user_id();

        if ( ! $post_id ) {
            $this->json_error( 'post_id obrigatório' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        global $wpdb;
        $table   = $wpdb->prefix . 'apollo_gestor_activity';
        $deleted = $wpdb->delete( $table, [ 'id' => $post_id ], [ '%d' ] );

        if ( ! $deleted ) {
            $this->json_error( 'Erro ao remover post' );
        }

        $this->json_success( [ 'deleted' => true ] );
    }
}
