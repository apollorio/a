<?php
/**
 * Module: Projetos — Backend Controller
 *
 * Handles project overview data, event loading, and overview stats.
 * Backend slug: proj | Frontend: projeto / projetos
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Modules;

use Apollo\Gestor\Core\Base_Module;
use Apollo\Gestor\Model\Task;
use Apollo\Gestor\Model\Team;
use Apollo\Gestor\Model\Activity;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Projetos extends Base_Module {

    public function get_slug(): string {
        return 'projetos';
    }

    public function get_label(): string {
        return 'Projetos';
    }

    public function register(): void {
        $this->add_ajax( 'load_events', 'ajax_load_events' );
        $this->add_ajax( 'load_overview', 'ajax_load_overview' );
        $this->add_ajax( 'update_event_status', 'ajax_update_event_status' );
        $this->add_ajax( 'load_activity', 'ajax_load_activity' );
    }

    /**
     * AJAX: Load events for selector + kanban
     */
    public function ajax_load_events(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $user_id  = get_current_user_id();
        $is_admin = $this->is_admin();

        $args = [
            'post_type'      => 'event',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value',
            'meta_key'       => '_event_start_date',
            'order'          => 'ASC',
        ];

        if ( ! $is_admin ) {
            $event_ids = Team::get_user_event_ids( $user_id );
            if ( empty( $event_ids ) ) {
                $this->json_success( [] );
            }
            $args['post__in'] = $event_ids;
        }

        $query  = new \WP_Query( $args );
        $events = [];

        foreach ( $query->posts as $post ) {
            $event_id   = $post->ID;
            $status     = get_post_meta( $event_id, '_event_status', true ) ?: 'planned';
            $start_date = get_post_meta( $event_id, '_event_start_date', true );
            $loc_id     = (int) get_post_meta( $event_id, '_event_loc_id', true );
            $loc_name   = $loc_id ? get_the_title( $loc_id ) : '';
            $budget     = (float) get_post_meta( $event_id, '_event_budget', true );
            $tasks      = Task::get_counts( $event_id );
            $team       = Team::get_by_event( $event_id );
            $progress   = $tasks['total'] > 0 ? round( ( $tasks['delivered'] / $tasks['total'] ) * 100 ) : 0;

            $team_avatars = [];
            foreach ( array_slice( $team, 0, 3 ) as $member ) {
                $team_avatars[] = $member['avatar_url'] ?? '';
            }

            $events[] = [
                'id'           => $event_id,
                'title'        => $post->post_title,
                'status'       => $status,
                'start_date'   => $start_date,
                'start_fmt'    => $start_date ? wp_date( 'd M', strtotime( $start_date ) ) : '',
                'loc_name'     => $loc_name,
                'budget'       => $budget,
                'budget_fmt'   => 'R$ ' . number_format( $budget, 0, ',', '.' ),
                'tasks'        => $tasks,
                'progress'     => $progress,
                'team_count'   => count( $team ),
                'team_avatars' => $team_avatars,
                'can_manage'   => $is_admin || Team::can_manage( $user_id, $event_id ),
                'can_finance'  => $is_admin || Team::can_view_finance( $user_id, $event_id ),
            ];
        }

        $this->json_success( $events );
    }

    /**
     * AJAX: Load overview aggregated stats
     */
    public function ajax_load_overview(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        global $wpdb;
        $prefix  = $wpdb->prefix;
        $user_id = get_current_user_id();

        $active_events = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$prefix}posts p
             INNER JOIN {$prefix}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_event_status'
             WHERE p.post_type = 'event' AND p.post_status = 'publish'
             AND pm.meta_value NOT IN ('delivered','canceled')"
        );

        $tasks_done = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$prefix}apollo_gestor_tasks WHERE status = 'delivered'"
        );

        $tasks_overdue = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$prefix}apollo_gestor_tasks
             WHERE status NOT IN ('delivered','canceled') AND due_date < %s",
            current_time( 'Y-m-d' )
        ) );

        $team_total = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$prefix}apollo_gestor_team"
        );

        $total_budget = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(meta_value), 0) FROM {$prefix}postmeta
             WHERE meta_key = '_event_budget'"
        );

        $this->json_success( [
            'active_events' => $active_events,
            'tasks_done'    => $tasks_done,
            'tasks_overdue' => $tasks_overdue,
            'team_total'    => $team_total,
            'total_budget'  => $total_budget,
            'budget_fmt'    => 'R$ ' . number_format( $total_budget, 0, ',', '.' ),
        ] );
    }

    /**
     * AJAX: Update event status (Kanban drag)
     */
    public function ajax_update_event_status(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $status   = $this->post_param( 'status' );
        $user_id  = get_current_user_id();

        if ( ! $event_id || ! $status ) {
            $this->json_error( 'Dados incompletos' );
        }

        $valid = [ 'planned', 'tostart', 'ongoing', 'delayed', 'canceled', 'delivered' ];
        if ( ! in_array( $status, $valid, true ) ) {
            $this->json_error( 'Status inválido' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        update_post_meta( $event_id, '_event_status', $status );

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'status_changed',
            'entity_type' => 'event',
            'entity_id'   => $event_id,
            'meta'        => wp_json_encode( [ 'status' => $status ] ),
        ] );

        do_action( 'apollo/gestor/event_status_changed', $event_id, $status, $user_id );

        $this->json_success( [ 'status' => $status ] );
    }

    /**
     * AJAX: Load activity feed
     */
    public function ajax_load_activity(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $page     = max( 1, $this->post_int( 'page', 1 ) );
        $per_page = 20;

        if ( $event_id ) {
            $items = Activity::get_by_event( $event_id, $per_page, ( $page - 1 ) * $per_page );
        } else {
            $items = Activity::get_by_user( get_current_user_id(), $per_page );
        }

        $this->json_success( $items );
    }
}
