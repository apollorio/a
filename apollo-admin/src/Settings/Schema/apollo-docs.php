<?php
/**
 * Schema: apollo-docs
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_versioning' => array(
        'type'    => 'toggle',
        'label'   => 'Versionamento',
        'default' => false,
    ),
    'enable_toc'        => array(
        'type'    => 'toggle',
        'label'   => 'Table of contents',
        'default' => true,
    ),
);
