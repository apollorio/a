<?php
/**
 * Shortcode: [apollo_rank]
 *
 * Display a single rank details.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render single rank
 *
 * @param array $atts
 * @return string HTML
 */
function apollo_shortcode_rank_single( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'apollo_rank'
	);

	$rank_id = (int) $atts['id'];
	if ( ! $rank_id ) {
		return '';
	}

	$rank = apollo_build_rank_object( $rank_id );
	if ( ! $rank ) {
		return '';
	}

	$user_id     = get_current_user_id();
	$user_points = $user_id ? apollo_get_users_points( $user_id ) : 0;
	$achieved    = $user_points >= $rank->points;

	// Count users with this rank
	global $wpdb;
	$table        = $wpdb->prefix . APOLLO_TABLE_RANKS;
	$holder_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$table} WHERE rank_id = %d",
			$rank_id
		)
	);

	ob_start();
	?>
	<div class="apollo-rank-single <?php echo $achieved ? 'achieved' : ''; ?>">
		<div class="rank-header">
			<img src="<?php echo esc_url( $rank->image ); ?>" alt="<?php echo esc_attr( $rank->title ); ?>" class="rank-image-lg" />
			<div class="rank-details">
				<h2><?php echo esc_html( $rank->title ); ?></h2>
				<span class="rank-priority"><?php echo esc_html( sprintf( __( 'Nível %d', 'apollo-membership' ), $rank->priority ) ); ?></span>
				<span class="rank-points-req"><?php echo esc_html( number_format( $rank->points ) ); ?> <?php esc_html_e( 'pontos necessários', 'apollo-membership' ); ?></span>
				<?php if ( $achieved ) : ?>
					<span class="rank-achieved-badge"><?php esc_html_e( 'Alcançado!', 'apollo-membership' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $rank->description ) : ?>
			<div class="rank-description"><?php echo wp_kses_post( $rank->description ); ?></div>
		<?php endif; ?>

		<div class="rank-stats">
			<span><?php echo esc_html( sprintf( __( '%d membros com este rank', 'apollo-membership' ), $holder_count ) ); ?></span>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'apollo_rank', 'apollo_shortcode_rank_single' );
