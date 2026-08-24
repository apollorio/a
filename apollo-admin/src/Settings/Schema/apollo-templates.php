<?php
/**
 * Schema: apollo-templates
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_blank_canvas' => array(
        'type'    => 'toggle',
        'label'   => 'Blank canvas',
        'default' => true,
    ),
    'override_archive'    => array(
        'type'    => 'toggle',
        'label'   => 'Override archives',
        'default' => false,
    ),
);
