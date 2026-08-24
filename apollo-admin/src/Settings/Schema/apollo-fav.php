<?php
/**
 * Schema: apollo-fav (favoritos)
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_favorites' => array(
        'type'    => 'toggle',
        'label'   => 'Habilitar favoritos',
        'default' => true,
    ),
);
