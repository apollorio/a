<?php
/**
 * Schema: apollo-shortcodes
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_manager' => array(
        'type'    => 'toggle',
        'label'   => 'Shortcode manager',
        'default' => true,
    ),
);
