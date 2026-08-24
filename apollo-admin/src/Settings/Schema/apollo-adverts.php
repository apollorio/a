<?php
/**
 * Schema: apollo-adverts
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_classifieds' => array(
        'type'    => 'toggle',
        'label'   => 'Habilitar classificados',
        'default' => true,
    ),
    'require_approval'   => array(
        'type'    => 'toggle',
        'label'   => 'Aprovação manual',
        'default' => true,
    ),
);
