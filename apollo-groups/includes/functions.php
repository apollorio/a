<?php
/**
 * Apollo Groups — Helper Functions
 *
 * @package Apollo\Groups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maximum number of extra administradores that can be assigned at creation.
 */
const APOLLO_GROUPS_MAX_ADMINS = 20;

/**
 * Whether a column exists on one of the groups tables.
 * Cached per request so the create path never pays for repeated SHOW COLUMNS.
 */
function apollo_groups_table_has_column( string $table, string $column ): bool {
	global $wpdb;
	static $cache = array();

	$full = $wpdb->prefix . 'apollo_' . $table;
	if ( ! isset( $cache[ $full ] ) ) {
		$cache[ $full ] = array_column(
			(array) $wpdb->get_results( "SHOW COLUMNS FROM {$full}" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'Field'
		);
	}

	return in_array( $column, $cache[ $full ], true );
}

/**
 * Normalize a submitted list of administrador IDs.
 *
 * Drops the creator (always admin anyway), non-existent users, banned users
 * and duplicates, then caps the list so notification fan-out stays bounded.
 *
 * @param array $ids        Raw IDs from the request.
 * @param int   $group_id   Group being created/edited (0 while creating).
 * @param int   $creator_id Owner, always excluded from the extra list.
 * @return int[]
 */
function apollo_sanitize_group_admin_ids( array $ids, int $group_id, int $creator_id ): array {
	$clean = array();

	foreach ( $ids as $id ) {
		$id = absint( $id );
		if ( ! $id || $id === $creator_id || isset( $clean[ $id ] ) ) {
			continue;
		}
		if ( ! get_userdata( $id ) ) {
			continue;
		}
		if ( $group_id && apollo_is_group_banned( $group_id, $id ) ) {
			continue;
		}
		$clean[ $id ] = true;

		if ( count( $clean ) >= APOLLO_GROUPS_MAX_ADMINS ) {
			break;
		}
	}

	return array_map( 'intval', array_keys( $clean ) );
}

/**
 * Insert a user directly as administrador, or upgrade an existing membership.
 *
 * `apollo_promote_group_member()` requires an existing membership row, so it
 * cannot be used when assigning administradores at creation time.
 */
function apollo_add_group_member_as_admin( int $group_id, int $user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT role FROM {$prefix}group_members WHERE group_id = %d AND user_id = %d",
			$group_id,
			$user_id
		)
	);

	if ( null !== $existing ) {
		if ( 'admin' === $existing ) {
			return true;
		}
		return false !== $wpdb->update(
			"{$prefix}group_members",
			array( 'role' => 'admin' ),
			array(
				'group_id' => $group_id,
				'user_id'  => $user_id,
			)
		);
	}

	return false !== $wpdb->insert(
		"{$prefix}group_members",
		array(
			'group_id'  => $group_id,
			'user_id'   => $user_id,
			'role'      => 'admin',
			'joined_at' => current_time( 'mysql' ),
		)
	);
}

/**
 * Notify a user that they were made administrador of a comuna.
 * Public vocabulary only — never "grupo".
 */
function apollo_notify_group_admin_assigned( int $group_id, int $user_id, array $group ): void {
	if ( ! function_exists( 'apollo_create_notification' ) ) {
		return;
	}

	$is_nucleo = ( $group['type'] ?? 'comuna' ) === 'nucleo';
	$label     = $is_nucleo ? __( 'núcleo', 'apollo-groups' ) : __( 'comuna', 'apollo-groups' );

	apollo_create_notification(
		$user_id,
		'comuna_admin',
		sprintf(
			/* translators: %s: comuna or núcleo */
			__( 'Você é administrador de uma %s', 'apollo-groups' ),
			$label
		),
		sprintf(
			/* translators: 1: comuna or núcleo, 2: community name */
			__( 'Você foi escolhido como administrador d%1$s "%2$s".', 'apollo-groups' ),
			$is_nucleo ? 'o ' . $label : 'a ' . $label,
			$group['name'] ?? ''
		),
		home_url( '/grupo/' . ( $group['slug'] ?? $group_id ) ),
		array(
			'group_id' => $group_id,
			'role'     => 'admin',
		),
		array(
			'icon'         => 'ri-shield-star-line',
			'severity'     => 'success',
			'channel'      => 'apollo/groups',
			'action_label' => $is_nucleo ? __( 'Abrir núcleo', 'apollo-groups' ) : __( 'Abrir comuna', 'apollo-groups' ),
			'action_link'  => home_url( '/grupo/' . ( $group['slug'] ?? $group_id ) ),
		)
	);
}

