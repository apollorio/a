<?php
/**
 * Schema: apollo-cdn
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'cdn_url'    => array(
        'type'    => 'text',
        'label'   => 'CDN base URL',
        'default' => '',
    ),
    'minify_js'  => array(
        'type'    => 'toggle',
        'label'   => 'Minificar JS',
        'default' => false,
    ),
    'minify_css' => array(
        'type'    => 'toggle',
        'label'   => 'Minificar CSS',
        'default' => false,
    ),
);
