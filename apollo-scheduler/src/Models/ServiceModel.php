<?php
/**
 * Service model.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ServiceModel {

	/**
	 * @return array<string, mixed>|null
	 */
	public function get( int $service_id ): ?array {
		$post = get_post( $service_id );

		if ( ! $post instanceof \WP_Post || APOLLO_SCHEDULER_CPT_SERVICE !== $post->post_type ) {
			return null;
		}

		return array(
			'id'            => $service_id,
			'title'         => $post->post_title,
			'duration'      => (int) get_post_meta( $service_id, '_apollo_service_duration', true ) ?: 60,
			'price'         => (float) get_post_meta( $service_id, '_apollo_service_price', true ),
			'slot_interval' => (int) get_post_meta( $service_id, '_apollo_service_slot_interval', true ) ?: 15,
			'agents'        => $this->int_array_meta( $service_id, '_apollo_service_agents' ),
			'resources'     => $this->int_array_meta( $service_id, '_apollo_service_resources' ),
			'nucleo_id'     => (int) get_post_meta( $service_id, '_apollo_service_nucleo_id', true ),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function list_for_nucleo( int $nucleo_id ): array {
		$query = new \WP_Query(
			array(
				'post_type'      => APOLLO_SCHEDULER_CPT_SERVICE,
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'   => '_apollo_service_nucleo_id',
						'value' => $nucleo_id,
					),
				),
			)
		);

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$item = $this->get( (int) $post->ID );
				if ( $item ) {
					$items[] = $item;
				}
			}
		}

		return $items;
	}

	/**
	 * @return array<int, int>
	 */
	private function int_array_meta( int $post_id, string $key ): array {
		$raw = get_post_meta( $post_id, $key, true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		return array_values( array_map( 'intval', $raw ) );
	}
}
