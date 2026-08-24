<?php
/**
 * Schema: apollo-rio (PWA)
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'pwa_name'        => array(
        'type'    => 'text',
        'label'   => 'PWA App Name',
        'default' => 'Apollo Rio',
    ),
    'pwa_short_name'  => array(
        'type'    => 'text',
        'label'   => 'PWA Short Name',
        'default' => 'Apollo',
    ),
    'pwa_theme_color' => array(
        'type'    => 'color',
        'label'   => 'Theme Color',
        'default' => '#6366f1',
    ),
    'enable_sw'       => array(
        'type'    => 'toggle',
        'label'   => 'Service Worker',
        'default' => true,
    ),
);
