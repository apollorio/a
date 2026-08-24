<?php
/**
 * Module: Tarefas — Backend Controller
 *
 * KANBAN tasks: by User, by Project, drag, priority, due date.
 * 6 columns: planned, tostart, ongoing, delayed, canceled, delivered
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

class Tarefas extends Base_Module {

    public function get_slug(): string {
        return 'tarefas';
    }

    public function get_label(): string {
        return 'Tarefas';
    }

    public function register(): void {
        $this->add_ajax( 'load_tasks', 'ajax_load_tasks' );
        $this->add_ajax( 'create_task', 'ajax_create_task' );
        $this->add_ajax( 'update_task', 'ajax_update_task' );
        $this->add_ajax( 'toggle_task', 'ajax_toggle_task' );
        $this->add_ajax( 'delete_task', 'ajax_delete_task' );
        $this->add_ajax( 'reorder_tasks', 'ajax_reorder_tasks' );
        $this->add_ajax( 'load_task_detail', 'ajax_load_task_detail' );
    }

    /**
     * AJAX: Load tasks for event (Kanban columns)
     */
    public function ajax_load_tasks(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        $tasks  = Task::get_by_event( $event_id );
        $counts = Task::get_counts( $event_id );

        $columns = [
            'planned'   => [],
            'tostart'   => [],
            'ongoing'   => [],
            'delayed'   => [],
            'canceled'  => [],
            'delivered' => [],
        ];

        foreach ( $tasks as $task ) {
            $status = $task['status'] ?? 'planned';
            if ( ! isset( $columns[ $status ] ) ) {
                $status = 'planned';
            }

            $assignee = null;
            if ( ! empty( $task['assignee_id'] ) ) {
                $user     = get_userdata( (int) $task['assignee_id'] );
                $assignee = $user ? [
                    'id'     => $user->ID,
                    'name'   => $user->display_name,
                    'avatar' => get_avatar_url( $user->ID, [ 'size' => 32 ] ),
                ] : null;
            }

            $task['assignee']  = $assignee;
            $task['overdue']   = ! empty( $task['due_date'] )
                && $task['status'] !== 'delivered'
                && $task['status'] !== 'canceled'
                && strtotime( $task['due_date'] ) < time();

            $columns[ $status ][] = $task;
        }

        $this->json_success( [
            'columns' => $columns,
            'counts'  => $counts,
        ] );
    }

    /**
     * AJAX: Create new task
     */
    public function ajax_create_task(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $title    = $this->post_param( 'title' );
        $user_id  = get_current_user_id();

        if ( ! $event_id || ! $title ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $data = [
            'event_id'         => $event_id,
            'title'            => $title,
            'description'      => $this->post_param( 'description' ),
            'assignee_id'      => $this->post_int( 'assignee_id' ),
            'due_date'         => $this->post_param( 'due_date' ),
            'priority'         => $this->post_param( 'priority', 'medium' ),
            'status'           => $this->post_param( 'status', 'planned' ),
            'reminder_enabled' => $this->post_int( 'reminder_enabled' ),
            'reminder_offset'  => $this->post_param( 'reminder_offset', '24h' ),
            'created_by'       => $user_id,
        ];

        $task_id = Task::create( $data );
        if ( ! $task_id ) {
            $this->json_error( 'Erro ao criar tarefa' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'task_created',
            'entity_type' => 'task',
            'entity_id'   => $task_id,
            'meta'        => wp_json_encode( [ 'title' => $title ] ),
        ] );

        $this->json_success( [ 'id' => $task_id ] );
    }

    /**
     * AJAX: Update task (title, status, priority, due_date, assignee_id)
     */
    public function ajax_update_task(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $task_id  = $this->post_int( 'task_id' );
        $event_id = $this->post_int( 'event_id' );
        $user_id  = get_current_user_id();

        if ( ! $task_id || ! $event_id ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $fields = [];
        $allowed = [ 'title', 'description', 'status', 'priority', 'due_date', 'assignee_id', 'reminder_enabled', 'reminder_offset' ];
        foreach ( $allowed as $key ) {
            $val = $this->post_param( $key );
            if ( $val !== '' ) {
                $fields[ $key ] = in_array( $key, [ 'assignee_id', 'reminder_enabled' ], true ) ? absint( $val ) : $val;
            }
        }

        if ( empty( $fields ) ) {
            $this->json_error( 'Nenhum campo para atualizar' );
        }

        $updated = Task::update( $task_id, $fields );
        if ( ! $updated ) {
            $this->json_error( 'Erro ao atualizar tarefa' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'task_updated',
            'entity_type' => 'task',
            'entity_id'   => $task_id,
            'meta'        => wp_json_encode( $fields ),
        ] );

        $this->json_success( [ 'updated' => true ] );
    }

    /**
     * AJAX: Toggle task status (planned ↔ done)
     */
    public function ajax_toggle_task(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $task_id  = $this->post_int( 'task_id' );
        $event_id = $this->post_int( 'event_id' );
        $user_id  = get_current_user_id();

        if ( ! $task_id ) {
            $this->json_error( 'task_id obrigatório' );
        }

        if ( $event_id && ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $new_status = Task::toggle( $task_id );
        if ( ! $new_status ) {
            $this->json_error( 'Erro ao alternar tarefa' );
        }

        $this->json_success( [ 'status' => $new_status ] );
    }

    /**
     * AJAX: Delete task
     */
    public function ajax_delete_task(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $task_id  = $this->post_int( 'task_id' );
        $event_id = $this->post_int( 'event_id' );
        $user_id  = get_current_user_id();

        if ( ! $task_id ) {
            $this->json_error( 'task_id obrigatório' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $deleted = Task::delete( $task_id );
        if ( ! $deleted ) {
            $this->json_error( 'Erro ao remover tarefa' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'task_deleted',
            'entity_type' => 'task',
            'entity_id'   => $task_id,
        ] );

        $this->json_success( [ 'deleted' => true ] );
    }

    /**
     * AJAX: Reorder tasks within a column (drag & drop)
     */
    public function ajax_reorder_tasks(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $user_id  = get_current_user_id();

        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        // order = [ { id: 123, status: 'ongoing', position: 0 }, ... ]
        $raw = isset( $_POST['order'] ) ? $_POST['order'] : [];
        if ( ! is_array( $raw ) || empty( $raw ) ) {
            $this->json_error( 'Dados de ordem inválidos' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'apollo_gestor_tasks';

        foreach ( $raw as $item ) {
            $id       = absint( $item['id'] ?? 0 );
            $status   = sanitize_text_field( $item['status'] ?? '' );
            $position = absint( $item['position'] ?? 0 );

            if ( $id && $status ) {
                $wpdb->update(
                    $table,
                    [
                        'status'     => $status,
                        'sort_order' => $position,
                    ],
                    [ 'id' => $id ],
                    [ '%s', '%d' ],
                    [ '%d' ]
                );
            }
        }

        $this->json_success( [ 'reordered' => true ] );
    }

    /**
     * AJAX: Load single task detail (for edit modal).
     */
    public function ajax_load_task_detail(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $task_id = $this->post_int( 'task_id' );
        if ( ! $task_id ) {
            $this->json_error( 'task_id obrigatório' );
        }

        $task = Task::get( $task_id );
        if ( ! $task ) {
            $this->json_error( 'Tarefa não encontrada', 404 );
        }

        $user_id  = get_current_user_id();
        $event_id = absint( $task['event_id'] );

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $this->json_success( $task );
    }
}
