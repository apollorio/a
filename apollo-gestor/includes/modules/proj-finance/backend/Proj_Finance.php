<?php
/**
 * Module: Proj_Finance — Backend Controller
 *
 * Handles budget overview, payment CRUD, financial summary.
 * Tabs: Budget + Financeiro (two panels, one module)
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Modules;

use Apollo\Gestor\Core\Base_Module;
use Apollo\Gestor\Model\Payment;
use Apollo\Gestor\Model\Income;
use Apollo\Gestor\Model\Team;
use Apollo\Gestor\Model\Activity;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Proj_Finance extends Base_Module {

    public function get_slug(): string {
        return 'proj-finance';
    }

    public function get_label(): string {
        return 'Financeiro';
    }

    public function register(): void {
        $this->add_ajax( 'load_payments', 'ajax_load_payments' );
        $this->add_ajax( 'create_payment', 'ajax_create_payment' );
        $this->add_ajax( 'update_payment', 'ajax_update_payment' );
        $this->add_ajax( 'delete_payment', 'ajax_delete_payment' );
        $this->add_ajax( 'load_budget', 'ajax_load_budget' );

        // Income / Receitas handlers
        $this->add_ajax( 'load_income', 'ajax_load_income' );
        $this->add_ajax( 'create_income', 'ajax_create_income' );
        $this->add_ajax( 'delete_income', 'ajax_delete_income' );
    }

    /* ─────────────────────────────────────────────────────────────────
       INCOME / RECEITAS — stored in apollo_gestor_income table (DB v4+)
       Legacy _gestor_income_items post_meta was migrated on upgrade.
    ───────────────────────────────────────────────────────────────── */

    /**
     * AJAX: Load income items for event
     */
    public function ajax_load_income(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $user_id  = get_current_user_id();

        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        if ( ! $this->is_admin() && ! Team::can_view_finance( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão financeira', 403 );
        }

        $items = Income::get_by_event( $event_id );

        $this->json_success( [ 'items' => $items ] );
    }

    /**
     * AJAX: Create income item
     */
    public function ajax_create_income(): void {
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

        $description = sanitize_text_field( $this->post_param( 'description' ) );
        $amount      = $this->post_float( 'amount' );
        $date_raw    = $this->post_param( 'date' );
        $date        = $date_raw ? sanitize_text_field( $date_raw ) : '';
        $qty         = absint( $this->post_int( 'qty' ) ) ?: 1;
        $unit        = $this->post_float( 'unit' );
        $category    = $this->post_param( 'category', 'outros' );

        if ( ! $description || $amount <= 0 ) {
            $this->json_error( 'Descrição e valor são obrigatórios' );
        }

        $id = Income::create( [
            'event_id'    => $event_id,
            'category'    => $category,
            'description' => $description,
            'amount'      => $amount,
            'date'        => $date,
            'qty'         => $qty,
            'unit'        => $unit,
            'created_by'  => $user_id,
        ] );

        if ( ! $id ) {
            $this->json_error( 'Erro ao criar receita' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'income_created',
            'entity_type' => 'income',
            'entity_id'   => $id,
            'meta'        => wp_json_encode( [
                'desc'   => $description,
                'amount' => $amount,
                'cat'    => $category,
            ] ),
        ] );

        $item = Income::get_by_id( $id );
        $this->json_success( [ 'item' => $item ] );
    }

    /**
     * AJAX: Delete income item
     */
    public function ajax_delete_income(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id  = $this->post_int( 'event_id' );
        $income_id = $this->post_int( 'income_id' );
        $user_id   = get_current_user_id();

        if ( ! $event_id || ! $income_id ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $deleted = Income::delete( $income_id );
        if ( ! $deleted ) {
            $this->json_error( 'Erro ao remover receita' );
        }

        $this->json_success( [ 'deleted' => true ] );
    }

    /**
     * AJAX: Load all payments for event
     */
    public function ajax_load_payments(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $user_id  = get_current_user_id();

        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        if ( ! $this->is_admin() && ! Team::can_view_finance( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão financeira', 403 );
        }

        $payments = Payment::get_by_event( $event_id );
        $summary  = Payment::get_summary( $event_id );

        $this->json_success( [
            'payments' => $payments,
            'summary'  => $summary,
        ] );
    }

    /**
     * AJAX: Create payment
     */
    public function ajax_create_payment(): void {
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

        $data = [
            'event_id'    => $event_id,
            'category'    => $this->post_param( 'category', 'production' ),
            'description' => $this->post_param( 'description' ),
            'payee_type'  => $this->post_param( 'payee_type', 'manual' ),
            'payee_id'    => $this->post_int( 'payee_id' ),
            'payee_name'  => $this->post_param( 'payee_name' ),
            'amount'      => $this->post_float( 'amount' ),
            'due_date'    => $this->post_param( 'due_date' ),
            'status'      => $this->post_param( 'status', 'pending' ),
            'method'      => $this->post_param( 'method', 'pix' ),
            'pix_key'     => $this->post_param( 'pix_key' ),
            'notes'       => $this->post_param( 'notes' ),
            'created_by'  => $user_id,
        ];

        if ( ! $data['description'] || ! $data['amount'] ) {
            $this->json_error( 'Descrição e valor obrigatórios' );
        }

        $payment_id = Payment::create( $data );
        if ( ! $payment_id ) {
            $this->json_error( 'Erro ao criar pagamento' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'payment_created',
            'entity_type' => 'payment',
            'entity_id'   => $payment_id,
            'meta'        => wp_json_encode( [
                'desc'   => $data['description'],
                'amount' => $data['amount'],
            ] ),
        ] );

        $this->json_success( [ 'id' => $payment_id ] );
    }

    /**
     * AJAX: Update payment
     */
    public function ajax_update_payment(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $payment_id = $this->post_int( 'payment_id' );
        $event_id   = $this->post_int( 'event_id' );
        $user_id    = get_current_user_id();

        if ( ! $payment_id || ! $event_id ) {
            $this->json_error( 'Dados incompletos' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $fields  = [];
        $allowed = [
            'category', 'description', 'payee_type', 'payee_id',
            'payee_name', 'amount', 'paid_amount', 'due_date', 'status',
            'method', 'pix_key', 'notes',
        ];

        foreach ( $allowed as $key ) {
            $val = $this->post_param( $key );
            if ( $val !== '' ) {
                if ( in_array( $key, [ 'amount', 'paid_amount' ], true ) ) {
                    $fields[ $key ] = $this->post_float( $key );
                } elseif ( $key === 'payee_id' ) {
                    $fields[ $key ] = absint( $val );
                } else {
                    $fields[ $key ] = $val;
                }
            }
        }

        if ( empty( $fields ) ) {
            $this->json_error( 'Nenhum campo para atualizar' );
        }

        $updated = Payment::update( $payment_id, $fields );
        if ( ! $updated ) {
            $this->json_error( 'Erro ao atualizar pagamento' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'payment_updated',
            'entity_type' => 'payment',
            'entity_id'   => $payment_id,
            'meta'        => wp_json_encode( $fields ),
        ] );

        $this->json_success( [ 'updated' => true ] );
    }

    /**
     * AJAX: Delete payment
     */
    public function ajax_delete_payment(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $payment_id = $this->post_int( 'payment_id' );
        $event_id   = $this->post_int( 'event_id' );
        $user_id    = get_current_user_id();

        if ( ! $payment_id ) {
            $this->json_error( 'payment_id obrigatório' );
        }

        if ( ! $this->is_admin() && ! Team::can_manage( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão', 403 );
        }

        $deleted = Payment::delete( $payment_id );
        if ( ! $deleted ) {
            $this->json_error( 'Erro ao remover pagamento' );
        }

        Activity::log( [
            'event_id'    => $event_id,
            'user_id'     => $user_id,
            'action'      => 'payment_deleted',
            'entity_type' => 'payment',
            'entity_id'   => $payment_id,
        ] );

        $this->json_success( [ 'deleted' => true ] );
    }

    /**
     * AJAX: Load budget breakdown
     */
    public function ajax_load_budget(): void {
        if ( ! $this->verify_nonce() ) {
            $this->json_error( 'Nonce inválido', 403 );
        }

        $event_id = $this->post_int( 'event_id' );
        $user_id  = get_current_user_id();

        if ( ! $event_id ) {
            $this->json_error( 'event_id obrigatório' );
        }

        if ( ! $this->is_admin() && ! Team::can_view_finance( $user_id, $event_id ) ) {
            $this->json_error( 'Sem permissão financeira', 403 );
        }

        $summary  = Payment::get_summary( $event_id );
        $payments = Payment::get_by_event( $event_id );

        $by_category = [];
        foreach ( $payments as $p ) {
            $cat = $p['category'] ?? 'production';
            if ( ! isset( $by_category[ $cat ] ) ) {
                $by_category[ $cat ] = [
                    'total'   => 0,
                    'paid'    => 0,
                    'pending' => 0,
                    'items'   => [],
                ];
            }
            $by_category[ $cat ]['total'] += (float) $p['amount'];
            if ( $p['status'] === 'paid' ) {
                $by_category[ $cat ]['paid'] += (float) $p['amount'];
            } else {
                $by_category[ $cat ]['pending'] += (float) $p['amount'];
            }
            $by_category[ $cat ]['items'][] = $p;
        }

        $this->json_success( [
            'summary'     => $summary,
            'by_category' => $by_category,
        ] );
    }
}
