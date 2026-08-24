<?php
/**
 * Schema: apollo-comment (depoimentos)
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_reactions' => array(
        'type'    => 'toggle',
        'label'   => 'Reactions em comments',
        'default' => true,
    ),
    'enable_threading' => array(
        'type'    => 'toggle',
        'label'   => 'Threading',
        'default' => true,
    ),
);
