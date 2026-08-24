<?php
/**
 * Classifieds Categories Widget
 *
 * Displays classified_domain taxonomy terms (categories) list.
 * Adapted from WPAdverts categories widget pattern.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apollo_Classifieds_Categories_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'apollo_classifieds_categories',
			__( 'Apollo - Categorias de Anúncios', 'apollo-adverts' ),
			array(
				'description' => __( 'Exibe as categorias de anúncios classificados.', 'apollo-adverts' ),
				'classname'   => 'widget-apollo-classifieds-categories',
			)
		);
	}

	/**
	 * Widget output
	 */
	public function widget( $args, $instance ): void {
		$title      = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Categorias', 'apollo-adverts' );
		$title      = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$show_count = ! empty( $instance['show_count'] );
		$hide_empty = ! empty( $instance['hide_empty'] );

		$terms = get_terms(
			array(
				'taxonomy'   => APOLLO_TAX_CLASSIFIED_DOMAIN,
				'hide_empty' => $hide_empty,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		echo '<ul class="apollo-classifieds-categories-list">';
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			echo '<li>';
			printf( '<a href="%s">%s', esc_url( $link ), esc_html( $term->name ) );
			if ( $show_count ) {
				printf( ' <span class="count">(%d)</span>', $term->count );
			}
			echo '</a></li>';
		}
		echo '</ul>';

		echo $args['after_widget'];
	}

	/**
	 * Widget form
	 */
	public function form( $instance ): void {
		$title      = $instance['title'] ?? __( 'Categorias', 'apollo-adverts' );
		$show_count = ! empty( $instance['show_count'] );
		$hide_empty = ! empty( $instance['hide_empty'] );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Título:', 'apollo-adverts' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>" value="1" <?php checked( $show_count ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"><?php esc_html_e( 'Mostrar quantidade', 'apollo-adverts' ); ?></label>
		</p>
		<p>
			<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_empty' ) ); ?>" value="1" <?php checked( $hide_empty ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>"><?php esc_html_e( 'Ocultar categorias vazias', 'apollo-adverts' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Save widget options
	 */
	public function update( $new_instance, $old_instance ): array {
		return array(
			'title'      => sanitize_text_field( $new_instance['title'] ?? '' ),
			'show_count' => ! empty( $new_instance['show_count'] ),
			'hide_empty' => ! empty( $new_instance['hide_empty'] ),
		);
	}
}