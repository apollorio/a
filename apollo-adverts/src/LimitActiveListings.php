<?php

/**
 * Apollo Adverts — Limit Active Listings.
 *
 * Limits the number of concurrent active classified listings per user.
 * Adapted from WPAdverts snippet limit-user-active-listings.
 *
 * @package Apollo\Adverts
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Adverts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Limit Active Listings per user.
 *
 * @since 1.1.0
 */
class LimitActiveListings {


	/**
	 * Default max active listings per user.
	 */
	private const DEFAULT_MAX = 10;

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// Frontend form: check before allowing new submission.
		add_filter( 'apollo/classifieds/can_submit', array( $this, 'check_limit' ), 10, 2 );

		// REST API: check before creating via API.
		add_filter( 'apollo/classifieds/pre_create', array( $this, 'check_limit_rest' ), 10, 2 );
	}

	/**
	 * Check if user has reached the active listings limit.
	 *
	 * Adapted from WPAdverts limit_user_active_listings_shortcode().
	 *
	 * @param bool $can_submit Whether the user can submit.
	 * @param int  $user_id    User ID.
	 * @return bool|\WP_Error
	 */
	public function check_limit( bool $can_submit, int $user_id ) {
		if ( ! $can_submit ) {
			return $can_submit;
		}

		$max    = $this->get_max_listings( $user_id );
		$active = $this->count_active_listings( $user_id );

		if ( $active >= $max ) {
			return new \WP_Error(
				'apollo_adverts_limit_reached',
				sprintf(
					/* translators: 1: max number, 2: active count */
					__( 'Você atingiu o limite de %1$d anúncios ativos (%2$d/%1$d). Remova ou aguarde a expiração de um anúncio existente.', 'apollo-adverts' ),
					$max,
					$active
				),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check limit for REST API creation.
	 *
	 * @param bool             $can_create Whether creation is allowed.
	 * @param \WP_REST_Request $request    REST request.
	 * @return bool|\WP_Error
	 */
	public function check_limit_rest( bool $can_create, \WP_REST_Request $request ) {
		if ( ! $can_create ) {
			return $can_create;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return $can_create;
		}

		// Admins bypass limits.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return $this->check_limit( true, $user_id );
	}

	/**
	 * Count a user's active listings.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public function count_active_listings( int $user_id ): int {
		$count = new \WP_Query(
			array(
				'post_type'      => APOLLO_CPT_CLASSIFIED,
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		return (int) $count->found_posts;
	}

	/**
	 * Get the maximum active listings for a user.
	 *
	 * Can be customized per user via user meta or per-role via filter.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private function get_max_listings( int $user_id ): int {
		// Check user-specific override.
		$user_max = get_user_meta( $user_id, '_apollo_max_classifieds', true );
		if ( $user_max && is_numeric( $user_max ) ) {
			return (int) $user_max;
		}

		// Check site-wide settings.
		$config_max = apollo_adverts_config( 'max_user_listings', self::DEFAULT_MAX );

		/**
		 * Filter the maximum active listings per user.
		 *
		 * @param int $max     Maximum active listings.
		 * @param int $user_id User ID.
		 */
		return (int) apply_filters( 'apollo/classifieds/max_active_listings', $config_max, $user_id );
	}

	/**
	 * Get user's listing status summary.
	 *
	 * Useful for frontend display.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public function get_user_status( int $user_id ): array {
		$max    = $this->get_max_listings( $user_id );
		$active = $this->count_active_listings( $user_id );

		return array(
			'active'    => $active,
			'max'       => $max,
			'remaining' => max( 0, $max - $active ),
			'can_post'  => $active < $max,
		);
	}
}
