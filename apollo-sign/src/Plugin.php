<?php

namespace Apollo\Sign;

use Apollo\Core\Traits\BlankCanvasTrait;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin singleton — orchestrates all Sign subsystems.
 */
final class Plugin
{

    use BlankCanvasTrait;

    private static ?self $instance = null;

    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void
    {
        /* DB upgrade check */
        $this->maybe_upgrade_db();

        /* REST API controllers */
        add_action(
            'rest_api_init',
            function (): void {
                (new API\SignController())->register_routes();
                (new API\VerifyController())->register_routes();
                (new API\RequestController())->register_routes();
            }
        );

        /* Admin Controller */
        if (is_admin()) {
            new Admin\Controller();
        }

        /* Virtual page: /assinar/{hash} */
        add_action('init', array($this, 'register_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_virtual_page'));

        /* Shortcode */
        add_shortcode('apollo_signature_pad', array($this, 'render_signature_pad'));

        /* Hook into docs finalization */
        add_action('apollo/docs/finalized', array($this, 'on_doc_finalized'));

        /* Chain multi-signer workflow: after one signer signs → invite next */
        add_action('apollo/sign/signed', array($this, 'on_signed'), 10, 2);

        do_action('apollo/sign/initialized');
    }

    private function maybe_upgrade_db(): void
    {
        $current = (int) get_option('apollo_sign_db_version', 0);
        if ($current < APOLLO_SIGN_DB_VERSION) {
            Database::install();
            update_option('apollo_sign_db_version', APOLLO_SIGN_DB_VERSION);
        }
    }

    /* ── Virtual page for signing ── */

    public function register_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^assinar/([a-f0-9]{64})/?$',
            'index.php?apollo_sign_hash=$matches[1]',
            'top'
        );
        add_rewrite_tag('%apollo_sign_hash%', '([a-f0-9]{64})');
    }

    public function handle_virtual_page(): void
    {
        $hash = get_query_var('apollo_sign_hash');
        if (! $hash) {
            return;
        }

        $signature = Model\Signature::get_by_hash($hash);
        if (! $signature) {
            wp_die('Documento não encontrado ou link expirado.', 'Assinatura', array('response' => 404));
        }

        /* Resolve PDF URL from attached document */
        $pdf_url = '';
        if (! empty($signature['doc_id'])) {
            $doc_id = absint($signature['doc_id']);
            /* Try post meta first (apollo-docs stores PDF path) */
            $pdf_path = get_post_meta($doc_id, '_doc_pdf_path', true);
            if ($pdf_path && file_exists($pdf_path)) {
                $upload_dir = wp_upload_dir();
                $relative   = str_replace($upload_dir['basedir'], '', $pdf_path);
                $pdf_url    = $upload_dir['baseurl'] . $relative;
            } else {
                /* Fallback: check for PDF attachment */
                $attachments = get_attached_media('application/pdf', $doc_id);
                if (! empty($attachments)) {
                    $first   = reset($attachments);
                    $pdf_url = wp_get_attachment_url($first->ID);
                }
            }
        }

        /* Set globals for template */
        $signature['pdf_url']        = $pdf_url;
        $GLOBALS['apollo_sign_data'] = $signature;

        /* Load signing template via BlankCanvasTrait */
        $this->render_blank_canvas(
            APOLLO_SIGN_DIR . 'templates/sign.php',
            array('signature' => $signature)
        );
    }

    /* ── Shortcode ── */

    public function render_signature_pad(array $atts = array()): string
    {
        $atts   = shortcode_atts(array('doc_id' => 0), $atts, 'apollo_signature_pad');
        $doc_id = absint($atts['doc_id']);

        if (! $doc_id || ! is_user_logged_in()) {
            return '<p>Documento ou sessão inválida.</p>';
        }

        ob_start();
        include APOLLO_SIGN_DIR . 'templates/signature-pad.php';
        return ob_get_clean();
    }

	/* ── Auto-generate signing link when doc finalized ── */

    /**
     * When a doc is finalized, do NOT auto-create signatures.
     * The owner must explicitly request signatures via the UI (POST /signatures/request).
     * This hook is kept as an extension point for future automation.
     */
    public function on_doc_finalized(int $doc_id): void
    {
        do_action('apollo/sign/doc_finalized', $doc_id);
    }

	/* ── Multi-signer chain: after signing, invite next signer ── */

    /**
     * Fired by apollo/sign/signed after Signature::sign_with_certificate() completes.
     *
     * Logic:
     *  1. Notify doc owner that signer X signed.
     *  2. Read _doc_signers queue.
     *  3. Find the next pending signer → create their Signature record → send invite.
     *  4. If all signers done → mark document as fully signed → notify owner.
     *
     * @param int $sig_id  Signature row ID that just completed.
     * @param int $doc_id  Document post ID.
     */
    public function on_signed(int $sig_id, int $doc_id): void
    {
        /* Notify owner about this signing event */
        Notifications::notify_owner_signed($sig_id, $doc_id);

        /* Load signer queue */
        $raw     = get_post_meta($doc_id, '_doc_signers', true);
        $signers = $raw ? json_decode($raw, true) : array();

        if (empty($signers)) {
            return; // No queue — single-signer flow, nothing more to do.
        }

        /* Mark the signer whose sig_id matches as signed */
        $next_signer = null;
        $all_done    = true;
        $updated     = false;

        foreach ($signers as &$s) {
            if ((int) ($s['sig_id'] ?? 0) === $sig_id) {
                $s['status'] = 'signed';
                $updated     = true;
                continue;
            }
            /* Find the first still-pending signer (no sig_id yet) */
            if ($next_signer === null && empty($s['sig_id'])) {
                $next_signer = &$s;
                $all_done    = false;
            } elseif (empty($s['sig_id'])) {
                $all_done = false;
            }
        }
        unset($s);

        if ($updated) {
            update_post_meta($doc_id, '_doc_signers', wp_json_encode($signers));
        }

        /* Invite the next signer */
        if ($next_signer !== null) {
            $new_sig_id = Model\Signature::create(
                array(
                    'doc_id'       => $doc_id,
                    'signer_id'    => (int) ($next_signer['user_id'] ?? 0),
                    'signer_name'  => $next_signer['name'] ?? '',
                    'signer_email' => $next_signer['email'] ?? '',
                )
            );

            if ($new_sig_id) {
                $next_signer['sig_id'] = $new_sig_id;
                $next_signer['status'] = 'invited';
                update_post_meta($doc_id, '_doc_signers', wp_json_encode($signers));
                Notifications::invite($new_sig_id);
            }
            return;
        }

        /* All signers done → finalize the document status */
        if ($all_done) {
            /* Only call mark_signed if available (apollo-docs active) */
            if (class_exists('Apollo\Docs\Model\Document')) {
                \Apollo\Docs\Model\Document::mark_signed($doc_id);
            } else {
                update_post_meta($doc_id, '_doc_status', 'signed');
            }
            Notifications::notify_all_signed($doc_id);
        }
    }

    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}
