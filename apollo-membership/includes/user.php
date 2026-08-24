<?php
/**
 * User profile — Apollo Membership (canonical wp-admin user-edit.php section).
 *
 * Replaces apollo-dj-sync "Feature Gates" on user-edit.php.
 * Writes _apollo_membership (string or array) consumed by apollo-login /app/auth.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile badge types (visible identity — not desktop/nicotine access slugs).
 *
 * @return array<string, array<string, mixed>>
 */
function apollo_membership_profile_badge_types(): array {
	$types = APOLLO_MEMBERSHIP_BADGE_TYPES;
	$skip  = array( 'app-apollodj', 'amigz', 'greatdjs' );

	return array_filter(
		$types,
		static function ( array $info, string $key ) use ( $skip ): bool {
			return ! in_array( $key, $skip, true ) && empty( $info['hidden'] );
		},
		ARRAY_FILTER_USE_BOTH
	);
}

/**
 * Desktop / nicotine access slugs (apolloDJ.exe + apollo-login).
 *
 * @return array<string, array<string, mixed>>
 */
function apollo_membership_access_slug_types(): array {
	$keys = array( 'app-apollodj', 'amigz', 'greatdjs' );
	$out  = array();

	foreach ( $keys as $key ) {
		if ( isset( APOLLO_MEMBERSHIP_BADGE_TYPES[ $key ] ) ) {
			$out[ $key ] = APOLLO_MEMBERSHIP_BADGE_TYPES[ $key ];
		}
	}

	return $out;
}

/**
 * Add membership data to user profile (admin side)
 *
 * @param WP_User $user User being edited.
 */
function apollo_membership_user_profile_data( WP_User $user ): void {
	if ( ! current_user_can( apollo_membership_get_manager_capability() ) ) {
		return;
	}

	$user_id     = $user->ID;
	$badge       = apollo_membership_get_user_badge( $user_id );
	$slugs       = apollo_membership_get_user_slugs( $user_id );
	$total_pts   = apollo_get_users_points( $user_id );
	$rank        = apollo_get_user_rank( $user_id );
	$rank_name   = $rank ? $rank->title : __( 'Sem rank', 'apollo-membership' );
	$profile_badges = apollo_membership_profile_badge_types();
	$access_slugs   = apollo_membership_access_slug_types();

	wp_nonce_field( 'apollo_membership_save_profile_' . $user_id, 'apollo_membership_profile_nonce' );

	?>
	<h2><?php esc_html_e( 'Apollo Membership', 'apollo-membership' ); ?></h2>
	<p class="description" style="margin:0 0 12px;">
		<?php esc_html_e( 'Per-user membership and apolloDJ desktop access. Source of truth for /app/auth and /dj/permissions.', 'apollo-membership' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="apollo_membership_badge"><?php esc_html_e( 'Profile badge', 'apollo-membership' ); ?></label></th>
			<td>
				<select name="apollo_membership_badge" id="apollo_membership_badge">
					<?php foreach ( $profile_badges as $key => $type ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $badge, $key ); ?>>
							<?php echo esc_html( $type['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Public badge shown on profile, feed, and chat.', 'apollo-membership' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Desktop & app access', 'apollo-membership' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Desktop and app access', 'apollo-membership' ); ?></legend>
					<?php foreach ( $access_slugs as $key => $type ) : ?>
						<label style="display:block;margin-bottom:8px;">
							<input type="checkbox"
								name="apollo_membership_access[]"
								value="<?php echo esc_attr( $key ); ?>"
								<?php checked( in_array( $key, $slugs, true ) ); ?>>
							<strong><?php echo esc_html( $type['label'] ); ?></strong>
							<?php if ( 'app-apollodj' === $key ) : ?>
								<span class="description"> — <?php esc_html_e( 'membership_active + premium gates in apolloDJ.exe', 'apollo-membership' ); ?></span>
							<?php elseif ( in_array( $key, array( 'amigz', 'greatdjs' ), true ) ) : ?>
								<span class="description"> — <?php esc_html_e( 'Nicotine+ biblioteca / transferencias tabs', 'apollo-membership' ); ?></span>
							<?php endif; ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<p class="description">
					<?php esc_html_e( 'Stored in user meta _apollo_membership (array). Site-wide nicotine allowlist: Settings → Apollo DJ.', 'apollo-membership' ); ?>
				</p>
				<?php if ( ! empty( $slugs ) ) : ?>
					<p><code><?php echo esc_html( implode( ', ', $slugs ) ); ?></code></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Gamification points', 'apollo-membership' ); ?></th>
			<td>
				<strong><?php echo esc_html( number_format( $total_pts ) ); ?></strong>
				<?php esc_html_e( 'pontos', 'apollo-membership' ); ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Current rank', 'apollo-membership' ); ?></th>
			<td><strong><?php echo esc_html( $rank_name ); ?></strong></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Achievements', 'apollo-membership' ); ?></th>
			<td>
				<?php
				$ach_count = (int) get_user_meta( $user_id, '_apollo_achievement_count', true );
				echo esc_html( sprintf( __( '%d conquistas', 'apollo-membership' ), $ach_count ) );
				?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'apollo_membership_user_profile_data', 30 );
add_action( 'edit_user_profile', 'apollo_membership_user_profile_data', 30 );

/**
 * Save membership from user profile
 *
 * @param int $user_id User ID.
 */
function apollo_membership_save_user_profile( int $user_id ): void {
	if ( ! current_user_can( apollo_membership_get_manager_capability() ) ) {
		return;
	}

	if (
		! isset( $_POST['apollo_membership_profile_nonce'] ) ||
		! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['apollo_membership_profile_nonce'] ) ),
			'apollo_membership_save_profile_' . $user_id
		)
	) {
		return;
	}

	$profile_badge = isset( $_POST['apollo_membership_badge'] )
		? sanitize_key( wp_unslash( $_POST['apollo_membership_badge'] ) )
		: 'nao-verificado';

	$raw_access = isset( $_POST['apollo_membership_access'] )
		? (array) wp_unslash( $_POST['apollo_membership_access'] )
		: array();

	$access = array_values(
		array_intersect(
			array_map( 'sanitize_key', $raw_access ),
			array_keys( apollo_membership_access_slug_types() )
		)
	);

	$slugs = array_merge( array( $profile_badge ), $access );
	$slugs = array_values( array_unique( $slugs ) );

	apollo_membership_set_user_slugs( $user_id, $slugs, get_current_user_id() );
}
add_action( 'personal_options_update', 'apollo_membership_save_user_profile' );
add_action( 'edit_user_profile_update', 'apollo_membership_save_user_profile' );

