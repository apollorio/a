<?php
/**
 * Widget: Classifieds Grid
 *
 * Lazy REST grid for apollo_classified CPT listings.
 *
 * @package Apollo\Elementor\Widgets
 */

declare(strict_types=1);

namespace Apollo\Elementor\Widgets;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ClassifiedsGrid extends Base {

	public function get_name(): string {
		return 'apollo_classifieds_grid';
	}

	public function get_title(): string {
		return __( 'Apollo Classifieds Grid', 'apollo-elementor-pro' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	protected function register_controls(): void {
		$this->add_content_section( [ 'per_page' => 12, 'columns' => 4 ] );

		$this->start_controls_section( 'section_filters', [
			'label' => __( 'Filters', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'category_slug', [
			'label'   => __( 'Category slug', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		] );

		$this->add_control( 'sort_by', [
			'label'   => __( 'Sort by', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'date',
			'options' => [
				'date'  => __( 'Newest', 'apollo-elementor-pro' ),
				'title' => __( 'Title', 'apollo-elementor-pro' ),
				'price' => __( 'Price', 'apollo-elementor-pro' ),
			],
		] );

		$this->end_controls_section();
		$this->add_shared_style_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$this->print_rest_config( 'classifieds' );
		?>
		<div class="apollo-classifieds-grid apollo-widget"
			data-per-page="<?php echo esc_attr( (string) $s['per_page'] ); ?>"
			data-columns="<?php echo esc_attr( $s['columns'] ); ?>"
			data-category="<?php echo esc_attr( $s['category_slug'] ); ?>"
			data-sort="<?php echo esc_attr( $s['sort_by'] ); ?>">
			<div class="apollo-loading"><span></span></div>
		</div>
		<?php
	}
}
