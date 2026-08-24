<?php
/**
 * Integrations — apollo-djs
 *
 * Integra com: apollo-fav, apollo-wow, apollo-social, apollo-notif, apollo-statistics
 *
 * @package Apollo\DJs
 */

declare(strict_types=1);

namespace Apollo\DJs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Integrations {

	public function __construct() {
		// Apollo Fav
		add_filter( 'apollo_fav_post_types', array( $this, 'add_to_fav' ) );

		// Apollo Wow
		add_filter( 'apollo_wow_post_types', array( $this, 'add_to_wow' ) );

		// Apollo Social — log activities
		add_action( 'apollo_dj_rest_created', array( $this, 'log_dj_created' ), 10, 2 );

		// Apollo Statistics
		add_filter( 'apollo_statistics_post_types', array( $this, 'add_to_statistics' ) );
		add_action( 'template_redirect', array( $this, 'track_dj_view' ) );

		// Apollo Mod
		add_filter( 'apollo_mod_post_types', array( $this, 'add_to_mod' ) );
	}

	public function add_to_fav( array $types ): array {
		$types[] = APOLLO_DJ_CPT;
		return array_unique( $types );
	}

	public function add_to_wow( array $types ): array {
		$types[] = APOLLO_DJ_CPT;
		return array_unique( $types );
	}

	public function add_to_statistics( array $types ): array {
		$types[] = APOLLO_DJ_CPT;
		return array_unique( $types );
	}

	public function add_to_mod( array $types ): array {
		$types[] = APOLLO_DJ_CPT;
		return array_unique( $types );
	}

	/**
	 * Log no Apollo Social quando DJ é criado
	 */
	public function log_dj_created( int $post_id, array $data ): void {
		if ( ! function_exists( 'apollo_log_activity' ) ) {
			return;
		}

		apollo_log_activity(
			array(
				'action'    => 'dj_created',
				'object_id' => $post_id,
				'user_id'   => get_current_user_id(),
				'content'   => sprintf( 'DJ "%s" criado', get_the_title( $post_id ) ),
			)
		);
	}

	/**
	 * Track view no Apollo Statistics
	 */
	public function track_dj_view(): void {
		if ( ! is_singular( APOLLO_DJ_CPT ) ) {
			return;
		}

		do_action( 'apollo_statistics_track_view', get_the_ID(), APOLLO_DJ_CPT );
	}
}
