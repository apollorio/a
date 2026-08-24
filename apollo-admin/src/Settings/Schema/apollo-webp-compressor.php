<?php
/**
 * Schema: apollo-webp-compressor
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'auto_convert' => array(
        'type'    => 'toggle',
        'label'   => 'Auto-convert WebP',
        'default' => true,
    ),
    'quality'      => array(
        'type'    => 'number',
        'label'   => 'Qualidade (0-100)',
        'default' => 82,
    ),
);
