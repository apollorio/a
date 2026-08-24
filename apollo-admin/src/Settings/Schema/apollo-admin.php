<?php
/**
 * Schema: apollo-admin
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'admin_accent' => array(
        'type'    => 'color',
        'label'   => 'Cor de destaque admin',
        'default' => '#6366f1',
    ),
);