/**
 * Create a new group (comuna or núcleo).
 */
function apollo_create_group( array $args ): int|false {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	$defaults = array(
		'name'        => '',
		'description' => '',
		'creator_id'  => get_current_user_id(),
		'type'        => 'comuna',
		'privacy'     => '',
		'cover_image' => 0,
		'tags'        => '',
		'rules'       => '',
		'admin_ids'   => array(),
	);

	$r = wp_parse_args( $args, $defaults );
	if ( empty( $r['name'] ) ) {
		return false;
	}

	// Validate type
	$type = in_array( $r['type'], array( 'comuna', 'nucleo' ), true ) ? $r['type'] : 'comuna';

	// SSOT: Núcleo (private/work group) is admin-created only. Trusted internal
	// callers (seeders/imports) may bypass via 'apollo/groups/allow_system_nucleo'.
	if ( 'nucleo' === $type
		&& ! current_user_can( 'manage_options' )
		&& ! apply_filters( 'apollo/groups/allow_system_nucleo', false, $r ) ) {
		return false;
	}

	// Map type → privacy if not explicitly set
	if ( empty( $r['privacy'] ) ) {
		$r['privacy'] = ( 'nucleo' === $type ) ? 'private' : 'public';
	}

	// Validate privacy
	if ( ! in_array( $r['privacy'], array( 'public', 'private', 'secret' ), true ) ) {
		$r['privacy'] = 'public';
	}

	$creator_id = (int) $r['creator_id'];
	$admin_ids  = apollo_sanitize_group_admin_ids(
		is_array( $r['admin_ids'] ) ? $r['admin_ids'] : array(),
		0,
		$creator_id
	);

	$base_slug = sanitize_title( $r['name'] );
	if ( '' === $base_slug ) {
		$base_slug = 'comuna';
	}

	$row = array(
		'name'         => sanitize_text_field( $r['name'] ),
		'slug'         => $base_slug,
		'description'  => wp_kses_post( $r['description'] ),
		'cover_image'  => (int) $r['cover_image'],
		'creator_id'   => $creator_id,
		'type'         => $type,
		'privacy'      => $r['privacy'],
		'member_count' => 1 + count( $admin_ids ),
		'created_at'   => current_time( 'mysql' ),
	);

	// Columns added by Activation::migrate(); guard so a stale schema still creates.
	if ( apollo_groups_table_has_column( 'groups', 'tags' ) ) {
		$row['tags'] = sanitize_text_field( $r['tags'] );
	}
	if ( apollo_groups_table_has_column( 'groups', 'rules' ) ) {
		$row['rules'] = sanitize_textarea_field( $r['rules'] );
	}

	$wpdb->query( 'START TRANSACTION' );

	// Insert-and-retry rather than SELECT-then-guess: the unique slug key is the
	// only authority, and a prior SELECT is racy under concurrent creates.
	$group_id = 0;
	$suppress = $wpdb->suppress_errors( true );
	for ( $attempt = 0; $attempt < 5; $attempt++ ) {
		$row['slug'] = ( 0 === $attempt ) ? $base_slug : $base_slug . '-' . wp_rand( 100, 99999 );
		if ( false !== $wpdb->insert( "{$prefix}groups", $row ) ) {
			$group_id = (int) $wpdb->insert_id;
			break;
		}
	}
	$wpdb->suppress_errors( $suppress );

	if ( ! $group_id ) {
		$wpdb->query( 'ROLLBACK' );
		return false;
	}

	$slug = $row['slug'];

	// Creator plus every selected administrador become admins immediately.
	$member_rows = array( $creator_id );
	foreach ( $admin_ids as $admin_id ) {
		$member_rows[] = $admin_id;
	}

	$assigned = array();
	foreach ( $member_rows as $member_id ) {
		if ( ! apollo_add_group_member_as_admin( $group_id, $member_id ) ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}
		if ( $member_id !== $creator_id ) {
			$assigned[] = $member_id;
		}
	}

	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$prefix}groups SET member_count = (SELECT COUNT(*) FROM {$prefix}group_members WHERE group_id = %d) WHERE id = %d",
			$group_id,
			$group_id
		)
	);

	$wpdb->query( 'COMMIT' );

	// Side effects only after the row is durable.
	if ( $assigned ) {
		$group = apollo_get_group( $group_id );
		foreach ( $assigned as $admin_id ) {
			apollo_notify_group_admin_assigned( $group_id, $admin_id, (array) $group );
		}
		do_action( 'apollo/groups/admins_assigned', $group_id, $assigned, $creator_id );
	}

	// Log activity
	if ( function_exists( 'apollo_log_activity' ) ) {
		apollo_log_activity(
			array(
				'user_id'      => $r['creator_id'],
				'component'    => 'groups',
				'type'         => 'created_group',
				'action_text'  => sprintf( 'criou %s "%s"', 'nucleo' === $type ? 'o núcleo' : 'a comuna', $r['name'] ),
				'item_id'      => $group_id,
				'primary_link' => home_url( '/grupo/' . $slug ),
			)
		);
	}

	do_action( 'apollo/groups/created', $group_id, $r );
	return $group_id;
}

