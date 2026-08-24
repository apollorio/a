<?php

/**
 * REST Controller: signature requests and users list.
 *
 * @package Apollo\Sign\API
 */

namespace Apollo\Sign\API;

if (! defined('ABSPATH')) {
    exit;
}

use Apollo\Sign\Model\Signature;
use Apollo\Sign\Notifications;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST Controller: signature requests and users list.
 *
 * POST  apollo/v1/signatures/request  — initiate multi-signer workflow
 * GET   apollo/v1/signatures/users    — list users for signer dropdown
 * GET   apollo/v1/signatures/doc/{id} — get signers/status for a document
 */
final class RequestController extends WP_REST_Controller
{

    protected $namespace = 'apollo/v1';
    protected $rest_base = 'signatures';

    public function register_routes(): void
    {
        /* POST /signatures/request */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/request',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_request'),
                    'permission_callback' => array($this, 'require_login'),
                    'args'                => $this->request_args(),
                ),
            )
        );

        /* GET /signatures/users */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/users',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'list_users'),
                    'permission_callback' => array($this, 'require_login'),
                ),
            )
        );

        /* GET /signatures/doc/{doc_id} — signer status for a document */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/doc/(?P<doc_id>[\d]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_doc_signers'),
                    'permission_callback' => array($this, 'require_login'),
                    'args'                => array(
                        'doc_id' => array(
                            'required'          => true,
                            'validate_callback' => fn($v) => is_numeric($v) && $v > 0,
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );
    }

    /* ── Permission helpers ─────────────────────────────────── */

    public function require_login(): bool|WP_Error
    {
        if (! is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Autenticação necessária.', array('status' => 401));
        }
        return true;
    }

	/* ── POST /signatures/request ──────────────────────────── */

    /**
     * Initiate a signing workflow on a finalized document.
     *
     * Request body:
     *   doc_id   int      — WP post ID
     *   signers  array    — ordered list of {email, user_id?, name?}
     *
     * Saves _doc_signers meta.
     * Creates Signature record for the first signer.
     * Sends invitation email to the first signer.
     */
    public function create_request(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $doc_id  = (int) $request->get_param('doc_id');
        $signers = $request->get_param('signers'); // array of {email,user_id?,name?}

        /* Validate document */
        $post = get_post($doc_id);
        if (! $post || 'doc' !== $post->post_type) {
            return new WP_Error('not_found', 'Documento não encontrado.', array('status' => 404));
        }

        /* Only the author or admin can request signatures */
        $user_id = get_current_user_id();
        if ((int) $post->post_author !== $user_id && ! current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', 'Apenas o autor pode solicitar assinaturas.', array('status' => 403));
        }

        /* Document must be finalized */
        $doc_status = get_post_meta($doc_id, '_doc_status', true);
        if ('finalized' !== $doc_status) {
            return new WP_Error(
                'invalid_status',
                sprintf('Documento deve estar finalizado (status atual: %s).', esc_html($doc_status)),
                array('status' => 422)
            );
        }

        /* Validate & normalise signers list */
        if (empty($signers) || ! is_array($signers)) {
            return new WP_Error('invalid_signers', 'Lista de signatários inválida.', array('status' => 422));
        }

        $signers    = array_slice($signers, 0, 20); // max 20 signers
        $normalised = array();

        foreach ($signers as $i => $s) {
            $email = sanitize_email($s['email'] ?? '');
            $uid   = absint($s['user_id'] ?? 0);
            $name  = sanitize_text_field($s['name'] ?? '');

            /* Resolve from user_id if email not supplied */
            if (! $email && $uid) {
                $u = get_userdata($uid);
                if ($u) {
                    $email = $u->user_email;
                    if (! $name) {
                        $name = $u->display_name;
                    }
                }
            }

            if (! is_email($email)) {
                return new WP_Error(
                    'invalid_email',
                    sprintf('Email inválido no signatário #%d.', $i + 1),
                    array('status' => 422)
                );
            }

            /* Try to resolve user_id from email */
            if (! $uid) {
                $u = get_user_by('email', $email);
                if ($u) {
                    $uid = $u->ID;
                    if (! $name) {
                        $name = $u->display_name;
                    }
                }
            }

            $normalised[] = array(
                'email'   => $email,
                'user_id' => $uid,
                'name'    => $name ?: $email,
                'sig_id'  => 0,    // filled when row is created
                'status'  => 'pending',
                'order'   => $i,
            );
        }

        /* Save full signer queue to doc meta */
        update_post_meta($doc_id, '_doc_signers', wp_json_encode($normalised));

        /* Create & email the first signer */
        $first  = &$normalised[0];
        $sig_id = $this->create_signer_record($doc_id, $first);

        if (! $sig_id) {
            return new WP_Error('sign_create_failed', 'Falha ao criar registro de assinatura.', array('status' => 500));
        }

        $first['sig_id'] = $sig_id;
        $first['status'] = 'invited'; // invited = record created, email sent (or attempted)

        /* Persist updated sig_id back to meta */
        update_post_meta($doc_id, '_doc_signers', wp_json_encode($normalised));

        /* Send invitation */
        Notifications::invite($sig_id);

        return rest_ensure_response(
            array(
                'success' => true,
                'doc_id'  => $doc_id,
                'signers' => $normalised,
                'sig_id'  => $sig_id,
                'message' => sprintf(
                    '%d signatário(s) adicionado(s). Convite enviado para %s.',
                    count($normalised),
                    $first['email']
                ),
            )
        );
    }

	/* ── GET /signatures/users ──────────────────────────────── */

    /**
     * Return a list of WP users suitable for the signer dropdown.
     * Excludes the requesting user (doc owner doesn't sign their own request modal easily).
     */
    public function list_users(WP_REST_Request $request): WP_REST_Response
    {
        $search = sanitize_text_field($request->get_param('search') ?? '');

        $args = array(
            'number'  => 50,
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'fields'  => array('ID', 'display_name', 'user_email'),
        );

        if ($search) {
            $args['search']         = '*' . $search . '*';
            $args['search_columns'] = array('display_name', 'user_email', 'user_login');
        }

        $users  = get_users($args);
        $result = array();

        foreach ($users as $u) {
            $result[] = array(
                'id'    => (int) $u->ID,
                'name'  => $u->display_name,
                'email' => $u->user_email,
            );
        }

        return rest_ensure_response($result);
    }

	/* ── GET /signatures/doc/{doc_id} ───────────────────────── */

    /**
     * Return the signer queue + status for a document.
     */
    public function get_doc_signers(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $doc_id = (int) $request->get_param('doc_id');
        $post   = get_post($doc_id);

        if (! $post || 'doc' !== $post->post_type) {
            return new WP_Error('not_found', 'Documento não encontrado.', array('status' => 404));
        }

        $user_id = get_current_user_id();
        if (
            (int) $post->post_author !== $user_id &&
            ! current_user_can('manage_options')
        ) {
            return new WP_Error('rest_forbidden', 'Acesso negado.', array('status' => 403));
        }

        $raw     = get_post_meta($doc_id, '_doc_signers', true);
        $signers = $raw ? json_decode($raw, true) : array();

        /* Enrich with live signature status from DB */
        if (! empty($signers)) {
            global $wpdb;
            foreach ($signers as &$s) {
                if (! empty($s['sig_id'])) {
                    $live = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT status, signed_at FROM {$wpdb->prefix}apollo_signatures WHERE id = %d",
                            $s['sig_id']
                        ),
                        ARRAY_A
                    );
                    if ($live) {
                        $s['db_status'] = $live['status'];
                        $s['signed_at'] = $live['signed_at'];
                    }
                }
            }
            unset($s);
        }

        return rest_ensure_response(
            array(
                'doc_id'  => $doc_id,
                'signers' => $signers ?: array(),
            )
        );
    }

	/* ── Shared helper ──────────────────────────────────────── */

    /**
     * Create a Signature DB record for a signer entry.
     *
     * @param  int                 $doc_id  Document post ID.
     * @param  array<string,mixed> $signer  Normalised signer entry.
     * @return int|false            Signature ID or false.
     */
    private function create_signer_record(int $doc_id, array $signer): int|false
    {
        $data = array(
            'doc_id'       => $doc_id,
            'signer_id'    => $signer['user_id'] ?: 0,
            'signer_name'  => $signer['name'],
            'signer_email' => $signer['email'],
        );

        return Signature::create($data);
    }

    /* ── Argument schema ────────────────────────────────────── */

    private function request_args(): array
    {
        return array(
            'doc_id'  => array(
                'required'          => true,
                'type'              => 'integer',
                'minimum'           => 1,
                'validate_callback' => fn($v) => is_numeric($v) && $v > 0,
                'sanitize_callback' => 'absint',
                'description'       => 'ID do documento a ser assinado.',
            ),
            'signers' => array(
                'required'          => true,
                'type'              => 'array',
                'items'             => array(
                    'type'       => 'object',
                    'properties' => array(
                        'email'   => array(
                            'type'   => 'string',
                            'format' => 'email',
                        ),
                        'user_id' => array(
                            'type'    => 'integer',
                            'minimum' => 0,
                        ),
                        'name'    => array('type' => 'string'),
                    ),
                ),
                'validate_callback' => fn($v) => is_array($v) && count($v) >= 1,
                'description'       => 'Lista ordenada de signatários.',
            ),
        );
    }
}
