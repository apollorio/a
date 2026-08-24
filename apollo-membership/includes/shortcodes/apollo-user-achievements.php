<?php
/**
 * Shortcode: [apollo_user_achievements]
 *
 * Display achievements earned by a user.
 * Adapted from BadgeOS shortcodes/badgeos_user_earned_achievements.php
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render user achievements
 *
 * @param array $atts Shortcode attributes
 * @return string HTML
 */
function apollo_shortcode_user_achievements( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'user_id' => 0,
			'type'    => '',
			'limit'   => 20,
			'order'   => 'DESC',
		),
		$atts,
		'apollo_user_achievements'
	);

	$user_id = (int) $atts['user_id'] ?: get_current_user_id();
	if ( ! $user_id ) {
		return '<p>' . esc_html__( 'Faça login para ver suas conquistas.', 'apollo-membership' ) . '</p>';
	}

	$args = array(
		'user_id' => $user_id,
		'order'   => $atts['order'],
	);

	if ( $atts['type'] ) {
		$args['achievement_type'] = sanitize_text_field( $atts['type'] );
	}

	if ( (int) $atts['limit'] > 0 ) {
		$args['pagination'] = true;
		$args['limit']      = (int) $atts['limit'];
	}

	$achievements = apollo_get_user_achievements( $args );

	if ( empty( $achievements ) ) {
		return '<p class="apollo-no-achievements">' . esc_html__( 'Nenhuma conquista ainda.', 'apollo-membership' ) . '</p>';
	}

	$user         = get_user_by( 'ID', $user_id );
	$display_name = $user ? $user->display_name : '';

	ob_start();
	?>
	<div class="apollo-user-achievements">
		<?php if ( $display_name && $user_id !== get_current_user_id() ) : ?>
			<h3><?php echo esc_html( sprintf( __( 'Conquistas de %s', 'apollo-membership' ), $display_name ) ); ?></h3>
		<?php endif; ?>

		<div class="achievements-grid">
			<?php foreach ( $achievements as $ach ) : ?>
				<div class="achievement-earned-item">
					<div class="achievement-thumb">
						<?php echo apollo_get_achievement_post_thumbnail( (int) $ach->achievement_id ); ?>
					</div>
					<div class="achievement-meta">
						<span class="achievement-title"><?php echo esc_html( $ach->achievement_title ); ?></span>
						<span class="achievement-date"><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $ach->date_earned ) ) ); ?></span>
						<?php if ( (int) $ach->points > 0 ) : ?>
							<span class="achievement-points">+<?php echo esc_html( $ach->points ); ?> pts</span>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'apollo_user_achievements', 'apollo_shortcode_user_achievements' );
