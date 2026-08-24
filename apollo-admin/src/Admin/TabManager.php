<?php
/**
 * Tab Manager — builds the ordered list of tabs for the admin UI.
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

final class TabManager {

    /**
     * Build the ordered list of tabs.
     *
     * @return array<string, array{slug:string,name:string,icon:string,layer:string,layer_name:string,installed:bool,active:bool}>
     */
    public static function get_tabs(): array {
        $tabs = array();

        $tabs['_status'] = array(
            'slug'       => '_status',
            'name'       => 'Status Overview',
            'icon'       => 'dashicons-dashboard',
            'layer'      => 'L0',
            'layer_name' => 'Visão Geral',
            'installed'  => true,
            'active'     => true,
        );

        $tabs['_global'] = array(
            'slug'       => '_global',
            'name'       => 'Global',
            'icon'       => 'dashicons-admin-site-alt3',
            'layer'      => 'L0',
            'layer_name' => 'Geral',
            'installed'  => true,
            'active'     => true,
        );

        $registry = Registry::get_instance();
        $manifest = Registry::get_registry_manifest();

        $sorted = $manifest;
        uasort(
            $sorted,
            function ( $a, $b ) {
                $cmp = strcmp( $a['layer'], $b['layer'] );
                return $cmp !== 0 ? $cmp : strcmp( $a['name'], $b['name'] );
            }
        );

        foreach ( $sorted as $slug => $meta ) {
            $info        = $registry->get( $slug );
            $tabs[ $slug ] = array(
                'slug'       => $slug,
                'name'       => $meta['name'],
                'icon'       => $meta['icon'],
                'layer'      => $meta['layer'],
                'layer_name' => $meta['layer_name'],
                'installed'  => $info['installed'] ?? false,
                'active'     => $info['active'] ?? false,
            );
        }

        return $tabs;
    }
}
