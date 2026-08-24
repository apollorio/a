<?php
/**
 * Rank Triggers
 *
 * Trigger definitions specific to rank advancement.
 * Adapted from BadgeOS ranks/triggers.php pattern.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get triggers that affect rank progression
 *
 * @return array trigger_hook => label
 */
function apollo_get_rank_triggers(): array {
	$triggers = array(
		'apollo_award_user_points'       => __( 'Pontos recebidos', 'apollo-membership' ),
		'apollo_deduct_points_from_user' => __( 'Pontos deduzidos', 'apollo-membership' ),
		'apollo_update_users_points'     => __( 'Pontos atualizados manualmente', 'apollo-membership' ),
		'apollo_award_achievement'       => __( 'Achievement conquistado', 'apollo-membership' ),
	);

	return (array) apply_filters( 'apollo_rank_triggers', $triggers );
}

/**
 * Check rank requirements for a triggered event
 * This is the rank rules engine, called on point changes
 *
 * @param int    $user_id
 * @param string $trigger
 * @param array  $args
 */
function apollo_maybe_check_rank_for_trigger( int $user_id, string $trigger, array $args = array() ): void {
	// Rank progression already handled by apollo_check_rank_progression()
	// hooked into point award/deduct actions
	// This function exists for extensibility via filters

	/**
	 * Filter: Additional rank conditions beyond points
	 *
	 * @param bool   $check   Whether to check rank (default true)
	 * @param int    $user_id User ID
	 * @param string $trigger Trigger name
	 * @param array  $args    Additional args
	 */
	$should_check = apply_filters( 'apollo_should_check_rank', true, $user_id, $trigger, $args );

	if ( $should_check ) {
		apollo_check_rank_progression( $user_id );
	}
}
