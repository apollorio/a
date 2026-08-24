<?php
/**
 * Schema: apollo-wow (reactions)
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_reactions' => array(
        'type'    => 'toggle',
        'label'   => 'Habilitar reactions',
        'default' => true,
    ),
    'available_types'  => array(
        'type'    => 'text',
        'label'   => 'Tipos disponíveis',
        'default' => 'like,love,fire,support,celebrate',
    ),
);
