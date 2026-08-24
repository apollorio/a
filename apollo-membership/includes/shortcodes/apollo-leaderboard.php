<?php
/**
 * Shortcode: [apollo_leaderboard]
 *
 * Display points leaderboard.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render leaderboard
 *
 * @param array $atts
 * @return string HTML
 */
function apollo_shortcode_leaderboard( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'limit'       => 10,
			'show_avatar' => 'yes',
		),
		$atts,
		'apollo_leaderboard'
	);

	$limit   = max( 1, min( 100, (int) $atts['limit'] ) );
	$leaders = apollo_get_points_leaderboard( $limit );

	if ( empty( $leaders ) ) {
		return '<p class="apollo-no-leaders">' . esc_html__( 'Nenhum dado de ranking ainda.', 'apollo-membership' ) . '</p>';
	}

	$current_user_id = get_current_user_id();

	ob_start();
	?>
	<div class="apollo-leaderboard">
		<table class="apollo-leaderboard-table">
			<thead>
				<tr>
					<th class="pos">#</th>
					<?php if ( $atts['show_avatar'] === 'yes' ) : ?>
						<th class="avatar"></th>
					<?php endif; ?>
					<th class="name"><?php esc_html_e( 'Membro', 'apollo-membership' ); ?></th>
					<th class="badge"><?php esc_html_e( 'Badge', 'apollo-membership' ); ?></th>
					<th class="points"><?php esc_html_e( 'Pontos', 'apollo-membership' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $leaders as $pos => $leader ) : ?>
					<?php
					$is_me   = ( (int) $leader->user_id === $current_user_id );
					$rank_no = $pos + 1;
					$badge   = apollo_membership_get_user_badge( (int) $leader->user_id );
					?>
					<tr class="<?php echo $is_me ? 'current-user' : ''; ?> rank-<?php echo esc_attr( $rank_no ); ?>"
						data-a-user="<?php echo esc_attr( (string) $leader->user_id ); ?>"
						data-a-component="leaderboard">
						<td class="pos">
							<?php if ( $rank_no <= 3 ) : ?>
								<span class="medal medal-<?php echo esc_attr( $rank_no ); ?>">
									<?php echo $rank_no === 1 ? '🥇' : ( $rank_no === 2 ? '🥈' : '🥉' ); ?>
								</span>
							<?php else : ?>
								<?php echo esc_html( $rank_no ); ?>
							<?php endif; ?>
						</td>
						<?php if ( $atts['show_avatar'] === 'yes' ) : ?>
							<td class="avatar"><?php echo get_avatar( (int) $leader->user_id, 32 ); ?></td>
						<?php endif; ?>
						<td class="name"><?php echo esc_html( $leader->display_name ); ?></td>
						<td class="badge"><?php echo apollo_membership_render_badge( $badge ); ?></td>
						<td class="points"><?php echo esc_html( number_format( (int) $leader->total_points ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $current_user_id ) : ?>
			<?php $my_pos = apollo_get_user_leaderboard_position( $current_user_id ); ?>
			<?php if ( $my_pos > $limit ) : ?>
				<div class="leaderboard-my-position">
					<?php echo esc_html( sprintf( __( 'Sua posição: #%d', 'apollo-membership' ), $my_pos ) ); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'apollo_leaderboard', 'apollo_shortcode_leaderboard' );
