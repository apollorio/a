<?php
/**
 * Schema: apollo-coauthor
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_coauthor'  => array(
        'type'    => 'toggle',
        'label'   => 'Multi-autoria',
        'default' => true,
    ),
    'show_in_frontend' => array(
        'type'    => 'toggle',
        'label'   => 'Mostrar no frontend',
        'default' => true,
    ),
);
