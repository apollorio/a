<?php
/**
 * /modera boot payload — server truth merged into APOLLO_ADMIN by data.boot.js.
 *
 * Structure-first build: real currentUser + role gate + cheap counts.
 * Demo datasets remain in assets/modera/js/data.defaults.js until each
 * section is wired to REST in follow-up passes.
 *
 * Role model (canonical map lives in apollo-core/includes/roles-map.php):
 *   administrator → frontend "apollo" (level 10) → panel role 'admin' (sees all)
 *   editor        → frontend "MOD"    (level 7)  → panel role 'mod'   (subset)
 *
 * @package Apollo\Admin
 * @since   2.1.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Frontend;

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

final class ModeraData {

    /**
     * Full boot payload for the /modera shell.
     *
     * @param string $role   'admin' | 'mod' (already gated by Router::modera_role()).
     * @param string $plugin Deep-linked plugin id ('' when none).
     * @param string $sub    Deep-linked sub id ('' when none).
     */
    public static function payload( string $role, string $plugin = '', string $sub = '' ): array {
        $user = wp_get_current_user();

        $payload = array(
            'currentUser' => self::current_user( $user, $role ),
            'roles'       => self::roles_matrix( $role ),
            'counts'      => self::counts(),
            'rest'        => array(
                'root'  => esc_url_raw( rest_url( 'apollo/v1/' ) ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
            ),
        );

        if ( '' !== $plugin ) {
            $payload['initial'] = array(
                'plugin' => $plugin,
                'sub'    => $sub,
            );
        }

        return $payload;
    }

    private static function current_user( \WP_User $user, string $role ): array {
        $name  = $user->display_name ? $user->display_name : $user->user_login;
        $label = \function_exists( 'apollo_user_frontend_role' ) ? apollo_user_frontend_role( $user ) : '';

        return array(
            'id'       => 'u-' . $user->ID,
            'name'     => $name,
            'initials' => self::initials( $name ),
            'handle'   => '@' . $user->user_login,
            'role'     => $role,
            'title'    => 'Núcleo Apollo' . ( '' !== $label ? ' · ' . $label : '' ),
        );
    }

    /**
     * Role matrix, server-gated: a MOD build ships ONLY the mod entry so the
     * client can never switch itself to admin (roles.js refuses unknown roles).
     * Labels follow the canonical frontend relabel (apollo / MOD).
     */
    private static function roles_matrix( string $role ): array {
        $admin = array(
            'label' => \function_exists( 'apollo_role_frontend_label' ) ? apollo_role_frontend_label( 'administrator' ) : 'apollo',
            'caps'  => array( 'manage_users', 'manage_roles', 'manage_plans', 'grant_any', 'moderate', 'view_audit', 'system', 'configure', 'grant_membership' ),
        );
        $mod = array(
            'label' => \function_exists( 'apollo_role_frontend_label' ) ? apollo_role_frontend_label( 'editor' ) : 'MOD',
            'caps'  => array( 'moderate', 'grant_membership' ),
        );

        return 'admin' === $role
            ? array(
                'admin' => $admin,
                'mod'   => $mod,
            )
            : array( 'mod' => $mod );
    }

    /**
     * Cheap real counts (null = keep the demo-derived number client-side).
     */
    private static function counts(): array {
        return array(
            'events'  => self::pending_count( 'event' ),
            'adverts' => self::pending_count( self::first_existing_type( array( 'advert', 'apollo_advert', 'adverts' ) ) ),
        );
    }

    private static function first_existing_type( array $candidates ): string {
        foreach ( $candidates as $type ) {
            if ( post_type_exists( $type ) ) {
                return $type;
            }
        }
        return '';
    }

    private static function pending_count( string $post_type ): ?int {
        if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
            return null;
        }
        $counts  = wp_count_posts( $post_type );
        $pending = isset( $counts->pending ) ? (int) $counts->pending : 0;
        $draft   = isset( $counts->draft ) ? (int) $counts->draft : 0;
        return $pending + $draft;
    }

    private static function initials( string $name ): string {
        $parts = preg_split( '/\s+/', trim( $name ) );
        if ( ! \is_array( $parts ) || '' === $parts[0] ) {
            return 'AP';
        }
        $first = mb_substr( $parts[0], 0, 1 );
        $last  = \count( $parts ) > 1 ? mb_substr( (string) end( $parts ), 0, 1 ) : '';
        $init  = mb_strtoupper( $first . $last );
        return '' !== $init ? $init : 'AP';
    }
}
