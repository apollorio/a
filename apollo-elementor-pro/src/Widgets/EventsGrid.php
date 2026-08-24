<?php
/**
 * Widget: Events Grid
 *
 * Renders a REST-hydrated grid of Apollo events (CPT: apollo_event).
 *
 * @package Apollo\Elementor\Widgets
 */

declare(strict_types=1);

namespace Apollo\Elementor\Widgets;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EventsGrid extends Base {

	public function get_name(): string {
		return 'apollo_events_grid';
	}

	public function get_title(): string {
		return __( 'Apollo Events Grid', 'apollo-elementor-pro' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	protected function register_controls(): void {
		$this->add_content_section( [ 'per_page' => 9, 'columns' => 3 ] );

		$this->start_controls_section( 'section_filters', [
			'label' => __( 'Filters', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'category_filter', [
			'label'   => __( 'Category filter', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		] );

		$this->add_control( 'upcoming_only', [
			'label'        => __( 'Upcoming only', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __( 'Yes', 'apollo-elementor-pro' ),
			'label_off'    => __( 'No', 'apollo-elementor-pro' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'show_date', [
			'label'        => __( 'Show date badge', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->end_controls_section();
		$this->add_shared_style_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$this->print_rest_config( 'events' );
		?>
		<div class="apollo-events-grid apollo-widget"
			data-per-page="<?php echo esc_attr( (string) $s['per_page'] ); ?>"
			data-columns="<?php echo esc_attr( $s['columns'] ); ?>"
			data-upcoming="<?php echo esc_attr( $s['upcoming_only'] ); ?>"
			data-category="<?php echo esc_attr( $s['category_filter'] ); ?>"
			data-show-date="<?php echo esc_attr( $s['show_date'] ); ?>">
			<div class="apollo-loading"><span></span></div>
		</div>
		<?php
	}
}
