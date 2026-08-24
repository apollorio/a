<?php
/**
 * Save Handler
 *
 * Processes both the admin-post (form) and AJAX (fetch) save paths for the
 * Apollo CPanel. Delegates sanitisation and option routing to SettingsBridge
 * for per-plugin WP options, and also persists the full blob to
 * APOLLO_ADMIN_OPTION_KEY for back-compat with partials not yet in the
 * RouteRegistry (including SMTP encryption for smtp_pass).
 *
 * @package Apollo\Admin\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SaveHandler {

    /**
     * Handle save from the CPanel dashboard (AJAX + admin-post).
     */
    public static function handle(): void {
        /* ── Nonce ────────────────────────── */
        if (
            ! isset( $_POST['apollo_cpanel_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_cpanel_nonce'] ) ), 'apollo_cpanel_save' )
        ) {
            self::respond( false, __( 'Nonce inválido.', 'apollo-admin' ) );
            return;
        }

        /* ── Capability ───────────────────── */
        if ( ! current_user_can( 'manage_options' ) ) {
            self::respond( false, __( 'Sem permissão.', 'apollo-admin' ) );
            return;
        }

        /* ── Raw posted data ──────────────── */
        $raw = isset( $_POST['apollo'] )
            ? wp_unslash( $_POST['apollo'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            : [];

        if ( ! is_array( $raw ) ) {
            $raw = [];
        }

        /* ── Route individual options via bridge ──── */
        $result = SettingsBridge::save( $raw );

        /* ── Back-compat: persist full blob to single option ──── */
        $existing = get_option( APOLLO_ADMIN_OPTION_KEY, [] );
        if ( ! is_array( $existing ) ) {
            $existing = [];
        }

        // Reset existing boolean keys to 0 (unchecked checkboxes don't post).
        foreach ( $existing as $k => $v ) {
            if ( 1 === $v || 0 === $v ) {
                $existing[ $k ] = 0;
            }
        }

        $sanitized = self::sanitize_fields( $raw );
        $merged    = array_merge( $existing, $sanitized );
        $merged    = self::handle_smtp_encryption( $merged, $existing );

        update_option( APOLLO_ADMIN_OPTION_KEY, $merged );

        /** @since 1.0.0 */
        do_action( 'apollo/admin/settings_saved', $merged );

        /* ── Respond ─────────────────────── */
        if ( wp_doing_ajax() ) {
            wp_send_json_success( [
                'saved'   => $result['saved'],
                'message' => sprintf(
                    /* translators: %d: number of options saved */
                    __( '%d opção(ões) salva(s).', 'apollo-admin' ),
                    $result['saved']
                ),
            ] );
        } else {
            wp_safe_redirect(
                add_query_arg(
                    [ 'page' => 'apollo', 'settings-updated' => 1 ],
                    admin_url( 'admin.php' )
                )
            );
            exit;
        }
    }

    /* ─── Private helpers ─────────────────────────────────────── */

    /**
     * Sanitize a flat key→value array with type inference.
     */
    private static function sanitize_fields( array $raw ): array {
        $sanitized = [];

        foreach ( $raw as $key => $value ) {
            $safe_key = sanitize_key( $key );
            if ( empty( $safe_key ) ) {
                continue;
            }

            if ( is_array( $value ) ) {
                $sanitized[ $safe_key ] = array_map( 'sanitize_text_field', $value );
            } elseif ( in_array( $value, [ '0', '1' ], true ) ) {
                $sanitized[ $safe_key ] = (int) $value;
            } elseif ( is_numeric( $value ) ) {
                $sanitized[ $safe_key ] = is_float( $value + 0 ) ? (float) $value : (int) $value;
            } elseif ( preg_match( '/^#[0-9a-f]{3,8}$/i', (string) $value ) ) {
                $sanitized[ $safe_key ] = sanitize_hex_color( (string) $value ) ?: '';
            } elseif ( is_email( $value ) ) {
                $sanitized[ $safe_key ] = sanitize_email( (string) $value );
            } elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
                $sanitized[ $safe_key ] = esc_url_raw( (string) $value );
            } else {
                $sanitized[ $safe_key ] = sanitize_text_field( (string) $value );
            }
        }

        return $sanitized;
    }

    /**
     * Encrypt SMTP password before persisting (AES-256-CBC).
     */
    private static function handle_smtp_encryption( array $merged, array $existing ): array {
        if ( ! empty( $merged['email_smtp_pass'] ) && '••••••••' !== $merged['email_smtp_pass'] ) {
            $cipher = 'aes-256-cbc';
            $key    = hash( 'sha256', AUTH_KEY, true );
            $iv     = substr( hash( 'sha256', SECURE_AUTH_KEY ), 0, 16 );
            $merged['email_smtp_pass']            = base64_encode( openssl_encrypt( $merged['email_smtp_pass'], $cipher, $key, 0, $iv ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
            $merged['_email_smtp_pass_encrypted'] = true;
        } elseif ( '••••••••' === ( $merged['email_smtp_pass'] ?? '' ) ) {
            $merged['email_smtp_pass']            = $existing['email_smtp_pass'] ?? '';
            $merged['_email_smtp_pass_encrypted'] = $existing['_email_smtp_pass_encrypted'] ?? false;
        }

        return $merged;
    }

    private static function respond( bool $success, string $message ): void {
        if ( wp_doing_ajax() ) {
            if ( $success ) {
                wp_send_json_success( [ 'message' => $message ] );
            } else {
                wp_send_json_error( [ 'message' => $message ] );
            }
        } else {
            wp_die( esc_html( $message ) );
        }
    }
}
