<?php
/**
 * Module: Proj_Staff — Backend Controller
 *
 * Suppliers (Fornecedores) panel: load supplier CPTs linked to events.
 * CPT 'supplier' is consumed from ecosystem (registered by apollo-core).
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Modules;

use Apollo\Gestor\Core\Base_Module;
use Apollo\Gestor\Model\Team;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Proj_Staff extends Base_Module {

    public function get_slug(): string {
        return 'proj-staff';
    }

    public function get_label(): string {
        return 'Staff';
    }

    public function register(): void {
        $this->add_ajax( 'load_suppliers', 'ajax_load_suppliers' );
        $this->add_ajax( 'link_supplier', 'ajax_link_supplier' );
        $this->add_ajax( 'unlink_supplier', 'ajax_unlink_supplier' );
    }

    /**
     * AJAX: Load suppliers linked to event
     */
    public function ajax_load_suppliers(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        $linked_ids = get_post_meta( $event_id, '_event_supplier_ids', true );
        if ( ! is_array( $linked_ids ) ) {
            $linked_ids = [];
        }

        $suppliers = [];
        if ( ! empty( $linked_ids ) ) {
            $query = new \WP_Query( [
                'post_type'      => 'supplier',
                'post__in'       => $linked_ids,
                'posts_per_page' => 50,
                'post_status'    => 'publish',
            ] );

            foreach ( $query->posts as $post ) {
                $suppliers[] = [
                    'id'       => $post->ID,
                    'name'     => $post->post_title,
                    'category' => get_post_meta( $post->ID, '_supplier_category', true ),
                    'phone'    => get_post_meta( $post->ID, '_supplier_phone', true ),
                    'email'    => get_post_meta( $post->ID, '_supplier_email', true ),
                    'avatar'   => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: '',
                ];
            }
        }

        // Also get all available suppliers for linking
        $all_query = new \WP_Query( [
            'post_type'      => 'supplier',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $available = [];
        foreach ( $all_query->posts as $post ) {
            if ( ! in_array( $post->ID, $linked_ids, true ) ) {
                $available[] = [
                    'id'       => $post->ID,
                    'name'     => $post->post_title,
                    'category' => get_post_meta( $post->ID, '_supplier_category', true ),
                ];
            }
        }

        $this->json_success( [
            'linked'    => $suppliers,
            'available' => $available,
        ] );
    }

    /**
     * AJAX: Link supplier to event
     */
    public function ajax_link_supplier(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id    = $this->post_int( 'event_id' );
        $supplier_id = $this->post_int( 'supplier_id' );
        $user_id     = get_current_user_id();

        if ( ! $event_id || ! $supplier_id ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $linked_ids = get_post_meta( $event_id, '_event_supplier_ids', true );
        if ( ! is_array( $linked_ids ) ) {
            $linked_ids = [];
        }

        if ( in_array( $supplier_id, $linked_ids, true ) ) {
            $this->json_error( 'Fornecedor já vinculado' );
        }

        $linked_ids[] = $supplier_id;
        update_post_meta( $event_id, '_event_supplier_ids', $linked_ids );

        $this->json_success( [ 'linked' => true ] );
    }

    /**
     * AJAX: Unlink supplier from event
     */
    public function ajax_unlink_supplier(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id    = $this->post_int( 'event_id' );
        $supplier_id = $this->post_int( 'supplier_id' );
        $user_id     = get_current_user_id();

        if ( ! $event_id || ! $supplier_id ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $linked_ids = get_post_meta( $event_id, '_event_supplier_ids', true );
        if ( ! is_array( $linked_ids ) ) {
            $linked_ids = [];
        }

        $linked_ids = array_values( array_diff( $linked_ids, [ $supplier_id ] ) );
        update_post_meta( $event_id, '_event_supplier_ids', $linked_ids );

        $this->json_success( [ 'unlinked' => true ] );
    }
}
