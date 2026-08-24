<?php

/**
 * Admin Controller — menu, AJAX handlers, asset enqueue.
 *
 * @package Apollo\Sign\Admin
 */

namespace Apollo\Sign\Admin;

if (! defined('ABSPATH')) {
    exit;
}

use Apollo\Sign\Model\Signature;
use Apollo\Sign\Storage;

/**
 * Admin Controller — menu, AJAX handlers, asset enqueue.
 */
final class Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        /* AJAX handlers */
        $actions = array(
            'load_signatures',
            'create_signature',
            'sign_document',
            'verify_signature',
            'load_audit',
            'revoke_signature',
        );

        foreach ($actions as $action) {
            add_action('wp_ajax_apollo_sign_' . $action, array($this, $action));
        }
    }

    /**
     * Register admin menu.
     */
    public function register_menu(): void
    {
        add_submenu_page(
            'apollo',
            'Assinaturas Digitais',
            'Assinaturas',
            'manage_options',
            'apollo-sign',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue assets.
     *
     * @param string $hook Hook.
     */
    public function enqueue_assets(string $hook): void
    {
        if ('docs_page_apollo-sign' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'apollo-sign-css',
            APOLLO_SIGN_URL . 'assets/css/sign.css',
            array(),
            APOLLO_SIGN_VERSION
        );

        wp_enqueue_script(
            'apollo-sign-js',
            APOLLO_SIGN_URL . 'assets/js/sign.js',
            array('jquery'),
            APOLLO_SIGN_VERSION,
            true
        );

        wp_localize_script(
            'apollo-sign-js',
            'ApolloSign',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('apollo_sign_nonce'),
                'user_id'  => get_current_user_id(),
                'rest_url' => rest_url('apollo/v1/signatures'),
                'is_admin' => current_user_can('manage_options'),
            )
        );
    }

    /**
     * Render page.
     */
    public function render_page(): void
    {
        include APOLLO_SIGN_DIR . 'templates/admin-signatures.php';
    }

    /* ── Helpers ── */

    private function verify_nonce(): bool
    {
        return check_ajax_referer('apollo_sign_nonce', 'nonce', false);
    }

    /**
     * JSON success.
     *
     * @param array $data Data.
     */
    private function json_success(array $data): void
    {
        wp_send_json_success($data);
    }

    /**
     * JSON error.
     *
     * @param string $msg Message.
     * @param int    $code Code.
     */
    private function json_error(string $msg, int $code = 400): void
    {
        wp_send_json_error(array('message' => $msg), $code);
    }

	/* ── AJAX Handlers ── */

    /**
     * Load signatures.
     */
    public function load_signatures(): void
    {
        if (! $this->verify_nonce()) {
            $this->json_error('Nonce inválido.', 403);
        }

        $result = Signature::list(
            array(
                'status'   => sanitize_text_field(wp_unslash($_POST['status'] ?? '')),
                'per_page' => absint($_POST['per_page'] ?? 20),
                'page'     => absint($_POST['page'] ?? 1),
            )
        );

        $this->json_success($result);
    }

    /**
     * Create signature.
     */
    public function create_signature(): void
    {
        if (! $this->verify_nonce()) {
            $this->json_error('Nonce inválido.', 403);
        }

        $doc_id = absint($_POST['doc_id'] ?? 0);
        if (! $doc_id) {
            $this->json_error('ID do documento obrigatório.');
        }

        $post = get_post($doc_id);
        if (! $post || 'doc' !== $post->post_type) {
            $this->json_error('Documento não encontrado.');
        }

        $sig_id = Signature::create(
            array(
                'doc_id'       => $doc_id,
                'signer_id'    => get_current_user_id(),
                'signer_name'  => sanitize_text_field(wp_unslash($_POST['signer_name'] ?? '')),
                'signer_cpf'   => sanitize_text_field(wp_unslash($_POST['signer_cpf'] ?? '')),
                'signer_email' => sanitize_email(wp_unslash($_POST['signer_email'] ?? '')),
            )
        );

        if (! $sig_id) {
            $this->json_error('Erro ao criar registro de assinatura.');
        }

        $this->json_success(Signature::get($sig_id));
    }

    /**
     * Sign document.
     */
    public function sign_document(): void
    {
        if (! $this->verify_nonce()) {
            $this->json_error('Nonce inválido.', 403);
        }

        $sig_id = absint($_POST['signature_id'] ?? 0);
        if (! $sig_id) {
            $this->json_error('ID da assinatura obrigatório.');
        }

        /* Handle PFX upload */
        if (empty($_FILES['certificate'])) {
            $this->json_error('Certificado PFX não enviado.');
        }

        $file = $_FILES['certificate'];
        if (UPLOAD_ERR_OK !== $file['error']) {
            $this->json_error('Erro no upload do certificado.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (! in_array($ext, array('pfx', 'p12'), true)) {
            $this->json_error('Formato inválido. Aceitos: .pfx, .p12');
        }

        $password = sanitize_text_field(wp_unslash($_POST['password'] ?? ''));
        if (empty($password)) {
            $this->json_error('Senha do certificado obrigatória.');
        }

        /* Save temp and sign */
        $pfx_path  = Storage::save_temp_cert('cert_' . $sig_id . '.' . $ext, file_get_contents($file['tmp_name']));
        $sig_image = sanitize_text_field(wp_unslash($_POST['signature_image'] ?? ''));

        /* Save placement coords if provided */
        $placement_mode = sanitize_text_field(wp_unslash($_POST['placement_mode'] ?? ''));
        if ($placement_mode) {
            Signature::save_placement(
                $sig_id,
                array(
                    'sig_x'          => floatval($_POST['sig_x'] ?? 0.65),
                    'sig_y'          => floatval($_POST['sig_y'] ?? 0.85),
                    'sig_w'          => floatval($_POST['sig_w'] ?? 0.28),
                    'sig_h'          => floatval($_POST['sig_h'] ?? 0.06),
                    'sig_page'       => absint($_POST['sig_page'] ?? 1),
                    'placement_mode' => $placement_mode,
                )
            );
        }

        $success = Signature::sign_with_certificate($sig_id, $pfx_path, $password, $sig_image);

        if (! $success) {
            $this->json_error('Falha na assinatura. Verifique o certificado e a senha.');
        }

        $sig = Signature::get($sig_id);
        unset($sig['signature_data']);

        $this->json_success($sig);
    }

    /**
     * Verify signature.
     */
    public function verify_signature(): void
    {
        if (! $this->verify_nonce()) {
            $this->json_error('Nonce inválido.', 403);
        }

        $sig_id = absint($_POST['signature_id'] ?? 0);
        $result = Signature::verify($sig_id);

        $this->json_success($result);
    }

    /**
     * Load audit.
     */
    public function load_audit(): void
    {
        if (! $this->verify_nonce()) {
            $this->json_error('Nonce inválido.', 403);
        }

        $sig_id = absint($_POST['signature_id'] ?? 0);
        $trail  = Signature::get_audit_trail($sig_id);

        $this->json_success($trail);
    }

    /**
     * Revoke signature.
     */
    public function revoke_signature(): void
    {
        if (! $this->verify_nonce()) {
            $this->json_error('Nonce inválido.', 403);
        }

        if (! current_user_can('manage_options')) {
            $this->json_error('Permissão negada.', 403);
        }

        $sig_id = absint($_POST['signature_id'] ?? 0);
        $sig    = Signature::get($sig_id);

        if (! $sig) {
            $this->json_error('Assinatura não encontrada.');
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'apollo_signatures',
            array('status' => 'revoked'),
            array('id' => $sig_id),
            array('%s'),
            array('%d')
        );

        Signature::audit($sig_id, 'revoked', 'Assinatura revogada pelo administrador.');

        $this->json_success(array('revoked' => true));
    }
}
