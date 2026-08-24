<?php
/**
 * Schema: _global settings
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'brand_name'  => array(
        'type'    => 'text',
        'label'   => 'Projeto apollo::rio v2.0.0',
        'default' => 'Apollo',
    ),
    'brand_color' => array(
        'type'    => 'color',
        'label'   => 'Cor primária',
        'default' => '#6366f1',
    ),
    'dark_mode'   => array(
        'type'    => 'toggle',
        'label'   => 'Modo escuro',
        'default' => false,
    ),
    'compact_ui'  => array(
        'type'    => 'toggle',
        'label'   => 'Interface compacta',
        'default' => false,
    ),
);
