<?php
/**
 * Schema: apollo-statistics
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'track_logins'    => array(
        'type'    => 'toggle',
        'label'   => 'Rastrear logins',
        'default' => true,
    ),
    'track_pageviews' => array(
        'type'    => 'toggle',
        'label'   => 'Rastrear pageviews',
        'default' => false,
    ),
    'retention_days'  => array(
        'type'    => 'number',
        'label'   => 'Retenção (dias)',
        'default' => 90,
    ),
);