/**
 * Join a group.
 */
function apollo_join_group( int $group_id, int $user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	// Check if already a member
	$exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$prefix}group_members WHERE group_id = %d AND user_id = %d",
			$group_id,
			$user_id
		)
	);
	if ( $exists ) {
		return true;
	}

	$wpdb->insert(
		"{$prefix}group_members",
		array(
			'group_id'  => $group_id,
			'user_id'   => $user_id,
			'role'      => 'member',
			'joined_at' => current_time( 'mysql' ),
		)
	);

	// Update member count
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$prefix}groups SET member_count = member_count + 1 WHERE id = %d",
			$group_id
		)
	);

	do_action( 'apollo/groups/user_joined', $group_id, $user_id );
	return true;
}

/**
 * Leave a group.
 */
function apollo_leave_group( int $group_id, int $user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	$deleted = $wpdb->delete(
		"{$prefix}group_members",
		array(
			'group_id' => $group_id,
			'user_id'  => $user_id,
		)
	);

	if ( $deleted ) {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$prefix}groups SET member_count = GREATEST(member_count - 1, 0) WHERE id = %d",
				$group_id
			)
		);

		do_action( 'apollo/groups/member_left', $group_id, $user_id );
	}

	return (bool) $deleted;
}

/**
 * Get a group by ID or slug.
 */
function apollo_get_group( int|string $id_or_slug ): ?array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	if ( is_numeric( $id_or_slug ) ) {
		$group = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$prefix}groups WHERE id = %d",
				(int) $id_or_slug
			),
			ARRAY_A
		);
	} else {
		$group = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$prefix}groups WHERE slug = %s",
				sanitize_title( $id_or_slug )
			),
			ARRAY_A
		);
	}

	return $group ?: null;
}

/**
 * Get members of a group.
 */
function apollo_get_group_members( int $group_id, int $limit = 50, int $offset = 0 ): array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gm.*, u.display_name, u.user_login, u.user_email
         FROM {$prefix}group_members gm
         LEFT JOIN {$wpdb->users} u ON gm.user_id = u.ID
         WHERE gm.group_id = %d
         ORDER BY gm.role DESC, gm.joined_at ASC
         LIMIT %d OFFSET %d",
			$group_id,
			$limit,
			$offset
		),
		ARRAY_A
	) ?: array();
}

/**
 * Check if user is a member of a group.
 */
function apollo_is_group_member( int $group_id, int $user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$prefix}group_members WHERE group_id = %d AND user_id = %d",
			$group_id,
			$user_id
		)
	);
}

/**
 * Check if a user can VIEW a group (privacy enforcement).
 *
 * - public → anyone
 * - private → members + site admins
 * - secret → members + site admins
 */
function apollo_can_view_group( array $group, int $user_id = 0 ): bool {
	$privacy = $group['privacy'] ?? 'public';

	if ( 'public' === $privacy ) {
		return true;
	}

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return false;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return apollo_is_group_member( (int) ( $group['id'] ?? 0 ), $user_id );
}

/**
 * Get user's groups.
 */
function apollo_get_user_groups( int $user_id, int $limit = 20 ): array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT g.*, gm.role, gm.joined_at
         FROM {$prefix}groups g
         INNER JOIN {$prefix}group_members gm ON g.id = gm.group_id
         WHERE gm.user_id = %d
         ORDER BY g.name ASC
         LIMIT %d",
			$user_id,
			$limit
		),
		ARRAY_A
	) ?: array();
}

