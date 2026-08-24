<?php
/**
 * Schema: apollo-loc
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_maps'      => array(
        'type'    => 'toggle',
        'label'   => 'Google Maps',
        'default' => true,
    ),
    'enable_geocoding' => array(
        'type'    => 'toggle',
        'label'   => 'Geocoding',
        'default' => true,
    ),
    'default_city'     => array(
        'type'    => 'text',
        'label'   => 'Cidade padrão',
        'default' => 'Rio de Janeiro',
    ),
);
