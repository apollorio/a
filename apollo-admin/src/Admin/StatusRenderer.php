<?php
/**
 * Status Renderer — renders the status overview grid.
 *
 * Shows all Apollo plugins grouped by layer with install/active stats.
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin\Admin;

use Apollo\Admin\Registry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class StatusRenderer {

    /**
     * Render the status overview grid.
     */
    public static function render(): void {
        $registry = Registry::get_instance();
        $manifest = Registry::get_registry_manifest();

        $layers = array();
        foreach ( $manifest as $slug => $meta ) {
            $layer = $meta['layer'];
            if ( ! isset( $layers[ $layer ] ) ) {
                $layers[ $layer ] = array(
                    'name'    => $meta['layer_name'],
                    'plugins' => array(),
                );
            }
            $info                                    = $registry->get( $slug );
            $layers[ $layer ]['plugins'][ $slug ] = array_merge( $meta, $info );
        }

        ksort( $layers );

        $stats = self::compute_stats( $manifest, $registry );

        ?>
        <div class="apollo-status-overview">

            <div class="apollo-tab-header">
                <span class="dashicons dashicons-dashboard apollo-tab-icon-large"></span>
                <div>
                    <h2><?php esc_html_e( 'Status Overview', 'apollo-admin' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Visão geral de todos os plugins Apollo — instalados, ativos, e faltantes.', 'apollo-admin' ); ?>
                    </p>
                </div>
            </div>

            <div class="apollo-stats-grid">
                <div class="apollo-stat-card">
                    <div class="apollo-stat-value"><?php echo esc_html( (string) $stats['total'] ); ?></div>
                    <div class="apollo-stat-label"><?php esc_html_e( 'Total Plugins', 'apollo-admin' ); ?></div>
                </div>
                <div class="apollo-stat-card active">
                    <div class="apollo-stat-value"><?php echo esc_html( (string) $stats['active'] ); ?></div>
                    <div class="apollo-stat-label"><?php esc_html_e( 'Ativos', 'apollo-admin' ); ?></div>
                </div>
                <div class="apollo-stat-card installed">
                    <div class="apollo-stat-value"><?php echo esc_html( (string) $stats['installed'] ); ?></div>
                    <div class="apollo-stat-label"><?php esc_html_e( 'Instalados', 'apollo-admin' ); ?></div>
                </div>
                <div class="apollo-stat-card missing">
                    <div class="apollo-stat-value"><?php echo esc_html( (string) $stats['missing'] ); ?></div>
                    <div class="apollo-stat-label"><?php esc_html_e( 'Faltando', 'apollo-admin' ); ?></div>
                </div>
                <div class="apollo-stat-card compliance">
                    <div class="apollo-stat-value"><?php echo esc_html( $stats['compliance'] . '%' ); ?></div>
                    <div class="apollo-stat-label"><?php esc_html_e( 'Compliance', 'apollo-admin' ); ?></div>
                </div>
            </div>

            <?php foreach ( $layers as $layer_key => $layer_data ) : ?>
                <div class="apollo-layer-section">
                    <h3 class="apollo-layer-title">
                        <span class="apollo-layer-badge"><?php echo esc_html( $layer_key ); ?></span>
                        <?php echo esc_html( $layer_data['name'] ); ?>
                        <span class="apollo-layer-count"><?php echo esc_html( count( $layer_data['plugins'] ) ); ?> plugins</span>
                    </h3>
                    <div class="apollo-plugins-grid">
                        <?php foreach ( $layer_data['plugins'] as $slug => $plugin ) : ?>
                            <?php self::render_plugin_card( $slug, $plugin ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
        <?php
    }

    /**
     * Render a single plugin card.
     */
    private static function render_plugin_card( string $slug, array $plugin ): void {
        $status_class = ( $plugin['active'] ?? false )
            ? 'active'
            : ( ( $plugin['installed'] ?? false ) ? 'installed' : 'missing' );
        ?>
        <div class="apollo-plugin-card <?php echo esc_attr( $status_class ); ?>">
            <div class="apollo-plugin-header">
                <span class="dashicons <?php echo esc_attr( $plugin['icon'] ); ?> apollo-plugin-icon"></span>
                <div class="apollo-plugin-info">
                    <h4 class="apollo-plugin-name">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo&tab=' . $slug ) ); ?>">
                            <?php echo esc_html( $plugin['name'] ); ?>
                        </a>
                    </h4>
                    <div class="apollo-plugin-meta">
                        <span class="apollo-plugin-slug"><?php echo esc_html( $slug ); ?></span>
                        <?php if ( ! empty( $plugin['version'] ) && $plugin['version'] !== '—' ) : ?>
                            <span class="apollo-plugin-version">v<?php echo esc_html( $plugin['version'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="apollo-plugin-status">
                    <?php if ( $plugin['active'] ?? false ) : ?>
                        <span class="apollo-status-badge active">
                            <span class="dashicons dashicons-yes-alt"></span> Ativo
                        </span>
                    <?php elseif ( $plugin['installed'] ?? false ) : ?>
                        <span class="apollo-status-badge installed">
                            <span class="dashicons dashicons-download"></span> Instalado
                        </span>
                    <?php else : ?>
                        <span class="apollo-status-badge missing">
                            <span class="dashicons dashicons-warning"></span> Faltando
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <p class="apollo-plugin-description">
                <?php echo esc_html( $plugin['description'] ?? '' ); ?>
            </p>
            <?php if ( ! empty( $plugin['file'] ) ) : ?>
                <div class="apollo-plugin-file">
                    <span class="dashicons dashicons-media-code"></span>
                    <code><?php echo esc_html( basename( $plugin['file'] ) ); ?></code>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Compute aggregate stats.
     */
    private static function compute_stats( array $manifest, Registry $registry ): array {
        $total     = count( $manifest );
        $installed = 0;
        $active    = 0;
        $missing   = 0;

        foreach ( $manifest as $slug => $meta ) {
            $info = $registry->get( $slug );
            if ( $info['active'] ?? false ) {
                ++$active;
            } elseif ( $info['installed'] ?? false ) {
                ++$installed;
            } else {
                ++$missing;
            }
        }

        $compliance = $total > 0 ? round( ( $active / $total ) * 100, 1 ) : 0;

        return compact( 'total', 'installed', 'active', 'missing', 'compliance' );
    }
}
