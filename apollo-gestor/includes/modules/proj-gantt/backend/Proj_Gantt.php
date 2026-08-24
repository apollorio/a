<?php
/**
 * Module: Proj_Gantt — Backend Controller
 *
 * Cronograma/Timeline: milestones, phase bars for amCharts Gantt.
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Modules;

use Apollo\Gestor\Core\Base_Module;
use Apollo\Gestor\Model\Milestone;
use Apollo\Gestor\Model\Task;
use Apollo\Gestor\Model\Team;
use Apollo\Gestor\Model\Activity;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Proj_Gantt extends Base_Module {

    public function get_slug(): string {
        return 'proj-gantt';
    }

    public function get_label(): string {
        return 'Cronograma';
    }

    public function register(): void {
        $this->add_ajax( 'load_milestones', 'ajax_load_milestones' );
        $this->add_ajax( 'create_milestone', 'ajax_create_milestone' );
        $this->add_ajax( 'toggle_milestone', 'ajax_toggle_milestone' );
        $this->add_ajax( 'load_gantt_data', 'ajax_load_gantt_data' );
    }

    /**
     * AJAX: Load milestones for event
     */
    public function ajax_load_milestones(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        $milestones = Milestone::get_by_event( $event_id );
        $progress   = Milestone::get_progress( $event_id );

        $this->json_success( [
            'milestones' => $milestones,
            'progress'   => $progress,
        ] );
    }

    /**
     * AJAX: Create milestone
     */
    public function ajax_create_milestone(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $title    = $this->post_param( 'title' );
        $due_date = $this->post_param( 'due_date' );
        $user_id  = get_current_user_id();

        if ( ! $event_id || ! $title ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $milestone_id = Milestone::create( [
            'event_id' => $event_id,
            'title'    => $title,
            'due_date' => $due_date,
        ] );

        if ( ! $milestone_id ) {
            $this->json_error( 'Erro ao criar milestone' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'milestone_created',
            'entity_type' => 'milestone',
            'entity_id'   => $milestone_id,
            'meta'        => wp_json_encode( [ 'title' => $title ] ),
        ] );

        $this->json_success( [ 'id' => $milestone_id ] );
    }

    /**
     * AJAX: Toggle milestone (pending ↔ done)
     */
    public function ajax_toggle_milestone(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $milestone_id = $this->post_int( 'milestone_id' );
        $event_id     = $this->post_int( 'event_id' );
        $user_id      = get_current_user_id();

        if ( ! $milestone_id ) {
            $this->json_error( 'milestone_id obrigatório' );
        }

        if ( $event_id && ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $new_status = Milestone::toggle( $milestone_id );
        if ( ! $new_status ) {
            $this->json_error( 'Erro ao alternar milestone' );
        }

        $this->json_success( [ 'status' => $new_status ] );
    }

    /**
     * AJAX: Load Gantt chart data (phases + tasks with dates for amCharts)
     */
    public function ajax_load_gantt_data(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        $tasks      = Task::get_by_event( $event_id );
        $milestones = Milestone::get_by_event( $event_id );
        $event_date = get_post_meta( $event_id, '_event_start_date', true );

        // Build phases from task categories/priorities
        $phases = [
            [
                'id'    => 'pre-production',
                'label' => 'Pré-Produção',
                'color' => '#6366f1',
            ],
            [
                'id'    => 'production',
                'label' => 'Produção',
                'color' => 'FF9820',
            ],
            [
                'id'    => 'post-production',
                'label' => 'Pós-Produção',
                'color' => '#16a34a',
            ],
        ];

        // Build gantt bars from tasks with due dates
        $bars = [];
        foreach ( $tasks as $task ) {
            if ( empty( $task['due_date'] ) ) {
                continue;
            }

            $created = $task['created_at'] ?? $task['due_date'];
            $bars[]  = [
                'id'       => (int) $task['id'],
                'title'    => $task['title'],
                'start'    => $created,
                'end'      => $task['due_date'],
                'status'   => $task['status'],
                'priority' => $task['priority'] ?? 'medium',
                'assignee' => ! empty( $task['assignee_id'] ) ? ( get_userdata( (int) $task['assignee_id'] )->display_name ?? '' ) : '',
            ];
        }

        // Milestones as diamond markers
        $markers = [];
        foreach ( $milestones as $ms ) {
            if ( ! empty( $ms['due_date'] ) ) {
                $markers[] = [
                    'id'     => (int) $ms['id'],
                    'title'  => $ms['title'],
                    'date'   => $ms['due_date'],
                    'status' => $ms['status'],
                ];
            }
        }

        $this->json_success( [
            'phases'     => $phases,
            'bars'       => $bars,
            'markers'    => $markers,
            'event_date' => $event_date,
        ] );
    }
}