// ═══════════════════════════════════════════════════════════════════════════
// USER SUMMARY — combined data for REST / shortcodes
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get comprehensive user membership data
 *
 * @param int $user_id User ID.
 * @return array<string, mixed>
 */
function apollo_get_user_membership_summary( int $user_id ): array {
	$user = get_user_by( 'ID', $user_id );
	if ( ! $user ) {
		return array();
	}

	$badge      = apollo_membership_get_user_badge( $user_id );
	$badge_info = apollo_membership_get_badge_info( $badge );
	$rank       = apollo_get_user_rank( $user_id );
	$next_rank  = apollo_get_next_rank( $user_id );
	$total_pts  = apollo_get_users_points( $user_id );
	$position   = apollo_get_user_leaderboard_position( $user_id );

	return array(
		'user_id'              => $user_id,
		'display_name'         => $user->display_name,
		'badge'                => array(
			'type'  => $badge,
			'label' => $badge_info['label'] ?? $badge,
			'icon'  => $badge_info['icon'] ?? '',
			'color' => $badge_info['color'] ?? '',
		),
		'membership_slugs'     => apollo_membership_get_user_slugs( $user_id ),
		'points'               => array(
			'total'    => $total_pts,
			'awarded'  => apollo_get_users_points_by_type( $user_id, 'Award' ),
			'deducted' => apollo_get_users_points_by_type( $user_id, 'Deduct' ),
		),
		'rank'                 => $rank ? array(
			'id'       => $rank->ID,
			'title'    => $rank->title,
			'priority' => $rank->priority,
			'image'    => $rank->image,
		) : null,
		'next_rank'            => $next_rank ? array(
			'id'              => $next_rank->ID,
			'title'           => $next_rank->title,
			'points_required' => $next_rank->points,
			'points_needed'   => max( 0, $next_rank->points - $total_pts ),
		) : null,
		'achievements'         => (int) get_user_meta( $user_id, '_apollo_achievement_count', true ),
		'leaderboard_position' => $position,
		'online_minutes'       => (int) get_user_meta( $user_id, '_apollo_online_minutes', true ),
		'member_since'         => $user->user_registered,
	);
}

// ═══════════════════════════════════════════════════════════════════════════
// USER LIST COLUMN — admin users list
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Add badge column to admin users list
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function apollo_membership_users_columns( array $columns ): array {
	$columns['apollo_badge']  = __( 'Badge', 'apollo-membership' );
	$columns['apollo_points'] = __( 'Pontos', 'apollo-membership' );
	return $columns;
}
add_filter( 'manage_users_columns', 'apollo_membership_users_columns' );

/**
 * Render badge/points column values
 *
 * @param string $value       Column value.
 * @param string $column_name Column name.
 * @param int    $user_id     User ID.
 * @return string
 */
function apollo_membership_users_column_value( string $value, string $column_name, int $user_id ): string {
	if ( 'apollo_badge' === $column_name ) {
		$badge = apollo_membership_get_user_badge( $user_id );
		return apollo_membership_render_badge( $badge );
	}

	if ( 'apollo_points' === $column_name ) {
		return number_format( apollo_get_users_points( $user_id ) );
	}

	return $value;
}
add_filter( 'manage_users_custom_column', 'apollo_membership_users_column_value', 10, 3 );

// ═══════════════════════════════════════════════════════════════════════════
// BuddyPress / PROFILE INTEGRATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Display membership info on BuddyPress profile (if BP active)
 */
function apollo_membership_bp_profile_display(): void {
	if ( ! function_exists( 'bp_displayed_user_id' ) ) {
		return;
	}

	$user_id = bp_displayed_user_id();
	if ( ! $user_id ) {
		return;
	}

	$summary = apollo_get_user_membership_summary( $user_id );
	if ( empty( $summary ) ) {
		return;
	}

	?>
	<div class="apollo-membership-profile-widget">
		<div class="apollo-membership-badge">
			<?php echo apollo_membership_render_badge( $summary['badge']['type'] ); ?>
		</div>
		<div class="apollo-membership-points">
			<strong><?php echo esc_html( number_format( $summary['points']['total'] ) ); ?></strong>
			<span><?php esc_html_e( 'pontos', 'apollo-membership' ); ?></span>
		</div>
		<?php if ( $summary['rank'] ) : ?>
			<div class="apollo-membership-rank">
				<span class="rank-label"><?php echo esc_html( $summary['rank']['title'] ); ?></span>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'bp_before_member_header_meta', 'apollo_membership_bp_profile_display' );
