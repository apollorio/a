<?php
/**
 * Schema: apollo-membership
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_points'   => array(
        'type'    => 'toggle',
        'label'   => 'Sistema de pontos',
        'default' => true,
    ),
    'enable_badges'   => array(
        'type'    => 'toggle',
        'label'   => 'Badges',
        'default' => true,
    ),
    'enable_ranks'    => array(
        'type'    => 'toggle',
        'label'   => 'Ranks',
        'default' => true,
    ),
    'points_per_post' => array(
        'type'    => 'number',
        'label'   => 'Pontos por post',
        'default' => 10,
    ),
);
