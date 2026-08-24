<?php
/**
 * Schema: apollo-core
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'debug_mode'           => array(
        'type'    => 'toggle',
        'label'   => 'Debug mode',
        'default' => false,
    ),
    'cleanup_on_uninstall' => array(
        'type'    => 'toggle',
        'label'   => 'Apagar tudo ao desinstalar',
        'default' => false,
    ),
);
