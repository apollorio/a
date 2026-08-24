<?php
/**
 * Resource (room) model.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ResourceModel {

	/**
	 * @return array<string, mixed>|null
	 */
	public function get( int $resource_id ): ?array {
		$post = get_post( $resource_id );
		if ( ! $post instanceof \WP_Post || APOLLO_SCHEDULER_CPT_RESOURCE !== $post->post_type ) {
			return null;
		}

		return array(
			'id'          => $resource_id,
			'title'       => $post->post_title,
			'capacity'    => (int) get_post_meta( $resource_id, '_apollo_resource_capacity', true ) ?: 1,
			'nucleo_id'   => (int) get_post_meta( $resource_id, '_apollo_resource_nucleo_id', true ),
			'loc_id'      => (int) get_post_meta( $resource_id, '_apollo_resource_loc_id', true ),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function list_for_nucleo( int $nucleo_id ): array {
		$query = new \WP_Query(
			array(
				'post_type'      => APOLLO_SCHEDULER_CPT_RESOURCE,
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'   => '_apollo_resource_nucleo_id',
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
}
