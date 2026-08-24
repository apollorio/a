<?php
/**
 * Shortcode: [apollo_achievements]
 *
 * Lists all available achievements.
 * Adapted from BadgeOS shortcodes/badgeos_achievements_list.php
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render achievements list
 *
 * @param array $atts Shortcode attributes
 * @return string HTML
 */
function apollo_shortcode_achievements_list( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'type'    => '',
			'limit'   => 10,
			'orderby' => 'menu_order',
			'order'   => 'ASC',
			'user_id' => 0,
			'show'    => 'all', // 'all'|'earned'|'unearned'
			'wpms'    => false,
		),
		$atts,
		'apollo_achievements'
	);

	$limit   = (int) $atts['limit'];
	$user_id = (int) $atts['user_id'] ?: get_current_user_id();

	// Query achievement posts
	$query_args = array(
		'post_type'      => 'apollo_achievement',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => $atts['orderby'],
		'order'          => $atts['order'],
	);

	if ( $atts['type'] ) {
		$query_args['meta_query'] = array(
			array(
				'key'   => '_achievement_type',
				'value' => sanitize_text_field( $atts['type'] ),
			),
		);
	}

	$achievements = new WP_Query( $query_args );

	if ( ! $achievements->have_posts() ) {
		return '<p class="apollo-no-achievements">' . esc_html__( 'Nenhuma conquista encontrada.', 'apollo-membership' ) . '</p>';
	}

	$earned_ids = $user_id ? apollo_get_user_earned_achievement_ids( $user_id ) : array();

	ob_start();
	?>
	<div class="apollo-achievements-list">
		<?php
		while ( $achievements->have_posts() ) :
			$achievements->the_post();
			?>
			<?php
			$ach_id = get_the_ID();
			$earned = in_array( $ach_id, $earned_ids, true );

			if ( $atts['show'] === 'earned' && ! $earned ) {
				continue;
			}
			if ( $atts['show'] === 'unearned' && $earned ) {
				continue;
			}

			$points = (int) get_post_meta( $ach_id, '_achievement_points', true );
			?>
			<div class="apollo-achievement-item <?php echo $earned ? 'earned' : 'not-earned'; ?>">
				<div class="achievement-thumb">
					<?php echo apollo_get_achievement_post_thumbnail( $ach_id ); ?>
				</div>
				<div class="achievement-content">
					<h3 class="achievement-title"><?php the_title(); ?></h3>
					<?php if ( $points > 0 ) : ?>
						<span class="achievement-points"><?php echo esc_html( $points ); ?> <?php esc_html_e( 'pts', 'apollo-membership' ); ?></span>
					<?php endif; ?>
					<div class="achievement-excerpt"><?php the_excerpt(); ?></div>
					<?php if ( $earned ) : ?>
						<span class="achievement-earned-badge"><?php esc_html_e( 'Conquistado!', 'apollo-membership' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		<?php endwhile; ?>
	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'apollo_achievements', 'apollo_shortcode_achievements_list' );