/**
 * Get a user's role in a group.
 * Returns 'admin', 'moderator', 'member', 'banned', or null if not a member.
 *
 * Adapted from BuddyPress groups_is_user_admin / groups_is_user_mod / groups_is_user_banned.
 */
function apollo_get_group_role( int $group_id, int $user_id ): ?string {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	// Check bans first
	$banned = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$prefix}group_bans WHERE group_id = %d AND user_id = %d",
			$group_id,
			$user_id
		)
	);
	if ( $banned ) {
		return 'banned';
	}

	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT role FROM {$prefix}group_members WHERE group_id = %d AND user_id = %d",
			$group_id,
			$user_id
		)
	) ?: null;
}

/**
 * Check if user can manage group (admin or moderator).
 */
function apollo_user_can_manage_group( int $group_id, int $user_id ): bool {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}
	$role = apollo_get_group_role( $group_id, $user_id );
	return in_array( $role, array( 'admin', 'moderator' ), true );
}

/**
 * Check if user is group admin.
 *
 * Adapted from BuddyPress groups_is_user_admin().
 */
function apollo_is_group_admin( int $group_id, int $user_id ): bool {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}
	return apollo_get_group_role( $group_id, $user_id ) === 'admin';
}

/**
 * Check if user is banned from group.
 *
 * Adapted from BuddyPress groups_is_user_banned().
 */
function apollo_is_group_banned( int $group_id, int $user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';
	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$prefix}group_bans WHERE group_id = %d AND user_id = %d",
			$group_id,
			$user_id
		)
	);
}

/**
 * Promote a group member to moderator or admin.
 *
 * Adapted from BuddyPress groups_promote_member().
 *
 * @param int    $group_id    Group ID.
 * @param int    $user_id     User to promote.
 * @param string $role        New role: 'moderator' or 'admin'.
 * @param int    $by_user_id  Performing user (must be admin).
 */
function apollo_promote_group_member( int $group_id, int $user_id, string $role, int $by_user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	if ( ! in_array( $role, array( 'moderator', 'admin' ), true ) ) {
		return false;
	}

	// Only admins can promote
	if ( ! apollo_is_group_admin( $group_id, $by_user_id ) ) {
		return false;
	}

	// Must be a current member
	if ( ! apollo_is_group_member( $group_id, $user_id ) ) {
		return false;
	}

	$updated = $wpdb->update(
		"{$prefix}group_members",
		array( 'role' => $role ),
		array(
			'group_id' => $group_id,
			'user_id'  => $user_id,
		)
	);

	if ( $updated !== false ) {
		do_action( 'apollo/groups/member_promoted', $group_id, $user_id, $role, $by_user_id );

		// Notify the promoted user
		if ( function_exists( 'apollo_create_notification' ) ) {
			$group = apollo_get_group( $group_id );
			apollo_create_notification(
				$user_id,
				'group_promoted',
				__( 'Você foi promovido no grupo', 'apollo-groups' ),
				sprintf( __( 'Você é agora %1$s em "%2$s".', 'apollo-groups' ), $role === 'admin' ? 'admin' : 'moderador', $group['name'] ?? '' ),
				home_url( '/grupo/' . ( $group['slug'] ?? $group_id ) ),
				array(
					'group_id' => $group_id,
					'role'     => $role,
				),
				array(
					'icon'     => 'ri-arrow-up-circle-line',
					'severity' => 'success',
				)
			);
		}
	}

	return $updated !== false;
}

/**
 * Demote a group admin/moderator back to member.
 *
 * Adapted from BuddyPress groups_demote_member().
 */
function apollo_demote_group_member( int $group_id, int $user_id, int $by_user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	if ( ! apollo_is_group_admin( $group_id, $by_user_id ) ) {
		return false;
	}

	$updated = $wpdb->update(
		"{$prefix}group_members",
		array( 'role' => 'member' ),
		array(
			'group_id' => $group_id,
			'user_id'  => $user_id,
		)
	);

	if ( $updated !== false ) {
		do_action( 'apollo/groups/member_demoted', $group_id, $user_id, $by_user_id );
	}

	return $updated !== false;
}

/**
 * Ban a user from a group.
 *
 * Adapted from BuddyPress groups_ban_member().
 * Removes them from members table and adds to bans table.
 */
