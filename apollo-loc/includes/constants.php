<?php
/**
 * Constantes do Apollo Local
 *
 * @package Apollo\Local
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── REST ────────────────────────────────────────────────────────────────────
define( 'APOLLO_LOCAL_REST_NAMESPACE', 'apollo/v1' );

// ─── CPT ─────────────────────────────────────────────────────────────────────
define( 'APOLLO_LOCAL_CPT', 'local' );

// ─── Taxonomies ──────────────────────────────────────────────────────────────
define( 'APOLLO_LOCAL_TAX_TYPE', 'local_type' );
define( 'APOLLO_LOCAL_TAX_AREA', 'local_area' );

// ─── Style ───────────────────────────────────────────────────────────────────
define( 'APOLLO_LOCAL_DEFAULT_STYLE', 'apollo-v1' );

// ─── Cache ───────────────────────────────────────────────────────────────────
define( 'APOLLO_LOCAL_CACHE_GROUP', 'apollo_locals' );
define( 'APOLLO_LOCAL_CACHE_TTL', 300 );

// ─── Meta Keys — conforme apollo-registry.json ──────────────────────────────
define(
	'APOLLO_LOCAL_META_KEYS',
	array(
		'_local_name',
		'_local_address',
		'_local_city',
		'_local_state',
		'_local_country',
		'_local_postal',
		'_local_lat',
		'_local_lng',
		'_local_phone',
		'_local_website',
		'_local_instagram',
		'_local_capacity',
		'_local_price_range',
	)
);
