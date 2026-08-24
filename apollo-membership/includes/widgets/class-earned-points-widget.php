<?php
/**
 * Widget: Earned Points
 *
 * Displays user's points total and position in sidebar.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_Earned_Points_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'apollo_earned_points',
			__( 'Apollo: Pontos', 'apollo-membership' ),
			array( 'description' => __( 'Exibe total de pontos do usuário.', 'apollo-membership' ) )
		);
	}

	public function widget( $args, $instance ): void {
		$title   = apply_filters( 'widget_title', $instance['title'] ?? '' );
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$total    = apollo_get_users_points( $user_id );
		$position = apollo_get_user_leaderboard_position( $user_id );

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		echo '<div class="apollo-widget-points">';
		echo '<div class="widget-points-total">';
		echo '<span class="points-number">' . esc_html( number_format( $total ) ) . '</span>';
		echo '<span class="points-label">' . esc_html__( 'pontos', 'apollo-membership' ) . '</span>';
		echo '</div>';

		if ( $position > 0 ) {
			echo '<div class="widget-points-position">';
			echo '<span>' . esc_html( sprintf( __( '#%d no ranking', 'apollo-membership' ), $position ) ) . '</span>';
			echo '</div>';
		}

		// Progress to next rank
		$next_rank = apollo_get_next_rank( $user_id );
		if ( $next_rank ) {
			$rank    = apollo_get_user_rank( $user_id );
			$min_pts = $rank ? $rank->points : 0;
			$range   = max( 1, $next_rank->points - $min_pts );
			$pct     = min( 100, max( 0, ( ( $total - $min_pts ) / $range ) * 100 ) );

			echo '<div class="widget-rank-progress">';
			echo '<span class="next-rank">' . esc_html( $next_rank->title ) . '</span>';
			echo '<div class="progress-bar"><div class="progress-fill" style="width:' . esc_attr( $pct ) . '%"></div></div>';
			echo '</div>';
		}

		echo '</div>';

		echo $args['after_widget'];
	}

	public function form( $instance ): void {
		$title = $instance['title'] ?? __( 'Meus Pontos', 'apollo-membership' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Título:', 'apollo-membership' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ),
		);
	}
}