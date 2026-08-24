<?php
/**
 * Apollo DJ Sync — global settings only (maintenance, roles, nicotine memberships).
 *
 * Per-user feature gates were removed from wp-admin user-edit.php (2026-05-27).
 * Assign membership / desktop access via Apollo Membership on user-edit.php instead.
 *
 * @package Apollo\DJSync\Admin
 */

namespace Apollo\DJSync\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DJUserAdmin {

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_settings_page(): void {
        add_options_page(
            __( 'Apollo DJ Sync', 'apollo-dj-sync' ),
            __( 'Apollo DJ', 'apollo-dj-sync' ),
            'manage_options',
            'apollo-dj-sync',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings(): void {
        register_setting( 'apollo_dj_sync_settings', 'apollo_dj_maintenance_mode', [ 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => false ] );
        register_setting( 'apollo_dj_sync_settings', 'apollo_dj_min_version',      [ 'type' => 'string',  'sanitize_callback' => 'sanitize_text_field',    'default' => '2.0.0' ] );
        register_setting( 'apollo_dj_sync_settings', 'apollo_dj_global_message',   [ 'type' => 'string',  'sanitize_callback' => 'sanitize_text_field',    'default' => '' ] );
        register_setting( 'apollo_dj_sync_settings', 'apollo_dj_allowed_roles_login', [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_roles_setting' ],
            'default'           => [],
        ] );
        register_setting( 'apollo_dj_sync_settings', 'apollo_dj_allowed_memberships_nicotine', [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize_memberships_setting' ],
            'default'           => [ 'amigz', 'greatdjs' ],
        ] );
    }

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $allowed_roles = get_option( 'apollo_dj_allowed_roles_login', [] );
        if ( ! is_array( $allowed_roles ) ) {
            $allowed_roles = [];
        }

        $allowed_memberships = get_option( 'apollo_dj_allowed_memberships_nicotine', [ 'amigz', 'greatdjs' ] );
        if ( ! is_array( $allowed_memberships ) ) {
            $allowed_memberships = [ 'amigz', 'greatdjs' ];
        }

        global $wp_roles;
        $roles = is_object( $wp_roles ) && isset( $wp_roles->roles ) ? (array) $wp_roles->roles : [];

        $membership_options = [
            'amigz'    => 'amigz',
            'greatdjs' => 'greatdjs',
            'amigxs'   => 'amigxs',
        ];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Apollo DJ Sync Settings', 'apollo-dj-sync' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Global apolloDJ.exe config only. Per-user membership and desktop access: WP Admin → Users → Edit user → Apollo Membership.', 'apollo-dj-sync' ); ?>
            </p>
            <form method="post" action="options.php">
                <?php settings_fields( 'apollo_dj_sync_settings' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e( 'Maintenance Mode', 'apollo-dj-sync' ); ?></th>
                        <td>
                            <input type="checkbox" name="apollo_dj_maintenance_mode" value="1"
                                <?php checked( get_option( 'apollo_dj_maintenance_mode' ) ); ?>>
                            <span class="description"><?php esc_html_e( 'Block all apolloDJ.exe logins', 'apollo-dj-sync' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Min App Version', 'apollo-dj-sync' ); ?></th>
                        <td>
                            <input type="text" name="apollo_dj_min_version"
                                value="<?php echo esc_attr( (string) get_option( 'apollo_dj_min_version', '2.0.0' ) ); ?>"
                                style="width:120px;">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Global Message', 'apollo-dj-sync' ); ?></th>
                        <td>
                            <input type="text" name="apollo_dj_global_message"
                                value="<?php echo esc_attr( (string) get_option( 'apollo_dj_global_message', '' ) ); ?>"
                                style="width:400px;">
                            <p class="description"><?php esc_html_e( 'Shown in apolloDJ.exe on boot. Leave blank to hide.', 'apollo-dj-sync' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Roles Allowed to Login', 'apollo-dj-sync' ); ?></th>
                        <td>
                            <?php foreach ( $roles as $role_key => $role_data ) : ?>
                                <label style="display:inline-block;margin-right:12px;margin-bottom:4px;">
                                    <input type="checkbox"
                                           name="apollo_dj_allowed_roles_login[]"
                                           value="<?php echo esc_attr( (string) $role_key ); ?>"
                                        <?php checked( in_array( (string) $role_key, $allowed_roles, true ) ); ?>>
                                    <?php echo esc_html( translate_user_role( $role_data['name'] ?? (string) $role_key ) ); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Only selected WordPress roles can authenticate in apolloDJ.exe. If no role is selected, all roles are allowed.', 'apollo-dj-sync' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Memberships Allowed for Nicotine+ Tabs', 'apollo-dj-sync' ); ?></th>
                        <td>
                            <?php foreach ( $membership_options as $membership_key => $membership_label ) : ?>
                                <label style="display:inline-block;margin-right:12px;margin-bottom:4px;">
                                    <input type="checkbox"
                                           name="apollo_dj_allowed_memberships_nicotine[]"
                                           value="<?php echo esc_attr( $membership_key ); ?>"
                                        <?php checked( in_array( $membership_key, $allowed_memberships, true ) ); ?>>
                                    <?php echo esc_html( $membership_label ); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e( 'Site-wide allowlist. User must also have the slug assigned under Apollo Membership on their profile.', 'apollo-dj-sync' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * @param mixed $value Raw option value.
     * @return array<int, string>
     */
    public function sanitize_roles_setting( $value ): array {
        $value = is_array( $value ) ? $value : [];
        $value = array_values( array_unique( array_map( 'sanitize_key', $value ) ) );

        global $wp_roles;
        $valid_roles = is_object( $wp_roles ) && isset( $wp_roles->roles ) ? array_keys( (array) $wp_roles->roles ) : [];

        return array_values( array_intersect( $value, $valid_roles ) );
    }

    /**
     * @param mixed $value Raw option value.
     * @return array<int, string>
     */
    public function sanitize_memberships_setting( $value ): array {
        $value = is_array( $value ) ? $value : [];
        $value = array_values( array_unique( array_map( 'sanitize_key', $value ) ) );

        $valid_memberships = [ 'amigz', 'greatdjs', 'amigxs' ];

        return array_values( array_intersect( $value, $valid_memberships ) );
    }
}
