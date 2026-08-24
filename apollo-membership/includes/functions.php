<?php

/**
 * Helper Functions
 *
 * Utility functions used throughout the plugin.
 * Adapted from BadgeOS utilities + helper functions.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// SETTINGS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get plugin settings
 *
 * @return array
 */
function apollo_membership_get_settings(): array {
	$defaults = array(
		'minimum_role'        => 'manage_options',
		'debug_mode'          => 'disabled',
		'image_width'         => 50,
		'image_height'        => 50,
		'remove_on_uninstall' => 'no',
	);
	$settings = get_option( 'apollo_membership_settings', array() );
	return wp_parse_args( $settings, $defaults );
}

/**
 * Get plugin directory path
 *
 * @return string
 */
function apollo_membership_get_directory_path(): string {
	return APOLLO_MEMBERSHIP_DIR;
}

/**
 * Get plugin directory URL
 *
 * @return string
 */
function apollo_membership_get_directory_url(): string {
	return APOLLO_MEMBERSHIP_URL;
}

// ═══════════════════════════════════════════════════════════════════════════
// LOGGING — adapted from BadgeOS badgeos_post_log_entry()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Log an activity entry
 * Adapted from badgeos_post_log_entry()
 *
 * @param int    $user_id     User performing action
 * @param string $action      Action name
 * @param string $object_type Object type (achievement, points, rank, badge, etc.)
 * @param int    $object_id   Object ID
 * @param string $details     Additional details
 * @param int    $admin_id    Admin performing action (0 = system/self)
 * @return int|false Insert ID or false
 */
function apollo_membership_log( int $user_id, string $action, string $object_type = '', string $object_type_name = '', int $object_id = 0, string $details = '', int $admin_id = 0 ) {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_MEMBERSHIP_LOG;

	$result = $wpdb->insert(
		$table,
		array(
			'user_id'     => $user_id,
			'admin_id'    => $admin_id,
			'action'      => $action,
			'object_type' => $object_type_name,
			'object_id'   => $object_id,
			'details'     => $details,
			'created_at'  => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
	);

	return $result ? $wpdb->insert_id : false;
}

// ═══════════════════════════════════════════════════════════════════════════
// MEMBERSHIP BADGES — ADMIN-ONLY (no relation to gamification points)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Normalize `_apollo_membership` to array SSOT: `[profile_badge, ...access_slugs]`.
 *
 * Accepts legacy string meta or mixed arrays; always returns a non-empty array
 * with index 0 as a profile badge and remaining indices as access slugs only.
 *
 * @param mixed $value Raw usermeta value (string|array|empty).
 * @return array<int, string>
 */
function apollo_membership_normalize_storage( $value ): array {
	$default_profile = 'nao-verificado';

	if ( is_array( $value ) ) {
		$raw = $value;
	} elseif ( is_string( $value ) && '' !== trim( $value ) ) {
		$raw = array( $value );
	} else {
		return array( $default_profile );
	}

	$use_registry = class_exists( '\Apollo\Core\Config\MembershipRegistry' );
	$profile      = null;
	$access       = array();

	foreach ( $raw as $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug ) {
			continue;
		}

		if ( $use_registry ) {
			$slug = \Apollo\Core\Config\MembershipRegistry::normalize_slug( $slug );

			if ( \Apollo\Core\Config\MembershipRegistry::is_profile_slug( $slug ) ) {
				if ( null === $profile ) {
					$profile = $slug;
				}
				continue;
			}

			if ( \Apollo\Core\Config\MembershipRegistry::is_access_slug( $slug ) ) {
				$access[] = $slug;
			}
			continue;
		}

		// Fallback when apollo-core registry is unavailable.
		$access_fallback = array( 'app-apollodj', 'amigz', 'greatdjs', 'amigxs' );
		if ( in_array( $slug, $access_fallback, true ) ) {
			$access[] = $slug;
			continue;
		}

		if ( defined( 'APOLLO_MEMBERSHIP_BADGE_TYPES' ) && isset( APOLLO_MEMBERSHIP_BADGE_TYPES[ $slug ] ) ) {
			if ( null === $profile ) {
				$profile = $slug;
			}
		}
	}

	if ( null === $profile ) {
		$profile = $default_profile;
	}

	$access = array_values( array_unique( array_filter( $access ) ) );

	return array_values( array_unique( array_merge( array( $profile ), $access ) ) );
}

/**
 * Get a user's membership badge type
 *
 * @param int $user_id
 * @return string Badge type slug (e.g., 'verificado', 'dj', 'producer')
 */
