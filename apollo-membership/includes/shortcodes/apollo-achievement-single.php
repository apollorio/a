<?php
/**
 * Shortcode: [apollo_achievement]
 *
 * Display a single achievement.
 * Adapted from BadgeOS single achievement shortcode.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render single achievement
 *
 * @param array $atts Shortcode attributes
 * @return string HTML
 */
function apollo_shortcode_achievement_single( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'apollo_achievement'
	);

	$ach_id = (int) $atts['id'];
	if ( ! $ach_id ) {
		return '';
	}

	$post = get_post( $ach_id );
	if ( ! $post || $post->post_status !== 'publish' ) {
		return '';
	}

	$points  = (int) get_post_meta( $ach_id, '_achievement_points', true );
	$user_id = get_current_user_id();
	$earned  = $user_id ? in_array( $ach_id, apollo_get_user_earned_achievement_ids( $user_id ), true ) : false;
	$earners = apollo_get_achievement_earners( $ach_id );
	$steps   = apollo_get_required_steps_for_achievement( $ach_id );

	ob_start();
	?>
	<div class="apollo-achievement-single <?php echo $earned ? 'earned' : ''; ?>">
		<div class="achievement-header">
			<div class="achievement-thumb">
				<?php echo apollo_get_achievement_post_thumbnail( $ach_id, 'apollo-achievement-thumb-lg' ); ?>
			</div>
			<div class="achievement-info">
				<h2><?php echo esc_html( $post->post_title ); ?></h2>
				<?php if ( $points > 0 ) : ?>
					<span class="achievement-points"><?php echo esc_html( $points ); ?> <?php esc_html_e( 'pontos', 'apollo-membership' ); ?></span>
				<?php endif; ?>
				<?php if ( $earned ) : ?>
					<span class="achievement-earned-badge"><?php esc_html_e( 'Conquistado!', 'apollo-membership' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="achievement-description">
			<?php echo wp_kses_post( $post->post_content ); ?>
		</div>

		<?php if ( ! empty( $steps ) ) : ?>
			<div class="achievement-steps">
				<h3><?php esc_html_e( 'Requisitos', 'apollo-membership' ); ?></h3>
				<ul>
					<?php foreach ( $steps as $step ) : ?>
						<?php
						$triggers      = apollo_get_activity_triggers();
						$trigger_label = $triggers[ $step->trigger_type ] ?? $step->trigger_type;
						$user_count    = $user_id ? apollo_get_user_trigger_count( $user_id, $step->trigger_type ) : 0;
						$complete      = $user_count >= (int) $step->required_count;
						?>
						<li class="<?php echo $complete ? 'complete' : 'incomplete'; ?>">
							<span class="step-status"><?php echo $complete ? '✓' : '○'; ?></span>
							<?php echo esc_html( $trigger_label ); ?>
							(<?php echo esc_html( $user_count . '/' . $step->required_count ); ?>)
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $earners ) ) : ?>
			<div class="achievement-earners">
				<h3><?php esc_html_e( 'Conquistado por', 'apollo-membership' ); ?></h3>
				<div class="earners-list">
					<?php foreach ( array_slice( $earners, 0, 10 ) as $earner ) : ?>
						<span class="earner-avatar" title="<?php echo esc_attr( $earner->display_name ); ?>">
							<?php echo get_avatar( $earner->ID, 32 ); ?>
						</span>
					<?php endforeach; ?>
					<?php if ( count( $earners ) > 10 ) : ?>
						<span class="earner-more">+<?php echo esc_html( count( $earners ) - 10 ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'apollo_achievement', 'apollo_shortcode_achievement_single' );
