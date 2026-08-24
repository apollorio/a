<?php
/**
 * Shortcode: [apollo_evidence]
 *
 * Display evidence/proof for an earned achievement.
 * Adapted from BadgeOS evidence shortcode pattern.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render evidence display
 *
 * @param array $atts
 * @return string HTML
 */
function apollo_shortcode_evidence( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'user_id'        => 0,
			'achievement_id' => 0,
		),
		$atts,
		'apollo_evidence'
	);

	$user_id        = (int) $atts['user_id'] ?: get_current_user_id();
	$achievement_id = (int) $atts['achievement_id'];

	if ( ! $user_id || ! $achievement_id ) {
		return '';
	}

	// Check that user has earned this achievement
	$earned = apollo_get_user_achievements(
		array(
			'user_id'        => $user_id,
			'achievement_id' => $achievement_id,
		)
	);

	if ( empty( $earned ) ) {
		return '<p class="apollo-no-evidence">' . esc_html__( 'Nenhum registro encontrado.', 'apollo-membership' ) . '</p>';
	}

	$user        = get_user_by( 'ID', $user_id );
	$achievement = get_post( $achievement_id );
	$latest      = end( $earned );

	ob_start();
	?>
	<div class="apollo-evidence">
		<div class="evidence-header">
			<h3><?php esc_html_e( 'Comprovante de Conquista', 'apollo-membership' ); ?></h3>
		</div>
		<div class="evidence-body">
			<div class="evidence-achievement">
				<?php echo apollo_get_achievement_post_thumbnail( $achievement_id ); ?>
				<div class="evidence-details">
					<strong><?php echo esc_html( $achievement ? $achievement->post_title : $latest->achievement_title ); ?></strong>
					<span class="evidence-type"><?php echo esc_html( $latest->post_type ?? 'achievement' ); ?></span>
				</div>
			</div>
			<div class="evidence-user">
				<?php echo get_avatar( $user_id, 48 ); ?>
				<span class="evidence-username"><?php echo esc_html( $user ? $user->display_name : '' ); ?></span>
			</div>
			<div class="evidence-meta">
				<span class="evidence-date">
					<?php esc_html_e( 'Conquistado em:', 'apollo-membership' ); ?>
					<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $latest->date_earned ) ) ); ?>
				</span>
				<?php if ( ! empty( $latest->this_trigger ) ) : ?>
					<?php $triggers = apollo_get_activity_triggers(); ?>
					<span class="evidence-trigger">
						<?php esc_html_e( 'Via:', 'apollo-membership' ); ?>
						<?php echo esc_html( $triggers[ $latest->this_trigger ] ?? $latest->this_trigger ); ?>
					</span>
				<?php endif; ?>
				<?php if ( (int) $latest->points > 0 ) : ?>
					<span class="evidence-points">
						+<?php echo esc_html( $latest->points ); ?> <?php esc_html_e( 'pontos', 'apollo-membership' ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'apollo_evidence', 'apollo_shortcode_evidence' );
