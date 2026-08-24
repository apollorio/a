<?php
/**
 * Shortcode: [apollo_ranks]
 *
 * Display all available ranks.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render ranks list
 *
 * @param array $atts Shortcode attributes
 * @return string HTML
 */
function apollo_shortcode_ranks( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'user_id' => 0,
		),
		$atts,
		'apollo_ranks'
	);

	$user_id = (int) $atts['user_id'] ?: get_current_user_id();
	$ranks   = apollo_get_all_ranks();

	if ( empty( $ranks ) ) {
		return '<p class="apollo-no-ranks">' . esc_html__( 'Nenhum rank configurado.', 'apollo-membership' ) . '</p>';
	}

	$current_rank_id = $user_id ? apollo_get_user_rank_id( $user_id ) : 0;
	$user_points     = $user_id ? apollo_get_users_points( $user_id ) : 0;

	ob_start();
	?>
	<div class="apollo-ranks-list">
		<?php foreach ( $ranks as $rank ) : ?>
			<?php
			$is_current  = ( $rank->ID === $current_rank_id );
			$is_achieved = ( $user_points >= $rank->points );
			?>
			<div class="apollo-rank-item <?php echo $is_current ? 'current-rank' : ''; ?> <?php echo $is_achieved ? 'achieved' : 'locked'; ?>">
				<div class="rank-image">
					<img src="<?php echo esc_url( $rank->image ); ?>" alt="<?php echo esc_attr( $rank->title ); ?>" width="48" height="48" />
				</div>
				<div class="rank-info">
					<h4 class="rank-title">
						<?php echo esc_html( $rank->title ); ?>
						<?php if ( $is_current ) : ?>
							<span class="current-badge"><?php esc_html_e( 'Atual', 'apollo-membership' ); ?></span>
						<?php endif; ?>
					</h4>
					<span class="rank-points">
						<?php echo esc_html( number_format( $rank->points ) ); ?> <?php esc_html_e( 'pontos necessários', 'apollo-membership' ); ?>
					</span>
				</div>
				<?php if ( ! $is_achieved && $user_points > 0 ) : ?>
					<div class="rank-progress">
						<?php $pct = min( 100, ( $user_points / max( 1, $rank->points ) ) * 100 ); ?>
						<div class="progress-bar"><div class="progress-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
						<span class="progress-text"><?php echo esc_html( round( $pct ) ); ?>%</span>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'apollo_ranks', 'apollo_shortcode_ranks' );
