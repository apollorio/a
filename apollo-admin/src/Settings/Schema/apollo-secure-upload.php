<?php
/**
 * Schema: apollo-secure-upload
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'max_file_size' => array(
        'type'    => 'number',
        'label'   => 'Max upload MB',
        'default' => 10,
    ),
    'scan_malware'  => array(
        'type'    => 'toggle',
        'label'   => 'Scan malware',
        'default' => true,
    ),
    'allowed_types' => array(
        'type'    => 'text',
        'label'   => 'Tipos permitidos',
        'default' => 'jpg,png,webp,pdf',
    ),
);
