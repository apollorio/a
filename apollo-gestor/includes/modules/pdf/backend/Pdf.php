<?php
/**
 * Module: Pdf — Backend Controller
 *
 * PDF export: generate project summary, budget sheets, task lists.
 * Uses server-side HTML rendering → dompdf or browser print.
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Modules;

use Apollo\Gestor\Core\Base_Module;
use Apollo\Gestor\Model\Task;
use Apollo\Gestor\Model\Team;
use Apollo\Gestor\Model\Payment;
use Apollo\Gestor\Model\Milestone;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Pdf extends Base_Module {

    public function get_slug(): string {
        return 'pdf';
    }

    public function get_label(): string {
        return 'PDF';
    }

    public function register(): void {
        $this->add_ajax( 'export_pdf', 'ajax_export_pdf' );
    }

    /**
     * AJAX: Generate PDF data (returns HTML for browser print)
     */
    public function ajax_export_pdf(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $section  = $this->post_param( 'section', 'summary' );
        $user_id  = get_current_user_id();

        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        if ( ! $this->is_admin() && ! Team::can_view_finance( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $post = get_post( $event_id );
        if ( ! $post ) {
            $this->json_error( 'Evento não encontrado' );
        }

        $data = [
            'title'      => $post->post_title,
            'status'     => get_post_meta( $event_id, '_event_status', true ),
            'start_date' => get_post_meta( $event_id, '_event_start_date', true ),
            'loc_name'   => '',
        ];

        $loc_id = (int) get_post_meta( $event_id, '_event_loc_id', true );
        if ( $loc_id ) {
            $data['loc_name'] = get_the_title( $loc_id );
        }

        switch ( $section ) {
            case 'budget':
                $data['payments'] = Payment::get_by_event( $event_id );
                $data['summary']  = Payment::get_summary( $event_id );
                break;

            case 'tasks':
                $data['tasks']  = Task::get_by_event( $event_id );
                $data['counts'] = Task::get_counts( $event_id );
                break;

            case 'team':
                $data['team'] = Team::get_by_event( $event_id );
                break;

            case 'timeline':
                $data['milestones'] = Milestone::get_by_event( $event_id );
                $data['progress']   = Milestone::get_progress( $event_id );
                break;

            case 'summary':
            default:
                $data['tasks']      = Task::get_counts( $event_id );
                $data['team']       = Team::get_by_event( $event_id );
                $data['payments']   = Payment::get_summary( $event_id );
                $data['milestones'] = Milestone::get_progress( $event_id );
                break;
        }

        $data['generated_at'] = wp_date( 'd/m/Y H:i' );
        $data['generated_by'] = wp_get_current_user()->display_name;

        $this->json_success( $data );
    }
}
