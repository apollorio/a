<?php
/**
 * Schema: apollo-social
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_activity'  => array(
        'type'    => 'toggle',
        'label'   => 'Activity stream',
        'default' => true,
    ),
    'enable_follow'    => array(
        'type'    => 'toggle',
        'label'   => 'Seguir/block',
        'default' => true,
    ),
    'enable_reactions' => array(
        'type'    => 'toggle',
        'label'   => 'Reactions',
        'default' => true,
    ),
);
