<?php
/**
 * Schema: apollo-dashboard
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_quick_publish' => array(
        'type'    => 'toggle',
        'label'   => 'Quick publish',
        'default' => true,
    ),
    'enable_mod_queue'     => array(
        'type'    => 'toggle',
        'label'   => 'Fila de moderação',
        'default' => true,
    ),
);
