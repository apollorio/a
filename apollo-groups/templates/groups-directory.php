<?php

/**
 * Groups Directory — /grupos, /comunas, /nucleos
 *
 * Blank Canvas + Apollo CDN + Design System.
 * Editorial-style listing with hero, tabs, trending, featured, grid.
 * FORBIDDEN: "group" on frontend — use "comuna" or "núcleo" only.
 *
 * Modular template — all parts in templates/parts/directory/
 * Can be loaded directly or via wrappers: groups.php, comunas.php, nucleos.php
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

// ═══════════════════════════════════════════════════════════════
// DATA
// ═══════════════════════════════════════════════════════════════

$is_logged = is_user_logged_in();

// $page_type can be pre-set by wrapper templates (groups.php, comunas.php, nucleos.php)
if ( ! isset( $page_type ) || $page_type === '' ) {
	$page_type = get_query_var( 'apollo_groups_page', '' );
}

$rest_url = rest_url( 'apollo/v1/groups' );
$nonce    = wp_create_nonce( 'wp_rest' );

// Context
$is_nucleos        = ( $page_type === 'nucleos' );
$is_all            = ( $page_type === 'all' || $page_type === '' );
$page_title        = $is_nucleos ? 'Núcleos' : ( $is_all ? 'Comunas & Núcleos' : 'Comunas' );
$page_desc         = $is_nucleos
	? 'Núcleos regionais e temáticos da cena carioca'
	: ( $is_all
		? 'Todas as comunidades e núcleos da cena carioca'
		: 'Comunidades da cena eletrônica do Rio de Janeiro' );
$create_url        = home_url( '/criar-comuna' );
$group_type_filter = $is_nucleos ? 'nucleo' : ( $is_all ? '' : 'comuna' );

// Stats (from DB)
$stats = array(
	'total_comunas' => 0,
	'total_nucleos' => 0,
	'total_members' => 0,
	'total_active'  => 0,
);
global $wpdb;
$groups_table  = $wpdb->prefix . 'apollo_groups';
$members_table = $wpdb->prefix . 'apollo_group_members';
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $groups_table ) ) === $groups_table ) {
	$stats['total_comunas'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$groups_table} WHERE type = %s", 'comuna' ) );
	$stats['total_nucleos'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$groups_table} WHERE type = %s", 'nucleo' ) );
}
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $members_table ) ) === $members_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate count, no user input.
	$stats['total_members'] = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$members_table}" );
}
$total_comunas = $stats['total_comunas'];
$total_nucleos = $stats['total_nucleos'];
$total_members = $stats['total_members'];
$total_active  = $stats['total_active'];

// ═══════════════════════════════════════════════════════════════
// TEMPLATE ORCHESTRATOR
// ═══════════════════════════════════════════════════════════════

$parts = __DIR__ . '/parts/directory/';

require $parts . 'head.php';
require $parts . 'loader.php';
require $parts . 'hero.php';
require $parts . 'tabs.php';
require $parts . 'search-bar.php';
require $parts . 'trending.php';
require $parts . 'featured.php';
require $parts . 'grid.php';
require $parts . 'create-cta.php';
require $parts . 'scripts.php';
