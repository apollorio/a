<?php
/**
 * Widget: Hub Listing
 *
 * REST-hydrated list of Apollo Hubs (CPT: hub) with GSAP scroll-trigger entrance.
 *
 * @package Apollo\Elementor\Widgets
 */

declare(strict_types=1);

namespace Apollo\Elementor\Widgets;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HubListing extends Base {

	public function get_name(): string {
		return 'apollo_hub_listing';
	}

	public function get_title(): string {
		return __( 'Apollo Hub Listing', 'apollo-elementor-pro' );
	}

	public function get_icon(): string {
		return 'eicon-sitemap';
	}

	protected function register_controls(): void {
		$this->add_content_section( [ 'per_page' => 6, 'columns' => 2 ] );

		$this->start_controls_section( 'section_options', [
			'label' => __( 'Options', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'hub_type', [
			'label'   => __( 'Hub type', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => '',
			'options' => [
				''        => __( 'All', 'apollo-elementor-pro' ),
				'venue'   => __( 'Venue', 'apollo-elementor-pro' ),
				'label'   => __( 'Label', 'apollo-elementor-pro' ),
				'agency'  => __( 'Agency', 'apollo-elementor-pro' ),
				'studio'  => __( 'Studio', 'apollo-elementor-pro' ),
			],
		] );

		$this->add_control( 'show_cover', [
			'label'        => __( 'Show cover image', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'animate_entrance', [
			'label'        => __( 'GSAP scroll entrance', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->end_controls_section();
		$this->add_shared_style_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$this->print_rest_config( 'hubs' );
		?>
		<div class="apollo-hub-listing apollo-widget<?php echo 'yes' === $s['animate_entrance'] ? ' apollo-gsap-trigger' : ''; ?>"
			data-per-page="<?php echo esc_attr( (string) $s['per_page'] ); ?>"
			data-columns="<?php echo esc_attr( $s['columns'] ); ?>"
			data-hub-type="<?php echo esc_attr( $s['hub_type'] ); ?>"
			data-show-cover="<?php echo esc_attr( $s['show_cover'] ); ?>">
			<div class="apollo-loading"><span></span></div>
		</div>
		<?php
	}
}
