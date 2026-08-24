<?php
/**
 * Widget: Earned Ranks
 *
 * Displays user's current rank in sidebar.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_Earned_Ranks_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'apollo_earned_ranks',
			__( 'Apollo: Rank', 'apollo-membership' ),
			array( 'description' => __( 'Exibe o rank atual do usuário.', 'apollo-membership' ) )
		);
	}

	public function widget( $args, $instance ): void {
		$title   = apply_filters( 'widget_title', $instance['title'] ?? '' );
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$rank      = apollo_get_user_rank( $user_id );
		$next_rank = apollo_get_next_rank( $user_id );
		$badge     = apollo_membership_get_user_badge( $user_id );

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		echo '<div class="apollo-widget-rank">';

		// Badge display
		echo '<div class="widget-badge">';
		echo apollo_membership_render_badge( $badge );
		echo '</div>';

		// Current rank
		if ( $rank ) {
			echo '<div class="widget-current-rank">';
			echo '<img src="' . esc_url( $rank->image ) . '" alt="' . esc_attr( $rank->title ) . '" class="rank-thumb" width="48" height="48" />';
			echo '<span class="rank-title">' . esc_html( $rank->title ) . '</span>';
			echo '</div>';
		}

		// Next rank info
		if ( $next_rank ) {
			$total  = apollo_get_users_points( $user_id );
			$needed = max( 0, $next_rank->points - $total );
			echo '<div class="widget-next-rank">';
			echo '<span class="next-label">' . esc_html__( 'Próximo:', 'apollo-membership' ) . ' ' . esc_html( $next_rank->title ) . '</span>';
			echo '<span class="points-needed">' . esc_html( sprintf( __( 'Faltam %s pts', 'apollo-membership' ), number_format( $needed ) ) ) . '</span>';
			echo '</div>';
		}

		echo '</div>';

		echo $args['after_widget'];
	}

	public function form( $instance ): void {
		$title = $instance['title'] ?? __( 'Meu Rank', 'apollo-membership' );
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