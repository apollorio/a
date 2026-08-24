<?php

/**
 * Constantes do plugin
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// REST API namespace (compartilhado com todo ecossistema Apollo)
define( 'APOLLO_EVENT_REST_NAMESPACE', 'apollo/v1' );

// CPT slug — conforme apollo-registry.json
define( 'APOLLO_EVENT_CPT', 'event' );

// Taxonomias — registradas pelo apollo-core como GLOBAL BRIDGE
define( 'APOLLO_EVENT_TAX_CATEGORY', 'event_category' );
define( 'APOLLO_EVENT_TAX_TYPE', 'event_type' );
define( 'APOLLO_EVENT_TAX_TAG', 'event_tag' );
define( 'APOLLO_EVENT_TAX_SOUND', 'sound' );
define( 'APOLLO_EVENT_TAX_SEASON', 'season' );

// Expiração — evento vira "gone" 30 minutos após end_date + end_time
define( 'APOLLO_EVENT_GONE_OFFSET_MINUTES', 30 );

// Templates — styles disponíveis
define( 'APOLLO_EVENT_STYLES', array( 'base', 'apollo-v1', 'apollo-v2', 'ui-thim', 'ui-lis' ) );
define( 'APOLLO_EVENT_DEFAULT_STYLE', 'base' );

// Cache group
define( 'APOLLO_EVENT_CACHE_GROUP', 'apollo_events' );
define( 'APOLLO_EVENT_CACHE_TTL', 300 ); // 5 minutos

// Meta keys — conforme Registry::register_meta (SSOT)
define(
	'APOLLO_EVENT_META_KEYS',
	array(
		'_event_start_date',
		'_event_end_date',
		'_event_start_time',
		'_event_end_time',
		'_event_dj_ids',
		'_event_dj_slots',
		'_event_loc_id',
		'_event_banner',
		'_event_bg_color',
		'_event_access_buttons',
		'_event_ticket_url',
		'_event_ticket_price',
		'_event_privacy',
		'_event_status',
		'_event_is_gone',
		'_event_video_url',
		'_event_gallery',
		'_event_coupon_code',
		'_event_list_url',
		'_event_audio_url',
		'_event_ticket_status',
		'_event_ticket_btn_style',
		'_event_list_btn_style',
		'_event_view_count',
		'_event_earlybird_enabled',
		'_event_earlybird_name',
		'_event_earlybird_sub',
		'_event_earlybird_url',
		'_event_lista_geral_enabled',
		'_event_lista_geral_sub',
		'_event_lista_fem_enabled',
		'_event_lista_fem_sub',
		'_event_lista_cta_label',
	)
);
