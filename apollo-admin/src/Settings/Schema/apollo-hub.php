<?php
/**
 * Schema: apollo-hub
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_search'    => array(
        'type'    => 'toggle',
        'label'   => 'Search global',
        'default' => true,
    ),
    'enable_directory' => array(
        'type'    => 'toggle',
        'label'   => 'Diretório',
        'default' => true,
    ),
);