function apollo_membership_get_user_badge( int $user_id ): string {
	$slugs = apollo_membership_normalize_storage( get_user_meta( $user_id, '_apollo_membership', true ) );
	$badge = $slugs[0] ?? 'nao-verificado';

	$info = apollo_membership_get_badge_info( $badge );
	if ( $info && empty( $info['hidden'] ) && 'app-apollodj' !== $badge ) {
		return $badge;
	}

	// Prefer first visible profile badge if index 0 is hidden / access-only edge case.
	foreach ( $slugs as $slug ) {
		$info = apollo_membership_get_badge_info( $slug );
		if ( $info && empty( $info['hidden'] ) && 'app-apollodj' !== $slug ) {
			if ( class_exists( '\Apollo\Core\Config\MembershipRegistry' )
				&& ! \Apollo\Core\Config\MembershipRegistry::is_profile_slug( $slug ) ) {
				continue;
			}
			return $slug;
		}
	}

	return 'nao-verificado';
}

/**
 * All membership slugs assigned to a user (_apollo_membership array SSOT).
 *
 * @param int $user_id User ID.
 * @return array<int, string>
 */
function apollo_membership_get_user_slugs( int $user_id ): array {
	return apollo_membership_normalize_storage( get_user_meta( $user_id, '_apollo_membership', true ) );
}

/**
 * Assign membership slugs for a user (admin user-edit + PMPro bridge).
 * Always persists as array SSOT via apollo_membership_normalize_storage().
 *
 * @param int               $user_id User ID.
 * @param array<int,string> $slugs   Valid badge/access slugs.
 * @param int               $admin_id Admin performing change.
 * @return bool
 */
function apollo_membership_set_user_slugs( int $user_id, array $slugs, int $admin_id = 0 ): bool {
	$old    = apollo_membership_get_user_slugs( $user_id );
	$stored = apollo_membership_normalize_storage( $slugs );

	update_user_meta( $user_id, '_apollo_membership', $stored );

	apollo_membership_log(
		$user_id,
		'membership_slugs_updated',
		'membership',
		implode( ',', $stored ),
		0,
		sprintf( 'Slugs: %s → %s', implode( ',', $old ), implode( ',', $stored ) ),
		$admin_id
	);

	do_action( 'apollo/membership/level_changed', $user_id, $stored );

	/**
	 * Fires after membership slugs change (badge + desktop/nicotine access).
	 *
	 * @param int               $user_id User ID.
	 * @param array<int,string> $slugs   New slugs.
	 * @param array<int,string> $old     Previous slugs.
	 * @param int               $admin_id Admin ID.
	 */
	do_action( 'apollo_membership_badge_assigned', $user_id, $stored[0] ?? 'nao-verificado', $old[0] ?? '', $admin_id );

	return true;
}

/**
 * Set a user's membership badge (ADMIN-ONLY)
 * This is completely separate from the gamification points system.
 *
 * @param int    $user_id   User ID
 * @param string $badge_type Badge type slug
 * @param int    $admin_id   Admin performing the action
 * @return bool
 */
function apollo_membership_set_user_badge( int $user_id, string $badge_type, int $admin_id = 0 ): bool {
	$valid_types = array_keys( APOLLO_MEMBERSHIP_BADGE_TYPES );

	if ( ! in_array( $badge_type, $valid_types, true ) ) {
		return false;
	}

	$existing = apollo_membership_get_user_slugs( $user_id );
	$access   = array_values(
		array_intersect(
			$existing,
			array( 'app-apollodj', 'amigz', 'greatdjs' )
		)
	);

	$slugs = array_merge( array( $badge_type ), $access );
	$slugs = array_values( array_unique( $slugs ) );

	return apollo_membership_set_user_slugs( $user_id, $slugs, $admin_id );
}

/**
 * Get all available membership badge types
 *
 * @return array
 */
function apollo_membership_get_badge_types(): array {
	return APOLLO_MEMBERSHIP_BADGE_TYPES;
}

/**
 * Get badge display info
 *
 * @param string $badge_type
 * @return array|null
 */
function apollo_membership_get_badge_info( string $badge_type ): ?array {
	$types = APOLLO_MEMBERSHIP_BADGE_TYPES;
	return $types[ $badge_type ] ?? null;
}

/**
 * Render a membership badge HTML (dashicons version for admin)
 *
 * @param string $badge_type
 * @param string $size 'small'|'medium'|'large'
 * @return string HTML
 */
