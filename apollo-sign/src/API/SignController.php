<?php

/**
 * REST Controller — /apollo/v1/signatures
 * Endpoints: POST create, GET single, POST sign (with certificate upload).
 *
 * @package Apollo\Sign\API
 */

namespace Apollo\Sign\API;

if (! defined('ABSPATH')) {
    exit;
}

use Apollo\Sign\Model\Signature;
use Apollo\Sign\Storage;

/**
 * REST Controller — /apollo/v1/signatures
 * Endpoints: POST create, GET single, POST sign (with certificate upload).
 */
final class SignController extends \WP_REST_Controller
{

    protected $namespace = 'apollo/v1';
    protected $rest_base = 'signatures';

    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        /* POST /signatures — create pending signature */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'create_signature'),
                'permission_callback' => array($this, 'create_permission'),
                'args'                => array(
                    'doc_id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        /* GET /signatures/{id} — get signature details (logged-in only) */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_signature'),
                'permission_callback' => array($this, 'create_permission'),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        /* POST /signatures/{id}/sign — perform signing with PFX upload */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)/sign',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'perform_sign'),
                'permission_callback' => array($this, 'sign_permission'),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        /* GET /signatures/{id}/audit — get audit trail */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)/audit',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_audit'),
                'permission_callback' => array($this, 'audit_permission'),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        /* POST /signatures/{id}/placement — save visual placement coordinates */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)/placement',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'save_placement'),
                'permission_callback' => array($this, 'sign_permission'),
                'args'                => array(
                    'id'             => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'sig_x'          => array(
                        'required'          => false,
                        'type'              => 'number',
                        'sanitize_callback' => fn( $v ) => (float) $v,
                    ),
                    'sig_y'          => array(
                        'required'          => false,
                        'type'              => 'number',
                        'sanitize_callback' => fn( $v ) => (float) $v,
                    ),
                    'sig_w'          => array(
                        'required'          => false,
                        'type'              => 'number',
                        'sanitize_callback' => fn( $v ) => (float) $v,
                    ),
                    'sig_h'          => array(
                        'required'          => false,
                        'type'              => 'number',
                        'sanitize_callback' => fn( $v ) => (float) $v,
                    ),
                    'sig_page'       => array(
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'placement_mode' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        /* GET /signatures/{id}/placement — get current placement */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\d+)/placement',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_placement'),
                'permission_callback' => array($this, 'sign_permission'),
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );
    }

	/* ── Permissions ── */

    /**
     * Create permission.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function create_permission(\WP_REST_Request $request): bool
    {
        return is_user_logged_in();
    }

    /**
     * Sign permission.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function sign_permission(\WP_REST_Request $request): bool
    {
        if (! is_user_logged_in()) {
            return false;
        }

        $sig = Signature::get(absint($request['id']));
        if (! $sig) {
            return false;
        }

        /* Only the designated signer or admin can sign */
        return (int) $sig['signer_id'] === get_current_user_id() || current_user_can('manage_options');
    }

    /**
     * Audit permission.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function audit_permission(\WP_REST_Request $request): bool
    {
        if (! is_user_logged_in()) {
            return false;
        }

        $sig = Signature::get(absint($request['id']));
        if (! $sig) {
            return false;
        }

        return (int) $sig['signer_id'] === get_current_user_id() || current_user_can('manage_options');
    }

	/* ── Callbacks ── */

    /**
     * Create signature.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function create_signature(\WP_REST_Request $request): \WP_REST_Response
    {
        $doc_id = absint($request['doc_id']);
        $post   = get_post($doc_id);

        if (! $post || 'doc' !== $post->post_type) {
            return new \WP_REST_Response(array('message' => 'Documento não encontrado.'), 404);
        }

        $status = get_post_meta($doc_id, '_doc_status', true);
        if (in_array($status, array('finalized', 'locked'), true) === false) {
            return new \WP_REST_Response(array('message' => 'Documento precisa estar finalizado para assinar.'), 400);
        }

        $sig_id = Signature::create(
            array(
                'doc_id'       => $doc_id,
                'signer_id'    => get_current_user_id(),
                'signer_name'  => sanitize_text_field($request->get_param('signer_name') ?? ''),
                'signer_cpf'   => sanitize_text_field($request->get_param('signer_cpf') ?? ''),
                'signer_email' => sanitize_email($request->get_param('signer_email') ?? ''),
            )
        );

        if (! $sig_id) {
            return new \WP_REST_Response(array('message' => 'Erro ao criar registro de assinatura.'), 500);
        }

        return new \WP_REST_Response(Signature::get($sig_id), 201);
    }

    /**
     * Get signature.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function get_signature(\WP_REST_Request $request): \WP_REST_Response
    {
        $sig = Signature::get(absint($request['id']));
        if (! $sig) {
            return new \WP_REST_Response(array('message' => 'Assinatura não encontrada.'), 404);
        }

        /* Remove raw signature data from public response */
        unset($sig['signature_data']);

        return new \WP_REST_Response($sig, 200);
    }

    /**
     * Perform sign.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function perform_sign(\WP_REST_Request $request): \WP_REST_Response
    {
        $sig_id = absint($request['id']);
        $sig    = Signature::get($sig_id);

        if (! $sig) {
            return new \WP_REST_Response(array('message' => 'Assinatura não encontrada.'), 404);
        }

        if ('pending' !== $sig['status']) {
            return new \WP_REST_Response(array('message' => 'Este documento já foi assinado.'), 400);
        }

        /* Expect PFX file in multipart form */
        $files = $request->get_file_params();
        if (empty($files['certificate'])) {
            return new \WP_REST_Response(array('message' => 'Certificado PFX não enviado.'), 400);
        }

        $pfx_file = $files['certificate'];
        if (UPLOAD_ERR_OK !== $pfx_file['error']) {
            return new \WP_REST_Response(array('message' => 'Erro no upload do certificado.'), 400);
        }

        /* Validate file extension */
        $ext = strtolower(pathinfo($pfx_file['name'], PATHINFO_EXTENSION));
        if (! in_array($ext, array('pfx', 'p12'), true)) {
            return new \WP_REST_Response(array('message' => 'Formato inválido. Aceitos: .pfx, .p12'), 400);
        }

        $password = sanitize_text_field($request->get_param('password') ?? '');
        if (empty($password)) {
            return new \WP_REST_Response(array('message' => 'Senha do certificado obrigatória.'), 400);
        }

        /* Placement coords from hidden form fields */
        $placement = array(
            'sig_x'          => floatval($request->get_param('sig_x') ?? $sig['sig_x'] ?? 0.65),
            'sig_y'          => floatval($request->get_param('sig_y') ?? $sig['sig_y'] ?? 0.85),
            'sig_w'          => floatval($request->get_param('sig_w') ?? $sig['sig_w'] ?? 0.28),
            'sig_h'          => floatval($request->get_param('sig_h') ?? $sig['sig_h'] ?? 0.06),
            'sig_page'       => absint($request->get_param('sig_page') ?? $sig['sig_page'] ?? 1),
            'placement_mode' => sanitize_text_field($request->get_param('placement_mode') ?? $sig['placement_mode'] ?? 'auto_footer'),
        );

        /* Save placement before signing */
        Signature::save_placement($sig_id, $placement);

        /* Save temp PFX — UUID filename prevents predictable/guessable paths */
        $pfx_path = Storage::save_temp_cert(
            'cert_' . wp_generate_uuid4() . '.' . $ext,
            file_get_contents($pfx_file['tmp_name'])
        );

        /* Get optional hand-drawn signature image (base64 PNG) */
        $sig_image = sanitize_text_field($request->get_param('signature_image') ?? '');

        /* Perform signing */
        $success = Signature::sign_with_certificate($sig_id, $pfx_path, $password, $sig_image);

        if (! $success) {
            return new \WP_REST_Response(array('message' => 'Falha na assinatura digital. Verifique o certificado e a senha.'), 400);
        }

        $updated = Signature::get($sig_id);
        unset($updated['signature_data']);

        return new \WP_REST_Response(
            array(
                'message'   => 'Documento assinado digitalmente com sucesso.',
                'signature' => $updated,
            ),
            200
        );
    }

    /**
     * Get audit.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function get_audit(\WP_REST_Request $request): \WP_REST_Response
    {
        $sig_id = absint($request['id']);
        $sig    = Signature::get($sig_id);

        if (! $sig) {
            return new \WP_REST_Response(array('message' => 'Assinatura não encontrada.'), 404);
        }

        $trail = Signature::get_audit_trail($sig_id);

        return new \WP_REST_Response(
            array(
                'signature_id' => $sig_id,
                'status'       => $sig['status'],
                'audit_trail'  => $trail,
            ),
            200
        );
    }

	/* ── Placement Callbacks ── */

    /**
     * Save placement.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function save_placement(\WP_REST_Request $request): \WP_REST_Response
    {
        $sig_id = absint($request['id']);
        $sig    = Signature::get($sig_id);

        if (! $sig) {
            return new \WP_REST_Response(array('message' => 'Assinatura não encontrada.'), 404);
        }

        if ('pending' !== $sig['status']) {
            return new \WP_REST_Response(array('message' => 'Não é possível alterar posição de assinatura já realizada.'), 400);
        }

        $mode = sanitize_text_field($request->get_param('placement_mode') ?? 'auto_footer');
        if (in_array($mode, array('manual', 'auto_footer'), true) === false) {
            $mode = 'auto_footer';
        }

        $data = array(
            'sig_x'          => floatval($request->get_param('sig_x') ?? 0.65),
            'sig_y'          => floatval($request->get_param('sig_y') ?? 0.85),
            'sig_w'          => floatval($request->get_param('sig_w') ?? 0.28),
            'sig_h'          => floatval($request->get_param('sig_h') ?? 0.06),
            'sig_page'       => absint($request->get_param('sig_page') ?? 1),
            'placement_mode' => $mode,
        );

        $ok = Signature::save_placement($sig_id, $data);

        if (! $ok) {
            return new \WP_REST_Response(array('message' => 'Erro ao salvar posição.'), 500);
        }

        return new \WP_REST_Response(
            array(
                'message'   => 'Posição salva.',
                'placement' => $data,
            ),
            200
        );
    }

    /**
     * Get placement.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function get_placement(\WP_REST_Request $request): \WP_REST_Response
    {
        $sig_id = absint($request['id']);
        $sig    = Signature::get($sig_id);

        if (! $sig) {
            return new \WP_REST_Response(array('message' => 'Assinatura não encontrada.'), 404);
        }

        return new \WP_REST_Response(
            array(
                'sig_x'          => floatval($sig['sig_x'] ?? 0.65),
                'sig_y'          => floatval($sig['sig_y'] ?? 0.85),
                'sig_w'          => floatval($sig['sig_w'] ?? 0.28),
                'sig_h'          => floatval($sig['sig_h'] ?? 0.06),
                'sig_page'       => intval($sig['sig_page'] ?? 1),
                'placement_mode' => $sig['placement_mode'] ?? 'auto_footer',
            ),
            200
        );
    }
}
