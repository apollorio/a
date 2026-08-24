<?php
/**
 * Apollo Scheduler helper functions.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if user is an Apollo scheduler agent.
 */
function apollo_scheduler_is_agent( int $user_id ): bool {
	return (bool) get_user_meta( $user_id, '_apollo_is_agent', true );
}

/**
 * Get nucleo term ID the agent belongs to.
 */
function apollo_scheduler_get_agent_nucleo( int $user_id ): int {
	return (int) get_user_meta( $user_id, '_apollo_agent_of_nucleo', true );
}

/**
 * Get agent working plan (JSON decoded).
 *
 * @return array<string, mixed>
 */
function apollo_scheduler_get_working_plan( int $user_id ): array {
	$raw = get_user_meta( $user_id, '_apollo_working_plan', true );

	if ( is_array( $raw ) ) {
		return $raw;
	}

	if ( is_string( $raw ) && $raw !== '' ) {
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	return apollo_scheduler_default_working_plan();
}

/**
 * Default Mon–Fri 09:00–18:00 working plan.
 *
 * @return array<string, mixed>
 */
function apollo_scheduler_default_working_plan(): array {
	$days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
	$plan   = array();

	foreach ( $days as $day ) {
		if ( in_array( $day, array( 'saturday', 'sunday' ), true ) ) {
			$plan[ $day ] = null;
			continue;
		}

		$plan[ $day ] = array(
			'start'  => '09:00',
			'end'    => '18:00',
			'breaks' => array(),
		);
	}

	return $plan;
}

/**
 * Build agent context using apollo_get_profile_context when available.
 *
 * @return array<string, mixed>|null
 */
function apollo_scheduler_get_agent_context( int $user_id ): ?array {
	if ( function_exists( 'apollo_get_profile_context' ) ) {
		$ctx = apollo_get_profile_context( $user_id );
		if ( is_array( $ctx ) ) {
			$ctx['is_agent']     = apollo_scheduler_is_agent( $user_id );
			$ctx['nucleo_id']    = apollo_scheduler_get_agent_nucleo( $user_id );
			$ctx['working_plan'] = apollo_scheduler_get_working_plan( $user_id );
			return $ctx;
		}
	}

	$user = get_userdata( $user_id );
	if ( ! $user instanceof \WP_User ) {
		return null;
	}

	return array(
		'user_id'      => $user_id,
		'display_name' => $user->display_name,
		'is_agent'     => apollo_scheduler_is_agent( $user_id ),
		'nucleo_id'    => apollo_scheduler_get_agent_nucleo( $user_id ),
		'working_plan' => apollo_scheduler_get_working_plan( $user_id ),
	);
}

/**
 * Whether current user can manage nucleo scheduling.
 */
function apollo_scheduler_can_manage_nucleo( int $nucleo_id, ?int $user_id = null ): bool {
	$user_id = $user_id ?? get_current_user_id();

	if ( $user_id <= 0 ) {
		return false;
	}

	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}

	return (bool) apply_filters(
		'apollo/scheduler/can_manage_nucleo',
		false,
		$nucleo_id,
		$user_id
	);
}

/**
 * Human-readable time ago via apollo_time_ago when available.
 */
function apollo_scheduler_time_ago( string $datetime ): string {
	if ( function_exists( 'apollo_time_ago' ) ) {
		return apollo_time_ago( $datetime );
	}

	$ts = strtotime( $datetime );
	if ( false === $ts ) {
		return $datetime;
	}

	return human_time_diff( $ts, time() ) . ' ' . __( 'ago', 'apollo-scheduler' );
}
