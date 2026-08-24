<?php
/**
 * Schema: apollo-events
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'enable_rsvp'      => array(
        'type'    => 'toggle',
        'label'   => 'RSVP',
        'default' => true,
    ),
    'enable_tickets'   => array(
        'type'    => 'toggle',
        'label'   => 'Tickets',
        'default' => false,
    ),
    'enable_calendar'  => array(
        'type'    => 'toggle',
        'label'   => 'Calendário',
        'default' => true,
    ),
    'default_currency' => array(
        'type'    => 'text',
        'label'   => 'Moeda padrão',
        'default' => 'BRL',
    ),
);
