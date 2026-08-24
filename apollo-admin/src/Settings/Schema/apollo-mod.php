<?php
/**
 * Schema: apollo-mod
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'auto_mod'         => array(
        'type'    => 'toggle',
        'label'   => 'Auto-moderação',
        'default' => false,
    ),
    'report_threshold' => array(
        'type'    => 'number',
        'label'   => 'Reports para flag',
        'default' => 3,
    ),
);
