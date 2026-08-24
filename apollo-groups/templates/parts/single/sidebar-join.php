<?php
/**
 * Single Part — Sidebar Join
 *
 * Join / Leave button + Invite link.
 * Expects: $is_logged, $is_member, $group_id
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sb-join">
	<?php if ( $is_logged ) : ?>
		<?php if ( $is_member ) : ?>
			<button
				class="btn-join joined"
				id="btnJoin"
				data-group-id="<?php echo esc_attr( $group_id ); ?>"
				data-state="joined"
			>
				<i class="ri-check-line"></i> Participando
			</button>
		<?php else : ?>
			<button
				class="btn-join"
				id="btnJoin"
				data-group-id="<?php echo esc_attr( $group_id ); ?>"
				data-state="join"
			>
				<i class="ri-add-line"></i> Participar
			</button>
		<?php endif; ?>
	<?php else : ?>
		<a href="<?php echo esc_url( home_url( '/acesso/' ) ); ?>" class="btn-join">
			<i class="ri-login-box-line"></i> Entrar para participar
		</a>
	<?php endif; ?>
</div>
