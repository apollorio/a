<?php

namespace Apollo\Radio\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin settings page for Apollo Radio.
 */
class Settings {

    private const OPTION_GROUP = 'apollo_radio_settings';
    private const PAGE_SLUG    = 'custom-radio';
    private const SECTION      = 'apollo_radio_main';

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_menu' ], 20 );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_menu(): void {
        // Try to add under Apollo menu first, fallback to Settings.
        $parent = '';
        if ( defined( 'APOLLO_CORE_VERSION' ) ) {
            $parent = 'apollo';
        }

        if ( $parent && menu_page_url( $parent, false ) ) {
            add_submenu_page(
                $parent,
                'Apollo Radio',
                'Radio',
                'manage_options',
                self::PAGE_SLUG,
                [ $this, 'render_page' ]
            );
        } else {
            add_options_page(
                'Apollo Radio',
                'Apollo Radio',
                'manage_options',
                self::PAGE_SLUG,
                [ $this, 'render_page' ]
            );
        }
    }

    public function register_settings(): void {
        // Section.
        add_settings_section(
            self::SECTION,
            'Configurações do Radio Engine',
            function () {
                echo '<p>Configure a fonte de streams SoundCloud e playlists JSON.</p>';
            },
            self::PAGE_SLUG
        );

        // SC Client ID.
        $this->add_field( 'apollo_radio_sc_client_id', 'SoundCloud Client ID', 'text', '' );

        // Proxy URL.
        $this->add_field(
            'apollo_radio_sc_proxy_url',
            'SC Proxy URL (Cloudflare Worker)',
            'url',
            'https://apradio.pages.dev/sc-proxy'
        );

        // CDN URL.
        $this->add_field(
            'apollo_radio_json_cdn',
            'JSON Playlist CDN URL',
            'url',
            'https://assets.apollo.rio.br/radio/json/'
        );

        // Fallback toggle.
        register_setting( self::OPTION_GROUP, 'apollo_radio_json_fallback', [
            'type'              => 'string',
            'sanitize_callback' => function ( $val ) {
                return $val === '1' ? '1' : '0';
            },
            'default'           => '0',
        ] );
        add_settings_field(
            'apollo_radio_json_fallback',
            'WP Uploads Fallback',
            function () {
                $val = get_option( 'apollo_radio_json_fallback', '0' );
                echo '<label class="custom-checkbox">';
                echo '<input type="checkbox" name="apollo_radio_json_fallback" value="1"'
                     . checked( $val, '1', false ) . ' />';
                echo ' Usar WP uploads como fallback quando CDN estiver indisponível';
                echo '</label>';
            },
            self::PAGE_SLUG,
            self::SECTION
        );
    }

    private function add_field( string $key, string $label, string $type, string $default ): void {
        $sanitize = $type === 'url' ? 'esc_url_raw' : 'sanitize_text_field';

        register_setting( self::OPTION_GROUP, $key, [
            'type'              => 'string',
            'sanitize_callback' => $sanitize,
            'default'           => $default,
        ] );

        add_settings_field(
            $key,
            $label,
            function () use ( $key, $type, $default ) {
                $val = get_option( $key, $default );
                printf(
                    '<input type="%s" name="%s" value="%s" class="regular-text" />',
                    esc_attr( $type ),
                    esc_attr( $key ),
                    esc_attr( $val )
                );
            },
            self::PAGE_SLUG,
            self::SECTION
        );
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Apollo Radio</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button( 'Salvar Configurações' );
                ?>
            </form>
            <hr />
            <h2>Status</h2>
            <table class="widefat" style="max-width:600px;">
                <tr>
                    <td><strong>Versão</strong></td>
                    <td><?php echo esc_html( APOLLO_RADIO_VERSION ); ?></td>
                </tr>
                <tr>
                    <td><strong>REST Endpoint</strong></td>
                    <td><code><?php echo esc_html( rest_url( 'apollo/v1/radio/' ) ); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Shortcode</strong></td>
                    <td><code>[apollo_radio]</code> ou <code>[apollo_radio mode="widget"]</code></td>
                </tr>
            </table>
        </div>
        <?php
    }
}