<?php
/**
 * Users integration — extends apollo-users agent meta surface.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UsersIntegration {

	public function __construct() {
		add_filter( 'apollo/users/profile_sections', array( $this, 'add_profile_section' ) );
		add_filter( 'apollo/scheduler/can_manage_nucleo', array( $this, 'default_nucleo_manager_check' ), 10, 3 );
	}

	/**
	 * @param array<int, array<string, mixed>> $sections
	 * @return array<int, array<string, mixed>>
	 */
	public function add_profile_section( array $sections ): array {
		$sections[] = array(
			'slug'  => 'scheduler-agent',
			'label' => __( 'Agent Availability', 'apollo-scheduler' ),
			'url'   => home_url( '/agent/availability' ),
		);
		return $sections;
	}

	public function default_nucleo_manager_check( bool $allowed, int $nucleo_id, int $user_id ): bool {
		if ( $allowed ) {
			return true;
		}

		$managed = get_user_meta( $user_id, '_apollo_manages_nucleo', true );
		if ( is_array( $managed ) ) {
			return in_array( $nucleo_id, array_map( 'intval', $managed ), true );
		}

		return (int) $managed === $nucleo_id;
	}
}
