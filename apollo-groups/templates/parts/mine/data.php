<?php
/**
 * Minhas Comunas / Meus Núcleos — data provider (PHASES 009 + 010).
 *
 * apollo-groups keeps groups in its OWN tables ({prefix}apollo_groups +
 * {prefix}apollo_group_members), not a CPT, so this reads SQL directly — same
 * approach as parts/cmn/data.php, which this file deliberately mirrors rather
 * than re-inventing. Schema per apollo-core DatabaseBuilder:
 *   apollo_groups        id, name, slug, description, cover_image, creator_id,
 *                        type ENUM('comuna','nucleo'),
 *                        privacy ENUM('public','private','secret'),
 *                        member_count, created_at, updated_at
 *   apollo_group_members group_id, user_id, role ENUM('member','moderator','admin')
 *
 * Nothing here is simulated: the mockup's APOLLO_COMUNAS / APOLLO_NUCLEOS
 * fixtures are not ported, only the real rows. Returns an empty list when the
 * table is missing or the user has no groups, so the screens show their empty
 * state instead of inventing communities.
 *
 * PRIVACY: both entry points are membership-scoped by an INNER JOIN on
 * group_members for the CURRENT user (plus creator_id, so an owner who was
 * never inserted as a member still sees their own group). A group the viewer
 * does not belong to can never appear here regardless of its privacy value —
 * that matters most for núcleos, which are private work teams.
 *
 * @package Apollo\Groups
 * @since   3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'apollo_grp_mine_list' ) ) {
	/**
	 * Groups of one type that the current user belongs to or owns.
	 *
	 * @param string $type    'comuna' | 'nucleo'.
	 * @param bool   $manage_only True → only where the viewer is admin/moderator/owner.
	 * @param int    $limit   Max rows.
	 * @return array<int,array<string,mixed>>
	 */
	function apollo_grp_mine_list( string $type = 'comuna', bool $manage_only = false, int $limit = 60 ): array {
		global $wpdb;

		// Whitelist, never interpolate a caller string into SQL even via prepare's
		// %s — the ENUM is fixed and anything else is a bug, not a query.
		if ( ! in_array( $type, array( 'comuna', 'nucleo' ), true ) ) {
			return array();
		}

		$uid = get_current_user_id();
		if ( ! $uid ) {
			return array();
		}

		$g = $wpdb->prefix . 'apollo_groups';
		$m = $wpdb->prefix . 'apollo_group_members';

		// Tables may not exist on a fresh install — never fatal, just empty.
		foreach ( array( $g, $m ) as $tbl ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) ) !== $tbl ) {
				return array();
			}
		}

		// Owner counts as management even without a members row; a plain member
		// row is enough for the "belongs to" view.
		$scope = $manage_only
			? "AND ( g.creator_id = %d OR me.role IN ('admin','moderator') )"
			: 'AND ( g.creator_id = %d OR me.user_id IS NOT NULL )';

		$sql = "SELECT g.id, g.slug, g.name, g.description, g.cover_image, g.privacy,
		               g.creator_id, g.created_at,
		               (SELECT COUNT(*) FROM {$m} mm WHERE mm.group_id = g.id) AS members,
		               me.role AS my_role,
		               me.joined_at AS my_joined
		        FROM {$g} g
		        LEFT JOIN {$m} me ON me.group_id = g.id AND me.user_id = %d
		        WHERE g.type = %s
		          {$scope}
		        ORDER BY members DESC, g.created_at DESC
		        LIMIT %d";

		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, $uid, $type, $uid, $limit ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $scope is a fixed literal chosen by an in-file branch; all values are placeholders.
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $r ) {
			$gid   = (int) $r['id'];
			$cover = ! empty( $r['cover_image'] )
				? wp_get_attachment_image_url( (int) $r['cover_image'], 'medium_large' )
				: '';

			$is_owner = ( (int) $r['creator_id'] === $uid );
			$role     = (string) ( $r['my_role'] ?? '' );
			if ( $is_owner ) {
				$role = 'owner';
			}

			$out[] = array(
				'id'       => $gid,
				'slug'     => (string) $r['slug'],
				'name'     => (string) $r['name'],
				'desc'     => (string) $r['description'],
				'cover'    => $cover ?: '',
				'privacy'  => (string) $r['privacy'],
				'members'  => (int) $r['members'],
				'role'     => $role,
				'is_owner' => $is_owner,
				'joined'   => (string) ( $r['my_joined'] ?? '' ),
				'created'  => (string) $r['created_at'],
				'url'      => home_url( '/grupo/' . $r['slug'] ),
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'apollo_grp_mine_role_label' ) ) {
	/**
	 * Human label for a membership role. Apollo vocabulary, not WP's.
	 *
	 * @param string $role owner|admin|moderator|member|''.
	 * @return string
	 */
	function apollo_grp_mine_role_label( string $role ): string {
		$map = array(
			'owner'     => __( 'Fundador', 'apollo-groups' ),
			'admin'     => __( 'Admin', 'apollo-groups' ),
			'moderator' => __( 'MOD', 'apollo-groups' ),
			'member'    => __( 'Membro', 'apollo-groups' ),
		);
		return $map[ $role ] ?? __( 'Membro', 'apollo-groups' );
	}
}

if ( ! function_exists( 'apollo_grp_mine_privacy_label' ) ) {
	/**
	 * Human label for a group's privacy setting.
	 *
	 * @param string $privacy public|private|secret.
	 * @return string
	 */
	function apollo_grp_mine_privacy_label( string $privacy ): string {
		$map = array(
			'public'  => __( 'Aberta', 'apollo-groups' ),
			'private' => __( 'Fechada', 'apollo-groups' ),
			'secret'  => __( 'Secreta', 'apollo-groups' ),
		);
		return $map[ $privacy ] ?? $privacy;
	}
}
