<?php
/**
 * @partial scripts
 * @expects $ctx — apollo_get_dj_context()
 * Localizes APOLLO_DJ for dj-card-single.js (BASE + LUXE layers).
 * No hidden DOM truth — all data via wp_localize_script.
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

$payload = $ctx;
// Strip HTML-heavy keys that JS does not need
unset( $payload['bio'] );

wp_register_script(
	'apollo-dj-card',
	APOLLO_DJ_URL . 'assets/js/dj-card-single.js',
	array(),
	APOLLO_DJ_VERSION,
	true
);
wp_localize_script( 'apollo-dj-card', 'APOLLO_DJ', $payload );
wp_enqueue_script( 'apollo-dj-card' );
wp_print_scripts( 'apollo-dj-card' );