function apollo_ban_group_member( int $group_id, int $user_id, int $by_user_id, string $reason = '' ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	if ( ! apollo_user_can_manage_group( $group_id, $by_user_id ) ) {
		return false;
	}

	// Cannot ban an admin
	if ( apollo_is_group_admin( $group_id, $user_id ) && ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	// Remove from members
	$wpdb->delete(
		"{$prefix}group_members",
		array(
			'group_id' => $group_id,
			'user_id'  => $user_id,
		)
	);
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$prefix}groups SET member_count = GREATEST(member_count - 1, 0) WHERE id = %d",
			$group_id
		)
	);

	// Add to bans
	$wpdb->replace(
		"{$prefix}group_bans",
		array(
			'group_id'   => $group_id,
			'user_id'    => $user_id,
			'banned_by'  => $by_user_id,
			'reason'     => sanitize_text_field( $reason ),
			'created_at' => current_time( 'mysql' ),
		)
	);

	do_action( 'apollo/groups/member_banned', $group_id, $user_id, $by_user_id, $reason );

	// Notify the banned user
	if ( function_exists( 'apollo_create_notification' ) ) {
		$group = apollo_get_group( $group_id );
		apollo_create_notification(
			$user_id,
			'group_banned',
			__( 'Você foi removido de um grupo', 'apollo-groups' ),
			sprintf( __( 'Você foi removido de "%s".', 'apollo-groups' ), $group['name'] ?? '' ),
			'',
			array( 'group_id' => $group_id ),
			array(
				'icon'     => 'ri-prohibited-line',
				'severity' => 'warning',
			)
		);
	}

	return true;
}

/**
 * Unban a user from a group.
 *
 * Adapted from BuddyPress groups_unban_member().
 */
function apollo_unban_group_member( int $group_id, int $user_id, int $by_user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	if ( ! apollo_user_can_manage_group( $group_id, $by_user_id ) ) {
		return false;
	}

	$deleted = $wpdb->delete(
		"{$prefix}group_bans",
		array(
			'group_id' => $group_id,
			'user_id'  => $user_id,
		)
	);

	if ( $deleted ) {
		do_action( 'apollo/groups/member_unbanned', $group_id, $user_id, $by_user_id );
	}

	return (bool) $deleted;
}

/**
 * Remove a member from a group by an admin/mod.
 *
 * Adapted from BuddyPress groups_remove_member().
 */
function apollo_remove_group_member( int $group_id, int $user_id, int $by_user_id ): bool {
	if ( ! apollo_user_can_manage_group( $group_id, $by_user_id ) ) {
		return false;
	}
	return apollo_leave_group( $group_id, $user_id );
}

/**
 * Get banned members of a group.
 */
function apollo_get_group_bans( int $group_id ): array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gb.*, u.display_name, u.user_login
         FROM {$prefix}group_bans gb
         LEFT JOIN {$wpdb->users} u ON gb.user_id = u.ID
         WHERE gb.group_id = %d
         ORDER BY gb.created_at DESC",
			$group_id
		),
		ARRAY_A
	) ?: array();
}

// ─────────────────────────────────────────────────────────────────────────────
// INVITATIONS — Adapted from BuddyPress groups_invite_user / groups_accept_invite
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Invite a user to join a group.
 *
 * Adapted from BuddyPress groups_invite_user().
 */
