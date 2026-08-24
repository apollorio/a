<?php
/**
 * Widget: DJ Card
 *
 * Shows a single DJ profile card hydrated from Apollo REST.
 *
 * @package Apollo\Elementor\Widgets
 */

declare(strict_types=1);

namespace Apollo\Elementor\Widgets;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DJCard extends Base {

	public function get_name(): string {
		return 'apollo_dj_card';
	}

	public function get_title(): string {
		return __( 'Apollo DJ Card', 'apollo-elementor-pro' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', [
			'label' => __( 'DJ Card', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'dj_user_id', [
			'label'       => __( 'DJ User ID', 'apollo-elementor-pro' ),
			'type'        => Controls_Manager::NUMBER,
			'description' => __( 'Leave 0 to use the current post author.', 'apollo-elementor-pro' ),
			'default'     => 0,
			'min'         => 0,
		] );

		$this->add_control( 'show_sounds', [
			'label'        => __( 'Show sound genres', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'show_stats', [
			'label'        => __( 'Show WoW / Fav stats', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'show_radio_btn', [
			'label'        => __( 'Show "On Air" radio button', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'no',
		] );

		$this->end_controls_section();
		$this->add_shared_style_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$this->print_rest_config( 'users' );
		?>
		<div class="apollo-dj-card apollo-widget"
			data-user-id="<?php echo esc_attr( (string) $s['dj_user_id'] ); ?>"
			data-show-sounds="<?php echo esc_attr( $s['show_sounds'] ); ?>"
			data-show-stats="<?php echo esc_attr( $s['show_stats'] ); ?>"
			data-show-radio="<?php echo esc_attr( $s['show_radio_btn'] ); ?>">
			<div class="apollo-loading"><span></span></div>
		</div>
		<?php
	}
}
