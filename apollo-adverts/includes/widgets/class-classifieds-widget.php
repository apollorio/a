<?php
/**
 * Classifieds Widget
 *
 * Displays recent/featured classifieds in a sidebar.
 * Adapted from WPAdverts widget pattern.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_Classifieds_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'apollo_classifieds',
			__( 'Apollo - Anúncios', 'apollo-adverts' ),
			array(
				'description' => __( 'Exibe anúncios recentes ou em destaque.', 'apollo-adverts' ),
				'classname'   => 'widget-apollo-classifieds',
			)
		);
	}

	/**
	 * Widget output
	 * Adapted from WPAdverts widget display pattern
	 */
	public function widget( $args, $instance ): void {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Anúncios', 'apollo-adverts' );
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$count = absint( $instance['count'] ?? 5 );
		$mode  = $instance['mode'] ?? 'recent'; // recent | featured

		$query_args = array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $mode === 'featured' ) {
			$query_args['meta_query'] = array(
				array(
					'key'   => '_classified_featured',
					'value' => '1',
				),
			);
		}

		$query = new WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			return;
		}

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		echo '<ul class="apollo-classifieds-widget-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			$price   = apollo_adverts_get_the_price( $post_id );
			$img     = apollo_adverts_get_main_image( $post_id, 'classified-thumb' );

			echo '<li class="apollo-classifieds-widget-item">';
			if ( $img ) {
				printf( '<a href="%s" class="widget-item-image"><img src="%s" alt="%s" /></a>', esc_url( get_permalink() ), esc_url( $img ), esc_attr( get_the_title() ) );
			}
			echo '<div class="widget-item-info">';
			printf( '<a href="%s" class="widget-item-title">%s</a>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
			if ( $price ) {
				printf( '<span class="widget-item-price">%s</span>', esc_html( $price ) );
			}
			echo '</div>';
			echo '</li>';
		}
		echo '</ul>';

		wp_reset_postdata();

		echo $args['after_widget'];
	}

	/**
	 * Widget form
	 */
	public function form( $instance ): void {
		$title = $instance['title'] ?? __( 'Anúncios', 'apollo-adverts' );
		$count = absint( $instance['count'] ?? 5 );
		$mode  = $instance['mode'] ?? 'recent';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Título:', 'apollo-adverts' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Quantidade:', 'apollo-adverts' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" min="1" max="20" value="<?php echo esc_attr( (string) $count ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>"><?php esc_html_e( 'Modo:', 'apollo-adverts' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mode' ) ); ?>">
				<option value="recent" <?php selected( $mode, 'recent' ); ?>><?php esc_html_e( 'Recentes', 'apollo-adverts' ); ?></option>
				<option value="featured" <?php selected( $mode, 'featured' ); ?>><?php esc_html_e( 'Destaques', 'apollo-adverts' ); ?></option>
			</select>
		</p>
		<?php
	}

	/**
	 * Save widget options
	 */
	public function update( $new_instance, $old_instance ): array {
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
			'count' => absint( $new_instance['count'] ?? 5 ),
			'mode'  => in_array( $new_instance['mode'] ?? '', array( 'recent', 'featured' ), true ) ? $new_instance['mode'] : 'recent',
		);
	}
}