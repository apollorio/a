<?php
/**
 * Shortcode: [apollo_points]
 *
 * Display user's points and point history.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render user points display
 *
 * @param array $atts Shortcode attributes
 * @return string HTML
 */
function apollo_shortcode_points( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'user_id'      => 0,
			'show_history' => 'yes',
			'limit'        => 10,
		),
		$atts,
		'apollo_points'
	);

	$user_id = (int) $atts['user_id'] ?: get_current_user_id();
	if ( ! $user_id ) {
		return '<p>' . esc_html__( 'Faça login para ver seus pontos.', 'apollo-membership' ) . '</p>';
	}

	$total    = apollo_get_users_points( $user_id );
	$awarded  = apollo_get_users_points_by_type( $user_id, 'Award' );
	$deducted = apollo_get_users_points_by_type( $user_id, 'Deduct' );
	$position = apollo_get_user_leaderboard_position( $user_id );

	ob_start();
	?>
	<div class="apollo-points-display">
		<div class="points-summary">
			<div class="points-total">
				<span class="points-number"><?php echo esc_html( number_format( $total ) ); ?></span>
				<span class="points-label"><?php esc_html_e( 'pontos', 'apollo-membership' ); ?></span>
			</div>

			<div class="points-breakdown">
				<span class="points-awarded">
					<span class="label"><?php esc_html_e( 'Recebidos:', 'apollo-membership' ); ?></span>
					<span class="value">+<?php echo esc_html( number_format( $awarded ) ); ?></span>
				</span>
				<span class="points-deducted">
					<span class="label"><?php esc_html_e( 'Deduzidos:', 'apollo-membership' ); ?></span>
					<span class="value">-<?php echo esc_html( number_format( $deducted ) ); ?></span>
				</span>
			</div>

			<?php if ( $position > 0 ) : ?>
				<div class="points-position">
					<?php echo esc_html( sprintf( __( '#%d no ranking', 'apollo-membership' ), $position ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $atts['show_history'] === 'yes' ) : ?>
			<?php
			$history = apollo_get_user_point_history( $user_id, array( 'limit' => (int) $atts['limit'] ) );
			?>
			<?php if ( ! empty( $history ) ) : ?>
				<div class="points-history">
					<h4><?php esc_html_e( 'Histórico de Pontos', 'apollo-membership' ); ?></h4>
					<table class="apollo-points-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Data', 'apollo-membership' ); ?></th>
								<th><?php esc_html_e( 'Tipo', 'apollo-membership' ); ?></th>
								<th><?php esc_html_e( 'Pontos', 'apollo-membership' ); ?></th>
								<th><?php esc_html_e( 'Motivo', 'apollo-membership' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $history as $entry ) : ?>
								<?php
								$triggers = apollo_get_activity_triggers();
								$reason   = $triggers[ $entry->this_trigger ] ?? $entry->this_trigger;
								$prefix   = $entry->type === 'Award' ? '+' : '-';
								?>
								<tr class="type-<?php echo esc_attr( strtolower( $entry->type ) ); ?>">
									<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $entry->dateadded ) ) ); ?></td>
									<td><?php echo esc_html( $entry->type ); ?></td>
									<td><?php echo esc_html( $prefix . $entry->credit ); ?></td>
									<td><?php echo esc_html( $reason ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'apollo_points', 'apollo_shortcode_points' );
