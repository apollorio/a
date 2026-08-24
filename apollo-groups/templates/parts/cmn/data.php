<?php
/**
 * Comunas — data provider (PHASE 004).
 *
 * apollo-groups stores groups in its OWN tables ({prefix}apollo_groups +
 * group_members), not a CPT — so this reads SQL directly rather than WP_Query.
 * The mockup's APOLLO_COMUNAS global is NOT ported; only its shape is honoured
 * so the ported markup keeps working while every value is real.
 *
 * Returns [] when the table is absent or empty, so the screen shows its empty
 * state rather than inventing communities.
 *
 * @package Apollo\Groups
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'apollo_cmn_list' ) ) {
	/**
	 * Public comunas with membership counts and the viewer's own role.
	 *
	 * @param int $limit Max rows.
	 * @return array<int,array<string,mixed>>
	 */
	function apollo_cmn_list( int $limit = 60 ): array {
		global $wpdb;
		$g = $wpdb->prefix . 'apollo_groups';
		$m = $wpdb->prefix . 'apollo_group_members';

		// Table may not exist on a fresh install — never fatal, just empty.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $g ) ) !== $g ) {
			return array();
		}

		$uid = get_current_user_id();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT g.id, g.slug, g.name, g.description, g.cover_image, g.created_at,
				        (SELECT COUNT(*) FROM {$m} mm WHERE mm.group_id = g.id) AS members,
				        (SELECT mr.role FROM {$m} mr WHERE mr.group_id = g.id AND mr.user_id = %d LIMIT 1) AS my_role
				 FROM {$g} g
				 WHERE g.type = 'comuna'
				 ORDER BY members DESC, g.created_at DESC
				 LIMIT %d",
				$uid,
				$limit
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $r ) {
			$cover = ! empty( $r['cover_image'] ) ? wp_get_attachment_image_url( (int) $r['cover_image'], 'medium_large' ) : '';
			$out[] = array(
				'id'      => (int) $r['id'],
				'slug'    => (string) $r['slug'],
				'name'    => (string) $r['name'],
				'desc'    => (string) $r['description'],
				'cover'   => $cover ?: '',
				'members' => (int) $r['members'],
				'role'    => (string) ( $r['my_role'] ?? '' ),
				'url'     => home_url( '/grupo/' . $r['slug'] ),
			);
		}
		return $out;
	}
}
