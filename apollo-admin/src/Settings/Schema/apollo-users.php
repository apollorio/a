<?php
/**
 * Schema: apollo-users
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_radar'       => array(
        'type'    => 'toggle',
        'label'   => 'Habilitar radar',
        'default' => true,
    ),
    'enable_matchmaking' => array(
        'type'    => 'toggle',
        'label'   => 'Matchmaking',
        'default' => false,
    ),
    'default_role'       => array(
        'type'    => 'text',
        'label'   => 'Role padrão',
        'default' => 'subscriber',
    ),
);