function apollo_membership_render_badge( string $badge_type, string $size = 'small' ): string {
	$info = apollo_membership_get_badge_info( $badge_type );
	if ( ! $info ) {
		return '';
	}

	$sizes = array(
		'small'  => '16px',
		'medium' => '24px',
		'large'  => '32px',
	);
	$px    = $sizes[ $size ] ?? '16px';

	// Use html_icon if available (RemixIcon/Apollo CDN), fallback to icon class
	if ( ! empty( $info['html_icon'] ) ) {
		return sprintf(
			'<span class="apollo-membership-badge apollo-badge-%s" style="font-size:%s" title="%s">%s %s</span>',
			esc_attr( $badge_type ),
			esc_attr( $px ),
			esc_attr( $info['label'] ),
			$info['html_icon'],
			esc_html( $info['label'] )
		);
	}

	return sprintf(
		'<span class="apollo-membership-badge apollo-badge-%s" style="color:%s;font-size:%s" title="%s"><i class="%s"></i> %s</span>',
		esc_attr( $badge_type ),
		esc_attr( $info['color'] ),
		esc_attr( $px ),
		esc_attr( $info['label'] ),
		esc_attr( $info['icon'] ),
		esc_html( $info['label'] )
	);
}

/**
 * Badge type → RemixIcon mapping (for CDN frontend — no dashicons dependency).
 * Used across all Apollo frontend: navbar, feed, chat, profiles, classifieds.
 */
function apollo_membership_get_ri_icon( string $badge_type ): string {
	$map = array(
		'nao-verificado' => 'ri-user-line',
		'verificado'     => 'ri-shield-check-fill',
		'dj'             => 'ri-disc-fill',
		'producer'       => 'ri-sound-module-fill',
		'music-prod'     => 'ri-equalizer-fill',
		'visual-artist'  => 'ri-palette-fill',
		'videomaker'     => 'ri-film-fill',
		'designer'       => 'ri-layout-masonry-fill',
		'marketing'      => 'ri-megaphone-fill',
		'governmt'       => 'ri-government-fill',
		'apollo'         => 'i-apollo-fill',
		'mod'            => 'ri-shield-star-fill',
		'suspect'        => 'ri-alert-fill',
		'photographer'   => 'ri-camera-fill',
		'cenario'        => 'ri-disc-line',
		'apollodj'       => 'ri-disc-fill',
	);
	return $map[ $badge_type ] ?? 'ri-user-line';
}

/**
 * Render membership badge HTML using RemixIcon (CDN).
 * Global utility — used everywhere in the frontend.
 *
 * @param int    $user_id
 * @param string $size 'xs'|'sm'|'md'|'lg'
 * @return string HTML span with icon + label
 */
function apollo_get_membership_badge_html( int $user_id, string $size = 'sm' ): string {
	$badge_type = apollo_membership_get_user_badge( $user_id );
	$info       = apollo_membership_get_badge_info( $badge_type );
	if ( ! $info ) {
		return '';
	}

	$ri_icon = apollo_membership_get_ri_icon( $badge_type );
	$sizes   = array(
		'xs' => '11px',
		'sm' => '14px',
		'md' => '18px',
		'lg' => '24px',
	);
	$px      = $sizes[ $size ] ?? '14px';

	// Skip rendering for 'nao-verificado' unless explicitly needed
	if ( $badge_type === 'nao-verificado' ) {
		return '';
	}

	return sprintf(
		'<span class="apollo-badge apollo-badge--%s" style="display:inline-flex;align-items:center;gap:3px;color:%s;font-size:%s;font-weight:600;" title="%s">' .
			'<i class="%s" style="font-size:%s;"></i>' .
			'</span>',
		esc_attr( $badge_type ),
		esc_attr( $info['color'] ),
		esc_attr( $px ),
		esc_attr( $info['label'] ),
		esc_attr( $ri_icon ),
		esc_attr( $px )
	);
}

/**
 * Get user badge data as array (for REST API responses).
 *
 * @param int $user_id
 * @return array{type:string,label:string,icon:string,color:string,ri_icon:string}
 */
