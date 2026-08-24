<?php
/**
 * Module: Editor — Backend Controller
 *
 * In-place content editor for event descriptions, documents.
 * Uses event post_content as main editable body.
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

class Editor extends Base_Module {

    public function get_slug(): string {
        return 'editor';
    }

    public function get_label(): string {
        return 'Editor';
    }

    public function register(): void {
        $this->add_ajax( 'load_doc', 'ajax_load_doc' );
        $this->add_ajax( 'save_doc', 'ajax_save_doc' );
    }

    /**
     * AJAX: Load document content
     */
    public function ajax_load_doc(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        $post = get_post( $event_id );
        if ( ! $post || $post->post_type !== 'event' ) {
            $this->json_error( 'Evento não encontrado' );
        }

        $docs = get_post_meta( $event_id, '_gestor_docs', true );
        if ( ! is_array( $docs ) ) {
            $docs = [];
        }

        $this->json_success( [
            'content' => $post->post_content,
            'docs'    => $docs,
            'title'   => $post->post_title,
        ] );
    }

    /**
     * AJAX: Save document content
     */
    public function ajax_save_doc(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $content  = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
        $user_id  = get_current_user_id();

        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        wp_update_post( [
            'ID'           => $event_id,
            'post_content' => $content,
        ] );

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'doc_edited',
            'entity_type' => 'event',
            'entity_id'   => $event_id,
        ] );

        $this->json_success( [ 'saved' => true ] );
    }
}
