<?php
/**
 * Paid Memberships Pro bridge (optional).
 *
 * Loads only when PMPro is active. Maps PMPro levels → _apollo_membership slugs
 * and enriches JWT payloads for apolloDJ when jwt-auth filter is present.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
	return;
}

/**
 * Map PMPro level name/id to Apollo membership slugs.
 *
 * @param int $level_id PMPro level ID.
 * @param string $level_name PMPro level name.
 * @return array<int, string>
 */
function apollo_membership_pmpro_level_to_slugs( int $level_id, string $level_name ): array {
	$name = strtolower( trim( $level_name ) );
	$slugs = array( 'verificado' );

	if ( str_contains( $name, 'pro' ) || str_contains( $name, 'enterprise' ) ) {
		$slugs[] = 'app-apollodj';
		$slugs[] = 'greatdjs';
	}

	if ( str_contains( $name, 'amig' ) || str_contains( $name, 'nicotine' ) ) {
		$slugs[] = 'amigz';
	}

	return array_values( array_unique( $slugs ) );
}

add_action(
	'pmpro_after_change_membership_level',
	static function ( int $level_id, int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		if ( $level_id <= 0 ) {
			apollo_membership_set_user_slugs( $user_id, array( 'nao-verificado' ), 0 );
			return;
		}

		$level = function_exists( 'pmpro_getLevel' ) ? pmpro_getLevel( $level_id ) : null;
		$name  = is_object( $level ) && isset( $level->name ) ? (string) $level->name : '';
		$slugs = apollo_membership_pmpro_level_to_slugs( $level_id, $name );

		apollo_membership_set_user_slugs( $user_id, $slugs, 0 );
		do_action( 'apollo/membership/level_changed', $user_id, $slugs );
	},
	10,
	2
);

add_filter(
	'jwt_auth_token_before_dispatch',
	static function ( array $data, \WP_User $user ): array {
		$level = pmpro_getMembershipLevelForUser( $user->ID );
		$name  = $level && isset( $level->name ) ? (string) $level->name : 'basic';
		$slugs = apollo_membership_get_user_slugs( $user->ID );

		$data['apollo'] = array(
			'level'          => sanitize_text_field( $name ),
			'memberships'    => $slugs,
			'visible_tabs'   => in_array( 'app-apollodj', $slugs, true )
				? array( 'coletanea', 'prep', 'usb', 'reports', 'advanced' )
				: array( 'coletanea', 'prep' ),
			'gamification'   => (int) apollo_get_users_points( $user->ID ),
			'membership_active' => in_array( 'app-apollodj', $slugs, true ),
		);

		return $data;
	},
	10,
	2
);
