<?php
/**
 * Schema: apollo-chat
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_dm'          => array(
        'type'    => 'toggle',
        'label'   => 'Mensagens diretas',
        'default' => true,
    ),
    'enable_group_chat'  => array(
        'type'    => 'toggle',
        'label'   => 'Chat em grupo',
        'default' => true,
    ),
    'max_message_length' => array(
        'type'    => 'number',
        'label'   => 'Max chars por msg',
        'default' => 2000,
    ),
);
