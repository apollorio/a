<?php
/**
 * Widget: Earned Achievements
 *
 * Displays a user's earned achievements in sidebar.
 * Adapted from BadgeOS widgets.php pattern.
 *
 * @package Apollo\Membership
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_Earned_Achievements_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'apollo_earned_achievements',
			__( 'Apollo: Conquistas', 'apollo-membership' ),
			array( 'description' => __( 'Exibe conquistas de um usuário.', 'apollo-membership' ) )
		);
	}

	public function widget( $args, $instance ): void {
		$title   = apply_filters( 'widget_title', $instance['title'] ?? '' );
		$limit   = (int) ( $instance['limit'] ?? 5 );
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$achievements = apollo_get_user_achievements(
			array(
				'user_id'    => $user_id,
				'pagination' => true,
				'limit'      => $limit,
				'order'      => 'DESC',
			)
		);

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		if ( ! empty( $achievements ) ) {
			echo '<div class="apollo-widget-achievements">';
			foreach ( $achievements as $ach ) {
				echo '<div class="widget-achievement-item">';
				echo '<span class="widget-ach-thumb">' . apollo_get_achievement_post_thumbnail( (int) $ach->achievement_id ) . '</span>';
				echo '<span class="widget-ach-title">' . esc_html( $ach->achievement_title ) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		} else {
			echo '<p class="no-achievements">' . esc_html__( 'Nenhuma conquista ainda.', 'apollo-membership' ) . '</p>';
		}

		echo $args['after_widget'];
	}

	public function form( $instance ): void {
		$title = $instance['title'] ?? __( 'Minhas Conquistas', 'apollo-membership' );
		$limit = $instance['limit'] ?? 5;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Título:', 'apollo-membership' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Quantidade:', 'apollo-membership' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" min="1" max="20" value="<?php echo esc_attr( $limit ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ): array {
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ),
			'limit' => max( 1, min( 20, (int) $new_instance['limit'] ) ),
		);
	}
}