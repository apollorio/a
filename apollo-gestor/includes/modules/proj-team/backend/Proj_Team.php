<?php
/**
 * Module: Proj_Team — Backend Controller
 *
 * Team/Equipe management: add, remove, update role, list members.
 * Roles hierarchy: adm > gestor > tgestor > team
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Modules;

use Apollo\Gestor\Core\Base_Module;
use Apollo\Gestor\Model\Team;
use Apollo\Gestor\Model\Activity;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Proj_Team extends Base_Module {

    public function get_slug(): string {
        return 'proj-team';
    }

    public function get_label(): string {
        return 'Equipe';
    }

    public function register(): void {
        $this->add_ajax( 'load_team', 'ajax_load_team' );
        $this->add_ajax( 'add_team_member', 'ajax_add_team_member' );
        $this->add_ajax( 'update_team_member', 'ajax_update_team_member' );
        $this->add_ajax( 'remove_team_member', 'ajax_remove_team_member' );
        $this->add_ajax( 'search_users', 'ajax_search_users' );
    }

    /**
     * AJAX: Load team members for event
     */
    public function ajax_load_team(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        $members = Team::get_by_event( $event_id );

        $role_order = [ 'adm' => 0, 'gestor' => 1, 'tgestor' => 2, 'team' => 3 ];
        usort( $members, function ( $a, $b ) use ( $role_order ) {
            $ra = $role_order[ $a['role'] ] ?? 4;
            $rb = $role_order[ $b['role'] ] ?? 4;
            return $ra - $rb;
        } );

        $this->json_success( $members );
    }

    /**
     * AJAX: Add team member to event
     */
    public function ajax_add_team_member(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id   = $this->post_int( 'event_id' );
        $member_id  = $this->post_int( 'user_id' );
        $role       = $this->post_param( 'role', 'team' );
        $user_id    = get_current_user_id();

        if ( ! $event_id || ! $member_id ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $valid_roles = [ 'adm', 'gestor', 'tgestor', 'team' ];
        if ( ! in_array( $role, $valid_roles, true ) ) {
            $role = 'team';
        }

        $result = Team::add( [
            'event_id' => $event_id,
            'user_id'  => $member_id,
            'role'     => $role,
        ] );

        if ( ! $result ) {
            $this->json_error( 'Erro ao adicionar membro (já existe?)' );
        }

        $user = get_userdata( $member_id );
        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'member_added',
            'entity_type' => 'team',
            'entity_id'   => $member_id,
            'meta'        => wp_json_encode( [
                'name' => $user ? $user->display_name : '',
                'role' => $role,
            ] ),
        ] );

        $this->json_success( [ 'added' => true ] );
    }

    /**
     * AJAX: Update member role
     */
    public function ajax_update_team_member(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id  = $this->post_int( 'event_id' );
        $member_id = $this->post_int( 'user_id' );
        $role      = $this->post_param( 'role' );
        $user_id   = get_current_user_id();

        if ( ! $event_id || ! $member_id || ! $role ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $valid_roles = [ 'adm', 'gestor', 'tgestor', 'team' ];
        if ( ! in_array( $role, $valid_roles, true ) ) {
            $this->json_error( 'Cargo inválido' );
        }

        $updated = Team::update( $event_id, $member_id, [ 'role' => $role ] );
        if ( ! $updated ) {
            $this->json_error( 'Erro ao atualizar membro' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'member_role_changed',
            'entity_type' => 'team',
            'entity_id'   => $member_id,
            'meta'        => wp_json_encode( [ 'role' => $role ] ),
        ] );

        $this->json_success( [ 'updated' => true ] );
    }

    /**
     * AJAX: Remove team member
     */
    public function ajax_remove_team_member(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id  = $this->post_int( 'event_id' );
        $member_id = $this->post_int( 'user_id' );
        $user_id   = get_current_user_id();

        if ( ! $event_id || ! $member_id ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $removed = Team::remove( $event_id, $member_id );
        if ( ! $removed ) {
            $this->json_error( 'Erro ao remover membro' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'member_removed',
            'entity_type' => 'team',
            'entity_id'   => $member_id,
        ] );

        $this->json_success( [ 'removed' => true ] );
    }

    /**
     * AJAX: Search users (for typeahead add member)
     */
    public function ajax_search_users(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $q = $this->post_param( 'q' );
        if ( strlen( $q ) < 2 ) {
            $this->json_success( [] );
        }

        $users = get_users( [
            'search'         => '*' . $q . '*',
            'search_columns' => [ 'user_login', 'display_name', 'user_email' ],
            'number'         => 10,
        ] );

        $results = [];
        foreach ( $users as $user ) {
            $results[] = [
                'id'     => $user->ID,
                'name'   => $user->display_name,
                'email'  => $user->user_email,
                'avatar' => get_avatar_url( $user->ID, [ 'size' => 32 ] ),
            ];
        }

        $this->json_success( $results );
    }
}
