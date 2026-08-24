<?php

/**
 * Migration — Converts legacy _hub_links to _hub_blocks format.
 *
 * Runs once per hub post on first access. Preserves _hub_links for backward compat.
 * Also migrates _hub_socials into a 'social' block appended after links.
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migration {


	/**
	 * Hook into hub data retrieval to auto-migrate.
	 */
	public function __construct() {
		add_action( 'apollo/hub/created', array( $this, 'initialize_blocks' ), 10, 2 );
	}

	/**
	 * Migrate a hub post from _hub_links to _hub_blocks.
	 *
	 * Idempotent: skips if _hub_blocks already exist.
	 *
	 * @param  int $post_id Hub post ID.
	 * @return bool True if migrated, false if already had blocks or nothing to migrate.
	 */
	public static function maybe_migrate( int $post_id ): bool {
		$existing_blocks = get_post_meta( $post_id, '_hub_blocks', true );

		// Already has blocks — skip
		if ( ! empty( $existing_blocks ) ) {
			$decoded = json_decode( $existing_blocks, true );
			if ( is_array( $decoded ) && ! empty( $decoded ) ) {
				return false;
			}
		}

		$links_raw = get_post_meta( $post_id, '_hub_links', true );
		$links     = is_array( $links_raw ) ? $links_raw : ( json_decode( (string) $links_raw, true ) ?: array() );

		$socials_raw = get_post_meta( $post_id, '_hub_socials', true );
		$socials     = is_array( $socials_raw ) ? $socials_raw : ( json_decode( (string) $socials_raw, true ) ?: array() );

		$blocks = array();

		// Convert socials to a social block (first in the list)
		if ( ! empty( $socials ) ) {
			$icons = array();
			foreach ( $socials as $social ) {
				$network = $social['network'] ?? '';
				$url     = $social['url'] ?? '';
				$icon    = $social['icon'] ?? '';

				if ( empty( $network ) || empty( $url ) ) {
					continue;
				}

				// Convert RemixIcon class to class name
				if ( empty( $icon ) ) {
					$icon = APOLLO_HUB_SOCIAL_ICONS[ $network ] ?? 'ri-link-m';
				}

				$icons[] = array(
					'icon'  => $icon,
					'url'   => $url,
					'label' => ucfirst( $network ),
				);
			}

			if ( ! empty( $icons ) ) {
				$blocks[] = array(
					'type'   => 'social',
					'id'     => wp_generate_uuid4(),
					'active' => true,
					'data'   => array(
						'icons'     => $icons,
						'size'      => 'md',
						'alignment' => 'center',
					),
				);
			}
		}

		// Convert links to link blocks
		foreach ( $links as $link ) {
			$title = $link['title'] ?? '';
			$url   = $link['url'] ?? '';
			$icon  = $link['icon'] ?? 'ri-link-m';

			if ( empty( $url ) ) {
				continue;
			}

			$active = isset( $link['active'] ) ? (bool) $link['active'] : true;

			$blocks[] = array(
				'type'   => 'link',
				'id'     => wp_generate_uuid4(),
				'active' => $active,
				'data'   => array(
					'title'     => $title,
					'sub'       => '',
					'url'       => $url,
					'icon'      => $icon,
					'variant'   => 'default',
					'bgColor'   => '',
					'textColor' => '',
					'iconBg'    => '',
					'badge'     => '',
				),
			);
		}

		// Only save if we have something to migrate
		if ( empty( $blocks ) ) {
			return false;
		}

		update_post_meta( $post_id, '_hub_blocks', wp_json_encode( $blocks ) );

		do_action( 'apollo/hub/migrated', $post_id, count( $blocks ) );

		return true;
	}

	/**
	 * Initialize empty blocks array when a new hub is created.
	 *
	 * @param int $post_id Hub post ID.
	 * @param int $user_id User ID.
	 */
	public function initialize_blocks( int $post_id, int $user_id ): void {
		$existing = get_post_meta( $post_id, '_hub_blocks', true );
		if ( empty( $existing ) ) {
			update_post_meta( $post_id, '_hub_blocks', wp_json_encode( array() ) );
		}
	}

	/**
	 * Batch migrate all hubs (admin tool).
	 *
	 * @return array ['migrated' => int, 'skipped' => int, 'total' => int]
	 */
	public static function migrate_all(): array {
		$posts = get_posts(
			array(
				'post_type'      => APOLLO_HUB_CPT,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		$migrated = 0;
		$skipped  = 0;

		foreach ( $posts as $post_id ) {
			if ( self::maybe_migrate( $post_id ) ) {
				++$migrated;
			} else {
				++$skipped;
			}
		}

		return array(
			'migrated' => $migrated,
			'skipped'  => $skipped,
			'total'    => count( $posts ),
		);
	}
}
