<?php
/**
 * Ativação — apollo-local
 *
 * @package Apollo\Local
 */

declare(strict_types=1);

namespace Apollo\Local;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {

	public static function activate(): void {
		self::set_defaults();
		self::create_pages();
	}

	private static function set_defaults(): void {
		$defaults = array(
			'default_style'  => 'apollo-v1',
			'per_page'       => 12,
			'default_view'   => 'grid',
			'enable_map'     => true,
			'default_center' => array( -22.9068, -43.1729 ),
			'default_zoom'   => 12,
		);

		if ( ! get_option( 'apollo_local_settings' ) ) {
			update_option( 'apollo_local_settings', $defaults );
		}
	}

	/**
	 * Cria /mapa page
	 */
	private static function create_pages(): void {
		$pages = array(
			array(
				'slug'    => 'mapa',
				'title'   => 'Mapa',
				'content' => '[apollo_map]',
			),
		);

		foreach ( $pages as $page_data ) {
			$existing = get_page_by_path( $page_data['slug'] );
			if ( $existing ) {
				continue;
			}

			wp_insert_post(
				array(
					'post_title'   => $page_data['title'],
					'post_name'    => $page_data['slug'],
					'post_content' => $page_data['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_author'  => get_current_user_id() ?: 1,
					'meta_input'   => array(
						'_apollo_virtual_page' => true,
					),
				)
			);
		}
	}
}
