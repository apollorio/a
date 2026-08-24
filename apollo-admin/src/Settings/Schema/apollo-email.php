<?php
/**
 * Schema: apollo-email
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'from_name'    => array(
        'type'    => 'text',
        'label'   => 'Nome do remetente',
        'default' => 'Apollo',
    ),
    'from_email'   => array(
        'type'    => 'email',
        'label'   => 'Email do remetente',
        'default' => '',
    ),
    'enable_queue' => array(
        'type'    => 'toggle',
        'label'   => 'Fila de emails',
        'default' => true,
    ),
    'smtp_host'    => array(
        'type'    => 'text',
        'label'   => 'SMTP Host',
        'default' => '',
    ),
    'smtp_port'    => array(
        'type'    => 'number',
        'label'   => 'SMTP Port',
        'default' => 587,
    ),
);
