<?php
/**
 * Frontend Router — registers rewrite rules and dispatches templates.
 *
 * Routes:
 *   /admin/panel       → panel.php
 *   /admin/pending     → pending.php
 *   /admin/memberships → memberships.php
 *   /modera[/plugin[/sub]] → modera/modera.php  (administrator = apollo → all;
 *                                                editor = MOD → limited subset)
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Frontend;

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

final class Router {

    private const PAGES = array(
        'panel'       => 'Admin Panel',
        'pending'     => 'Pending Drafts',
        'memberships' => 'Memberships',
    );

    /**
     * Register hooks.
     */
    public function init(): void {
        add_action( 'init', array( $this, 'register_rewrites' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'handle_template' ) );

        if ( ! get_option( 'apollo_admin_frontend_rewrite_v2' ) ) {
            add_action(
                'init',
                function () {
                    flush_rewrite_rules();
                    update_option( 'apollo_admin_frontend_rewrite_v2', true );
                },
                999
            );
        }
    }

    public function register_rewrites(): void {
        add_rewrite_rule(
            '^admin/(panel|pending|memberships)/?$',
            'index.php?apollo_admin_page=$matches[1]',
            'top'
        );

        // /modera — frontend Apollo suite settings & moderation panel.
        add_rewrite_rule(
            '^modera/([a-z0-9_-]+)/([a-z0-9_-]+)/?$',
            'index.php?apollo_modera=1&apollo_modera_plugin=$matches[1]&apollo_modera_sub=$matches[2]',
            'top'
        );
        add_rewrite_rule(
            '^modera/([a-z0-9_-]+)/?$',
            'index.php?apollo_modera=1&apollo_modera_plugin=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^modera/?$',
            'index.php?apollo_modera=1',
            'top'
        );
    }

    public function add_query_vars( array $vars ): array {
        $vars[] = 'apollo_admin_page';
        $vars[] = 'apollo_modera';
        $vars[] = 'apollo_modera_plugin';
        $vars[] = 'apollo_modera_sub';
        return $vars;
    }

    /**
     * /modera access role: 'admin' (administrator → apollo), 'mod' (editor → MOD),
     * '' when the current user may not enter.
     */
    public static function modera_role(): string {
        if ( ! is_user_logged_in() ) {
            return '';
        }
        $user = wp_get_current_user();
        if ( in_array( 'administrator', (array) $user->roles, true ) || is_super_admin( $user->ID ) ) {
            return 'admin';
        }
        if ( in_array( 'editor', (array) $user->roles, true ) ) {
            return 'mod';
        }
        return '';
    }

    public function handle_template(): void {
        // ── /modera — administrator (apollo) sees all, editor (MOD) a subset. ──
        if ( get_query_var( 'apollo_modera' ) ) {
            $role = self::modera_role();
            if ( '' === $role ) {
                wp_safe_redirect( home_url( '/' ) );
                exit;
            }

            nocache_headers();
            status_header( 200 );

            $template = APOLLO_ADMIN_DIR . 'templates/modera/modera.php';
            if ( \file_exists( $template ) ) {
                $apollo_modera_role   = $role; // phpcs:ignore -- consumed by template.
                $apollo_modera_plugin = sanitize_key( (string) get_query_var( 'apollo_modera_plugin' ) );
                $apollo_modera_sub    = sanitize_key( (string) get_query_var( 'apollo_modera_sub' ) );
                require $template;
            } else {
                wp_die( esc_html__( 'Template not found.', 'apollo-admin' ) );
            }
            exit;
        }

        $page = get_query_var( 'apollo_admin_page' );

        if ( empty( $page ) || ! isset( self::PAGES[ $page ] ) ) {
            return;
        }

        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        nocache_headers();
        status_header( 200 );

        $template = APOLLO_ADMIN_DIR . 'templates/frontend/' . $page . '.php';
        if ( \file_exists( $template ) ) {
            require $template;
        } else {
            wp_die( esc_html__( 'Template not found.', 'apollo-admin' ) );
        }
        exit;
    }
}
