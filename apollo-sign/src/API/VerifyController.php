<?php

/**
 * REST Controller — /apollo/v1/signatures/verify/{hash}
 * Public verification endpoint for signed documents.
 *
 * @package Apollo\Sign\API
 */

namespace Apollo\Sign\API;

if (! defined('ABSPATH')) {
    exit;
}

use Apollo\Sign\Model\Signature;

/**
 * REST Controller — /apollo/v1/signatures/verify/{hash}
 * Public verification endpoint for signed documents.
 */
final class VerifyController extends \WP_REST_Controller
{


    /**
     * Namespace.
     *
     * @var string
     */
    protected $namespace = 'apollo/v1';

    /**
     * Rest base.
     *
     * @var string
     */
    protected $rest_base = 'signatures/verify';

    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        /* GET /signatures/verify/{hash} — public verification */
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<hash>[a-f0-9]{64})',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'verify_signature'),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'hash' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function ($val) {
                            return (bool) preg_match('/^[a-f0-9]{64}$/', $val);
                        },
                    ),
                ),
            )
        );
    }

    /**
     * Verify signature.
     *
     * @param \WP_REST_Request $request Request.
     */
    public function verify_signature(\WP_REST_Request $request): \WP_REST_Response
    {
        $hash = sanitize_text_field($request['hash']);
        $sig  = Signature::get_by_hash($hash);

        if (! $sig) {
            return new \WP_REST_Response(
                array(
                    'valid'   => false,
                    'message' => 'Assinatura não encontrada.',
                ),
                404
            );
        }

        /* If the document is signed, verify the PKCS7 */
        if ('signed' === $sig['status']) {
            $result = Signature::verify((int) $sig['id']);

            return new \WP_REST_Response(
                array(
                    'valid'       => $result['valid'],
                    'message'     => $result['details'],
                    'signed_at'   => $sig['signed_at'],
                    'signer'      => array(
                        'name'  => $sig['signer_name'],
                        'cpf'   => $sig['signer_cpf'] ? self::mask_cpf($sig['signer_cpf']) : '',
                        'email' => $sig['signer_email'] ? self::mask_email($sig['signer_email']) : '',
                    ),
                    'certificate' => array(
                        'cn'         => $sig['certificate_cn'],
                        'issuer'     => $sig['certificate_issuer'],
                        'valid_from' => $sig['certificate_valid_from'],
                        'valid_to'   => $sig['certificate_valid_to'],
                    ),
                    'algorithm'   => $sig['algorithm'],
                    'doc_id'      => (int) $sig['doc_id'],
                ),
                200
            );
        }

        /* Document pending signing */
        return new \WP_REST_Response(
            array(
                'valid'   => false,
                'message' => 'Este documento ainda não foi assinado. Status: ' . $sig['status'],
                'status'  => $sig['status'],
            ),
            200
        );
    }

	/* ── Helpers ── */

    /**
     * Mask CPF for public display: 123.***.***.89
     *
     * @param string $cpf CPF.
     */
    private static function mask_cpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) {
            return '***.***.***-**';
        }
        return substr($cpf, 0, 3) . '.***.***-' . substr($cpf, 9, 2);
    }

    /**
     * Mask email: r***@gmail.com
     *
     * @param string $email Email.
     */
    private static function mask_email(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***.***';
        }
        $name = $parts[0];
        return substr($name, 0, 1) . '***@' . $parts[1];
    }
}
