<?php
/**
 * Schema: apollo-djs
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_profiles' => array(
        'type'    => 'toggle',
        'label'   => 'DJ Profiles',
        'default' => true,
    ),
    'enable_carousel' => array(
        'type'    => 'toggle',
        'label'   => 'Carousel view',
        'default' => true,
    ),
);
