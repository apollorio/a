<?php
/**
 * Module: Proj_Equip — Backend Controller
 *
 * Equipamentos: manage equipment/gear linked to events.
 * Stores in activity log as equipment entries (lightweight).
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

class Proj_Equip extends Base_Module {

    public function get_slug(): string {
        return 'proj-equip';
    }

    public function get_label(): string {
        return 'Equipamentos';
    }

    public function register(): void {
        $this->add_ajax( 'load_equipment', 'ajax_load_equipment' );
        $this->add_ajax( 'save_equipment', 'ajax_save_equipment' );
    }

    /**
     * AJAX: Load equipment list for event
     */
    public function ajax_load_equipment(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        $equipment = get_post_meta( $event_id, '_gestor_equipment', true );
        if ( ! is_array( $equipment ) ) {
            $equipment = [];
        }

        $this->json_success( $equipment );
    }

    /**
     * AJAX: Save equipment list (overwrite)
     */
    public function ajax_save_equipment(): void {
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

        $raw = isset( $_POST['items'] ) ? $_POST['items'] : [];
        if ( ! is_array( $raw ) ) {
            $raw = [];
        }

        $sanitized = [];
        foreach ( $raw as $item ) {
            $sanitized[] = [
                'name'     => sanitize_text_field( $item['name'] ?? '' ),
                'qty'      => absint( $item['qty'] ?? 1 ),
                'category' => sanitize_text_field( $item['category'] ?? '' ),
                'status'   => sanitize_text_field( $item['status'] ?? 'pending' ),
                'supplier' => sanitize_text_field( $item['supplier'] ?? '' ),
                'notes'    => sanitize_text_field( $item['notes'] ?? '' ),
            ];
        }

        update_post_meta( $event_id, '_gestor_equipment', $sanitized );

        $this->json_success( [ 'saved' => true, 'count' => count( $sanitized ) ] );
    }
}
