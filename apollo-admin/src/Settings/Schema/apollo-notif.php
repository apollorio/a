<?php
/**
 * Schema: apollo-notif
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_push'         => array(
        'type'    => 'toggle',
        'label'   => 'Push notifications',
        'default' => false,
    ),
    'enable_email_digest' => array(
        'type'    => 'toggle',
        'label'   => 'Email digest',
        'default' => true,
    ),
    'digest_frequency'    => array(
        'type'    => 'select',
        'label'   => 'Frequência do digest',
        'default' => 'weekly',
        'options' => array(
            'daily'   => 'Diário',
            'weekly'  => 'Semanal',
            'monthly' => 'Mensal',
        ),
    ),
);
