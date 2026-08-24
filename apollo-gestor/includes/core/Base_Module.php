<?php
/**
 * Base Module — abstract base class for all Gestor modules
 *
 * Every module extends this and implements register().
 * Provides shared helpers for AJAX registration, nonce verification, and JSON responses.
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Base_Module {

    /** Module slug (e.g. 'projetos', 'proj-finance') */
    abstract public function get_slug(): string;

    /** Module display label (e.g. 'Projetos', 'Financeiro') */
    abstract public function get_label(): string;

    /** Register hooks, AJAX actions, filters */
    abstract public function register(): void;

    /**
     * Register an AJAX action scoped to this module
     *
     * Action name format: apollo_gestor_{method}
     */
    protected function add_ajax( string $action, string $method ): void {
        add_action(
            "wp_ajax_apollo_gestor_{$action}",
            [ $this, $method ]
        );
    }

    /**
     * Verify AJAX nonce
     */
    protected function verify_nonce(): bool {
        return check_ajax_referer( 'apollo_gestor_nonce', 'nonce', false ) !== false;
    }

    /**
     * Send JSON success response
     *
     * @param mixed $data
     */
    protected function json_success( $data = null ): void {
        wp_send_json_success( $data );
    }

    /**
     * Send JSON error response
     */
    protected function json_error( string $msg = 'Erro', int $code = 400 ): void {
        wp_send_json_error( [ 'message' => $msg ], $code );
    }

    /**
     * Get sanitized POST param
     */
    protected function post_param( string $key, string $default = '' ): string {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default;
    }

    /**
     * Get sanitized POST int param
     */
    protected function post_int( string $key, int $default = 0 ): int {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return isset( $_POST[ $key ] ) ? absint( $_POST[ $key ] ) : $default;
    }

    /**
     * Get sanitized POST float param
     */
    protected function post_float( string $key, float $default = 0.0 ): float {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        return isset( $_POST[ $key ] ) ? (float) sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default;
    }

    /**
     * Check if current user is admin
     */
    protected function is_admin(): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Get the frontend partial path for this module
     */
    public function get_partial_path( string $partial ): string {
        $slug = $this->get_slug();
        $path = APOLLO_GESTOR_DIR . "includes/modules/{$slug}/frontend/{$partial}.php";
        return file_exists( $path ) ? $path : '';
    }
}
