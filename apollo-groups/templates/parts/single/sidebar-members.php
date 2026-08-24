<?php
/**
 * Single Part — Sidebar Members
 *
 * Members list with avatars, names, roles, and mod badges.
 * Expects: $members (array), $mods (array), $member_count (int)
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $members ) ) {
	return;
}

// Show mods first, then regular members (limit 8)
$display_members = array_slice( $members, 0, 8 );
?>
<div class="sb-section">
	<div class="sb-section-title"><i class="ri-team-line"></i> Membros</div>
	<div class="sb-members-list">
		<?php
		foreach ( $display_members as $member ) :
			$avatar   = $member['avatar_url'] ?? '';
			$name     = $member['display_name'] ?? $member['user_login'] ?? 'Membro';
			$role     = $member['role'] ?? 'member';
			$is_mod   = in_array( $role, array( 'admin', 'mod' ), true );
			$username = $member['user_login'] ?? '';
			?>
			<a href="<?php echo esc_url( home_url( '/id/' . $username ) ); ?>" class="sb-member"
			data-a-user="<?php echo esc_attr( (string) ( $member['user_id'] ?? 0 ) ); ?>"
			data-a-component="group-member">
				<div class="sb-member-avatar">
					<?php if ( $avatar ) : ?>
						<img src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy">
					<?php endif; ?>
				</div>
				<div class="sb-member-info">
					<div class="sb-member-name"><?php echo esc_html( $name ); ?></div>
					<div class="sb-member-role">
						<?php
						if ( $role === 'admin' ) {
							echo 'Criador';
						} elseif ( $role === 'mod' ) {
							echo 'Moderador';
						} else {
							echo 'Membro';
						}
						?>
					</div>
				</div>
				<?php if ( $is_mod ) : ?>
					<span class="sb-member-badge">MOD</span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>

		<?php if ( $member_count > 8 ) : ?>
			<div class="sb-members-more">
				Ver todos os <?php echo esc_html( $member_count ); ?> membros →
			</div>
		<?php endif; ?>
	</div>
</div>