function apollo_invite_to_group( int $group_id, int $user_id, int $inviter_id, string $message = '' ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	// Can't invite if already a member
	if ( apollo_is_group_member( $group_id, $user_id ) ) {
		return false;
	}

	// Can't invite if banned
	if ( apollo_is_group_banned( $group_id, $user_id ) ) {
		return false;
	}

	// Only group members can invite
	if ( ! apollo_is_group_member( $group_id, $inviter_id ) && ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	// Upsert invitation
	$exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$prefix}group_invitations WHERE group_id = %d AND user_id = %d",
			$group_id,
			$user_id
		)
	);

	if ( $exists ) {
		// Re-send: reset to pending
		$wpdb->update(
			"{$prefix}group_invitations",
			array(
				'status'     => 'pending',
				'inviter_id' => $inviter_id,
				'message'    => sanitize_textarea_field( $message ),
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'group_id' => $group_id,
				'user_id'  => $user_id,
			)
		);
	} else {
		$wpdb->insert(
			"{$prefix}group_invitations",
			array(
				'group_id'   => $group_id,
				'user_id'    => $user_id,
				'inviter_id' => $inviter_id,
				'status'     => 'pending',
				'message'    => sanitize_textarea_field( $message ),
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			)
		);
	}

	do_action( 'apollo/groups/user_invited', $group_id, $user_id, $inviter_id );

	// Notify the invitee
	if ( function_exists( 'apollo_create_notification' ) ) {
		$group   = apollo_get_group( $group_id );
		$inviter = get_userdata( $inviter_id );
		apollo_create_notification(
			$user_id,
			'group_invite',
			__( 'Convite para grupo', 'apollo-groups' ),
			sprintf(
				__( '%1$s te convidou para "%2$s".', 'apollo-groups' ),
				$inviter ? $inviter->display_name : __( 'Alguém', 'apollo-groups' ),
				$group['name'] ?? ''
			),
			home_url( '/grupos' ),
			array(
				'group_id'   => $group_id,
				'inviter_id' => $inviter_id,
			),
			array(
				'icon'         => 'ri-group-line',
				'severity'     => 'info',
				'action_label' => __( 'Ver grupos', 'apollo-groups' ),
				'action_link'  => home_url( '/grupos' ),
			)
		);
	}

	return true;
}

/**
 * Accept a group invitation.
 *
 * Adapted from BuddyPress groups_accept_invite().
 */
function apollo_accept_group_invite( int $group_id, int $user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	$invite = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$prefix}group_invitations WHERE group_id = %d AND user_id = %d AND status = 'pending'",
			$group_id,
			$user_id
		),
		ARRAY_A
	);

	if ( ! $invite ) {
		return false;
	}

	// Update invitation status
	$wpdb->update(
		"{$prefix}group_invitations",
		array(
			'status'     => 'accepted',
			'updated_at' => current_time( 'mysql' ),
		),
		array( 'id' => $invite['id'] )
	);

	// Add to group
	apollo_join_group( $group_id, $user_id );

	do_action( 'apollo/groups/invite_accepted', $group_id, $user_id, (int) $invite['inviter_id'] );

	return true;
}

/**
 * Reject a group invitation.
 *
 * Adapted from BuddyPress groups_reject_invite().
 */
function apollo_reject_group_invite( int $group_id, int $user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	$updated = $wpdb->update(
		"{$prefix}group_invitations",
		array(
			'status'     => 'rejected',
			'updated_at' => current_time( 'mysql' ),
		),
		array(
			'group_id' => $group_id,
			'user_id'  => $user_id,
			'status'   => 'pending',
		)
	);

	return $updated !== false && $updated > 0;
}

/**
 * Get all pending invitations for a user.
 *
 * Adapted from BuddyPress groups_get_invites_for_user().
 */
function apollo_get_user_group_invitations( int $user_id ): array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gi.*, g.name AS group_name, g.slug AS group_slug, g.description AS group_description,
             g.member_count, u.display_name AS inviter_name, u.user_login AS inviter_login
         FROM {$prefix}group_invitations gi
         LEFT JOIN {$prefix}groups g ON gi.group_id = g.id
         LEFT JOIN {$wpdb->users} u ON gi.inviter_id = u.ID
         WHERE gi.user_id = %d AND gi.status = 'pending'
         ORDER BY gi.created_at DESC",
			$user_id
		),
		ARRAY_A
	) ?: array();
}

/**
 * Get all pending invitations for a group (admin view).
 *
 * Adapted from BuddyPress groups_get_invites_for_group().
 */
function apollo_get_group_invitations( int $group_id ): array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gi.*, u.display_name, u.user_login, u.user_email,
             inv.display_name AS inviter_name
         FROM {$prefix}group_invitations gi
         LEFT JOIN {$wpdb->users} u ON gi.user_id = u.ID
         LEFT JOIN {$wpdb->users} inv ON gi.inviter_id = inv.ID
         WHERE gi.group_id = %d AND gi.status = 'pending'
         ORDER BY gi.created_at DESC",
			$group_id
		),
		ARRAY_A
	) ?: array();
}

/**
 * Count pending invitations for a user.
 *
 * Adapted from BuddyPress groups_get_invite_count_for_user().
 */
function apollo_get_group_invite_count_for_user( int $user_id ): int {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$prefix}group_invitations WHERE user_id = %d AND status = 'pending'",
			$user_id
		)
	);
}

