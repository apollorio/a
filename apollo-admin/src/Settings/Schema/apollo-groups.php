<?php
/**
 * Schema: apollo-groups
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_groups'       => array(
        'type'    => 'toggle',
        'label'   => 'Habilitar grupos',
        'default' => true,
    ),
    'max_groups_per_user' => array(
        'type'    => 'number',
        'label'   => 'Max grupos por user',
        'default' => 10,
    ),
);
