<?php
/**
 * Uninstall cleanup.
 *
 * @package Apollo\Scheduler
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'apollo_scheduler_version' );

$user_keys = array(
	'_apollo_is_agent',
	'_apollo_agent_of_nucleo',
	'_apollo_agent_services',
	'_apollo_agent_rooms',
	'_apollo_working_plan',
	'_apollo_working_plan_exceptions',
);

foreach ( $user_keys as $key ) {
	delete_metadata( 'user', 0, $key, '', true );
}

$cpts = array( 'appointment', 'service', 'resource' );
foreach ( $cpts as $cpt ) {
	$posts = get_posts(
		array(
			'post_type'      => $cpt,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}
}

if ( function_exists( 'wp_cache_flush_group' ) ) {
	wp_cache_flush_group( 'apollo_scheduler' );
}