// ─────────────────────────────────────────────────────────────────────────────
// MEMBERSHIP REQUESTS — Adapted from BuddyPress groups_send_membership_request
// Used for future private/closed comunas
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Send a membership request for a group.
 *
 * Adapted from BuddyPress groups_send_membership_request().
 */
function apollo_send_group_request( int $group_id, int $user_id, string $message = '' ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	// Already a member?
	if ( apollo_is_group_member( $group_id, $user_id ) ) {
		return false;
	}

	// Banned?
	if ( apollo_is_group_banned( $group_id, $user_id ) ) {
		return false;
	}

	// Already has pending request?
	$exists = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$prefix}group_requests WHERE group_id = %d AND user_id = %d AND status = 'pending'",
			$group_id,
			$user_id
		)
	);
	if ( $exists ) {
		return false;
	}

	$wpdb->insert(
		"{$prefix}group_requests",
		array(
			'group_id'   => $group_id,
			'user_id'    => $user_id,
			'message'    => sanitize_textarea_field( $message ),
			'status'     => 'pending',
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		)
	);

	do_action( 'apollo/groups/membership_requested', $group_id, $user_id );

	// Notify group admins
	if ( function_exists( 'apollo_create_notification' ) ) {
		$group  = apollo_get_group( $group_id );
		$admins = apollo_get_group_admins( $group_id );
		$user   = get_userdata( $user_id );
		foreach ( $admins as $admin ) {
			apollo_create_notification(
				(int) $admin['user_id'],
				'group_request',
				__( 'Pedido de entrada no grupo', 'apollo-groups' ),
				sprintf(
					__( '%1$s quer entrar em "%2$s".', 'apollo-groups' ),
					$user ? $user->display_name : __( 'Alguém', 'apollo-groups' ),
					$group['name'] ?? ''
				),
				home_url( '/grupo/' . ( $group['slug'] ?? $group_id ) ),
				array(
					'group_id'     => $group_id,
					'requester_id' => $user_id,
				),
				array(
					'icon'     => 'ri-user-add-line',
					'severity' => 'info',
				)
			);
		}
	}

	return true;
}

/**
 * Accept a membership request.
 *
 * Adapted from BuddyPress groups_accept_membership_request().
 */
function apollo_accept_group_request( int $group_id, int $user_id, int $by_user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	if ( ! apollo_user_can_manage_group( $group_id, $by_user_id ) ) {
		return false;
	}

	$updated = $wpdb->update(
		"{$prefix}group_requests",
		array(
			'status'     => 'accepted',
			'handled_by' => $by_user_id,
			'updated_at' => current_time( 'mysql' ),
		),
		array(
			'group_id' => $group_id,
			'user_id'  => $user_id,
			'status'   => 'pending',
		)
	);

	if ( $updated ) {
		apollo_join_group( $group_id, $user_id );
		do_action( 'apollo/groups/request_accepted', $group_id, $user_id, $by_user_id );

		if ( function_exists( 'apollo_create_notification' ) ) {
			$group = apollo_get_group( $group_id );
			apollo_create_notification(
				$user_id,
				'group_request_accepted',
				__( 'Pedido aceito!', 'apollo-groups' ),
				sprintf( __( 'Você foi aceito em "%s".', 'apollo-groups' ), $group['name'] ?? '' ),
				home_url( '/grupo/' . ( $group['slug'] ?? $group_id ) ),
				array( 'group_id' => $group_id ),
				array(
					'icon'     => 'ri-check-line',
					'severity' => 'success',
				)
			);
		}
	}

	return (bool) $updated;
}

/**
 * Reject a membership request.
 *
 * Adapted from BuddyPress groups_reject_membership_request().
 */
function apollo_reject_group_request( int $group_id, int $user_id, int $by_user_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	if ( ! apollo_user_can_manage_group( $group_id, $by_user_id ) ) {
		return false;
	}

	$updated = $wpdb->update(
		"{$prefix}group_requests",
		array(
			'status'     => 'rejected',
			'handled_by' => $by_user_id,
			'updated_at' => current_time( 'mysql' ),
		),
		array(
			'group_id' => $group_id,
			'user_id'  => $user_id,
			'status'   => 'pending',
		)
	);

	return $updated !== false && $updated > 0;
}

/**
 * Get all pending requests for a group.
 *
 * Adapted from BuddyPress groups_get_requests().
 */
