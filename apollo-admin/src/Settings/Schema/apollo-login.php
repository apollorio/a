<?php
/**
 * Schema: apollo-login
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_registration' => array(
        'type'    => 'toggle',
        'label'   => 'Permitir registro',
        'default' => true,
    ),
    'registration_quiz'   => array(
        'type'    => 'toggle',
        'label'   => 'Quiz no registro',
        'default' => true,
    ),
    'login_redirect'      => array(
        'type'    => 'text',
        'label'   => 'Redirect após login',
        'default' => '/',
    ),
    'disable_wp_login'    => array(
        'type'    => 'toggle',
        'label'   => 'Ocultar wp-login.php',
        'default' => true,
    ),
);
