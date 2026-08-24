<?php
/**
 * Schema: apollo-suppliers
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_suppliers' => array(
        'type'    => 'toggle',
        'label'   => 'Habilitar fornecedores',
        'default' => true,
    ),
);