function apollo_get_group_requests( int $group_id ): array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gr.*, u.display_name, u.user_login, u.user_email
         FROM {$prefix}group_requests gr
         LEFT JOIN {$wpdb->users} u ON gr.user_id = u.ID
         WHERE gr.group_id = %d AND gr.status = 'pending'
         ORDER BY gr.created_at ASC",
			$group_id
		),
		ARRAY_A
	) ?: array();
}

/**
 * Get group admins (users with role = 'admin').
 *
 * Adapted from BuddyPress groups_get_group_admins().
 */
function apollo_get_group_admins( int $group_id ): array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gm.user_id, u.display_name, u.user_login
         FROM {$prefix}group_members gm
         LEFT JOIN {$wpdb->users} u ON gm.user_id = u.ID
         WHERE gm.group_id = %d AND gm.role = 'admin'",
			$group_id
		),
		ARRAY_A
	) ?: array();
}

/**
 * Get group moderators.
 *
 * Adapted from BuddyPress groups_get_group_mods().
 */
function apollo_get_group_mods( int $group_id ): array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT gm.user_id, u.display_name, u.user_login
         FROM {$prefix}group_members gm
         LEFT JOIN {$wpdb->users} u ON gm.user_id = u.ID
         WHERE gm.group_id = %d AND gm.role = 'moderator'",
			$group_id
		),
		ARRAY_A
	) ?: array();
}

/**
 * apollo_get_groups overload: support type filtering.
 * The original function didn't accept $type. Replace it.
 */
function apollo_get_groups( int $limit = 20, int $offset = 0, string $search = '', string $type = '' ): array {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	$where  = "WHERE privacy = 'public'";
	$params = array();

	if ( $search ) {
		$like     = '%' . $wpdb->esc_like( $search ) . '%';
		$where   .= ' AND (name LIKE %s OR description LIKE %s)';
		$params[] = $like;
		$params[] = $like;
	}

	if ( $type ) {
		$where   .= ' AND type = %s';
		$params[] = $type;
	}

	$params[] = $limit;
	$params[] = $offset;

	$sql = "SELECT * FROM {$prefix}groups {$where} ORDER BY member_count DESC, created_at DESC LIMIT %d OFFSET %d";

	return $wpdb->get_results(
		empty( $params ) ? $sql : $wpdb->prepare( $sql, ...$params ),
		ARRAY_A
	) ?: array();
}

/**
 * Search groups by term.
 *
 * Adapted from BuddyPress groups_search_groups() equivalent.
 */
function apollo_search_groups( string $term, int $limit = 20 ): array {
	return apollo_get_groups( $limit, 0, $term );
}

/**
 * Get total group count.
 *
 * Adapted from BuddyPress groups_get_total_group_count().
 */
function apollo_get_total_group_count( string $type = '' ): int {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';
	if ( $type ) {
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$prefix}groups WHERE privacy = 'public' AND type = %s",
				$type
			)
		);
	}
	return (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$prefix}groups WHERE privacy = 'public'"
	);
}

/**
 * Get total groups for a user.
 *
 * Adapted from BuddyPress groups_total_groups_for_user().
 */
function apollo_get_user_group_count( int $user_id ): int {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$prefix}group_members WHERE user_id = %d",
			$user_id
		)
	);
}

/**
 * Transfer group ownership to another member.
 * (No BP equivalent — Apollo-native)
 */
function apollo_transfer_group_ownership( int $group_id, int $new_owner_id, int $current_owner_id ): bool {
	global $wpdb;
	$prefix = $wpdb->prefix . 'apollo_';

	if ( ! apollo_is_group_admin( $group_id, $current_owner_id ) ) {
		return false;
	}

	if ( ! apollo_is_group_member( $group_id, $new_owner_id ) ) {
		return false;
	}

	// Demote current owner to member
	$wpdb->update(
		"{$prefix}group_members",
		array( 'role' => 'member' ),
		array(
			'group_id' => $group_id,
			'user_id'  => $current_owner_id,
		)
	);

	// Promote new owner to admin
	$wpdb->update(
		"{$prefix}group_members",
		array( 'role' => 'admin' ),
		array(
			'group_id' => $group_id,
			'user_id'  => $new_owner_id,
		)
	);

	// Update creator_id
	$wpdb->update(
		"{$prefix}groups",
		array( 'creator_id' => $new_owner_id ),
		array( 'id' => $group_id )
	);

	do_action( 'apollo/groups/ownership_transferred', $group_id, $new_owner_id, $current_owner_id );
	return true;
}
