<?php
/**
 * Shortcode: [apollo_user_rank]
 *
 * Display current user's rank with progress to next.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render user rank display
 *
 * @param array $atts
 * @return string HTML
 */
function apollo_shortcode_user_rank( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'user_id'       => 0,
			'show_progress' => 'yes',
		),
		$atts,
		'apollo_user_rank'
	);

	$user_id = (int) $atts['user_id'] ?: get_current_user_id();
	if ( ! $user_id ) {
		return '<p>' . esc_html__( 'Faça login para ver seu rank.', 'apollo-membership' ) . '</p>';
	}

	$rank      = apollo_get_user_rank( $user_id );
	$next_rank = apollo_get_next_rank( $user_id );
	$total_pts = apollo_get_users_points( $user_id );

	ob_start();
	?>
	<div class="apollo-user-rank">
		<?php if ( $rank ) : ?>
			<div class="current-rank">
				<img src="<?php echo esc_url( $rank->image ); ?>" alt="<?php echo esc_attr( $rank->title ); ?>" class="rank-image" />
				<div class="rank-info">
					<span class="rank-label"><?php esc_html_e( 'Rank Atual', 'apollo-membership' ); ?></span>
					<h3 class="rank-title"><?php echo esc_html( $rank->title ); ?></h3>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $atts['show_progress'] === 'yes' && $next_rank ) : ?>
			<div class="rank-progression">
				<span class="next-rank-label">
					<?php echo esc_html( sprintf( __( 'Próximo: %1$s (%2$s pts)', 'apollo-membership' ), $next_rank->title, number_format( $next_rank->points ) ) ); ?>
				</span>
				<?php
				$current_min = $rank ? $rank->points : 0;
				$range       = max( 1, $next_rank->points - $current_min );
				$progress    = min( 100, max( 0, ( ( $total_pts - $current_min ) / $range ) * 100 ) );
				$needed      = max( 0, $next_rank->points - $total_pts );
				?>
				<div class="progress-bar">
					<div class="progress-fill" style="width:<?php echo esc_attr( $progress ); ?>%"></div>
				</div>
				<span class="points-needed">
					<?php echo esc_html( sprintf( __( 'Faltam %s pontos', 'apollo-membership' ), number_format( $needed ) ) ); ?>
				</span>
			</div>
		<?php elseif ( ! $next_rank ) : ?>
			<div class="rank-max">
				<span><?php esc_html_e( 'Rank máximo alcançado!', 'apollo-membership' ); ?></span>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'apollo_user_rank', 'apollo_shortcode_user_rank' );