function apollo_get_user_badge_data( int $user_id ): array {
	$badge_type = apollo_membership_get_user_badge( $user_id );
	$info       = apollo_membership_get_badge_info( $badge_type );

	return array(
		'type'    => $badge_type,
		'label'   => $info['label'] ?? '',
		'icon'    => $info['icon'] ?? '',
		'color'   => $info['color'] ?? '',
		'ri_icon' => apollo_membership_get_ri_icon( $badge_type ),
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// GAMIFICATION HELPERS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get manager capability
 * Adapted from badgeos_get_manager_capability()
 *
 * @return string
 */
function apollo_membership_get_manager_capability(): string {
	$settings = apollo_membership_get_settings();
	return $settings['minimum_role'] ?? 'manage_options';
}

/**
 * Check if debug mode is enabled
 * Adapted from badgeos_is_debug_mode()
 *
 * @return bool
 */
function apollo_membership_is_debug_mode(): bool {
	$settings = apollo_membership_get_settings();
	return ( $settings['debug_mode'] ?? 'disabled' ) === 'enabled';
}

/**
 * Get default datetime string
 *
 * @param string $context
 * @return string
 */
function apollo_membership_default_datetime( string $context = 'mysql' ): string {
	return current_time( 'mysql' );
}

/**
 * Resolve timestamp column on apollo_triggers (legacy `date` vs `date_triggered`).
 *
 * @return string
 */
function apollo_membership_triggers_date_column(): string {
	static $column = null;

	if ( null !== $column ) {
		return $column;
	}

	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_TRIGGERS;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		$column = 'date_triggered';
		return $column;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$columns = $wpdb->get_col( "DESCRIBE {$table}", 0 );

	if ( is_array( $columns ) && in_array( 'date_triggered', $columns, true ) ) {
		$column = 'date_triggered';
	} elseif ( is_array( $columns ) && in_array( 'date', $columns, true ) ) {
		$column = 'date';
	} else {
		$column = 'date_triggered';
	}

	return $column;
}

/**
 * Get points by type for a user
 * Adapted from badgeos_get_points_by_type()
 *
 * @param int $point_type_id Point type ID (0 for total)
 * @param int $user_id       User ID
 * @return int
 */
function apollo_membership_get_points_by_type( int $point_type_id, int $user_id ): int {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_POINTS;

	$awarded = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(credit), 0) FROM {$table} WHERE user_id = %d AND type = 'Award' AND credit_id = %d",
			$user_id,
			$point_type_id
		)
	);

	$deducted = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(credit), 0) FROM {$table} WHERE user_id = %d AND type = 'Deduct' AND credit_id = %d",
			$user_id,
			$point_type_id
		)
	);

	return max( 0, $awarded - $deducted );
}

/**
 * Recalculate total points for a user
 * Adapted from badgeos_recalc_total_points()
 *
 * @param int $user_id
 * @return int
 */
function apollo_membership_recalc_total_points( int $user_id ): int {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_POINTS;

	$awarded = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(credit), 0) FROM {$table} WHERE user_id = %d AND type = 'Award'",
			$user_id
		)
	);

	$deducted = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(credit), 0) FROM {$table} WHERE user_id = %d AND type = 'Deduct'",
			$user_id
		)
	);

	$total = max( 0, $awarded - $deducted );

	update_user_meta( $user_id, '_apollo_points_total', $total );

	return $total;
}

/**
 * Add credit to a user
 * Adapted from badgeos_add_credit()
 *
 * @param int    $credit_id      Point type ID
 * @param int    $user_id        User ID
 * @param string $type           'Award' or 'Deduct'
 * @param int    $amount         Amount
 * @param string $trigger        Trigger name
 * @param int    $admin_id       Admin ID (0 = system)
 * @param int    $step_id        Step ID
 * @param int    $achievement_id Achievement ID
 * @return int|false
 */
function apollo_membership_add_credit( int $credit_id, int $user_id, string $type, int $amount, string $trigger = '', int $admin_id = 0, int $step_id = 0, int $achievement_id = 0 ) {
	global $wpdb;

	$table = $wpdb->prefix . APOLLO_TABLE_POINTS;

	$result = $wpdb->insert(
		$table,
		array(
			'achievement_id'     => $achievement_id,
			'credit_id'          => $credit_id,
			'step_id'            => $step_id,
			'user_id'            => $user_id,
			'admin_id'           => $admin_id,
			'type'               => $type,
			'this_trigger'       => $trigger,
			'credit'             => $amount,
			'actual_date_earned' => apollo_membership_default_datetime(),
			'date_added'         => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
	);

	if ( $result ) {
		// Recalculate total
		apollo_membership_recalc_total_points( $user_id );

		/**
		 * Fires after credit is added
		 */
		do_action( 'apollo_membership_credit_added', $user_id, $type, $amount, $credit_id, $trigger );
	}

	return $result ? $wpdb->insert_id : false;
}

/**
 * Revoke credit from a user
 *
 * @param int    $credit_id Point type ID
 * @param int    $user_id   User ID
 * @param int    $amount    Amount to deduct
 * @param string $trigger   Trigger name
 * @param int    $admin_id  Admin ID
 * @return int|false
 */
function apollo_membership_revoke_credit( int $credit_id, int $user_id, int $amount, string $trigger = '', int $admin_id = 0 ) {
	return apollo_membership_add_credit( $credit_id, $user_id, 'Deduct', $amount, $trigger, $admin_id );
}

/**
 * Award credit to a user
 *
 * @param int    $credit_id Point type ID
 * @param int    $user_id   User ID
 * @param int    $amount    Amount
 * @param string $trigger   Trigger name
 * @param int    $admin_id  Admin ID
 * @return int|false
 */
function apollo_membership_award_credit( int $credit_id, int $user_id, int $amount, string $trigger = '', int $admin_id = 0 ) {
	return apollo_membership_add_credit( $credit_id, $user_id, 'Award', $amount, $trigger, $admin_id );
}
